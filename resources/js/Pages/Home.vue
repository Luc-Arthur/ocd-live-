<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AfricaLiveMap from '@/Components/AfricaLiveMap.vue';
import PackageCard from '@/Components/PackageCard.vue';
import ListenButton from '@/Components/ListenButton.vue';
import CountryChips from '@/Components/CountryChips.vue';
import AfricaMap from '@/Components/AfricaMap.vue';
import Monitor from '@/Components/Regie/Monitor.vue';
import MixerKeys from '@/Components/Regie/MixerKeys.vue';
import OnAir from '@/Components/Regie/OnAir.vue';
import LomeClock from '@/Components/Regie/LomeClock.vue';
import DemoNote from '@/Components/Regie/DemoNote.vue';
import { gsap, EASE, useMotion, revealList, whenVisible } from '@/motion';
import { blackOut, powerOn, syncServerTime, two } from '@/regie';
import { fcfa } from '@/utils';

const props = defineProps({
    packages: Array, launchDate: String, freeSlots: Number,
    session: Object, countryCounts: Object, categoryCounts: Object, onAirTotal: Number, preview: Array, subscriptionPrice: Number,
});
const countriesLive = computed(() => Object.values(props.countryCounts).filter(Boolean).length);
const perDay = computed(() => Math.round(props.subscriptionPrice / 30));
watch(() => props.session.now, syncServerTime, { immediate: true });

const hero = ref(null);
const monitors = ref(null);
const howTo = ref(null);
const offers = ref(null);

useMotion((reduce) => {
    // Le haut de page arrive vite (lisible et cliquable en moins d'une seconde) pendant que la carte s'allume
    const items = hero.value.querySelectorAll('[data-hero]');
    gsap.set(items, { autoAlpha: 0, y: reduce ? 0 : 18, transition: 'none' });
    const tl = gsap.timeline({ defaults: { ease: EASE, duration: reduce ? 0.4 : 0.8 } });
    tl.to(items, { autoAlpha: 1, y: 0, stagger: reduce ? 0 : 0.07, clearProps: 'opacity,visibility,transform,transition' });
    if (!reduce) {
        tl.fromTo(hero.value.querySelector('h1'), { filter: 'blur(8px)' }, { filter: 'blur(0px)', duration: 0.9, clearProps: 'filter' }, 0.07);
        // Les pays en live s'allument un par un sur la carte
        tl.from(hero.value.querySelectorAll('[data-pin]'), { scale: 0, transformOrigin: '50% 50%', duration: 0.6, stagger: 0.06, ease: 'back.out(2)' }, 0.3);
    }

    // Les 4 moniteurs s'allument quand on les voit
    blackOut(monitors.value);
    const offs = [
        whenVisible(monitors.value, () => powerOn(monitors.value), { rootMargin: '0px 0px -15% 0px' }),
        revealList(howTo.value, 'li'),
        revealList(offers.value, '[data-reveal]'),
    ];
    return () => offs.forEach((off) => off());
});
</script>

<template>
    <Head title="Qui vend en live ?" />
    <PublicLayout dark>
        <!-- Haut de page en régie : la carte est le menu, les moniteurs montrent qui est à l'antenne -->
        <section ref="hero" class="regie-surface bg-regie text-cream">
            <div class="mx-auto max-w-6xl px-4 pt-4 pb-12 sm:px-6 lg:pt-6">
                <div data-hero class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-regie-line pb-4">
                    <OnAir :open="session.open" />
                    <p class="font-mono text-[12px] tracking-[0.12em] text-regie-mute uppercase">
                        <span class="font-bold text-cream tabular-nums">{{ onAirTotal }}</span> en direct<span class="hidden sm:inline"> · {{ countriesLive }} pays</span>
                    </p>
                    <LomeClock size="sm" class="ml-auto" />
                </div>

                <div class="mt-6 grid items-center gap-6 lg:mt-8 lg:grid-cols-[1fr_1fr] lg:gap-8">
                    <div class="min-w-0">
                        <h1 data-hero class="text-[2.5rem] leading-[1.02] font-extrabold text-white sm:text-6xl">Qui vend en live <span class="text-ember">en ce moment&nbsp;?</span></h1>
                        <div data-hero class="mt-7 flex flex-wrap items-center gap-3">
                            <Link href="/en-direct" class="btn bg-cream !px-6 !py-3.5 text-base text-ink hover:bg-white"><span class="tally-dot h-2.5 w-2.5 rounded-full bg-tally"></span>Voir les lives</Link>
                            <Link href="/vendre" class="btn-primary !px-6 !py-3.5 text-base">Je vends en live</Link>
                        </div>
                        <div data-hero class="mt-4">
                            <ListenButton dark text="Bienvenue sur OCD Live. Ici, tu trouves les vendeurs qui sont en live sur TikTok dans ton pays. Touche ton pays sur la carte, ou choisis ce que tu cherches : nourriture, habits, beauté. Tu vends ? Appuie sur Je vends en live." />
                        </div>
                    </div>
                    <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                        <div class="absolute inset-12 rounded-full bg-[#F6A94A]/15 blur-3xl"></div>
                        <AfricaLiveMap dark class="relative" :counts="countryCounts" />
                    </div>
                </div>
                <div class="mt-1 lg:hidden">
                    <CountryChips dark :all="false" :counts="countryCounts" @update:model-value="(c) => c && router.get('/en-direct', { pays: c })" />
                </div>

                <!-- Aperçu du mur de moniteurs -->
                <div class="mt-10 flex items-end justify-between gap-4">
                    <h2 class="font-mono text-[12px] font-bold tracking-[0.22em] text-regie-mute uppercase">À l’antenne</h2>
                    <Link href="/en-direct" class="inline-flex min-h-11 items-center text-sm font-semibold text-glow underline-offset-4 hover:underline">Tout voir</Link>
                </div>
                <div ref="monitors" class="mt-2 grid auto-rows-[17rem] grid-cols-2 gap-3 sm:auto-rows-[20rem] lg:grid-cols-4">
                    <Monitor v-for="(l, i) in preview" :key="l.id" :live="l" :cam="two(i + 1)" small />
                </div>

                <!-- Catégories : la table de mixage -->
                <MixerKeys class="mt-3" :counts="categoryCounts" @update:model-value="(c) => c && router.get('/en-direct', { categorie: c })" />
            </div>
            <DemoNote />
        </section>

        <!-- Vendeurs : 3 gestes -->
        <section class="bg-night text-cream">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <h2 class="text-3xl font-bold sm:text-4xl">Tu vends ? <span class="text-glow">Passe sur OCD Live.</span></h2>
                    <ListenButton dark text="Tu vends en live sur TikTok ? Trois gestes. Un : ton numéro WhatsApp. Deux : ton nom TikTok. Trois : quand tu es en live, appuie sur le gros bouton rouge. L'équipe OCD vérifie, et tout le monde te voit sur la carte. Cinq mille francs par mois." />
                </div>
                <ol ref="howTo" class="mt-10 grid gap-4 sm:grid-cols-3">
                    <li class="flex items-center gap-4 rounded-3xl bg-white/[0.06] p-5 ring-1 ring-white/10">
                        <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-[#25D366] text-white">
                            <svg viewBox="0 0 24 24" class="h-9 w-9" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm4.5 12.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3z" /></svg>
                        </span>
                        <div><p class="font-display text-3xl font-extrabold text-glow">1</p><p class="text-lg font-semibold">Ton WhatsApp</p></div>
                    </li>
                    <li class="flex items-center gap-4 rounded-3xl bg-white/[0.06] p-5 ring-1 ring-white/10">
                        <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-black text-white ring-1 ring-white/20">
                            <svg viewBox="0 0 24 24" class="h-8 w-8" fill="currentColor"><path d="M16.6 5.8A4.3 4.3 0 0 1 15.5 3h-3.1v12.4a2.6 2.6 0 1 1-1.8-2.5V9.7a5.7 5.7 0 1 0 4.9 5.7V9a7.3 7.3 0 0 0 4.3 1.4V7.3a4.3 4.3 0 0 1-3.2-1.5z" /></svg>
                        </span>
                        <div><p class="font-display text-3xl font-extrabold text-glow">2</p><p class="text-lg font-semibold">Ton TikTok</p></div>
                    </li>
                    <li class="flex items-center gap-4 rounded-3xl bg-white/[0.06] p-5 ring-1 ring-white/10">
                        <span class="relative grid h-16 w-16 shrink-0 place-items-center rounded-full bg-live text-white">
                            <span class="absolute inset-0 animate-ping rounded-full bg-live opacity-30 motion-reduce:animate-none"></span>
                            <span class="relative text-xs font-extrabold tracking-wider">LIVE</span>
                        </span>
                        <div><p class="font-display text-3xl font-extrabold text-glow">3</p><p class="text-lg font-semibold">Je suis en live</p></div>
                    </li>
                </ol>
                <div class="mt-8 flex flex-wrap items-center gap-5">
                    <Link href="/vendre" class="btn-primary !px-7 !py-4 text-base">Je commence</Link>
                    <p class="text-cream/70"><span class="font-display text-2xl font-extrabold text-cream">{{ fcfa(subscriptionPrice) }}</span> / mois · environ {{ perDay }} FCFA par jour</p>
                </div>
            </div>
        </section>

        <!-- Studio de Lomé -->
        <section id="studio" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-16 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Studio OCD Live · Lomé</p>
                    <h2 class="mt-2 text-3xl font-bold sm:text-4xl">Fais ton live avec notre régie</h2>
                </div>
                <Link href="/reserver" class="btn-dark">Réserver un créneau</Link>
            </div>
            <div ref="offers" class="mt-8 grid items-center gap-6 lg:grid-cols-[1fr_380px]">
              <div class="grid gap-4">
                <Link data-reveal href="/reserver?mode=presentiel" class="group flex items-center gap-4 rounded-3xl bg-ember p-6 text-white transition hover:-translate-y-0.5">
                    <svg viewBox="0 0 24 24" class="h-10 w-10 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    <div><p class="font-display text-2xl font-extrabold">Au studio</p><p class="text-white/85">Tu viens à Lomé avec tes produits</p></div>
                    <span class="ml-auto text-2xl transition group-hover:translate-x-1">→</span>
                </Link>
                <Link data-reveal href="/reserver?mode=distance" class="group flex items-center gap-4 rounded-3xl bg-white p-6 ring-1 ring-sand-deep transition hover:-translate-y-0.5">
                    <svg viewBox="0 0 24 24" class="h-10 w-10 shrink-0 text-ember" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z" /></svg>
                    <div><p class="font-display text-2xl font-extrabold">À distance</p><p class="text-ink-soft">Depuis ta boutique, avec notre animatrice</p></div>
                    <span class="ml-auto text-2xl text-ink-mute transition group-hover:translate-x-1">→</span>
                </Link>
              </div>
              <div data-reveal class="relative mx-auto w-full max-w-sm">
                <div class="absolute inset-8 rounded-full bg-glow/30 blur-3xl"></div>
                <AfricaMap class="relative" />
              </div>
            </div>
            <div id="forfaits" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <PackageCard v-for="p in packages" :key="p.id" :pkg="p" :href="`/reserver?forfait=${p.slug}`" />
            </div>
        </section>
    </PublicLayout>
</template>
