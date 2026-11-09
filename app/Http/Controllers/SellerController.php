<?php

namespace App\Http\Controllers;

use App\Models\Live;
use App\Models\Seller;
use App\Models\User;
use App\Notifications\LiveSubmittedNotification;
use App\Services\LiveSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Espace vendeur : connexion WhatsApp → profil → abonnement → « Je suis en live » */
class SellerController extends Controller
{
    public function __construct(private LiveSessionService $sessions) {}

    private function seller(Request $request): ?Seller
    {
        $id = $request->session()->get('seller_id');

        return $id ? Seller::find($id) : null;
    }

    public function show(Request $request): Response
    {
        $seller = $this->seller($request);
        $pendingPhone = $request->session()->get('pending_phone');

        $step = match (true) {
            ! $seller => $pendingPhone ? 'code' : 'phone',
            ! $seller->hasProfile() => 'profile',
            ! $seller->isSubscribed() => 'subscribe',
            default => 'live',
        };

        $live = $seller?->latestLive;

        return Inertia::render('Seller/Space', [
            'step' => $step,
            'pendingPhone' => $pendingPhone,
            'demoCode' => $pendingPhone ? config('ocdlive.directory.demo_code') : null,
            'seller' => $seller ? [
                ...$seller->toCard(),
                'phone' => $seller->phone,
                'tiktok_username' => $seller->tiktok_username,
                'subscription_ends_at' => $seller->subscription_ends_at?->locale('fr')->isoFormat('D MMMM YYYY'),
            ] : null,
            'live' => $live && in_array($live->publicStatus(), ['pending', 'approved', 'rejected'], true) ? $live->toCard() : null,
            'session' => $this->sessions->state(),
            'price' => config('ocdlive.directory.subscription_price'),
            'methods' => config('ocdlive.subscription_methods'),
        ]);
    }

    public function sendCode(Request $request)
    {
        $data = $request->validate([
            'dial' => ['required', 'regex:/^\+\d{1,4}$/'],
            'number' => ['required', 'string', 'max:20'],
        ], ['number.required' => 'Écris ton numéro WhatsApp.']);

        $digits = preg_replace('/\D+/', '', $data['number']);
        if (strlen($digits) < 8) {
            throw ValidationException::withMessages(['number' => 'Numéro trop court.']);
        }

        // En production : envoyer un code à 4 chiffres par WhatsApp (ou SMS) ici.
        $request->session()->put('pending_phone', $data['dial'].$digits);

        return redirect()->route('seller.space');
    }

    public function verify(Request $request)
    {
        $phone = $request->session()->get('pending_phone');
        abort_unless($phone, 403);

        $request->validate(['code' => ['required', 'digits:4']], ['code.*' => 'Le code a 4 chiffres.']);

        if ($request->input('code') !== (string) config('ocdlive.directory.demo_code')) {
            throw ValidationException::withMessages(['code' => 'Code incorrect. Réessaie.']);
        }

        $seller = Seller::firstOrCreate(['phone' => $phone]);
        $request->session()->forget('pending_phone');
        $request->session()->put('seller_id', $seller->id);
        $request->session()->regenerate();

        return redirect()->route('seller.space');
    }

    public function changePhone(Request $request)
    {
        $request->session()->forget('pending_phone');

        return redirect()->route('seller.space');
    }

    public function saveProfile(Request $request)
    {
        $seller = $this->seller($request) ?? abort(403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'business_name' => ['nullable', 'string', 'max:80'],
            'country' => ['required', Rule::in(array_keys(config('ocdlive.countries')))],
            'category' => ['required', Rule::in(array_keys(config('ocdlive.categories')))],
            'tiktok' => ['required', 'string', 'max:120'],
            'voice' => ['nullable', 'file', 'max:8192'],
            'photo' => ['nullable', 'image', 'max:6144'],
        ], [
            'name.required' => 'Écris ton nom.',
            'country.required' => 'Choisis ton pays.',
            'category.required' => 'Choisis ce que tu vends.',
            'tiktok.required' => 'Écris ton nom TikTok.',
            'photo.image' => 'Ce fichier n’est pas une photo. Reprends la photo.',
            'photo.uploaded' => 'La photo n’est pas passée. Réessaie.',
            'photo.max' => 'Photo trop lourde (6 Mo maximum).',
        ]);

        $username = $this->tiktokUsername($data['tiktok']);
        if (! $username) {
            throw ValidationException::withMessages(['tiktok' => 'Nom TikTok invalide. Exemple : @chezafi']);
        }

        if ($request->hasFile('voice')) {
            if ($seller->voice_note_path) {
                Storage::disk('local')->delete($seller->voice_note_path);
            }
            $ext = $request->file('voice')->getClientOriginalExtension() ?: 'webm';
            $seller->voice_note_path = $request->file('voice')->storeAs('voices', $seller->id.'-'.Str::random(8).'.'.$ext, 'local');
        }

        if ($request->hasFile('photo')) {
            if ($seller->photo_path) {
                Storage::disk('local')->delete($seller->photo_path);
            }
            $ext = $request->file('photo')->extension() ?: 'jpg';
            $seller->photo_path = $request->file('photo')->storeAs('photos', $seller->id.'-'.Str::random(8).'.'.$ext, 'local');
        }

        $seller->fill([
            'name' => $data['name'],
            'business_name' => ($data['business_name'] ?? null) ?: null,
            'country' => $data['country'],
            'category' => $data['category'],
            'tiktok_username' => $username,
        ])->save();

        return redirect()->route('seller.space');
    }

    public function subscribe(Request $request)
    {
        $seller = $this->seller($request) ?? abort(403);

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(config('ocdlive.subscription_methods')))],
            'payer_phone' => ['nullable', 'string', 'max:30'],
        ]);

        // DÉMO : paiement simulé. En production : agrégateur (CinetPay, PayDunya, Moneroo…) + webhook.
        $start = $seller->isSubscribed() ? $seller->subscription_ends_at : now();
        $end = $start->copy()->addDays(config('ocdlive.directory.subscription_days'));

        $seller->payments()->create([
            'amount' => config('ocdlive.directory.subscription_price'),
            'method' => $data['method'],
            'payer_phone' => $data['payer_phone'] ?: $seller->phone,
            'reference' => 'ABO-'.strtoupper(Str::random(8)),
            'paid_at' => now(),
            'period_ends_at' => $end,
        ]);
        $seller->update(['subscription_ends_at' => $end]);

        return redirect()->route('seller.space');
    }

    public function goLive(Request $request)
    {
        $seller = $this->seller($request) ?? abort(403);
        abort_unless($seller->hasProfile() && $seller->isSubscribed(), 403);

        $session = $this->sessions->current();
        if (! $session) {
            throw ValidationException::withMessages(['live' => 'Pas de session en ce moment. Reviens à '.str_replace(':', 'h', $this->sessions->next()['start']).'.']);
        }

        $existing = $seller->lives()->whereIn('status', ['pending', 'approved'])->where('ends_at', '>', now())->first();
        if (! $existing) {
            $live = $seller->lives()->create([
                'tiktok_url' => $seller->tiktokLiveUrl(),
                'status' => 'pending',
                'session_key' => $session['key'],
                'ends_at' => $session['ends_at'],
            ]);
            $live->load('seller');
            Notification::send(User::where('is_admin', true)->get(), new LiveSubmittedNotification($live));
        }

        return redirect()->route('seller.space');
    }

    public function endLive(Request $request)
    {
        $seller = $this->seller($request) ?? abort(403);
        $seller->lives()->whereIn('status', ['pending', 'approved'])->where('ends_at', '>', now())
            ->update(['status' => 'ended', 'ends_at' => now()]);

        return redirect()->route('seller.space');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['seller_id', 'pending_phone']);

        return redirect()->route('seller.space');
    }

    /** Note vocale du vendeur (lue sur la carte du live) */
    public function voice(Seller $seller)
    {
        abort_unless($seller->voice_note_path && Storage::disk('local')->exists($seller->voice_note_path), 404);

        return Storage::disk('local')->response($seller->voice_note_path);
    }

    /** Photo du produit (moniteurs de la régie, fil vertical, validation équipe) */
    public function photo(Seller $seller)
    {
        abort_unless($seller->photo_path && Storage::disk('local')->exists($seller->photo_path), 404);

        // L'URL change avec la photo (?v=…) : le navigateur peut la garder en cache
        return Storage::disk('local')->response($seller->photo_path, null, ['Cache-Control' => 'public, max-age=604800']);
    }

    private function tiktokUsername(string $input): ?string
    {
        $input = trim($input);
        if (preg_match('#tiktok\.com/@([A-Za-z0-9._]{2,24})#', $input, $m)) {
            return $m[1];
        }
        $input = ltrim($input, '@');

        return preg_match('/^[A-Za-z0-9._]{2,24}$/', $input) ? $input : null;
    }
}
