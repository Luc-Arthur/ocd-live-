<script setup>
// La carte de l'Afrique sert de menu : chaque pays avec des vendeurs en live s'allume.
// `dark` : version régie, contour lumineux comme le mur du studio et repères rouges.
// `compact` : petite carte (colonne des chaînes), sans les noms de pays.
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { coast, madagascar, X, Y, path } from '@/africa';

const props = defineProps({
    counts: { type: Object, default: () => ({}) },
    selected: { type: String, default: null },
    category: { type: String, default: null },
    dark: Boolean,
    compact: Boolean,
});
const countries = computed(() => usePage().props.directory.countries);
const id = (n) => `${n}-${props.dark ? 'd' : 'l'}${props.compact ? 'c' : ''}`;

// Côté de l'étiquette pour éviter les chevauchements
const side = { TG: 's', BJ: 'e', CI: 'w', CM: 'e', SN: 'n', BF: 'n', ML: 'n', NE: 'e', GN: 's', GA: 'w', CG: 'e', CD: 'e' };
const pins = computed(() => Object.entries(countries.value).map(([code, c]) => {
    const n = props.counts[code] ?? 0;
    const x = X(c.lon), y = Y(c.lat);
    const s = side[code] ?? 'e';
    const lx = s === 'e' ? x + 27 : s === 'w' ? x - 27 : x;
    const ly = s === 'n' ? y - 30 : s === 's' ? y + 40 : y + 6;
    const anchor = s === 'e' ? 'start' : s === 'w' ? 'end' : 'middle';
    return { code, name: c.name, n, x, y, lx, ly, anchor };
}).sort((a, b) => a.n - b.n));

// Petite carte : simples repères (les nombres sont dans la liste des chaînes)
const r = computed(() => (props.compact ? 15 : 20));
const pinFill = (code) => {
    if (props.selected === code) return props.dark ? '#FBF6EF' : '#1C1410';
    return props.dark ? '#FF3B3B' : '#D9622B';
};
const pinText = (code) => (props.dark && props.selected === code ? '#0D0907' : '#fff');

const go = (code) => router.get('/en-direct', { pays: props.selected === code ? undefined : code, categorie: props.category ?? undefined }, { preserveScroll: true });
</script>

<template>
    <!-- Marge de 40 autour du continent : le halo lumineux et le Sénégal (tout à l'ouest) ne sont pas coupés -->
    <svg viewBox="-40 -40 800 820" class="h-auto w-full select-none" role="img" aria-label="Carte de l'Afrique : touche un pays pour voir ses lives">
        <defs>
            <filter :id="id('mapglow')" x="-20%" y="-20%" width="140%" height="140%">
                <feGaussianBlur :stdDeviation="dark ? 12 : 9" result="b" />
                <feMerge><feMergeNode in="b" /><feMergeNode in="b" /><feMergeNode in="SourceGraphic" /></feMerge>
            </filter>
            <radialGradient :id="id('mapfill')" cx="40%" cy="40%" r="70%">
                <template v-if="dark"><stop offset="0%" stop-color="#2A1F18" /><stop offset="100%" stop-color="#140E0B" /></template>
                <template v-else><stop offset="0%" stop-color="#FFFDF9" /><stop offset="100%" stop-color="#F5EADC" /></template>
            </radialGradient>
        </defs>
        <!-- Régie : la lumière déborde derrière le contour, comme les LED du mur du studio -->
        <g v-if="dark" :filter="`url(#${id('mapglow')})`" opacity=".85">
            <path :d="path(coast)" fill="none" stroke="#F6A94A" stroke-width="14" stroke-linejoin="round" />
            <path :d="path(madagascar)" fill="none" stroke="#F6A94A" stroke-width="14" stroke-linejoin="round" />
        </g>
        <g :filter="dark ? undefined : `url(#${id('mapglow')})`">
            <path :d="path(coast)" :fill="`url(#${id('mapfill')})`" :stroke="dark ? '#FFD9A0' : '#F6C77D'" :stroke-width="dark ? 3.5 : 5" stroke-linejoin="round" />
            <path :d="path(madagascar)" :fill="`url(#${id('mapfill')})`" :stroke="dark ? '#FFD9A0' : '#F6C77D'" :stroke-width="dark ? 3.5 : 5" stroke-linejoin="round" />
        </g>

        <g v-for="p in pins" :key="p.code" class="map-pin cursor-pointer outline-none" role="link" tabindex="0"
            :aria-label="`${p.name} : ${p.n} en live`" @click="go(p.code)" @keydown.enter="go(p.code)">
            <g v-if="p.n > 0" data-pin>
                <circle class="pin-focus" :cx="p.x" :cy="p.y" :r="r + 9" fill="none" :stroke="dark ? '#F6C77D' : '#D9622B'" stroke-width="3" />
                <circle class="pulse-ring" :cx="p.x" :cy="p.y" :r="r - 4" :fill="dark ? '#FF3B3B' : '#E5383B'" opacity=".45" />
                <circle :cx="p.x" :cy="p.y" :r="r" :fill="pinFill(p.code)" :stroke="dark ? '#0D0907' : '#fff'" stroke-width="4" />
                <text v-if="!compact" :x="p.x" :y="p.y + 6.5" text-anchor="middle" font-size="19" font-weight="800" :fill="pinText(p.code)" font-family="Bricolage Grotesque, sans-serif">{{ p.n }}</text>
                <text v-if="!compact" :x="p.lx" :y="p.ly" :text-anchor="p.anchor" font-size="17" font-weight="600" :fill="dark ? '#FBF6EF' : '#1C1410'" font-family="Instrument Sans, sans-serif"
                    paint-order="stroke" :stroke="dark ? '#0D0907' : '#FBF6EF'" stroke-width="5" stroke-linejoin="round">{{ p.name }}</text>
            </g>
            <template v-else>
                <circle :cx="p.x" :cy="p.y" r="14" fill="transparent" />
                <circle :cx="p.x" :cy="p.y" :r="compact ? 8 : 6" :fill="dark ? '#6B5A4C' : '#8A796C'" :opacity="dark ? 0.8 : 0.45" />
            </template>
        </g>
    </svg>
</template>

<style scoped>
.pin-focus { opacity: 0; }
.map-pin:focus-visible .pin-focus { opacity: 1; }
</style>
