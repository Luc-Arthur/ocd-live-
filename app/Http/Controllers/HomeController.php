<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Services\SlotService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(SlotService $slots): Response
    {
        $availability = $slots->availability();
        $free = collect($availability)->flatten()->filter()->count();

        return Inertia::render('Home', [
            'packages' => Package::active()->get(),
            'launchDate' => config('ocdlive.launch_date'),
            'freeSlots' => $free,
        ]);
    }
}
