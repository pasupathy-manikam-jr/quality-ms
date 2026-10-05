<?php

namespace Database\Seeders\Modules;

use App\Models\Document;
use App\Models\IsoClause;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DocumentSeeder extends Seeder
{
    /**
     * Controlled documents written by the quality manager and approved by the admin, one of
     * them still in review. The quality manual is nearly due for its yearly review.
     */
    public function run(): void
    {
        /** @var list<array{number: string, title: string, type: string, clauses: list<string>, approved_months_ago: int|null, summary: string}> $documents */
        $documents = File::json(database_path('demo/documents.json'), JSON_THROW_ON_ERROR);
        $author = User::query()->where('email', 'quality-manager@example.com')->firstOrFail();
        $approver = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $readers = User::query()->whereIn('email', ['inspector@example.com', 'auditor@example.com'])->pluck('id');

        foreach ($documents as $data) {
            if (Document::query()->where('number', $data['number'])->exists()) {
                continue;
            }

            DB::transaction(function () use ($data, $author, $approver, $readers) {
                auth()->setUser($author);
                $document = Document::query()->create([...array_intersect_key($data, array_flip(['number', 'title', 'type'])), 'owner_id' => $author->id]);
                $document->clauses()->sync(IsoClause::query()->whereIn('number', $data['clauses'])->pluck('id'));

                $revision = $document->startRevision();
                $revision->attachUpload(UploadedFile::fake()->createWithContent("{$data['number']}.pdf", "%PDF-1.4\n% {$data['number']} {$data['title']} (demo)\n"));
                $revision->fill(['change_summary' => $data['summary']])->save();
                $revision->submit();

                if ($data['approved_months_ago'] !== null) {
                    auth()->setUser($approver);
                    $revision->approve();
                    $revision->forceFill(['approved_at' => now()->subMonths($data['approved_months_ago'])])->save();
                    $document->forceFill(['next_review_on' => now()->subMonths($data['approved_months_ago'])->addMonths(12)->toDateString()])->save();
                    $revision->readers()->attach($readers, ['acknowledged_at' => $data['number'] === 'QM-001' ? now()->subMonths(10) : null]);
                }
            });
        }
    }
}
