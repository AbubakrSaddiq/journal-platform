<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->id === $this->route('submission')->author_id;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|min:5|max:255',
            'abstract' => 'nullable|string|min:50|max:5000',
            'keywords' => 'nullable|string|max:500',
            'cover_letter' => 'nullable|string|max:2000',
        ];
    }
}