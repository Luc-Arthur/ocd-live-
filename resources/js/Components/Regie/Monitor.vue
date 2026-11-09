<script setup>
// Un moniteur du mur : photo ou signal caméra, voyant LIVE, code temporel, Regarder / WhatsApp.
// Cliquer sur l'image l'envoie au PGM (voyant rouge), le survol l'allume en vert (aperçu).
import { computed } from 'vue';
import FlagIcon from '@/Components/FlagIcon.vue';
import CamSignal from '@/Components/Regie/CamSignal.vue';
import RIcon from '@/Components/Regie/RIcon.vue';
import Act from '@/Components/Regie/Act.vue';
import { timecode, useNow, waHref } from '@/regie';

const props = defineProps({ live: Object, cam: String, pgm: Boolean, small: Boolean, eager: Boolean });
const emit = defineEmits(['take']);
const now = useNow();
const s = computed(() => props.live.seller);
const name = computed(() => s.value.business_name || s.value.name);
</script>

<template>
    <article data-monitor class="monitor group relative isolate min-h-0 overflow-hidden rounded-xl bg-regie-panel ring-1 transition-[box-shadow] duration-200"
        :class="pgm
            ? 'ring-2 ring-tally shadow-[0_0_26px_-6px_rgba(255,59,59,.7)]'
            : 'ring-regie-line hover:ring-2 hover:ring-preview focus-within:ring-2 focus-within:ring-preview'"
        :aria-label="`${name}, ${s.country_name}`">
        <CamSignal :seller="s" :icon="small ? 52 : 64" :eager="eager" />
        <!-- Assombrit le haut et le bas : le texte reste lisible sur une photo claire -->
        <div class="pointer-events-none absolute inset-0 bg-linear-to-b from-black/55 via-transparent via-25% to-transparent"></div>
        <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-black/90 via-black/55 via-30% to-transparent to-60%"></div>

        <!-- L'image entière sert à envoyer ce live au PGM -->
        <button v-if="!small" type="button" class="absolute inset-0 z-10 cursor-pointer" :aria-label="`Mettre ${name} à l’antenne`" :aria-pressed="pgm" @click="emit('take')"></button>

        <div class="pointer-events-none absolute inset-x-2.5 top-2.5 z-20 flex items-center justify-between gap-2">
            <span class="inline-flex items-center gap-1.5 rounded bg-tally px-1.5 py-0.5 font-mono text-[10px] font-bold tracking-[0.14em] text-white">
                <span class="tally-dot h-1.5 w-1.5 rounded-full bg-white"></span>{{ pgm ? 'PGM' : 'LIVE' }}
            </span>
            <time class="rounded bg-black/60 px-1.5 py-0.5 font-mono text-[11px] text-cream/90 tabular-nums">{{ timecode(live.on_air_at, now) }}</time>
        </div>

        <div class="absolute inset-x-0 bottom-0 z-20 p-2.5">
            <p class="line-clamp-2 font-display text-[15px] leading-[1.15] font-extrabold text-white text-shadow-soft">{{ name }}</p>
            <p class="mt-1 flex items-center gap-1.5 font-mono text-[10px] tracking-[0.1em] text-cream/85 uppercase text-shadow-soft">
                <FlagIcon :code="s.country" :size="14" /><span class="truncate">Cam {{ cam }} · {{ s.country_name }}</span>
            </p>
            <div class="mt-2 grid grid-cols-[1fr_auto] gap-1.5">
                <Act :href="live.url" :demo="s.is_demo" :label="`Regarder le live de ${name}`"
                    class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg bg-ember text-[13px] font-bold text-white transition hover:bg-ember-dark active:scale-[0.97]">
                    <RIcon name="play" :size="14" />Regarder
                </Act>
                <Act :href="waHref(live)" :demo="s.is_demo" :label="`Écrire à ${name} sur WhatsApp`"
                    class="grid h-11 w-11 place-items-center rounded-lg bg-whatsapp text-white transition hover:brightness-110 active:scale-[0.97]">
                    <RIcon name="wa" :size="20" />
                </Act>
            </div>
        </div>

        <!-- Allumage : écran noir, éclair blanc, puis l'image -->
        <div class="screen-fx pointer-events-none absolute inset-0 z-30 opacity-0"></div>
    </article>
</template>
