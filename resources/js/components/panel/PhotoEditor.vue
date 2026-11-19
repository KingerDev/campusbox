<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

/**
 * Úprava fotky pred nahratím: orez na pevný pomer (posun ťahaním, priblíženie), jemný filter
 * a náhľad, ako bude vyzerať v appke. Výsledok je vždy JPEG v pevnej veľkosti, takže aj veľká fotka z mobilu
 * sa nahrá rýchlo a zmestí sa do limitu.
 *
 * kind: „dish“ fotka jedla (4:3), „cover“ titulná fotka podniku (2:1), „logo“ logo podniku (štvorec, zobrazuje sa v kruhu).
 */
type Kind = 'dish' | 'cover' | 'logo';
const props = withDefaults(
    defineProps<{
        src: string;
        kind?: Kind;
        /** Jedlo: názov a cena. Podnik: názov, typ a druhý obrázok (pri úprave titulky logo a naopak). */
        name?: string;
        price?: string;
        type?: string;
        cover?: string | null;
        logo?: string | null;
        busy?: boolean;
    }>(),
    { kind: 'dish', name: '', price: '', type: '', cover: null, logo: null, busy: false },
);
const emit = defineEmits<{ apply: [file: File, url: string]; cancel: [] }>();

// ew/eh = plátno editora, ow/oh = uložená veľkosť.
const KINDS: Record<Kind, { title: string; hint: string; ew: number; eh: number; ow: number; oh: number; file: string }> = {
    dish: { title: 'Upraviť fotku', hint: 'V appke sa zobrazuje v pomere 4 : 3.', ew: 640, eh: 480, ow: 1200, oh: 900, file: 'jedlo.jpg' },
    cover: {
        title: 'Upraviť titulnú fotku',
        hint: 'Titulka je široký pás v pomere 2 : 1.',
        ew: 640,
        eh: 320,
        ow: 1600,
        oh: 800,
        file: 'titulka.jpg',
    },
    logo: {
        title: 'Upraviť logo',
        hint: 'Logo sa zobrazuje v kruhu – dôležité veci držte v strede.',
        ew: 480,
        eh: 480,
        ow: 600,
        oh: 600,
        file: 'logo.jpg',
    },
};
const cfg = KINDS[props.kind];
const RATIO = cfg.ew / cfg.eh;

/** Filter = jas, kontrast, sýtosť a teplota farieb. Počíta sa po pixeloch, aby náhľad a výsledok boli totožné vo všetkých prehliadačoch. */
interface Look {
    key: string;
    label: string;
    b: number;
    c: number;
    s: number;
    w: number;
}
const LOOKS: Look[] = [
    { key: 'none', label: 'Pôvodná', b: 1, c: 1, s: 1, w: 0 },
    { key: 'bright', label: 'Jasnejšia', b: 1.1, c: 1.04, s: 1.05, w: 0 },
    { key: 'warm', label: 'Teplejšia', b: 1.03, c: 1.04, s: 1.1, w: 0.07 },
    { key: 'vivid', label: 'Sýtejšia', b: 1.02, c: 1.1, s: 1.28, w: 0.02 },
];

const editor = ref<HTMLCanvasElement | null>(null);
const card = ref<HTMLCanvasElement | null>(null);
const thumb = ref<HTMLCanvasElement | null>(null);
const look = ref<Look>(LOOKS[0]);
const zoom = ref(1);
const ready = ref(false);
const failed = ref(false);
const saving = ref(false);

let img: HTMLImageElement | null = null;
// Stred výrezu v súradniciach pôvodnej fotky.
let cx = 0;
let cy = 0;
let raf = 0;

const crop = () => {
    const w = img!.naturalWidth;
    const h = img!.naturalHeight;
    const cw = Math.min(w, h * RATIO) / zoom.value;
    const ch = cw / RATIO;
    cx = Math.min(w - cw / 2, Math.max(cw / 2, cx));
    cy = Math.min(h - ch / 2, Math.max(ch / 2, cy));

    return { x: cx - cw / 2, y: cy - ch / 2, w: cw, h: ch };
};

const applyLook = (ctx: CanvasRenderingContext2D, w: number, h: number, l: Look) => {
    if (l.key === 'none') return;
    const data = ctx.getImageData(0, 0, w, h);
    const p = data.data;
    for (let i = 0; i < p.length; i += 4) {
        let r = p[i] * (1 + l.w);
        let g = p[i + 1];
        let b = p[i + 2] * (1 - l.w);
        const gray = 0.299 * r + 0.587 * g + 0.114 * b;
        r = ((gray + (r - gray) * l.s) * l.b - 128) * l.c + 128;
        g = ((gray + (g - gray) * l.s) * l.b - 128) * l.c + 128;
        b = ((gray + (b - gray) * l.s) * l.b - 128) * l.c + 128;
        p[i] = r;
        p[i + 1] = g;
        p[i + 2] = b; // Uint8ClampedArray hodnoty sám oreže na 0–255
    }
    ctx.putImageData(data, 0, 0);
};

const render = (canvas: HTMLCanvasElement, w: number, h: number) => {
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d', { willReadFrequently: true })!;
    const c = crop();
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(img!, c.x, c.y, c.w, c.h, 0, 0, w, h);
    applyLook(ctx, w, h, look.value);
};

const draw = () => {
    raf = 0;
    if (!img || !editor.value) return;
    render(editor.value, cfg.ew, cfg.eh);
    // Náhľady v appke sú kópie editora – presne to, čo sa uloží.
    const copy = (el: HTMLCanvasElement | null, w: number, h: number, square = false) => {
        if (!el || !editor.value) return;
        el.width = w;
        el.height = h;
        const ctx = el.getContext('2d')!;
        ctx.imageSmoothingQuality = 'high';
        // Okrúhla miniatúra berie stredný štvorec výrezu.
        if (square) ctx.drawImage(editor.value, (cfg.ew - cfg.eh) / 2, 0, cfg.eh, cfg.eh, 0, 0, w, h);
        else ctx.drawImage(editor.value, 0, 0, w, h);
    };
    copy(card.value, props.kind === 'logo' ? 120 : 320, props.kind === 'logo' ? 120 : Math.round(320 / RATIO));
    copy(thumb.value, 96, 96, true);
};
const schedule = () => {
    if (!raf) raf = requestAnimationFrame(draw);
};
watch([zoom, look], schedule);

// ---- posun ťahaním ----
let drag: { x: number; y: number } | null = null;
const onDown = (e: PointerEvent) => {
    drag = { x: e.clientX, y: e.clientY };
    (e.currentTarget as HTMLElement).setPointerCapture(e.pointerId);
};
const onMove = (e: PointerEvent) => {
    if (!drag || !img || !editor.value) return;
    const scale = crop().w / editor.value.getBoundingClientRect().width;
    cx -= (e.clientX - drag.x) * scale;
    cy -= (e.clientY - drag.y) * scale;
    drag = { x: e.clientX, y: e.clientY };
    schedule();
};
const onUp = () => (drag = null);
const onWheel = (e: WheelEvent) => (zoom.value = Math.min(3, Math.max(1, +(zoom.value - e.deltaY * 0.002).toFixed(2))));
const onKey = (e: KeyboardEvent) => {
    if (!img) return;
    const step = crop().w * 0.04;
    const move: Record<string, [number, number]> = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] };
    if (!move[e.key]) return;
    e.preventDefault();
    cx += move[e.key][0];
    cy += move[e.key][1];
    schedule();
};

const apply = () => {
    if (!img || saving.value || props.busy) return;
    saving.value = true;
    const out = document.createElement('canvas');
    render(out, cfg.ow, cfg.oh);
    out.toBlob(
        (blob) => {
            saving.value = false;
            if (!blob) return (failed.value = true);
            emit('apply', new File([blob], cfg.file, { type: 'image/jpeg' }), URL.createObjectURL(blob));
        },
        'image/jpeg',
        0.88,
    );
};

onMounted(() => {
    const el = new Image();
    el.onload = () => {
        img = el;
        cx = el.naturalWidth / 2;
        cy = el.naturalHeight / 2;
        ready.value = true;
        nextTick(draw);
    };
    el.onerror = () => (failed.value = true);
    el.src = props.src;
});
onBeforeUnmount(() => cancelAnimationFrame(raf));
</script>

<template>
    <div class="fixed inset-0 z-[70] box-border flex items-end justify-center bg-[rgba(42,29,22,.5)] p-3 backdrop-blur-[6px] sm:items-center sm:p-6">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="photo-title"
            class="box-border flex max-h-full w-full max-w-[860px] flex-col gap-4 overflow-y-auto rounded-[30px] bg-[#FFF6EA] p-[22px]"
        >
            <div class="flex items-center justify-between gap-3">
                <span id="photo-title" class="text-2xl font-black tracking-[-.02em]">{{ cfg.title }}</span>
                <button
                    type="button"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-lg font-bold"
                    aria-label="Zavrieť"
                    @click="emit('cancel')"
                >
                    ✕
                </button>
            </div>

            <div v-if="failed" class="rounded-2xl bg-[rgba(219,59,34,.12)] px-4 py-3 text-sm font-bold text-cb-red" role="alert">
                Fotku sa nepodarilo načítať. Skúste iný súbor (JPG alebo PNG).
            </div>

            <div v-else class="grid gap-5 md:grid-cols-[minmax(0,1fr)_240px]">
                <div class="flex min-w-0 flex-col gap-3">
                    <div class="relative overflow-hidden rounded-[22px] bg-[#EADBC8]" :class="kind === 'logo' && 'mx-auto w-full max-w-[340px]'">
                        <canvas
                            ref="editor"
                            tabindex="0"
                            aria-label="Výrez fotky. Posúvajte ťahaním alebo šípkami."
                            class="block w-full cursor-grab touch-none outline-none focus-visible:ring-2 focus-visible:ring-cb-ink active:cursor-grabbing"
                            :style="{ aspectRatio: `${cfg.ew} / ${cfg.eh}` }"
                            @pointerdown="onDown"
                            @pointermove="onMove"
                            @pointerup="onUp"
                            @pointercancel="onUp"
                            @wheel.prevent="onWheel"
                            @keydown="onKey"
                        />
                        <!-- Logo: stmavené rohy ukazujú, čo kruh v appke odreže. -->
                        <span
                            v-if="kind === 'logo'"
                            class="pointer-events-none absolute inset-0 rounded-full shadow-[0_0_0_999px_rgba(42,29,22,.45)]"
                            aria-hidden="true"
                        />
                        <span v-if="!ready" class="absolute inset-0 flex items-center justify-center text-sm font-bold text-cb-taupe"
                            >Načítavam…</span
                        >
                    </div>
                    <span class="text-[12.5px] text-cb-taupe">Fotku posuniete ťahaním. {{ cfg.hint }}</span>

                    <label class="flex items-center gap-3">
                        <span class="plabel flex-none">Priblíženie</span>
                        <input v-model.number="zoom" type="range" min="1" max="3" step="0.01" class="h-2 w-full accent-[#DB3B22]" />
                    </label>

                    <div class="flex flex-col gap-2">
                        <span class="plabel">Filter</span>
                        <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="Filter">
                            <button
                                v-for="l in LOOKS"
                                :key="l.key"
                                type="button"
                                role="radio"
                                :aria-checked="look.key === l.key"
                                class="flex h-[38px] items-center rounded-full border-[1.5px] px-3.5 text-[13.5px] font-bold"
                                :class="look.key === l.key ? 'border-cb-ink bg-cb-ink text-cb-cream' : 'border-cb-line bg-white'"
                                @click="look = l"
                            >
                                {{ l.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <span class="plabel">Náhľad v appke</span>
                    <template v-if="kind === 'dish'">
                        <!-- Karta ponuky -->
                        <div class="overflow-hidden rounded-[22px] bg-white shadow-[0_14px_30px_-18px_rgba(60,25,5,.45)]">
                            <div class="relative">
                                <canvas ref="card" class="block aspect-[4/3] w-full" />
                                <span class="absolute left-2.5 top-2.5 rounded-full bg-cb-red px-2 py-0.5 text-[11px] font-extrabold text-white"
                                    >Zostáva 3</span
                                >
                            </div>
                            <div class="flex flex-col gap-0.5 px-3.5 pb-3.5 pt-2.5">
                                <span class="truncate text-[15px] font-extrabold">{{ name || 'Názov jedla' }}</span>
                                <span class="text-xs text-cb-taupe">Dnes 19:30–20:00 · 300 m</span>
                                <span class="mt-1 text-[17px] font-black text-cb-green">{{ price ? price + ' €' : '– €' }}</span>
                            </div>
                        </div>
                        <!-- Riadok v zozname -->
                        <div class="flex items-center gap-3 rounded-[18px] bg-white px-3 py-2.5 shadow-[0_14px_30px_-18px_rgba(60,25,5,.45)]">
                            <canvas ref="thumb" class="h-12 w-12 flex-none rounded-full" />
                            <span class="flex min-w-0 flex-col">
                                <span class="truncate text-sm font-extrabold">{{ name || 'Názov jedla' }}</span>
                                <span class="text-xs text-cb-taupe">v zozname a v košíku</span>
                            </span>
                        </div>
                    </template>

                    <!-- Podnik: karta v appke s titulkou a logom -->
                    <div v-else class="overflow-hidden rounded-[22px] bg-white shadow-[0_14px_30px_-18px_rgba(60,25,5,.45)]">
                        <div class="relative bg-[#F09A72]" :style="{ aspectRatio: '2 / 1' }">
                            <canvas v-if="kind === 'cover'" ref="card" class="block h-full w-full" />
                            <img v-else-if="cover" :src="cover" alt="" class="h-full w-full object-cover" />
                            <div
                                class="absolute -bottom-[22px] left-3.5 box-border h-12 w-12 overflow-hidden rounded-full border-[3px] border-white bg-cb-ink"
                            >
                                <canvas v-if="kind === 'logo'" ref="card" class="block h-full w-full" />
                                <img v-else-if="logo" :src="logo" alt="" class="h-full w-full object-cover" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-0.5 px-3.5 pb-3.5 pt-[30px]">
                            <span v-if="type" class="text-[11px] font-bold uppercase tracking-[.05em] text-cb-red">{{ type }}</span>
                            <span class="truncate text-[15px] font-extrabold">{{ name || 'Názov podniku' }}</span>
                            <span class="text-xs text-cb-taupe">Dnes otvorené · 300 m</span>
                        </div>
                    </div>
                    <span class="text-[12px] leading-[1.4] text-cb-taupe">Náhľad je orientačný – ukazuje výrez a farby, ktoré sa uložia.</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2.5">
                <button type="button" class="pbtn pbtn-red flex-[1_1_200px]" :disabled="!ready || saving || busy || failed" @click="apply">
                    <span>{{ busy ? 'Nahrávam…' : saving ? 'Pripravujem…' : kind === 'dish' ? 'Použiť fotku' : 'Uložiť fotku' }}</span
                    ><span>→</span>
                </button>
                <button type="button" class="pbtn pbtn-ghost" @click="emit('cancel')">Zrušiť</button>
            </div>
        </div>
    </div>
</template>
