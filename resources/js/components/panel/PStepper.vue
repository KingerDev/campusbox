<script setup lang="ts">
defineProps<{ label: string; display: string; valueClass?: string; minWidth?: string }>();
defineEmits<{ down: []; up: [] }>();
</script>

<template>
    <div class="flex flex-col gap-1">
        <span class="text-[11px] font-bold text-cb-taupe">{{ label }}</span>
        <div class="flex items-center gap-0.5 rounded-[18px] bg-cb-panel p-[3px]">
            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full text-lg font-bold hover:bg-white"
                :aria-label="label + ' menej'"
                @click="$emit('down')"
            >
                −
            </button>
            <span class="flex justify-center text-base font-extrabold" :class="valueClass" :style="{ minWidth: minWidth ?? '26px' }">{{
                display
            }}</span>
            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full text-lg font-bold hover:bg-white"
                :aria-label="label + ' viac'"
                @click="$emit('up')"
            >
                +
            </button>
        </div>
    </div>
</template>
