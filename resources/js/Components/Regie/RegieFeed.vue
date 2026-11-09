<script setup>
// /en-direct sur téléphone : un vendeur par écran, on glisse vers le haut pour le suivant (comme TikTok).
import { Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import FlagIcon from '@/Components/FlagIcon.vue';
import CategoryIcon from '@/Components/CategoryIcon.vue';
import CategoryTiles from '@/Components/CategoryTiles.vue';
import CamSignal from '@/Components/Regie/CamSignal.vue';
import RIcon from '@/Components/Regie/RIcon.vue';
import Act from '@/Components/Regie/Act.vue';
import DemoNote from '@/Components/Regie/DemoNote.vue';
import OnAir from '@/Components/Regie/OnAir.vue';
import { gsap, reducedMotion } from '@/motion';
import { CAT_SHORT, channelList, listen, shareLive, speakingId, timecode, useNow, waHref } from '@/regie';
import { CAT_COLORS } from '@/utils';

const props = defineProps({
    lives: Array, filters: Object, countryCounts: Object, categoryCounts: Object,
    onAirTotal: Number, channels: Object, session: Object,
});
const emit = defineEmits(['filter']);
const dir = computed(() => usePage().props.directory);
const now = useNow();

const chOf = computed(() => Object.fromEntries(channelList(props.channels, dir.value.countries).map((c) => [c.code, c.ch])));
const countries = computed(() => Object.entries(dir.value.countries)
    .map(([code, c]) => ({ code, name: c.name, n: props.countryCounts[code] ?? 0 }))
    .sort((a, b) => b.n - a.n || a.name.localeCompare(b.name, 'fr')));
const total = computed(() => Object.values(props.countryCounts).reduce((a, b) => a + b, 0));
const countryLabel = computed(() => (props.filters.pays ? dir.value.countries[props.filters.pays].name : 'Toute l’Afrique'));
const countryCount = computed(() => (props.filters.pays ? props.countryCounts[props.filters.pays] ?? 0 : total.value));

/* ---------- Ordre stable : un nouveau live validé arrive juste après l'écran en cours ---------- */
const scroller = ref(null);
const index = ref(0);
const filterKey = computed(() => `${props.filters.pays ?? ''}|${props.filters.categorie ?? ''}`);
const order = ref(props.lives.map((l) => l.id));
const fresh = ref(new Set());
let lastKey = filterKey.value;
watch(() => props.lives, (lives) => {
    const ids = lives.map((l) => l.id);
    if (filterKey.value !== lastKey) {
        lastKey = filterKey.value;
        order.value = ids;
        index.value = 0;
        scroller.value?.scrollTo({ top: 0 });
        return;
    }
    const present = new Set(ids);
    const kept = order.value.filter((id) => present.has(id));
    const added = ids.filter((id) => !order.value.includes(id));
    const at = Math.min(index.value + 1, kept.length);
    order.value = [...kept.slice(0, at), ...added, ...kept.slice(at)];
    if (added.length) {
        fresh.value = new Set(added);
        setTimeout(() => (fresh.value = new Set()), 2000);
    }
});
const byId = computed(() => new Map(props.lives.map((l) => [l.id, l])));
const list = computed(() => order.value.map((id) => byId.value.get(id)).filter(Boolean));

/* ---------- Écran en cours ---------- */
let io;
const observe = () => {
    io?.disconnect();
    if (!scroller.value || !('IntersectionObserver' in window)) return;
    io = new IntersectionObserver((entries) => {
        for (const e of entries) if (e.isIntersecting) index.value = Number(e.target.dataset.i);
    }, { root: scroller.value, threshold: 0.6 });
    scroller.value.querySelectorAll('[data-slide]').forEach((el) => io.observe(el));
};
watch(list, () => nextTick(observe));

// Indice « Glisse » : affiché jusqu'au premier geste, puis plus jamais
const HINT = 'ocd.feed.swiped';
const hintSeen = ref(false);
try { hintSeen.value = localStorage.getItem(HINT) === '1'; } catch { /* stockage indisponible */ }
watch(index, (i) => {
    if (i > 0 && !hintSeen.value) {
        hintSeen.value = true;
        try { localStorage.setItem(HINT, '1'); } catch { /* stockage indisponible */ }
    }
});

// Barres de position : au plus 15, centrées sur l'écran en cours
const bars = computed(() => {
    const n = list.value.length;
    const max = 15;
    const start = n <= max ? 0 : Math.min(Math.max(0, index.value - 7), n - max);
    return Array.from({ length: Math.min(n, max) }, (_, k) => start + k);
});

/* ---------- Filtres : deux boutons en haut, chacun ouvre une feuille du bas ---------- */
const sheet = ref(null); // 'pays' | 'categories' | null
const closeBtn = ref(null);
watch(sheet, (open) => { if (open) nextTick(() => closeBtn.value?.focus()); });
const onKey = (e) => { if (e.key === 'Escape') sheet.value = null; };
const pickCategory = (c) => { sheet.value = null; emit('filter', { categorie: c ?? undefined }); };
const pickCountry = (c) => { sheet.value = null; emit('filter', { pays: c ?? undefined }); };

// Pendant qu'on glisse d'un vendeur à l'autre, les boutons s'effacent pour laisser toute la place à l'image
const scrolling = ref(false);
let scrollTimer = 0;
const onScroll = () => {
    scrolling.value = true;
    clearTimeout(scrollTimer);
    scrollTimer = setTimeout(() => (scrolling.value = false), 450);
};

/* ---------- Allumage du premier écran ---------- */
const root = ref(null);
let ctx;
onMounted(() => {
    observe();
    window.addEventListener('keydown', onKey);
    ctx = gsap.context(() => {
        const first = root.value.querySelector('[data-slide] .screen-fx');
        if (!first) return;
        gsap.set(first, { autoAlpha: 1, backgroundColor: '#000' });
        if (reducedMotion()) {
            gsap.to(first, { autoAlpha: 0, duration: 0.45, ease: 'power1.out' });
        } else {
            gsap.timeline().set(first, { backgroundColor: '#fff' }, 0.08).to(first, { autoAlpha: 0, duration: 0.35, ease: 'power2.out' }, 0.16);
        }
    }, root.value);
});
onBeforeUnmount(() => {
    io?.disconnect();
    clearTimeout(scrollTimer);
    window.removeEventListener('keydown', onKey);
    ctx?.revert();
});

const nameOf = (s) => s.business_name || s.name;
const catName = computed(() => (props.filters.categorie ? CAT_SHORT[props.filters.categorie] : null));
</script>

<template>
    <div ref="root" class="regie-surface relative bg-regie text-cream">
        <h1 class="sr-only">En direct : {{ onAirTotal }} vendeurs en live</h1>

        <div ref="scroller" class="feed overflow-y-scroll" aria-label="Vendeurs en live, un par écran" @scroll.passive="onScroll">
            <article v-for="(l, i) in list" :key="l.id" data-slide :data-i="i" class="relative h-full overflow-hidden"
                :aria-label="`${nameOf(l.seller)}, ${l.seller.country_name}, ${l.seller.category_name}`">
                <CamSignal :seller="l.seller" :icon="150" :eager="i < 2" />
                <div class="pointer-events-none absolute inset-0 bg-linear-to-b from-black/75 via-transparent via-28% to-transparent"></div>
                <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-black/90 via-black/25 via-35% to-transparent to-60%"></div>
                <div v-if="fresh.has(l.id)" class="pointer-events-none absolute inset-0 z-10 ring-4 ring-tally ring-inset notif-enter-active"></div>

                <!-- En haut : voyant, chaîne, code temporel -->
                <div class="absolute inset-x-3 top-3 z-10 flex items-center gap-1.5 font-mono text-[11.5px] font-bold tracking-[0.1em] uppercase">
                    <span class="inline-flex items-center gap-1.5 rounded bg-tally px-2 py-1 text-white"><span class="tally-dot h-1.5 w-1.5 rounded-full bg-white"></span>Live</span>
                    <span class="inline-flex min-w-0 items-center gap-1.5 rounded bg-black/55 px-2 py-1 text-cream">
                        CH {{ chOf[l.seller.country] ?? '--' }} · <FlagIcon :code="l.seller.country" :size="16" /><span class="truncate">{{ l.seller.country_name }}</span>
                    </span>
                    <time class="ml-auto rounded bg-black/55 px-2 py-1 text-cream tabular-nums">{{ timecode(l.on_air_at, now) }}</time>
                </div>

                <!-- Colonne d'actions, comme TikTok -->
                <div class="absolute right-2.5 bottom-24 z-10 flex w-[4.5rem] flex-col items-center gap-3 text-[12px] font-semibold text-white">
                    <Act :href="l.url" :demo="l.seller.is_demo" :label="`Regarder le live de ${nameOf(l.seller)}`" class="flex flex-col items-center gap-1 text-shadow-soft">
                        <span class="grid h-16 w-16 place-items-center rounded-full bg-ember shadow-[0_10px_28px_-8px_rgba(217,98,43,.9)] ring-2 ring-white/25 transition active:scale-95"><RIcon name="play" :size="28" /></span>Regarder
                    </Act>
                    <Act :href="waHref(l)" :demo="l.seller.is_demo" :label="`Écrire à ${nameOf(l.seller)} sur WhatsApp`" class="flex flex-col items-center gap-1 text-shadow-soft">
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-whatsapp shadow-[0_8px_22px_-8px_rgba(37,211,102,.8)] transition active:scale-95"><RIcon name="wa" :size="26" /></span>WhatsApp
                    </Act>
                    <button type="button" class="flex flex-col items-center gap-1 text-shadow-soft" :aria-pressed="speakingId === l.id" @click="listen(l)">
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-black/45 ring-1 ring-white/25 backdrop-blur-sm transition active:scale-95"
                            :class="speakingId === l.id && '!bg-cream !text-ink'"><RIcon :name="speakingId === l.id ? 'stop' : 'sound'" :size="24" /></span>{{ speakingId === l.id ? 'Stop' : 'Écouter' }}
                    </button>
                    <button type="button" class="flex flex-col items-center gap-1 text-shadow-soft" @click="shareLive(l)">
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-black/45 ring-1 ring-white/25 backdrop-blur-sm transition active:scale-95"><RIcon name="share" :size="24" /></span>Partager
                    </button>
                </div>

                <!-- En bas à gauche : qui vend quoi -->
                <div class="absolute right-[5.75rem] bottom-11 left-4 z-10">
                    <h2 class="line-clamp-2 font-display text-[2rem] leading-[1.04] font-extrabold text-white text-shadow-soft">{{ nameOf(l.seller) }}</h2>
                    <p class="mt-1 truncate text-[15px] text-cream/90 text-shadow-soft">{{ l.seller.name }} · {{ l.seller.tiktok }}</p>
                    <span class="mt-2.5 inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 font-mono text-[11.5px] font-bold tracking-[0.1em] text-white uppercase"
                        :style="{ background: (CAT_COLORS[l.seller.category] ?? CAT_COLORS.autre)[1] }">
                        <CategoryIcon :slug="l.seller.category" :size="16" />{{ CAT_SHORT[l.seller.category] }}
                    </span>
                </div>

                <p v-if="i === 0 && !hintSeen && list.length > 1" class="hint-rise pointer-events-none absolute inset-x-0 bottom-3 z-10 flex items-center justify-center gap-1.5 text-[13px] font-semibold text-cream text-shadow-soft">
                    <RIcon name="up" :size="15" />Glisse pour le vendeur suivant
                </p>
                <div class="screen-fx pointer-events-none absolute inset-0 z-30 opacity-0"></div>
            </article>

            <!-- Personne en live -->
            <article v-if="!list.length" data-slide data-i="0" class="relative grid h-full place-items-center px-6 text-center">
                <div class="scanlines absolute inset-0 opacity-50"></div>
                <div class="relative">
                    <OnAir :open="session.open" class="mx-auto" />
                    <p class="mt-5 font-display text-3xl font-extrabold">{{ session.open ? 'Personne en live ici' : `Retour à ${session.next_start.replace(':', 'h')}` }}</p>
                    <div class="mt-7 grid gap-2.5">
                        <button v-if="filters.pays || filters.categorie" type="button" class="btn h-14 bg-cream text-base text-ink" @click="emit('filter', { pays: undefined, categorie: undefined })">Tout voir</button>
                        <Link href="/vendre" class="btn-primary h-14 text-base">Je vends en live</Link>
                    </div>
                </div>
            </article>
        </div>

        <!-- Où ? Quoi ? Deux boutons compacts, qui s'effacent pendant qu'on glisse -->
        <div class="pointer-events-none absolute inset-x-0 top-12 z-20 flex gap-2 px-3 transition duration-200 ease-out"
            :class="scrolling && !sheet ? 'opacity-0 motion-safe:-translate-y-1.5' : 'opacity-100'">
            <button type="button" class="pointer-events-auto inline-flex h-11 min-w-0 items-center gap-2 rounded-full pr-3 pl-3 text-sm font-bold shadow-[0_6px_16px_-8px_rgba(0,0,0,.8)] transition"
                :class="filters.pays ? 'bg-cream text-ink' : 'bg-black/45 text-white ring-1 ring-white/20 backdrop-blur-sm'"
                aria-haspopup="dialog" :aria-expanded="sheet === 'pays'" @click="sheet = 'pays'">
                <FlagIcon v-if="filters.pays" :code="filters.pays" :size="20" /><RIcon v-else name="globe" :size="18" />
                <span class="truncate">{{ countryLabel }}</span>
                <span class="tabular-nums" :class="filters.pays ? 'text-live' : 'text-cream/70'">{{ countryCount }}</span>
                <RIcon name="chevron" :size="14" class="opacity-70" />
            </button>
            <button type="button" class="pointer-events-auto inline-flex h-11 shrink-0 items-center gap-2 rounded-full pr-3 pl-3.5 text-sm font-bold shadow-[0_6px_16px_-8px_rgba(0,0,0,.8)] transition"
                :class="catName ? 'bg-ember text-white' : 'bg-black/45 text-white ring-1 ring-white/20 backdrop-blur-sm'"
                aria-haspopup="dialog" :aria-expanded="sheet === 'categories'" @click="sheet = 'categories'">
                <RIcon name="grid" :size="16" />{{ catName ?? 'Catégories' }}
                <RIcon name="chevron" :size="14" class="opacity-70" />
            </button>
        </div>

        <!-- Position dans le fil -->
        <div v-if="list.length > 1" class="pointer-events-none absolute top-1/2 right-1 z-20 flex -translate-y-1/2 flex-col items-center gap-1" aria-hidden="true">
            <span v-for="k in bars" :key="k" class="w-[3px] rounded-full transition-all duration-300"
                :class="k === index ? 'h-6 bg-white' : 'h-2.5 bg-white/35'"></span>
        </div>

        <DemoNote bottom="bottom-[5.5rem]" />

        <!-- Feuilles du bas : pays, catégories -->
        <Teleport to="body">
            <Transition name="veil">
                <div v-if="sheet" class="fixed inset-0 z-50 bg-black/65" @click="sheet = null"></div>
            </Transition>
            <Transition name="sheet">
                <div v-if="sheet" role="dialog" aria-modal="true" :aria-label="sheet === 'pays' ? 'Pays' : 'Catégories'"
                    class="regie-surface fixed inset-x-0 bottom-0 z-50 max-h-[85dvh] overflow-y-auto rounded-t-3xl bg-regie-panel px-4 pt-3 pb-[calc(1.25rem+env(safe-area-inset-bottom))] text-cream ring-1 ring-regie-line">
                    <div class="mx-auto h-1.5 w-10 rounded-full bg-white/20"></div>
                    <div class="mt-2 flex items-center justify-between">
                        <p class="font-display text-xl font-extrabold">{{ sheet === 'pays' ? 'Dans quel pays ?' : 'Tu cherches quoi ?' }}</p>
                        <button ref="closeBtn" type="button" class="grid h-11 w-11 place-items-center rounded-full bg-white/10" aria-label="Fermer" @click="sheet = null">
                            <RIcon name="close" :size="20" />
                        </button>
                    </div>

                    <template v-if="sheet === 'pays'">
                        <button type="button" class="mt-3 flex h-14 w-full items-center gap-3 rounded-2xl px-4 text-left text-base font-bold transition"
                            :class="!filters.pays ? 'bg-cream text-ink' : 'bg-white/8 ring-1 ring-regie-line'" :aria-pressed="!filters.pays" @click="pickCountry(null)">
                            <RIcon name="globe" :size="22" /><span class="flex-1">Toute l’Afrique</span>
                            <span class="font-mono tabular-nums" :class="!filters.pays ? 'text-live' : 'text-tally'">{{ total }}</span>
                        </button>
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            <button v-for="c in countries" :key="c.code" type="button"
                                class="relative flex min-h-[5.25rem] flex-col items-center justify-center gap-1.5 rounded-2xl px-1 py-2.5 text-center text-[13px] leading-tight font-semibold transition"
                                :class="[filters.pays === c.code ? 'bg-cream text-ink' : 'bg-white/8 ring-1 ring-regie-line', !c.n && filters.pays !== c.code && 'opacity-45']"
                                :aria-pressed="filters.pays === c.code" @click="pickCountry(filters.pays === c.code ? null : c.code)">
                                <FlagIcon :code="c.code" :size="34" />{{ c.name }}
                                <span v-if="c.n" class="absolute top-1.5 right-2 font-mono text-[11px] font-bold tabular-nums" :class="filters.pays === c.code ? 'text-live' : 'text-tally'">{{ c.n }}</span>
                            </button>
                        </div>
                    </template>

                    <template v-else>
                        <CategoryTiles class="mt-3" :model-value="filters.categorie" :counts="categoryCounts" @update:model-value="pickCategory" />
                        <button v-if="filters.categorie" type="button" class="btn mt-3 h-12 w-full bg-white/10 text-base text-cream" @click="pickCategory(null)">Tout voir</button>
                    </template>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
