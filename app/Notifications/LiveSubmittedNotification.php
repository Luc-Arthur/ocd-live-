<?php

namespace App\Notifications;

use App\Models\Live;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Un vendeur vient d'annoncer son live : l'équipe doit le valider */
class LiveSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public Live $live) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $s = $this->live->seller;

        return [
            'kind' => 'live',
            'message' => sprintf(
                '%s%s est en live · %s · %s — à valider',
                $s->name,
                $s->business_name ? " ({$s->business_name})" : '',
                config("ocdlive.countries.{$s->country}.name"),
                config("ocdlive.categories.{$s->category}"),
            ),
            'live_id' => $this->live->id,
        ];
    }
}
