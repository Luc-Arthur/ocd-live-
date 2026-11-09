<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FlagIcon from '@/Components/FlagIcon.vue';

const props = defineProps({
    modelValue: { type: String, default: null },
    counts: { type: Object, default: () => ({}) },
    all: { type: Boolean, default: true },
    dark: Boolean,
});
const emit = defineEmits(['update:modelValue']);
const countries = computed(() => {
    const list = Object.entries(usePage().props.directory.countries).map(([code, c]) => ({ code, ...c, n: props.counts[code] ?? 0 }));
    return list.sort((a, b) => b.n - a.n);
});
const total = computed(() => Object.values(props.counts).reduce((a, b) => a + b, 0));
const chip = (on) => (props.dark
    ? (on ? 'bg-cream text-ink' : 'bg-white/8 text-cream ring-1 ring-regie-line hover:bg-white/12')
    : (on ? 'bg-ink text-cream' : 'bg-white text-ink ring-1 ring-sand-deep'));
</script>

<template>
    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
        <button v-if="all" type="button" @click="emit('update:modelValue', null)"
            class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold transition"
            :class="chip(!modelValue)">
            Toute l’Afrique <span class="tabular-nums opacity-70">{{ total }}</span>
        </button>
        <button v-for="c in countries" :key="c.code" type="button" @click="emit('update:modelValue', modelValue === c.code ? null : c.code)"
            class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full py-2 pr-4 pl-2.5 text-sm font-semibold transition"
            :class="[chip(modelValue === c.code), !c.n && modelValue !== c.code ? 'opacity-55' : '']"
            :aria-pressed="modelValue === c.code">
            <FlagIcon :code="c.code" :size="24" /> {{ c.name }}
            <span v-if="c.n" class="tabular-nums" :class="modelValue === c.code ? (dark ? 'text-live' : 'text-glow') : (dark ? 'text-tally' : 'text-live')">{{ c.n }}</span>
        </button>
    </div>
</template>
