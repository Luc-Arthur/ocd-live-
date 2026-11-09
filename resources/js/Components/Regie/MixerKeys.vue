<script setup>
// Table de mixage : chaque catégorie est une touche de mélangeur vidéo.
// Rouge = catégorie à l'antenne (filtre actif), vert = touche survolée (aperçu).
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CategoryIcon from '@/Components/CategoryIcon.vue';
import { CAT_SHORT } from '@/regie';

const props = defineProps({
    modelValue: { type: String, default: null },
    counts: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue']);
const slugs = computed(() => Object.keys(usePage().props.directory.categories));
const fullName = (slug) => usePage().props.directory.categories[slug];
</script>

<template>
    <div class="flex items-stretch gap-3 rounded-2xl bg-regie-panel p-3 ring-1 ring-regie-line">
        <span class="hidden rotate-180 self-center font-mono text-[10px] tracking-[0.3em] text-regie-mute uppercase [writing-mode:vertical-rl] sm:block" aria-hidden="true">Catégories</span>
        <div class="grid flex-1 grid-cols-4 gap-2 sm:grid-cols-8 sm:gap-2.5" role="group" aria-label="Catégories">
            <button v-for="slug in slugs" :key="slug" type="button"
                class="mix-key relative flex min-h-[4.25rem] flex-col items-center justify-center gap-1.5 rounded-xl px-1 py-2"
                :class="modelValue === slug
                    ? 'border-[#7a0f12] bg-[#c4161c] text-white shadow-[0_0_0_2px_var(--color-tally),0_0_24px_-4px_rgba(255,59,59,.75)]'
                    : ['border-black/70 bg-[#241b15] text-cream/85 hover:border-[#0b3a22] hover:bg-[#10422a] hover:text-white hover:shadow-[0_0_0_2px_var(--color-preview),0_0_20px_-6px_rgba(47,210,122,.7)]', !counts[slug] && 'opacity-55']"
                :aria-pressed="modelValue === slug" :aria-label="`${fullName(slug)} : ${counts[slug] ?? 0} en live`"
                @click="emit('update:modelValue', modelValue === slug ? null : slug)">
                <CategoryIcon :slug="slug" :size="24" />
                <span class="font-mono text-[10.5px] leading-none font-bold tracking-[0.1em] uppercase">{{ CAT_SHORT[slug] }}</span>
                <span v-if="counts[slug]" class="absolute top-1.5 right-2 font-mono text-[10px] font-bold tabular-nums opacity-80">{{ counts[slug] }}</span>
            </button>
        </div>
    </div>
</template>
