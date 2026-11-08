<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Logo from '@/Components/Logo.vue';
import { fcfa, hh, parseDate, iso, frDate, ucfirst } from '@/utils';
import { gsap, EASE, useMotion } from '@/motion';

const props = defineProps({
    date: String, dateLabel: String, monthLabel: String,
    planning: Array, stats: Object, notifications: Array, upcoming: Array,
});
const page = usePage();
const unread = computed(() => page.props.auth.unread);

// Le planning glisse dans le sens du jour choisi
const dayDir = ref(1);
const goDay = (date, opts = {}) => {
    dayDir.value = date >= props.date ? 1 : -1;
    router.get('/admin', { date }, { preserveScroll: true, preserveState: true, ...opts });
};
const shiftDay = (n) => {
    const d = parseDate(props.date);
    d.setDate(d.getDate() + n);
    goDay(iso(d));
};
const pickDay = (e) => e.target.value && goDay(e.target.value);

// La jauge de remplissage se remplit une fois, à l'arrivée
const fill = ref(null);
useMotion((reduce) => {
    if (!reduce) gsap.from(fill.value, { scaleX: 0, transformOrigin: '0% 50%', duration: 0.9, ease: EASE, delay: 0.1 });
});
const markRead = () => router.post('/admin/notifications/lues', {}, { preserveScroll: true });

// Rafraîchissement automatique : les nouvelles réservations apparaissent sans recharger
let timer;
onMounted(() => { timer = setInterval(() => router.reload({ only: ['notifications', 'planning', 'stats', 'upcoming', 'auth'] }), 20000); });
onUnmounted(() => clearInterval(timer));
</script>

<template>
    <Head title="Tableau de bord" />
    <div class="min-h-screen bg-cream">
        <header class="bg-night text-cream">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
                <div class="flex items-center gap-4">
                    <Link href="/"><Logo light /></Link>
                    <span class="hidden rounded-full bg-white/10 px-3 py-1 text-xs font-semibold sm:inline">Espace équipe</span>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <span class="hidden text-cream/70 sm:inline">{{ page.props.auth.user?.name }}</span>
                    <Link href="/admin/deconnexion" method="post" as="button" class="text-cream/70 hover:text-cream">Déconnexion</Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <!-- KPIs -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="rounded-2xl border border-sand-deep bg-white p-5">
                    <p class="text-sm text-ink-mute">Réservations</p>
                    <p class="mt-1 font-display text-3xl font-extrabold"><Transition name="tick" mode="out-in"><span :key="stats.bookings" class="inline-block">{{ stats.bookings }}</span></Transition></p>
                </div>
                <div class="rounded-2xl border border-sand-deep bg-white p-5">
                    <p class="text-sm text-ink-mute">Encaissé</p>
                    <p class="mt-1 font-display text-2xl font-extrabold sm:text-3xl"><Transition name="tick" mode="out-in"><span :key="stats.revenue" class="inline-block">{{ fcfa(stats.revenue) }}</span></Transition></p>
                </div>
                <div class="rounded-2xl border border-sand-deep bg-white p-5">
                    <p class="text-sm text-ink-mute">Remplissage · {{ monthLabel }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold"><Transition name="tick" mode="out-in"><span :key="stats.fill_rate" class="inline-block">{{ stats.fill_rate }}%</span></Transition></p>
                    <div class="mt-2 h-1.5 rounded-full bg-sand"><div ref="fill" class="h-1.5 rounded-full bg-ember transition-[width] duration-700 ease-out-expo" :style="{ width: Math.max(2, stats.fill_rate) + '%' }"></div></div>
                    <p class="mt-1.5 text-xs text-ink-mute">{{ stats.month_booked }} / {{ stats.month_capacity }} créneaux</p>
                </div>
                <div class="rounded-2xl border border-sand-deep bg-white p-5">
                    <p class="text-sm text-ink-mute">Lives à distance</p>
                    <p class="mt-1 font-display text-3xl font-extrabold"><Transition name="tick" mode="out-in"><span :key="stats.distance" class="inline-block">{{ stats.distance }}</span></Transition></p>
                    <p class="mt-1 text-xs text-ink-mute">avec l’animatrice au studio</p>
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                <!-- Planning du jour -->
                <section class="rounded-2xl border border-sand-deep bg-white p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="eyebrow">Planning du studio</p>
                            <Transition :name="dayDir > 0 ? 'slide-next' : 'slide-prev'" mode="out-in"><h2 :key="date" class="mt-1 text-xl font-bold">{{ dateLabel }}</h2></Transition>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="btn-ghost !h-9 !w-9 !p-0" @click="shiftDay(-1)" aria-label="Jour précédent">‹</button>
                            <input type="date" :value="date" @change="pickDay" class="field !w-auto !py-1.5 text-sm" aria-label="Choisir un jour" />
                            <button type="button" class="btn-ghost !h-9 !w-9 !p-0" @click="shiftDay(1)" aria-label="Jour suivant">›</button>
                        </div>
                    </div>
                    <Transition :name="dayDir > 0 ? 'slide-next' : 'slide-prev'" mode="out-in">
                    <ul :key="date" class="mt-5 divide-y divide-sand">
                        <li v-for="s in planning" :key="s.start" class="grid grid-cols-[88px_1fr] gap-4 py-3.5">
                            <div class="font-display text-sm font-bold">{{ hh(s.start) }}<span class="block text-xs font-medium text-ink-mute">→ {{ hh(s.end) }}</span></div>
                            <Transition name="land" mode="out-in">
                            <div v-if="s.booking" key="booked" class="rounded-xl border-l-4 px-4 py-2.5" :class="s.booking.mode === 'presentiel' ? 'border-ember bg-ember/5' : 'border-ink bg-sand/50'">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="font-semibold">{{ s.booking.business_name }} <span class="font-normal text-ink-soft">· {{ s.booking.full_name }}</span></p>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="s.booking.mode === 'presentiel' ? 'bg-ember text-white' : 'bg-ink text-cream'">
                                        {{ s.booking.mode === 'presentiel' ? 'Studio' : 'À distance · ' + s.booking.country }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-ink-soft">{{ s.booking.business_type }} — {{ s.booking.products }}</p>
                                <p class="mt-1 text-xs text-ink-mute">{{ s.booking.package.name }} · {{ s.booking.phone }} · {{ s.booking.reference }}</p>
                            </div>
                            <div v-else key="free" class="flex items-center rounded-xl border border-dashed border-sand-deep px-4 py-2.5 text-sm text-ink-mute">Créneau libre</div>
                            </Transition>
                        </li>
                    </ul>
                    </Transition>
                </section>

                <!-- Notifications -->
                <section class="rounded-2xl bg-night p-5 text-cream sm:p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="flex items-center gap-2 text-xl font-bold">
                            Notifications
                            <Transition name="pop" mode="out-in"><span v-if="unread" :key="unread" class="rounded-full bg-live px-2 py-0.5 text-xs font-semibold">{{ unread }}</span></Transition>
                        </h2>
                        <button v-if="unread" type="button" @click="markRead" class="text-xs text-cream/60 hover:text-cream">Tout marquer comme lu</button>
                    </div>
                    <p class="mt-1 text-xs text-cream/50">Chaque réservation arrive ici et par e-mail.</p>
                    <TransitionGroup tag="ul" name="notif" class="mt-5 space-y-3">
                        <li v-for="n in notifications" :key="n.id" class="rounded-xl p-3.5 text-sm transition-colors duration-500" :class="n.unread ? 'bg-white/10' : 'bg-white/[0.03]'">
                            <div class="flex gap-3">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full transition-colors duration-500" :class="n.unread ? 'bg-live' : 'bg-white/20'"></span>
                                <div>
                                    <p class="leading-snug">{{ n.message }}</p>
                                    <p class="mt-1 text-xs text-cream/50">
                                        {{ n.ago }}
                                        <button v-if="n.date" type="button" @click="goDay(n.date, { preserveScroll: false })" class="ml-2 text-glow hover:underline">voir le planning</button>
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li v-if="!notifications.length" key="empty" class="text-sm text-cream/50">Aucune réservation pour l’instant.</li>
                    </TransitionGroup>
                </section>
            </div>

            <!-- Prochains lives -->
            <section class="mt-8 rounded-2xl border border-sand-deep bg-white p-5 sm:p-6">
                <h2 class="text-xl font-bold">Prochains lives</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead class="text-xs text-ink-mute uppercase">
                            <tr class="border-b border-sand"><th class="py-2.5 pr-4 font-semibold">Date</th><th class="pr-4 font-semibold">Heure</th><th class="pr-4 font-semibold">Business</th><th class="pr-4 font-semibold">Format</th><th class="pr-4 font-semibold">Forfait</th><th class="pr-4 font-semibold">Contact</th><th class="text-right font-semibold">Montant</th></tr>
                        </thead>
                        <TransitionGroup tag="tbody" name="row">
                            <tr v-for="b in upcoming" :key="b.reference" class="border-b border-sand/70 last:border-0">
                                <td class="py-3 pr-4 whitespace-nowrap">{{ ucfirst(frDate(b.date)) }}</td>
                                <td class="pr-4 whitespace-nowrap">{{ hh(b.start_time) }}</td>
                                <td class="pr-4"><span class="font-semibold">{{ b.business_name }}</span><span class="block text-xs text-ink-mute">{{ b.business_type }}</span></td>
                                <td class="pr-4">{{ b.mode === 'presentiel' ? 'Studio' : b.city + ', ' + b.country }}</td>
                                <td class="pr-4">{{ b.package.name }}</td>
                                <td class="pr-4 whitespace-nowrap">{{ b.phone }}</td>
                                <td class="text-right font-semibold whitespace-nowrap">{{ fcfa(b.amount) }}</td>
                            </tr>
                        </TransitionGroup>
                    </table>
                </div>
            </section>
        </main>
    </div>
</template>
