<template>
  <!-- Backdrop -->
  <div class="fixed inset-0 z-40 bg-black/30" @click="$emit('close')" />

  <!-- Slide-out panel -->
  <div class="fixed right-0 top-0 bottom-0 z-50 w-full max-w-lg bg-white dark:bg-dc-dark-surface shadow-2xl flex flex-col overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-dc-surface dark:border-dc-dark-border">
      <h3 class="text-h3 font-medium">Task Details</h3>
      <div class="flex items-center gap-2">
        <Button v-if="isOwner" variant="ghost" size="sm" class="text-dc-danger" @click="confirmDelete = true">
          Delete
        </Button>
        <button @click="$emit('close')" class="text-dc-muted hover:text-dc-body p-1 rounded">✕</button>
      </div>
    </div>

    <!-- Delete confirmation -->
    <div v-if="confirmDelete" class="mx-4 mt-4 p-4 bg-dc-danger/10 border border-dc-danger/30 rounded-lg">
      <p class="text-small text-dc-danger font-medium mb-3">Delete this task? This cannot be undone.</p>
      <div class="flex gap-2">
        <Button variant="ghost" size="sm" class="text-dc-danger" @click="deleteTask">Delete</Button>
        <Button variant="ghost" size="sm" @click="confirmDelete = false">Cancel</Button>
      </div>
    </div>

    <div v-if="saveError" class="mx-4 mt-4 p-3 bg-dc-danger-tint border border-dc-danger/30 rounded-md text-small text-dc-danger">
      {{ saveError }}
    </div>

    <!-- Body -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4">
      <!-- Title -->
      <div>
        <label class="text-small font-medium text-dc-muted block mb-1">Title</label>
        <input
          v-model="form.title"
          class="w-full px-3 py-2 bg-dc-surface dark:bg-dc-dark-border border border-dc-muted rounded-md text-body focus:outline-none focus:ring-2 focus:ring-dc-primary"
          @blur="save"
        />
      </div>

      <!-- Description -->
      <div>
        <label class="text-small font-medium text-dc-muted block mb-1">Description</label>
        <Textarea v-model="form.description" :rows="3" placeholder="What needs to be done?" @blur="save" />
      </div>

      <!-- Row: Story Points + Status -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-small font-medium text-dc-muted block mb-1">Story Points</label>
          <Select
            v-model="form.story_points"
            :options="storyPointOptions"
            @change="save"
          />
        </div>
        <div>
          <label class="text-small font-medium text-dc-muted block mb-1">Status</label>
          <Select
            v-model="form.status"
            :options="statusOptions"
            @change="save"
          />
        </div>
      </div>

      <!-- Row: Priority + Energy -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-small font-medium text-dc-muted block mb-1">Priority</label>
          <Select
            v-model="form.priority"
            :options="priorityOptions"
            @change="save"
          />
        </div>
        <div>
          <label class="text-small font-medium text-dc-muted block mb-1">Energy</label>
          <Select
            v-model="form.energy"
            :options="energyOptions"
            @change="save"
          />
        </div>
      </div>

      <!-- Sprint assignment -->
      <div>
        <label class="text-small font-medium text-dc-muted block mb-1">Sprint</label>
        <Select
          v-model="form.sprint_id"
          :options="sprintOptions"
          @change="save"
        />
      </div>

      <!-- Assigned to -->
      <div>
        <label class="text-small font-medium text-dc-muted block mb-1">Assigned To</label>
        <Select
          v-model="form.assigned_to"
          :options="memberOptions"
          @change="save"
        />
      </div>

      <!-- Role tag -->
      <div>
        <label class="text-small font-medium text-dc-muted block mb-1">Role Tag</label>
        <Select
          v-model="form.role_tag"
          :options="roleOptions"
          @change="save"
        />
      </div>

      <!-- Due date -->
      <div>
        <label class="text-small font-medium text-dc-muted block mb-1">Due Date</label>
        <input
          type="date"
          v-model="form.due_date"
          class="w-full px-3 py-2 bg-dc-surface dark:bg-dc-dark-border border border-dc-muted rounded-md text-body focus:outline-none focus:ring-2 focus:ring-dc-primary"
          @change="save"
        />
      </div>

      <!-- Subtasks -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <label class="text-small font-medium text-dc-muted">Subtasks ({{ task.subtasks?.length ?? 0 }})</label>
          <button @click="showAddSubtask = !showAddSubtask" class="text-dc-primary text-small hover:underline">+ Add</button>
        </div>

        <!-- Add subtask form -->
        <div v-if="showAddSubtask" class="flex gap-2 mb-3">
          <input
            v-model="newSubtaskTitle"
            class="flex-1 px-3 py-1.5 bg-dc-surface dark:bg-dc-dark-border border border-dc-muted rounded-md text-small focus:outline-none focus:ring-2 focus:ring-dc-primary"
            placeholder="Subtask title"
            @keydown.enter="addSubtask"
            @keydown.escape="showAddSubtask = false"
          />
          <Button size="sm" variant="primary" @click="addSubtask" :disabled="!newSubtaskTitle.trim()">Add</Button>
        </div>

        <!-- Subtask list -->
        <div v-if="task.subtasks && task.subtasks.length > 0" class="space-y-1.5">
          <div
            v-for="subtask in task.subtasks"
            :key="subtask.id"
            class="flex items-center gap-2 p-2 bg-dc-surface/50 dark:bg-dc-dark-border/50 rounded"
          >
            <input
              type="checkbox"
              :checked="subtask.status === 'done'"
              @change="toggleSubtask(subtask)"
              class="rounded border-dc-muted text-dc-primary"
            />
            <span :class="['text-small flex-1', subtask.status === 'done' ? 'line-through text-dc-muted' : '']">
              {{ subtask.title }}
            </span>
            <button v-if="isOwner" @click="deleteSubtask(subtask)" class="text-dc-muted hover:text-dc-danger text-xs">✕</button>
          </div>
        </div>
        <p v-else-if="!showAddSubtask" class="text-small text-dc-muted">No subtasks yet.</p>
      </div>
    </div>

    <!-- Save indicator -->
    <div v-if="saving" class="px-4 py-2 border-t border-dc-surface dark:border-dc-dark-border flex items-center justify-center gap-2 text-small text-dc-muted">
      <ThreeDotBounce />
      <span>Saving...</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import Button from '@/Components/Button.vue'
import Select from '@/Components/Select.vue'
import Textarea from '@/Components/Textarea.vue'
import ThreeDotBounce from '@/Components/ThreeDotBounce.vue'
import { urls } from '@/lib/routes'

interface TaskAssignee { id: number; name: string; avatar_url: string | null }
interface SubTask { id: number; title: string; status: string }
interface Sprint { id: number; name: string; status: string }
interface Task {
  id: number
  project_id: number
  sprint_id?: number | null
  assigned_to?: number | null
  role_tag?: string | null
  title: string
  description?: string | null
  energy?: string | null
  priority?: string | null
  story_points?: number | null
  status: string
  due_date?: string | null
  assignee?: TaskAssignee | null
  subtasks?: SubTask[]
  sprint?: Sprint | null
}
interface Member { id: number; user: { id: number; name: string }; role: string }
interface ProjectRole { id: number; role_name: string }

const props = defineProps<{
  task: Task
  projectId: number
  isOwner: boolean
  members: Member[]
  roles: ProjectRole[]
  sprints: Sprint[]
}>()

const emit = defineEmits<{
  close: []
  updated: [task: Task]
  deleted: [taskId: number]
}>()

const saving = ref(false)
const saveError = ref('')
const confirmDelete = ref(false)
const showAddSubtask = ref(false)
const newSubtaskTitle = ref('')

const form = reactive({
  title: props.task.title,
  description: props.task.description ?? '',
  story_points: props.task.story_points?.toString() ?? '',
  status: props.task.status,
  priority: props.task.priority ?? 'medium',
  energy: props.task.energy ?? '',
  sprint_id: props.task.sprint_id?.toString() ?? '',
  assigned_to: props.task.assigned_to?.toString() ?? '',
  role_tag: props.task.role_tag ?? '',
  due_date: props.task.due_date ?? '',
})

watch(() => props.task, (t) => {
  form.title = t.title
  form.description = t.description ?? ''
  form.story_points = t.story_points?.toString() ?? ''
  form.status = t.status
  form.priority = t.priority ?? 'medium'
  form.energy = t.energy ?? ''
  form.sprint_id = t.sprint_id?.toString() ?? ''
  form.assigned_to = t.assigned_to?.toString() ?? ''
  form.role_tag = t.role_tag ?? ''
  form.due_date = t.due_date ?? ''
})

const storyPointOptions = [
  { value: '', label: '— No estimate' },
  { value: '1', label: '1 — Trivial' },
  { value: '2', label: '2 — Small' },
  { value: '3', label: '3 — Small feature' },
  { value: '5', label: '5 — Medium feature' },
  { value: '8', label: '8 — Large feature' },
  { value: '13', label: '13 — Epic' },
]

const statusOptions = [
  { value: 'todo', label: 'Todo' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'done', label: 'Done' },
]

const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
]

const energyOptions = [
  { value: '', label: '— Not set' },
  { value: 'quick_win', label: 'Quick Win (< 1h)' },
  { value: 'deep_work', label: 'Deep Work (2h+)' },
  { value: 'blocked', label: 'Blocked' },
]

const sprintOptions = [
  { value: '', label: '— Product Backlog' },
  ...props.sprints
    .filter(s => s.status !== 'completed')
    .map(s => ({ value: s.id.toString(), label: `${s.name} (${s.status})` })),
]

const memberOptions = [
  { value: '', label: '— Unassigned' },
  ...props.members.map(m => ({ value: m.user.id.toString(), label: `${m.user.name} (${m.role})` })),
]

const roleOptions = [
  { value: '', label: '— Any role' },
  ...props.roles.map(r => ({ value: r.role_name, label: r.role_name })),
]

function csrfToken(): string {
  return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''
}

async function save() {
  saving.value = true
  saveError.value = ''
  try {
    const body: Record<string, unknown> = {
      title: form.title,
      description: form.description || null,
      story_points: form.story_points ? Number(form.story_points) : null,
      status: form.status,
      priority: form.priority,
      energy: form.energy || null,
      sprint_id: form.sprint_id ? Number(form.sprint_id) : null,
      assigned_to: form.assigned_to ? Number(form.assigned_to) : null,
      role_tag: form.role_tag || null,
      due_date: form.due_date || null,
    }

    const res = await fetch(urls.projects.tasks.update(props.projectId, props.task.id), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify(body),
    })

    if (res.ok) {
      const data = await res.json()
      emit('updated', data.task)
    } else {
      saveError.value = 'Could not save task changes. Please try again.'
    }
  } catch {
    saveError.value = 'Could not save task changes. Please try again.'
  } finally {
    saving.value = false
  }
}

async function deleteTask() {
  saveError.value = ''
  try {
    const res = await fetch(urls.projects.tasks.destroy(props.projectId, props.task.id), {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
    })
    if (res.ok) {
      emit('deleted', props.task.id)
    } else {
      saveError.value = 'Could not delete this task. Please try again.'
    }
  } catch {
    saveError.value = 'Could not delete this task. Please try again.'
  }
}

async function addSubtask() {
  if (!newSubtaskTitle.value.trim()) return

  saveError.value = ''
  try {
    const res = await fetch(urls.projects.tasks.store(props.projectId), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({
        title: newSubtaskTitle.value.trim(),
        parent_task_id: props.task.id,
        status: 'todo',
      }),
    })

    if (!res.ok) {
      saveError.value = 'Could not add the subtask. Please try again.'
      return
    }

    // Re-fetch the task to get updated subtasks
    await refreshTask('Could not refresh subtasks. Please reload the panel.')
    newSubtaskTitle.value = ''
    showAddSubtask.value = false
  } catch {
    saveError.value = 'Could not add the subtask. Please try again.'
  }
}

async function toggleSubtask(subtask: SubTask) {
  const newStatus = subtask.status === 'done' ? 'todo' : 'done'
  saveError.value = ''
  try {
    const res = await fetch(urls.projects.tasks.update(props.projectId, subtask.id), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({ status: newStatus }),
    })

    if (res.ok) {
      await refreshTask('Could not refresh subtasks. Please reload the panel.')
    } else {
      saveError.value = 'Could not update the subtask. Please try again.'
    }
  } catch {
    saveError.value = 'Could not update the subtask. Please try again.'
  }
}

async function deleteSubtask(subtask: SubTask) {
  saveError.value = ''
  try {
    const res = await fetch(urls.projects.tasks.destroy(props.projectId, subtask.id), {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
    })
    if (res.ok) {
      await refreshTask('Could not refresh subtasks. Please reload the panel.')
    } else {
      saveError.value = 'Could not delete the subtask. Please try again.'
    }
  } catch {
    saveError.value = 'Could not delete the subtask. Please try again.'
  }
}

async function refreshTask(errorMessage: string) {
    const taskRes = await fetch(urls.projects.tasks.show(props.projectId, props.task.id), {
      headers: { 'Accept': 'application/json' },
    })
    if (taskRes.ok) {
      const taskData = await taskRes.json()
      emit('updated', taskData.task)
      return
    }

    saveError.value = errorMessage
}
</script>
