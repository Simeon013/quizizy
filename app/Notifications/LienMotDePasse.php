<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Le lien pour choisir un nouveau mot de passe. Envoyé de façon synchrone :
 * pas de processus de file d'attente sur un hébergement mutualisé.
 */
class LienMotDePasse extends Notification
{
    public function __construct(public readonly string $jeton) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lien = route('mot-de-passe.nouveau', ['jeton' => $this->jeton, 'email' => $notifiable->getEmailForPasswordReset()]);
        $minutes = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Ton nouveau mot de passe Eurêka')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Tu as demandé à changer le mot de passe de ton cahier Eurêka. Ce bouton t\'emmène choisir le nouveau :')
            ->action('Choisir un nouveau mot de passe', $lien)
            ->line("Le lien reste valable {$minutes} minutes et ne sert qu'une fois.")
            ->line('Ce n\'est pas toi ? Ignore ce message : ton mot de passe actuel reste en place.')
            ->salutation('Gribouille, pour Eurêka');
    }
}
