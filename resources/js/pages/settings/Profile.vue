<script setup lang="ts">
import AccountLayout from '@/layouts/AccountLayout.vue';
import { type SharedData, type User } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ ownedBusinesses: string[] }>();
const owned = computed(() => props.ownedBusinesses.join(', '));

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({ name: user.name, email: user.email });
const submit = () => form.patch(route('profile.update'), { preserveScroll: true });

// Zmazanie účtu: až po rozbalení a zadaní hesla.
const removing = ref(false);
const remove = useForm({ password: '' });
const toggleRemove = () => {
    removing.value = !removing.value;
    remove.reset();
    remove.clearErrors();
};
const submitRemove = () => remove.delete(route('profile.destroy'), { preserveScroll: true, onFinish: () => remove.reset() });
</script>

<template>
    <AccountLayout title="Moje údaje" sub="Meno vidí váš tím v paneli. E‑mailom sa prihlasujete a chodia naň upozornenia.">
        <form class="flex flex-col gap-3.5" @submit.prevent="submit">
            <label class="flex flex-col gap-[7px]">
                <span class="plabel">Meno a priezvisko</span>
                <input v-model="form.name" required autocomplete="name" class="pinput" :class="form.errors.name && '!border-cb-red'" />
                <span v-if="form.errors.name" class="text-sm font-bold text-cb-red" role="alert">{{ form.errors.name }}</span>
            </label>
            <label class="flex flex-col gap-[7px]">
                <span class="plabel">E‑mail</span>
                <input
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="username"
                    autocapitalize="none"
                    spellcheck="false"
                    class="pinput"
                    :class="form.errors.email && '!border-cb-red'"
                />
                <span v-if="form.errors.email" class="text-sm font-bold text-cb-red" role="alert">{{ form.errors.email }}</span>
            </label>
            <div class="flex flex-wrap items-center gap-4 pt-1">
                <button type="submit" class="pbtn pbtn-red" :disabled="form.processing || !form.isDirty">
                    <span>Uložiť údaje</span><span>→</span>
                </button>
                <span v-if="form.recentlySuccessful" class="text-sm font-bold text-cb-green" role="status">Uložené ✓</span>
            </div>
        </form>

        <template #after>
            <section class="pcard flex w-full max-w-[620px] flex-col gap-3 p-5 lg:p-6">
                <div class="flex flex-col gap-1">
                    <h2 class="m-0 text-[20px] font-extrabold tracking-[-.01em]">Zmazať účet</h2>
                    <span v-if="!ownedBusinesses.length" class="text-sm leading-[1.45] text-cb-taupe">
                        Zmaže sa váš prihlasovací účet a prístup do panela. Nedá sa to vrátiť späť.
                    </span>
                </div>
                <div
                    v-if="ownedBusinesses.length"
                    class="flex flex-col gap-1 rounded-2xl bg-[rgba(255,122,26,.14)] px-4 py-3 text-sm font-semibold leading-[1.45] text-cb-or-dark"
                >
                    <span class="font-black">Ste majiteľom podniku {{ owned }}.</span>
                    <span class="text-cb-ink">
                        Bez majiteľa by nemal kto spravovať objednávky a výplaty, preto sa účet zmazať nedá. Ak chcete podnik odovzdať niekomu inému
                        alebo ho zrušiť, napíšte nám na
                        <a href="mailto:ahoj@campusbox.sk" class="font-bold underline">ahoj@campusbox.sk</a>.
                    </span>
                </div>
                <button v-else-if="!removing" type="button" class="pbtn pbtn-ghost h-12 self-start text-[15px] text-cb-red" @click="toggleRemove">
                    Zmazať účet
                </button>
                <form v-else class="flex flex-col gap-3 rounded-3xl bg-[rgba(219,59,34,.08)] px-5 py-[18px]" @submit.prevent="submitRemove">
                    <label class="flex flex-col gap-[7px]">
                        <span class="plabel">Pre potvrdenie zadajte heslo</span>
                        <input
                            v-model="remove.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="pinput"
                            :class="remove.errors.password && '!border-cb-red'"
                        />
                    </label>
                    <span v-if="remove.errors.password" class="text-sm font-bold text-cb-red" role="alert">{{ remove.errors.password }}</span>
                    <div class="flex flex-wrap gap-2.5">
                        <button type="submit" class="pbtn pbtn-red h-12 text-[15px]" :disabled="remove.processing">Natrvalo zmazať účet</button>
                        <button type="button" class="pbtn pbtn-ghost h-12 text-[15px]" @click="toggleRemove">Zrušiť</button>
                    </div>
                </form>
            </section>
        </template>
    </AccountLayout>
</template>
