<?php

namespace Tests\Feature\Api;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Use fake storage for tests
        Storage::fake('submissions');
    }

    /**
     * Test author can upload manuscript.
     */
    public function test_author_can_upload_manuscript(): void
    {
        $author = User::factory()->create();
        $submission = Submission::factory()->create([
            'author_id' => $author->id,
            'status' => 'submitted',
        ]);

        $file = UploadedFile::fake()->create('manuscript.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($author, 'sanctum')
            ->postJson("/api/submissions/{$submission->id}/upload", [
                'manuscript' => $file,
                'upload_notes' => 'Initial submission',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'version' => [
                    'id',
                    'version_number',
                    'files',
                ],
            ]);

        // Verify version was created
        $this->assertDatabaseHas('submission_versions', [
            'submission_id' => $submission->id,
            'version_number' => 1,
        ]);

        // Verify file was stored
        Storage::disk('submissions')->assertExists(
            "submissions/{$submission->id}/v1/"
        );
    }

    /**
     * Test non-author cannot upload.
     */
    public function test_non_author_cannot_upload(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $submission = Submission::factory()->create(['author_id' => $author->id]);

        $file = UploadedFile::fake()->create('manuscript.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($other, 'sanctum')
            ->postJson("/api/submissions/{$submission->id}/upload", [
                'manuscript' => $file,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test invalid file type is rejected.
     */
    public function test_invalid_file_type_rejected(): void
    {
        $author = User::factory()->create();
        $submission = Submission::factory()->create(['author_id' => $author->id]);

        $file = UploadedFile::fake()->create('script.exe', 1024, 'application/octet-stream');

        $response = $this->actingAs($author, 'sanctum')
            ->postJson("/api/submissions/{$submission->id}/upload", [
                'manuscript' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['manuscript']);
    }

    /**
     * Test file list returns versions.
     */
    public function test_file_list_returns_versions(): void
    {
        $author = User::factory()->create();
        $submission = Submission::factory()->create(['author_id' => $author->id]);

        $response = $this->actingAs($author, 'sanctum')
            ->getJson("/api/submissions/{$submission->id}/versions");

        $response->assertStatus(200)
            ->assertJsonStructure(['versions']);
    }
}