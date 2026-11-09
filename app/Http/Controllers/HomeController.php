<?php

namespace App\Http\Controllers;

use App\Models\Live;
use App\Models\Package;
use App\Services\LiveSessionService;
use App\Services\SlotService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(SlotService $slots, LiveSessionService $sessions): Response
    {
        $free = collect($slots->availability())->flatten()->filter()->count();

        return Inertia::render('Home', [
            'packages' => Package::active()->get(),
            'launchDate' => config('ocdlive.launch_date'),
            'freeSlots' => $free,
            'session' => $sessions->state(),
            'countryCounts' => DirectoryController::countsBy('country'),
            'categoryCounts' => DirectoryController::countsBy('category'),
            'onAirTotal' => Live::onAir()->count(),
            'preview' => Live::onAir()->with('seller')->latest('validated_at')->take(4)->get()->map->toCard(),
            'subscriptionPrice' => config('ocdlive.directory.subscription_price'),
        ]);
    }
}
