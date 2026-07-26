<?php

namespace App\Providers;

use App\Models\Submission;
use App\Models\ReviewInvitation;
use App\Policies\SubmissionPolicy;
use App\Policies\ReviewInvitationPolicy;
use Illuminate\Support\ServiceProvider;

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
    }

    protected function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            \Illuminate\Support\Facades\Gate::policy($model, $policy);
        }
    }
}