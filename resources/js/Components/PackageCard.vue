<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { fcfa } from '@/utils';

const props = defineProps({
    pkg: Object,
    href: { type: String, default: null },
    selected: { type: Boolean, default: false },
});
const emit = defineEmits(['select']);

const BASE = 20000;
const saving = computed(() => {
    const full = BASE * props.pkg.lives_count;
    return props.pkg.lives_count > 1 && props.pkg.price < full ? full - props.pkg.price : 0;
});
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href"
        :type="href ? undefined : 'button'"
        @click="!href && emit('select', pkg)"
        class="relative flex h-full w-full flex-col rounded-2xl border bg-white p-6 text-left transition active:scale-[0.98]"
        :class="[
            selected ? 'border-ember ring-2 ring-ember/30 shadow-glow' : 'border-sand-deep shadow-card hover:border-ink-mute',
            pkg.is_featured && !selected ? 'border-ember/50' : '',
        ]"
        :aria-pressed="href ? undefined : selected"
    >
        <span v-if="pkg.is_featured" class="absolute -top-3 left-6 rounded-full bg-ember px-3 py-1 text-xs font-semibold text-white">Le plus choisi</span>
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold">{{ pkg.name }}</h3>
                <p class="mt-0.5 text-sm text-ink-soft">{{ pkg.tagline }}</p>
            </div>
            <span v-if="selected" class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-ember text-white">
                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path d="M7.7 13.3 4.4 10l-1.1 1.1 4.4 4.4 9-9-1.1-1.1z" /></svg>
            </span>
        </div>
        <p class="mt-5 font-display whitespace-nowrap"><span class="text-3xl font-extrabold">{{ new Intl.NumberFormat('fr-FR').format(pkg.price).replace(/\u202f|\u00a0/g, ' ') }}</span> <span class="text-sm font-bold text-ink-soft">FCFA</span></p>
        <p class="mt-1 text-xs text-ink-mute">
            <template v-if="pkg.lives_count > 1">{{ fcfa(pkg.price_per_live) }} par live</template>
            <template v-else>1 live de 1h30</template>
            <span v-if="saving" class="ml-1 inline-block whitespace-nowrap rounded-full bg-leaf/10 px-2 py-0.5 font-semibold text-leaf">−{{ fcfa(saving) }}</span>
        </p>
        <ul class="mt-5 space-y-2 border-t border-sand pt-5 text-sm text-ink-soft">
            <li v-for="f in pkg.features" :key="f" class="flex gap-2">
                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-ember"></span>{{ f }}
            </li>
        </ul>
    </component>
</template>
