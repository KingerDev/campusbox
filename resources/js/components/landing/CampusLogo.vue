<script setup lang="ts">
withDefaults(defineProps<{ size?: number; inverted?: boolean; wordmark?: boolean; textSize?: number }>(), {
    size: 38,
    inverted: false,
    wordmark: true,
    textSize: 24,
});
</script>

<template>
    <span class="flex items-center gap-2">
        <svg :width="size" :height="size" viewBox="0 0 100 100" class="flex-none" aria-hidden="true">
            <rect x="18" y="44" width="64" height="44" rx="12" fill="#DB3B22" />
            <path d="M36 63 Q40.5 57.5 45 63 M55 63 Q59.5 57.5 64 63" fill="none" stroke="#FFF6EA" stroke-width="4.2" stroke-linecap="round" />
            <path d="M44 71 Q50 76.5 56 71" fill="none" stroke="#FFF6EA" stroke-width="4.2" stroke-linecap="round" />
            <polygon
                points="50,14 90,30 50,46 10,30"
                :fill="inverted ? '#FFF6EA' : '#2A1D16'"
                :stroke="inverted ? '#FFF6EA' : '#2A1D16'"
                stroke-width="5"
                stroke-linejoin="round"
            />
            <path d="M80 34 V52" stroke="#15803D" stroke-width="4" stroke-linecap="round" />
            <circle cx="80" cy="55" r="5" fill="#15803D" />
        </svg>
        <span v-if="wordmark" class="font-black leading-none tracking-[-.045em]" :style="{ fontSize: textSize + 'px' }">
            <span :class="inverted ? 'text-cb-cream' : 'text-cb-ink'">campus</span><span class="text-cb-red">box</span>
        </span>
    </span>
</template>
