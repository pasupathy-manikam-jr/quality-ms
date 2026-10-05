<?php

namespace App\Notifications;

use App\Models\Gauge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class CalibrationDue extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, Gauge>  $gauges  the owner's active gauges that are due or overdue
     */
    public function __construct(public Collection $gauges) {}

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
            ->subject(__(':count gauge(s) need calibration', ['count' => $this->gauges->count()]))
            ->line(__('These gauges you own are due or overdue for calibration. Overdue gauges cannot be used for inspection.'));

        foreach ($this->gauges as $gauge) {
            $mail->line($gauge->next_due_on
                ? __(':code – :description: calibration due :date', ['code' => $gauge->code, 'description' => $gauge->description, 'date' => $gauge->next_due_on->format('Y-m-d')])
                : __(':code – :description: never calibrated', ['code' => $gauge->code, 'description' => $gauge->description]));
        }

        return $mail->action(__('Open gauges'), route('gauges.index', ['state' => 'overdue']));
    }
}
