<?php

namespace App\Http\Controllers\Api;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;

class ArticleController extends BaseController
{
    /**
     * Public article view.
     * Only published submissions are publicly viewable.
     */
    public function show(Submission $submission)
    {
        // Only published articles are publicly accessible
        if ($submission->status !== 'published') {
            return response()->json([
                'message' => 'Article not found or not yet published.'
            ], 404);
        }

        $submission->load([
            'author',
            'journal',
            'section',
            'currentVersion.files',
            'issues',
        ]);

        // Get issue info
        $issue = $submission->issues->first();

        // Build citation formats
        $year = $submission->submitted_at->format('Y');
        $authorName = $submission->author->name;
        $title = $submission->title;
        $journalTitle = $submission->journal->title;
        $volumeIssue = $issue
            ? "Vol. {$issue->volume}, No. {$issue->issue_number}"
            : '';
        $pages = $issue
            ? optional($submission->issues()->withPivot('page_number')->first()?->pivot)->page_number
            : '';

        $citations = [
            'apa' => "{$authorName}. ({$year}). {$title}. *{$journalTitle}*, {$volumeIssue}" .
                ($pages ? ", pp. {$pages}" : '') . ".",

            'mla' => "{$authorName}. \"{$title}.\" *{$journalTitle}*, " .
                "{$volumeIssue}" . ($pages ? ", {$year}, pp. {$pages}" : ", {$year}") . ".",

            'chicago' => "{$authorName}. \"{$title}.\" *{$journalTitle}* " .
                "{$volumeIssue}" . ($pages ? " ({$year}): {$pages}" : " ({$year})") . ".",
        ];

        // Check if manuscript file exists
        $hasDownload = $submission->currentVersion?->files
            ->where('file_role', 'manuscript')
            ->isNotEmpty() ?? false;

        return response()->json([
            'article' => [
                'id' => $submission->id,
                'title' => $submission->title,
                'abstract' => $submission->abstract,
                'keywords' => $submission->keywords,
                'status' => $submission->status,
                'submitted_at' => $submission->submitted_at->toIso8601String(),
                'published_at' => $issue?->published_at,
                'author' => [
                    'id' => $submission->author->id,
                    'name' => $submission->author->name,
                    'affiliation' => $submission->author->affiliation,
                ],
                'journal' => [
                    'id' => $submission->journal->id,
                    'title' => $submission->journal->title,
                    'slug' => $submission->journal->slug,
                    'issn' => $submission->journal->issn,
                ],
                'section' => [
                    'id' => $submission->section->id,
                    'title' => $submission->section->title,
                ],
                'issue' => $issue ? [
                    'id' => $issue->id,
                    'label' => "Vol. {$issue->volume}, No. {$issue->issue_number}",
                    'volume' => $issue->volume,
                    'issue_number' => $issue->issue_number,
                    'published_at' => $issue->published_at,
                ] : null,
                'page_number' => $pages,
                'citations' => $citations,
                'has_download' => $hasDownload,
                'doi' => null, // Will be filled when DOI system is implemented
            ],
        ]);
    }

    /**
     * Download manuscript file.
     * Requires authentication.
     */
   public function download(Request $request, Submission $submission)
{
    if ($submission->status !== 'published') {
        return response()->json(['message' => 'Article not found.'], 404);
    }

    $submission->load('currentVersion.files');

    $file = $submission->currentVersion?->files
        ->where('file_role', 'manuscript')
        ->first();

    if (!$file || !Storage::disk('submissions')->exists($file->file_path)) {
        return response()->json(['message' => 'File not found.'], 404);
    }

    return Storage::disk('submissions')->download(
        $file->file_path,
        $file->original_filename,
        ['Content-Type' => 'application/octet-stream']
    );
}
}