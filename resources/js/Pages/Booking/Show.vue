<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { fcfa, hh, ucfirst } from '@/utils';
import { gsap, EASE, useMotion } from '@/motion';

const props = defineProps({ booking: Object });
const page = usePage();
const wa = computed(() => {
    const n = page.props.app.whatsapp;
    if (!n) return null;
    const text = `Bonjour OCD Live, j'ai réservé un live (réf. ${props.booking.reference}) le ${props.booking.date_label} à ${hh(props.booking.start_time)}.`;
    return `https://wa.me/${n}?text=${encodeURIComponent(text)}`;
});
const prep = computed(() => props.booking.mode === 'presentiel'
    ? ['Arrive 30 minutes avant ton créneau au studio de Lomé', 'Apporte 30 à 45 produits ou échantillons', 'Prépare ton numéro et ton adresse à afficher', 'Pense à ce que tu veux dire en 1 minute sur ton business']
    : ['Teste ta connexion et charge ton téléphone', 'Installe-toi dans un endroit éclairé, avec tes produits visibles', 'L’animatrice te contacte avant le live pour caler le déroulé', 'Prépare ton numéro et ton adresse à afficher']);

const badge = ref(null);
const burst = ref(null);
const check = ref(null);
const intro = ref(null);
const ticket = ref(null);
const after = ref(null);

// Le paiement est passé : la coche se trace, puis le billet sort comme d'une imprimante
useMotion((reduce) => {
    const lines = intro.value.querySelectorAll('h1, p');
    const cards = after.value.children;
    const ticketLines = ticket.value.querySelectorAll('[data-ticket]');

    if (reduce) {
        gsap.timeline({ defaults: { duration: 0.4, ease: 'power1.out' } })
            .from([badge.value, ...lines], { autoAlpha: 0 })
            .from([ticket.value, ...cards], { autoAlpha: 0, stagger: 0.05 }, 0.15);
        return;
    }

    const len = check.value.getTotalLength();
    gsap.set(check.value, { strokeDasharray: len, strokeDashoffset: len });
    gsap.set([...lines, ...ticketLines, ...cards], { autoAlpha: 0, y: 16 });
    gsap.set(ticket.value, { clipPath: 'inset(0% 0% 100% 0% round 24px)' });

    gsap.timeline({ defaults: { ease: EASE } })
        .fromTo(badge.value, { scale: 0.5, autoAlpha: 0 }, { scale: 1, autoAlpha: 1, duration: 0.6 }, 0)
        .to(check.value, { strokeDashoffset: 0, duration: 0.45, ease: 'power2.out' }, 0.18)
        .fromTo(burst.value, { scale: 1, opacity: 0.7 }, { scale: 2.4, opacity: 0, duration: 0.9, ease: 'power2.out' }, 0.22)
        .to(lines, { autoAlpha: 1, y: 0, duration: 0.7, stagger: 0.07 }, 0.25)
        .to(ticket.value, { clipPath: 'inset(0% 0% 0% 0% round 24px)', duration: 0.95, ease: 'power3.out', clearProps: 'clipPath' }, 0.45)
        .to(ticketLines, { autoAlpha: 1, y: 0, duration: 0.6, stagger: 0.06, clearProps: 'transform' }, 0.6)
        .to(cards, { autoAlpha: 1, y: 0, duration: 0.7, stagger: 0.08, clearProps: 'transform' }, 0.9);
});
</script>

<template>
    <Head title="Live confirmé" />
    <PublicLayout>
        <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
            <div ref="intro" class="text-center">
                <span class="relative mx-auto grid h-14 w-14 place-items-center">
                    <span ref="burst" class="absolute inset-0 rounded-full border-2 border-leaf opacity-0" aria-hidden="true"></span>
                    <span ref="badge" class="grid h-14 w-14 place-items-center rounded-full bg-leaf text-white">
                        <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path ref="check" d="M5 12.5l4.3 4.3L19 7" /></svg>
                    </span>
                </span>
                <h1 class="mt-5 text-3xl font-bold sm:text-4xl">C’est réservé, {{ booking.full_name.split(' ')[0] }} !</h1>
                <p class="mt-2 text-ink-soft">L’équipe OCD Live vient d’être prévenue. <template v-if="booking.email">Une confirmation part aussi sur {{ booking.email }}.</template></p>
            </div>

            <!-- Billet -->
            <div ref="ticket" class="relative mt-10 overflow-hidden rounded-3xl bg-night text-cream shadow-card">
                <div class="flex items-center justify-between border-b border-dashed border-white/15 px-6 py-4 sm:px-8">
                    <span class="flex items-center gap-2 text-xs font-semibold tracking-[0.14em] text-glow uppercase"><span class="live-dot h-2 w-2 rounded-full bg-live"></span> Billet de live</span>
                    <span class="font-mono text-sm text-cream/70">{{ booking.reference }}</span>
                </div>
                <div class="grid gap-6 px-6 py-7 sm:grid-cols-[1.3fr_1fr] sm:px-8">
                    <div data-ticket>
                        <p class="text-sm text-cream/60">Date et heure</p>
                        <p class="mt-1 font-display text-2xl font-extrabold sm:text-3xl">{{ ucfirst(booking.date_label) }}</p>
                        <p class="font-display text-xl font-bold text-glow">{{ hh(booking.start_time) }} – {{ hh(booking.end_time) }}</p>
                        <p class="mt-4 text-sm text-cream/80">{{ booking.mode_label }}</p>
                    </div>
                    <dl data-ticket class="space-y-3 text-sm">
                        <div><dt class="text-cream/60">Business</dt><dd class="font-semibold">{{ booking.business_name }} · {{ booking.business_type }}</dd></div>
                        <div><dt class="text-cream/60">Forfait</dt><dd class="font-semibold">{{ booking.package.name }}</dd></div>
                        <div><dt class="text-cream/60">Payé</dt><dd class="font-semibold">{{ fcfa(booking.amount) }} · {{ booking.payment_method }}</dd></div>
                    </dl>
                </div>
                <span class="absolute top-[3.2rem] -left-3 h-6 w-6 rounded-full bg-cream"></span>
                <span class="absolute top-[3.2rem] -right-3 h-6 w-6 rounded-full bg-cream"></span>
            </div>

            <div ref="after" class="mt-8 grid gap-5 sm:grid-cols-2">
                <div class="rounded-2xl border border-sand-deep bg-white p-6">
                    <h2 class="text-lg font-bold">Pour bien préparer ton live</h2>
                    <ul class="mt-3 space-y-2 text-sm text-ink-soft">
                        <li v-for="p in prep" :key="p" class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-ember"></span>{{ p }}</li>
                    </ul>
                </div>
                <div class="rounded-2xl border border-sand-deep bg-white p-6">
                    <h2 class="text-lg font-bold">Et après ?</h2>
                    <p class="mt-3 text-sm text-ink-soft">
                        <template v-if="booking.package.lives_count > 1">Ton forfait compte {{ booking.package.lives_count }} lives. L’équipe te contacte sur WhatsApp ({{ booking.phone }}) pour planifier les suivants.</template>
                        <template v-else>L’équipe te contacte sur WhatsApp ({{ booking.phone }}) la veille de ton live.</template>
                        <template v-if="booking.package.announcements_count"> Bovann publiera la vidéo d’annonce de ton live sur son compte.</template>
                    </p>
                    <a v-if="wa" :href="wa" target="_blank" rel="noopener" class="btn-dark mt-4">Écrire à l’équipe sur WhatsApp</a>
                </div>
            </div>
            <div class="mt-10 text-center">
                <Link href="/" class="text-sm font-medium text-ink-soft underline-offset-4 hover:underline">Retour à l’accueil</Link>
            </div>
        </div>
    </PublicLayout>
</template>
