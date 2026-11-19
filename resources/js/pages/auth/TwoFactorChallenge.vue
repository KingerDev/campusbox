<script setup lang="ts">
import CampusAuthLayout from '@/layouts/CampusAuthLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const recovery = ref(false);
const input = ref<HTMLInputElement | null>(null);
const form = useForm({ code: '', recovery_code: '' });

const submit = () => form.post(route('two-factor.challenge'), { onError: () => (form.reset(), nextTick(() => input.value?.focus())) });
const toggle = () => {
    recovery.value = !recovery.value;
    form.reset();
    form.clearErrors();
    nextTick(() => input.value?.focus());
};
</script>

<template>
    <CampusAuthLayout
        title="Overenie prihlásenia"
        kicker="Druhý krok"
        :sub="
            recovery
                ? 'Zadajte jeden zo záložných kódov, ktoré ste si uložili pri nastavení. Každý platí len raz.'
                : 'Otvorte autentifikačnú appku a zadajte 6‑miestny kód pre CampusBox.'
        "
    >
        <form class="flex flex-col gap-3.5" @submit.prevent="submit">
            <label v-if="!recovery" class="flex flex-col gap-[7px]">
                <span class="plabel">Kód z appky</span>
                <input
                    ref="input"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="7"
                    required
                    autofocus
                    placeholder="123 456"
                    class="pinput bg-white/75 text-[22px] font-extrabold tracking-[.18em]"
                    :class="form.errors.code && '!border-cb-red'"
                />
            </label>
            <label v-else class="flex flex-col gap-[7px]">
                <span class="plabel">Záložný kód</span>
                <input
                    ref="input"
                    v-model="form.recovery_code"
                    type="text"
                    autocomplete="off"
                    autocapitalize="none"
                    spellcheck="false"
                    required
                    placeholder="abcde-fghij"
                    class="pinput bg-white/75 font-mono"
                    :class="form.errors.recovery_code && '!border-cb-red'"
                />
            </label>
            <span v-if="form.errors.code || form.errors.recovery_code" class="text-sm font-bold text-cb-red" role="alert">
                {{ form.errors.code || form.errors.recovery_code }}
            </span>
            <button type="submit" class="pbtn pbtn-red mt-2" :disabled="form.processing"><span>Overiť a prihlásiť</span><span>→</span></button>
        </form>
        <button type="button" class="self-start text-sm font-bold text-cb-red" @click="toggle">
            {{ recovery ? 'Použiť kód z appky' : 'Nemám telefón – použiť záložný kód' }}
        </button>
        <Link :href="route('login')" class="text-sm font-semibold text-cb-taupe hover:text-cb-ink">← Späť na prihlásenie</Link>
    </CampusAuthLayout>
</template>
