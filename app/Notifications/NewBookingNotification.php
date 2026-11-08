<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Envoyée à l'équipe OCD Live à chaque nouvelle réservation */
class NewBookingNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof \App\Models\User ? ['mail', 'database'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $b = $this->booking;

        return (new MailMessage)
            ->subject("Nouveau live réservé · {$b->business_name} · {$b->dateLabel()} {$b->start_time}")
            ->greeting('Nouvelle réservation OCD Live')
            ->line($b->summary().'.')
            ->line("Produits : {$b->products}")
            ->line("Contact : {$b->phone}".($b->email ? " · {$b->email}" : ''))
            ->line('Montant payé : '.number_format($b->amount, 0, ',', ' ').' FCFA ('.config('ocdlive.payment_methods.'.$b->payment_method).')')
            ->action('Voir le planning', route('admin.dashboard', ['date' => $b->live_date->toDateString()]))
            ->line("Référence : {$b->reference}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reference' => $this->booking->reference,
            'message' => $this->booking->summary(),
            'date' => $this->booking->live_date->toDateString(),
            'mode' => $this->booking->mode,
            'amount' => $this->booking->amount,
        ];
    }
}
