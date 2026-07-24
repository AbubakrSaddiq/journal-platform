<?php

namespace App\Http\Controllers\Api;

use App\Models\ReviewInvitation;
use App\Services\ReviewService;
use App\Http\Resources\ReviewResource;
use Illuminate\Http\Response;

class ReviewController
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Get all review invitations for authenticated reviewer.
     */
    public function index()
    {
        $invitations = ReviewInvitation::where('reviewer_id', auth()->id())
            ->with(['submission', 'submission.journal', 'submission.section'])
            ->latest()
            ->paginate(15);

        return ReviewResource::collection($invitations);
    }

    /**
     * Show a single review invitation.
     */
    public function show(ReviewInvitation $reviewInvitation)
    {
        $this->authorize('view', $reviewInvitation);

        return new ReviewResource(
            $reviewInvitation->load(['submission', 'submission.journal'])
        );
    }

    /**
     * Reviewer accepts invitation.
     */
    public function acceptInvitation(ReviewInvitation $reviewInvitation)
    {
        $this->authorize('view', $reviewInvitation);

        $this->reviewService->acceptInvitation($reviewInvitation, auth()->id());

        return new ReviewResource($reviewInvitation->refresh());
    }

    /**
     * Reviewer declines invitation.
     */
    public function declineInvitation(ReviewInvitation $reviewInvitation)
    {
        $this->authorize('view', $reviewInvitation);

        $this->reviewService->declineInvitation($reviewInvitation, auth()->id());

        return new ReviewResource($reviewInvitation->refresh());
    }

    /**
     * Reviewer submits review.
     */
    public function submitReview(ReviewInvitation $reviewInvitation)
    {
        $this->authorize('view', $reviewInvitation);

        $validated = request()->validate([
            'comments_for_editor' => 'required|string|min:10|max:5000',
            'comments_for_author' => 'required|string|min:10|max:5000',
            'recommendation' => 'required|in:accept,minor_revision,major_revision,reject,resubmit',
        ]);

        $review = $this->reviewService->submitReview(
            $reviewInvitation,
            $validated['comments_for_editor'],
            $validated['comments_for_author'],
            $validated['recommendation'],
            auth()->id()
        );

        return new ReviewResource($reviewInvitation->refresh());
    }

    /**
     * Update a review (limited—can't change recommendation after submission).
     */
    public function update(ReviewInvitation $reviewInvitation)
    {
        return response()->json([
            'message' => 'Reviews cannot be updated after submission.'
        ], Response::HTTP_FORBIDDEN);
    }
}