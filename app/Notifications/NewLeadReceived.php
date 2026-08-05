<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeadReceived extends Notification
{
    use Queueable;

    public function __construct(public Lead $lead)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Nueva solicitud de asesoría · CRM Carmen Mestanza')
            ->greeting('Nueva solicitud desde la página web')
            ->line('Nombre: '.$this->lead->full_name)
            ->line('Teléfono: '.$this->lead->phone);

        if ($this->lead->email) {
            $message->line('Correo: '.$this->lead->email)
                ->replyTo($this->lead->email, $this->lead->full_name);
        }

        if ($this->lead->interest) {
            $message->line('Interés: '.$this->lead->interest);
        }

        if ($this->lead->notes) {
            $message->line('Mensaje: '.$this->lead->notes);
        }

        return $message
            ->action('Abrir prospecto en el CRM', route('admin.leads.edit', $this->lead))
            ->line('La solicitud ya fue guardada como un prospecto nuevo.');
    }
}
