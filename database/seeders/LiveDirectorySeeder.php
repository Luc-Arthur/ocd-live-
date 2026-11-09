<?php

namespace Database\Seeders;

use App\Models\Live;
use App\Models\Seller;
use App\Models\User;
use App\Services\LiveSessionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Vendeurs et lives fictifs pour que l'annuaire ait l'air vivant pendant la démo */
class LiveDirectorySeeder extends Seeder
{
    // Vraies photos de produits pour les premiers vendeurs (database/seeders/photos, voir CREDITS.md)
    private const PHOTOS = [
        'demo_chezafi' => 'chez-afi-saveurs.jpg',
        'demo_kphone' => 'k-phone-store.jpg',
        'demo_dzifadeco' => 'dzifa-deco.jpg',
        'demo_mawulimode' => 'mawuli-mode.jpg',
        'demo_nkoulou' => 'nkoulou-fashion.jpg',
        'demo_senajus' => 'sena-jus-naturels.jpg',
        'demo_fatou' => 'fatou-thiouraye.jpg',
    ];

    public function run(): void
    {
        if (! Seller::where('is_demo', true)->exists()) {
            $this->createDemoSellers();
        }

        $this->attachDemoPhotos();
    }

    /** Copie les photos de démo sur le disque local, pour les vendeurs de démo qui n'en ont pas encore */
    private function attachDemoPhotos(): void
    {
        $sellers = Seller::where('is_demo', true)->whereIn('tiktok_username', array_keys(self::PHOTOS))->get();

        foreach ($sellers as $seller) {
            if ($seller->photo_path && Storage::disk('local')->exists($seller->photo_path)) {
                continue;
            }
            $source = database_path('seeders/photos/'.self::PHOTOS[$seller->tiktok_username]);
            if (! is_file($source)) {
                continue;
            }
            $path = 'photos/demo-'.self::PHOTOS[$seller->tiktok_username];
            Storage::disk('local')->put($path, file_get_contents($source));
            $seller->update(['photo_path' => $path]);
        }
    }

    private function createDemoSellers(): void
    {
        $session = app(LiveSessionService::class)->current();
        // En mode démo (session ouverte en continu), les lives fictifs restent visibles 7 jours
        $endsAt = config('ocdlive.directory.always_open') ? now()->addDays(7) : ($session['ends_at'] ?? now()->addHours(3));
        $admin = User::where('is_admin', true)->first();

        // [nom, business, pays, catégorie, tiktok, statut]
        $rows = [
            ['Afi Mensah', 'Chez Afi Saveurs', 'TG', 'nourriture', 'demo_chezafi', 'approved'],
            ['Kossi Agbodjan', 'K-Phone Store', 'TG', 'telephones', 'demo_kphone', 'approved'],
            ['Yawa Dzifa', 'Dzifa Déco', 'TG', 'maison', 'demo_dzifadeco', 'approved'],
            ['Mawuli Kpeto', 'Mawuli Mode', 'TG', 'habits', 'demo_mawulimode', 'approved'],
            ['Brice Nkoulou', 'Nkoulou Fashion', 'CM', 'habits', 'demo_nkoulou', 'approved'],
            ['Estelle Ngo', 'Ndolé de Maman Estelle', 'CM', 'nourriture', 'demo_ndole', 'approved'],
            ['Junior Fotso', 'Fotso Électronique', 'CM', 'telephones', 'demo_fotso', 'approved'],
            ['Mireille Abena', 'Abena Beauty', 'CM', 'beaute', 'demo_abena', 'approved'],
            ['Paul Essomba', 'Essomba Plantain', 'CM', 'champ', 'demo_essomba', 'approved'],
            ['Rodrigue Houngbo', 'Houngbo Agro', 'BJ', 'champ', 'demo_houngbo', 'approved'],
            ['Nadège Ahouansou', 'Nadège Pagnes', 'BJ', 'habits', 'demo_nadege', 'approved'],
            ['Mariam Traoré', 'Mariam Beauté', 'CI', 'beaute', 'demo_mariam', 'approved'],
            ['Koffi Yao', 'Garba de Koffi', 'CI', 'nourriture', 'demo_garba', 'approved'],
            ['Aminata Diallo', 'Diallo Tissus', 'SN', 'habits', 'demo_diallo', 'approved'],
            ['Issa Ouédraogo', 'Ouédraogo Services', 'BF', 'services', 'demo_issa', 'approved'],
            ['Gloire Mbuyi', 'Mbuyi Wax', 'CD', 'habits', 'demo_mbuyi', 'approved'],
            ['Prisca Moussavou', 'Prisca Cosmétiques', 'GA', 'beaute', 'demo_prisca', 'approved'],
            // En attente : à valider par l'équipe pendant la démo
            ['Sena Adjovi', 'Sena Jus Naturels', 'TG', 'nourriture', 'demo_senajus', 'pending'],
            ['Arnaud Tchoua', 'Tchoua Chaussures', 'CM', 'habits', 'demo_tchoua', 'pending'],
            ['Fatou Ndiaye', 'Fatou Thiouraye', 'SN', 'beaute', 'demo_fatou', 'pending'],
        ];

        foreach ($rows as $i => [$name, $biz, $country, $cat, $tiktok, $status]) {
            $seller = Seller::create([
                'phone' => config("ocdlive.countries.$country.dial").'9000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'name' => $name,
                'business_name' => $biz,
                'country' => $country,
                'category' => $cat,
                'tiktok_username' => $tiktok,
                'subscription_ends_at' => now()->addDays(20 - $i % 15),
                'is_demo' => true,
            ]);

            $live = new Live([
                'tiktok_url' => $seller->tiktokLiveUrl(),
                'status' => $status,
                'session_key' => $session['key'] ?? 'demo',
                'ends_at' => $endsAt,
                'validated_at' => $status === 'approved' ? now()->subMinutes(3 + $i * 4) : null,
                'validated_by' => $status === 'approved' ? $admin?->id : null,
            ]);
            $live->seller()->associate($seller);
            $live->created_at = now()->subMinutes($status === 'approved' ? 6 + $i * 4 : 1 + ($i % 3) * 2);
            $live->save();
        }
    }
}
