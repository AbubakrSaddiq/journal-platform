<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submission' => [
                'id' => $this->submission->id,
                'title' => $this->submission->title,
                'abstract' => $this->submission->abstract,
                'keywords' => $this->submission->keywords,
                'status' => $this->submission->status,
                'submitted_at' => $this->submission->submitted_at?->toIso8601String(),
                'journal' => $this->submission->journal->title,
                'section' => $this->submission->section?->title,
                'author' => $this->submission->author?->name,
                // ✅ Include current version + files for the reviewer
                'current_version' => $this->when(
                    $this->submission->currentVersion,
                    function () {
                        $version = $this->submission->currentVersion;

                        return [
                            'id' => $version->id,
                            'version_number' => $version->version_number,
                            'uploaded_at' => $version->uploaded_at?->toIso8601String(),
                            'files' => $version->files->map(fn ($f) => [
                                'id' => $f->id,
                                'original_filename' => $f->original_filename,
                                'file_type' => $f->file_type,
                                'file_size' => $f->file_size,
                                'file_role' => $f->file_role,
                            ]),
                        ];
                    }
                ),
            ],
            'reviewer_id' => $this->reviewer_id,
            'status' => $this->status,
            'invited_at' => $this->invited_at->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'review' => $this->whenLoaded('reviews', function () {
                $review = $this->reviews?->first();

                if (!$review) {
                    return null;
                }

                return [
                    'recommendation' => $review->recommendation,
                    'comments_for_editor' => $review->comments_for_editor,
                    'comments_for_author' => $review->comments_for_author,
                    'submitted_at' => $review->submitted_at?->toIso8601String(),
                ];
            }),
        ];
    }
}