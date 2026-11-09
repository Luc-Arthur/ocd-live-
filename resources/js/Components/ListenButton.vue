<script setup>
// « Écouter » : le site explique chaque étape à voix haute (synthèse vocale du téléphone)
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({ text: String, label: { type: String, default: 'Écouter' }, dark: Boolean });
const supported = ref(false);
const speaking = ref(false);

onMounted(() => { supported.value = typeof window !== 'undefined' && 'speechSynthesis' in window; });
onBeforeUnmount(() => { if (speaking.value) window.speechSynthesis.cancel(); });

const toggle = () => {
    const synth = window.speechSynthesis;
    if (speaking.value) { synth.cancel(); speaking.value = false; return; }
    synth.cancel();
    const u = new SpeechSynthesisUtterance(props.text);
    u.lang = 'fr-FR';
    u.rate = 0.95;
    const voice = synth.getVoices().find((v) => v.lang?.toLowerCase().startsWith('fr'));
    if (voice) u.voice = voice;
    u.onend = u.onerror = () => { speaking.value = false; };
    speaking.value = true;
    synth.speak(u);
};
</script>

<template>
    <button v-if="supported" type="button" @click="toggle"
        class="inline-flex shrink-0 items-center gap-2 rounded-full py-2 pr-4 pl-2 text-sm font-semibold transition"
        :class="dark ? 'bg-white/10 text-cream hover:bg-white/15' : 'bg-ember/10 text-ember-dark hover:bg-ember/15'"
        :aria-pressed="speaking">
        <span class="grid h-8 w-8 place-items-center rounded-full" :class="dark ? 'bg-glow text-night' : 'bg-ember text-white'">
            <svg v-if="!speaking" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><path d="M4 9v6h4l5 4V5L8 9H4z" /><path d="M16 8.5a5 5 0 0 1 0 7M18.5 6a8.5 8.5 0 0 1 0 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
            <svg v-else viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="2" /></svg>
        </span>
        {{ speaking ? 'Stop' : label }}
    </button>
</template>
