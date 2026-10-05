<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DocumentReviewDue extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, Document>  $documents  the owner's documents due for periodic review
     */
    public function __construct(public Collection $documents) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__(':count document(s) due for review', ['count' => $this->documents->count()]))
            ->line(__('These documents you own are due for their periodic review. Confirm each is still correct, or start a new revision.'));

        foreach ($this->documents as $document) {
            $mail->line(__(':number – :title: review due :date', ['number' => $document->number, 'title' => $document->title, 'date' => $document->next_review_on?->format('Y-m-d')]));
        }

        return $mail->action(__('Open documents'), route('documents.index', ['due' => 1]));
    }
}
