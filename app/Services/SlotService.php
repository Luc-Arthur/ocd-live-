<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Carbon;

class SlotService
{
    public function firstBookableDay(): Carbon
    {
        $launch = Carbon::parse(config('ocdlive.launch_date'))->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();

        return $launch->greaterThan($tomorrow) ? $launch : $tomorrow;
    }

    public function lastBookableDay(): Carbon
    {
        return $this->firstBookableDay()->addDays(config('ocdlive.booking_window_days'));
    }

    public function endTime(string $start): string
    {
        return Carbon::createFromFormat('H:i', $start)
            ->addMinutes(config('ocdlive.live_duration_minutes'))
            ->format('H:i');
    }

    public function isBookable(string $date, string $start): bool
    {
        if (! in_array($start, config('ocdlive.slots'), true)) {
            return false;
        }

        $day = Carbon::parse($date)->startOfDay();

        return $day->betweenIncluded($this->firstBookableDay(), $this->lastBookableDay())
            && ! Booking::where('live_date', $day->toDateString())
                ->where('start_time', $start)
                ->where('status', '!=', 'cancelled')
                ->exists();
    }

    /**
     * Disponibilités de toute la fenêtre de réservation :
     * [ '2026-12-01' => ['08:00' => true, '10:00' => false, ...], ... ]
     */
    public function availability(): array
    {
        $first = $this->firstBookableDay();
        $last = $this->lastBookableDay();

        $taken = Booking::whereBetween('live_date', [$first->toDateString(), $last->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->get(['live_date', 'start_time'])
            ->groupBy(fn ($b) => $b->live_date->toDateString())
            ->map(fn ($g) => $g->pluck('start_time')->all());

        $days = [];
        for ($d = $first->copy(); $d->lte($last); $d->addDay()) {
            $key = $d->toDateString();
            foreach (config('ocdlive.slots') as $slot) {
                $days[$key][$slot] = ! in_array($slot, $taken[$key] ?? [], true);
            }
        }

        return $days;
    }
}
