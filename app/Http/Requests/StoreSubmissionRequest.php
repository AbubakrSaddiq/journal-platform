<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'journal_id' => 'required|exists:journals,id',
            'section_id' => 'required|exists:sections,id',
            'title' => 'required|string|min:5|max:255',
            'abstract' => 'required|string|min:50|max:5000',
            'keywords' => 'nullable|string|max:500',
            'cover_letter' => 'nullable|string|max:2000',
            'manuscript' => 'required|file|mimes:pdf,docx,doc|max:10240', // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Title must be at least 5 characters.',
            'abstract.min' => 'Abstract must be at least 50 characters.',
            'manuscript.required' => 'Please upload your manuscript.',
            'manuscript.mimes' => 'Manuscript must be PDF or Word document.',
            'manuscript.max' => 'Manuscript must not exceed 10MB.',
        ];
    }
}