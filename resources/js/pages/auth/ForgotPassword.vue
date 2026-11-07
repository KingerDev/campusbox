<script setup lang="ts">
import CampusAuthLayout from '@/layouts/CampusAuthLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps<{ status?: string }>();
const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>

<template>
    <CampusAuthLayout title="Zabudnuté heslo" sub="Pošleme vám e‑mail s odkazom na nastavenie nového hesla.">
        <div v-if="status" class="rounded-2xl bg-[rgba(21,128,61,.12)] px-4 py-3 text-sm font-bold text-cb-green">{{ status }}</div>
        <form class="flex flex-col gap-3.5" @submit.prevent="submit">
            <label class="flex flex-col gap-[7px]">
                <span class="plabel">E‑mail</span>
                <input v-model="form.email" type="email" required autofocus autocomplete="email" class="pinput bg-white/75" />
            </label>
            <span v-if="form.errors.email" class="text-sm font-bold text-cb-red" role="alert">{{ form.errors.email }}</span>
            <button type="submit" class="pbtn pbtn-red mt-2" :disabled="form.processing"><span>Poslať odkaz</span><span>→</span></button>
        </form>
        <Link :href="route('login')" class="text-sm font-semibold text-cb-taupe hover:text-cb-ink">← Späť na prihlásenie</Link>
    </CampusAuthLayout>
</template>
