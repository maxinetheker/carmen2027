<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CrmReminderDigest extends Notification
{
    use Queueable;

    public function __construct(
        public string $heading,
        public string $intro,
        public array $items,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->heading.' · CRM Carmen Mestanza')
            ->markdown('mail.crm-reminder', [
                'heading' => $this->heading,
                'intro' => $this->intro,
                'items' => $this->items,
                'adminUrl' => route('admin.dashboard'),
            ]);
    }
}
