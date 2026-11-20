import { onBeforeUnmount, onMounted, type Ref } from 'vue';

/**
 * Nekonečné animácie vnútri SVG ilustrácií beží prehliadač na hlavnom vlákne a prekresľuje ich pri každom snímku,
 * aj keď ilustráciu nevidno. Pri scrolle potom nestíha putujúci mobil. Mimo obrazovky ich preto pozastavíme
 * (trieda `cb-paused`, pravidlo v app.css).
 */
export function usePauseOffscreen(root: Ref<HTMLElement | null>) {
    let io: IntersectionObserver | null = null;
    let mo: MutationObserver | null = null;
    const seen = new WeakSet<Element>();

    const scan = () => {
        root.value?.querySelectorAll('svg').forEach((svg) => {
            if (seen.has(svg) || !svg.querySelector('[style*="animation"]')) return;
            seen.add(svg);
            svg.classList.add('cb-paused');
            io?.observe(svg);
        });
    };

    onMounted(() => {
        if (!root.value || typeof IntersectionObserver === 'undefined') return;
        io = new IntersectionObserver((entries) => entries.forEach((e) => e.target.classList.toggle('cb-paused', !e.isIntersecting)), {
            rootMargin: '80px 0px',
        });
        scan();
        // Ilustrácie sa menia s rozložením (desktop / mobil), nové treba začať sledovať.
        mo = new MutationObserver(scan);
        mo.observe(root.value, { childList: true, subtree: true });
    });
    onBeforeUnmount(() => {
        io?.disconnect();
        mo?.disconnect();
    });
}
