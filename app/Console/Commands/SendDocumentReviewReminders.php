<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('qms:document-review-reminders')]
#[Description('Email each document owner the documents due for periodic review')]
class SendDocumentReviewReminders extends Command
{
    public function handle(): int
    {
        $documents = Document::query()->dueForReview()->whereNotNull('owner_id')->orderBy('next_review_on')->get();
        $owners = User::query()->whereIn('id', $documents->pluck('owner_id')->unique())->get()->keyBy('id');

        foreach ($documents->groupBy('owner_id') as $ownerId => $owned) {
            if ($owner = $owners->get($ownerId)) {
                Notify::documentReviewDue($owner, $owned->values());
            }
        }

        $this->info(__('Reminded :owners owner(s) about :documents document(s).', ['owners' => $owners->count(), 'documents' => $documents->count()]));

        return self::SUCCESS;
    }
}
