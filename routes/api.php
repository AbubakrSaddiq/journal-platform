<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\SubmissionFileController; // ✅ Add this import
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\ArticleController;


// Health check (no rate limit)
Route::get('/health', fn() => response()->json([
    'status' => 'ok',
    'version' => '1.0',
    'timestamp' => now()->toIso8601String(),
]));

// Auth routes (strict rate limiting)
Route::middleware('throttle:auth')->prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Public journal browsing (general rate limit)
Route::middleware('throttle:api')->group(function () {
    Route::get('journals', [JournalController::class, 'index']);
    Route::get('journals/slug/{slug}', [JournalController::class, 'showBySlug']);
    Route::get('journals/{journal}', [JournalController::class, 'show']);
    Route::get('articles/{submission}', [ArticleController::class, 'show']);
});

// Protected routes
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Article download
    Route::get('articles/{submission}/download', [ArticleController::class, 'download']);

    // Auth management
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Submissions (with submission rate limit on store)
    Route::get('submissions', [SubmissionController::class, 'index']);
    Route::get('submissions/{submission}', [SubmissionController::class, 'show']);
    Route::patch('submissions/{submission}', [SubmissionController::class, 'update']);

    Route::middleware('throttle:submissions')->group(function () {
        Route::post('submissions', [SubmissionController::class, 'store']);
    });

    // ✅ FIXED: Use SubmissionFileController for file operations
    Route::prefix('submissions/{submission}')->group(function () {
        Route::post('send-to-review', [SubmissionController::class, 'sendToReview']);
        Route::post('request-revision', [SubmissionController::class, 'requestRevision']);
        Route::post('accept', [SubmissionController::class, 'accept']);
        Route::post('reject', [SubmissionController::class, 'reject']);
        
        // ✅ Fixed file upload routes - using SubmissionFileController
        Route::post('upload', [SubmissionFileController::class, 'upload']);
        Route::get('versions', [SubmissionFileController::class, 'index']);
        Route::get('files/{file}/download', [SubmissionFileController::class, 'download']);
        
        Route::post('invite-reviewer', [ReviewController::class, 'inviteReviewer']);
    });

    // Reviews
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::get('{reviewInvitation}', [ReviewController::class, 'show']);
        Route::post('{reviewInvitation}/accept', [ReviewController::class, 'acceptInvitation']);
        Route::post('{reviewInvitation}/decline', [ReviewController::class, 'declineInvitation']);
        Route::post('{reviewInvitation}/submit', [ReviewController::class, 'submitReview']);
    });

    // Admin routes
    Route::prefix('admin')->middleware('throttle:api')->group(function () {
        Route::get('users', [AdminController::class, 'users']);
        Route::post('users/{user}/roles', [AdminController::class, 'assignRole']);
        Route::delete('users/{user}/roles/{roleSlug}', [AdminController::class, 'removeRole']);
    });

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

    // Profile
    Route::get('profile', [ProfileController::class, 'show']);
    Route::post('profile/update', [ProfileController::class, 'update']);
    Route::post('profile/password', [ProfileController::class, 'updatePassword']);

    // Issues
    Route::prefix('journals/{journal}/issues')->group(function() {
        Route::get('/', [IssueController::class, 'index']);
        Route::post('/', [IssueController::class, 'store']);
        Route::get('{issue}', [IssueController::class, 'show']);
        Route::post('{issue}/schedule', [IssueController::class, 'scheduleSubmission']);
        Route::delete('{issue}/submissions/{submission}', [IssueController::class, 'removeSubmission']);
        Route::post('{issue}/publish', [IssueController::class, 'publish']);
    });
});