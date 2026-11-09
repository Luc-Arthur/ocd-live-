<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Les vendeurs qui annoncent leurs lives TikTok
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique();          // numéro WhatsApp = identifiant
            $table->string('name')->nullable();
            $table->string('business_name')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('category')->nullable();
            $table->string('tiktok_username')->nullable();
            $table->string('voice_note_path')->nullable(); // description vocale du produit
            $table->timestamp('subscription_ends_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('method');
            $table->string('payer_phone')->nullable();
            $table->string('reference')->unique();
            $table->timestamp('paid_at');
            $table->timestamp('period_ends_at');
            $table->timestamps();
        });

        // Chaque annonce « Je suis en live »
        Schema::create('lives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('tiktok_url');
            $table->string('status')->default('pending'); // pending | approved | rejected | ended
            $table->string('session_key');                // matin | soir | demo
            $table->timestamp('ends_at');                 // fin de la session : le live disparaît
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lives');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('sellers');
    }
};
