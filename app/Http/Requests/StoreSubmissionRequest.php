<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            // ✅ Removed manuscript validation since it's handled separately
        ];
    }

    public function messages(): array
    {
        return [
            'journal_id.required' => 'Please select a journal.',
            'journal_id.exists' => 'The selected journal does not exist.',
            'section_id.required' => 'Please select a section.',
            'section_id.exists' => 'The selected section does not exist.',
            'title.required' => 'Please enter a title.',
            'title.min' => 'Title must be at least 5 characters.',
            'title.max' => 'Title must not exceed 255 characters.',
            'abstract.required' => 'Please enter an abstract.',
            'abstract.min' => 'Abstract must be at least 50 characters.',
            'abstract.max' => 'Abstract must not exceed 5000 characters.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}