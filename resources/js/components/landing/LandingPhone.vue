<script setup lang="ts">
import { useClock } from '@/composables/useClock';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import CampusLogo from './CampusLogo.vue';

export type PhoneScreen = 'home' | 'zachrana' | 'menu' | 'overenie' | 'platba' | 'qr';

const props = withDefaults(defineProps<{ screen?: PhoneScreen; mode?: 'live' | 'preview' }>(), { screen: 'home', mode: 'live' });

const clock = useClock();
const now = ref(new Date());
const tick = ref(0);
const stage = ref(0); // 0 začiatok, 1 prebieha, 2 hotovo
const typed = ref(0);

const ISIC = 'S 421 012 345 678 A';
const live = computed(() => props.mode === 'live');

let tickTimer: ReturnType<typeof setInterval> | undefined;
let nowTimer: ReturnType<typeof setInterval> | undefined;
let typeTimer: ReturnType<typeof setInterval> | undefined;
let timeouts: ReturnType<typeof setTimeout>[] = [];

const clearStage = () => {
    timeouts.forEach(clearTimeout);
    timeouts = [];
    clearInterval(typeTimer);
};

/** Spustí časované animácie obrazovky odznova (overenie, platba, tlač lístka). */
const runStage = () => {
    clearStage();
    if (!live.value) {
        stage.value = 2;
        typed.value = ISIC.length;
        return;
    }
    stage.value = 0;
    typed.value = 0;
    const at = (ms: number, fn: () => void) => timeouts.push(setTimeout(fn, ms));
    if (props.screen === 'overenie') {
        const L = ISIC.length;
        at(450, () => {
            typeTimer = setInterval(() => {
                typed.value = Math.min(L, typed.value + 1);
                if (typed.value >= L) clearInterval(typeTimer);
            }, 55);
        });
        at(450 + L * 55 + 400, () => (stage.value = 1));
        at(450 + L * 55 + 1300, () => (stage.value = 2));
    } else if (props.screen === 'platba') {
        at(1300, () => (stage.value = 1));
        at(2400, () => (stage.value = 2));
    } else if (props.screen === 'qr') {
        at(1900, () => (stage.value = 2));
    } else {
        stage.value = 2;
    }
};

onMounted(() => {
    nowTimer = setInterval(() => (now.value = new Date()), 15000);
    if (live.value) tickTimer = setInterval(() => tick.value++, 2400);
    runStage();
});
watch(() => [props.screen, props.mode], runStage);
onBeforeUnmount(() => {
    clearInterval(tickTimer);
    clearInterval(nowTimer);
    clearStage();
});

// ---- texty a dáta ----
const DAYS = ['Nedeľa', 'Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota'];
const MONTHS = ['januára', 'februára', 'marca', 'apríla', 'mája', 'júna', 'júla', 'augusta', 'septembra', 'októbra', 'novembra', 'decembra'];
const day = computed(() => DAYS[now.value.getDay()]);
const dateLabel = computed(() => `${day.value} ${now.value.getDate()}. ${MONTHS[now.value.getMonth()]}`);

const isLock = computed(() => props.screen === 'home');
const isList = computed(() => props.screen === 'zachrana' || props.screen === 'menu');
const isMenu = computed(() => props.screen === 'menu');
const loc = computed(() => (live.value ? tick.value % 6 : 0));

const notifs = computed(() =>
    [
        ['teraz', 'Pekáreň Kvások · 4 balíčky', 'Balíček prekvapenia za 1,90 € namiesto 6,50 €. Vyzdvihni 18:30–19:00.', '1.5s'],
        ['pred 5 min', 'Obľúbené: Bistro Pod Urpínom', 'Zostávajú 3 večere za 3,50 € · 200 m od teba.', '.9s'],
        ['včera', 'Tento mesiac si ušetrila 23,60 €', 'A zachránila si 9 jedál. Len tak ďalej!', '.3s'],
    ].map(([t, h, b, d]) => ({ t, h, b, d: live.value ? d : '0s' })),
);

type Row = [string, string, string, string, string, number, number[]];
const ZACHRANA: Row[] = [
    ['Bistro Pod Urpínom', '19:30–20:00 · 200 m', '9,00 €', '3,50 €', '#F09A72', 3, [2]],
    ['Pekáreň Kvások', '18:30–19:00 · 350 m', '6,50 €', '1,90 €', '#F2C48D', 4, [4]],
    ['Kaviareň na Námestí', '17:30–18:00 · 1,1 km', '7,50 €', '2,90 €', '#D9B08C', 5, [1, 3, 5]],
];
const MENU: Row[] = [
    ['Bistro Pod Urpínom', 'Menu · 11:00–14:00 · 200 m', '7,90 €', '5,90 €', '#EFB08A', 15, [2, 4]],
    ['Zelená miska', 'Menu · 11:00–15:00 · 650 m', '7,50 €', '5,50 €', '#B9D7A1', 20, [1, 3, 5]],
    ['Lotos – ázijské bistro', 'Menu · 11:30–14:30 · 900 m', '8,50 €', '6,50 €', '#F0B65A', 12, [3]],
];
const cards = computed(() =>
    (isMenu.value ? MENU : ZACHRANA).map(([name, meta, was, nowPrice, ph, base, decs], i) => {
        const n = Math.max(1, base - decs.filter((x) => loc.value >= x).length);
        return {
            name,
            meta,
            was,
            now: nowPrice,
            ph,
            delay: i * 0.12 + 's',
            tag: n === 1 ? 'Posledný kus' : (n < 5 ? 'Zostávajú ' : 'Zostáva ') + n,
            hit: live.value && decs.includes(loc.value),
        };
    }),
);
const showToast = computed(() => isList.value && live.value && (loc.value === 2 || loc.value === 3));

const typedValue = computed(() => ISIC.slice(0, Math.min(ISIC.length, typed.value)));

const reached = computed(() => (stage.value >= 2 ? 3 : 2));
const qSteps = computed(() =>
    ['Zaplatené', 'Potvrdené', 'Pripravené', 'Vydané'].map((l, i) => ({
        l,
        c: i < reached.value ? '#15803D' : i === reached.value ? '#FF7A1A' : '#EAD6BE',
        tc: i < reached.value ? '#116B33' : i === reached.value ? '#A64B0C' : '#77604F',
    })),
);

/** Pseudo-QR 25×25 s tromi rohovými značkami (deterministický, ako v dizajne). */
const qr = (() => {
    const N = 25;
    const out: boolean[] = [];
    let seed = 4821;
    const rnd = () => ((seed = (seed * 9301 + 49297) % 233280), seed / 233280);
    const finder = (r: number, c: number) => {
        for (const [fr, fc] of [
            [0, 0],
            [0, N - 7],
            [N - 7, 0],
        ]) {
            const y = r - fr,
                x = c - fc;
            if (y >= 0 && y < 7 && x >= 0 && x < 7) return y === 0 || y === 6 || x === 0 || x === 6 || (y >= 2 && y <= 4 && x >= 2 && x <= 4) ? 1 : 0;
            if (y >= -1 && y <= 7 && x >= -1 && x <= 7) return 0;
        }
        return null;
    };
    for (let r = 0; r < N; r++)
        for (let c = 0; c < N; c++) {
            const f = finder(r, c);
            out.push(!!(f === null ? rnd() > 0.52 : f));
        }
    return out;
})();
</script>

<template>
    <div
        class="lm box-border h-[612px] w-[300px] flex-none rounded-[48px] bg-[#0E0B09] p-[9px] font-archivo text-cb-ink antialiased shadow-[0_40px_80px_-30px_rgba(42,29,22,.55)]"
        aria-hidden="true"
    >
        <div class="lm-screen relative box-border flex h-full w-full flex-col gap-3 overflow-hidden rounded-[40px] p-[14px]">
            <div class="relative flex h-[26px] flex-none items-center justify-between px-3 text-xs font-bold">
                <span>{{ isLock ? '' : clock }}</span>
                <span class="absolute left-1/2 top-[2px] h-[26px] w-[92px] -translate-x-1/2 rounded-[14px] bg-black" />
                <span class="text-[10px]">5G</span>
            </div>

            <!-- Zamknutá obrazovka s notifikáciami -->
            <div v-if="isLock" :key="'lock'" class="lm-in flex min-h-0 flex-1 flex-col items-center gap-1.5 pt-1">
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#5E4A3C"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect x="5" y="11" width="14" height="10" rx="2.5" />
                    <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                </svg>
                <div class="mt-0.5 text-[13px] font-semibold text-cb-brown">{{ dateLabel }}</div>
                <div class="text-[70px] font-extrabold leading-[.95] tracking-[-.045em] [font-variant-numeric:tabular-nums]">{{ clock }}</div>
                <div class="mt-4 flex w-full flex-col gap-[7px]">
                    <div
                        v-for="n in notifs"
                        :key="n.h"
                        class="lm-notif lm-glass grid grid-cols-[32px_minmax(0,1fr)] items-start gap-2.5 rounded-[20px] px-3 py-2.5"
                        :style="{ animationDelay: n.d }"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-[9px] bg-cb-cream shadow-[inset_0_0_0_1px_rgba(120,80,50,.14)]">
                            <CampusLogo :size="24" :wordmark="false" />
                        </div>
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <div class="flex justify-between gap-2 text-[9.5px] font-bold tracking-[.06em] text-cb-taupe">
                                <span>CAMPUSBOX</span><span class="font-semibold tracking-normal">{{ n.t }}</span>
                            </div>
                            <div class="text-[12.5px] font-extrabold leading-[1.25]">{{ n.h }}</div>
                            <div class="text-[11px] leading-[1.35] text-cb-brown">{{ n.b }}</div>
                        </div>
                    </div>
                </div>
                <div class="flex-1" />
                <div class="my-0.5 h-[5px] w-[110px] rounded-[3px] bg-cb-ink" />
            </div>

            <!-- Zoznam ponúk (záchrana / menu) -->
            <div v-else-if="isList" :key="'list'" class="lm-in flex min-h-0 flex-1 flex-col gap-3">
                <div class="flex items-end justify-between px-1 pt-1">
                    <span class="flex flex-col gap-0.5">
                        <span class="text-[11px] font-semibold text-cb-taupe">{{ day }}{{ isMenu ? ' · obed' : ' · dnes večer' }}</span>
                        <span class="text-[22px] font-extrabold tracking-[-.02em]">Ahoj, Gréta</span>
                    </span>
                    <CampusLogo :size="30" :wordmark="false" />
                </div>
                <div class="grid grid-cols-2 gap-1.5">
                    <span
                        v-for="t in [
                            { l: 'Záchrana jedla', sub: 'Nepredané · až −70 %', on: !isMenu },
                            { l: 'Študentské menu', sub: 'Obed · každý deň', on: isMenu },
                        ]"
                        :key="t.l"
                        class="flex flex-col gap-px rounded-2xl px-2.5 py-[9px] shadow-[inset_0_1px_0_rgba(255,255,255,.6)] transition-colors [transition-duration:400ms]"
                        :class="t.on ? 'bg-cb-red text-white' : 'bg-white/60 text-cb-ink'"
                    >
                        <span class="whitespace-nowrap text-[11.5px] font-extrabold">{{ t.l }}</span>
                        <span class="whitespace-nowrap text-[9.5px] opacity-85">{{ t.sub }}</span>
                    </span>
                </div>
                <div
                    v-for="c in cards"
                    :key="screen + c.name"
                    class="lm-card lm-glass shrink-0 overflow-hidden rounded-[22px]"
                    :style="{ animationDelay: c.delay }"
                >
                    <div class="relative h-[56px]" :style="{ background: c.ph }">
                        <span
                            class="absolute left-2.5 top-2.5 flex h-[22px] items-center rounded-[11px] px-2 text-[10.5px] font-bold transition-colors [transition-duration:350ms]"
                            :class="c.hit ? 'bg-cb-red text-white' : 'bg-white/85 text-cb-ink'"
                            >{{ c.tag }}</span
                        >
                    </div>
                    <div class="grid grid-cols-[1fr_auto] items-end gap-x-2 gap-y-0.5 px-3 pb-3 pt-2.5">
                        <span class="flex flex-col gap-0.5">
                            <span class="text-[13.5px] font-extrabold">{{ c.name }}</span>
                            <span class="text-[10.5px] font-semibold text-cb-taupe">{{ c.meta }}</span>
                        </span>
                        <span class="flex flex-col items-end">
                            <s class="text-[10px] text-cb-taupe">{{ c.was }}</s>
                            <span class="text-lg font-extrabold tracking-[-.02em] text-cb-green">{{ c.now }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Overenie študenta -->
            <div v-else-if="screen === 'overenie'" :key="'ver'" class="lm-in flex min-h-0 flex-1 flex-col gap-[11px] px-1 pb-1 pt-0.5">
                <div class="lm-back flex h-[34px] w-[34px] flex-none items-center justify-center rounded-full bg-white/70">
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </div>
                <div class="mt-1.5 text-[10.5px] font-bold uppercase tracking-[.07em] text-[#B8301B]">Len pre vysokoškolákov</div>
                <div class="-mt-1.5 text-[26px] font-extrabold leading-[1.02] tracking-[-.025em]">Over, že študuješ</div>
                <div class="text-[12.5px] leading-[1.45] text-cb-taupe">
                    Ceny v CampusBoxe sú len pre aktívnych študentov. Overenie zopakujeme raz za rok.
                </div>
                <div class="grid grid-cols-2 gap-1 rounded-2xl bg-white/70 p-[3px]">
                    <div class="flex h-[34px] items-center rounded-[13px] bg-cb-ink px-2.5 text-xs font-bold text-cb-cream">Preukaz ISIC</div>
                    <div class="flex h-[34px] items-center rounded-[13px] px-2.5 text-xs font-bold">Univerzitný e‑mail</div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <div class="text-[11.5px] font-bold">Číslo preukazu ISIC</div>
                    <div
                        class="box-border flex h-[46px] items-center rounded-[15px] border-2 bg-white px-[13px] text-sm transition-colors [font-variant-numeric:tabular-nums]"
                        :style="{ borderColor: stage >= 2 ? '#15803D' : typed > 0 ? '#2A1D16' : '#EAD6BE' }"
                    >
                        <span class="whitespace-pre">{{ typedValue }}</span>
                        <span v-if="typed === 0" class="text-[#846B5A]">{{ ISIC }}</span>
                        <span v-if="stage === 0 && typed < ISIC.length" class="lm-caret ml-px h-[18px] w-0.5 bg-cb-red" />
                    </div>
                </div>
                <div v-if="stage >= 2" class="lm-in flex items-center gap-[9px] rounded-[15px] bg-cb-mint px-3 py-2.5 text-[#116B33]">
                    <div class="lm-pop flex h-6 w-6 flex-none items-center justify-center rounded-full bg-cb-green text-[13px] font-black text-white">
                        ✓
                    </div>
                    <div class="flex flex-col gap-px">
                        <span class="text-[12.5px] font-extrabold">Overené</span>
                        <span class="text-[11px] font-semibold">Študentka UMB · do 28. 2. 2027</span>
                    </div>
                </div>
                <div v-else class="text-[11.5px] leading-[1.45] text-cb-taupe">Číslo nájdeš na preukaze. Platnosť overíme v databáze ISIC.</div>
                <div class="flex-1" />
                <div
                    class="flex h-12 flex-none items-center justify-between rounded-2xl px-4 text-[14.5px] font-bold text-white transition-colors [transition-duration:350ms]"
                    :class="stage >= 2 ? 'bg-cb-green' : 'bg-cb-red'"
                >
                    <span class="flex items-center gap-2"
                        ><span v-if="stage === 1" class="lm-spin" />{{
                            stage >= 2 ? 'Pokračovať' : stage === 1 ? 'Overujem…' : 'Overiť preukaz'
                        }}</span
                    ><span>→</span>
                </div>
            </div>

            <!-- Platba -->
            <div v-else-if="screen === 'platba'" :key="'pay'" class="lm-in flex min-h-0 flex-1 flex-col gap-2.5 px-0.5 pb-1 pt-0.5">
                <div class="flex items-center gap-2.5">
                    <div class="lm-back flex h-[34px] w-[34px] flex-none items-center justify-center rounded-full bg-white/70">
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                    </div>
                    <span class="text-[19px] font-extrabold">Platba</span>
                </div>
                <div class="lm-glass flex flex-col gap-[9px] rounded-[20px] !bg-white/70 p-[13px]">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[14.5px] font-extrabold">Bistro Pod Urpínom</span>
                        <span class="text-[11px] font-semibold text-cb-taupe">Dnes 19:30–20:00 · vyzdvihnutie</span>
                    </div>
                    <div class="grid grid-cols-[22px_1fr_auto] gap-1.5 border-t-2 border-[#EAD6BE] pt-[9px] text-[12.5px]">
                        <b>1×</b><span>Balíček prekvapenia</span><b>3,50 €</b>
                    </div>
                    <div class="flex justify-between text-xs text-cb-taupe"><span>Pôvodná hodnota</span><s>9,00 €</s></div>
                    <div class="flex justify-between text-xs font-bold text-[#116B33]"><span>Ušetríš</span><span>5,50 €</span></div>
                    <div class="flex items-baseline justify-between border-t-2 border-[#EAD6BE] pt-[9px]">
                        <span class="text-sm font-extrabold">Spolu</span><span class="text-[22px] font-extrabold tracking-[-.03em]">3,50 €</span>
                    </div>
                </div>
                <div class="px-0.5 pt-0.5 text-[13.5px] font-extrabold">Spôsob platby</div>
                <div
                    v-for="m in [
                        { l: 'Apple Pay', sub: 'Rýchla platba telefónom', ico: 'Pay', ibg: '#000', on: true },
                        { l: 'Visa •••• 4821', sub: 'Platnosť 09/28', ico: 'VISA', ibg: '#1A1F71', on: false },
                    ]"
                    :key="m.l"
                    class="grid grid-cols-[40px_1fr_18px] items-center gap-2.5 rounded-2xl border-2 bg-white/70 p-2.5"
                    :style="{ borderColor: m.on ? '#DB3B22' : '#EAD6BE' }"
                >
                    <span
                        class="flex h-7 w-10 items-center justify-center rounded-[7px] text-[9.5px] font-extrabold text-white"
                        :style="{ background: m.ibg }"
                        >{{ m.ico }}</span
                    >
                    <span class="flex flex-col gap-px"
                        ><span class="text-[12.5px] font-bold">{{ m.l }}</span
                        ><span class="text-[10.5px] text-cb-taupe">{{ m.sub }}</span></span
                    >
                    <span
                        class="box-border flex h-[18px] w-[18px] items-center justify-center rounded-full border-2"
                        :style="{ borderColor: m.on ? '#DB3B22' : '#EAD6BE' }"
                    >
                        <span class="h-2 w-2 rounded-full" :style="{ background: m.on ? '#DB3B22' : 'transparent' }" />
                    </span>
                </div>
                <div class="flex-1" />
                <div
                    class="flex h-12 flex-none items-center justify-between rounded-2xl px-4 text-sm font-bold text-white transition-colors [transition-duration:350ms]"
                    :class="stage >= 2 ? 'bg-cb-green' : 'bg-[#0E0B09]'"
                >
                    <span class="flex items-center gap-2"
                        ><span v-if="stage === 1" class="lm-spin" />{{
                            stage >= 2 ? 'Zaplatené ✓' : stage === 1 ? 'Potvrdzujem…' : 'Zaplatiť cez Apple Pay'
                        }}</span
                    ><span>3,50 €</span>
                </div>
            </div>

            <!-- Lístok s QR kódom -->
            <div v-else-if="screen === 'qr'" :key="'qr'" class="lm-in flex min-h-0 flex-1 flex-col gap-2.5 px-0.5 pt-0.5">
                <div class="flex items-center justify-between">
                    <span class="text-[19px] font-extrabold">Tvoja rezervácia</span><span class="text-xs font-bold text-cb-taupe">Zavrieť</span>
                </div>
                <div class="grid grid-cols-4 gap-[5px]">
                    <div v-for="q in qSteps" :key="q.l" class="flex flex-col gap-[5px]">
                        <div class="h-1 rounded-sm transition-colors [transition-duration:400ms]" :style="{ background: q.c }" />
                        <span class="text-[9.5px] font-bold transition-colors [transition-duration:400ms]" :style="{ color: q.tc }">{{ q.l }}</span>
                    </div>
                </div>
                <div class="mt-1 flex flex-none flex-col">
                    <div
                        class="lm-printer relative z-[2] box-border flex h-[46px] flex-col justify-between rounded-[16px_16px_10px_10px] px-[13px] pt-[9px]"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="h-[7px] w-[7px] rounded-full transition-colors [transition-duration:400ms]"
                                    :style="{
                                        background: stage >= 2 ? '#4ADE80' : '#FBBF24',
                                        boxShadow: `0 0 7px ${stage >= 2 ? '#4ADE80' : '#FBBF24'}`,
                                    }"
                                />
                                <span class="text-[8.5px] font-bold uppercase tracking-[.14em] text-[rgba(255,246,234,.6)]">campusbox · tlač</span>
                            </div>
                            <div class="flex gap-[3px]">
                                <span class="h-[5px] w-3.5 rounded-[3px] bg-white/[.12]" /><span class="h-[5px] w-3.5 rounded-[3px] bg-white/[.12]" />
                            </div>
                        </div>
                        <div
                            class="-mx-[3px] h-2.5 rounded-t-[5px] bg-[#0C0907] shadow-[inset_0_3px_3px_rgba(0,0,0,.8),0_-1px_0_rgba(255,255,255,.08)]"
                        />
                    </div>
                </div>
                <div class="mx-[11px] -mt-3.5 flex-none overflow-hidden pb-1.5">
                    <div class="lm-ticket lm-print flex flex-col bg-[#FFFDF8] pb-2.5 pt-1 text-cb-ink">
                        <div class="flex flex-col gap-0.5 px-[15px] pb-2.5 pt-3">
                            <span class="text-[10.5px] font-bold text-cb-taupe">Bistro Pod Urpínom · 200 m</span>
                            <span
                                class="text-2xl font-black leading-[1.05] tracking-[-.03em] transition-colors [transition-duration:400ms]"
                                :class="stage >= 2 ? 'text-cb-green' : 'text-cb-ink'"
                            >
                                {{ stage >= 2 ? 'Pripravené!' : 'Hotové o 19:35' }}
                            </span>
                            <span class="text-[11px] font-semibold">{{
                                stage >= 2 ? 'Môžeš ísť po jedlo · výdaj do 20:00' : 'Potvrdené podnikom · výdaj 19:30–20:00'
                            }}</span>
                        </div>
                        <div class="mx-[15px] h-0 border-t-[3px] border-dashed border-[#EAD6BE]" />
                        <div class="flex flex-col items-center gap-1.5 px-[15px] pb-1.5 pt-3">
                            <div class="grid h-[140px] w-[140px] grid-cols-[repeat(25,minmax(0,1fr))]">
                                <span v-for="(on, i) in qr" :key="i" :class="on ? 'bg-[#1d1410]' : ''" />
                            </div>
                            <span class="font-mono text-[17px] font-extrabold tracking-[.12em]">KV‑4821</span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="showToast"
                class="lm-toast absolute bottom-[14px] left-3 right-3 flex items-center gap-2.5 rounded-[18px] bg-[rgba(42,29,22,.92)] px-3 py-2.5 text-cb-cream"
            >
                <span class="flex h-7 w-7 flex-none items-center justify-center rounded-full bg-cb-green text-sm font-black">✓</span>
                <span class="flex flex-col gap-px">
                    <span class="text-xs font-extrabold">{{ isMenu ? 'Práve rezervované · Menu B' : 'Niekto práve zachránil balíček' }}</span>
                    <span class="text-[10.5px] text-cb-beige">{{ isMenu ? 'Bistro Pod Urpínom · 12:10' : 'Bistro Pod Urpínom · −61 %' }}</span>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.lm-screen {
    background-color: #fff2e4;
    background-image:
        radial-gradient(60% 40% at 10% 5%, #ffc59a 0%, transparent 70%), radial-gradient(50% 35% at 95% 30%, #ffa894 0%, transparent 70%),
        radial-gradient(60% 40% at 15% 95%, #cfefd9 0%, transparent 70%);
}
.lm-glass {
    background: rgba(255, 255, 255, 0.62);
    backdrop-filter: blur(22px) saturate(170%);
    -webkit-backdrop-filter: blur(22px) saturate(170%);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.9),
        0 18px 40px -22px rgba(60, 25, 5, 0.4);
}
.lm-back {
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.9),
        0 10px 24px -14px rgba(60, 25, 5, 0.35);
}
.lm-printer {
    background: linear-gradient(180deg, #4a3a31 0%, #2e231d 55%, #211915 100%);
    box-shadow:
        inset 0 2px 0 rgba(255, 255, 255, 0.14),
        inset 0 -2px 0 rgba(0, 0, 0, 0.4),
        0 14px 24px -12px rgba(42, 29, 22, 0.6);
}
.lm-ticket {
    -webkit-mask: conic-gradient(from -45deg at bottom, #0000, #000 1deg 89deg, #0000 90deg) 50% / 14px 100%;
    mask: conic-gradient(from -45deg at bottom, #0000, #000 1deg 89deg, #0000 90deg) 50% / 14px 100%;
}
.lm-spin {
    width: 14px;
    height: 14px;
    border-radius: 7px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    box-sizing: border-box;
    animation: lmSpin 0.8s linear infinite;
}
.lm-in {
    animation: lmIn 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}
.lm-card {
    animation: lmCard 0.55s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}
.lm-notif {
    animation: lmNotif 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}
.lm-toast {
    animation: lmToast 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}
.lm-print {
    animation: lmPrint 1.6s cubic-bezier(0.25, 0.8, 0.3, 1) 0.15s both;
}
.lm-pop {
    animation: lmPop 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}
.lm-caret {
    animation: lmCaret 1s steps(1) infinite;
}
@keyframes lmIn {
    from {
        opacity: 0;
        transform: translateY(14px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}
@keyframes lmCard {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: none;
    }
}
@keyframes lmToast {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}
@keyframes lmPrint {
    from {
        transform: translateY(-100%);
    }
    to {
        transform: none;
    }
}
@keyframes lmPop {
    0% {
        transform: scale(0.4);
        opacity: 0;
    }
    70% {
        transform: scale(1.12);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
@keyframes lmCaret {
    0%,
    49% {
        opacity: 1;
    }
    50%,
    100% {
        opacity: 0;
    }
}
@keyframes lmSpin {
    to {
        transform: rotate(360deg);
    }
}
@keyframes lmNotif {
    from {
        opacity: 0;
        transform: translateY(-14px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: none;
    }
}
</style>
