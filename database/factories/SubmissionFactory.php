<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'journal_id' => Journal::factory(),
            'section_id' => Section::factory(),
            'author_id' => User::factory(),
            'title' => fake()->sentence(6),
            'abstract' => fake()->paragraph(5),
            'keywords' => fake()->words(5, asText: true),
            'cover_letter' => fake()->paragraph(3),
            'status' => 'submitted',
            'submitted_at' => now(),
        ];
    }
}