<script setup>
// Bandeau de session : « En direct · se termine dans 1h12 » ou « Prochaine session dans 2h14 »
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({ session: Object, compact: Boolean });
const now = ref(Date.now());
let t;
onMounted(() => { t = setInterval(() => (now.value = Date.now()), 1000); });
onBeforeUnmount(() => clearInterval(t));

const left = (iso) => {
    const s = Math.max(0, Math.floor((new Date(iso).getTime() - now.value) / 1000));
    const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
    return h ? `${h}h${String(m).padStart(2, '0')}` : `${m}:${String(sec).padStart(2, '0')}`;
};
const text = computed(() => props.session.open
    ? `Se termine dans ${left(props.session.ends_at)}`
    : `Prochaine session à ${props.session.next_start.replace(':', 'h')} · dans ${left(props.session.next_starts_at)}`);
</script>

<template>
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 rounded-2xl px-4 py-3"
        :class="session.open ? 'bg-live/10 text-ink' : 'bg-sand text-ink-soft'">
        <span class="inline-flex items-center gap-2 font-display text-sm font-extrabold tracking-wide uppercase"
            :class="session.open ? 'text-live' : 'text-ink-mute'">
            <span class="h-2.5 w-2.5 rounded-full" :class="session.open ? 'live-dot bg-live' : 'bg-ink-mute/50'"></span>
            {{ session.open ? 'En direct' : 'Pause' }}
        </span>
        <span class="text-sm font-semibold tabular-nums">{{ text }}</span>
        <span v-if="!compact" class="text-xs text-ink-mute">Sessions : {{ session.slots.join(' et ') }}</span>
        <span v-if="session.demo" class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-ink-mute ring-1 ring-sand-deep">Démo : ouvert en continu</span>
    </div>
</template>
