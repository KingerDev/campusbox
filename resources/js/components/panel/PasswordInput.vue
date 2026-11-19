<script setup lang="ts">
import { passwordStrength } from '@/composables/useAccountFields';
import { computed, ref } from 'vue';

/** Nové heslo s prepínačom zobrazenia a ukazovateľom sily (Slabé / Priemerné / Dobré / Silné). */
const model = defineModel<string>({ required: true });
const props = defineProps<{ error?: boolean; personal?: string[] }>();

const visible = ref(false);
const strength = computed(() => passwordStrength(model.value, props.personal ?? []));
const COLORS = ['#DB3B22', '#DB3B22', '#E08A1E', '#5FA86B', '#15803D'];
const color = computed(() => COLORS[strength.value.score]);
</script>

<template>
    <span class="relative block">
        <input
            v-model="model"
            :type="visible ? 'text' : 'password'"
            autocomplete="new-password"
            autocapitalize="none"
            spellcheck="false"
            class="pinput bg-white/75 pr-[84px]"
            :class="error && '!border-cb-red'"
            :aria-invalid="!!error"
            aria-describedby="heslo-sila"
        />
        <button
            type="button"
            class="absolute right-2 top-1/2 flex h-9 -translate-y-1/2 items-center rounded-full px-3 text-[13px] font-bold text-cb-taupe hover:text-cb-ink"
            :aria-pressed="visible"
            @click="visible = !visible"
        >
            {{ visible ? 'Skryť' : 'Zobraziť' }}
        </button>
    </span>
    <span id="heslo-sila" class="flex flex-col gap-1.5" aria-live="polite">
        <span class="grid grid-cols-4 gap-1" aria-hidden="true">
            <span
                v-for="i in 4"
                :key="i"
                class="h-[5px] rounded-[3px] transition-colors"
                :style="{ background: model && i <= Math.max(1, strength.score) ? color : 'rgba(120,80,50,.18)' }"
            />
        </span>
        <span class="text-[12.5px] leading-[1.4] text-cb-taupe">
            <span v-if="strength.label" class="font-extrabold" :style="{ color }">{{ strength.label }}. </span>{{ strength.hint }}
        </span>
    </span>
</template>
