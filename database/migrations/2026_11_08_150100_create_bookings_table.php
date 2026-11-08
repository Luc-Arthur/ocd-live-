<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('package_id')->constrained();
            $table->enum('mode', ['presentiel', 'distance']);

            // Premier live réservé (les suivants d'un pack sont planifiés avec l'équipe)
            $table->date('live_date');
            $table->string('start_time', 5);
            $table->string('end_time', 5);

            // Le business
            $table->string('full_name');
            $table->string('business_name');
            $table->string('business_type');
            $table->text('products');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('city');
            $table->string('country');
            $table->string('business_address')->nullable();
            $table->string('social_handle')->nullable();

            // Paiement
            $table->unsignedInteger('amount');
            $table->string('payment_method');
            $table->string('payment_phone')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->string('status')->default('confirmed');
            $table->timestamps();

            // Un seul live par créneau (une seule régie au studio)
            $table->unique(['live_date', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
