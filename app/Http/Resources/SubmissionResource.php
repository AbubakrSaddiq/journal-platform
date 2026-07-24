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
            'current_version' => [
                'id' => $this->currentVersion?->id,
                'version_number' => $this->currentVersion?->version_number,
                'uploaded_at' => $this->currentVersion?->uploaded_at->toIso8601String(),
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}