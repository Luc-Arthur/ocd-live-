<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\SlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SlotService $slots): Response
    {
        $first = $slots->firstBookableDay();
        $date = $request->filled('date') && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->query('date'))
            ? Carbon::parse($request->query('date'))
            : (now()->lt($first) ? $first->copy() : now()->startOfDay());

        $active = Booking::with('package')->where('status', '!=', 'cancelled');

        $dayBookings = (clone $active)->whereDate('live_date', $date)->get()->keyBy('start_time');
        $planning = collect(config('ocdlive.slots'))->map(fn ($slot) => [
            'start' => $slot,
            'end' => $slots->endTime($slot),
            'booking' => isset($dayBookings[$slot]) ? $dayBookings[$slot]->toPublicArray() : null,
        ]);

        // Taux de remplissage du mois affiché
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();
        $monthBooked = (clone $active)->whereBetween('live_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->count();
        $monthCapacity = $monthStart->daysInMonth * count(config('ocdlive.slots'));

        $user = $request->user();

        return Inertia::render('Admin/Dashboard', [
            'date' => $date->toDateString(),
            'dateLabel' => ucfirst(Booking::frenchDate($date)).' '.$date->year,
            'monthLabel' => ucfirst($date->copy()->locale('fr')->isoFormat('MMMM YYYY')),
            'planning' => $planning,
            'stats' => [
                'bookings' => (clone $active)->count(),
                'revenue' => (int) (clone $active)->where('payment_status', 'paid')->sum('amount'),
                'distance' => (clone $active)->where('mode', 'distance')->count(),
                'fill_rate' => $monthCapacity ? round($monthBooked / $monthCapacity * 100) : 0,
                'month_booked' => $monthBooked,
                'month_capacity' => $monthCapacity,
            ],
            'notifications' => $user->notifications()->latest()->take(12)->get()->map(fn ($n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? '',
                'reference' => $n->data['reference'] ?? null,
                'date' => $n->data['date'] ?? null,
                'mode' => $n->data['mode'] ?? null,
                'unread' => $n->read_at === null,
                'ago' => $n->created_at->locale('fr')->diffForHumans(),
            ]),
            'upcoming' => (clone $active)->whereDate('live_date', '>=', now()->toDateString())
                ->orderBy('live_date')->orderBy('start_time')->take(15)->get()
                ->map->toPublicArray(),
        ]);
    }

    public function markRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
