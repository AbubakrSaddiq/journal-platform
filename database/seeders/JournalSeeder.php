<?php

namespace Database\Seeders;

use App\Models\Journal;
use App\Models\Section;
use Illuminate\Database\Seeder;

class JournalSeeder extends Seeder
{
    public function run(): void
    {
        $journal = Journal::updateOrCreate(
            ['slug' => 'journal-of-science'],
            [
                'title' => 'Journal of Science',
                'issn' => '1234-5678',
                'description' => 'A premier journal for scientific research.',
                'settings' => [
                    'review_period_days' => 30,
                    'allow_author_suggestions' => true,
                    'blind_review' => true,
                ],
                'published_at' => now(),
            ]
        );

        // Create sections
        $sections = [
            ['title' => 'Original Research', 'slug' => 'original-research', 'sort_order' => 1],
            ['title' => 'Review Articles', 'slug' => 'review-articles', 'sort_order' => 2],
            ['title' => 'Case Studies', 'slug' => 'case-studies', 'sort_order' => 3],
            ['title' => 'Letters', 'slug' => 'letters', 'sort_order' => 4],
        ];

        foreach ($sections as $section) {
            Section::updateOrCreate(
                ['journal_id' => $journal->id, 'slug' => $section['slug']],
                array_merge($section, ['journal_id' => $journal->id])
            );
        }

        $this->command->info('Journal and sections created successfully.');
    }
}