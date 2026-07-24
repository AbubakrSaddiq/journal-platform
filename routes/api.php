<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\ReviewController;

// Public routes
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    Route::apiResource('submissions', SubmissionController::class);
    Route::post('submissions/{submission}/send-to-review', [SubmissionController::class, 'sendToReview']);
    Route::post('submissions/{submission}/request-revision', [SubmissionController::class, 'requestRevision']);
    Route::post('submissions/{submission}/accept', [SubmissionController::class, 'accept']);
    Route::post('submissions/{submission}/reject', [SubmissionController::class, 'reject']);

    Route::apiResource('reviews', ReviewController::class)->only(['index', 'show', 'update']);
    Route::post('reviews/{reviewInvitation}/accept', [ReviewController::class, 'acceptInvitation']);
    Route::post('reviews/{reviewInvitation}/decline', [ReviewController::class, 'declineInvitation']);
    Route::post('reviews/{reviewInvitation}/submit', [ReviewController::class, 'submitReview']);
});