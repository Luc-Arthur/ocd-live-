<?php

return [

    // Premier jour de lives
    'launch_date' => env('OCD_LAUNCH_DATE', '2026-12-01'),

    // Nombre de jours ouverts à la réservation après le lancement / aujourd'hui
    'booking_window_days' => (int) env('OCD_BOOKING_WINDOW_DAYS', 90),

    // Créneaux quotidiens (heure de Lomé). Chaque live dure 1h30.
    'live_duration_minutes' => 90,
    'slots' => ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00'],

    // Le studio
    'studio' => [
        'city' => 'Lomé',
        'country' => 'Togo',
        'address' => env('OCD_STUDIO_ADDRESS', 'Studio OCD Live, Lomé'),
    ],

    // Compte TikTok sur lequel les lives sont diffusés / annoncés
    'tiktok_account' => env('OCD_TIKTOK_ACCOUNT', '@bovann'),

    // Contact de l'équipe (WhatsApp, format international sans +)
    'whatsapp' => env('OCD_WHATSAPP', ''),

    // Adresse qui reçoit les notifications de réservation
    'team_email' => env('OCD_TEAM_EMAIL', 'reservations@ocdlive.test'),

    'currency' => 'FCFA',

    'business_types' => [
        'Restauration & nourriture',
        'Mode & vêtements',
        'Beauté & cosmétiques',
        'Téléphones & électronique',
        'Artisanat & décoration',
        'Agriculture & produits locaux',
        'Services',
        'Autre',
    ],

    'payment_methods' => [
        'tmoney' => 'T-Money',
        'flooz' => 'Flooz (Moov Money)',
        'mobile_money' => 'Autre Mobile Money (MTN, Orange, Wave…)',
        'card' => 'Carte bancaire',
    ],
];
