<script setup>
import { computed, ref } from 'vue';
import FlagIcon from '@/Components/FlagIcon.vue';
import CategoryIcon from '@/Components/CategoryIcon.vue';
import { CAT_COLORS, waLink } from '@/utils';

const props = defineProps({ live: Object });
const s = computed(() => props.live.seller);
const colors = computed(() => CAT_COLORS[s.value.category] ?? CAT_COLORS.autre);
const initials = computed(() => (s.value.name ?? '?').split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase());
const demoNote = ref(false);

const audio = ref(null);
const playing = ref(false);
const toggleVoice = () => {
    if (!audio.value) {
        audio.value = new Audio(s.value.voice_url);
        audio.value.onended = () => (playing.value = false);
    }
    if (playing.value) { audio.value.pause(); playing.value = false; } else { audio.value.play(); playing.value = true; }
};
</script>

<template>
    <article class="flex flex-col overflow-hidden rounded-3xl border border-sand-deep bg-white shadow-card">
        <div class="relative flex items-center gap-3 px-4 pt-4 pb-3" :style="{ background: colors[0] }">
            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-white/70" :style="{ color: colors[1] }">
                <CategoryIcon :slug="s.category" :size="34" />
            </span>
            <div class="min-w-0 pr-14">
                <p class="line-clamp-2 font-display text-lg leading-tight font-extrabold">{{ s.business_name || s.name }}</p>
                <p class="mt-0.5 flex items-center gap-1.5 truncate text-sm text-ink-soft">
                    <FlagIcon :code="s.country" :size="20" /> {{ s.country_name }}
                </p>
            </div>
            <span class="absolute top-3 right-3 inline-flex items-center gap-1.5 rounded-full bg-live px-2.5 py-1 text-[11px] font-extrabold tracking-wide text-white uppercase">
                <span class="live-dot h-1.5 w-1.5 rounded-full bg-white"></span>Live
            </span>
        </div>
        <div class="flex flex-1 flex-col gap-3 px-4 pt-3 pb-4">
            <div class="flex items-center gap-2 text-sm text-ink-soft">
                <span class="grid h-7 w-7 place-items-center rounded-full bg-ink text-[11px] font-bold text-cream">{{ initials }}</span>
                <span class="truncate">{{ s.name }} · {{ s.tiktok }}</span>
            </div>
            <button v-if="s.has_voice" type="button" @click="toggleVoice"
                class="inline-flex items-center gap-2 self-start rounded-full bg-sand px-3 py-1.5 text-sm font-semibold text-ink">
                <span class="grid h-6 w-6 place-items-center rounded-full bg-ink text-cream">
                    <svg v-if="!playing" viewBox="0 0 24 24" class="h-3 w-3" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
                    <svg v-else viewBox="0 0 24 24" class="h-3 w-3" fill="currentColor"><rect x="6" y="5" width="4" height="14" /><rect x="14" y="5" width="4" height="14" /></svg>
                </span>
                Écouter le vendeur
            </button>
            <div class="mt-auto grid grid-cols-[1fr_auto] gap-2">
                <a v-if="!s.is_demo" :href="live.url" target="_blank" rel="noopener" class="btn-primary !py-3">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><path d="M8 5v14l11-7z" /></svg> Regarder le live
                </a>
                <button v-else type="button" class="btn-primary !py-3" @click="demoNote = !demoNote">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><path d="M8 5v14l11-7z" /></svg> Regarder le live
                </button>
                <a v-if="!s.is_demo" :href="waLink(s.whatsapp, `Bonjour, je vous ai vu sur OCD Live. Je suis intéressé(e) par vos produits.`)"
                    target="_blank" rel="noopener" class="btn !px-3.5 !py-3 bg-[#25D366] text-white hover:bg-[#1eb457]" aria-label="Écrire sur WhatsApp">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3z" /></svg>
                </a>
                <button v-else type="button" @click="demoNote = !demoNote" class="btn !px-3.5 !py-3 bg-[#25D366] text-white" aria-label="Écrire sur WhatsApp">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z" /></svg>
                </button>
            </div>
            <p v-if="demoNote" class="rounded-xl bg-sand px-3 py-2 text-xs text-ink-soft">Vendeur de démonstration : en vrai, ce bouton ouvre son live TikTok ou son WhatsApp.</p>
        </div>
    </article>
</template>
