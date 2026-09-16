<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'journal' => [
            'id' => $this->journal->id,
            'title' => $this->journal->title,
            'slug' => $this->journal->slug,
        ],
        'section' => [
            'id' => $this->section->id,
            'title' => $this->section->title,
        ],
        'author' => [
            'id' => $this->author->id,
            'name' => $this->author->name,
            'email' => $this->author->email,
        ],
        'title' => $this->title,
        'abstract' => $this->abstract,
        'keywords' => $this->keywords,
        'status' => $this->status,
        'submitted_at' => $this->submitted_at->toIso8601String(),
        'current_version' => $this->when($this->currentVersion, [
            'id' => $this->currentVersion?->id,
            'version_number' => $this->currentVersion?->version_number,
            'uploaded_at' => $this->currentVersion?->uploaded_at?->toIso8601String(),
            'files' => $this->currentVersion?->files?->map(fn($f) => [
                'id' => $f->id,
                'original_filename' => $f->original_filename,
                'file_type' => $f->file_type,
                'file_size' => $f->file_size,
                'file_role' => $f->file_role,
            ]),
        ]),
        'issue' => $this->when(
        $this->relationLoaded('issues') && $this->issues->isNotEmpty(),
        function () {
            $issue = $this->issues->first();

            return [
                'id' => $issue->id,
                'volume' => $issue->volume,
                'issue_number' => $issue->issue_number,
                'label' => "Vol. {$issue->volume}, No. {$issue->issue_number}",
                'published_at' => $issue->published_at?->toIso8601String(),
                'is_published' => !is_null($issue->published_at),
            ];
        }
        ),
        'created_at' => $this->created_at->toIso8601String(),
        'updated_at' => $this->updated_at->toIso8601String(),
        'reviews_count' => $this->reviews()->count(),
        'review_invitations_count' => $this->reviewInvitations()->count(),
        'review_invitations_completed_count' => $this->reviewInvitations()
            ->where('status', 'completed')
            ->count(),
    ];
}
}