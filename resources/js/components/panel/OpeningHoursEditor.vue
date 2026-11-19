<script setup lang="ts">
import PToggle from './PToggle.vue';
import TimeInput from './TimeInput.vue';

export type Hours = [string, string, string, boolean][];

const hours = defineModel<Hours>({ required: true });

const VALID = /^([01]\d|2[0-3]):[0-5]\d$/;

/** Zatvorený deň časy nepotrebuje; pri znovuotvorení doplníme bežné hodiny, aby nezostal nevyplnený či chybný čas. */
const setOpen = (i: number, open: boolean) => {
    const h = hours.value[i];
    hours.value[i] = open ? [h[0], VALID.test(h[1]) ? h[1] : '10:00', VALID.test(h[2]) ? h[2] : '21:00', false] : [h[0], '', '', true];
};

const copyMonday = () => {
    const [, o, c, closed] = hours.value[0];
    for (let i = 1; i <= 4; i++) hours.value[i] = [hours.value[i][0], o, c, closed];
};
</script>

<template>
    <div class="flex flex-col">
        <div class="flex justify-end">
            <button type="button" class="text-[13px] font-bold text-cb-red" @click="copyMonday">Skopírovať pondelok na Ut–Pi</button>
        </div>
        <div
            v-for="(h, i) in hours"
            :key="h[0]"
            class="flex flex-wrap items-center gap-x-3 gap-y-2 py-2.5"
            :class="i < hours.length - 1 && 'border-b-[1.5px] border-cb-line'"
        >
            <span class="flex-[0_0_92px] text-[14.5px] font-bold">{{ h[0] }}</span>
            <PToggle :model-value="!h[3]" small :label="h[0] + ' otvorené'" @update:model-value="setOpen(i, $event)" />
            <span v-if="!h[3]" class="flex items-center gap-1.5">
                <TimeInput v-model="h[1]" placeholder="10:00" :label="h[0] + ' od'" />
                <span class="text-cb-taupe">–</span>
                <TimeInput v-model="h[2]" placeholder="21:00" :label="h[0] + ' do'" />
            </span>
            <span v-else class="text-sm font-semibold text-cb-taupe">Zatvorené</span>
        </div>
    </div>
</template>
