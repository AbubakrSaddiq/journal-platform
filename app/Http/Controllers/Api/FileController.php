<?php

namespace App\Http\Controllers\Api;

use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class FileController extends BaseController
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload a new manuscript version.
     */
    public function uploadManuscript(Request $request, Submission $submission)
    {
        Gate::authorize('update', $submission);

        $request->validate([
            'manuscript' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'upload_notes' => 'nullable|string|max:500',
        ]);

        try {
            $version = $this->fileUploadService->uploadNewVersion(
                $submission,
                $request->file('manuscript'),
                auth()->id(),
                $request->input('upload_notes', '')
            );

            return response()->json([
                'message' => 'Manuscript uploaded successfully',
                'version' => [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'uploaded_at' => $version->uploaded_at,
                    'files' => $version->files->map(fn($f) => [
                        'id' => $f->id,
                        'original_filename' => $f->original_filename,
                        'file_type' => $f->file_type,
                        'file_size' => $f->file_size,
                        'file_role' => $f->file_role,
                    ]),
                ],
            ], Response::HTTP_CREATED);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Download a submission file.
     */
    public function download(Submission $submission, SubmissionFile $file)
    {
        Gate::authorize('view', $submission);

        try {
            $filePath = $this->fileUploadService->getFileForDownload($file);

            return Storage::disk('submissions')->download(
                $filePath,
                $file->original_filename
            );

        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * List all versions for a submission.
     */
    public function versions(Submission $submission)
    {
        Gate::authorize('view', $submission);

        $versions = $submission->versions()
            ->with('files', 'uploadedBy')
            ->orderBy('version_number')
            ->get()
            ->map(fn($version) => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'upload_notes' => $version->upload_notes,
                'uploaded_by' => $version->uploadedBy->name,
                'uploaded_at' => $version->uploaded_at,
                'files' => $version->files->map(fn($f) => [
                    'id' => $f->id,
                    'original_filename' => $f->original_filename,
                    'file_type' => $f->file_type,
                    'file_size' => $f->file_size,
                    'file_role' => $f->file_role,
                ]),
            ]);

        return response()->json(['versions' => $versions]);
    }
}