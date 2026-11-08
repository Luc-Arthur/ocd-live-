<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $guarded = [];

    protected $casts = [
        'live_date' => 'date',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->reference ??= static::newReference();
        });
    }

    public static function newReference(): string
    {
        do {
            $ref = 'OCD-'.strtoupper(Str::random(6));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function modeLabel(): string
    {
        return $this->mode === 'presentiel'
            ? 'En présentiel au studio de '.config('ocdlive.studio.city')
            : 'À distance depuis '.$this->city.', '.$this->country;
    }

    /** « mardi 1er décembre » */
    public static function frenchDate(Carbon $date): string
    {
        $date = $date->copy()->locale('fr');
        $day = $date->day === 1 ? '1er' : (string) $date->day;

        return $date->isoFormat('dddd').' '.$day.' '.$date->isoFormat('MMMM');
    }

    public function dateLabel(): string
    {
        return static::frenchDate($this->live_date);
    }

    public function slotLabel(): string
    {
        return str_replace(':', 'h', $this->start_time).' à '.str_replace(':', 'h', $this->end_time);
    }

    /** Phrase de notification pour l'équipe */
    public function summary(): string
    {
        return sprintf(
            '%s (%s · %s) a réservé le %s de %s · %s · %s',
            $this->full_name,
            $this->business_name,
            $this->business_type,
            $this->dateLabel(),
            $this->slotLabel(),
            $this->modeLabel(),
            $this->package->name,
        );
    }

    public function toPublicArray(): array
    {
        return [
            'reference' => $this->reference,
            'mode' => $this->mode,
            'mode_label' => $this->modeLabel(),
            'date' => $this->live_date->toDateString(),
            'date_label' => $this->dateLabel(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'slot_label' => $this->slotLabel(),
            'full_name' => $this->full_name,
            'business_name' => $this->business_name,
            'business_type' => $this->business_type,
            'products' => $this->products,
            'phone' => $this->phone,
            'email' => $this->email,
            'city' => $this->city,
            'country' => $this->country,
            'business_address' => $this->business_address,
            'social_handle' => $this->social_handle,
            'amount' => $this->amount,
            'payment_method' => config('ocdlive.payment_methods.'.$this->payment_method, $this->payment_method),
            'payment_status' => $this->payment_status,
            'payment_reference' => $this->payment_reference,
            'created_at' => $this->created_at?->toIso8601String(),
            'package' => $this->package?->only(['name', 'lives_count', 'announcements_count']),
        ];
    }
}
