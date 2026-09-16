<?php

namespace App\Http\Controllers\Api;

use App\Models\Submission;
use App\Services\SubmissionService;
use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Requests\UpdateSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Routing\Controller as BaseController;

class SubmissionController extends BaseController
{
    protected SubmissionService $submissionService;

    public function __construct(SubmissionService $submissionService)
    {
        $this->submissionService = $submissionService;
    }

    /**
     * Get all submissions for authenticated user.
     * Authors see their own, editors see journal's, admins see all.
     */
   public function index()
    {
    if (!auth('sanctum')->check()) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $user = auth()->user();

    $query = Submission::with(['author', 'journal', 'section', 'currentVersion', 'issues'])
        ->latest();

    $isEditor = $user->roles()
        ->whereIn('slug', ['editor', 'managing_editor', 'admin'])
        ->exists();

    // Editors/admins see everything; authors see only their own
    if (!$isEditor) {
        $query->where('author_id', $user->id);
    }

    $submissions = $query->paginate(15);

    return SubmissionResource::collection($submissions);
    }

    /**
     * Show a single submission.
     */
    public function show(Submission $submission)
    {
        Gate::authorize('view', $submission);

        return new SubmissionResource(
            $submission->load(['author', 'journal', 'section', 'versions', 'reviews', 'editorialDecisions'])
        );
    }

    /**
     * Store a new submission.
     */
    public function store(StoreSubmissionRequest $request)
    {
        $submission = Submission::create([
            'journal_id' => $request->journal_id,
            'section_id' => $request->section_id,
            'author_id' => auth()->id(),
            'title' => $request->title,
            'abstract' => $request->abstract,
            'keywords' => $request->keywords,
            'cover_letter' => $request->cover_letter,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Fire notification to author
        $submission->author->notify(
            new \App\Notifications\SubmissionReceived(
                $submission->load('journal')
            )
        );

        // Log to audit trail - FIXED: added parentheses to auth()->id()
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(), // ✅ Fixed: Added parentheses
            'submission_id' => $submission->id,
            'action' => 'submission_created',
            'changes' => ['status' => 'submitted'],
            'timestamp' => now()
        ]);

        return new SubmissionResource($submission->load(['journal', 'section', 'author']));
    }

    /**
     * Update submission metadata (only before review).
     */
    public function update(UpdateSubmissionRequest $request, Submission $submission)
    {
        Gate::authorize('update', $submission);

        // Only allow updates before review starts
        if (!in_array($submission->status, ['submitted', 'editorial_review'])) {
            return response()->json([
                'message' => 'Cannot update submission after editorial review begins'
            ], Response::HTTP_FORBIDDEN);
        }

        $submission->update($request->validated());

        return new SubmissionResource($submission);
    }

    /**
     * Send submission to peer review (editor action).
     */
    public function sendToReview(Submission $submission)
    {
        Gate::authorize('sendToReview', $submission);

        $this->submissionService->sendToReview($submission, auth()->id());

        return new SubmissionResource($submission->refresh());
    }

    /**
     * Request revision from author (editor action).
     */
    public function requestRevision(Submission $submission)
    {
        Gate::authorize('requestRevision', $submission);

        $revisionType = request()->input('revision_type', 'minor'); // minor or major

        $this->submissionService->requestRevision($submission, $revisionType, auth()->id());

        return new SubmissionResource($submission->refresh());
    }

    /**
     * Accept submission (editor action).
     */
    public function accept(Submission $submission)
    {
        Gate::authorize('accept', $submission);

        $this->submissionService->accept($submission, auth()->id());

        return new SubmissionResource($submission->refresh());
    }

    /**
     * Send accepted submission to editing (editor action).
     */
    public function sendToEditing(Submission $submission)
    {
        Gate::authorize('sendToEditing', $submission);

        $this->submissionService->sendToEditing($submission, auth()->id());

        return new SubmissionResource($submission->refresh());
    }

    /**
 * Send edited submission to production (editor action).
 */
public function sendToProduction(Submission $submission)
{
    Gate::authorize('sendToProduction', $submission);

    $this->submissionService->sendToProduction($submission, auth()->id());

    return new SubmissionResource($submission->refresh());
}

    /**
     * Schedule submission into an issue for publication (managing editor action).
     */
    public function schedule(Submission $submission)
    {
    Gate::authorize('schedule', $submission);

    $validated = request()->validate([
        'issue_id' => 'required|exists:issues,id',
    ]);

    try {
        $this->submissionService->schedule(
            $submission,
            (int) $validated['issue_id'],
            auth()->id()
        );
    } catch (\InvalidArgumentException $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }

    return new SubmissionResource(
        $submission->refresh()->load(['journal', 'section', 'author', 'issues'])
    );
    }

/**
 * Publish submission (managing editor action).
 */
public function publish(Submission $submission)
{
    Gate::authorize('publish', $submission);

    $this->submissionService->publish($submission, auth()->id());

    return new SubmissionResource($submission->refresh());
}

    /**
     * Reject submission (editor action).
     */
    public function reject(Submission $submission)
    {
        Gate::authorize('reject', $submission);

        $reason = request()->input('reason', 'Submission does not meet journal standards');

        $this->submissionService->reject($submission, auth()->id(), $reason);

        return new SubmissionResource($submission->refresh());
    }
}