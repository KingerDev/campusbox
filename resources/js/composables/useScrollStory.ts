import { onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

/** Breakpoint putujúceho mobilu – rovnaký v CSS (@media min-width: 980px) aj tu. */
export const STORY_BREAKPOINT = 980;

export interface StoryMetrics {
    H: number;
    sc: number;
    ph: number;
    top: number;
}

/** Rozmery mobilu podľa výšky okna (72 px = sticky hlavička). */
export function storyMetrics(vh: number): StoryMetrics {
    const H = Math.min(vh || 800, 1000);
    const sc = Math.max(0.62, Math.min(1, (H - 120) / 612));
    const ph = Math.round(612 * sc);
    return { H, sc, ph, top: Math.round(72 + (H - 72 - ph) / 2) };
}

interface Stop {
    y: number;
    h: number;
    x: number;
    dx: number;
    s: number;
    r: number;
    k: string;
}

/** Y prvku v súradniciach story cez offsetTop (reveal animácie posúvajú prvky transformom). */
function offsetTopIn(story: HTMLElement, el: HTMLElement) {
    let y = 0;
    let n: HTMLElement | null = el;
    while (n && n !== story) {
        y += n.offsetTop;
        n = n.offsetParent as HTMLElement | null;
    }
    return y;
}

/**
 * Putujúci mobil: sleduje scroll, posúva mobil medzi zastávkami [data-step] a počíta bodkovanú trasu.
 * Transform mobilu sa zapisuje priamo do DOM, reaktívne sa mení len obrazovka, trasa a krúžky.
 */
export function useScrollStory(story: Ref<HTMLElement | null>, mover: Ref<HTMLElement | null>, clip: Ref<SVGRectElement | null>) {
    const desk = ref(typeof window !== 'undefined' ? window.innerWidth >= STORY_BREAKPOINT : true);
    const metrics = ref<StoryMetrics>(storyMetrics(typeof window !== 'undefined' ? window.innerHeight : 800));
    const screen = ref('home');
    const route = ref('');
    const dots = ref<{ x: number; y: number; on: boolean }[]>([]);

    let raf = 0;
    let routeSig = '';
    let reduce = false;
    let zoom = 0;
    const late: ReturnType<typeof setTimeout>[] = [];

    const move = () => {
        raf = 0;
        if (window.innerWidth < STORY_BREAKPOINT) return;
        const st = story.value;
        const mv = mover.value;
        if (!st || !mv) return;
        const wrap = mv.closest('.lp-wrap') as HTMLElement | null;
        if (!wrap) return;

        const cs = getComputedStyle(wrap);
        const padL = parseFloat(cs.paddingLeft);
        const span = Math.max(0, wrap.clientWidth - padL - parseFloat(cs.paddingRight) - 300);
        const left0 = wrap.offsetLeft + padL;
        const m = storyMetrics(window.innerHeight);
        const mid = m.top + m.ph / 2 - st.getBoundingClientRect().top;

        const ks: Stop[] = [...st.querySelectorAll<HTMLElement>('[data-step]')]
            .map((el) => ({
                y: offsetTopIn(st, el) + el.offsetHeight / 2,
                h: el.offsetHeight,
                x: +(el.dataset.x || 1),
                dx: +(el.dataset.dx || 0),
                s: +(el.dataset.s || 1),
                r: +(el.dataset.r || 0),
                k: el.dataset.step as string,
            }))
            .filter((k) => k.h > 0)
            .sort((p, q) => p.y - q.y);
        if (!ks.length) return;

        let a = ks[0];
        let b = ks[0];
        let e = 0;
        if (mid >= ks[ks.length - 1].y) a = b = ks[ks.length - 1];
        else if (mid > ks[0].y)
            for (let i = 0; i < ks.length - 1; i++)
                if (mid <= ks[i + 1].y) {
                    a = ks[i];
                    b = ks[i + 1];
                    const t = (mid - a.y) / Math.max(1, b.y - a.y);
                    const u = Math.min(1, Math.max(0, (t - 0.2) / 0.6)); // 20 % stojí, 60 % ide, 20 % stojí
                    e = u * u * (3 - 2 * u); // smoothstep
                    break;
                }

        const X = (k: Stop) => k.x * span + k.dx;
        const xa = X(a);
        const xb = X(b);
        const dist = Math.abs(xb - xa);
        const arc = reduce ? 0 : Math.sin(e * Math.PI);
        const x = xa + (xb - xa) * e;
        const rot = reduce ? 0 : a.r + (b.r - a.r) * e + Math.sign(xb - xa) * arc * Math.min(10, dist / 30);
        const sc = m.sc * (a.s + (b.s - a.s) * e) * (1 - 0.08 * arc * Math.min(1, dist / 200));
        const y = -(612 - m.ph) / 2 - arc * Math.min(34, dist / 8);
        // Mierku zastávky nesie `zoom` (ostré vykreslenie), transform len zvyšok počas presunu – v pokoji presne 1.
        // Zoom sa mení iba keď mobil stojí na zastávke, aby nebolo vidieť skok veľkosti.
        const zm = mv.querySelector<HTMLElement>('[data-lp-zoom]');
        if (zm && (arc < 0.02 || !zoom)) {
            const z = +(m.sc * (e < 0.5 ? a.s : b.s)).toFixed(3);
            if (z !== zoom) {
                zoom = z;
                zm.style.zoom = String(z);
                mv.style.transition = 'none';
                setTimeout(() => (mv.style.transition = ''));
            }
        }
        const rel = zoom ? sc / zoom : sc;
        mv.style.transform = `translate3d(${x.toFixed(1)}px,${y.toFixed(1)}px,0) rotate(${rot.toFixed(2)}deg) scale(${rel.toFixed(4)})`;

        // Trasa: tie isté body a easing ako mobil, bodky vedú presne pod jeho stredom.
        const P = ks.map((k) => ({ x: Math.round(left0 + X(k) + 150), y: Math.round(k.y) }));
        let d = `M${P[0].x} ${P[0].y}`;
        for (let i = 0; i < P.length - 1; i++) {
            const p = P[i];
            const q = P[i + 1];
            const y1 = p.y + (q.y - p.y) * 0.2;
            const y2 = p.y + (q.y - p.y) * 0.8;
            const c = (y2 - y1) / 3;
            d += ` L${p.x} ${y1.toFixed(1)} C${p.x} ${(y1 + c).toFixed(1)} ${q.x} ${(y2 - c).toFixed(1)} ${q.x} ${y2.toFixed(1)} L${q.x} ${q.y}`;
        }
        clip.value?.setAttribute('height', String(Math.max(0, Math.round(mid))));

        const nextDots = P.map((pt, i) => ({ x: pt.x, y: pt.y, on: mid >= ks[i].y - 2 }));
        const sig = d + '|' + nextDots.map((o) => (o.on ? 1 : 0)).join('');
        if (sig !== routeSig) {
            routeSig = sig;
            route.value = d;
            dots.value = nextDots;
        }
        const key = e < 0.5 ? a.k : b.k;
        if (key !== screen.value) screen.value = key;
    };

    const onScroll = () => {
        if (!raf) raf = requestAnimationFrame(move);
    };
    const onResize = () => {
        desk.value = window.innerWidth >= STORY_BREAKPOINT;
        metrics.value = storyMetrics(window.innerHeight);
        onScroll();
    };

    onMounted(() => {
        try {
            reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        } catch {
            reduce = false;
        }
        onResize();
        window.addEventListener('resize', onResize);
        window.addEventListener('scroll', onScroll, { capture: true, passive: true });
        // Po načítaní fontov a obrázkov sa výšky sekcií ešte menia.
        [400, 1200, 2500].forEach((ms) => late.push(setTimeout(onScroll, ms)));
    });
    onBeforeUnmount(() => {
        window.removeEventListener('resize', onResize);
        window.removeEventListener('scroll', onScroll, { capture: true });
        cancelAnimationFrame(raf);
        late.forEach(clearTimeout);
    });

    return { desk, metrics, screen, route, dots, refresh: onScroll };
}
