<script setup>
// Bouton d'action d'un moniteur : ouvre TikTok ou WhatsApp, ou explique pour un vendeur de démonstration
import { flashDemoNote } from '@/regie';

defineProps({ href: String, demo: Boolean, label: String });
</script>

<template>
    <button v-if="demo" type="button" :aria-label="label" @click="flashDemoNote"><slot /></button>
    <a v-else :href="href" target="_blank" rel="noopener" :aria-label="label"><slot /></a>
</template>
