import { onBeforeUnmount, onMounted, type Ref } from 'vue';

/**
 * Scroll-reveal ([data-reveal], [data-stagger] children) and number count-up ([data-count="from|to|decimals|suffix"])
 * scoped to the given root element. Respects prefers-reduced-motion.
 */
export function useLandingMotion(root: Ref<HTMLElement | null>) {
    let io: IntersectionObserver | undefined;

    const fmt = (v: number, d: number, suf: string) => v.toFixed(d).replace('.', ',') + suf;

    const count = (el: HTMLElement) => {
        const [f, t, d, suf] = el.dataset.count!.split('|');
        const t0 = performance.now();
        const step = (now: number) => {
            const p = Math.min(1, (now - t0) / 1300);
            const e = 1 - Math.pow(1 - p, 3);
            el.textContent = fmt(+f + (+t - +f) * e, +d, suf);
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    const show = (el: HTMLElement, delay: number) => {
        el.style.transition = `opacity .75s cubic-bezier(.2,.7,.2,1) ${delay}s, transform .75s cubic-bezier(.2,.7,.2,1) ${delay}s`;
        el.style.opacity = '1';
        el.style.transform = '';
        setTimeout(() => (el.style.transition = ''), (delay + 0.8) * 1000);
        el.querySelectorAll<HTMLElement>('[data-count]').forEach((c) => setTimeout(() => count(c), delay * 1000 + 150));
    };

    const hide = (el: HTMLElement) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(28px)';
    };

    onMounted(() => {
        const el = root.value;
        if (!el || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        io = new IntersectionObserver(
            (entries) =>
                entries.forEach((e) => {
                    if (!e.isIntersecting) return;
                    io!.unobserve(e.target);
                    const t = e.target as HTMLElement;
                    if (t.dataset.stagger) [...t.children].forEach((c, i) => show(c as HTMLElement, i * 0.1));
                    else show(t, 0);
                }),
            { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
        );

        el.querySelectorAll<HTMLElement>('[data-count]').forEach((c) => {
            const [f, , d, suf] = c.dataset.count!.split('|');
            c.textContent = fmt(+f, +d, suf);
        });
        el.querySelectorAll<HTMLElement>('[data-reveal]').forEach((r) => {
            hide(r);
            io!.observe(r);
        });
        el.querySelectorAll<HTMLElement>('[data-stagger]').forEach((s) => {
            [...s.children].forEach((c) => hide(c as HTMLElement));
            io!.observe(s);
        });
    });

    onBeforeUnmount(() => io?.disconnect());
}
