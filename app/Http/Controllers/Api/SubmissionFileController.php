<?php

namespace App\Http\Controllers\Api;

use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Services\FileUploadService;
use App\Http\Resources\SubmissionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Gate;

class SubmissionFileController extends BaseController
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload a new version of a submission.
     */
    public function upload(Request $request, Submission $submission)
    {
        Gate::authorize('update', $submission);

        // Validate the upload
        $request->validate([
            'manuscript' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'supplementary.*' => 'nullable|file|mimes:pdf,doc,docx,zip|max:20480',
            'upload_notes' => 'nullable|string|max:1000',
        ]);

        // Build files array
        $files = ['manuscript' => $request->file('manuscript')];

        // Add supplementary files if provided
        if ($request->hasFile('supplementary')) {
            foreach ($request->file('supplementary') as $index => $file) {
                $files["supplementary_{$index}"] = $file;
            }
        }

        $version = $this->fileUploadService->createVersion(
            submission: $submission,
            files: $files,
            uploadedById: auth()->id(),
            notes: $request->input('upload_notes')
        );

        return response()->json([
            'message' => 'Files uploaded successfully',
            'version' => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'uploaded_at' => $version->uploaded_at,
                'files' => $version->files->map(fn($f) => [
                    'id' => $f->id,
                    'original_filename' => $f->original_filename,
                    'file_type' => $f->file_type,
                    'file_role' => $f->file_role,
                    'file_size' => $this->fileUploadService->formatFileSize($f->file_size),
                ]),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Download a submission file securely.
     */
    public function download(Submission $submission, SubmissionFile $file)
    {
        Gate::authorize('view', $submission);

        // Ensure file belongs to this submission
        if ($file->submissionVersion->submission_id !== $submission->id) {
            abort(403, 'File does not belong to this submission.');
        }

        $contents = $this->fileUploadService->getFile($file);

        return response($contents, 200, [
            'Content-Type' => 'application/' . $file->file_type,
            'Content-Disposition' => "attachment; filename=\"{$file->original_filename}\"",
        ]);
    }

    /**
     * List all versions and files for a submission.
     */
    public function index(Submission $submission)
    {
        Gate::authorize('view', $submission);

        $versions = $submission->versions()
            ->with('files', 'uploadedBy')
            ->orderBy('version_number')
            ->get()
            ->map(fn($version) => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'uploaded_by' => $version->uploadedBy->name,
                'upload_notes' => $version->upload_notes,
                'uploaded_at' => $version->uploaded_at,
                'files' => $version->files->map(fn($f) => [
                    'id' => $f->id,
                    'original_filename' => $f->original_filename,
                    'file_type' => $f->file_type,
                    'file_role' => $f->file_role,
                    'file_size' => $this->fileUploadService->formatFileSize($f->file_size),
                ]),
            ]);

        return response()->json(['versions' => $versions]);
    }
}