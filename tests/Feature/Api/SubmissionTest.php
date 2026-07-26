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

    /**
     * Test non-author cannot view submission.
     */
    public function test_non_author_cannot_view_submission(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $submission = Submission::factory()->create(['author_id' => $author->id]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/submissions/{$submission->id}");

        $response->assertStatus(403);
    }

    /**
     * Test author can update own submission before review.
     */
    public function test_author_can_update_before_review(): void
    {
        $author = User::factory()->create();
        $submission = Submission::factory()->create([
            'author_id' => $author->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($author, 'sanctum')
            ->patchJson("/api/submissions/{$submission->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Updated Title', $submission->fresh()->title);
    }

    /**
     * Test cannot update after review begins.
     */
    public function test_cannot_update_after_review_begins(): void
    {
        $author = User::factory()->create();
        $submission = Submission::factory()->create([
            'author_id' => $author->id,
            'status' => 'under_review',
        ]);

        $response = $this->actingAs($author, 'sanctum')
            ->patchJson("/api/submissions/{$submission->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(403);
    }
}