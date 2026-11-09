<script setup>
// Note vocale : le vendeur décrit son produit en parlant, sans écrire
import { onBeforeUnmount, ref } from 'vue';

const props = defineProps({ existingUrl: { type: String, default: null } });
const emit = defineEmits(['update:file']);

const state = ref('idle'); // idle | recording | done | error
const seconds = ref(0);
const url = ref(props.existingUrl);
let rec, chunks = [], timer, stream;
const MAX = 30;

const start = async () => {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (e) {
        state.value = 'error';
        return;
    }
    chunks = [];
    const type = ['audio/webm', 'audio/mp4', 'audio/ogg'].find((t) => window.MediaRecorder?.isTypeSupported?.(t)) || '';
    rec = new MediaRecorder(stream, type ? { mimeType: type } : undefined);
    rec.ondataavailable = (e) => e.data.size && chunks.push(e.data);
    rec.onstop = () => {
        const mime = rec.mimeType || 'audio/webm';
        const blob = new Blob(chunks, { type: mime });
        const ext = mime.includes('mp4') ? 'm4a' : mime.includes('ogg') ? 'ogg' : 'webm';
        url.value = URL.createObjectURL(blob);
        emit('update:file', new File([blob], `voix.${ext}`, { type: mime }));
        stream.getTracks().forEach((t) => t.stop());
        state.value = 'done';
    };
    rec.start();
    seconds.value = 0;
    state.value = 'recording';
    timer = setInterval(() => { seconds.value++; if (seconds.value >= MAX) stop(); }, 1000);
};
const stop = () => { clearInterval(timer); if (rec?.state === 'recording') rec.stop(); };
const reset = () => { url.value = null; emit('update:file', null); state.value = 'idle'; };
onBeforeUnmount(() => { clearInterval(timer); stream?.getTracks().forEach((t) => t.stop()); });
</script>

<template>
    <div class="rounded-2xl bg-sand/70 p-4">
        <div class="flex items-center gap-4">
            <button v-if="state !== 'recording'" type="button" @click="start"
                class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-ink text-cream transition hover:scale-105" aria-label="Enregistrer ma voix">
                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="currentColor"><rect x="9" y="3" width="6" height="11" rx="3" /><path d="M5 11a7 7 0 0 0 14 0M12 18v3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
            </button>
            <button v-else type="button" @click="stop"
                class="relative grid h-16 w-16 shrink-0 place-items-center rounded-full bg-live text-white" aria-label="Arrêter">
                <span class="absolute inset-0 animate-ping rounded-full bg-live opacity-30"></span>
                <span class="relative h-5 w-5 rounded-sm bg-white"></span>
            </button>
            <div class="min-w-0 flex-1">
                <p class="font-semibold">
                    <template v-if="state === 'recording'">Je t’écoute… {{ seconds }} s</template>
                    <template v-else-if="url">Ta voix est enregistrée</template>
                    <template v-else>Dis ce que tu vends</template>
                </p>
                <p v-if="state === 'error'" class="text-sm text-live">Autorise le micro pour enregistrer.</p>
                <p v-else-if="!url && state !== 'recording'" class="text-sm text-ink-mute">Appuie sur le micro · 30 s max · facultatif</p>
                <audio v-if="url && state !== 'recording'" :src="url" controls class="mt-2 h-9 w-full max-w-xs"></audio>
            </div>
            <button v-if="url && state !== 'recording'" type="button" @click="reset" class="text-sm font-semibold text-ink-mute hover:text-ink">Refaire</button>
        </div>
    </div>
</template>
