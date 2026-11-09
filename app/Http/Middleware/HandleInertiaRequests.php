<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'studio' => config('ocdlive.studio'),
                'tiktok' => config('ocdlive.tiktok_account'),
                'whatsapp' => config('ocdlive.whatsapp'),
            ],
            'directory' => [
                'countries' => collect(config('ocdlive.countries'))->map(fn ($c) => ['name' => $c['name'], 'dial' => $c['dial'], 'lon' => $c['lon'], 'lat' => $c['lat']]),
                'categories' => config('ocdlive.categories'),
                'price' => config('ocdlive.directory.subscription_price'),
            ],
            'auth' => [
                'user' => $request->user()?->only(['name', 'email']),
                'unread' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
            ],
        ];
    }
}
