<script setup>
defineProps({ light: { type: Boolean, default: false } });
</script>

<template>
    <span class="inline-flex items-center gap-2 select-none">
        <span class="font-display text-[1.65rem] leading-none font-extrabold tracking-tight text-ember">OCD</span>
        <span class="font-display text-lg leading-none font-extrabold" :class="light ? 'text-cream' : 'text-ink'">Live</span>
        <span class="live-dot ml-0.5 h-2 w-2 rounded-full bg-live" aria-hidden="true"></span>
        <span class="sr-only">OCD Live</span>
    </span>
</template>
