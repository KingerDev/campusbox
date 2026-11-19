import { onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

/** Breakpoint putujúceho mobilu – rovnaký v CSS (@media min-width: 980px) aj tu. */
export const STORY_BREAKPOINT = 980;
/** Pod touto šírkou (jeden stĺpec) putuje zmenšený mobil vedľa kariet. Rovnaký v CSS (max-width: 639.98px). */
export const MOBILE_BREAKPOINT = 640;
/** Šírka zmenšeného mobilu ako podiel vnútornej šírky stránky. Rovnaký podiel má CSS (.lp-step margin 36 %). */
export const MOBILE_PHONE_SHARE = 0.36;

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
    /** Úsek cesty k tejto zastávke (0–1), v ktorom sa mobil presúva; pred ním a po ňom stojí. */
    g0: number;
    g1: number;
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
    /** Mobilný režim: zmenšený mobil putuje vedľa kariet (karta na jednej strane, mobil na druhej). */
    const mob = ref(false);
    /** Mobil: odsadenie overlaya zhora, aby mobil začínal v hero pod textom a až potom sa prilepil. */
    const heroPad = ref(0);
    const metrics = ref<StoryMetrics>(storyMetrics(typeof window !== 'undefined' ? window.innerHeight : 800));
    const screen = ref('home');
    const route = ref('');
    const dots = ref<{ x: number; y: number; on: boolean }[]>([]);

    let raf = 0;
    let routeSig = '';
    let zoom = 0;
    let lastW = 0;
    const late: ReturnType<typeof setTimeout>[] = [];

    const move = () => {
        raf = 0;
        const mobile = window.innerWidth < MOBILE_BREAKPOINT;
        if (!mobile && window.innerWidth < STORY_BREAKPOINT) return;
        const st = story.value;
        const mv = mover.value;
        if (!st || !mv) return;
        const wrap = mv.closest('.lp-wrap') as HTMLElement | null;
        if (!wrap) return;

        const cs = getComputedStyle(wrap);
        const padL = parseFloat(cs.paddingLeft);
        const inner = wrap.clientWidth - padL - parseFloat(cs.paddingRight);
        const span = mobile ? inner - 300 : Math.max(0, inner - 300);
        const left0 = wrap.offsetLeft + padL;
        const m = metrics.value;
        const stTop = st.getBoundingClientRect().top;
        // Mobil: v hero ide mobil so stránkou (ešte nie je prilepený), preto sa stred berie zo skutočnej polohy.
        const stick = mobile ? mv.closest<HTMLElement>('[data-lp-stick]') : null;
        const mid = (stick ? stick.getBoundingClientRect().top : m.top) + m.ph / 2 - stTop;

        if (mobile) {
            const anchor = st.querySelector<HTMLElement>('[data-step-anchor]');
            const hp = anchor ? offsetTopIn(st, anchor) : 0;
            if (hp !== heroPad.value) {
                heroPad.value = hp;
                setTimeout(onScroll);
            }
        }
        // Mobil: zmenšený telefón stojí pri okraji na opačnej strane ako karta ([data-side]); hero a video sú v strede.
        const small = Math.max(0.3, (inner * MOBILE_PHONE_SHARE) / 300);
        const edge = (300 - 300 * small) / 2;

        const ks: Stop[] = [...st.querySelectorAll<HTMLElement>('[data-step]')]
            .map((el) => {
                const at = (mobile && el.querySelector<HTMLElement>('[data-step-anchor]')) || el;
                const side = el.dataset.side;
                const base = {
                    y: offsetTopIn(st, at) + at.offsetHeight / 2,
                    h: at.offsetHeight,
                    r: +(el.dataset.r || 0),
                    k: el.dataset.step as string,
                };
                // 20 % stojí, 60 % ide, 20 % stojí; na mobile si zastávka môže určiť vlastný úsek (data-mgo), aby mobil neprešiel cez text.
                const go = (mobile && el.dataset.mgo ? el.dataset.mgo : '.2,.8').split(',').map(Number);
                const all = { ...base, g0: go[0], g1: go[1] };
                if (!mobile) return { ...all, x: +(el.dataset.x || 1), dx: +(el.dataset.dx || 0), s: +(el.dataset.s || 1) };
                if (!side) return { ...all, x: 0.5, dx: 0, s: 1 };
                return { ...all, x: side === 'l' ? 1 : 0, dx: side === 'l' ? edge : -edge, s: small / m.sc };
            })
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
                    const u = Math.min(1, Math.max(0, (t - b.g0) / (b.g1 - b.g0)));
                    e = u * u * (3 - 2 * u); // smoothstep
                    break;
                }

        const X = (k: Stop) => k.x * span + k.dx;
        const xa = X(a);
        const xb = X(b);
        const dist = Math.abs(xb - xa);
        const arc = Math.sin(e * Math.PI);
        const x = xa + (xb - xa) * e;
        const rot = a.r + (b.r - a.r) * e + Math.sign(xb - xa) * arc * Math.min(10, dist / 30);
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
            const y1 = p.y + (q.y - p.y) * ks[i + 1].g0;
            const y2 = p.y + (q.y - p.y) * ks[i + 1].g1;
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
        const w = window.innerWidth;
        desk.value = w >= STORY_BREAKPOINT;
        mob.value = w < MOBILE_BREAKPOINT;
        // Na mobile sa výška okna mení pri schovaní lišty prehliadača – veľkosť telefónu vtedy nechávame, inak by pri scrolle skákal.
        if (!(mob.value && w === lastW)) metrics.value = storyMetrics(window.innerHeight);
        lastW = w;
        onScroll();
    };

    onMounted(() => {
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

    return { desk, mob, heroPad, metrics, screen, route, dots, refresh: onScroll };
}
