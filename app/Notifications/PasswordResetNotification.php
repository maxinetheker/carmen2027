<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Restablece tu contraseña del CRM')
            ->greeting('Hola, '.$notifiable->name)
            ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta.')
            ->action('Crear una nueva contraseña', $url)
            ->line('El enlace vence en '.config('auth.passwords.users.expire').' minutos.')
            ->line('Si no realizaste esta solicitud, puedes ignorar este mensaje.');
    }
}
