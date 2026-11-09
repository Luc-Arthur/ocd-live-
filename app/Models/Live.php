<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Live extends Model
{
    protected $guarded = [];

    protected $casts = [
        'ends_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** Validés et pas encore expirés : visibles par le public */
    public function scopeOnAir(Builder $q): Builder
    {
        return $q->where('status', 'approved')->where('ends_at', '>', now());
    }

    /** En attente de l'équipe, dans une session encore ouverte */
    public function scopeWaiting(Builder $q): Builder
    {
        return $q->where('status', 'pending')->where('ends_at', '>', now());
    }

    public function isOnAir(): bool
    {
        return $this->status === 'approved' && $this->ends_at->isFuture();
    }

    public function publicStatus(): string
    {
        if (in_array($this->status, ['approved', 'pending'], true) && $this->ends_at->isPast()) {
            return 'ended';
        }

        return $this->status;
    }

    /**
     * Début de l'antenne, pour le code temporel des moniteurs (00:41:12).
     * Un vrai live dure au plus une session (3 h). Les lives de démo restent en ligne
     * plusieurs jours : leur compteur tourne en boucle sur 3 h pour rester crédible.
     */
    public function onAirSince(): \Illuminate\Support\Carbon
    {
        $since = $this->validated_at ?? $this->created_at;

        if ($this->seller?->is_demo) {
            $elapsed = (int) abs(now()->diffInSeconds($since));
            $since = now()->subSeconds($elapsed % (3 * 3600));
        }

        return $since;
    }

    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->tiktok_url,
            'status' => $this->publicStatus(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'on_air_at' => $this->onAirSince()->toIso8601String(),
            'since' => ($this->validated_at ?? $this->created_at)->locale('fr')->diffForHumans(),
            'created_ago' => $this->created_at->locale('fr')->diffForHumans(),
            'reject_reason' => $this->reject_reason,
            'seller' => $this->seller->toCard(),
        ];
    }
}
