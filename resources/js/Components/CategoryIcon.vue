<script setup>
// Une image par catégorie : on reconnaît sans lire
defineProps({ slug: String, size: { type: Number, default: 32 } });
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <!-- Nourriture : marmite fumante -->
        <template v-if="slug === 'nourriture'">
            <path d="M5 15h22v3a9 9 0 0 1-9 9h-4a9 9 0 0 1-9-9z" fill="currentColor" fill-opacity=".15" />
            <path d="M3 15h26M11 4c-1.5 2 1.5 3 0 5M16 3c-1.5 2 1.5 3 0 5M21 4c-1.5 2 1.5 3 0 5" />
        </template>
        <!-- Habits : robe -->
        <template v-else-if="slug === 'habits'">
            <path d="M12 3h8l-1.5 6 7 18H6.5l7-18z" fill="currentColor" fill-opacity=".15" />
            <path d="M12 3c1 2 7 2 8 0M13.5 9h5" />
        </template>
        <!-- Beauté : flacon + rouge à lèvres -->
        <template v-else-if="slug === 'beaute'">
            <rect x="5" y="12" width="10" height="15" rx="2.5" fill="currentColor" fill-opacity=".15" />
            <path d="M8 12V8h4v4M7 7h6" />
            <rect x="19" y="17" width="7" height="10" rx="1.5" fill="currentColor" fill-opacity=".15" />
            <path d="M20 17v-6l5-3v9" />
        </template>
        <!-- Téléphones -->
        <template v-else-if="slug === 'telephones'">
            <rect x="9" y="3" width="14" height="26" rx="3" fill="currentColor" fill-opacity=".15" />
            <path d="M14 6.5h4M15 25h2" />
        </template>
        <!-- Maison -->
        <template v-else-if="slug === 'maison'">
            <path d="M5 14 16 5l11 9v13H5z" fill="currentColor" fill-opacity=".15" />
            <path d="M13 27v-8h6v8" />
        </template>
        <!-- Produits du champ : pousse -->
        <template v-else-if="slug === 'champ'">
            <path d="M16 28V14" />
            <path d="M16 15c0-6 4-9 10-9 0 6-4 9-10 9z" fill="currentColor" fill-opacity=".15" />
            <path d="M16 18c0-5-3-7-9-7 0 5 3 7 9 7z" fill="currentColor" fill-opacity=".15" />
            <path d="M8 28h16" />
        </template>
        <!-- Services : clé -->
        <template v-else-if="slug === 'services'">
            <path d="M20.5 4.5a6.5 6.5 0 0 0-6 9L5 23l4 4 9.5-9.5a6.5 6.5 0 0 0 9-6l-4 1.5-3-3z" fill="currentColor" fill-opacity=".15" />
        </template>
        <!-- Autre : étincelle -->
        <template v-else>
            <path d="M16 4c1 6 3 8 9 9-6 1-8 3-9 9-1-6-3-8-9-9 6-1 8-3 9-9z" fill="currentColor" fill-opacity=".15" />
            <path d="M25 22c.4 2 1 2.6 3 3-2 .4-2.6 1-3 3-.4-2-1-2.6-3-3 2-.4 2.6-1 3-3z" />
        </template>
    </svg>
</template>
