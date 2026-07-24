<?php

namespace Tests\Feature\Api;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test author can view own submission.
     */
    public function test_author_can_view_own_submission(): void
    {
        $author = User::factory()->create();
        $submission = Submission::factory()->create(['author_id' => $author->id]);

        $response = $this->actingAs($author, 'sanctum')
            ->getJson("/api/submissions/{$submission->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.title', $submission->title);
    }
}