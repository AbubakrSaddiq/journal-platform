<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\SubmissionFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    protected array $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    protected int $maxFileSize = 10485760; // 10MB

    /**
     * Create a new version with multiple files (manuscript + supplementary)
     */
    public function createVersion(
        Submission $submission,
        array $files,
        int $uploadedById,
        string $notes = ''
    ): SubmissionVersion {
        // Validate all files before proceeding
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $this->validateFile($file);
            }
        }

        // Create version record
        $versionNumber = $submission->versions()->count() + 1;

        $version = SubmissionVersion::create([
            'submission_id' => $submission->id,
            'version_number' => $versionNumber,
            'uploaded_by_id' => $uploadedById,
            'upload_notes' => $notes,
            'uploaded_at' => now(),
        ]);

        // Store each file
        foreach ($files as $role => $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            // Determine file role
            $fileRole = 'manuscript';
            if (str_starts_with($role, 'supplementary')) {
                $fileRole = 'supplementary';
            }

            $filePath = $this->storeFile(
                $file,
                $submission->id,
                $versionNumber,
                $fileRole
            );

            SubmissionFile::create([
                'submission_version_id' => $version->id,
                'file_path' => $filePath,
                'original_filename' => $file->getClientOriginalName(),
                'file_type' => $file->getClientOriginalExtension(),
                'file_role' => $fileRole,
                'file_size' => $file->getSize(),
            ]);
        }

        // Update submission's current version
        $submission->update(['current_version_id' => $version->id]);

        return $version->load('files');
    }

    /**
     * Upload a new version of a submission (single file - legacy method)
     */
    public function uploadNewVersion(
        Submission $submission,
        UploadedFile $file,
        int $uploadedById,
        string $uploadNotes = ''
    ): SubmissionVersion {
        $this->validateFile($file);

        $versionNumber = $submission->versions()->count() + 1;

        $version = SubmissionVersion::create([
            'submission_id' => $submission->id,
            'version_number' => $versionNumber,
            'uploaded_by_id' => $uploadedById,
            'upload_notes' => $uploadNotes,
            'uploaded_at' => now(),
        ]);

        $filePath = $this->storeFile($file, $submission->id, $versionNumber);

        SubmissionFile::create([
            'submission_version_id' => $version->id,
            'file_path' => $filePath,
            'original_filename' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_role' => 'manuscript',
            'file_size' => $file->getSize(),
        ]);

        $submission->update(['current_version_id' => $version->id]);

        return $version->load('files');
    }

    /**
     * Upload supplementary file to an existing version
     */
    public function uploadSupplementaryFile(
        SubmissionVersion $version,
        UploadedFile $file,
        string $fileRole = 'supplementary'
    ): SubmissionFile {
        $this->validateFile($file);
        
        $filePath = $this->storeFile(
            $file,
            $version->submission_id,
            $version->version_number,
            $fileRole
        );

        return SubmissionFile::create([
            'submission_version_id' => $version->id,
            'file_path' => $filePath,
            'original_filename' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_role' => $fileRole,
            'file_size' => $file->getSize(),
        ]);
    }

    /**
     * Get file contents for download
     */
    public function getFile(SubmissionFile $file): string
    {
        if (!Storage::disk('submissions')->exists($file->file_path)) {
            throw new \RuntimeException("File not found: {$file->original_filename}");
        }

        return Storage::disk('submissions')->get($file->file_path);
    }

    /**
     * Get file path for download (legacy method)
     */
    public function getFileForDownload(SubmissionFile $file): string
    {
        return $this->getFile($file);
    }

    /**
     * Delete all files for a version
     */
    public function deleteVersionFiles(SubmissionVersion $version): void
    {
        foreach ($version->files as $file) {
            Storage::disk('submissions')->delete($file->file_path);
            $file->delete();
        }
    }

    /**
     * Store a single file
     */
    protected function storeFile(
        UploadedFile $file,
        int $submissionId,
        int $versionNumber,
        string $role = 'manuscript'
    ): string {
        $directory = "submissions/{$submissionId}/v{$versionNumber}";
        $filename = $role . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        return Storage::disk('submissions')->putFileAs(
            $directory,
            $file,
            $filename
        );
    }

    /**
     * Validate a file
     */
    protected function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException(
                'File size exceeds maximum allowed size of 10MB.'
            );
        }

        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new \InvalidArgumentException(
                'Invalid file type. Only PDF and Word documents are allowed.'
            );
        }
    }

    /**
     * Format file size for display
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}