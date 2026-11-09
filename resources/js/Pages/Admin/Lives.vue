<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Logo from '@/Components/Logo.vue';
import FlagIcon from '@/Components/FlagIcon.vue';
import CategoryIcon from '@/Components/CategoryIcon.vue';
import SessionBanner from '@/Components/SessionBanner.vue';
import { CAT_COLORS, fcfa } from '@/utils';

const props = defineProps({ waiting: Array, onAir: Array, session: Object, stats: Object });
const page = usePage();
const rejecting = ref(null);
const reasons = ['Pas en live', 'Ne vend rien', 'Mauvais lien TikTok', 'Contenu interdit'];

const approve = (l) => router.post(`/admin/lives/${l.id}/valider`, {}, { preserveScroll: true });
const reject = (l, reason) => { router.post(`/admin/lives/${l.id}/refuser`, { reason }, { preserveScroll: true }); rejecting.value = null; };
const end = (l) => router.post(`/admin/lives/${l.id}/terminer`, {}, { preserveScroll: true });

const playing = ref(null);
let audio;
const play = (l) => {
    if (playing.value === l.id) { audio?.pause(); playing.value = null; return; }
    audio?.pause();
    audio = new Audio(l.seller.voice_url);
    audio.onended = () => (playing.value = null);
    audio.play();
    playing.value = l.id;
};

let timer;
onMounted(() => { timer = setInterval(() => router.reload({ only: ['waiting', 'onAir', 'stats', 'session'] }), 8000); });
onBeforeUnmount(() => { clearInterval(timer); audio?.pause(); });
</script>

<template>
    <Head title="Lives à valider" />
    <div class="min-h-screen bg-cream">
        <header class="bg-night text-cream">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
                <Link href="/"><Logo light /></Link>
                <nav class="flex items-center gap-1 text-sm font-semibold">
                    <Link href="/admin" class="rounded-full px-3 py-1.5 text-cream/70 hover:text-cream">Studio</Link>
                    <Link href="/admin/lives" class="rounded-full bg-white/10 px-3 py-1.5">Lives</Link>
                    <Link href="/admin/deconnexion" method="post" as="button" class="ml-2 hidden text-cream/60 hover:text-cream sm:inline">Déconnexion</Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
            <SessionBanner :session="session" />
            <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-2xl bg-white p-3 ring-1 ring-sand-deep"><p class="font-display text-2xl font-extrabold">{{ stats.subscribers }}</p><p class="text-xs text-ink-mute">abonnés actifs</p></div>
                <div class="rounded-2xl bg-white p-3 ring-1 ring-sand-deep"><p class="font-display text-base font-extrabold whitespace-nowrap sm:text-2xl">{{ fcfa(stats.revenue) }}</p><p class="text-xs text-ink-mute">abonnements ce mois</p></div>
                <div class="rounded-2xl bg-white p-3 ring-1 ring-sand-deep"><p class="font-display text-2xl font-extrabold">{{ stats.today }}</p><p class="text-xs text-ink-mute">lives aujourd’hui</p></div>
            </div>

            <!-- À valider -->
            <h1 class="mt-8 flex items-center gap-3 text-2xl font-bold">
                À valider <span class="grid h-8 min-w-8 place-items-center rounded-full px-2 text-sm text-white" :class="waiting.length ? 'bg-live' : 'bg-ink-mute/50'">{{ waiting.length }}</span>
            </h1>
            <p v-if="!waiting.length" class="mt-3 rounded-2xl border border-dashed border-sand-deep bg-white px-5 py-8 text-center text-ink-mute">Aucun live en attente. Les nouveaux apparaissent ici tout seuls.</p>
            <TransitionGroup tag="ul" name="notif" class="mt-4 grid gap-3 md:grid-cols-2">
                <li v-for="l in waiting" :key="l.id" class="rounded-3xl bg-white p-4 shadow-card ring-1 ring-sand-deep">
                    <!-- La photo envoyée par le vendeur : l'équipe vérifie qu'elle montre bien ce qu'il vend -->
                    <a v-if="l.seller.photo_url" :href="l.seller.photo_url" target="_blank" rel="noopener" class="mb-3 block overflow-hidden rounded-2xl ring-1 ring-sand-deep" aria-label="Voir la photo en grand">
                        <img :src="l.seller.photo_url" :alt="`Photo envoyée par ${l.seller.business_name || l.seller.name}`" class="aspect-[16/10] w-full object-cover" loading="lazy" />
                    </a>
                    <div class="flex items-center gap-3">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl" :style="{ background: CAT_COLORS[l.seller.category][0], color: CAT_COLORS[l.seller.category][1] }"><CategoryIcon :slug="l.seller.category" :size="28" /></span>
                        <div class="min-w-0">
                            <p class="truncate font-display text-lg font-extrabold">{{ l.seller.business_name || l.seller.name }}</p>
                            <p class="flex items-center gap-1.5 truncate text-sm text-ink-soft"><FlagIcon :code="l.seller.country" :size="18" />{{ l.seller.country_name }} · {{ l.seller.category_name }}</p>
                        </div>
                        <span class="ml-auto shrink-0 text-xs text-ink-mute">{{ l.created_ago }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                        <a :href="l.url" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full bg-ink px-3 py-1.5 font-semibold text-cream">Ouvrir le live {{ l.seller.tiktok }} ↗</a>
                        <button v-if="l.seller.has_voice" type="button" @click="play(l)" class="rounded-full bg-sand px-3 py-1.5 font-semibold">{{ playing === l.id ? 'Pause' : 'Écouter sa voix' }}</button>
                    </div>
                    <div v-if="rejecting === l.id" class="mt-3 flex flex-wrap gap-2">
                        <button v-for="r in reasons" :key="r" type="button" class="rounded-full bg-live/10 px-3 py-2 text-sm font-semibold text-live" @click="reject(l, r)">{{ r }}</button>
                        <button type="button" class="px-2 text-sm text-ink-mute" @click="rejecting = null">Annuler</button>
                    </div>
                    <div v-else class="mt-4 grid grid-cols-[1fr_auto] gap-2">
                        <button type="button" class="btn !py-3.5 bg-leaf text-base text-white hover:bg-leaf/90" @click="approve(l)">
                            <svg viewBox="0 0 20 20" class="h-5 w-5" fill="currentColor"><path d="M7.7 13.3 4.4 10l-1.1 1.1 4.4 4.4 9-9-1.1-1.1z" /></svg>Valider
                        </button>
                        <button type="button" class="btn-ghost !px-5 !py-3.5" @click="rejecting = l.id">Refuser</button>
                    </div>
                </li>
            </TransitionGroup>

            <!-- À l'antenne -->
            <h2 class="mt-10 flex items-center gap-3 text-2xl font-bold"><span class="live-dot h-3 w-3 rounded-full bg-live"></span>En direct sur le site · {{ onAir.length }}</h2>
            <ul class="mt-4 divide-y divide-sand rounded-3xl bg-white ring-1 ring-sand-deep">
                <li v-for="l in onAir" :key="l.id" class="flex items-center gap-3 px-4 py-3">
                    <img v-if="l.seller.photo_url" :src="l.seller.photo_url" alt="" class="h-10 w-10 shrink-0 rounded-lg object-cover" loading="lazy" />
                    <FlagIcon :code="l.seller.country" :size="24" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ l.seller.business_name || l.seller.name }}</p>
                        <p class="truncate text-xs text-ink-mute">{{ l.seller.category_name }} · validé {{ l.since }}</p>
                    </div>
                    <a :href="l.url" target="_blank" rel="noopener" class="hidden text-sm font-semibold text-ink-soft hover:text-ink sm:inline">Voir ↗</a>
                    <button type="button" class="rounded-full bg-sand px-3 py-1.5 text-sm font-semibold" @click="end(l)">Terminer</button>
                </li>
                <li v-if="!onAir.length" class="px-4 py-6 text-center text-sm text-ink-mute">Personne à l’antenne.</li>
            </ul>
        </main>
    </div>
</template>
