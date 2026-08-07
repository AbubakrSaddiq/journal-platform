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

        $query = Submission::with(['author', 'journal', 'section', 'currentVersion'])
            ->latest();

        // Filter by user role (simplified—real implementation would use policies)
        if ($user->roles()->where('slug', 'author')->exists()) {
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