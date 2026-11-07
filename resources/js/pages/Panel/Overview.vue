<script setup lang="ts">
import StatusPill from '@/components/panel/StatusPill.vue';
import { useOrderActions } from '@/composables/useOrderActions';
import { eur, fulfilmentLabel, usePanel, type PanelOrder } from '@/composables/usePanel';
import PanelLayout from '@/layouts/PanelLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    dateLabel: string;
    kpi: { offered: number; reserved: number; issued: number; waiting: number; free: number; sales: number | null };
    reliability: { rate: number; reported: number; expired: number } | null;
    active: PanelOrder[];
    offer: { name: string; count: number; price: number | null }[];
    onboarding: { dish: boolean; offer: boolean; issue: boolean } | null;
}>();

const { panel, can } = usePanel();
const { actionFor } = useOrderActions();

const total = computed(() => Math.max(1, props.kpi.offered, props.kpi.reserved));
const pIssued = computed(() => (props.kpi.issued / total.value) * 100);
const pWaiting = computed(() => (props.kpi.waiting / total.value) * 100);

const onbSteps = computed(() =>
    props.onboarding
        ? [
              { done: props.onboarding.dish, t: 'Pridajte jedlá do menu', d: 'Názov, cena, alergény a možnosti výberu.', href: route('panel.menu') },
              { done: props.onboarding.offer, t: 'Zverejnite dnešnú ponuku', d: 'Vyberte jedlá, porcie a čas výdaja.', href: route('panel.offer') },
              { done: props.onboarding.issue, t: 'Vyskúšajte výdaj', d: 'Takto naskenujete QR kód študenta.', href: route('panel.issue') },
          ]
        : [],
);
const onbDone = computed(() => onbSteps.value.filter((s) => s.done).length);
const dismissOnb = () => router.post(route('panel.onboarding.dismiss'), {}, { preserveScroll: true });
</script>

<template>
    <PanelLayout title="Prehľad dňa" :sub="dateLabel" :cta="{ label: 'Otvoriť výdaj', href: route('panel.issue') }">
        <!-- Úvodný sprievodca -->
        <div v-if="onboarding" class="flex flex-col gap-3.5 rounded-3xl bg-white px-5 py-[18px] shadow-[0_0_0_2px_#2A1D16]">
            <div class="flex items-start justify-between gap-3">
                <span class="flex flex-col gap-[3px]">
                    <span class="text-xs font-extrabold uppercase tracking-[.08em] text-cb-red">Vitajte na CampusBoxe</span>
                    <span class="text-[21px] font-black tracking-[-.02em]">Prvý predaj v 3 krokoch · {{ onbDone }} / 3</span>
                </span>
                <button
                    type="button"
                    class="flex h-11 items-center rounded-full px-3.5 text-[13.5px] font-bold text-cb-taupe hover:bg-cb-panel"
                    @click="dismissOnb"
                >
                    Skryť
                </button>
            </div>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-2.5">
                <Link
                    v-for="(o, i) in onbSteps"
                    :key="o.t"
                    :href="o.href"
                    class="box-border grid grid-cols-[32px_1fr] items-start gap-2.5 rounded-[18px] border-[1.5px] border-cb-line p-3.5 hover:bg-cb-panel"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-black"
                        :class="o.done ? 'bg-cb-green text-white' : 'bg-cb-panel'"
                        >{{ o.done ? '✓' : i + 1 }}</span
                    >
                    <span class="flex flex-col gap-0.5">
                        <span class="text-[15px] font-extrabold" :class="o.done && 'line-through'">{{ o.t }}</span>
                        <span class="text-[12.5px] leading-[1.4] text-cb-taupe">{{ o.d }}</span>
                    </span>
                </Link>
            </div>
        </div>

        <!-- KPI -->
        <div class="grid grid-cols-[repeat(auto-fit,minmax(140px,1fr))] gap-2.5">
            <div class="pcard flex flex-col gap-1.5 rounded-[26px] p-[18px]">
                <span class="text-[13px] font-bold text-cb-taupe">Ponúknuté</span>
                <span class="text-[44px] font-black leading-[.9] tracking-[-.04em] lg:text-[56px]">{{ kpi.offered }}</span>
            </div>
            <div class="pcard flex flex-col gap-1.5 rounded-[26px] p-[18px]">
                <span class="text-[13px] font-bold text-cb-taupe">Rezervované</span>
                <span class="text-[44px] font-black leading-[.9] tracking-[-.04em] text-cb-or-dark lg:text-[56px]">{{ kpi.reserved }}</span>
            </div>
            <div class="pcard flex flex-col gap-1.5 rounded-[26px] p-[18px]">
                <span class="text-[13px] font-bold text-cb-taupe">Vydané</span>
                <span class="text-[44px] font-black leading-[.9] tracking-[-.04em] text-cb-green lg:text-[56px]">{{ kpi.issued }}</span>
            </div>
            <div
                v-if="kpi.sales !== null"
                class="flex flex-col gap-1.5 rounded-[26px] bg-[rgba(21,128,61,.12)] p-[18px] shadow-[inset_0_1px_0_rgba(255,255,255,.9)]"
            >
                <span class="text-[13px] font-bold text-cb-green">Tržby dnes</span>
                <span class="text-[44px] font-black leading-[.9] tracking-[-.04em] text-cb-green lg:text-[56px]">{{ Math.round(kpi.sales) }} €</span>
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <div
                class="flex h-3.5 overflow-hidden rounded-[7px] bg-white/70"
                role="img"
                :aria-label="`Vydané ${kpi.issued}, čaká ${kpi.waiting}, voľné ${kpi.free}`"
            >
                <div class="bg-cb-green" :style="{ width: pIssued + '%' }" />
                <div class="bg-cb-or" :style="{ width: pWaiting + '%' }" />
            </div>
            <div class="flex flex-wrap gap-x-[18px] gap-y-1.5 text-[13px] font-semibold text-cb-taupe">
                <span><span class="text-cb-green">●</span> Vydané {{ kpi.issued }}</span>
                <span><span class="text-cb-or">●</span> Čaká {{ kpi.waiting }}</span>
                <span>● Voľné {{ kpi.free }}</span>
                <span>Výdaj {{ panel.business.rescueSlot }}</span>
            </div>
        </div>

        <!-- Spoľahlivosť -->
        <div v-if="reliability" class="pcard flex flex-col gap-3 px-5 py-[18px]">
            <div class="flex flex-wrap items-center justify-between gap-2.5">
                <span class="text-lg font-extrabold">Spoľahlivosť podniku · 30 dní</span>
                <span
                    class="flex h-[26px] items-center rounded-full px-2.5 text-xs font-extrabold"
                    :class="reliability.rate < 5 ? 'bg-[rgba(21,128,61,.12)] text-cb-green' : 'bg-[rgba(219,59,34,.14)] text-cb-red'"
                >
                    {{ reliability.rate < 5 ? 'V poriadku' : reliability.rate < 10 ? 'Pozor' : 'Kritické' }}
                </span>
            </div>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(130px,1fr))] gap-3">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[28px] font-black tracking-[-.03em]">{{ String(reliability.rate).replace('.', ',') }} %</span>
                    <span class="text-[12.5px] text-cb-taupe">objednávok s problémom</span>
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-[28px] font-black tracking-[-.03em]">{{ reliability.reported }}</span>
                    <span class="text-[12.5px] text-cb-taupe">nahlásené problémy</span>
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-[28px] font-black tracking-[-.03em]">{{ reliability.expired }}</span>
                    <span class="text-[12.5px] text-cb-taupe">nevydané · vrátené automaticky</span>
                </div>
            </div>
            <div class="relative h-2.5 rounded-[5px] bg-cb-panel">
                <div
                    class="h-full rounded-[5px]"
                    :class="reliability.rate < 5 ? 'bg-cb-green' : 'bg-cb-red'"
                    :style="{ width: Math.min(100, reliability.rate * 10) + '%' }"
                />
                <div class="absolute -bottom-1 -top-1 left-1/2 w-0.5 bg-cb-red" />
            </div>
            <span class="text-[12.5px] leading-[1.45] text-cb-taupe">
                Nad hranicou 5 % (červená čiara) vás upozorníme. Pri 10 % podnik dočasne skryjeme z appky, kým problém nevyriešime spolu.
            </span>
        </div>

        <div v-if="panel.role.key === 'pracovnik'" class="rounded-[20px] bg-white/60 px-4 py-3.5 text-sm leading-[1.45] text-cb-taupe">
            Ako <b class="text-cb-ink">pracovník</b> vidíte výdaj a objednávky. Tržby, výplaty a nastavenia ponuky vidí majiteľ alebo manažér.
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(280px,1fr))] items-start gap-3.5">
            <div class="pcard flex flex-col gap-1 p-5">
                <span class="mb-1.5 text-lg font-extrabold">Aktívne objednávky</span>
                <div
                    v-for="o in active"
                    :key="o.id"
                    class="flex flex-wrap items-center gap-x-3 gap-y-1.5 border-t-[1.5px] border-cb-line py-3 text-[14.5px]"
                >
                    <span class="font-mono text-sm font-bold">{{ o.code }}</span>
                    <span class="flex flex-[1_1_120px] flex-col gap-0.5 font-semibold">
                        <span
                            >{{ o.name }} · <span class="font-medium text-cb-taupe">{{ o.items }}</span></span
                        >
                        <span class="text-xs font-bold text-cb-taupe">{{ fulfilmentLabel(o) }}</span>
                    </span>
                    <StatusPill :order="o" size="sm" />
                    <button
                        v-if="actionFor(o)"
                        type="button"
                        class="flex h-9 items-center rounded-full px-4 text-[13px] font-bold text-white"
                        :style="{ background: actionFor(o)!.bg }"
                        @click="actionFor(o)!.run()"
                    >
                        {{ actionFor(o)!.label }}
                    </button>
                </div>
                <span v-if="!active.length" class="text-sm text-cb-taupe">Všetko vydané.</span>
            </div>

            <div class="pcard flex flex-col gap-2.5 p-5">
                <div class="flex items-center justify-between">
                    <span class="text-lg font-extrabold">Dnešná ponuka</span>
                    <span
                        class="flex h-[26px] items-center rounded-full px-2.5 text-xs font-extrabold"
                        :class="offer.length ? 'bg-[rgba(21,128,61,.12)] text-cb-green' : 'bg-cb-panel text-cb-taupe'"
                    >
                        {{ offer.length ? 'Zverejnená' : 'Nezverejnená' }}
                    </span>
                </div>
                <div v-for="o in offer" :key="o.name" class="flex justify-between gap-2.5 border-t-[1.5px] border-cb-line py-1.5 text-[14.5px]">
                    <span class="font-semibold">{{ o.count }}× {{ o.name }}</span>
                    <span v-if="o.price !== null" class="font-bold text-cb-green">{{ eur(o.price) }}</span>
                </div>
                <span v-if="!offer.length" class="text-sm text-cb-taupe">Na dnes ešte nič nepredávate.</span>
                <Link v-if="can('offer')" :href="route('panel.offer')" class="text-sm font-bold text-cb-red">{{
                    offer.length ? 'Upraviť ponuku →' : 'Zverejniť ponuku →'
                }}</Link>
            </div>
        </div>
    </PanelLayout>
</template>
