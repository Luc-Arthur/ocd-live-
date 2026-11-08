<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\NewBookingNotification;
use App\Services\SlotService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(private SlotService $slots) {}

    public function create(Request $request): Response
    {
        return Inertia::render('Booking/Create', [
            'initialMode' => in_array($request->query('mode'), ['presentiel', 'distance'], true) ? $request->query('mode') : null,
            'initialPackage' => $request->query('forfait'),
            'packages' => Package::active()->get(),
            'availability' => $this->slots->availability(),
            'firstDay' => $this->slots->firstBookableDay()->toDateString(),
            'lastDay' => $this->slots->lastBookableDay()->toDateString(),
            'slotDuration' => config('ocdlive.live_duration_minutes'),
            'businessTypes' => config('ocdlive.business_types'),
            'paymentMethods' => config('ocdlive.payment_methods'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['presentiel', 'distance'])],
            'live_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', Rule::in(config('ocdlive.slots'))],
            'package_id' => ['required', Rule::exists('packages', 'id')->where('is_active', true)],
            'full_name' => ['required', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:120'],
            'business_type' => ['required', Rule::in(config('ocdlive.business_types'))],
            'products' => ['required', 'string', 'max:1000'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[+0-9 ().-]{8,}$/'],
            'email' => ['nullable', 'email', 'max:160'],
            'city' => ['required', 'string', 'max:80'],
            'country' => ['required', 'string', 'max:80'],
            'business_address' => ['nullable', 'string', 'max:200'],
            'social_handle' => ['nullable', 'string', 'max:80'],
            'payment_method' => ['required', Rule::in(array_keys(config('ocdlive.payment_methods')))],
            'payment_phone' => ['nullable', 'required_unless:payment_method,card', 'string', 'max:30'],
        ], [
            'phone.regex' => 'Numéro de téléphone invalide.',
            'payment_phone.required_unless' => 'Indique le numéro Mobile Money à débiter.',
        ]);

        if (! $this->slots->isBookable($data['live_date'], $data['start_time'])) {
            throw ValidationException::withMessages([
                'start_time' => 'Ce créneau vient d\'être réservé ou n\'est plus disponible. Choisis-en un autre.',
            ]);
        }

        $package = Package::findOrFail($data['package_id']);

        try {
            $booking = DB::transaction(function () use ($data, $package) {
                return Booking::create([
                    ...$data,
                    'end_time' => $this->slots->endTime($data['start_time']),
                    'amount' => $package->price,
                    // DÉMO : paiement simulé. En production : agrégateur Mobile Money (webhook de confirmation).
                    'payment_status' => 'paid',
                    'payment_reference' => 'SIM-'.strtoupper(Str::random(8)),
                    'paid_at' => now(),
                    'status' => 'confirmed',
                ]);
            });
        } catch (QueryException $e) {
            // Deux personnes sur le même créneau au même moment
            throw ValidationException::withMessages([
                'start_time' => 'Ce créneau vient d\'être pris. Choisis-en un autre.',
            ]);
        }

        $booking->load('package');

        // Notifications équipe (mail + tableau de bord) et confirmation client
        Notification::send(User::where('is_admin', true)->get(), new NewBookingNotification($booking));
        Notification::route('mail', config('ocdlive.team_email'))->notify(new NewBookingNotification($booking));
        if ($booking->email) {
            Notification::route('mail', $booking->email)->notify(new BookingConfirmedNotification($booking));
        }

        return redirect()->route('booking.show', $booking);
    }

    public function show(Booking $booking): Response
    {
        $booking->load('package');

        return Inertia::render('Booking/Show', [
            'booking' => $booking->toPublicArray(),
        ]);
    }
}
