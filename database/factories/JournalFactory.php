<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class JournalFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(3, asText: true);
        
        return [
            'title' => $title,
            'slug' => str($title)->slug(),
            'issn' => fake()->isbn10(),
            'description' => fake()->paragraph(),
            'settings' => [],
        ];
    }
}