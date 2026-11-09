<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Notifications\NewBookingNotification;
use App\Services\SlotService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@ocdlive.test'],
            ['name' => 'Équipe OCD Live', 'password' => Hash::make('ocdlive2026'), 'is_admin' => true],
        );

        // Prix indicatifs (FCFA) — à ajuster avec l'équipe
        $packages = [
            [
                'name' => 'Live Solo', 'slug' => 'solo', 'tagline' => 'Pour tester et te faire connaître',
                'lives_count' => 1, 'announcements_count' => 0, 'price' => 20000,
                'features' => ['1 live de 1h30', 'Régie OCD Live pendant tout le live', 'Diffusion sur le compte OCD'],
            ],
            [
                'name' => 'Live Boost', 'slug' => 'boost', 'tagline' => 'Ton live annoncé par Bovann',
                'lives_count' => 1, 'announcements_count' => 1, 'price' => 35000,
                'features' => ['1 live de 1h30', 'Vidéo d\'annonce sur le compte de Bovann', 'Régie OCD Live', 'Ton numéro et ton adresse épinglés'],
            ],
            [
                'name' => 'Pack 4 Lives', 'slug' => 'pack-4', 'tagline' => 'Un live par semaine pendant un mois',
                'lives_count' => 4, 'announcements_count' => 0, 'price' => 70000,
                'features' => ['4 lives de 1h30', 'Régie OCD Live', 'Créneaux réservés à l\'avance'],
            ],
            [
                'name' => 'Pack 4 Lives + Annonce', 'slug' => 'pack-4-annonce', 'tagline' => 'Le meilleur rapport visibilité / prix',
                'lives_count' => 4, 'announcements_count' => 1, 'price' => 85000, 'is_featured' => true,
                'features' => ['4 lives de 1h30', '1 vidéo d\'annonce par Bovann', 'Régie OCD Live', 'Ton numéro et ton adresse épinglés'],
            ],
            [
                'name' => 'Pack Mensuel', 'slug' => 'mensuel', 'tagline' => 'Deux lives par semaine, tout le mois',
                'lives_count' => 8, 'announcements_count' => 2, 'price' => 140000,
                'features' => ['8 lives de 1h30', '2 vidéos d\'annonce par Bovann', 'Régie OCD Live', 'Priorité sur les créneaux du soir'],
            ],
            [
                'name' => 'Pack Boss du Mois', 'slug' => 'boss', 'tagline' => 'Visibilité maximale pendant 30 jours',
                'lives_count' => 12, 'announcements_count' => 4, 'price' => 195000,
                'features' => ['12 lives de 1h30', '1 annonce de Bovann chaque semaine', 'Mise en avant « Business du mois »', 'Assistante dédiée pour les lives à distance'],
            ],
        ];

        foreach ($packages as $i => $p) {
            Package::updateOrCreate(['slug' => $p['slug']], [...$p, 'sort' => $i, 'is_featured' => $p['is_featured'] ?? false]);
        }

        $this->seedDemoBookings($admin);

        $this->call(LiveDirectorySeeder::class);
    }

    /** Quelques réservations fictives pour que la démo ait l'air vivante */
    private function seedDemoBookings(User $admin): void
    {
        if (Booking::exists()) {
            return;
        }

        $slots = app(SlotService::class);
        $first = $slots->firstBookableDay();

        $demo = [
            [0, '10:00', 'presentiel', 'Afi Mensah', 'Chez Afi Saveurs', 'Restauration & nourriture', 'Attiéké poisson, alloco, jus de bissap', 'Lomé', 'Togo', 'pack-4-annonce'],
            [0, '16:00', 'distance', 'Brice Nkoulou', 'Nkoulou Fashion', 'Mode & vêtements', 'Chemises en pagne, ensembles wax sur mesure', 'Douala', 'Cameroun', 'boost'],
            [0, '18:00', 'presentiel', 'Kossi Agbodjan', 'K-Phone Store', 'Téléphones & électronique', 'Smartphones reconditionnés, accessoires', 'Lomé', 'Togo', 'solo'],
            [1, '12:00', 'distance', 'Mariam Traoré', 'Mariam Beauté', 'Beauté & cosmétiques', 'Beurre de karité, savons naturels', 'Abidjan', "Côte d'Ivoire", 'pack-4'],
            [1, '20:00', 'distance', 'Rodrigue Houngbo', 'Houngbo Agro', 'Agriculture & produits locaux', 'Gari, huile rouge, piment séché', 'Cotonou', 'Bénin', 'mensuel'],
            [2, '10:00', 'presentiel', 'Yawa Dzifa', 'Dzifa Déco', 'Artisanat & décoration', 'Paniers tressés, poterie', 'Lomé', 'Togo', 'solo'],
            [3, '14:00', 'distance', 'Aminata Diallo', 'Diallo Tissus', 'Mode & vêtements', 'Bazin, voiles, broderie', 'Dakar', 'Sénégal', 'boss'],
        ];

        foreach ($demo as $i => [$offset, $start, $mode, $name, $biz, $type, $products, $city, $country, $slug]) {
            $package = Package::where('slug', $slug)->first();
            $booking = Booking::create([
                'reference' => Booking::newReference(),
                'package_id' => $package->id,
                'mode' => $mode,
                'live_date' => $first->copy()->addDays($offset)->toDateString(),
                'start_time' => $start,
                'end_time' => $slots->endTime($start),
                'full_name' => $name,
                'business_name' => $biz,
                'business_type' => $type,
                'products' => $products,
                'phone' => '+228 90 00 00 0'.$i,
                'city' => $city,
                'country' => $country,
                'amount' => $package->price,
                'payment_method' => $i % 2 ? 'flooz' : 'tmoney',
                'payment_phone' => '+228 90 00 00 0'.$i,
                'payment_status' => 'paid',
                'payment_reference' => 'SIM-DEMO000'.$i,
                'paid_at' => now(),
                'created_at' => Carbon::now()->subMinutes(($i + 1) * 47),
            ]);
            $booking->load('package');

            $notification = new NewBookingNotification($booking);
            $admin->notifications()->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => NewBookingNotification::class,
                'data' => $notification->toArray($admin),
                'read_at' => $i > 2 ? now() : null,
                'created_at' => $booking->created_at,
            ]);
        }
    }
}
