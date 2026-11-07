<script setup lang="ts">
import CampusAuthLayout from '@/layouts/CampusAuthLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{ token: string; business: string; role: string; email: string; hasAccount: boolean; loggedInAs: string | null }>();

const form = useForm({ name: '', password: '', password_confirmation: '' });
const accept = () => form.post(route('panel.invitation.accept', props.token));
const logout = () => router.post(route('logout'), {}, { onSuccess: () => router.visit(route('panel.invitation', props.token)) });
</script>

<template>
    <CampusAuthLayout
        :title="'Pozvánka do tímu ' + business"
        kicker="Panel pre podniky"
        :sub="`Pozvali vás s rolou ${role.toLowerCase()} pre ${email}.`"
    >
        <template v-if="loggedInAs && loggedInAs.toLowerCase() === email.toLowerCase()">
            <button type="button" class="pbtn pbtn-red" :disabled="form.processing" @click="accept">
                <span>Prijať pozvánku</span><span>→</span>
            </button>
        </template>
        <template v-else-if="loggedInAs">
            <p class="m-0 text-[15px] leading-[1.45]">
                Ste prihlásený ako <b>{{ loggedInAs }}</b
                >, ale pozvánka patrí účtu <b>{{ email }}</b
                >.
            </p>
            <button type="button" class="pbtn pbtn-ghost" @click="logout"><span>Odhlásiť sa a pokračovať</span><span>→</span></button>
        </template>
        <template v-else-if="hasAccount">
            <p class="m-0 text-[15px] leading-[1.45]">Pre e‑mail {{ email }} už účet máte. Prihláste sa a pozvánku prijmite.</p>
            <Link :href="route('login')" class="pbtn pbtn-red"><span>Prihlásiť sa</span><span>→</span></Link>
        </template>
        <form v-else class="flex flex-col gap-3.5" @submit.prevent="accept">
            <label class="flex flex-col gap-[7px]"
                ><span class="plabel">Meno a priezvisko</span><input v-model="form.name" required autocomplete="name" class="pinput bg-white/75"
            /></label>
            <label class="flex flex-col gap-[7px]"
                ><span class="plabel">Heslo</span
                ><input v-model="form.password" type="password" required autocomplete="new-password" class="pinput bg-white/75"
            /></label>
            <label class="flex flex-col gap-[7px]"
                ><span class="plabel">Heslo ešte raz</span
                ><input v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="pinput bg-white/75"
            /></label>
            <span v-for="e in Object.values(form.errors)" :key="e" class="text-sm font-bold text-cb-red" role="alert">{{ e }}</span>
            <button type="submit" class="pbtn pbtn-red mt-2" :disabled="form.processing"><span>Vytvoriť účet a prijať</span><span>→</span></button>
        </form>
    </CampusAuthLayout>
</template>
