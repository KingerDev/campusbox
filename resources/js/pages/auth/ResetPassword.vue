<script setup lang="ts">
import CampusAuthLayout from '@/layouts/CampusAuthLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ token: string; email: string }>();
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' });
const submit = () => form.post(route('password.store'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <CampusAuthLayout title="Nové heslo" sub="Zvoľte si heslo s aspoň 8 znakmi.">
        <form class="flex flex-col gap-3.5" @submit.prevent="submit">
            <label class="flex flex-col gap-[7px]"
                ><span class="plabel">E‑mail</span><input v-model="form.email" type="email" readonly class="pinput bg-white/50"
            /></label>
            <label class="flex flex-col gap-[7px]"
                ><span class="plabel">Nové heslo</span
                ><input v-model="form.password" type="password" required autofocus autocomplete="new-password" class="pinput bg-white/75"
            /></label>
            <label class="flex flex-col gap-[7px]"
                ><span class="plabel">Heslo ešte raz</span
                ><input v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="pinput bg-white/75"
            /></label>
            <span v-for="e in Object.values(form.errors)" :key="e" class="text-sm font-bold text-cb-red" role="alert">{{ e }}</span>
            <button type="submit" class="pbtn pbtn-red mt-2" :disabled="form.processing"><span>Uložiť heslo</span><span>→</span></button>
        </form>
    </CampusAuthLayout>
</template>
