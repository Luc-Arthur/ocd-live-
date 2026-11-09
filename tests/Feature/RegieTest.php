<?php

namespace Tests\Feature;

use App\Models\Live;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegieTest extends TestCase
{
    use RefreshDatabase;

    private function sellerOnAir(array $attrs = []): Seller
    {
        $seller = Seller::create([
            'phone' => '+22890000001', 'name' => 'Afi Mensah', 'business_name' => 'Chez Afi', 'country' => 'TG',
            'category' => 'nourriture', 'tiktok_username' => 'chezafi', 'subscription_ends_at' => now()->addDays(10), ...$attrs,
        ]);
        $seller->lives()->create([
            'tiktok_url' => $seller->tiktokLiveUrl(), 'status' => 'approved', 'session_key' => 'demo',
            'ends_at' => now()->addHours(2), 'validated_at' => now()->subMinutes(41),
        ]);

        return $seller;
    }

    public function test_la_regie_recoit_les_lives_les_chaines_et_l_heure_du_serveur(): void
    {
        $this->sellerOnAir();

        $this->get('/en-direct')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Lives/Index')
            ->has('lives', 1)
            ->where('onAirTotal', 1)
            ->where('channels.TG', 1)
            ->has('lives.0.on_air_at')
            ->where('lives.0.seller.photo_url', null)
            ->has('session.now'));
    }

    public function test_la_photo_du_vendeur_est_enregistree_puis_servie(): void
    {
        Storage::fake('local');
        $seller = $this->sellerOnAir();

        $this->withSession(['seller_id' => $seller->id])->post('/vendre/profil', [
            'name' => 'Afi Mensah', 'country' => 'TG', 'category' => 'nourriture', 'tiktok' => '@chezafi',
            'photo' => UploadedFile::fake()->image('produit.jpg', 1080, 1440),
        ])->assertRedirect('/vendre')->assertSessionHasNoErrors();

        $seller->refresh();
        Storage::disk('local')->assertExists($seller->photo_path);
        $this->assertStringStartsWith('photos/', $seller->photo_path);

        $url = $seller->toCard()['photo_url'];
        $this->assertNotNull($url);
        $this->get($url)->assertOk();
    }

    public function test_un_fichier_qui_n_est_pas_une_photo_est_refuse(): void
    {
        Storage::fake('local');
        $seller = $this->sellerOnAir();

        $this->withSession(['seller_id' => $seller->id])->post('/vendre/profil', [
            'name' => 'Afi Mensah', 'country' => 'TG', 'category' => 'nourriture', 'tiktok' => '@chezafi',
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('photo');

        $this->assertNull($seller->fresh()->photo_path);
    }
}
