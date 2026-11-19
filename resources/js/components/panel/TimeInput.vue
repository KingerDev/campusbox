<script setup lang="ts">
import { computed, ref } from 'vue';

/**
 * Pole na čas HH:MM, v ktorom sa ťažko pomýliť: berie len číslice, dvojbodku dopĺňa samo
 * („1030“ aj „10“ + „30“ → 10:30, „9“ → 09:), hodiny nad 23 a minúty nad 59 nepustí a pri odchode z poľa čas doplní („9“ → 09:00).
 */
const model = defineModel<string>({ required: true });
defineProps<{ placeholder?: string; label: string }>();

const VALID = /^([01]\d|2[0-3]):[0-5]\d$/;
const touched = ref(false);
const invalid = computed(() => touched.value && !VALID.test(model.value));

const mask = (raw: string, deleting: boolean): string => {
    const colon = raw.includes(':');
    const digits = raw.replace(/\D/g, '');
    let h: string;
    let m: string;
    if (colon) {
        [h, m = ''] = raw.split(':').map((part) => part.replace(/\D/g, '').slice(0, 2));
        if (h.length === 1) h = '0' + h; // „9:“ → 09:
    } else if (digits.length >= 1 && (+digits[0] > 2 || +digits.slice(0, 2) > 23)) {
        // „9“, „930“ alebo „25“: prvá číslica je celá hodina, zvyšok sú minúty.
        h = '0' + digits[0];
        m = digits.slice(1, 3);
    } else {
        h = digits.slice(0, 2);
        m = digits.slice(2, 4);
    }

    if (!h) return '';
    if (+h > 23) h = '23';
    if (m && +m[0] > 5) m = '';
    if (h.length < 2) return h;
    if (m) return `${h}:${m}`;

    return deleting && !colon ? h : `${h}:`;
};

const onInput = (e: Event) => {
    const el = e.target as HTMLInputElement;
    const next = mask(el.value, (e as InputEvent).inputType?.startsWith('delete') ?? false);
    model.value = next;
    el.value = next;
};

const onBlur = () => {
    touched.value = true;
    const [h = '', m = ''] = model.value.split(':');
    if (!h) return;
    model.value = `${h.padStart(2, '0')}:${m.padEnd(2, '0')}`;
};
</script>

<template>
    <input
        :value="model"
        type="text"
        inputmode="numeric"
        maxlength="5"
        autocomplete="off"
        :placeholder="placeholder"
        :aria-label="label"
        :aria-invalid="invalid"
        class="pinput h-[38px] w-[70px] rounded-xl px-2.5 text-sm font-bold"
        :class="invalid && '!border-cb-red'"
        @input="onInput"
        @blur="onBlur"
        @focus="($event.target as HTMLInputElement).select()"
    />
</template>
