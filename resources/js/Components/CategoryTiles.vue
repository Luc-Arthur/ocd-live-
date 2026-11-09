<script setup>
// Grandes tuiles-images pour choisir ce qu'on cherche (ou ce qu'on vend)
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CategoryIcon from '@/Components/CategoryIcon.vue';
import { CAT_COLORS } from '@/utils';

const props = defineProps({
    modelValue: { type: String, default: null },
    counts: { type: Object, default: null },
    compact: Boolean,
});
const emit = defineEmits(['update:modelValue']);
const categories = computed(() => usePage().props.directory.categories);
</script>

<template>
    <div class="grid gap-2.5" :class="compact ? 'grid-cols-4 sm:grid-cols-8' : 'grid-cols-4'">
        <button v-for="(name, slug) in categories" :key="slug" type="button"
            @click="emit('update:modelValue', modelValue === slug ? null : slug)"
            class="relative flex flex-col items-center gap-1.5 rounded-2xl px-1 pt-3 pb-2.5 text-center transition"
            :class="modelValue === slug ? 'ring-[3px] ring-ink' : 'ring-1 ring-black/5 hover:-translate-y-0.5'"
            :style="{ background: CAT_COLORS[slug][0], color: CAT_COLORS[slug][1] }"
            :aria-pressed="modelValue === slug">
            <CategoryIcon :slug="slug" :size="compact ? 30 : 38" />
            <span class="text-[12px] leading-tight font-semibold text-ink sm:text-[13px]">{{ name }}</span>
            <span v-if="counts && counts[slug]" class="absolute -top-1.5 -right-1.5 grid h-6 min-w-6 place-items-center rounded-full bg-live px-1.5 text-[11px] font-bold text-white">{{ counts[slug] }}</span>
        </button>
    </div>
</template>
