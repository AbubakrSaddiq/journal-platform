<?php

namespace Database\Factories;

use App\Models\Journal;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->word();
        
        return [
            'journal_id' => Journal::factory(),
            'title' => $title,
            'slug' => str($title)->slug(),
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}