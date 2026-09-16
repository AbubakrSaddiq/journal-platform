<?php

namespace App\Http\Controllers\Api;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class IssueController extends BaseController
{
    /**
     * Get all issues for a journal.
     */
    public function index(Journal $journal)
    {
        $issues = $journal->issues()
            ->withCount('submissions')
            ->orderByDesc('volume')
            ->orderByDesc('issue_number')
            ->get()
            ->map(fn($issue) => [
                'id' => $issue->id,
                'volume' => $issue->volume,
                'issue_number' => $issue->issue_number,
                'published_at' => $issue->published_at?->toIso8601String(),
                'is_published' => !is_null($issue->published_at),
                'submissions_count' => $issue->submissions_count,
                'label' => "Vol. {$issue->volume}, No. {$issue->issue_number}",
            ]);

        return response()->json(['issues' => $issues]);
    }

    /**
     * Create a new issue.
     */
    public function store(Request $request, Journal $journal)
    {
        $validated = $request->validate([
            'volume' => 'required|integer|min:1',
            'issue_number' => 'required|integer|min:1',
        ]);

        // Check for duplicate
        $exists = $journal->issues()
            ->where('volume', $validated['volume'])
            ->where('issue_number', $validated['issue_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => "Vol. {$validated['volume']}, No. {$validated['issue_number']} already exists."
            ], 422);
        }

        $issue = $journal->issues()->create($validated);

        return response()->json([
            'message' => 'Issue created successfully',
            'issue' => [
                'id' => $issue->id,
                'volume' => $issue->volume,
                'issue_number' => $issue->issue_number,
                'label' => "Vol. {$issue->volume}, No. {$issue->issue_number}",
                'is_published' => false,
            ],
        ], 201);
    }

    /**
     * Get a single issue with its submissions.
     */
    public function show(Journal $journal, Issue $issue)
    {
        $issue->load(['submissions' => function ($query) {
            $query->with(['author', 'section'])
                ->withPivot('page_number');
        }]);

        return response()->json([
            'issue' => [
                'id' => $issue->id,
                'volume' => $issue->volume,
                'issue_number' => $issue->issue_number,
                'label' => "Vol. {$issue->volume}, No. {$issue->issue_number}",
                'published_at' => $issue->published_at?->toIso8601String(),
                'is_published' => !is_null($issue->published_at),
                'submissions' => $issue->submissions->map(fn($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'author' => $s->author->name,
                    'section' => $s->section->title,
                    'page_number' => $s->pivot->page_number,
                    'status' => $s->status,
                ]),
            ],
        ]);
    }

    /**
     * Schedule a submission to an issue.
     */
   public function scheduleSubmission(Request $request, Journal $journal, Issue $issue)
    {
    $validated = $request->validate([
        'submission_id' => 'required|exists:submissions,id',
        'page_number' => 'nullable|string|max:20',
    ]);

    $submission = Submission::findOrFail($validated['submission_id']);

    if ($submission->journal_id !== $journal->id) {
        return response()->json([
            'message' => 'Submission does not belong to this journal.'
        ], 422);
    }

    //  Accept production (the actual pre-scheduling status)
    if (!in_array($submission->status, ['production', 'accepted'], true)) {
        return response()->json([
            'message' => 'Only submissions in production can be scheduled.'
        ], 422);
    }

    try {
        app(\App\Services\SubmissionService::class)->schedule(
            $submission,
            $issue->id,
            auth()->id()
        );
    } catch (\InvalidArgumentException $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }

    // Optional page number, set after the pivot row exists
    if (!empty($validated['page_number'])) {
        \App\Models\IssueSubmission::where('issue_id', $issue->id)
            ->where('submission_id', $submission->id)
            ->update(['page_number' => $validated['page_number']]);
    }

    return response()->json([
        'message' => 'Submission scheduled successfully',
    ]);
    }

    /**
     * Remove submission from issue.
     */
    public function removeSubmission(Journal $journal, Issue $issue, Submission $submission)
    {
        $issue->submissions()->detach($submission->id);
        $submission->update(['status' => 'accepted']);

        return response()->json([
            'message' => 'Submission removed from issue',
        ]);
    }

    /**
     * Publish an issue.
     */
   public function publish(Journal $journal, Issue $issue)
    {
        if ($issue->published_at) {
            return response()->json([
                'message' => 'Issue is already published.'
            ], 422);
        }

        if ($issue->submissions()->count() === 0) {
            return response()->json([
                'message' => 'Cannot publish an empty issue.'
            ], 422);
        }

        $userId = auth()->id();
        $submissionService = app(\App\Services\SubmissionService::class);

        DB::transaction(function () use ($issue, $userId, $submissionService) {
            $issue->update(['published_at' => now()]);

            $issue->submissions()->each(function ($submission) use ($userId, $submissionService) {
                if ($submission->status !== 'scheduled') {
                    return;
                }

                $submissionService->transitionTo(
                    $submission,
                    'published',
                    $userId,
                    "Published in Vol. {$issue->volume}, No. {$issue->issue_number}"
                );

                $submission->author->notify(
                    new \App\Notifications\SubmissionStatusChanged($submission, 'published')
                );
            });
        });

        return response()->json([
            'message' => "Vol. {$issue->volume}, No. {$issue->issue_number} published successfully",
        ]);
    }
}