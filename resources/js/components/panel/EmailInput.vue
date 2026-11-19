<script setup lang="ts">
import { cleanEmail, emailProblem, emailSuggestion } from '@/composables/useAccountFields';
import { computed, ref } from 'vue';

/** E‑mail: pri odchode z poľa odstráni medzery a veľké písmená, upozorní na neúplný tvar a ponúkne opravu preklepu v doméne. */
const model = defineModel<string>({ required: true });
defineProps<{ error?: boolean }>();

const touched = ref(false);
const problem = computed(() => (touched.value ? emailProblem(model.value) : ''));
const suggestion = computed(() => (touched.value && !problem.value ? emailSuggestion(model.value) : null));

const onBlur = () => {
    model.value = cleanEmail(model.value);
    touched.value = true;
};
</script>

<template>
    <input
        v-model="model"
        type="email"
        inputmode="email"
        autocomplete="email"
        autocapitalize="none"
        spellcheck="false"
        placeholder="meno@podnik.sk"
        class="pinput bg-white/75"
        :class="(error || problem) && '!border-cb-red'"
        :aria-invalid="!!(error || problem)"
        @blur="onBlur"
    />
    <span v-if="problem" class="text-[12.5px] font-semibold text-cb-red">{{ problem }}</span>
    <button v-else-if="suggestion" type="button" class="self-start text-left text-[12.5px] font-semibold text-cb-or-dark" @click="model = suggestion">
        Mysleli ste <span class="font-extrabold underline">{{ suggestion }}</span
        >?
    </button>
</template>
