<script setup lang="ts">
import { statusTone, type PanelOrder } from '@/composables/usePanel';
import { computed } from 'vue';

const props = defineProps<{ order: PanelOrder; size?: 'sm' | 'md' }>();
const tone = computed(() => statusTone(props.order));
</script>

<template>
    <span
        class="inline-flex flex-none items-center whitespace-nowrap rounded-full font-extrabold"
        :class="size === 'sm' ? 'h-[26px] px-2.5 text-xs' : 'h-7 px-3 text-[12.5px]'"
        :style="{ background: tone.bg, color: tone.col }"
    >
        {{ order.statusLabel }}
    </span>
</template>
