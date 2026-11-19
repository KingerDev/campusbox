<script setup lang="ts">
/** Obrázok balíčka prekvapenie: papierová taška s otáznikom na oranžovom kruhu. */
withDefaults(defineProps<{ size?: number }>(), { size: 48 });
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 48 48" class="flex-none" role="img" aria-label="Balíček prekvapenie">
        <circle cx="24" cy="24" r="24" fill="#FFD9B5" />
        <!-- iskričky -->
        <path d="M9.5 13.5l1 2.6 2.6 1-2.6 1-1 2.6-1-2.6-2.6-1 2.6-1z" fill="#FF7A1A" />
        <path d="M39 9l.7 1.8 1.8.7-1.8.7L39 14l-.7-1.8-1.8-.7 1.8-.7z" fill="#DB3B22" />
        <!-- ušká -->
        <path d="M19 17v-3.2a5 5 0 0 1 10 0V17" fill="none" stroke="#2A1D16" stroke-width="2" stroke-linecap="round" />
        <!-- taška -->
        <path d="M13.5 17h21l2 19.2a2.5 2.5 0 0 1-2.5 2.8H14a2.5 2.5 0 0 1-2.5-2.8z" fill="#DB3B22" />
        <path d="M13.5 17h21l.5 4.6h-22z" fill="#B82E19" />
        <!-- otáznik -->
        <path
            d="M20.8 26.6a3.3 3.3 0 1 1 5.1 2.8c-1 .7-1.8 1.3-1.8 2.6"
            fill="none"
            stroke="#FFF6EA"
            stroke-width="2.4"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <circle cx="24.1" cy="35.3" r="1.5" fill="#FFF6EA" />
    </svg>
</template>
