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
                'journal' => $this->submission->journal->title,
            ],
            'reviewer_id' => $this->reviewer_id,
            'status' => $this->status,
            'invited_at' => $this->invited_at->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'review' => $this->whenLoaded('reviews', function () {
                return [
                    'recommendation' => $this->reviews?->first()?->recommendation,
                    'comments_for_editor' => $this->reviews?->first()?->comments_for_editor,
                    'comments_for_author' => $this->reviews?->first()?->comments_for_author,
                    'submitted_at' => $this->reviews?->first()?->submitted_at?->toIso8601String(),
                ];
            }),
        ];
    }
}