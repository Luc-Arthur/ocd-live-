<script setup>
// /en-direct sur ordinateur : la régie du studio.
// Chaînes (pays) à gauche, PGM (le live à l'antenne) + mur de moniteurs, table de mixage (catégories) en bas.
import { Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Logo from '@/Components/Logo.vue';
import FlagIcon from '@/Components/FlagIcon.vue';
import AfricaLiveMap from '@/Components/AfricaLiveMap.vue';
import CamSignal from '@/Components/Regie/CamSignal.vue';
import Monitor from '@/Components/Regie/Monitor.vue';
import MixerKeys from '@/Components/Regie/MixerKeys.vue';
import OnAir from '@/Components/Regie/OnAir.vue';
import LomeClock from '@/Components/Regie/LomeClock.vue';
import RIcon from '@/Components/Regie/RIcon.vue';
import Act from '@/Components/Regie/Act.vue';
import DemoNote from '@/Components/Regie/DemoNote.vue';
import { gsap, reducedMotion } from '@/motion';
import { CAT_SHORT, channelList, listen, powerOn as powerScreens, sessionParts, speakingId, timecode, two, useNow, waHref } from '@/regie';

const props = defineProps({
    lives: Array, filters: Object, countryCounts: Object, categoryCounts: Object,
    onAirTotal: Number, channels: Object, session: Object,
});
const emit = defineEmits(['filter']);
const dir = computed(() => usePage().props.directory);
const now = useNow();
const reduce = reducedMotion();

/* ---------- Chaînes : numérotées sur le total, comptées avec le filtre catégorie ---------- */
const chans = computed(() => channelList(props.channels, dir.value.countries));
const chOf = computed(() => Object.fromEntries(chans.value.map((c) => [c.code, c.ch])));
const visibleChans = computed(() => {
    const list = chans.value.map((c) => ({ ...c, n: props.countryCounts[c.code] ?? 0 }));
    // Pays filtré sans aucun live : il reste affiché, pour pouvoir le désélectionner
    if (props.filters.pays && !chOf.value[props.filters.pays]) {
        list.push({ code: props.filters.pays, name: dir.value.countries[props.filters.pays].name, n: 0, ch: '--' });
    }
    return list;
});
const totalShown = computed(() => Object.values(props.countryCounts).reduce((a, b) => a + b, 0));

/* ---------- Ordre du mur : stable, les nouveaux lives validés arrivent en tête ---------- */
const filterKey = computed(() => `${props.filters.pays ?? ''}|${props.filters.categorie ?? ''}`);
const order = ref(props.lives.map((l) => l.id));
let lastKey = filterKey.value;
watch(() => props.lives, (lives) => {
    const ids = lives.map((l) => l.id);
    if (filterKey.value !== lastKey) {
        lastKey = filterKey.value;
        order.value = ids;
        nextTick(powerOn); // changement de chaîne : le mur se rallume
        return;
    }
    const present = new Set(ids);
    const kept = order.value.filter((id) => present.has(id));
    const fresh = ids.filter((id) => !order.value.includes(id));
    order.value = [...fresh, ...kept];
});
const byId = computed(() => new Map(props.lives.map((l) => [l.id, l])));
const list = computed(() => order.value.map((id) => byId.value.get(id)).filter(Boolean));
const camOf = (i) => two(i + 1);
const fillers = computed(() => Math.max(8, Math.ceil(list.value.length / 4) * 4) - list.value.length);

/* ---------- PGM : le live à l'antenne, change tout seul toutes les 8 secondes ---------- */
const pgmId = ref(list.value[0]?.id ?? null);
const pgm = computed(() => byId.value.get(pgmId.value) ?? null);
const pgmIndex = computed(() => list.value.findIndex((l) => l.id === pgmId.value));
watch(list, (l) => {
    if (!l.find((x) => x.id === pgmId.value)) pgmId.value = l[0]?.id ?? null;
});
const take = (live) => { pgmId.value = live.id; };
const next = () => {
    if (list.value.length < 2) return;
    pgmId.value = list.value[(pgmIndex.value + 1) % list.value.length].id;
};

const bar = ref(null);
let cycle = null;
const hovering = ref(false);
const hidden = ref(document.hidden);
const startCycle = () => {
    cycle?.kill();
    cycle = null;
    if (reduce || !bar.value || list.value.length < 2) {
        if (bar.value) gsap.set(bar.value, { scaleX: 0 });
        return;
    }
    cycle = gsap.fromTo(bar.value, { scaleX: 0 }, { scaleX: 1, duration: 8, ease: 'none', onComplete: next });
    if (hovering.value || hidden.value) cycle.pause();
};
watch(pgmId, () => startCycle());
// Un seul live : plus rien à faire défiler (et inversement quand un deuxième arrive)
watch(() => list.value.length, (n, o) => { if (!cycle || (n < 2) !== (o < 2)) startCycle(); });
const hold = (on) => {
    hovering.value = on;
    if (!cycle) return;
    if (on || hidden.value) cycle.pause(); else cycle.resume();
};
const onVisibility = () => {
    hidden.value = document.hidden;
    if (!cycle) return;
    if (hidden.value || hovering.value) cycle.pause(); else cycle.resume();
};

// Fondu GSAP entre deux lives (les deux couches se superposent)
const pgmEnter = (el, done) => {
    gsap.fromTo(el, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.55, ease: 'power2.out', onComplete: done });
    if (!reduce) {
        const img = el.querySelector('[data-cam]');
        if (img) gsap.fromTo(img, { scale: 1.05 }, { scale: 1, duration: 1.4, ease: 'expo.out', clearProps: 'transform' });
    }
};
const pgmLeave = (el, done) => gsap.to(el, { autoAlpha: 0, duration: 0.55, ease: 'power2.in', onComplete: done });

/* ---------- Allumage des moniteurs (ouverture de la régie, changement de chaîne) ---------- */
const root = ref(null);
let ctx;
const powerOn = () => { if (root.value) ctx.add(() => powerScreens(root.value)); };

onMounted(() => {
    ctx = gsap.context(() => {}, root.value);
    powerOn();
    startCycle();
    document.addEventListener('visibilitychange', onVisibility);
});
onBeforeUnmount(() => {
    cycle?.kill();
    ctx?.revert();
    document.removeEventListener('visibilitychange', onVisibility);
});

const sp = computed(() => sessionParts(props.session));
const setCountry = (c) => emit('filter', { pays: c ?? undefined });
const setCategory = (c) => emit('filter', { categorie: c ?? undefined });
</script>

<template>
    <div ref="root" class="regie-surface flex h-dvh min-h-[640px] flex-col bg-regie text-cream">
        <h1 class="sr-only">En direct : {{ onAirTotal }} vendeurs en live</h1>

        <!-- Barre du haut -->
        <header class="flex h-16 shrink-0 items-center gap-5 border-b border-regie-line px-5">
            <Link href="/" aria-label="Accueil OCD Live" class="shrink-0"><Logo light /></Link>
            <OnAir :open="session.open" />
            <p class="min-w-0 truncate font-mono text-[12.5px] tracking-[0.12em] text-regie-mute uppercase">
                {{ sp.label }} · <span class="font-bold text-cream">{{ sp.hours }}</span>
                <template v-if="session.open"> · <span class="tabular-nums">{{ onAirTotal }}</span> vendeurs en direct</template>
            </p>
            <div class="ml-auto flex shrink-0 items-center gap-5">
                <LomeClock />
                <Link href="/vendre" class="btn-primary !py-2.5"><span class="tally-dot h-2 w-2 rounded-full bg-white"></span>Je suis en live</Link>
            </div>
        </header>

        <div class="flex min-h-0 flex-1 gap-4 p-4">
            <!-- Chaînes -->
            <aside class="flex min-h-0 w-[220px] shrink-0 flex-col gap-3">
                <p class="px-1 font-mono text-[11px] tracking-[0.22em] text-regie-mute uppercase">Chaînes</p>
                <nav class="regie-scroll min-h-0 shrink space-y-1 overflow-y-auto pr-1" aria-label="Chaînes par pays">
                    <button type="button" class="flex min-h-11 w-full items-center gap-2.5 rounded-lg px-2.5 text-left transition"
                        :class="!filters.pays ? 'bg-ember/12 ring-1 ring-ember' : 'hover:bg-white/5'" :aria-pressed="!filters.pays" @click="setCountry(null)">
                        <span class="w-9 font-mono text-[10.5px] text-regie-mute">TOUT</span>
                        <span class="flex-1 text-[15px] font-semibold">Toute l’Afrique</span>
                        <span class="font-mono text-sm font-bold text-tally tabular-nums">{{ totalShown }}</span>
                    </button>
                    <button v-for="c in visibleChans" :key="c.code" type="button"
                        class="flex min-h-11 w-full items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-left transition"
                        :class="[filters.pays === c.code ? 'bg-ember/12 ring-1 ring-ember' : 'hover:bg-white/5', !c.n && filters.pays !== c.code && 'opacity-45']"
                        :aria-pressed="filters.pays === c.code" @click="setCountry(filters.pays === c.code ? null : c.code)">
                        <span class="w-9 font-mono text-[10.5px] text-regie-mute">CH {{ c.ch }}</span>
                        <FlagIcon :code="c.code" :size="22" />
                        <span class="flex-1 text-[15px] leading-tight font-semibold">{{ c.name }}</span>
                        <span class="relative font-mono text-sm font-bold text-tally tabular-nums">
                            {{ c.n }}
                            <span v-if="pgm && pgm.seller.country === c.code" class="tally-dot absolute top-1/2 -left-3.5 h-1.5 w-1.5 -translate-y-1/2 rounded-full bg-tally" title="À l’antenne"></span>
                        </span>
                    </button>
                </nav>
                <div class="flex min-h-[150px] flex-1 items-center justify-center rounded-2xl bg-regie-panel p-2 ring-1 ring-regie-line">
                    <AfricaLiveMap dark compact class="max-h-full" :counts="countryCounts" :selected="filters.pays" :category="filters.categorie" />
                </div>
            </aside>

            <section class="flex min-h-0 min-w-0 flex-1 flex-col gap-3">
                <div class="flex min-h-0 flex-1 gap-3">
                    <!-- PGM : le live à l'antenne -->
                    <div data-monitor class="relative isolate min-h-0 w-[34%] max-w-[440px] shrink-0 overflow-hidden rounded-2xl bg-regie-panel ring-2 ring-tally shadow-[0_0_44px_-10px_rgba(255,59,59,.6)]"
                        @mouseenter="hold(true)" @mouseleave="hold(false)" @focusin="hold(true)" @focusout="hold(false)">
                        <Transition :css="false" @enter="pgmEnter" @leave="pgmLeave">
                            <div v-if="pgm" :key="pgm.id" class="absolute inset-0">
                                <div data-cam class="absolute inset-0"><CamSignal :seller="pgm.seller" :icon="128" eager /></div>
                                <div class="pointer-events-none absolute inset-0 bg-linear-to-b from-black/50 via-transparent via-25% to-transparent"></div>
                                <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-black/90 via-black/50 via-30% to-transparent to-60%"></div>
                                <div class="absolute inset-x-0 bottom-0 p-5">
                                    <p class="line-clamp-2 font-display text-[2rem] leading-[1.05] font-extrabold text-white">{{ pgm.seller.business_name || pgm.seller.name }}</p>
                                    <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 font-mono text-[11.5px] tracking-[0.12em] text-cream/80 uppercase">
                                        <FlagIcon :code="pgm.seller.country" :size="18" />{{ pgm.seller.country_name }} · {{ CAT_SHORT[pgm.seller.category] }} · <span class="tracking-normal normal-case">{{ pgm.seller.tiktok }}</span>
                                    </p>
                                    <div class="mt-4 flex items-center gap-2">
                                        <Act :href="pgm.url" :demo="pgm.seller.is_demo" :label="`Regarder le live de ${pgm.seller.business_name || pgm.seller.name}`"
                                            class="inline-flex h-12 items-center gap-2 rounded-xl bg-ember px-5 text-base font-bold text-white transition hover:bg-ember-dark active:scale-[0.97]">
                                            <RIcon name="play" :size="16" />Regarder
                                        </Act>
                                        <button v-if="pgm.seller.has_voice" type="button" class="grid h-12 w-12 place-items-center rounded-xl bg-white/12 text-cream ring-1 ring-white/15 transition hover:bg-white/20"
                                            :aria-label="speakingId === pgm.id ? 'Arrêter' : 'Écouter le vendeur'" :aria-pressed="speakingId === pgm.id" @click="listen(pgm)">
                                            <RIcon :name="speakingId === pgm.id ? 'stop' : 'sound'" :size="20" />
                                        </button>
                                        <Act :href="waHref(pgm)" :demo="pgm.seller.is_demo" :label="`Écrire à ${pgm.seller.business_name || pgm.seller.name} sur WhatsApp`"
                                            class="inline-flex h-12 items-center gap-2 rounded-xl bg-whatsapp px-5 text-base font-bold text-white transition hover:brightness-110 active:scale-[0.97]">
                                            <RIcon name="wa" :size="18" />WhatsApp
                                        </Act>
                                    </div>
                                </div>
                            </div>
                            <div v-else key="none" class="absolute inset-0 grid place-items-center p-8 text-center">
                                <div>
                                    <p class="font-mono text-sm font-bold tracking-[0.24em] text-regie-mute uppercase">Pas de signal</p>
                                    <p class="mt-3 font-display text-2xl font-extrabold">{{ session.open ? 'Personne en live ici' : `Retour à ${session.next_start.replace(':', 'h')}` }}</p>
                                    <div class="mt-6 flex flex-wrap justify-center gap-2">
                                        <button v-if="filters.pays || filters.categorie" type="button" class="btn !py-3 bg-white/10 text-cream hover:bg-white/15" @click="emit('filter', { pays: undefined, categorie: undefined })">Tout voir</button>
                                        <Link href="/vendre" class="btn-primary !py-3">Je vends en live</Link>
                                    </div>
                                </div>
                            </div>
                        </Transition>

                        <div class="pointer-events-none absolute inset-x-4 top-4 z-20 flex items-center justify-between">
                            <span class="inline-flex items-center gap-2 rounded-md bg-tally px-2 py-1 font-mono text-[12px] font-bold tracking-[0.14em] text-white">
                                <span class="tally-dot h-1.5 w-1.5 rounded-full bg-white"></span>PGM · CH {{ pgm ? chOf[pgm.seller.country] ?? '--' : '--' }}
                            </span>
                            <time v-if="pgm" class="rounded-md bg-black/60 px-2 py-1 font-mono text-[13px] text-cream tabular-nums">{{ timecode(pgm.on_air_at, now) }}</time>
                        </div>
                        <!-- Prochain live dans… (s'arrête au survol) -->
                        <div class="absolute inset-x-0 bottom-0 z-20 h-[3px] bg-white/10" aria-hidden="true">
                            <div ref="bar" class="h-full origin-left bg-tally" style="transform: scaleX(0)"></div>
                        </div>
                        <div class="screen-fx pointer-events-none absolute inset-0 z-30 opacity-0"></div>
                    </div>

                    <!-- Mur de moniteurs : 4 colonnes, 2 rangées visibles, sans trou -->
                    <div class="regie-scroll min-h-0 min-w-0 flex-1 snap-y snap-mandatory overflow-y-auto pr-1">
                        <TransitionGroup :key="filterKey" tag="div" name="notif" class="grid h-full grid-cols-4 gap-3" style="grid-auto-rows: calc((100% - 0.75rem) / 2)">
                            <Monitor v-for="(l, i) in list" :key="l.id" class="snap-start" :live="l" :cam="camOf(i)" :pgm="l.id === pgmId" :eager="i < 8" @take="take(l)" />
                            <Link v-for="f in fillers" :key="`libre-${f}`" href="/vendre" data-monitor
                                class="group relative isolate grid snap-start place-items-center overflow-hidden rounded-xl bg-regie-panel text-center ring-1 ring-regie-line transition hover:ring-2 hover:ring-preview">
                                <span class="scanlines absolute inset-0 opacity-60"></span>
                                <span class="relative px-3">
                                    <span class="block font-mono text-[10.5px] tracking-[0.2em] text-regie-mute uppercase">Cam {{ camOf(list.length + f - 1) }} · libre</span>
                                    <span class="mt-3 inline-flex items-center gap-1.5 font-display text-[15px] font-extrabold text-cream/85 group-hover:text-white">
                                        <RIcon name="plus" :size="16" />Ta boutique ici
                                    </span>
                                </span>
                                <span class="screen-fx pointer-events-none absolute inset-0 z-30 opacity-0"></span>
                            </Link>
                        </TransitionGroup>
                    </div>
                </div>

                <!-- Table de mixage -->
                <MixerKeys class="shrink-0" :model-value="filters.categorie" :counts="categoryCounts" @update:model-value="setCategory" />
            </section>
        </div>

        <DemoNote />
    </div>
</template>
