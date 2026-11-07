<script setup lang="ts">
import { confirmState, useOrderActions } from '@/composables/useOrderActions';
import { eur, fulfilmentLabel, usePanel } from '@/composables/usePanel';
import { computed, ref, watch } from 'vue';
import PChip from './PChip.vue';

const { can, panel } = usePanel();
const { closeConfirm, confirm, reject } = useOrderActions();

type Eta = number | 'start' | 'custom' | 'none';
const eta = ref<Eta>(15);
const custom = ref('');
const reason = ref('Vypredané');

const order = computed(() => confirmState.order);
watch(order, (o) => {
    if (!o) return;
    eta.value = 15;
    reason.value = 'Vypredané';
    custom.value = addMin(25);
});

const pad = (n: number) => String(n).padStart(2, '0');
function addMin(m: number) {
    const d = new Date(Date.now() + m * 60000);
    return d.getHours() + ':' + pad(d.getMinutes());
}
const slotStart = computed(() => panel.value.business.rescueSlot.split('–')[0]);

const etaTime = computed<string | null>(() => {
    if (eta.value === 'none') return null;
    if (eta.value === 'start') return slotStart.value;
    if (eta.value === 'custom') return custom.value || addMin(25);
    return addMin(eta.value);
});

const opts: [Eta, string][] = [
    [5, '5 min'],
    [10, '10 min'],
    [15, '15 min'],
    [20, '20 min'],
    [30, '30 min'],
    ['start', 'Na začiatok výdaja'],
    ['custom', 'Vlastný čas'],
    ['none', 'Nešpecifikovať'],
];

/** Pri donáške vidí študent čas doručenia (príprava + ~20 min cesty). */
const shownTime = computed(() => {
    if (!etaTime.value || !order.value?.delivery) return etaTime.value;
    const [h, m] = etaTime.value.split(':').map(Number);
    const t = h * 60 + m + 20;
    return (Math.floor(t / 60) % 24) + ':' + pad(t % 60);
});

const submit = () => {
    if (!order.value) return;
    const t = etaTime.value ? etaTime.value.padStart(5, '0') : null;
    confirm(order.value, t);
    closeConfirm();
};
const doReject = () => {
    if (!order.value) return;
    reject(order.value, reason.value);
    closeConfirm();
};
</script>

<template>
    <Transition
        enter-from-class="opacity-0"
        leave-to-class="opacity-0"
        enter-active-class="transition-opacity"
        leave-active-class="transition-opacity"
    >
        <div
            v-if="order"
            class="fixed inset-0 z-[60] box-border flex items-end justify-center bg-[rgba(42,29,22,.32)] p-3 backdrop-blur-[6px] sm:items-center sm:p-6"
            @click.self="closeConfirm"
            @keydown.esc="closeConfirm"
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="conf-title"
                class="pglass box-border flex max-h-full w-full max-w-[520px] flex-col gap-4 overflow-y-auto rounded-[30px] p-[22px]"
            >
                <div class="flex items-center justify-between">
                    <span id="conf-title" class="text-2xl font-black tracking-[-.02em]">Potvrdiť objednávku</span>
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-white/80 text-lg font-bold"
                        aria-label="Zavrieť"
                        @click="closeConfirm"
                    >
                        ✕
                    </button>
                </div>

                <div class="flex flex-col gap-1 rounded-[22px] bg-white px-4 py-3.5">
                    <div class="flex justify-between">
                        <span class="font-mono text-base font-extrabold">{{ order.code }}</span>
                        <span v-if="can('money')" class="font-extrabold">{{ eur(order.sum) }}</span>
                    </div>
                    <span class="text-[15px] font-bold">{{ order.name }}</span>
                    <span class="text-sm text-cb-taupe">{{ order.items }}</span>
                    <span class="text-[13px] font-bold">{{ fulfilmentLabel(order) }}</span>
                </div>

                <div class="flex flex-col gap-2">
                    <span class="text-[13px] font-bold">{{
                        order.delivery ? 'Kedy bude pripravené pre kuriéra?' : 'Kedy bude pripravené na odovzdanie?'
                    }}</span>
                    <div class="flex flex-wrap gap-1.5">
                        <PChip v-for="[v, l] in opts" :key="String(v)" size="lg" :on="eta === v" @click="eta = v">{{ l }}</PChip>
                    </div>
                    <label v-if="eta === 'custom'" class="flex items-center gap-2.5 text-[13px] font-bold">
                        Pripravené o
                        <input
                            v-model="custom"
                            type="time"
                            class="h-[42px] rounded-[14px] border-[1.5px] border-[rgba(120,80,50,.3)] bg-white px-3 text-base font-bold"
                        />
                    </label>
                </div>

                <div class="flex flex-col gap-0.5 rounded-[22px] px-4 py-3.5" :class="eta === 'none' ? 'bg-white/70' : 'bg-[rgba(21,128,61,.12)]'">
                    <span class="text-[12.5px] font-bold" :class="eta === 'none' ? 'text-cb-taupe' : 'text-cb-green'">
                        {{
                            eta === 'none'
                                ? 'Študent uvidí stav'
                                : order.delivery
                                  ? 'Študent uvidí doručenie približne o'
                                  : 'Študent dostane upozornenie · pripravené o'
                        }}
                    </span>
                    <span class="text-[26px] font-black tracking-[-.02em]" :class="eta === 'none' ? 'text-cb-ink' : 'text-cb-green'">
                        {{ shownTime ?? 'Pripravuje sa' }}
                    </span>
                    <span v-if="order.delivery && etaTime" class="text-[12.5px] text-cb-green"
                        >Kuriér vyzdvihne o {{ etaTime }}, cesta cca 20 min</span
                    >
                    <span v-if="eta === 'none'" class="text-[12.5px] leading-[1.4] text-cb-taupe">
                        Keď bude hotové, kliknite v objednávkach na „Pripravené“. Študent dostane upozornenie až vtedy.
                    </span>
                </div>

                <button type="button" class="pbtn pbtn-red" @click="submit">
                    <span>{{ etaTime ? 'Potvrdiť · pripravené o ' + etaTime : 'Potvrdiť · čas upresním neskôr' }}</span
                    ><span>✓</span>
                </button>

                <div class="flex flex-col gap-2 border-t-[1.5px] border-cb-line pt-3.5">
                    <span class="text-[13px] font-bold text-cb-taupe">Nemôžete objednávku splniť?</span>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <PChip v-for="r in ['Vypredané', 'Zatvárame skôr', 'Iný dôvod']" :key="r" size="sm" :on="reason === r" @click="reason = r">{{
                            r
                        }}</PChip>
                        <button type="button" class="flex h-9 items-center px-1.5 text-[13.5px] font-bold text-cb-red" @click="doReject">
                            Odmietnuť a vrátiť peniaze
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
