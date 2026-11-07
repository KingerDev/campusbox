<script setup lang="ts">
import CampusAuthLayout from '@/layouts/CampusAuthLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps<{ status?: string; canResetPassword: boolean }>();

const form = useForm({ email: '', password: '', remember: true });
const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <CampusAuthLayout title="Prihlásenie do panela" kicker="Panel pre podniky" sub="Výdaj, objednávky a dnešná ponuka na jednom mieste.">
        <div v-if="status" class="rounded-2xl bg-[rgba(21,128,61,.12)] px-4 py-3 text-sm font-bold text-cb-green">{{ status }}</div>
        <form class="flex flex-col gap-3.5" @submit.prevent="submit">
            <label class="flex flex-col gap-[7px]">
                <span class="plabel">E‑mail</span>
                <input
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    class="pinput bg-white/75"
                    :class="form.errors.email && '!border-cb-red'"
                />
            </label>
            <label class="flex flex-col gap-[7px]">
                <span class="flex justify-between">
                    <span class="plabel">Heslo</span>
                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-[13px] font-bold text-cb-red">Zabudnuté heslo?</Link>
                </span>
                <input v-model="form.password" type="password" required autocomplete="current-password" class="pinput bg-white/75" />
            </label>
            <label class="flex cursor-pointer items-center gap-2.5 text-sm font-semibold">
                <input v-model="form.remember" type="checkbox" class="h-5 w-5 rounded-md border-cb-taupe text-cb-red focus:ring-cb-red" />Zapamätať si
                ma
            </label>
            <span v-if="form.errors.email" class="text-sm font-bold text-cb-red" role="alert">{{ form.errors.email }}</span>
            <button type="submit" class="pbtn pbtn-red mt-2" :disabled="form.processing"><span>Prihlásiť sa</span><span>→</span></button>
        </form>
        <span class="text-sm text-cb-taupe"
            >Nemáte účet? <Link :href="route('panel.registration')" class="font-bold text-cb-red">Zaregistrujte podnik</Link></span
        >
    </CampusAuthLayout>
</template>
