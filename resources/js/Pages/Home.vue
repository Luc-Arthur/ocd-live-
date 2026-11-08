<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AfricaMap from '@/Components/AfricaMap.vue';
import PackageCard from '@/Components/PackageCard.vue';
import { frDate } from '@/utils';
import { gsap, EASE, useMotion, revealList } from '@/motion';

const props = defineProps({ packages: Array, launchDate: String, freeSlots: Number });

const steps = [
    { n: '01', t: 'Choisis ton format', d: 'Au studio de Lomé, ou à distance depuis ta boutique ou ta maison.' },
    { n: '02', t: 'Ta date et ton heure', d: 'Un calendrier, des créneaux de 1h30. Tu prends celui qui te va.' },
    { n: '03', t: 'Ton forfait', d: 'Un live pour tester, ou un pack pour être vu toute la semaine.' },
    { n: '04', t: 'Paie et passe en live', d: 'T-Money, Flooz ou carte. La régie s’occupe du reste.' },
];

const hero = ref(null);
const band = ref(null);
const howTo = ref(null);
const offers = ref(null);

useMotion((reduce) => {
    // Le texte du hero arrive vite (lisible et cliquable en moins d'une seconde) pendant que la carte s'allume
    const items = hero.value.querySelectorAll('[data-hero]');
    gsap.set(items, { autoAlpha: 0, y: reduce ? 0 : 18, transition: 'none' });
    const tl = gsap.timeline({ defaults: { ease: EASE, duration: reduce ? 0.4 : 0.8 } });
    tl.to(items, { autoAlpha: 1, y: 0, stagger: reduce ? 0 : 0.07, clearProps: 'opacity,visibility,transform,transition' });
    if (!reduce) tl.fromTo(hero.value.querySelector('h1'), { filter: 'blur(8px)' }, { filter: 'blur(0px)', duration: 0.9, clearProps: 'filter' }, 0.07);

    const offs = [
        revealList(band.value, '[data-reveal]', { y: 16 }),
        revealList(howTo.value, 'li'),
        revealList(offers.value, '[data-reveal]'),
    ];
    return () => offs.forEach((off) => off());
});
</script>

<template>
    <Head title="Réserve ton live" />
    <PublicLayout>
        <!-- HERO : les deux catégories visibles dès l'ouverture -->
        <section class="relative overflow-hidden">
            <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 pt-10 pb-16 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:pt-16">
                <div ref="hero">
                    <p data-hero class="inline-flex items-center gap-2 rounded-full border border-ember/25 bg-white px-3 py-1.5 text-xs font-semibold text-ink-soft">
                        <span class="live-dot h-2 w-2 rounded-full bg-live"></span>
                        Premiers lives le {{ frDate(launchDate) }}
                    </p>
                    <h1 data-hero class="mt-5 text-[2.6rem] leading-[1.02] font-extrabold sm:text-6xl">
                        Place ton produit devant <span class="text-ember">toute l’Afrique.</span>
                    </h1>
                    <p data-hero class="mt-5 max-w-lg text-lg text-ink-soft">
                        Réserve ton live TikTok de 1h30. Une régie professionnelle gère la diffusion, toi tu présentes ton business, ton numéro et ton adresse.
                    </p>

                    <p data-hero class="mt-9 mb-3 text-sm font-semibold text-ink">Comment veux-tu faire ton live ?</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <Link data-hero href="/reserver?mode=presentiel" class="group rounded-2xl border border-sand-deep bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:border-ember hover:shadow-glow">
                            <div class="flex items-center justify-between">
                                <span class="grid h-10 w-10 place-items-center rounded-xl bg-ember/10 text-ember">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                </span>
                                <span class="text-ink-mute transition group-hover:translate-x-1 group-hover:text-ember">→</span>
                            </div>
                            <h2 class="mt-4 text-lg font-bold">En présentiel</h2>
                            <p class="mt-1 text-sm text-ink-soft">Tu viens au studio de Lomé avec tes produits. Tu t’assois, on lance le live.</p>
                        </Link>
                        <Link data-hero href="/reserver?mode=distance" class="group rounded-2xl border border-sand-deep bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:border-ember hover:shadow-glow">
                            <div class="flex items-center justify-between">
                                <span class="grid h-10 w-10 place-items-center rounded-xl bg-ember/10 text-ember">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z" /></svg>
                                </span>
                                <span class="text-ink-mute transition group-hover:translate-x-1 group-hover:text-ember">→</span>
                            </div>
                            <h2 class="mt-4 text-lg font-bold">À distance</h2>
                            <p class="mt-1 text-sm text-ink-soft">Depuis ta boutique, partout en Afrique ou dans le monde. Une animatrice t’accompagne depuis le studio.</p>
                        </Link>
                    </div>
                    <p data-hero class="mt-4 text-sm text-ink-mute">{{ freeSlots }} créneaux encore libres · à partir de 20 000 FCFA</p>
                </div>

                <AfricaMap class="mx-auto w-full max-w-md lg:max-w-none" />
            </div>
        </section>

        <!-- Le marketing avant la vente -->
        <section class="bg-night text-cream">
            <div class="mx-auto grid max-w-6xl gap-8 px-4 py-14 sm:px-6 md:grid-cols-[1fr_2fr] md:items-center">
                <h2 class="text-3xl font-bold sm:text-4xl">Le marketing<br /><span class="text-glow">avant la vente.</span></h2>
                <div ref="band" class="grid gap-6 sm:grid-cols-3">
                    <div data-reveal><p class="font-display text-xl font-bold">Ton produit</p><p class="mt-1 text-sm text-cream/70">Montré en direct à des milliers de personnes.</p></div>
                    <div data-reveal><p class="font-display text-xl font-bold">Ton numéro</p><p class="mt-1 text-sm text-cream/70">Épinglé pendant le live, pour qu’on t’appelle après.</p></div>
                    <div data-reveal><p class="font-display text-xl font-bold">Ton adresse</p><p class="mt-1 text-sm text-cream/70">Pour que les clients sachent où te trouver.</p></div>
                </div>
            </div>
        </section>

        <!-- Comment ça marche -->
        <section id="comment" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6">
            <p class="eyebrow">Comment ça marche</p>
            <h2 class="mt-2 max-w-xl text-3xl font-bold sm:text-4xl">Quatre étapes, cinq minutes, et tu es en live.</h2>
            <ol ref="howTo" class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <li v-for="s in steps" :key="s.n" class="rounded-2xl border border-sand-deep bg-white p-6">
                    <span class="font-display text-3xl font-extrabold text-ember/80">{{ s.n }}</span>
                    <h3 class="mt-3 text-lg font-bold">{{ s.t }}</h3>
                    <p class="mt-1.5 text-sm text-ink-soft">{{ s.d }}</p>
                </li>
            </ol>
        </section>

        <!-- Forfaits -->
        <section id="forfaits" class="scroll-mt-20 bg-sand/60">
            <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Forfaits</p>
                        <h2 class="mt-2 text-3xl font-bold sm:text-4xl">Plus tu fais de lives, moins tu paies.</h2>
                    </div>
                    <p class="max-w-sm text-sm text-ink-soft">Chaque live dure 1h30. Les forfaits « Annonce » incluent une vidéo de Bovann sur son compte pour annoncer ton live.</p>
                </div>
                <div ref="offers" class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="p in packages" :key="p.id" data-reveal>
                        <PackageCard :pkg="p" :href="`/reserver?forfait=${p.slug}`" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Formats -->
        <section id="formats" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6">
            <div class="grid gap-5 md:grid-cols-2">
                <div class="rounded-3xl bg-ember p-8 text-white">
                    <p class="text-xs font-semibold tracking-[0.14em] uppercase opacity-80">Présentiel · Lomé</p>
                    <h3 class="mt-3 text-2xl font-bold">Tu viens, tu t’assois, tu vends.</h3>
                    <ul class="mt-5 space-y-2 text-[15px] text-white/90">
                        <li>• Plateau éclairé, décor OCD Live</li>
                        <li>• Une régie devant toi qui dirige ton live</li>
                        <li>• Apporte 30 à 45 produits ou échantillons</li>
                        <li>• Arrive 30 minutes avant ton créneau</li>
                    </ul>
                </div>
                <div class="rounded-3xl bg-night p-8 text-cream">
                    <p class="text-xs font-semibold tracking-[0.14em] text-glow uppercase">À distance · partout</p>
                    <h3 class="mt-3 text-2xl font-bold">Ta boutique devient le plateau.</h3>
                    <ul class="mt-5 space-y-2 text-[15px] text-cream/80">
                        <li>• Tu montes sur le live depuis chez toi</li>
                        <li>• Une animatrice au studio t’accompagne et relance le public</li>
                        <li>• Idéal pour le Cameroun, la Côte d’Ivoire, le Bénin, le Sénégal…</li>
                        <li>• Une bonne connexion et un téléphone suffisent</li>
                    </ul>
                </div>
            </div>
            <div class="mt-14 flex flex-col items-center text-center">
                <h2 class="max-w-2xl text-3xl font-bold sm:text-4xl">Les créneaux de décembre partent vite.</h2>
                <Link href="/reserver" class="btn-primary mt-6 !px-7 !py-3.5 text-base">Réserver mon live</Link>
            </div>
        </section>
    </PublicLayout>
</template>
