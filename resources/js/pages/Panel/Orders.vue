<script setup lang="ts">
import StatusPill from '@/components/panel/StatusPill.vue';
import { useOrderActions } from '@/composables/useOrderActions';
import { eur, fulfilmentLabel, usePanel, type PanelOrder } from '@/composables/usePanel';
import PanelLayout from '@/layouts/PanelLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps<{ orders: PanelOrder[] }>();
const { can } = usePanel();
const { actionFor, openConfirm } = useOrderActions();

const count = (s: string) => props.orders.filter((o) => o.status === s).length;
const sub = computed(() => `${count('nova')} na potvrdenie · ${count('prip')} sa pripravuje · ${count('hot')} pripravené`);

const newOrders = computed(() => props.orders.filter((o) => o.status === 'nova'));
const FILTERS = [
    ['all', 'Všetky'],
    ['prip', 'Pripravuje sa'],
    ['hot', 'Pripravené'],
    ['cesta', 'Na ceste'],
    ['vyd', 'Vydané'],
    ['nahl', 'Problémy'],
] as const;
const filter = ref<(typeof FILTERS)[number][0]>('all');
const rows = computed(() =>
    props.orders.filter((o) => {
        if (o.status === 'nova') return false;
        if (filter.value === 'all') return true;
        if (filter.value === 'nahl') return ['nahl', 'zrus', 'odm'].includes(o.status);
        return o.status === filter.value;
    }),
);
</script>

<template>
    <PanelLayout title="Objednávky" :sub="sub">
        <div v-if="newOrders.length" class="flex flex-col gap-2.5">
            <span class="flex items-center gap-2 text-[17px] font-extrabold"
                ><span class="h-2.5 w-2.5 rounded-full bg-cb-red" />Čakajú na potvrdenie</span
            >
            <div class="grid grid-cols-[repeat(auto-fill,minmax(260px,1fr))] gap-2.5">
                <div
                    v-for="n in newOrders"
                    :key="n.id"
                    class="flex flex-col gap-2.5 rounded-[26px] border-2 border-cb-red bg-white/70 p-4 shadow-[0_14px_30px_-16px_rgba(219,59,34,.45)]"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-[17px] font-extrabold">{{ n.code }}</span>
                        <span class="text-[12.5px] font-bold text-cb-taupe">objednané {{ n.at }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-base font-extrabold">{{ n.name }}</span>
                        <span class="text-sm text-cb-taupe">{{ n.items }}</span>
                        <span
                            class="flex min-h-[26px] items-center self-start rounded-[13px] px-2.5 py-1 text-xs font-extrabold leading-tight"
                            :class="n.delivery ? 'bg-[rgba(47,111,235,.14)] text-cb-blue' : 'bg-cb-panel text-cb-taupe'"
                        >
                            {{ fulfilmentLabel(n) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span v-if="can('money')" class="text-base font-extrabold">{{ eur(n.sum) }}</span>
                        <button
                            type="button"
                            class="box-border flex h-11 max-w-[200px] flex-auto items-center justify-between rounded-full bg-cb-red px-[18px] text-[14.5px] font-bold text-white"
                            @click="openConfirm(n)"
                        >
                            <span>Potvrdiť</span><span>→</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-x-[18px] gap-y-1.5 rounded-[22px] bg-white/55 px-4 py-3 text-[13px] leading-[1.4] text-cb-taupe">
            <span><b class="text-cb-ink">Zrušenie študentom:</b> zadarmo do potvrdenia, potom do 30 min pred výdajom.</span>
            <span><b class="text-cb-ink">Automatické vrátenie:</b> objednávka nevydaná do 15 min po konci okna sa zruší a peniaze sa vrátia.</span>
        </div>

        <div class="flex flex-wrap gap-1 self-start rounded-[22px] bg-white/60 p-1">
            <button
                v-for="[k, l] in FILTERS"
                :key="k"
                type="button"
                class="flex h-9 items-center rounded-full px-4 text-[13.5px] font-bold"
                :class="filter === k ? 'bg-cb-ink text-cb-cream' : ''"
                :aria-pressed="filter === k"
                @click="filter = k"
            >
                {{ l }}
            </button>
        </div>

        <div class="pcard px-[18px] py-1">
            <div
                v-for="(r, i) in rows"
                :key="r.id"
                class="flex flex-wrap items-center gap-x-4 gap-y-1.5 py-3.5 text-[14.5px]"
                :class="i < rows.length - 1 && 'border-b-[1.5px] border-cb-line'"
            >
                <span class="flex-[0_0_86px] font-mono text-sm font-bold">{{ r.code }}</span>
                <span class="flex min-w-0 flex-[1_1_200px] flex-col gap-0.5">
                    <span class="font-bold">{{ r.name }}</span>
                    <span class="text-[13px] text-cb-taupe"
                        >{{ r.items }} · objednané {{ r.at }}<template v-if="r.eta"> · čas {{ r.eta }}</template></span
                    >
                    <span class="text-xs font-bold text-cb-taupe">{{ r.reason ?? fulfilmentLabel(r) }}</span>
                </span>
                <span v-if="can('money')" class="flex-[0_0_64px] font-bold">{{ eur(r.sum) }}</span>
                <StatusPill :order="r" />
                <button
                    v-if="actionFor(r)"
                    type="button"
                    class="flex h-9 items-center rounded-full px-4 text-[13px] font-bold text-white"
                    :style="{ background: actionFor(r)!.bg }"
                    @click="actionFor(r)!.run()"
                >
                    {{ actionFor(r)!.label }}
                </button>
            </div>
            <div v-if="!rows.length" class="py-5 text-sm text-cb-taupe">Žiadne objednávky v tomto filtri.</div>
        </div>
    </PanelLayout>
</template>
