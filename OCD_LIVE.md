# OCD Live — démo

Site de réservation de lives pour OCD Live (Laravel 12 + Vue 3 + Inertia + Tailwind 4, base SQLite).

## Ce que fait la démo

- **Accueil** : les deux catégories de live visibles dès l'ouverture (présentiel au studio de Lomé / à distance).
- **Réservation en 6 étapes** : format → jour (calendrier de décembre, disponibilités en temps réel) → créneau de 1h30 → forfait (6 packs) → infos du business → paiement Mobile Money (simulé).
- **Notifications** : à chaque réservation, l'équipe reçoit un e-mail + une notification dans l'espace équipe :
  « Telle personne (tel business) a réservé le mardi 1er décembre de 10h00 à 11h30 · à distance depuis Douala, Cameroun ».
  Le client reçoit aussi une confirmation par e-mail.
- **Espace équipe** (`/admin`) : planning du studio jour par jour, notifications, chiffre encaissé, taux de remplissage, prochains lives.
  Accès démo : `admin@ocdlive.test` / `ocdlive2026`.
- Un créneau ne peut être réservé qu'une fois (une seule régie au studio), même si deux personnes paient en même temps.

## Lancer (Windows)

Double-clique sur `demarrer.bat`. Il installe Inertia et les dépendances front la première fois, migre la base, lance le serveur, puis ouvre le tunnel Cloudflare et affiche le lien public.

Pré-requis : PHP 8.2+, Composer, Node, et `cloudflared` (`winget install --id Cloudflare.cloudflared`). Pour développer : `composer run dev`.

## Lancer à la main

```bash
composer require inertiajs/inertia-laravel:^2.0
npm install
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
cloudflared tunnel --url http://localhost:8000
```

## Réglages utiles (`.env`)

- `OCD_WHATSAPP` : numéro de l'équipe (affiche le bouton WhatsApp sur la confirmation).
- `OCD_TEAM_EMAIL` : adresse qui reçoit les réservations. En démo, les e-mails sont écrits dans `storage/logs/laravel.log` (`MAIL_MAILER=log`) ; mettre un SMTP pour les envoyer vraiment.
- Prix et contenu des forfaits : `database/seeders/DatabaseSeeder.php`. Créneaux et types d'activité : `config/ocdlive.php`.

## Pour la mise en production

- Brancher un agrégateur de paiement qui couvre le Togo et le Mobile Money (T-Money, Flooz, MTN, Orange, Wave, carte) avec confirmation par webhook, à la place du paiement simulé dans `BookingController@store`.
- Notifications WhatsApp/SMS à l'équipe en plus de l'e-mail.
- Planification des lives suivants des packs directement depuis l'espace équipe.
