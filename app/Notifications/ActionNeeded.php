<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Something is waiting for you": one line and a button to the record. Built by App\Support\Notify.
 */
class ActionNeeded extends Notification
{
    use Queueable;

    public function __construct(public string $subject, public string $line, public string $url, public string $button) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->subject)->line($this->line)->action($this->button, $this->url);
    }
}
