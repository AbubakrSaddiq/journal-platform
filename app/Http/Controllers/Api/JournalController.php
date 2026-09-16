<?php

namespace App\Http\Controllers\Api;

use App\Models\Journal;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

class JournalController extends BaseController
{
    /**
     * List all published journals (public).
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
     * List ALL journals for editor (including unpublished).
     */
    public function adminIndex()
    {
        $journals = Journal::with(['sections'])
            ->withCount('submissions')
            ->latest()
            ->get()
            ->map(fn($journal) => [
                'id' => $journal->id,
                'title' => $journal->title,
                'slug' => $journal->slug,
                'issn' => $journal->issn,
                'description' => $journal->description,
                'settings' => $journal->settings,
                'published_at' => $journal->published_at?->toIso8601String(),
                'is_published' => !is_null($journal->published_at),
                'submissions_count' => $journal->submissions_count,
                'sections' => $journal->sections->map(fn($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'slug' => $s->slug,
                    'description' => $s->description,
                    'sort_order' => $s->sort_order,
                ]),
            ]);

        return response()->json(['journals' => $journals]);
    }

    /**
     * Show single journal (public).
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
     * Show journal by slug (public).
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

    /**
     * Create new journal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:journals,title',
            'issn' => 'nullable|string|max:20|unique:journals,issn',
            'description' => 'nullable|string|max:2000',
            'settings' => 'nullable|array',
        ]);

        $journal = Journal::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'issn' => $validated['issn'] ?? null,
            'description' => $validated['description'] ?? null,
            'settings' => $validated['settings'] ?? [],
        ]);

        return response()->json([
            'message' => 'Journal created successfully',
            'journal' => $journal,
        ], 201);
    }

    /**
     * Update journal.
     */
    public function update(Request $request, Journal $journal)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:journals,title,' . $journal->id,
            'issn' => 'nullable|string|max:20|unique:journals,issn,' . $journal->id,
            'description' => 'nullable|string|max:2000',
            'settings' => 'nullable|array',
        ]);

        $journal->update([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'issn' => $validated['issn'] ?? null,
            'description' => $validated['description'] ?? null,
            'settings' => $validated['settings'] ?? $journal->settings,
        ]);

        return response()->json([
            'message' => 'Journal updated successfully',
            'journal' => $journal->fresh(),
        ]);
    }

    /**
     * Publish journal (make public).
     */
    public function publish(Journal $journal)
    {
        $journal->update(['published_at' => now()]);

        return response()->json([
            'message' => "{$journal->title} is now published",
        ]);
    }

    /**
     * Unpublish journal.
     */
    public function unpublish(Journal $journal)
    {
        $journal->update(['published_at' => null]);

        return response()->json([
            'message' => "{$journal->title} has been unpublished",
        ]);
    }

    /**
     * Delete journal.
     */
    public function destroy(Journal $journal)
    {
        if ($journal->submissions()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete a journal that has submissions.'
            ], 422);
        }

        $journal->delete();

        return response()->json([
            'message' => 'Journal deleted successfully',
        ]);
    }

    /**
     * Add section to journal.
     */
    public function addSection(Request $request, Journal $journal)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        $section = $journal->sections()->create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Section added successfully',
            'section' => $section,
        ], 201);
    }

    /**
     * Update section.
     */
    public function updateSection(Request $request, Journal $journal, Section $section)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        $section->update([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $section->sort_order,
        ]);

        return response()->json([
            'message' => 'Section updated successfully',
            'section' => $section->fresh(),
        ]);
    }

    /**
     * Delete section.
     */
    public function deleteSection(Journal $journal, Section $section)
    {
        if ($section->submissions()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete a section that has submissions.'
            ], 422);
        }

        $section->delete();

        return response()->json([
            'message' => 'Section deleted successfully',
        ]);
    }
}