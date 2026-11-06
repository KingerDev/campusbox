<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useClock } from '@/composables/useClock';
import CampusLogo from './CampusLogo.vue';

type Row = [name: string, meta: string, was: string, now: string, ph: string, base: number, decs: number[]];

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

const tick = ref(0);
const clock = useClock();
let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    timer = setInterval(() => tick.value++, 2400);
});
onBeforeUnmount(() => clearInterval(timer));

const isMenu = computed(() => Math.floor(tick.value / 6) % 2 === 1);
const loc = computed(() => tick.value % 6);

const cards = computed(() =>
    (isMenu.value ? MENU : ZACHRANA).map(([name, meta, was, now, ph, base, decs], i) => {
        const n = Math.max(1, base - decs.filter((x) => loc.value >= x).length);
        const hit = decs.includes(loc.value);
        return {
            name,
            meta,
            was,
            now,
            ph,
            delay: i * 0.12 + 's',
            tag: n === 1 ? 'Posledný kus' : (n < 5 ? 'Zostávajú ' : 'Zostáva ') + n,
            hit,
        };
    }),
);

const tabs = computed(() => [
    { l: 'Záchrana jedla', sub: 'Nepredané · až −70 %', on: !isMenu.value },
    { l: 'Študentské menu', sub: 'Obed · každý deň', on: isMenu.value },
]);

const showToast = computed(() => loc.value === 2 || loc.value === 3);
</script>

<template>
    <div class="box-border h-[612px] w-[300px] flex-none rounded-[48px] bg-[#0E0B09] p-[9px] shadow-[0_40px_80px_-30px_rgba(42,29,22,.55)]" aria-hidden="true">
        <div class="phone-screen relative box-border flex h-full w-full flex-col gap-2.5 overflow-hidden rounded-[40px] p-[14px] text-cb-ink">
            <div class="relative flex h-[26px] items-center justify-between px-3 text-xs font-bold">
                <span>{{ clock }}</span>
                <span class="absolute left-1/2 top-[2px] h-[26px] w-[92px] -translate-x-1/2 rounded-[14px] bg-black" />
                <span class="text-[10px]">5G</span>
            </div>

            <div class="flex items-end justify-between px-1 pt-1">
                <span class="flex flex-col gap-0.5">
                    <span class="text-[11px] font-semibold text-cb-taupe">Štvrtok · dnes večer</span>
                    <span class="text-[22px] font-extrabold tracking-[-.02em]">Ahoj, Gréta</span>
                </span>
                <CampusLogo :size="30" :wordmark="false" />
            </div>

            <div class="grid grid-cols-2 gap-1.5">
                <span
                    v-for="t in tabs"
                    :key="t.l"
                    class="flex flex-col gap-px rounded-2xl px-2.5 py-[9px] shadow-[inset_0_1px_0_rgba(255,255,255,.6)] transition-colors [transition-duration:400ms]"
                    :class="t.on ? 'bg-cb-red text-white' : 'bg-white/60 text-cb-ink'"
                >
                    <span class="whitespace-nowrap text-[11.5px] font-extrabold">{{ t.l }}</span>
                    <span class="whitespace-nowrap text-[9.5px] opacity-80">{{ t.sub }}</span>
                </span>
            </div>

            <div
                v-for="c in cards"
                :key="(isMenu ? 'm' : 'z') + c.name"
                class="cb-glass shrink-0 overflow-hidden rounded-[22px]"
                :style="{ animation: 'cbCard .55s cubic-bezier(.2,.8,.2,1) both', animationDelay: c.delay }"
            >
                <div class="relative h-[56px]" :style="{ background: c.ph }">
                    <span
                        class="absolute left-2.5 top-2.5 flex h-[22px] items-center rounded-[11px] px-2 text-[10.5px] font-bold transition-colors [transition-duration:350ms]"
                        :class="c.hit ? 'bg-cb-red text-white' : 'bg-white/85 text-cb-ink'"
                    >
                        {{ c.tag }}
                    </span>
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

            <div
                v-if="showToast"
                class="absolute bottom-[14px] left-3 right-3 flex items-center gap-2.5 rounded-[18px] bg-[rgba(42,29,22,.92)] px-3 py-2.5 text-cb-cream"
                style="animation: cbToast 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) both"
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
.phone-screen {
    background-color: #fff2e4;
    background-image:
        radial-gradient(60% 40% at 10% 5%, #ffc59a 0%, transparent 70%),
        radial-gradient(50% 35% at 95% 30%, #ffa894 0%, transparent 70%),
        radial-gradient(60% 40% at 15% 95%, #cfefd9 0%, transparent 70%);
}
</style>
