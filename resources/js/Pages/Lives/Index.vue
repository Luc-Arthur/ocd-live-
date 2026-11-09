<script setup>
// /en-direct : la régie sur ordinateur, le fil vertical (façon TikTok) sur téléphone
import { Head, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import RegieDesk from '@/Components/Regie/RegieDesk.vue';
import RegieFeed from '@/Components/Regie/RegieFeed.vue';
import { stopListening, syncServerTime } from '@/regie';

const props = defineProps({
    lives: Array, filters: Object, countryCounts: Object, categoryCounts: Object,
    onAirTotal: Number, channels: Object, session: Object,
});

watch(() => props.session.now, syncServerTime, { immediate: true });

// Un seul affichage monté à la fois (pas de double minuterie ni de photos chargées deux fois)
const mq = window.matchMedia('(min-width: 1024px)');
const desk = ref(mq.matches);
const onMq = (e) => (desk.value = e.matches);

const visit = (patch) => router.get('/en-direct', {
    pays: props.filters.pays ?? undefined,
    categorie: props.filters.categorie ?? undefined,
    ...patch,
}, { preserveScroll: true, preserveState: true });

// Les nouveaux lives validés apparaissent sans recharger
let timer;
onMounted(() => {
    mq.addEventListener('change', onMq);
    timer = setInterval(() => router.reload({ only: ['lives', 'countryCounts', 'categoryCounts', 'onAirTotal', 'channels', 'session'] }), 20000);
});
onBeforeUnmount(() => {
    mq.removeEventListener('change', onMq);
    clearInterval(timer);
    stopListening();
});
</script>

<template>
    <Head title="En direct" />
    <PublicLayout bare>
        <RegieDesk v-if="desk" v-bind="props" @filter="visit" />
        <RegieFeed v-else v-bind="props" @filter="visit" />
    </PublicLayout>
</template>
