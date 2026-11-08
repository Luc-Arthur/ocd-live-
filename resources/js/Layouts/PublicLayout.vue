<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from '@/Components/Logo.vue';

const page = usePage();
const app = computed(() => page.props.app);
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-30 border-b border-sand-deep/60 bg-cream/85 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <Link href="/" aria-label="Accueil OCD Live"><Logo /></Link>
                <nav class="hidden items-center gap-7 text-sm font-medium text-ink-soft md:flex">
                    <a href="/#comment" class="hover:text-ink">Comment ça marche</a>
                    <a href="/#forfaits" class="hover:text-ink">Forfaits</a>
                    <a href="/#formats" class="hover:text-ink">Présentiel ou à distance</a>
                </nav>
                <Link href="/reserver" class="btn-primary !py-2.5">Réserver mon live</Link>
            </div>
        </header>

        <main class="flex-1"><slot /></main>

        <footer class="bg-night text-cream/70">
            <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3">
                <div>
                    <Logo light />
                    <p class="mt-3 max-w-xs text-sm">Le studio de lives des OCD. Le marketing avant la vente : place ton produit, ton numéro et ton adresse devant toute l'Afrique.</p>
                </div>
                <div class="text-sm">
                    <p class="font-semibold text-cream">Studio</p>
                    <p class="mt-2">{{ app.studio.address }}</p>
                    <p>Lives diffusés sur TikTok · {{ app.tiktok }}</p>
                </div>
                <div class="text-sm">
                    <p class="font-semibold text-cream">Liens</p>
                    <Link href="/reserver" class="mt-2 block hover:text-cream">Réserver un live</Link>
                    <Link href="/admin" class="block hover:text-cream">Espace équipe</Link>
                </div>
            </div>
            <div class="border-t border-white/10 py-5 text-center text-xs text-cream/50">© {{ new Date().getFullYear() }} OCD Live · Démo</div>
        </footer>
    </div>
</template>
