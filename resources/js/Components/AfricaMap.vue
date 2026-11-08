<script setup>
import { ref } from 'vue';
import { gsap, EASE, useMotion, whenVisible } from '@/motion';

// Silhouette simplifiée de l'Afrique (lon, lat) — rappel du mur lumineux du studio
const coast = [
    [-5.8, 35.8], [-2, 35.1], [3, 36.8], [8.6, 36.9], [11, 37.1], [10.2, 34.3], [10, 33.6], [11.5, 33.1], [13.2, 32.9], [15.3, 32.3],
    [19, 30.3], [20, 31.2], [20.1, 32.4], [22.5, 32.8], [25, 31.7], [29.9, 31.2], [32.3, 31.3], [32.6, 29.9], [33.6, 27.6], [35.5, 23.9],
    [37.2, 19.6], [38.6, 17.5], [39.5, 15.6], [41.6, 13.5], [43.3, 12.3], [44.5, 10.4], [47, 11.1], [51.3, 11.8], [51.1, 10.4], [49.8, 7.5],
    [47.9, 4.4], [45.3, 2], [42.6, -0.6], [40.2, -2.8], [39.7, -4], [38.8, -6.4], [39.3, -6.8], [39.5, -9.5], [40.5, -10.5], [40.6, -14.5],
    [39.2, -16.8], [36.8, -18], [34.8, -19.8], [35.5, -22.1], [35.4, -24], [32.6, -26], [32.4, -28.6], [31, -29.9], [28.7, -32.4], [25.6, -34],
    [22.5, -34], [20, -34.8], [18.4, -34.2], [18.3, -32], [17.4, -30.3], [16.5, -28.6], [15.1, -26.6], [14.5, -22.9], [13.2, -20], [11.8, -17.3],
    [12.1, -15.2], [13.6, -12.4], [13.2, -8.8], [12.3, -6], [11.8, -4.3], [9.6, -2.4], [8.7, -0.7], [9.4, 1], [9.7, 4], [8.5, 4.6],
    [6, 4.3], [4.5, 6.3], [2.4, 6.3], [1.2, 6.1], [-0.2, 5.6], [-2, 4.8], [-4, 5.2], [-7.7, 4.4], [-9.5, 5.4], [-10.8, 6.3],
    [-13.2, 8.5], [-13.7, 9.5], [-15.6, 11.9], [-16.7, 13.4], [-17.5, 14.7], [-16.5, 16.2], [-16, 18], [-16.3, 19.4], [-17, 20.8], [-16.4, 23.5],
    [-14.5, 26.1], [-13.1, 27.7], [-11.5, 28.3], [-9.6, 30.4], [-9.8, 31.7], [-8.4, 33.3], [-6.8, 34.1],
];
const madagascar = [[49.3, -12], [50.5, -15.5], [49.5, -17.2], [48.4, -21], [47.1, -24.9], [45.2, -25.6], [43.7, -23.6], [43.3, -21.8], [44.4, -19.5], [44.1, -17], [46.3, -15.7], [48, -13.5]];

const X = (lon) => (lon + 19) * 10;
const Y = (lat) => (38 - lat) * 10;
const path = (pts) => 'M' + pts.map(([lo, la]) => `${X(lo).toFixed(1)},${Y(la).toFixed(1)}`).join('L') + 'Z';

const lome = { x: X(1.22), y: Y(6.13) };
const remotes = [
    { name: 'Dakar', lon: -17.45, lat: 14.7, dx: 9, dy: -6, anchor: 'start' },
    { name: 'Bamako', lon: -8.0, lat: 12.65, dx: 9, dy: 5, anchor: 'start' },
    { name: 'Abidjan', lon: -4.0, lat: 5.35, dx: -9, dy: 5, anchor: 'end' },
    { name: 'Cotonou', lon: 2.42, lat: 6.37, dx: 4, dy: -12, anchor: 'start' },
    { name: 'Douala', lon: 9.7, lat: 4.05, dx: 9, dy: 0, anchor: 'start' },
    { name: 'Kinshasa', lon: 15.3, lat: -4.3, dx: 9, dy: 5, anchor: 'start' },
].map((c) => ({ ...c, x: X(c.lon), y: Y(c.lat) }));

// Liaison ville → studio (le signal d'un vendeur à distance qui arrive à la régie)
const link = (c) => `M${c.x},${c.y} Q${(c.x + lome.x) / 2},${Math.min(c.y, lome.y) - 60} ${lome.x},${lome.y}`;

const root = ref(null);

/*
 * Moment fort de l'accueil :
 * le mur lumineux s'allume (contour tracé puis lueur qui grésille), Lomé passe en live,
 * les liaisons partent du studio vers chaque ville à vitesse constante (les plus proches arrivent d'abord),
 * puis les signaux des vendeurs remontent en boucle vers la régie. La boucle s'arrête hors écran.
 */
useMotion((reduce) => {
    if (reduce) return;
    const q = gsap.utils.selector(root.value);
    const [mainland, island] = q('.coast-draw');
    const masks = q('.link-mask');
    const links = q('.link');
    const cities = q('.city');
    const signals = q('.signal');
    const core = q('.lome-core')[0];

    for (const p of [mainland, island]) {
        const len = p.getTotalLength();
        gsap.set(p, { strokeDasharray: len, strokeDashoffset: len });
    }
    // Décalage négatif : la liaison se dessine depuis sa fin (Lomé) vers la ville
    const maskLens = masks.map((p) => p.getTotalLength());
    masks.forEach((p, i) => gsap.set(p, { strokeDasharray: maskLens[i], strokeDashoffset: -maskLens[i] }));
    gsap.set(q('.coast-glow'), { opacity: 0 });
    gsap.set(q('.map-halo'), { opacity: 0, scale: 0.8 });
    gsap.set(q('.lome-dot'), { scale: 0, transformOrigin: '50% 50%' });
    gsap.set(q('.lome-label'), { opacity: 0, y: 8 });
    gsap.set(cities, { opacity: 0 });
    gsap.set(q('.city-dot'), { scale: 0.2, transformOrigin: '50% 50%' });

    const LIGHT = 1.05;
    const WAVE = 1.45;

    // Boucles de signal : ville → studio, petit éclat de Lomé à l'arrivée
    const loops = links.map((p, i) => {
        const len = p.getTotalLength();
        const dot = signals[i];
        const s = { t: 0 };
        const put = () => {
            const pt = p.getPointAtLength(s.t * len);
            dot.setAttribute('cx', pt.x.toFixed(1));
            dot.setAttribute('cy', pt.y.toFixed(1));
            dot.setAttribute('opacity', Math.max(0, Math.min(1, s.t / 0.12, (1 - s.t) / 0.12)).toFixed(2));
        };
        return gsap
            .timeline({ paused: true, repeat: -1, repeatDelay: 0.9 + (i % 3) * 0.8 })
            .fromTo(s, { t: 0 }, { t: 1, duration: gsap.utils.clamp(1.4, 3.2, len / 150), ease: 'sine.inOut', onUpdate: put })
            .fromTo(core, { attr: { r: 9 } }, { attr: { r: 11.5 }, duration: 0.14, yoyo: true, repeat: 1, ease: 'power1.out' });
    });

    const intro = gsap.timeline({ paused: true });
    intro
        .to(mainland, { strokeDashoffset: 0, duration: 1.3, ease: 'power2.inOut' }, 0)
        .to(island, { strokeDashoffset: 0, duration: 0.5, ease: 'power2.out' }, 0.9)
        .to(q('.coast-glow'), { keyframes: { opacity: [0, 0.75, 0.3, 1], easeEach: 'none' }, duration: 0.5 }, LIGHT)
        .to(q('.map-halo'), { opacity: 1, scale: 1, duration: 1.4, ease: EASE }, LIGHT)
        .to(q('.lome-dot'), { scale: 1, duration: 0.7, ease: EASE }, LIGHT + 0.2)
        .to(q('.lome-label'), { opacity: 1, y: 0, duration: 0.6, ease: EASE }, LIGHT + 0.35);

    let waveEnd = WAVE;
    masks.forEach((m, i) => {
        const d = gsap.utils.clamp(0.3, 1, maskLens[i] / 420);
        intro
            .to(m, { strokeDashoffset: 0, duration: d, ease: 'power1.out' }, WAVE)
            .to(cities[i], { opacity: 1, duration: 0.35 }, WAVE + d * 0.85)
            .to(cities[i].querySelector('.city-dot'), { scale: 1, duration: 0.5, ease: EASE }, WAVE + d * 0.85);
        waveEnd = Math.max(waveEnd, WAVE + d);
    });
    loops.forEach((l, i) => intro.call(() => l.play(), null, waveEnd + 0.2 + i * 0.45));

    let started = false;
    return whenVisible(
        root.value,
        () => {
            if (!started) {
                started = true;
                intro.play();
            } else if (intro.progress() === 1) {
                loops.forEach((l) => l.play());
            }
        },
        { once: false, onLeave: () => loops.forEach((l) => l.pause()) },
    );
});
</script>

<template>
    <div ref="root" class="relative">
        <div class="map-halo absolute inset-8 rounded-full bg-glow/30 blur-3xl" aria-hidden="true"></div>
        <svg viewBox="0 0 720 740" class="relative h-auto w-full" role="img" aria-label="Carte de l'Afrique : le studio de Lomé relié aux vendeurs de toute l'Afrique">
            <defs>
                <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="9" result="b" />
                    <feMerge><feMergeNode in="b" /><feMergeNode in="b" /><feMergeNode in="SourceGraphic" /></feMerge>
                </filter>
                <radialGradient id="fill" cx="45%" cy="40%" r="70%">
                    <stop offset="0%" stop-color="#FFFDF9" />
                    <stop offset="100%" stop-color="#F7EEE2" />
                </radialGradient>
                <mask v-for="(c, i) in remotes" :id="`ocd-link-${i}`" :key="'m' + c.name" maskUnits="userSpaceOnUse" x="0" y="0" width="720" height="740">
                    <path class="link-mask" :d="link(c)" fill="none" stroke="#fff" stroke-width="6" />
                </mask>
            </defs>

            <!-- Tracé du contour (sans filtre, léger à animer), recouvert ensuite par la version lumineuse -->
            <path class="coast-draw" :d="path(coast)" fill="none" stroke="#F6C77D" stroke-width="4" stroke-linejoin="round" />
            <path class="coast-draw" :d="path(madagascar)" fill="none" stroke="#F6C77D" stroke-width="4" stroke-linejoin="round" />
            <g class="coast-glow" filter="url(#glow)">
                <path :d="path(coast)" fill="url(#fill)" stroke="#F6C77D" stroke-width="5" stroke-linejoin="round" />
                <path :d="path(madagascar)" fill="url(#fill)" stroke="#F6C77D" stroke-width="5" stroke-linejoin="round" />
            </g>

            <!-- Liaisons à distance vers le studio -->
            <g fill="none" stroke="#D9622B" stroke-width="1.6" stroke-dasharray="4 6" opacity="0.55">
                <path v-for="(c, i) in remotes" :key="c.name" class="link" :d="link(c)" :mask="`url(#ocd-link-${i})`" />
            </g>
            <g v-for="c in remotes" :key="'d' + c.name" class="city">
                <circle class="city-dot" :cx="c.x" :cy="c.y" r="5" fill="#1C1410" />
                <text :x="c.x + c.dx" :y="c.y + c.dy" :text-anchor="c.anchor" font-size="15" fill="#5B4B40" font-family="Instrument Sans, sans-serif">{{ c.name }}</text>
            </g>

            <!-- Signaux des vendeurs qui remontent vers la régie -->
            <circle v-for="c in remotes" :key="'s' + c.name" class="signal" :cx="c.x" :cy="c.y" r="4.5" fill="#D9622B" stroke="#FFFDF9" stroke-width="1.5" opacity="0" aria-hidden="true" />

            <!-- Studio de Lomé -->
            <g class="lome-dot">
                <circle class="pulse-ring" :cx="lome.x" :cy="lome.y" r="12" fill="#E5383B" opacity="0.5" />
                <circle class="lome-core" :cx="lome.x" :cy="lome.y" r="9" fill="#E5383B" stroke="#fff" stroke-width="3" />
            </g>
            <g :transform="`translate(${lome.x - 70}, ${lome.y + 48})`">
                <g class="lome-label">
                    <rect x="0" y="-17" width="118" height="26" rx="13" fill="#1C1410" />
                    <circle cx="14" cy="-4" r="4" fill="#E5383B" class="live-dot" />
                    <text x="24" y="1" font-size="14" font-weight="600" fill="#FBF6EF" font-family="Instrument Sans, sans-serif">Studio · Lomé</text>
                </g>
            </g>
        </svg>
    </div>
</template>
