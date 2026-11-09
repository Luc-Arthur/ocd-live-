<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Live;
use App\Models\Seller;
use App\Models\SubscriptionPayment;
use App\Services\LiveSessionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Écran de l'équipe : valider ou refuser les lives annoncés */
class LiveValidationController extends Controller
{
    public function index(LiveSessionService $sessions): Response
    {
        return Inertia::render('Admin/Lives', [
            'waiting' => Live::waiting()->with('seller')->oldest()->get()->map->toCard(),
            'onAir' => Live::onAir()->with('seller')->latest('validated_at')->get()->map->toCard(),
            'session' => $sessions->state(),
            'stats' => [
                'subscribers' => Seller::where('subscription_ends_at', '>', now())->count(),
                'revenue' => (int) SubscriptionPayment::where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                'today' => Live::whereDate('created_at', today())->count(),
            ],
        ]);
    }

    public function approve(Request $request, Live $live)
    {
        abort_unless($live->status === 'pending', 422);
        $live->update(['status' => 'approved', 'validated_at' => now(), 'validated_by' => $request->user()->id]);

        return back();
    }

    public function reject(Request $request, Live $live)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:120']]);
        $live->update([
            'status' => 'rejected',
            'validated_at' => now(),
            'validated_by' => $request->user()->id,
            'reject_reason' => $data['reason'] ?? null,
        ]);

        return back();
    }

    public function end(Live $live)
    {
        $live->update(['status' => 'ended', 'ends_at' => now()]);

        return back();
    }
}
