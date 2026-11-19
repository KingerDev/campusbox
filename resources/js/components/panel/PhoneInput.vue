<script setup lang="ts">
import { formatPhone, phoneProblem } from '@/composables/useAccountFields';
import { computed, ref } from 'vue';

/** Telefón, ktorý sa sám formátuje na +421 9xx xxx xxx (predvoľbu doplní, „0903…“ prepíše). */
const model = defineModel<string>({ required: true });
defineProps<{ error?: boolean }>();

const touched = ref(false);
const problem = computed(() => (touched.value ? phoneProblem(model.value) : ''));

const onInput = (e: Event) => {
    const el = e.target as HTMLInputElement;
    const deleting = (e as InputEvent).inputType?.startsWith('delete') ?? false;
    // Pri mazaní neformátujeme nanovo samotnú predvoľbu, aby sa dala zmazať celá.
    const next = deleting && el.value.replace(/\D/g, '').length <= 3 ? el.value.trim() : formatPhone(el.value);
    model.value = next;
    el.value = next;
};
const onFocus = () => {
    if (!model.value) model.value = '+421 ';
};
const onBlur = () => {
    model.value = model.value.replace(/\D/g, '').length <= 3 ? '' : formatPhone(model.value);
    touched.value = true;
};
</script>

<template>
    <input
        :value="model"
        type="tel"
        inputmode="tel"
        autocomplete="tel"
        maxlength="20"
        placeholder="+421 9xx xxx xxx"
        class="pinput bg-white/75"
        :class="(error || problem) && '!border-cb-red'"
        :aria-invalid="!!(error || problem)"
        @input="onInput"
        @focus="onFocus"
        @blur="onBlur"
    />
    <span v-if="problem" class="text-[12.5px] font-semibold text-cb-red">{{ problem }}</span>
</template>
