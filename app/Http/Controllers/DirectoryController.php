<?php

namespace App\Http\Controllers;

use App\Models\Live;
use App\Services\LiveSessionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DirectoryController extends Controller
{
    public function index(Request $request, LiveSessionService $sessions): Response
    {
        $countries = config('ocdlive.countries');
        $categories = config('ocdlive.categories');

        $country = array_key_exists($request->query('pays'), $countries) ? $request->query('pays') : null;
        $category = array_key_exists($request->query('categorie'), $categories) ? $request->query('categorie') : null;

        $onAir = Live::onAir()->with('seller');

        $lives = (clone $onAir)
            ->when($country, fn ($q) => $q->whereHas('seller', fn ($s) => $s->where('country', $country)))
            ->when($category, fn ($q) => $q->whereHas('seller', fn ($s) => $s->where('category', $category)))
            ->latest('validated_at')
            ->get()
            ->map->toCard();

        return Inertia::render('Lives/Index', [
            'lives' => $lives,
            'filters' => ['pays' => $country, 'categorie' => $category],
            'countryCounts' => $this->countsBy('country', $category ? ['category' => $category] : []),
            'categoryCounts' => $this->countsBy('category', $country ? ['country' => $country] : []),
            // Total à l'antenne, tous filtres confondus (barre de la régie)
            'onAirTotal' => Live::onAir()->count(),
            // Chaînes de la régie : numérotées une fois pour toutes, sans filtre
            'channels' => $this->countsBy('country'),
            'session' => $sessions->state(),
        ]);
    }

    /** Nombre de lives à l'antenne, groupés par pays ou par catégorie du vendeur */
    public static function countsBy(string $column, array $where = []): array
    {
        return Live::onAir()
            ->join('sellers', 'sellers.id', '=', 'lives.seller_id')
            ->when($where, fn ($q) => $q->where(collect($where)->mapWithKeys(fn ($v, $k) => ["sellers.$k" => $v])->all()))
            ->selectRaw("sellers.$column as k, count(*) as n")
            ->groupBy("sellers.$column")
            ->pluck('n', 'k')
            ->map(fn ($n) => (int) $n)
            ->all();
    }
}
