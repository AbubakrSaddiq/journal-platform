<?php

namespace App\Providers;

use App\Models\Submission;
use App\Models\ReviewInvitation;
use App\Policies\SubmissionPolicy;
use App\Policies\ReviewInvitationPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Submission::class => SubmissionPolicy::class,
        ReviewInvitation::class => ReviewInvitationPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->configureRateLimiting();
    }

    protected function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    protected function configureRateLimiting(): void
    {
        // General API: 60 requests per minute per user/IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please wait before trying again.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // Auth endpoints: 5 attempts per minute per IP
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts. Please wait 1 minute before trying again.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // Submissions: 10 per hour per user
        RateLimiter::for('submissions', function (Request $request) {
            return Limit::perHour(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Submission limit reached. You can submit up to 10 manuscripts per hour.',
                        'retry_after' => 3600,
                    ], 429);
                });
        });

        // File uploads: 20 per hour per user
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perHour(20)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Upload limit reached. Please try again later.',
                        'retry_after' => 3600,
                    ], 429);
                });
        });
    }
}