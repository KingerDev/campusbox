<script setup lang="ts">
import { useDevTools } from '@/composables/useDevFill';

/** Tlačidlo „Vyplniť“ pre testovanie. Vidno ho len lokálne (APP_ENV=local). */
withDefaults(defineProps<{ label?: string }>(), { label: 'Vyplniť testovacie údaje' });
defineEmits<{ fill: [] }>();

const devTools = useDevTools();
</script>

<template>
    <button
        v-if="devTools"
        type="button"
        class="flex h-9 flex-none items-center gap-1.5 self-start rounded-full border-[1.5px] border-dashed border-[#2457C5] bg-[rgba(47,111,235,.08)] px-3 text-[12.5px] font-bold text-[#1F3F8F]"
        title="Len pri lokálnom vývoji"
        @click="$emit('fill')"
    >
        <span aria-hidden="true">⚡</span>{{ label }}
    </button>
</template>
