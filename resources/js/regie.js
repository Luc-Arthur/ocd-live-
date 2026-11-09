// La régie : horloge commune, codes temporels, chaînes, et les gestes communs à tous les moniteurs
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { gsap, reducedMotion } from '@/motion';
import { waLink } from '@/utils';

/* ---------- Allumage des moniteurs : écran noir, éclair blanc de 80 ms, puis l'image ---------- */

const screensIn = (container) => (container ? [...container.querySelectorAll('[data-monitor] .screen-fx')] : []);

/** Met les écrans au noir (avant l'allumage) */
export function blackOut(container) {
    const fx = screensIn(container);
    if (fx.length) gsap.set(fx, { autoAlpha: 1, backgroundColor: '#000' });
}

/** Allume les écrans un par un, en cascade de 0,4 s maximum. Mouvement réduit : simple fondu, sans éclair. */
export function powerOn(container) {
    const fx = screensIn(container);
    if (!fx.length) return null;
    const reduce = reducedMotion();
    const stagger = fx.length > 1 ? Math.min(0.06, 0.4 / (fx.length - 1)) : 0;
    gsap.killTweensOf(fx);
    gsap.set(fx, { autoAlpha: 1, backgroundColor: '#000' });
    const tl = gsap.timeline();
    fx.forEach((f, i) => {
        const t = i * stagger;
        if (reduce) {
            tl.to(f, { autoAlpha: 0, duration: 0.45, ease: 'power1.out' }, t);
        } else {
            tl.set(f, { backgroundColor: '#fff' }, t + 0.06).to(f, { autoAlpha: 0, duration: 0.32, ease: 'power2.out' }, t + 0.14);
        }
    });
    return tl;
}

/* ---------- Horloge partagée : un seul intervalle pour toute la page ---------- */

const now = ref(Date.now());
let offset = 0; // écart entre l'horloge du téléphone et celle du serveur
let users = 0;
let timer = 0;

/** Recale l'horloge sur l'heure du serveur (session.now), pour que les codes temporels soient justes */
export function syncServerTime(iso) {
    const t = iso ? Date.parse(iso) : NaN;
    if (!Number.isNaN(t)) {
        offset = t - Date.now();
        now.value = Date.now() + offset;
    }
}

/** Heure courante (ms), mise à jour chaque seconde tant qu'un composant l'utilise */
export function useNow() {
    onMounted(() => {
        if (users++ === 0) {
            now.value = Date.now() + offset;
            // Recalé sur la seconde pleine : toutes les horloges changent en même temps
            const tick = () => {
                now.value = Date.now() + offset;
                timer = setTimeout(tick, 1000 - ((Date.now() + offset) % 1000) + 5);
            };
            timer = setTimeout(tick, 1000 - (now.value % 1000) + 5);
        }
    });
    onBeforeUnmount(() => {
        if (--users === 0) clearTimeout(timer);
    });
    return now;
}

const pad = (n) => String(n).padStart(2, '0');

/** 00:41:12 depuis la mise à l'antenne */
export function timecode(iso, t) {
    const s = Math.max(0, Math.floor((t - Date.parse(iso)) / 1000));
    return `${pad(Math.floor(s / 3600))}:${pad(Math.floor((s % 3600) / 60))}:${pad(s % 60)}`;
}

const lomeFmt = new Intl.DateTimeFormat('fr-FR', { timeZone: 'Africa/Lome', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
/** 18:42:07, heure de Lomé, quel que soit le pays du visiteur */
export const lomeTime = (t) => lomeFmt.format(t);

/* ---------- Libellés de régie ---------- */

export const two = (n) => pad(n);

// Noms courts pour les touches de la table de mixage
export const CAT_SHORT = {
    nourriture: 'Nourriture', habits: 'Habits', beaute: 'Beauté', telephones: 'Téléphones',
    maison: 'Maison', champ: 'Champ', services: 'Services', autre: 'Autre',
};

/** Ligne de session : { label: 'Session du soir', hours: '18h00 – 21h00' } */
export function sessionParts(session) {
    if (!session.open) return { label: 'Prochaine session', hours: session.next_start.replace(':', 'h') };
    if (session.demo || !session.start) return { label: 'Session démo', hours: 'ouverte en continu' };
    return { label: session.label, hours: `${session.start.replace(':', 'h')} – ${session.end.replace(':', 'h')}` };
}

/* ---------- Vendeurs de démonstration : les boutons expliquent au lieu d'ouvrir ---------- */

export const demoNote = ref(false);
let demoTimer = 0;
export function flashDemoNote() {
    demoNote.value = true;
    clearTimeout(demoTimer);
    demoTimer = setTimeout(() => (demoNote.value = false), 3200);
}

/** Les pays deviennent des chaînes : CH 01 = le pays qui a le plus de lives (ordre stable par nom en cas d'égalité) */
export function channelList(counts, countries) {
    return Object.entries(countries)
        .map(([code, c]) => ({ code, name: c.name, n: counts[code] ?? 0 }))
        .filter((c) => c.n > 0)
        .sort((a, b) => b.n - a.n || a.name.localeCompare(b.name, 'fr'))
        .map((c, i) => ({ ...c, ch: pad(i + 1) }));
}

/* ---------- « Écouter » : la note vocale du vendeur, sinon la voix du téléphone ---------- */

const IN_COUNTRY = {
    TG: 'au Togo', BJ: 'au Bénin', CI: 'en Côte d’Ivoire', CM: 'au Cameroun', SN: 'au Sénégal', BF: 'au Burkina Faso',
    ML: 'au Mali', NE: 'au Niger', GN: 'en Guinée', GA: 'au Gabon', CG: 'au Congo', CD: 'en RD Congo',
};
const SELLS = {
    nourriture: 'vend de la nourriture', habits: 'vend des habits et des chaussures', beaute: 'vend des produits de beauté',
    telephones: 'vend des téléphones', maison: 'vend des articles pour la maison', champ: 'vend des produits du champ',
    services: 'propose des services', autre: 'est en live',
};

/** « Afi, au Togo, vend de la nourriture. Chez Afi Saveurs. » */
export function sayText(s) {
    const first = (s.name ?? '').split(' ')[0] || 'Ce vendeur';
    const shop = s.business_name ? ` ${s.business_name}.` : '';
    return `${first}, ${IN_COUNTRY[s.country] ?? ''}, ${SELLS[s.category] ?? 'est en live'}.${shop} Appuie sur Regarder pour voir son live.`;
}

// Une seule voix à la fois sur toute la page
let current = null; // { id, stop }
export const speakingId = ref(null);

export function listen(live) {
    const id = live.id;
    if (current) {
        const same = current.id === id;
        current.stop();
        if (same) return;
    }
    const s = live.seller;
    const done = () => {
        if (current?.id === id) { current = null; speakingId.value = null; }
    };

    if (s.voice_url) {
        const audio = new Audio(s.voice_url);
        audio.onended = audio.onerror = done;
        audio.play().catch(done);
        current = { id, stop: () => { audio.pause(); done(); } };
    } else if ('speechSynthesis' in window) {
        const synth = window.speechSynthesis;
        synth.cancel();
        const u = new SpeechSynthesisUtterance(sayText(s));
        u.lang = 'fr-FR';
        u.rate = 0.95;
        const voice = synth.getVoices().find((v) => v.lang?.toLowerCase().startsWith('fr'));
        if (voice) u.voice = voice;
        u.onend = u.onerror = done;
        synth.speak(u);
        current = { id, stop: () => { synth.cancel(); done(); } };
    } else {
        return;
    }
    speakingId.value = id;
}

export function stopListening() {
    current?.stop();
}

/* ---------- Regarder / WhatsApp / Partager ---------- */

export const waHref = (live) => waLink(live.seller.whatsapp, 'Bonjour, je vous ai vu en live sur OCD Live. Je suis intéressé(e) par vos produits.');

export async function shareLive(live) {
    const s = live.seller;
    const name = s.business_name || s.name;
    const url = `${location.origin}/en-direct?pays=${s.country}`;
    const text = s.is_demo
        ? `${name} est en live sur OCD Live.`
        : `${name} est en live sur TikTok : ${live.url}`;
    if (navigator.share) {
        try {
            await navigator.share({ title: `${name} · OCD Live`, text, url });
            return;
        } catch (e) {
            if (e?.name === 'AbortError') return; // la personne a fermé le partage
        }
    }
    window.open(waLink('', `${text}\n${url}`), '_blank', 'noopener');
}
