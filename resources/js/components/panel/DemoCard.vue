<script setup lang="ts">
import { useDemo } from '@/composables/useDemo';

const { startDemo, starting, demoError } = useDemo();
</script>

<template>
    <div class="flex flex-col gap-2 rounded-[20px] border-[1.5px] border-dashed border-cb-line bg-white/60 px-4 py-3.5">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2.5">
            <span class="flex min-w-[200px] flex-1 flex-col gap-0.5">
                <span class="text-[15px] font-extrabold">Chcete si panel najprv pozrieť?</span>
                <span class="text-[13px] leading-[1.4] text-cb-taupe">
                    Otvoríme vám ukážkové bistro s objednávkami, ponukou aj výplatami. Bez registrácie, nič sa neposiela ani neúčtuje.
                </span>
            </span>
            <button
                type="button"
                class="flex h-11 flex-none items-center gap-3 rounded-full bg-cb-ink px-[18px] text-sm font-bold text-cb-cream disabled:opacity-60"
                :disabled="starting"
                @click="startDemo"
            >
                <span>{{ starting ? 'Pripravujeme…' : 'Vyskúšať demo' }}</span
                ><span>→</span>
            </button>
        </div>
        <span v-if="demoError" class="text-sm font-bold text-cb-red" role="alert">{{ demoError }}</span>
    </div>
</template>
