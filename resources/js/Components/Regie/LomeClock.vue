<script setup>
// Horloge de la régie, à l'heure de Lomé
import { lomeTime, useNow } from '@/regie';

defineProps({ size: { type: String, default: 'lg' } });
const now = useNow();
</script>

<template>
    <span class="inline-flex items-baseline gap-2 font-mono tabular-nums">
        <time class="font-bold text-glow" :class="size === 'lg' ? 'text-[1.9rem] leading-none tracking-[0.04em]' : 'text-base'"
            style="text-shadow: 0 0 18px rgb(246 199 125 / 0.35)">{{ lomeTime(now) }}</time>
        <span class="text-[11px] font-medium tracking-[0.18em] text-regie-mute uppercase">Lomé</span>
    </span>
</template>
