<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Sessions de validation : 8h-11h et 18h-21h (heure de Lomé).
 * Un live validé reste visible jusqu'à la fin de sa session.
 */
class LiveSessionService
{
    public function sessions(): array
    {
        return config('ocdlive.directory.sessions');
    }

    public function alwaysOpen(): bool
    {
        return config('ocdlive.directory.always_open');
    }

    /** Session en cours, ou null */
    public function current(?Carbon $now = null): ?array
    {
        $now ??= now();

        foreach ($this->sessions() as $s) {
            [$start, $end] = $this->bounds($s, $now);
            if ($now->betweenIncluded($start, $end->copy()->subSecond())) {
                return [...$s, 'starts_at' => $start, 'ends_at' => $end, 'demo' => false];
            }
        }

        if ($this->alwaysOpen()) {
            // Démo : une session ouverte en continu, qui se termine dans 3 heures
            return ['key' => 'demo', 'label' => 'Session de démonstration', 'start' => null, 'end' => null,
                'starts_at' => $now->copy(), 'ends_at' => $now->copy()->addHours(3), 'demo' => true];
        }

        return null;
    }

    /** Prochaine session (aujourd'hui ou demain) */
    public function next(?Carbon $now = null): array
    {
        $now ??= now();

        foreach ([0, 1] as $day) {
            foreach ($this->sessions() as $s) {
                [$start, $end] = $this->bounds($s, $now->copy()->addDays($day));
                if ($start->gt($now)) {
                    return [...$s, 'starts_at' => $start, 'ends_at' => $end];
                }
            }
        }

        $s = $this->sessions()[0];
        [$start, $end] = $this->bounds($s, $now->copy()->addDays(2));

        return [...$s, 'starts_at' => $start, 'ends_at' => $end];
    }

    /** Données envoyées au front pour le bandeau et le compte à rebours */
    public function state(): array
    {
        $cur = $this->current();
        $next = $this->next();

        return [
            'open' => (bool) $cur,
            'demo' => (bool) ($cur['demo'] ?? false),
            'label' => $cur['label'] ?? null,
            'start' => $cur['start'] ?? null,
            'end' => $cur['end'] ?? null,
            'ends_at' => $cur ? $cur['ends_at']->toIso8601String() : null,
            // Heure du serveur : l'horloge de Lomé et les codes temporels s'y recalent
            'now' => now()->toIso8601String(),
            'next_label' => $next['label'],
            'next_start' => $next['start'],
            'next_starts_at' => $next['starts_at']->toIso8601String(),
            'slots' => collect($this->sessions())->map(fn ($s) => str_replace(':', 'h', $s['start']).' – '.str_replace(':', 'h', $s['end']))->all(),
        ];
    }

    private function bounds(array $s, Carbon $day): array
    {
        $start = $day->copy()->setTimeFromTimeString($s['start']);
        $end = $day->copy()->setTimeFromTimeString($s['end']);

        return [$start, $end];
    }
}
