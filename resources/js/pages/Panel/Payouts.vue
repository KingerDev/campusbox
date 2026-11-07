<script setup lang="ts">
import { eur } from '@/composables/usePanel';
import PanelLayout from '@/layouts/PanelLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    month: string;
    monthLabel: string;
    prev: string;
    next: string | null;
    stats: { sales: number; commission: number; net: number; rescued: number; portions: number };
    bars: { day: number; qty: number; h: number; weekend: boolean }[];
    average: number;
    periods: { label: string; amount: number; status: string; tone: 'green' | 'orange' }[];
}>();
</script>

<template>
    <PanelLayout title="Výplaty a štatistiky" :sub="monthLabel">
        <template #actions>
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="route('panel.payouts', { mesiac: prev })"
                    class="flex h-11 w-11 items-center justify-center rounded-full border-[1.5px] border-cb-line bg-white font-bold"
                    aria-label="Predošlý mesiac"
                    preserve-scroll
                    >‹</Link
                >
                <Link
                    v-if="next"
                    :href="route('panel.payouts', { mesiac: next })"
                    class="flex h-11 w-11 items-center justify-center rounded-full border-[1.5px] border-cb-line bg-white font-bold"
                    aria-label="Ďalší mesiac"
                    preserve-scroll
                    >›</Link
                >
                <a
                    :href="route('panel.payouts.statement', { mesiac: month })"
                    target="_blank"
                    class="flex h-11 items-center rounded-full border-2 border-cb-ink px-4 text-sm font-bold text-cb-ink"
                    >Stiahnuť výpis (PDF)</a
                >
            </div>
        </template>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-2.5">
            <div class="pcard flex flex-col gap-1.5 rounded-[26px] p-[18px]">
                <span class="text-[13px] font-bold text-cb-taupe">Tržby</span
                ><span class="text-[30px] font-black tracking-[-.03em]">{{ eur(stats.sales) }}</span>
            </div>
            <div class="pcard flex flex-col gap-1.5 rounded-[26px] p-[18px]">
                <span class="text-[13px] font-bold text-cb-taupe">Provízia · 11 % záchrana, 6 % menu</span>
                <span class="text-[30px] font-black tracking-[-.03em] text-cb-taupe">−{{ eur(stats.commission) }}</span>
            </div>
            <div class="pcard flex flex-col gap-1.5 rounded-[26px] p-[18px]">
                <span class="text-[13px] font-bold text-cb-taupe">Po provízii</span
                ><span class="text-[30px] font-black tracking-[-.03em]">{{ eur(stats.net) }}</span>
            </div>
            <div class="flex flex-col gap-1.5 rounded-[26px] bg-[rgba(21,128,61,.12)] p-[18px] shadow-[inset_0_1px_0_rgba(255,255,255,.9)]">
                <span class="text-[13px] font-bold text-cb-green">Zachránené jedlá</span
                ><span class="text-[30px] font-black tracking-[-.03em] text-cb-green">{{ stats.rescued }}</span>
            </div>
        </div>

        <div class="pcard flex flex-col gap-3 p-5">
            <div class="flex flex-wrap justify-between gap-1">
                <span class="text-[17px] font-extrabold">Vydané porcie po dňoch</span>
                <span class="text-[13px] text-cb-taupe">{{ monthLabel.toLowerCase() }} · priemer {{ String(average).replace('.', ',') }} / deň</span>
            </div>
            <div class="grid h-[120px] items-end gap-[3px] border-b-2 border-cb-ink" :style="{ gridTemplateColumns: `repeat(${bars.length}, 1fr)` }">
                <div
                    v-for="b in bars"
                    :key="b.day"
                    class="rounded-t-[4px]"
                    :class="b.weekend ? 'bg-cb-beige' : 'bg-cb-green'"
                    :style="{ height: Math.max(b.qty ? 4 : 0, b.h) + '%' }"
                    :title="`${b.day}. · ${b.qty} porcií`"
                />
            </div>
            <div class="flex justify-between text-xs font-semibold text-cb-taupe">
                <span>1.</span><span>15.</span><span>{{ bars.length }}.</span>
            </div>
        </div>

        <div class="pcard px-[18px] py-1">
            <div
                v-for="(p, i) in periods"
                :key="p.label"
                class="flex flex-wrap justify-between gap-x-4 gap-y-1 py-3.5 text-[14.5px]"
                :class="i < periods.length - 1 && 'border-b-[1.5px] border-cb-line'"
            >
                <span class="font-semibold">{{ p.label }} · {{ eur(p.amount) }}</span>
                <span class="font-bold" :class="p.tone === 'green' ? 'text-cb-green' : 'text-cb-or-dark'">{{ p.status }}</span>
            </div>
        </div>
        <span class="text-[12.5px] leading-[1.45] text-cb-taupe"
            >Výplaty posielame každých 14 dní na IBAN z profilu podniku. Províziu účtujeme len z vydaných objednávok.</span
        >
    </PanelLayout>
</template>
