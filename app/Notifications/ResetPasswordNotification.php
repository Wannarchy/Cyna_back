<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(#[\SensitiveParameter] public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $baseUrl = rtrim((string) config('cyna.frontend_url'), '/');
        $resetUrl = $baseUrl.'/reinitialiser_mot_de_passe.php?token='.urlencode($this->token);

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe — CYNA')
            ->greeting('Bonjour '.$notifiable->prenom.' !')
            ->line('Vous recevez cet email suite à une demande de réinitialisation de mot de passe.')
            ->action('Réinitialiser mon mot de passe', $resetUrl)
            ->line('Ce lien expire dans '.$expireMinutes.' minutes.')
            ->line('Si vous n\'avez pas demandé de réinitialisation, ignorez cet email.');
    }
}
