<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Seller extends Model
{
    protected $guarded = [];

    protected $casts = [
        'subscription_ends_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public function lives(): HasMany
    {
        return $this->hasMany(Live::class);
    }

    public function latestLive(): HasOne
    {
        return $this->hasOne(Live::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function hasProfile(): bool
    {
        return filled($this->name) && filled($this->country) && filled($this->category) && filled($this->tiktok_username);
    }

    public function isSubscribed(): bool
    {
        return $this->subscription_ends_at?->isFuture() ?? false;
    }

    public function tiktokLiveUrl(): string
    {
        return 'https://www.tiktok.com/@'.ltrim($this->tiktok_username, '@').'/live';
    }

    /** wa.me attend le numéro international sans + ni espaces */
    public function whatsappNumber(): string
    {
        return preg_replace('/\D+/', '', $this->phone);
    }

    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'business_name' => $this->business_name,
            'country' => $this->country,
            'country_name' => config("ocdlive.countries.{$this->country}.name"),
            'category' => $this->category,
            'category_name' => config("ocdlive.categories.{$this->category}"),
            'tiktok' => '@'.ltrim((string) $this->tiktok_username, '@'),
            'whatsapp' => $this->whatsappNumber(),
            'has_voice' => (bool) $this->voice_note_path,
            'voice_url' => $this->voice_note_path ? route('seller.voice', $this) : null,
            'photo_url' => $this->photo_path ? route('seller.photo', ['seller' => $this, 'v' => substr(md5($this->photo_path), 0, 8)]) : null,
            'is_demo' => $this->is_demo,
        ];
    }
}
