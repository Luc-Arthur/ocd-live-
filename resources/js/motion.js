import { gsap } from 'gsap';
import { onBeforeUnmount, onMounted } from 'vue';

export { gsap };

// Décélération franche : arrivée assurée, sans rebond
export const EASE = 'expo.out';

export const reducedMotion = () =>
    typeof window !== 'undefined' && !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/**
 * Lance `setup(reduce, ctx)` au montage dans un gsap.context, et nettoie tout au démontage
 * (indispensable avec Inertia : chaque visite démonte la page précédente).
 * `setup` peut renvoyer une fonction de nettoyage supplémentaire.
 */
export function useMotion(setup) {
    let ctx;
    let cleanup;
    onMounted(() => {
        ctx = gsap.context((self) => {
            cleanup = setup(reducedMotion(), self);
        });
    });
    onBeforeUnmount(() => {
        cleanup?.();
        ctx?.revert();
    });
}

/** Appelle `onEnter` quand `el` entre dans l'écran (et `onLeave` quand il en sort). Renvoie la déconnexion. */
export function whenVisible(el, onEnter, { once = true, onLeave, rootMargin = '0px' } = {}) {
    if (!el) return () => {};
    if (!('IntersectionObserver' in window)) {
        onEnter();
        return () => {};
    }
    const io = new IntersectionObserver(
        (entries) => {
            for (const e of entries) {
                if (e.isIntersecting) {
                    onEnter();
                    if (once) io.disconnect();
                } else {
                    onLeave?.();
                }
            }
        },
        { rootMargin },
    );
    io.observe(el);
    return () => io.disconnect();
}

/**
 * Entrée en cascade des éléments d'une liste, au fur et à mesure qu'ils arrivent à l'écran.
 * Les éléments qui entrent ensemble partent ensemble (cascade plafonnée à `total` secondes).
 */
export function revealList(container, selector, { y = 24, duration = 0.7, stagger = 0.08, total = 0.4 } = {}) {
    const items = container ? [...container.querySelectorAll(selector)] : [];
    if (!items.length || !('IntersectionObserver' in window)) return () => {};

    const reduce = reducedMotion();
    gsap.set(items, { autoAlpha: 0, y: reduce ? 0 : y, transition: 'none' });

    const pending = [];
    let raf = 0;
    const io = new IntersectionObserver(
        (entries) => {
            for (const e of entries) {
                if (!e.isIntersecting) continue;
                pending.push(e.target);
                io.unobserve(e.target);
            }
            if (!pending.length || raf) return;
            raf = requestAnimationFrame(() => {
                raf = 0;
                const batch = pending.splice(0).sort((a, b) => items.indexOf(a) - items.indexOf(b));
                gsap.to(batch, {
                    autoAlpha: 1,
                    y: 0,
                    duration: reduce ? 0.35 : duration,
                    ease: EASE,
                    stagger: reduce ? 0 : Math.min(stagger, total / Math.max(1, batch.length - 1)),
                    clearProps: 'opacity,visibility,transform,transition',
                });
            });
        },
        { rootMargin: '0px 0px -8% 0px' },
    );
    items.forEach((el) => io.observe(el));

    return () => {
        cancelAnimationFrame(raf);
        io.disconnect();
    };
}

/** Petit refus horizontal : « il manque quelque chose ». Rien en mouvement réduit. */
export function shake(el) {
    if (!el || reducedMotion()) return;
    gsap.to(el, {
        keyframes: { x: [0, -8, 7, -4, 2, 0], easeEach: 'power1.inOut' },
        duration: 0.42,
        clearProps: 'transform',
    });
}
