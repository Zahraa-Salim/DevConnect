<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Services\AiTaskBreakdownService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $isMember = $project->members()->where('user_id', auth()->id())->where('status', 'active')->exists();
        $isOwner = auth()->id() === $project->owner_id;
        abort_unless($isMember || $isOwner, 403);

        $query = Task::where('project_id', $project->id)->with(['assignee:id,name,avatar_url', 'sprint:id,name,status']);

        // Filters
        if ($request->filled('role_tag')) {
            $query->where('role_tag', $request->query('role_tag'));
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->query('assigned_to'));
        }
        if ($request->filled('energy')) {
            $query->where('energy', $request->query('energy'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $sprintParam = $request->query('sprint_id');

        if ($sprintParam === 'backlog' || $sprintParam === 'null' || $sprintParam === null) {
            // Product backlog: tasks with no sprint assigned
            $backlog = (clone $query)->whereNull('sprint_id')
                ->orderByRaw("FIELD(priority,'high','medium','low')")
                ->orderBy('position')
                ->get()
                ->map(fn (Task $t) => $this->formatTask($t));

            return response()->json([
                'todo' => [],
                'in_progress' => [],
                'done' => [],
                'backlog' => $backlog,
            ]);
        }

        // Sprint tasks grouped by status
        $sprintQuery = (clone $query)->where('sprint_id', $sprintParam)->orderBy('position');

        $tasks = $sprintQuery->get();

        return response()->json([
            'todo' => $tasks->where('status', 'todo')->values()->map(fn (Task $t) => $this->formatTask($t)),
            'in_progress' => $tasks->where('status', 'in_progress')->values()->map(fn (Task $t) => $this->formatTask($t)),
            'done' => $tasks->where('status', 'done')->values()->map(fn (Task $t) => $this->formatTask($t)),
            'backlog' => [],
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $isMember = $project->members()->where('user_id', auth()->id())->where('status', 'active')->exists();
        $isOwner = auth()->id() === $project->owner_id;
        abort_unless($isMember || $isOwner, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'parent_task_id' => ['nullable', 'exists:tasks,id'],
            'role_tag' => ['nullable', 'string', 'max:80'],
            'energy' => ['nullable', 'in:quick_win,deep_work,blocked'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'status' => ['nullable', 'in:todo,in_progress,done'],
            'story_points' => ['nullable', 'integer', 'in:1,2,3,5,8,13'],
            'sprint_id' => ['nullable', 'exists:sprints,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $position = Task::where('project_id', $project->id)
            ->where('status', $validated['status'] ?? 'todo')
            ->when(isset($validated['sprint_id']), fn ($q) => $q->where('sprint_id', $validated['sprint_id']))
            ->when(!isset($validated['sprint_id']), fn ($q) => $q->whereNull('sprint_id'))
            ->max('position') + 1;

        $task = Task::create([
            ...$validated,
            'project_id' => $project->id,
            'status' => $validated['status'] ?? 'todo',
            'position' => $position,
        ]);

        return response()->json(['task' => $this->formatTask($task->load(['assignee:id,name,avatar_url', 'subtasks', 'sprint:id,name,status']))], 201);
    }

    public function show(Project $project, Task $task): JsonResponse
    {
        $isMember = $project->members()->where('user_id', auth()->id())->where('status', 'active')->exists();
        $isOwner = auth()->id() === $project->owner_id;
        abort_unless($isMember || $isOwner, 403);
        abort_unless($task->project_id === $project->id, 404);

        $task->load(['assignee:id,name,avatar_url', 'subtasks.assignee:id,name,avatar_url', 'sprint:id,name,status']);

        return response()->json(['task' => $this->formatTask($task)]);
    }

    public function update(Request $request, Project $project, Task $task): JsonResponse
    {
        $isMember = $project->members()->where('user_id', auth()->id())->where('status', 'active')->exists();
        $isOwner = auth()->id() === $project->owner_id;
        abort_unless($isMember || $isOwner, 403);
        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'role_tag' => ['nullable', 'string', 'max:80'],
            'energy' => ['nullable', 'in:quick_win,deep_work,blocked'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'status' => ['nullable', 'in:todo,in_progress,done'],
            'story_points' => ['nullable', 'integer', 'in:1,2,3,5,8,13'],
            'sprint_id' => ['nullable', 'exists:sprints,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        // Handle completed_at
        if (isset($validated['status'])) {
            if ($validated['status'] === 'done' && $task->status !== 'done') {
                $validated['completed_at'] = now();
            } elseif ($validated['status'] !== 'done' && $task->status === 'done') {
                $validated['completed_at'] = null;
            }
        }

        $task->update($validated);

        return response()->json(['task' => $this->formatTask($task->fresh()->load(['assignee:id,name,avatar_url', 'subtasks', 'sprint:id,name,status']))]);
    }

    public function destroy(Project $project, Task $task): JsonResponse
    {
        abort_unless(auth()->id() === $project->owner_id, 403);
        abort_unless($task->project_id === $project->id, 404);

        $task->delete();

        return response()->json(['message' => 'Task deleted.']);
    }

    public function move(Request $request, Project $project, Task $task): JsonResponse
    {
        $isMember = $project->members()->where('user_id', auth()->id())->where('status', 'active')->exists();
        $isOwner = auth()->id() === $project->owner_id;
        abort_unless($isMember || $isOwner, 403);
        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:todo,in_progress,done'],
            'position' => ['required', 'integer', 'min:0'],
            'sprint_id' => ['nullable', 'exists:sprints,id'],
        ]);

        $data = ['status' => $validated['status'], 'position' => $validated['position']];

        if (array_key_exists('sprint_id', $validated)) {
            $data['sprint_id'] = $validated['sprint_id'];
        }

        if ($validated['status'] === 'done' && $task->status !== 'done') {
            $data['completed_at'] = now();
        } elseif ($validated['status'] !== 'done' && $task->status === 'done') {
            $data['completed_at'] = null;
        }

        $task->update($data);

        return response()->json(['task' => $this->formatTask($task->fresh())]);
    }

    public function assignToSprint(Request $request, Project $project, Task $task): JsonResponse
    {
        $isMember = $project->members()->where('user_id', auth()->id())->where('status', 'active')->exists();
        $isOwner = auth()->id() === $project->owner_id;
        abort_unless($isMember || $isOwner, 403);
        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'sprint_id' => ['required', 'exists:sprints,id'],
        ]);

        $sprint = Sprint::findOrFail($validated['sprint_id']);
        abort_if($sprint->status === Sprint::STATUS_COMPLETED, 400, 'Cannot assign tasks to a completed sprint.');

        $task->update([
            'sprint_id' => $sprint->id,
            'status' => 'todo',
        ]);

        return response()->json(['task' => $this->formatTask($task->fresh())]);
    }

    public function removeFromSprint(Project $project, Task $task): JsonResponse
    {
        $isMember = $project->members()->where('user_id', auth()->id())->where('status', 'active')->exists();
        $isOwner = auth()->id() === $project->owner_id;
        abort_unless($isMember || $isOwner, 403);
        abort_unless($task->project_id === $project->id, 404);

        $task->update(['sprint_id' => null]);

        return response()->json(['task' => $this->formatTask($task->fresh())]);
    }

    public function generateAiTasks(Request $request, Project $project, AiTaskBreakdownService $service): JsonResponse
    {
        abort_unless(auth()->id() === $project->owner_id, 403);

        // Rate limit: 3 per project per day
        $todayCount = AiUsageLog::where('project_id', $project->id)
            ->where('feature', AiUsageLog::FEATURE_TASK_GEN)
            ->whereDate('created_at', today())
            ->where('status', AiUsageLog::STATUS_SUCCESS)
            ->count();

        if ($todayCount >= 3) {
            return response()->json([
                'success' => false,
                'error' => 'You have reached the limit of 3 AI task generations per project per day.',
            ], 429);
        }

        $startTime = microtime(true);

        try {
            $result = $service->generate($project);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Save generated tasks to product backlog (sprint_id = null)
            $tasks = [];
            $position = Task::where('project_id', $project->id)->whereNull('sprint_id')->max('position') ?? 0;

            foreach ($result['tasks'] as $taskData) {
                $position++;
                $task = Task::create([
                    'project_id' => $project->id,
                    'sprint_id' => null,
                    'title' => substr($taskData['title'] ?? 'Untitled task', 0, 200),
                    'description' => $taskData['description'] ?? null,
                    'role_tag' => isset($taskData['role_tag']) ? substr($taskData['role_tag'], 0, 80) : null,
                    'energy' => in_array($taskData['energy'] ?? '', ['quick_win', 'deep_work', 'blocked']) ? $taskData['energy'] : null,
                    'priority' => in_array($taskData['priority'] ?? '', ['low', 'medium', 'high']) ? $taskData['priority'] : 'medium',
                    'story_points' => in_array((int) ($taskData['story_points'] ?? 0), [1, 2, 3, 5, 8, 13]) ? (int) $taskData['story_points'] : null,
                    'status' => 'todo',
                    'position' => $position,
                ]);
                $tasks[] = $this->formatTask($task);
            }

            AiUsageLog::create([
                'user_id' => auth()->id(),
                'project_id' => $project->id,
                'feature' => AiUsageLog::FEATURE_TASK_GEN,
                'model' => 'claude-sonnet-4-20250514',
                'prompt_tokens' => $result['usage']['input_tokens'] ?? 0,
                'completion_tokens' => $result['usage']['output_tokens'] ?? 0,
                'latency_ms' => $latencyMs,
                'status' => AiUsageLog::STATUS_SUCCESS,
                'created_at' => now(),
            ]);

            return response()->json(['success' => true, 'tasks' => $tasks]);
        } catch (\Exception $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            AiUsageLog::create([
                'user_id' => auth()->id(),
                'project_id' => $project->id,
                'feature' => AiUsageLog::FEATURE_TASK_GEN,
                'model' => 'claude-sonnet-4-20250514',
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'latency_ms' => $latencyMs,
                'status' => AiUsageLog::STATUS_ERROR,
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            return response()->json(['success' => false, 'error' => 'Failed to generate tasks. Please try again.'], 500);
        }
    }

    private function formatTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'project_id' => $task->project_id,
            'sprint_id' => $task->sprint_id,
            'assigned_to' => $task->assigned_to,
            'parent_task_id' => $task->parent_task_id,
            'role_tag' => $task->role_tag,
            'title' => $task->title,
            'description' => $task->description,
            'energy' => $task->energy,
            'priority' => $task->priority,
            'story_points' => $task->story_points,
            'status' => $task->status,
            'position' => $task->position,
            'due_date' => $task->due_date?->toDateString(),
            'completed_at' => $task->completed_at,
            'created_at' => $task->created_at,
            'assignee' => $task->relationLoaded('assignee') ? $task->assignee : null,
            'sprint' => $task->relationLoaded('sprint') ? $task->sprint : null,
            'subtasks' => $task->relationLoaded('subtasks') ? $task->subtasks->map(fn (Task $s) => $this->formatTask($s)) : [],
        ];
    }
}
