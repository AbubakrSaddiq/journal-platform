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

    protected int $maxFileSize = 10485760;

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

    public function uploadSupplementaryFile(
        SubmissionVersion $version,
        UploadedFile $file,
        string $fileRole = 'supplementary'
    ): SubmissionFile {
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

    public function getFileForDownload(SubmissionFile $file): string
    {
        if (!Storage::disk('submissions')->exists($file->file_path)) {
            throw new \RuntimeException("File not found: {$file->original_filename}");
        }

        return $file->file_path;
    }

    public function deleteVersionFiles(SubmissionVersion $version): void
    {
        foreach ($version->files as $file) {
            Storage::disk('submissions')->delete($file->file_path);
            $file->delete();
        }
    }

    protected function storeFile(
        UploadedFile $file,
        int $submissionId,
        int $versionNumber,
        string $role = 'manuscript'
    ): string {
        $directory = "submissions/{$submissionId}/v{$versionNumber}";
        $filename = $role . '_' . time() . '.' . $file->getClientOriginalExtension();

        return Storage::disk('submissions')->putFileAs(
            $directory,
            $file,
            $filename
        );
    }

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
}