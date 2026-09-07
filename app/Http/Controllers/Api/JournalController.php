<?php

namespace App\Http\Controllers\Api;

use App\Models\Journal;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class JournalController extends BaseController
{
    /**
     * List all published journals.
     */
    public function index()
    {
        $journals = Journal::whereNotNull('published_at')
            ->with('sections')
            ->get()
            ->map(fn($journal) => [
                'id' => $journal->id,
                'title' => $journal->title,
                'slug' => $journal->slug,
                'issn' => $journal->issn,
                'description' => $journal->description,
                'sections' => $journal->sections->map(fn($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'slug' => $s->slug,
                ]),
            ]);

        return response()->json(['journals' => $journals]);
    }

    /**
     * Show a single journal.
     */
    public function show(Journal $journal)
    {
        return response()->json([
            'journal' => [
                'id' => $journal->id,
                'title' => $journal->title,
                'slug' => $journal->slug,
                'issn' => $journal->issn,
                'description' => $journal->description,
                'settings' => $journal->settings,
                'sections' => $journal->sections,
            ]
        ]);
    }

    /**
 * Show journal by slug.
 */
public function showBySlug(string $slug)
{
    $journal = Journal::where('slug', $slug)
        ->with(['sections', 'issues' => function ($query) {
            $query->whereNotNull('published_at')
                ->withCount('submissions')
                ->orderByDesc('volume')
                ->orderByDesc('issue_number');
        }])
        ->firstOrFail();

    return response()->json([
        'journal' => [
            'id' => $journal->id,
            'title' => $journal->title,
            'slug' => $journal->slug,
            'issn' => $journal->issn,
            'description' => $journal->description,
            'settings' => $journal->settings,
            'sections' => $journal->sections->map(fn($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'slug' => $s->slug,
                'description' => $s->description,
            ]),
            'issues' => $journal->issues->map(fn($i) => [
                'id' => $i->id,
                'volume' => $i->volume,
                'issue_number' => $i->issue_number,
                'label' => "Vol. {$i->volume}, No. {$i->issue_number}",
                'published_at' => $i->published_at?->toIso8601String(),
                'submissions_count' => $i->submissions_count,
            ]),
        ],
    ]);
    }
}