<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted } from 'vue';
import Logo from '@/Components/Logo.vue';

// dark : en-tête et barre du bas en version régie (accueil)
// bare : page plein écran en régie (/en-direct) : ni en-tête ni pied de page, la page dessine les siens
const props = defineProps({ dark: Boolean, bare: Boolean });

const page = usePage();
const app = computed(() => page.props.app);
const url = computed(() => page.url);
const is = (p) => (p === '/' ? url.value === '/' : url.value.startsWith(p));
const night = computed(() => props.dark || props.bare);

// Page entièrement sombre : fond, barres de défilement et rebond du navigateur en régie
onMounted(() => { if (props.bare) document.documentElement.classList.add('regie'); });
onBeforeUnmount(() => document.documentElement.classList.remove('regie'));
</script>

<template>
    <div class="flex min-h-screen flex-col" :class="bare && 'bg-regie'">
        <header v-if="!bare" class="sticky top-0 z-30 border-b backdrop-blur"
            :class="dark ? 'border-regie-line bg-regie/90 text-cream' : 'border-sand-deep/60 bg-cream/85'">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <Link href="/" aria-label="Accueil OCD Live"><Logo :light="dark" /></Link>
                <nav class="hidden items-center gap-7 text-sm font-semibold md:flex" :class="dark ? 'text-cream/70' : 'text-ink-soft'">
                    <Link href="/en-direct" class="inline-flex items-center gap-2" :class="dark ? 'hover:text-cream' : ['hover:text-ink', is('/en-direct') && 'text-ink']"><span class="live-dot h-2 w-2 rounded-full bg-live"></span>En direct</Link>
                    <Link href="/vendre" :class="dark ? 'hover:text-cream' : ['hover:text-ink', is('/vendre') && 'text-ink']">Je vends</Link>
                    <Link href="/#studio" :class="dark ? 'hover:text-cream' : ['hover:text-ink', is('/reserv') && 'text-ink']">Studio Lomé</Link>
                </nav>
                <Link href="/vendre" class="btn-primary !py-2.5"><span class="live-dot h-2 w-2 rounded-full bg-white"></span>Je suis en live</Link>
            </div>
        </header>

        <main class="flex-1" :class="bare ? 'pb-[calc(4rem+env(safe-area-inset-bottom))] lg:pb-0' : 'pb-16 md:pb-0'"><slot /></main>

        <footer v-if="!bare" class="bg-night pb-20 text-cream/70 md:pb-0">
            <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3">
                <div>
                    <Logo light />
                    <p class="mt-3 max-w-xs text-sm">Les vendeurs africains en live, au même endroit. Par pays, par catégorie.</p>
                </div>
                <div class="text-sm">
                    <p class="font-semibold text-cream">Studio</p>
                    <p class="mt-2">{{ app.studio.address }}</p>
                    <p>TikTok · {{ app.tiktok }}</p>
                </div>
                <div class="text-sm">
                    <p class="font-semibold text-cream">Liens</p>
                    <Link href="/en-direct" class="mt-2 block hover:text-cream">En direct</Link>
                    <Link href="/vendre" class="block hover:text-cream">Je vends en live</Link>
                    <Link href="/reserver" class="block hover:text-cream">Réserver le studio</Link>
                    <Link href="/admin" class="block hover:text-cream">Espace équipe</Link>
                </div>
            </div>
            <div class="border-t border-white/10 py-5 text-center text-xs text-cream/50">© {{ new Date().getFullYear() }} OCD Live · Démo</div>
        </footer>

        <!-- Barre du bas sur téléphone : 3 grosses icônes -->
        <nav class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-3 border-t backdrop-blur"
            :class="[bare ? 'lg:hidden' : 'md:hidden', night ? 'border-regie-line bg-regie/95' : 'border-sand-deep bg-cream/95']"
            style="padding-bottom: env(safe-area-inset-bottom, 0px)" aria-label="Navigation principale">
            <Link href="/en-direct" class="flex h-16 flex-col items-center justify-center gap-1 text-[12px] font-semibold"
                :class="is('/en-direct') || is('/') ? (night ? 'text-white' : 'text-ink') : (night ? 'text-cream/60' : 'text-ink-mute')"
                :aria-current="is('/en-direct') ? 'page' : undefined">
                <span class="relative grid h-7 w-8 place-items-center">
                    <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="13" rx="3" /><path d="M10 9.5v4l3.5-2z" fill="currentColor" /><path d="M8 21h8" stroke-linecap="round" /></svg>
                    <span class="live-dot absolute top-0 right-0 h-2.5 w-2.5 rounded-full bg-live ring-2" :class="night ? 'ring-regie' : 'ring-cream'"></span>
                </span>En direct
            </Link>
            <Link href="/vendre" class="flex h-16 flex-col items-center justify-center gap-1 text-[12px] font-semibold"
                :class="is('/vendre') ? 'text-ember-dark' : (night ? 'text-cream/60' : 'text-ink-mute')">
                <span class="grid h-7 w-8 place-items-center rounded-full bg-live text-[9px] font-extrabold tracking-wider text-white">LIVE</span>Je vends
            </Link>
            <Link href="/reserver" class="flex h-16 flex-col items-center justify-center gap-1 text-[12px] font-semibold"
                :class="is('/reserv') ? (night ? 'text-white' : 'text-ink') : (night ? 'text-cream/60' : 'text-ink-mute')">
                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6" stroke-linecap="round" stroke-linejoin="round" /></svg>Studio
            </Link>
        </nav>
    </div>
</template>
