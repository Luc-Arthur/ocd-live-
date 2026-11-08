<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import PackageCard from '@/Components/PackageCard.vue';
import { fcfa, hh, frDate, parseDate, iso, MONTHS, ucfirst } from '@/utils';
import { reducedMotion, shake } from '@/motion';

const props = defineProps({
    initialMode: String,
    initialPackage: String,
    packages: Array,
    availability: Object,
    firstDay: String,
    lastDay: String,
    slotDuration: Number,
    businessTypes: Array,
    paymentMethods: Object,
});

const steps = ['Format', 'Date', 'Heure', 'Forfait', 'Ton business', 'Paiement'];
const preselected = props.packages.find((p) => p.slug === props.initialPackage);

const form = useForm({
    mode: props.initialMode ?? null,
    live_date: null,
    start_time: null,
    package_id: preselected?.id ?? null,
    full_name: '',
    business_name: '',
    business_type: '',
    products: '',
    phone: '',
    email: '',
    city: props.initialMode === 'presentiel' ? 'Lomé' : '',
    country: props.initialMode === 'presentiel' ? 'Togo' : '',
    business_address: '',
    social_handle: '',
    payment_method: 'tmoney',
    payment_phone: '',
});

const step = ref(props.initialMode ? 1 : 0);
const dir = ref(1); // sens du parcours, pour orienter les transitions
const top = ref(null);
const go = async (i) => {
    dir.value = i >= step.value ? 1 : -1;
    step.value = i;
    await nextTick();
    top.value?.scrollIntoView({ behavior: reducedMotion() ? 'auto' : 'smooth', block: 'start' });
};

const pkg = computed(() => props.packages.find((p) => p.id === form.package_id));

const chooseMode = (m) => {
    form.mode = m;
    if (m === 'presentiel' && !form.city) { form.city = 'Lomé'; form.country = 'Togo'; }
    if (m === 'distance' && form.city === 'Lomé' && form.country === 'Togo') { form.city = ''; form.country = ''; }
    go(1);
};

/* ---------- Calendrier ---------- */
const first = parseDate(props.firstDay);
const last = parseDate(props.lastDay);
const cursor = ref(new Date(first.getFullYear(), first.getMonth(), 1));
const canPrev = computed(() => cursor.value > new Date(first.getFullYear(), first.getMonth(), 1));
const canNext = computed(() => new Date(cursor.value.getFullYear(), cursor.value.getMonth() + 1, 1) <= last);
const monthDir = ref(1);
const shift = (n) => {
    monthDir.value = n;
    cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + n, 1);
};

const freeCount = (key) => Object.values(props.availability[key] ?? {}).filter(Boolean).length;
const totalSlots = computed(() => Object.keys(Object.values(props.availability)[0] ?? {}).length);

const cells = computed(() => {
    const y = cursor.value.getFullYear();
    const m = cursor.value.getMonth();
    const offset = (new Date(y, m, 1).getDay() + 6) % 7; // lundi en premier
    const days = new Date(y, m + 1, 0).getDate();
    const out = Array.from({ length: offset }, () => null);
    for (let d = 1; d <= days; d++) {
        const date = new Date(y, m, d);
        const key = iso(date);
        const inWindow = key in props.availability;
        const free = inWindow ? freeCount(key) : 0;
        out.push({ d, key, inWindow, free, full: inWindow && free === 0 });
    }
    return out;
});

const pickDate = (c) => {
    if (!c?.inWindow || c.full) return;
    form.live_date = c.key;
    if (form.start_time && !props.availability[c.key][form.start_time]) form.start_time = null;
    go(2);
};

const slots = computed(() => {
    if (!form.live_date) return [];
    return Object.entries(props.availability[form.live_date] ?? {}).map(([start, free]) => {
        const [h, mi] = start.split(':').map(Number);
        const end = new Date(2000, 0, 1, h, mi + props.slotDuration);
        return { start, end: `${String(end.getHours()).padStart(2, '0')}:${String(end.getMinutes()).padStart(2, '0')}`, free };
    });
});
const slotEnd = computed(() => slots.value.find((s) => s.start === form.start_time)?.end);
const pickSlot = (s) => { if (!s.free) return; form.start_time = s.start; go(3); };
const pickPackage = (p) => { form.package_id = p.id; go(4); };

/* ---------- Infos ---------- */
const infoErrors = ref({});
const infoCard = ref(null);
const validateInfo = () => {
    const e = {};
    const req = { full_name: 'Ton nom', business_name: 'Le nom de ton business', business_type: 'Ton activité', products: 'Tes produits', phone: 'Ton numéro', city: 'Ta ville', country: 'Ton pays' };
    for (const [k, label] of Object.entries(req)) if (!String(form[k] ?? '').trim()) e[k] = `${label} est obligatoire.`;
    if (form.phone && !/^[+0-9 ().-]{8,}$/.test(form.phone)) e.phone = 'Numéro de téléphone invalide.';
    if (form.email && !/^\S+@\S+\.\S+$/.test(form.email)) e.email = 'Adresse e-mail invalide.';
    infoErrors.value = e;
    if (!Object.keys(e).length) {
        if (!form.payment_phone) form.payment_phone = form.phone;
        go(5);
    } else {
        shake(infoCard.value);
    }
};
const err = (k) => infoErrors.value[k] || form.errors[k];

/* ---------- Envoi ---------- */
const stepOfField = { mode: 0, live_date: 1, start_time: 2, package_id: 3, payment_method: 5, payment_phone: 5 };
const submit = () => {
    form.post('/reserver', {
        preserveScroll: true,
        onError: (errors) => {
            const keys = Object.keys(errors);
            const target = Math.min(...keys.map((k) => stepOfField[k] ?? 4));
            if (keys.includes('start_time')) {
                form.start_time = null;
                router.reload({ only: ['availability'] });
            }
            go(target);
        },
    });
};

const canOpen = (i) => {
    if (i === 0) return true;
    if (i === 1) return !!form.mode;
    if (i === 2) return !!form.live_date;
    if (i === 3) return !!form.start_time;
    if (i === 4) return !!form.package_id;
    return i === 5 && step.value >= 5;
};

// Libellés du récap : chaque choix vient s'inscrire dans le billet
const recap = computed(() => ({
    mode: form.mode === 'presentiel' ? 'Présentiel · Lomé' : form.mode === 'distance' ? 'À distance' : '—',
    date: form.live_date ? ucfirst(frDate(form.live_date)) : '—',
    time: form.start_time ? `${hh(form.start_time)} – ${hh(slotEnd.value ?? '')}` : '—',
    pkg: pkg.value?.name ?? '—',
    total: pkg.value ? fcfa(pkg.value.price) : '—',
}));
const recapRows = [['mode', 'Format'], ['date', 'Date'], ['time', 'Heure'], ['pkg', 'Forfait']];

watch(() => form.payment_method, (m) => { if (m === 'card') form.payment_phone = ''; else if (!form.payment_phone) form.payment_phone = form.phone; });
</script>

<template>
    <Head title="Réserver un live" />
    <PublicLayout>
        <div ref="top" class="scroll-mt-16"></div>
        <div class="mx-auto max-w-6xl px-4 pt-8 pb-20 sm:px-6">
            <!-- Progression -->
            <ol class="flex gap-1.5 overflow-x-auto pb-1 text-xs font-semibold sm:gap-2">
                <li v-for="(s, i) in steps" :key="s" class="flex-1">
                    <button
                        type="button"
                        :disabled="!canOpen(i)"
                        @click="go(i)"
                        class="w-full min-w-[4.5rem] text-left disabled:cursor-default"
                        :aria-current="step === i ? 'step' : undefined"
                    >
                        <span class="relative block h-1.5 overflow-hidden rounded-full bg-sand-deep">
                            <span class="absolute inset-0 origin-left rounded-full transition-[scale,background-color] duration-500 ease-out-expo" :class="i < step ? 'scale-x-100 bg-ember' : i === step ? 'scale-x-100 bg-ink' : 'scale-x-0 bg-ink'"></span>
                        </span>
                        <span class="mt-2 block truncate transition-colors duration-300" :class="i === step ? 'text-ink' : 'text-ink-mute'">{{ i + 1 }}. {{ s }}</span>
                    </button>
                </li>
            </ol>

            <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_340px]">
                <section class="min-w-0">
                    <!-- 1. Format -->
                    <Transition :name="dir > 0 ? 'slide-next' : 'slide-prev'" mode="out-in">
                    <div v-if="step === 0">
                        <h1 class="text-3xl font-bold sm:text-4xl">Où veux-tu faire ton live ?</h1>
                        <p class="mt-2 text-ink-soft">Dans les deux cas, la régie OCD Live dirige la diffusion depuis le studio.</p>
                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <button v-for="m in [
                                { k: 'presentiel', t: 'En présentiel', s: 'Au studio de Lomé, Togo', d: 'Tu viens avec tes produits, tu t’assois sur le plateau et tu vends pendant 1h30.' },
                                { k: 'distance', t: 'À distance', s: 'Partout en Afrique et dans le monde', d: 'Tu fais ton live depuis ta boutique. Une animatrice au studio t’accompagne et anime avec toi.' },
                            ]" :key="m.k" type="button" @click="chooseMode(m.k)"
                                class="rounded-2xl border bg-white p-6 text-left transition hover:-translate-y-0.5 active:scale-[0.98]"
                                :class="form.mode === m.k ? 'border-ember ring-2 ring-ember/30 shadow-glow' : 'border-sand-deep shadow-card hover:border-ink-mute'"
                                :aria-pressed="form.mode === m.k">
                                <p class="eyebrow">{{ m.s }}</p>
                                <h2 class="mt-2 text-2xl font-bold">{{ m.t }}</h2>
                                <p class="mt-2 text-sm text-ink-soft">{{ m.d }}</p>
                            </button>
                        </div>
                        <p v-if="form.errors.mode" class="msg-in mt-3 text-sm text-live">{{ form.errors.mode }}</p>
                    </div>

                    <!-- 2. Date -->
                    <div v-else-if="step === 1">
                        <h1 class="text-3xl font-bold sm:text-4xl">Choisis ton jour</h1>
                        <p class="mt-2 text-ink-soft">Les lives commencent le {{ frDate(firstDay) }}. Les jours grisés sont complets.</p>
                        <div class="mt-8 rounded-2xl border border-sand-deep bg-white p-4 shadow-card sm:p-6">
                            <div class="flex items-center justify-between">
                                <button type="button" class="btn-ghost !h-9 !w-9 !p-0" :disabled="!canPrev" @click="shift(-1)" aria-label="Mois précédent">‹</button>
                                <Transition :name="monthDir > 0 ? 'slide-next' : 'slide-prev'" mode="out-in">
                                    <h2 :key="cursor.getTime()" class="text-xl font-bold capitalize">{{ MONTHS[cursor.getMonth()] }} {{ cursor.getFullYear() }}</h2>
                                </Transition>
                                <button type="button" class="btn-ghost !h-9 !w-9 !p-0" :disabled="!canNext" @click="shift(1)" aria-label="Mois suivant">›</button>
                            </div>
                            <div class="mt-5 grid grid-cols-7 gap-1 text-center text-xs font-semibold text-ink-mute sm:gap-2">
                                <span v-for="d in ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']" :key="d">{{ d }}</span>
                            </div>
                            <Transition :name="monthDir > 0 ? 'slide-next' : 'slide-prev'" mode="out-in">
                            <div :key="cursor.getTime()" class="mt-2 grid grid-cols-7 gap-1 sm:gap-2">
                                <template v-for="(c, i) in cells" :key="i">
                                    <span v-if="!c"></span>
                                    <button v-else type="button" @click="pickDate(c)" :disabled="!c.inWindow || c.full"
                                        class="flex aspect-square flex-col items-center justify-center rounded-xl border text-sm transition active:scale-95 sm:aspect-[1.15]"
                                        :class="[
                                            form.live_date === c.key ? 'border-ember bg-ember text-white' :
                                            !c.inWindow ? 'border-transparent text-ink-mute/40' :
                                            c.full ? 'border-transparent bg-sand/70 text-ink-mute line-through' :
                                            'border-sand-deep bg-cream hover:border-ember',
                                        ]"
                                        :aria-label="c.inWindow ? `${frDate(c.key)}, ${c.full ? 'complet' : c.free + ' créneaux libres'}` : undefined">
                                        <span class="font-display text-base font-bold sm:text-lg">{{ c.d }}</span>
                                        <span v-if="c.inWindow && !c.full" class="mt-0.5 hidden text-[10px] sm:block"
                                            :class="form.live_date === c.key ? 'text-white/85' : c.free <= 2 ? 'text-ember-dark' : 'text-ink-mute'">
                                            {{ c.free }}/{{ totalSlots }} libres
                                        </span>
                                        <span v-if="c.inWindow && !c.full" class="mt-1 h-1 w-1 rounded-full sm:hidden"
                                            :class="form.live_date === c.key ? 'bg-white' : c.free <= 2 ? 'bg-ember' : 'bg-leaf'"></span>
                                    </button>
                                </template>
                            </div>
                            </Transition>
                        </div>
                    </div>

                    <!-- 3. Heure -->
                    <div v-else-if="step === 2">
                        <h1 class="text-3xl font-bold sm:text-4xl">À quelle heure ?</h1>
                        <p class="mt-2 text-ink-soft">{{ ucfirst(frDate(form.live_date)) }} · chaque live dure 1h30 (heure de Lomé).</p>
                        <p v-if="form.errors.start_time" class="msg-in mt-4 rounded-xl bg-live/10 px-4 py-3 text-sm font-medium text-live">{{ form.errors.start_time }}</p>
                        <div class="mt-8 grid gap-3 sm:grid-cols-2">
                            <button v-for="s in slots" :key="s.start" type="button" @click="pickSlot(s)" :disabled="!s.free"
                                class="flex items-center justify-between rounded-2xl border px-5 py-4 text-left transition active:scale-[0.98]"
                                :class="form.start_time === s.start ? 'border-ember bg-white ring-2 ring-ember/30 shadow-glow' : s.free ? 'border-sand-deep bg-white shadow-card hover:border-ember' : 'border-transparent bg-sand/60 text-ink-mute'">
                                <span>
                                    <span class="font-display text-xl font-bold">{{ hh(s.start) }} – {{ hh(s.end) }}</span>
                                    <span class="block text-xs" :class="s.free ? 'text-ink-mute' : ''">{{ Number(s.start.slice(0, 2)) < 12 ? 'Matin' : Number(s.start.slice(0, 2)) < 17 ? 'Après-midi' : 'Soirée' }}</span>
                                </span>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="s.free ? 'bg-leaf/10 text-leaf' : 'bg-ink/5 text-ink-mute'">{{ s.free ? 'Libre' : 'Réservé' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- 4. Forfait -->
                    <div v-else-if="step === 3">
                        <h1 class="text-3xl font-bold sm:text-4xl">Choisis ton forfait</h1>
                        <p class="mt-2 text-ink-soft">Tu réserves maintenant ton premier live. Pour les packs, l’équipe planifie les suivants avec toi sur WhatsApp.</p>
                        <div class="mt-8 grid gap-5 pt-2 sm:grid-cols-2 xl:grid-cols-3">
                            <PackageCard v-for="p in packages" :key="p.id" :pkg="p" :selected="form.package_id === p.id" @select="pickPackage" />
                        </div>
                    </div>

                    <!-- 5. Infos -->
                    <form v-else-if="step === 4" @submit.prevent="validateInfo" novalidate>
                        <h1 class="text-3xl font-bold sm:text-4xl">Parle-nous de ton business</h1>
                        <p class="mt-2 text-ink-soft">Ces infos servent à préparer ton live et à l’annoncer.</p>
                        <div ref="infoCard" class="mt-8 grid gap-5 rounded-2xl border border-sand-deep bg-white p-5 shadow-card sm:grid-cols-2 sm:p-7">
                            <div>
                                <label class="label" for="full_name">Ton nom complet *</label>
                                <input id="full_name" v-model="form.full_name" class="field" autocomplete="name" placeholder="Ex. Afi Mensah" />
                                <p v-if="err('full_name')" class="msg-in mt-1 text-sm text-live">{{ err('full_name') }}</p>
                            </div>
                            <div>
                                <label class="label" for="business_name">Nom de ton business *</label>
                                <input id="business_name" v-model="form.business_name" class="field" placeholder="Ex. Chez Afi Saveurs" />
                                <p v-if="err('business_name')" class="msg-in mt-1 text-sm text-live">{{ err('business_name') }}</p>
                            </div>
                            <div>
                                <label class="label" for="business_type">Ton activité *</label>
                                <select id="business_type" v-model="form.business_type" class="field">
                                    <option value="" disabled>Choisir…</option>
                                    <option v-for="t in businessTypes" :key="t" :value="t">{{ t }}</option>
                                </select>
                                <p v-if="err('business_type')" class="msg-in mt-1 text-sm text-live">{{ err('business_type') }}</p>
                            </div>
                            <div>
                                <label class="label" for="phone">Numéro WhatsApp *</label>
                                <input id="phone" v-model="form.phone" class="field" type="tel" autocomplete="tel" placeholder="+228 90 12 34 56" />
                                <p v-if="err('phone')" class="msg-in mt-1 text-sm text-live">{{ err('phone') }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label" for="products">Ce que tu vas présenter *</label>
                                <textarea id="products" v-model="form.products" rows="3" class="field" placeholder="Ex. Attiéké poisson, alloco, jus de bissap… environ 40 portions"></textarea>
                                <p v-if="err('products')" class="msg-in mt-1 text-sm text-live">{{ err('products') }}</p>
                            </div>
                            <div>
                                <label class="label" for="city">Ville *</label>
                                <input id="city" v-model="form.city" class="field" autocomplete="address-level2" />
                                <p v-if="err('city')" class="msg-in mt-1 text-sm text-live">{{ err('city') }}</p>
                            </div>
                            <div>
                                <label class="label" for="country">Pays *</label>
                                <input id="country" v-model="form.country" class="field" autocomplete="country-name" list="countries" />
                                <datalist id="countries">
                                    <option v-for="c in ['Togo', 'Bénin', 'Cameroun', 'Côte d’Ivoire', 'Sénégal', 'Burkina Faso', 'Mali', 'Niger', 'Ghana', 'Gabon', 'Congo', 'RD Congo', 'Guinée', 'France']" :key="c" :value="c" />
                                </datalist>
                                <p v-if="err('country')" class="msg-in mt-1 text-sm text-live">{{ err('country') }}</p>
                            </div>
                            <div>
                                <label class="label" for="business_address">Adresse de ta boutique <span class="font-normal text-ink-mute">(affichée en live)</span></label>
                                <input id="business_address" v-model="form.business_address" class="field" placeholder="Quartier, repère…" />
                            </div>
                            <div>
                                <label class="label" for="email">E-mail <span class="font-normal text-ink-mute">(pour la confirmation)</span></label>
                                <input id="email" v-model="form.email" class="field" type="email" autocomplete="email" />
                                <p v-if="err('email')" class="msg-in mt-1 text-sm text-live">{{ err('email') }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label" for="social_handle">Ton compte TikTok / Facebook <span class="font-normal text-ink-mute">(facultatif)</span></label>
                                <input id="social_handle" v-model="form.social_handle" class="field" placeholder="@monbusiness" />
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="btn-primary !px-7">Continuer vers le paiement →</button>
                        </div>
                    </form>

                    <!-- 6. Paiement -->
                    <form v-else-if="step === 5" @submit.prevent="submit">
                        <h1 class="text-3xl font-bold sm:text-4xl">Paiement</h1>
                        <p class="mt-2 text-ink-soft">Ton créneau est bloqué dès que le paiement est confirmé.</p>
                        <fieldset class="mt-8 grid gap-3 sm:grid-cols-2">
                            <legend class="sr-only">Moyen de paiement</legend>
                            <label v-for="(label, key) in paymentMethods" :key="key"
                                class="flex cursor-pointer items-center gap-3 rounded-2xl border bg-white px-5 py-4 transition active:scale-[0.98]"
                                :class="form.payment_method === key ? 'border-ember ring-2 ring-ember/30' : 'border-sand-deep hover:border-ink-mute'">
                                <input type="radio" v-model="form.payment_method" :value="key" class="h-4 w-4 accent-[#D9622B]" />
                                <span class="font-semibold">{{ label }}</span>
                            </label>
                        </fieldset>
                        <div v-if="form.payment_method !== 'card'" class="mt-5 max-w-sm">
                            <label class="label" for="payment_phone">Numéro Mobile Money à débiter</label>
                            <input id="payment_phone" v-model="form.payment_phone" class="field" type="tel" />
                            <p v-if="form.errors.payment_phone" class="msg-in mt-1 text-sm text-live">{{ form.errors.payment_phone }}</p>
                        </div>
                        <p class="mt-6 rounded-xl border border-dashed border-ember/40 bg-ember/5 px-4 py-3 text-sm text-ink-soft">
                            <strong class="text-ink">Version démo :</strong> le paiement est simulé, aucun argent n’est débité. En production, il passe par un agrégateur Mobile Money (T-Money, Flooz, MTN, Orange, Wave, carte) et l’équipe est payée directement.
                        </p>
                        <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                            <button type="button" class="text-sm font-medium text-ink-soft underline-offset-4 hover:underline" @click="go(4)">← Modifier mes infos</button>
                            <button type="submit" class="btn-primary !px-7 !py-3.5 text-base" :disabled="form.processing">
                                <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" /></svg>
                                {{ form.processing ? 'Confirmation…' : `Payer ${pkg ? fcfa(pkg.price) : ''}` }}
                            </button>
                        </div>
                    </form>
                    </Transition>
                </section>

                <!-- Récap -->
                <aside class="lg:sticky lg:top-24 lg:self-start">
                    <div class="rounded-2xl bg-night p-6 text-cream">
                        <p class="flex items-center gap-2 text-xs font-semibold tracking-[0.14em] text-glow uppercase">
                            <span class="live-dot h-2 w-2 rounded-full bg-live"></span> Ton live
                        </p>
                        <dl class="mt-5 space-y-4 text-sm">
                            <div v-for="[k, label] in recapRows" :key="k" class="flex justify-between gap-4">
                                <dt class="text-cream/60">{{ label }}</dt>
                                <dd class="text-right font-semibold"><Transition name="recap" mode="out-in"><span :key="recap[k]" class="inline-block">{{ recap[k] }}</span></Transition></dd>
                            </div>
                            <Transition name="recap-row">
                                <div v-if="form.business_name" class="flex justify-between gap-4"><dt class="text-cream/60">Business</dt><dd class="text-right font-semibold">{{ form.business_name }}</dd></div>
                            </Transition>
                        </dl>
                        <div class="mt-6 flex items-end justify-between border-t border-white/10 pt-5">
                            <span class="text-sm text-cream/60">Total</span>
                            <span class="font-display text-2xl font-extrabold"><Transition name="recap" mode="out-in"><span :key="recap.total" class="inline-block">{{ recap.total }}</span></Transition></span>
                        </div>
                    </div>
                    <p class="mt-4 px-1 text-xs text-ink-mute">Besoin d’aide ? L’équipe OCD Live te répond sur WhatsApp après ta réservation.</p>
                </aside>
            </div>
        </div>
    </PublicLayout>
</template>
