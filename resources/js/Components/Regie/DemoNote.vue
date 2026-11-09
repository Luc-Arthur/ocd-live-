<script setup>
import { demoNote } from '@/regie';

defineProps({ bottom: { type: String, default: 'bottom-6' } });
</script>

<template>
    <Transition name="note">
        <p v-if="demoNote" role="status"
            class="pointer-events-none fixed left-1/2 z-50 w-[min(92vw,26rem)] -translate-x-1/2 rounded-xl bg-cream px-4 py-3 text-center text-sm font-semibold text-ink shadow-[0_12px_32px_-12px_rgba(0,0,0,.6)]"
            :class="bottom">
            Vendeur de démonstration : en vrai, ce bouton ouvre son live TikTok ou son WhatsApp.
        </p>
    </Transition>
</template>
