<script setup lang="ts">
withDefaults(defineProps<{ on?: boolean; size?: 'sm' | 'md' | 'lg' }>(), { on: false, size: 'md' });
</script>

<template>
    <button
        type="button"
        class="box-border inline-flex flex-none items-center whitespace-nowrap rounded-full border-[1.5px] font-bold transition-colors"
        :class="[
            on ? 'border-cb-ink bg-cb-ink text-cb-cream' : 'border-cb-line bg-white text-cb-ink hover:border-cb-ink/40',
            size === 'sm' ? 'h-9 px-3 text-[13px]' : size === 'lg' ? 'h-[42px] px-4 text-sm' : 'h-[38px] px-3.5 text-[13.5px]',
        ]"
        :aria-pressed="on"
    >
        <slot />
    </button>
</template>
