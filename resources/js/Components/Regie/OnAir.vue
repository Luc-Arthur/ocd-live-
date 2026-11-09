<script setup>
// Voyant d'antenne : rouge et lumineux pendant une session, gris sinon
defineProps({ open: Boolean, size: { type: String, default: 'md' } });
</script>

<template>
    <span v-if="open" class="on-air inline-flex shrink-0 items-center gap-2 rounded-md bg-tally font-mono font-bold tracking-[0.16em] text-white uppercase"
        :class="size === 'sm' ? 'px-2 py-1 text-[11px]' : 'px-3 py-1.5 text-[13px]'">
        <span class="tally-dot h-2 w-2 rounded-full bg-white"></span>On air
    </span>
    <span v-else class="inline-flex shrink-0 items-center gap-2 rounded-md bg-white/8 font-mono font-bold tracking-[0.16em] text-regie-mute uppercase ring-1 ring-regie-line"
        :class="size === 'sm' ? 'px-2 py-1 text-[11px]' : 'px-3 py-1.5 text-[13px]'">
        <span class="h-2 w-2 rounded-full bg-regie-mute/60"></span>Hors antenne
    </span>
</template>
