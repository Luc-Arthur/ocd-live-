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

    /*
    |--------------------------------------------------------------------------
    | Annuaire « En direct » : les vendeurs annoncent leur live TikTok,
    | l'équipe valide, le public trouve les lives par pays et par catégorie.
    |--------------------------------------------------------------------------
    */
    'directory' => [
        'subscription_price' => 5000,
        'subscription_days' => 30,

        // Sessions de validation (heure de Lomé)
        'sessions' => [
            ['key' => 'matin', 'label' => 'Session du matin', 'start' => '08:00', 'end' => '11:00'],
            ['key' => 'soir', 'label' => 'Session du soir', 'start' => '18:00', 'end' => '21:00'],
        ],

        // DÉMO : session ouverte en continu pour pouvoir montrer le site à toute heure
        'always_open' => (bool) env('OCD_SESSIONS_ALWAYS_OPEN', true),

        // DÉMO : code de connexion affiché à l'écran. En production : code envoyé par WhatsApp/SMS.
        'demo_code' => env('OCD_DEMO_CODE', '1234'),
    ],

    // Pays (position du repère sur la carte : lon / lat)
    'countries' => [
        'TG' => ['name' => 'Togo', 'dial' => '+228', 'lon' => 0.6, 'lat' => 7.0],
        'BJ' => ['name' => 'Bénin', 'dial' => '+229', 'lon' => 3.2, 'lat' => 11.0],
        'CI' => ['name' => "Côte d'Ivoire", 'dial' => '+225', 'lon' => -5.5, 'lat' => 7.6],
        'CM' => ['name' => 'Cameroun', 'dial' => '+237', 'lon' => 12.4, 'lat' => 5.2],
        'SN' => ['name' => 'Sénégal', 'dial' => '+221', 'lon' => -14.6, 'lat' => 14.6],
        'BF' => ['name' => 'Burkina Faso', 'dial' => '+226', 'lon' => -1.8, 'lat' => 12.8],
        'ML' => ['name' => 'Mali', 'dial' => '+223', 'lon' => -4.0, 'lat' => 17.0],
        'NE' => ['name' => 'Niger', 'dial' => '+227', 'lon' => 8.5, 'lat' => 16.5],
        'GN' => ['name' => 'Guinée', 'dial' => '+224', 'lon' => -11.0, 'lat' => 10.6],
        'GA' => ['name' => 'Gabon', 'dial' => '+241', 'lon' => 11.6, 'lat' => -0.6],
        'CG' => ['name' => 'Congo', 'dial' => '+242', 'lon' => 15.4, 'lat' => -0.9],
        'CD' => ['name' => 'RD Congo', 'dial' => '+243', 'lon' => 23.0, 'lat' => -3.0],
    ],

    // Catégories (une icône par catégorie côté front)
    'categories' => [
        'nourriture' => 'Nourriture',
        'habits' => 'Habits & chaussures',
        'beaute' => 'Beauté',
        'telephones' => 'Téléphones',
        'maison' => 'Maison',
        'champ' => 'Produits du champ',
        'services' => 'Services',
        'autre' => 'Autre',
    ],

    // Moyens de paiement Mobile Money proposés pour l'abonnement (démo)
    'subscription_methods' => [
        'tmoney' => 'T-Money',
        'flooz' => 'Moov Money',
        'orange' => 'Orange Money',
        'mtn' => 'MTN MoMo',
        'wave' => 'Wave',
    ],
];
