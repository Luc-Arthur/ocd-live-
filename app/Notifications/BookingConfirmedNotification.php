<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Confirmation envoyée au client */
class BookingConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $b = $this->booking;

        return (new MailMessage)
            ->subject("Ton live OCD Live est confirmé · {$b->dateLabel()}")
            ->greeting("Bravo {$b->full_name} !")
            ->line("Ton live est réservé le {$b->dateLabel()} de {$b->slotLabel()} ({$b->modeLabel()}).")
            ->line("Forfait : {$b->package->name}.")
            ->line('Prépare tes produits, ton numéro et ton adresse : le but est de te faire connaître.')
            ->action('Voir ma réservation', route('booking.show', $b))
            ->line("Référence : {$b->reference}");
    }
}
