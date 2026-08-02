<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\FileController;

// Health check
Route::get('/health', fn() => response()->json(['status' => 'ok', 'version' => '1.0']));

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Public journal browsing
Route::get('journals', [JournalController::class, 'index']);
Route::get('journals/{journal}', [JournalController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Submissions
    Route::apiResource('submissions', SubmissionController::class);
    Route::prefix('submissions/{submission}')->group(function () {
        Route::post('send-to-review', [SubmissionController::class, 'sendToReview']);
        Route::post('request-revision', [SubmissionController::class, 'requestRevision']);
        Route::post('accept', [SubmissionController::class, 'accept']);
        Route::post('reject', [SubmissionController::class, 'reject']);
        Route::post('upload', [FileController::class, 'uploadManuscript']);
        Route::get('versions', [FileController::class, 'versions']);
        Route::get('files/{file}/download', [FileController::class, 'download']);
    });

    // Reviews
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::get('{reviewInvitation}', [ReviewController::class, 'show']);
        Route::post('{reviewInvitation}/accept', [ReviewController::class, 'acceptInvitation']);
        Route::post('{reviewInvitation}/decline', [ReviewController::class, 'declineInvitation']);
        Route::post('{reviewInvitation}/submit', [ReviewController::class, 'submitReview']);
    });
});