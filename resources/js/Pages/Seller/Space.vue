<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import FlagIcon from '@/Components/FlagIcon.vue';
import CategoryIcon from '@/Components/CategoryIcon.vue';
import CategoryTiles from '@/Components/CategoryTiles.vue';
import ListenButton from '@/Components/ListenButton.vue';
import SessionBanner from '@/Components/SessionBanner.vue';
import VoiceRecorder from '@/Components/VoiceRecorder.vue';
import { CAT_COLORS, fcfa, waLink } from '@/utils';
import { compressPhoto } from '@/photo';

const props = defineProps({
    step: String, pendingPhone: String, demoCode: String, seller: Object, live: Object,
    session: Object, price: Number, methods: Object,
});
const dir = computed(() => usePage().props.directory);
const countryList = computed(() => Object.entries(dir.value.countries).map(([code, c]) => ({ code, ...c })));

// Modifier le profil / renouveler depuis l'écran principal
const editing = ref(false);
const renewing = ref(false);
const view = computed(() => (props.step === 'live' && editing.value ? 'profile' : props.step === 'live' && renewing.value ? 'subscribe' : props.step));
watch(() => props.step, () => { editing.value = false; renewing.value = false; });

const steps = [['phone', 'Numéro'], ['profile', 'Profil'], ['subscribe', 'Abonnement'], ['live', 'Live']];
const stepIndex = computed(() => Math.max(0, steps.findIndex(([k]) => k === (view.value === 'code' ? 'phone' : view.value))));

/* 1. Numéro */
const phoneForm = useForm({ dial: '+228', number: '' });
const dialCountry = computed(() => countryList.value.find((c) => c.dial === phoneForm.dial)?.code ?? 'TG');
const showDials = ref(false);

/* 2. Code */
const codeForm = useForm({ code: '' });
watch(() => codeForm.code, (v) => { if (/^\d{4}$/.test(v)) codeForm.post('/vendre/connexion'); });

/* 3. Profil */
const profileForm = useForm({
    name: props.seller?.name ?? '',
    business_name: props.seller?.business_name ?? '',
    country: props.seller?.country ?? (props.pendingPhone ? null : null),
    category: props.seller?.category ?? null,
    tiktok: props.seller?.tiktok_username ? '@' + props.seller.tiktok_username : '',
    voice: null,
    photo: null,
});
if (!profileForm.country && props.seller?.phone) {
    profileForm.country = countryList.value.find((c) => props.seller.phone.startsWith(c.dial))?.code ?? null;
}
const saveProfile = () => profileForm.post('/vendre/profil', { forceFormData: true, preserveScroll: true });

// Photo du produit : réduite dans le téléphone avant l'envoi
const photoPreview = ref(props.seller?.photo_url ?? null);
const photoBusy = ref(false);
const photoError = ref('');
const onPhoto = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    photoBusy.value = true;
    photoError.value = '';
    profileForm.clearErrors('photo');
    try {
        const small = await compressPhoto(file);
        profileForm.photo = small;
        if (photoPreview.value?.startsWith('blob:')) URL.revokeObjectURL(photoPreview.value);
        photoPreview.value = URL.createObjectURL(small);
    } catch {
        // Format que le navigateur ne sait pas lire : on envoie l'original, le serveur vérifie
        if (file.size > 6 * 1024 * 1024) {
            photoError.value = 'Photo trop lourde. Reprends la photo.';
        } else {
            profileForm.photo = file;
            photoPreview.value = null;
            photoError.value = 'Photo ajoutée (aperçu indisponible).';
        }
    } finally {
        photoBusy.value = false;
    }
};
onBeforeUnmount(() => { if (photoPreview.value?.startsWith('blob:')) URL.revokeObjectURL(photoPreview.value); });

/* 4. Abonnement */
const payForm = useForm({ method: 'tmoney', payer_phone: props.seller?.phone ?? '' });
watch(() => props.seller, (s) => {
    if (!s) return;
    if (!payForm.payer_phone) payForm.payer_phone = s.phone;
    if (!profileForm.country) profileForm.country = countryList.value.find((c) => s.phone?.startsWith(c.dial))?.code ?? null;
}, { immediate: true });
const methodColor = { tmoney: '#0057B8', flooz: '#0098D9', orange: '#FF7900', mtn: '#FFCC00', wave: '#1DC4FF' };

/* 5. Live */
const liveForm = useForm({});
const goLive = () => liveForm.post('/vendre/live', { preserveScroll: true });
const endLive = () => router.post('/vendre/live/terminer', {}, { preserveScroll: true });

// En attente : on vérifie toutes les 4 secondes si l'équipe a validé
let poll;
watch(() => props.live?.status, (s) => {
    clearInterval(poll);
    if (s === 'pending') poll = setInterval(() => router.reload({ only: ['live', 'session'] }), 4000);
}, { immediate: true });
onBeforeUnmount(() => clearInterval(poll));

const shareText = computed(() => props.seller ? `Je suis en live maintenant sur TikTok ! Viens voir : https://www.tiktok.com/@${props.seller.tiktok_username}/live (trouvé sur OCD Live)` : '');
const listen = {
    phone: 'Écris ton numéro WhatsApp, puis appuie sur Recevoir mon code.',
    code: 'Regarde ton WhatsApp. Écris le code à quatre chiffres que tu as reçu.',
    profile: 'Appuie sur l’appareil photo et prends ton produit en photo. Écris ton nom. Touche ton pays. Touche ce que tu vends. Écris ton nom TikTok. Si tu veux, appuie sur le micro et dis ce que tu vends. Puis appuie sur Enregistrer.',
    subscribe: 'L\'abonnement coûte cinq mille francs par mois. Choisis ton Mobile Money et appuie sur Payer.',
    live: 'D\'abord, lance ton live sur TikTok. Ensuite, reviens ici et appuie sur le gros bouton rouge. L\'équipe OCD vérifie, puis tout le monde te voit.',
};
</script>

<template>
    <Head title="Je vends en live" />
    <PublicLayout>
        <div class="mx-auto max-w-lg px-4 pt-6 pb-16">
            <!-- Progression -->
            <ol class="flex gap-1.5" aria-label="Étapes">
                <li v-for="([k, label], i) in steps" :key="k" class="flex-1">
                    <span class="block h-1.5 rounded-full" :class="i < stepIndex ? 'bg-ember' : i === stepIndex ? 'bg-ink' : 'bg-sand-deep'"></span>
                    <span class="mt-1.5 block text-[11px] font-semibold" :class="i === stepIndex ? 'text-ink' : 'text-ink-mute'">{{ label }}</span>
                </li>
            </ol>

            <!-- 1. Numéro WhatsApp -->
            <form v-if="view === 'phone'" class="mt-8" @submit.prevent="phoneForm.post('/vendre/code')">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-[#25D366] text-white">
                    <svg viewBox="0 0 24 24" class="h-9 w-9" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm4.5 12.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3z" /></svg>
                </span>
                <div class="mt-4 flex items-start justify-between gap-3">
                    <h1 class="text-3xl font-extrabold">Ton numéro WhatsApp</h1>
                    <ListenButton :text="listen.phone" />
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="button" @click="showDials = !showDials" class="flex shrink-0 items-center gap-2 rounded-2xl border border-sand-deep bg-white px-3 text-lg font-semibold" :aria-expanded="showDials">
                        <FlagIcon :code="dialCountry" :size="28" />{{ phoneForm.dial }}
                    </button>
                    <input id="number" v-model="phoneForm.number" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="90 12 34 56"
                        class="field !py-4 !text-2xl font-semibold tracking-wide" aria-label="Numéro WhatsApp" />
                </div>
                <div v-if="showDials" class="mt-3 grid grid-cols-3 gap-2">
                    <button v-for="c in countryList" :key="c.code" type="button" @click="phoneForm.dial = c.dial; showDials = false"
                        class="flex items-center gap-2 rounded-xl bg-white px-2.5 py-2 text-sm font-semibold ring-1 ring-sand-deep" :class="phoneForm.dial === c.dial && 'ring-2 ring-ink'">
                        <FlagIcon :code="c.code" :size="22" />{{ c.dial }}
                    </button>
                </div>
                <p v-if="phoneForm.errors.number" class="mt-2 text-sm font-medium text-live">{{ phoneForm.errors.number }}</p>
                <button type="submit" class="btn-primary mt-6 w-full !py-4 text-lg" :disabled="phoneForm.processing">Recevoir mon code</button>
            </form>

            <!-- 2. Code -->
            <form v-else-if="view === 'code'" class="mt-8" @submit.prevent="codeForm.post('/vendre/connexion')">
                <div class="flex items-start justify-between gap-3">
                    <h1 class="text-3xl font-extrabold">Ton code</h1>
                    <ListenButton :text="listen.code" />
                </div>
                <p class="mt-2 text-ink-soft">Envoyé sur WhatsApp au <strong class="text-ink">{{ pendingPhone }}</strong></p>
                <p class="mt-4 rounded-xl border border-dashed border-ember/50 bg-ember/5 px-4 py-3 text-sm">Démo : ton code est <strong class="font-display text-lg">{{ demoCode }}</strong></p>
                <input id="code" v-model="codeForm.code" inputmode="numeric" maxlength="4" autocomplete="one-time-code" placeholder="• • • •"
                    class="field mt-5 !py-5 text-center !text-4xl font-extrabold tracking-[0.6em]" aria-label="Code à 4 chiffres" />
                <p v-if="codeForm.errors.code" class="mt-2 text-sm font-medium text-live">{{ codeForm.errors.code }}</p>
                <button type="submit" class="btn-primary mt-5 w-full !py-4 text-lg" :disabled="codeForm.processing">Valider</button>
                <button type="button" class="mt-4 w-full text-sm font-semibold text-ink-mute hover:text-ink" @click="router.post('/vendre/changer-numero')">Changer de numéro</button>
            </form>

            <!-- 3. Profil -->
            <form v-else-if="view === 'profile'" class="mt-8 space-y-7" @submit.prevent="saveProfile">
                <div class="flex items-start justify-between gap-3">
                    <h1 class="text-3xl font-extrabold">Qui es-tu ?</h1>
                    <ListenButton :text="listen.profile" />
                </div>

                <!-- Photo du produit : le premier geste, le plus visible -->
                <div>
                    <label for="photo" class="group relative flex aspect-[4/3] w-full cursor-pointer flex-col items-center justify-center gap-3 overflow-hidden rounded-3xl border-2 border-dashed border-ember/45 bg-ember/5 transition hover:bg-ember/10 focus-within:ring-[3px] focus-within:ring-ember/40"
                        :class="photoPreview && 'border-solid border-transparent'">
                        <img v-if="photoPreview" :src="photoPreview" alt="Photo de ton produit" class="absolute inset-0 h-full w-full object-cover" />
                        <template v-if="!photoPreview">
                            <span class="grid h-20 w-20 place-items-center rounded-full bg-ember text-white shadow-glow transition group-active:scale-95">
                                <svg viewBox="0 0 24 24" class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h1.6l1.4-2h5l1.4 2h1.6A2.5 2.5 0 0 1 20 8.5v8a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5z" /><circle cx="12" cy="12.5" r="3.6" /></svg>
                            </span>
                            <span class="text-center">
                                <span class="block font-display text-xl font-extrabold text-ink">Une photo de ton produit</span>
                                <span class="mt-0.5 block text-sm text-ink-soft">Les clients la voient en premier · facultatif</span>
                            </span>
                        </template>
                        <span v-else class="absolute bottom-3 left-1/2 inline-flex -translate-x-1/2 items-center gap-2 rounded-full bg-ink/85 px-4 py-2.5 text-sm font-semibold whitespace-nowrap text-cream">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h1.6l1.4-2h5l1.4 2h1.6A2.5 2.5 0 0 1 20 8.5v8a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5z" /><circle cx="12" cy="12.5" r="3.6" /></svg>
                            Reprendre la photo
                        </span>
                        <span v-if="photoBusy" class="absolute inset-0 grid place-items-center bg-cream/80 text-sm font-semibold text-ink">Préparation de la photo…</span>
                        <input id="photo" type="file" accept="image/*" capture="environment" class="sr-only" @change="onPhoto" />
                    </label>
                    <label class="mt-2 inline-flex min-h-11 cursor-pointer items-center text-sm font-semibold text-ink-soft underline underline-offset-4 focus-within:text-ink">
                        Choisir dans la galerie
                        <input type="file" accept="image/*" class="sr-only" @change="onPhoto" />
                    </label>
                    <p v-if="profileForm.errors.photo || photoError" class="msg-in text-sm font-medium" :class="profileForm.errors.photo || !profileForm.photo ? 'text-live' : 'text-ink-soft'">{{ profileForm.errors.photo || photoError }}</p>
                </div>

                <div class="grid gap-3">
                    <input id="name" v-model="profileForm.name" class="field !py-4 !text-lg" placeholder="Ton nom" aria-label="Ton nom" autocomplete="name" />
                    <input id="business_name" v-model="profileForm.business_name" class="field" placeholder="Nom de ta boutique (facultatif)" aria-label="Nom de ta boutique" />
                    <p v-if="profileForm.errors.name" class="text-sm font-medium text-live">{{ profileForm.errors.name }}</p>
                </div>
                <div>
                    <p class="mb-2 font-display text-lg font-bold">Ton pays</p>
                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="c in countryList" :key="c.code" type="button" @click="profileForm.country = c.code"
                            class="flex flex-col items-center gap-1.5 rounded-xl bg-white px-1 py-2.5 text-xs font-semibold ring-1 ring-sand-deep"
                            :class="profileForm.country === c.code && '!ring-[3px] !ring-ink'" :aria-pressed="profileForm.country === c.code">
                            <FlagIcon :code="c.code" :size="34" />{{ c.name }}
                        </button>
                    </div>
                    <p v-if="profileForm.errors.country" class="mt-2 text-sm font-medium text-live">{{ profileForm.errors.country }}</p>
                </div>
                <div>
                    <p class="mb-2 font-display text-lg font-bold">Tu vends quoi ?</p>
                    <CategoryTiles v-model="profileForm.category" />
                    <p v-if="profileForm.errors.category" class="mt-2 text-sm font-medium text-live">{{ profileForm.errors.category }}</p>
                </div>
                <div>
                    <p class="mb-2 font-display text-lg font-bold">Ton TikTok</p>
                    <input id="tiktok" v-model="profileForm.tiktok" class="field !py-4 !text-lg" placeholder="@tonnom" aria-label="Ton nom TikTok" autocapitalize="off" />
                    <p v-if="profileForm.errors.tiktok" class="mt-2 text-sm font-medium text-live">{{ profileForm.errors.tiktok }}</p>
                </div>
                <div>
                    <p class="mb-2 font-display text-lg font-bold">Ta voix <span class="text-sm font-normal text-ink-mute">(facultatif)</span></p>
                    <VoiceRecorder :existing-url="seller?.voice_url" @update:file="(f) => (profileForm.voice = f)" />
                </div>
                <button type="submit" class="btn-primary w-full !py-4 text-lg" :disabled="profileForm.processing">Enregistrer</button>
                <button v-if="editing" type="button" class="w-full text-sm font-semibold text-ink-mute" @click="editing = false">Annuler</button>
            </form>

            <!-- 4. Abonnement -->
            <form v-else-if="view === 'subscribe'" class="mt-8" @submit.prevent="payForm.post('/vendre/abonnement', { preserveScroll: true, onSuccess: () => (renewing = false) })">
                <div class="flex items-start justify-between gap-3">
                    <h1 class="text-3xl font-extrabold">Abonnement</h1>
                    <ListenButton :text="listen.subscribe" />
                </div>
                <div class="mt-5 rounded-3xl bg-night p-6 text-cream">
                    <p class="font-display text-5xl font-extrabold">{{ fcfa(price) }}</p>
                    <p class="mt-1 text-cream/70">par mois · environ {{ Math.round(price / 30) }} FCFA par jour</p>
                    <p class="mt-4 flex items-center gap-2 text-glow"><span class="live-dot h-2 w-2 rounded-full bg-live"></span>Visible sur la carte pendant chaque session</p>
                </div>
                <p class="mt-6 mb-2 font-display text-lg font-bold">Paie avec</p>
                <div class="grid grid-cols-2 gap-2">
                    <label v-for="(label, key) in methods" :key="key" class="flex cursor-pointer items-center gap-3 rounded-2xl bg-white px-3 py-3.5 ring-1 ring-sand-deep" :class="payForm.method === key && '!ring-[3px] !ring-ink'">
                        <input type="radio" v-model="payForm.method" :value="key" class="sr-only" />
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-sm font-extrabold text-white" :style="{ background: methodColor[key] }">{{ label[0] }}</span>
                        <span class="font-semibold">{{ label }}</span>
                    </label>
                </div>
                <input id="payer_phone" v-model="payForm.payer_phone" type="tel" class="field mt-4 !py-4 !text-lg" aria-label="Numéro Mobile Money" placeholder="Numéro Mobile Money" />
                <p class="mt-3 text-xs text-ink-mute">Démo : paiement simulé, rien n’est débité.</p>
                <button type="submit" class="btn-primary mt-5 w-full !py-4 text-lg" :disabled="payForm.processing">Payer {{ fcfa(price) }}</button>
                <button v-if="renewing" type="button" class="mt-3 w-full text-sm font-semibold text-ink-mute" @click="renewing = false">Annuler</button>
            </form>

            <!-- 5. Je suis en live -->
            <div v-else class="mt-6">
                <div class="flex items-center gap-3">
                    <img v-if="seller.photo_url" :src="seller.photo_url" alt="" class="h-14 w-14 shrink-0 rounded-2xl object-cover ring-1 ring-sand-deep" />
                    <span v-else class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl" :style="{ background: CAT_COLORS[seller.category][0], color: CAT_COLORS[seller.category][1] }">
                        <CategoryIcon :slug="seller.category" :size="32" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-display text-xl font-extrabold">{{ seller.business_name || seller.name }}</p>
                        <p class="flex items-center gap-1.5 text-sm text-ink-soft"><FlagIcon :code="seller.country" :size="20" />{{ seller.country_name }} · {{ seller.tiktok }}</p>
                    </div>
                    <ListenButton class="ml-auto" label="" :text="listen.live" />
                </div>
                <SessionBanner class="mt-5" :session="session" compact />

                <!-- Validé -->
                <div v-if="live?.status === 'approved'" class="mt-8 text-center">
                    <span class="mx-auto grid h-28 w-28 place-items-center rounded-full bg-leaf text-white">
                        <svg viewBox="0 0 20 20" class="h-14 w-14" fill="currentColor"><path d="M7.7 13.3 4.4 10l-1.1 1.1 4.4 4.4 9-9-1.1-1.1z" /></svg>
                    </span>
                    <h1 class="mt-5 text-3xl font-extrabold">Tu es sur OCD Live !</h1>
                    <p class="mt-2 text-ink-soft">Visible dans {{ seller.country_name }} · {{ seller.category_name }}</p>
                    <div class="mt-6 grid gap-3">
                        <Link :href="`/en-direct?pays=${seller.country}&categorie=${seller.category}`" class="btn-dark w-full !py-4 text-base">Voir ma fiche</Link>
                        <a :href="waLink('', shareText)" target="_blank" rel="noopener" class="btn w-full !py-4 text-base bg-[#25D366] text-white">Partager sur WhatsApp</a>
                        <button type="button" class="btn-ghost w-full !py-4" @click="endLive">Mon live est fini</button>
                    </div>
                </div>

                <!-- En attente -->
                <div v-else-if="live?.status === 'pending'" class="mt-8 text-center">
                    <span class="relative mx-auto grid h-28 w-28 place-items-center rounded-full bg-glow/40">
                        <span class="absolute inset-0 animate-ping rounded-full bg-glow/40 motion-reduce:animate-none"></span>
                        <svg viewBox="0 0 24 24" class="relative h-12 w-12 text-ember-dark" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" fill="currentColor" /></svg>
                    </span>
                    <h1 class="mt-5 text-3xl font-extrabold">L’équipe regarde ton live…</h1>
                    <p class="mt-2 text-ink-soft">Garde ton live ouvert. Ça prend quelques minutes.</p>
                    <a href="/admin/lives" target="_blank" rel="noopener" class="mt-6 inline-block rounded-xl border border-dashed border-ember/50 bg-ember/5 px-4 py-3 text-sm">Démo : valide-le toi-même dans <strong>l’espace équipe</strong> ↗</a>
                    <button type="button" class="mt-6 block w-full text-sm font-semibold text-ink-mute" @click="endLive">Annuler</button>
                </div>

                <!-- Bouton principal -->
                <div v-else class="mt-8 text-center">
                    <p v-if="live?.status === 'rejected'" class="mb-6 rounded-2xl bg-live/10 px-4 py-3 text-sm font-medium text-live">
                        Live refusé{{ live.reject_reason ? ' : ' + live.reject_reason : '' }}. Vérifie que ton live est bien lancé, puis réessaie.
                    </p>
                    <button type="button" @click="goLive" :disabled="!session.open || liveForm.processing"
                        class="relative mx-auto grid h-56 w-56 place-items-center rounded-full text-white transition active:scale-95 disabled:cursor-not-allowed"
                        :class="session.open ? 'bg-live shadow-[0_20px_60px_-10px_rgba(229,56,59,.7)]' : 'bg-ink-mute/40'">
                        <template v-if="session.open">
                            <span class="absolute inset-0 animate-ping rounded-full bg-live opacity-20 motion-reduce:animate-none"></span>
                            <span class="absolute -inset-3 rounded-full ring-8 ring-live/15"></span>
                        </template>
                        <span class="relative">
                            <span class="block text-sm font-bold tracking-[0.2em] opacity-85">JE SUIS</span>
                            <span class="block font-display text-5xl font-extrabold">EN LIVE</span>
                        </span>
                    </button>
                    <p class="mt-6 font-semibold">{{ session.open ? 'Lance ton live sur TikTok, puis appuie.' : `Reviens à ${session.next_start.replace(':', 'h')}` }}</p>
                    <p v-if="liveForm.errors.live" class="mt-2 text-sm font-medium text-live">{{ liveForm.errors.live }}</p>
                    <a href="https://www.tiktok.com/" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-ink-soft underline-offset-4 hover:underline">Ouvrir TikTok ↗</a>
                </div>

                <div class="mt-10 space-y-2 rounded-2xl bg-white p-4 text-sm ring-1 ring-sand-deep">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-ink-soft">Abonnement jusqu’au <strong class="text-ink">{{ seller.subscription_ends_at }}</strong></span>
                        <button type="button" class="font-semibold text-ember-dark" @click="renewing = true">Renouveler</button>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-sand pt-2">
                        <button type="button" class="font-semibold text-ink" @click="editing = true">Modifier mon profil</button>
                        <button type="button" class="text-ink-mute" @click="router.post('/vendre/deconnexion')">Se déconnecter</button>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
