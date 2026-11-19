<script setup lang="ts">
import PasswordInput from '@/components/panel/PasswordInput.vue';
import AccountLayout from '@/layouts/AccountLayout.vue';
import { type SharedData, type User } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';

const user = usePage<SharedData>().props.auth.user as User;

const form = useForm({ current_password: '', password: '', password_confirmation: '' });
const submit = () =>
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: (errors) => {
            if (errors.password) form.reset('password', 'password_confirmation');
            if (errors.current_password) form.reset('current_password');
        },
    });
</script>

<template>
    <AccountLayout title="Heslo" sub="Použite dlhé heslo, ktoré nemáte nikde inde. Najjednoduchšie je nechať si ho vygenerovať správcom hesiel.">
        <form class="flex flex-col gap-3.5" @submit.prevent="submit">
            <label class="flex flex-col gap-[7px]">
                <span class="plabel">Súčasné heslo</span>
                <input
                    v-model="form.current_password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="pinput"
                    :class="form.errors.current_password && '!border-cb-red'"
                />
                <span v-if="form.errors.current_password" class="text-sm font-bold text-cb-red" role="alert">{{ form.errors.current_password }}</span>
            </label>
            <div class="flex flex-col gap-[7px]">
                <span class="plabel">Nové heslo</span>
                <PasswordInput v-model="form.password" :error="!!form.errors.password" :personal="[user.name, user.email]" />
                <span v-if="form.errors.password" class="text-sm font-bold text-cb-red" role="alert">{{ form.errors.password }}</span>
            </div>
            <label class="flex flex-col gap-[7px]">
                <span class="plabel">Nové heslo ešte raz</span>
                <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="pinput" />
            </label>
            <div class="flex flex-wrap items-center gap-4 pt-1">
                <button type="submit" class="pbtn pbtn-red" :disabled="form.processing"><span>Zmeniť heslo</span><span>→</span></button>
                <span v-if="form.recentlySuccessful" class="text-sm font-bold text-cb-green" role="status">Heslo zmenené ✓</span>
            </div>
        </form>
    </AccountLayout>
</template>
