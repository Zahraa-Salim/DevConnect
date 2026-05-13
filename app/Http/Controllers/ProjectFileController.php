<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectFileController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $isMember = $project->members()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->exists();

        abort_unless($isMember || auth()->id() === $project->owner_id, 403);

        $files = ProjectFile::where('project_id', $project->id)
            ->with('uploader:id,name,avatar_url')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($file) {
                $file->size_formatted = $this->formatFileSize($file->file_size);
                $file->icon = $this->getMimeIcon($file->mime_type);
                return $file;
            });

        return response()->json($files);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $isMember = $project->members()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->exists();

        abort_unless($isMember || auth()->id() === $project->owner_id, 403);

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,md,csv,zip,rar,7z,mp4,mp3,json,xml,sql',
            ],
        ]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store("project-files/{$project->id}", 'local');

        $projectFile = ProjectFile::create([
            'project_id'  => $project->id,
            'uploaded_by' => auth()->id(),
            'file_name'   => $uploadedFile->getClientOriginalName(),
            'file_url'    => $path,
            'file_size'   => $uploadedFile->getSize(),
            'mime_type'   => $uploadedFile->getClientMimeType(),
        ]);

        $projectFile->load('uploader:id,name,avatar_url');
        $projectFile->size_formatted = $this->formatFileSize($projectFile->file_size);
        $projectFile->icon = $this->getMimeIcon($projectFile->mime_type);

        return response()->json($projectFile, 201);
    }

    public function download(Project $project, ProjectFile $file)
    {
        $isMember = $project->members()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->exists();

        abort_unless($isMember || auth()->id() === $project->owner_id, 403);
        abort_if($file->project_id !== $project->id, 404);

        return Storage::disk('local')->download($file->file_url, $file->file_name);
    }

    public function destroy(Project $project, ProjectFile $file): JsonResponse
    {
        abort_if($file->project_id !== $project->id, 404);

        abort_unless(
            auth()->id() === $file->uploaded_by || auth()->id() === $project->owner_id,
            403
        );

        Storage::disk('local')->delete($file->file_url);
        $file->delete();

        return response()->json(['success' => true]);
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    private function getMimeIcon(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/')                                                    => 'image',
            str_contains($mime, 'pdf')                                                          => 'pdf',
            str_contains($mime, 'word') || str_contains($mime, 'document')                     => 'doc',
            str_contains($mime, 'sheet') || str_contains($mime, 'excel')                       => 'spreadsheet',
            str_contains($mime, 'presentation') || str_contains($mime, 'powerpoint')           => 'presentation',
            str_contains($mime, 'zip') || str_contains($mime, 'rar') || str_contains($mime, '7z') => 'archive',
            str_contains($mime, 'video')                                                        => 'video',
            str_contains($mime, 'audio')                                                        => 'audio',
            str_starts_with($mime, 'text/')                                                     => 'text',
            default                                                                             => 'file',
        };
    }
}
