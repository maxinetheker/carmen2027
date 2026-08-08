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
        public string $urgency = 'normal',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // El prefijo hace visible en la bandeja qué tan urgente es el correo sin
        // tener que abrirlo, que era el reclamo principal sobre los avisos.
        $prefix = match ($this->urgency) {
            'overdue' => 'Vencido · ',
            'now' => 'Ahora · ',
            'soon' => 'Pronto · ',
            default => '',
        };

        return (new MailMessage)
            ->subject($prefix.$this->heading.' · CRM Carmen Mestanza')
            ->markdown('mail.crm-reminder', [
                'heading' => $this->heading,
                'intro' => $this->intro,
                'items' => $this->items,
                'urgency' => $this->urgency,
                'adminUrl' => route('admin.dashboard'),
            ]);
    }
}
