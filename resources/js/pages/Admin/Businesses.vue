<script setup lang="ts">
import CampusLogo from '@/components/landing/CampusLogo.vue';
import TwoFactorNudge from '@/components/panel/TwoFactorNudge.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

type Status = 'pending' | 'approved' | 'rejected';
interface Row {
    id: number;
    name: string;
    type: string;
    ico: string;
    dic: string | null;
    address: string;
    map: string | null;
    iban: string;
    billingEmail: string;
    delivery: boolean;
    dishes: number;
    owner: { name: string; email: string; phone: string | null } | null;
    registeredAt: string;
    waitingHours: number | null;
    reviewedAt: string | null;
    reason: string | null;
}

const props = defineProps<{ status: Status; counts: Record<Status, number>; businesses: Row[]; flash: string | null; suggestTwoFactor: boolean }>();

const TABS: [Status, string][] = [
    ['pending', 'Čakajú na overenie'],
    ['approved', 'Schválené'],
    ['rejected', 'Zamietnuté'],
];
const EMPTY: Record<Status, string> = {
    pending: 'Žiadna žiadosť nečaká. Keď sa zaregistruje nový podnik, príde vám e‑mail.',
    approved: 'Zatiaľ nie je schválený žiadny podnik.',
    rejected: 'Žiadny podnik nie je zamietnutý.',
};

const busy = ref<number | null>(null);
const rejecting = ref<number | null>(null);
const reject = useForm({ reason: '' });

const toast = ref(props.flash);
let timer: ReturnType<typeof setTimeout> | undefined;
watch(
    () => props.flash,
    (f) => {
        toast.value = f;
        clearTimeout(timer);
        if (f) timer = setTimeout(() => (toast.value = null), 4000);
    },
    { immediate: true },
);

const approve = (b: Row) =>
    router.post(
        route('admin.businesses.approve', b.id),
        {},
        { preserveScroll: true, onStart: () => (busy.value = b.id), onFinish: () => (busy.value = null) },
    );
const openReject = (b: Row) => {
    rejecting.value = rejecting.value === b.id ? null : b.id;
    reject.reset();
    reject.clearErrors();
};
const submitReject = (b: Row) =>
    reject.post(route('admin.businesses.reject', b.id), { preserveScroll: true, onSuccess: () => (rejecting.value = null) });

/** Sľub majiteľovi je 24 hodín – od 18 h žiadosť zvýrazníme. */
const waitTone = (h: number) =>
    h >= 24 ? 'bg-[rgba(219,59,34,.14)] text-cb-red' : h >= 18 ? 'bg-[rgba(255,122,26,.16)] text-cb-or-dark' : 'bg-cb-panel text-cb-taupe';
const waitLabel = (h: number) => (h < 1 ? 'čaká menej než hodinu' : h < 24 ? `čaká ${h} h` : `čaká ${Math.floor(h / 24)} d ${h % 24} h`);
</script>

<template>
    <Head title="Administrácia – podniky" />
    <div class="min-h-screen bg-cb-panel font-archivo text-cb-ink antialiased">
        <header class="border-b-[1.5px] border-cb-line bg-white/70">
            <div class="mx-auto flex max-w-[1000px] flex-wrap items-center justify-between gap-3 px-4 py-3.5 lg:px-6">
                <div class="flex items-center gap-2.5">
                    <CampusLogo :size="30" :text-size="19" />
                    <span class="rounded-full bg-cb-ink px-2.5 py-1 text-[11.5px] font-extrabold uppercase tracking-[.06em] text-cb-cream"
                        >Administrácia</span
                    >
                </div>
                <div class="flex items-center gap-4 text-[13.5px] font-bold">
                    <Link :href="route('two-factor.edit')" class="text-cb-taupe hover:text-cb-ink">Zabezpečenie účtu</Link>
                    <Link :href="route('logout')" method="post" as="button" class="text-cb-taupe hover:text-cb-ink">Odhlásiť sa</Link>
                </div>
            </div>
        </header>

        <main class="mx-auto flex max-w-[1000px] flex-col gap-5 px-4 pb-16 pt-6 lg:px-6 lg:pt-9">
            <TwoFactorNudge
                v-if="suggestTwoFactor"
                text="Vidíte údaje a IBAN‑y všetkých podnikov. Zapnite si dvojfaktorové overenie, nech účet správcu nechráni len heslo."
            />
            <div class="flex flex-col gap-1">
                <h1 class="m-0 text-[30px] font-black leading-none tracking-[-.03em] lg:text-[38px]">Overovanie podnikov</h1>
                <span class="text-sm text-cb-taupe">Majiteľom sľubujeme overenie do 24 hodín. Študenti vidia len schválené podniky.</span>
            </div>

            <nav class="flex flex-wrap gap-1.5" aria-label="Stav podniku">
                <Link
                    v-for="[key, label] in TABS"
                    :key="key"
                    :href="route('admin.businesses', key === 'pending' ? {} : { stav: key })"
                    class="flex h-10 items-center gap-2 rounded-full border-[1.5px] px-4 text-[14px] font-bold"
                    :class="status === key ? 'border-cb-ink bg-cb-ink text-cb-cream' : 'border-cb-line bg-white text-cb-ink'"
                    :aria-current="status === key ? 'page' : undefined"
                >
                    <span>{{ label }}</span>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-extrabold"
                        :class="
                            status === key ? 'bg-white/20' : key === 'pending' && counts[key] ? 'bg-cb-red text-white' : 'bg-cb-panel text-cb-taupe'
                        "
                        >{{ counts[key] }}</span
                    >
                </Link>
            </nav>

            <div v-if="!businesses.length" class="pcard px-5 py-8 text-center text-[15px] font-semibold text-cb-taupe">{{ EMPTY[status] }}</div>

            <article v-for="b in businesses" :key="b.id" class="pcard flex flex-col gap-4 p-[18px] lg:p-6">
                <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <span class="text-[11.5px] font-bold uppercase tracking-[.05em] text-cb-red">{{ b.type }}</span>
                        <h2 class="m-0 text-[22px] font-black leading-tight tracking-[-.02em] [overflow-wrap:anywhere]">{{ b.name }}</h2>
                        <span class="text-[13px] text-cb-taupe">Registrácia {{ b.registeredAt }}</span>
                    </div>
                    <span
                        v-if="b.waitingHours !== null"
                        class="flex h-8 items-center rounded-full px-3 text-[13px] font-extrabold"
                        :class="waitTone(b.waitingHours)"
                    >
                        {{ waitLabel(b.waitingHours) }}
                    </span>
                    <span v-else-if="b.reviewedAt" class="text-[13px] font-semibold text-cb-taupe"
                        >{{ status === 'approved' ? 'Schválené' : 'Zamietnuté' }} {{ b.reviewedAt }}</span
                    >
                </div>

                <dl class="m-0 grid grid-cols-[repeat(auto-fit,minmax(230px,1fr))] gap-x-6 gap-y-3 border-t-[1.5px] border-cb-line pt-4 text-sm">
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-[12px] font-bold uppercase tracking-[.04em] text-cb-taupe">Majiteľ</dt>
                        <dd v-if="b.owner" class="m-0 flex flex-col font-semibold">
                            <span>{{ b.owner.name }}</span>
                            <a :href="'mailto:' + b.owner.email" class="text-cb-red [overflow-wrap:anywhere]">{{ b.owner.email }}</a>
                            <a v-if="b.owner.phone" :href="'tel:' + b.owner.phone.replace(/\s/g, '')" class="text-cb-red">{{ b.owner.phone }}</a>
                        </dd>
                        <dd v-else class="m-0 font-semibold text-cb-taupe">–</dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-[12px] font-bold uppercase tracking-[.04em] text-cb-taupe">Firma</dt>
                        <dd class="m-0 flex flex-col font-semibold">
                            <span>IČO {{ b.ico }}</span>
                            <span v-if="b.dic">DIČ {{ b.dic }}</span>
                            <a
                                :href="'https://www.orsr.sk/hladaj_ico.asp?ICO=' + b.ico.replace(/\s/g, '')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-cb-red"
                                >Overiť v obchodnom registri ↗</a
                            >
                        </dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-[12px] font-bold uppercase tracking-[.04em] text-cb-taupe">Adresa</dt>
                        <dd class="m-0 flex flex-col font-semibold">
                            <span>{{ b.address }}</span>
                            <a v-if="b.map" :href="b.map" target="_blank" rel="noopener noreferrer" class="text-cb-red">Pozrieť na mape ↗</a>
                        </dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-[12px] font-bold uppercase tracking-[.04em] text-cb-taupe">Výplaty</dt>
                        <dd class="m-0 flex flex-col font-semibold">
                            <span class="[overflow-wrap:anywhere]">{{ b.iban }}</span>
                            <span class="text-cb-taupe [overflow-wrap:anywhere]">{{ b.billingEmail }}</span>
                        </dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-[12px] font-bold uppercase tracking-[.04em] text-cb-taupe">Pripravenosť</dt>
                        <dd class="m-0 flex flex-col font-semibold">
                            <span>{{ b.dishes ? 'Jedlá v menu: ' + b.dishes : 'Menu je zatiaľ prázdne' }}</span>
                            <span class="text-cb-taupe">{{ b.delivery ? 'Ponúka aj doručenie' : 'Len osobný odber' }}</span>
                        </dd>
                    </div>
                </dl>

                <div v-if="status === 'rejected' && b.reason" class="rounded-2xl bg-[rgba(219,59,34,.1)] px-4 py-3 text-sm font-semibold">
                    <span class="font-black text-cb-red">Dôvod zamietnutia:</span> {{ b.reason }}
                </div>

                <div v-if="status !== 'approved'" class="flex flex-wrap gap-2.5">
                    <button type="button" class="pbtn pbtn-red h-12 text-[15px]" :disabled="busy === b.id" @click="approve(b)">
                        <span>{{ status === 'rejected' ? 'Predsa schváliť' : 'Schváliť' }}</span
                        ><span>✓</span>
                    </button>
                    <button
                        v-if="status === 'pending'"
                        type="button"
                        class="pbtn pbtn-ghost h-12 text-[15px]"
                        :aria-expanded="rejecting === b.id"
                        @click="openReject(b)"
                    >
                        Zamietnuť
                    </button>
                </div>
                <button
                    v-else
                    type="button"
                    class="self-start text-[13.5px] font-bold text-cb-taupe hover:text-cb-red"
                    :aria-expanded="rejecting === b.id"
                    @click="openReject(b)"
                >
                    Odobrať schválenie
                </button>

                <form v-if="rejecting === b.id" class="flex flex-col gap-3 rounded-3xl bg-cb-panel p-4" @submit.prevent="submitReject(b)">
                    <label class="flex flex-col gap-[7px]">
                        <span class="plabel">Dôvod (majiteľ ho dostane e‑mailom a uvidí v paneli)</span>
                        <textarea
                            v-model="reject.reason"
                            rows="3"
                            maxlength="300"
                            required
                            class="ptextarea"
                            :class="reject.errors.reason && '!border-cb-red'"
                            placeholder="Napr. IČO sa nezhoduje s názvom podniku v obchodnom registri."
                        />
                    </label>
                    <span v-if="reject.errors.reason" class="text-sm font-bold text-cb-red" role="alert">{{ reject.errors.reason }}</span>
                    <div class="flex flex-wrap gap-2.5">
                        <button type="submit" class="pbtn pbtn-red h-12 text-[15px]" :disabled="reject.processing">
                            <span>Zamietnuť a poslať dôvod</span><span>→</span>
                        </button>
                        <button type="button" class="pbtn pbtn-ghost h-12 text-[15px]" @click="rejecting = null">Zrušiť</button>
                    </div>
                </form>
            </article>
        </main>

        <div
            v-if="toast"
            role="status"
            class="fixed bottom-5 left-1/2 z-40 max-w-[calc(100vw-32px)] -translate-x-1/2 rounded-full bg-cb-ink px-5 py-3 text-sm font-bold text-cb-cream shadow-[0_14px_30px_-12px_rgba(42,29,22,.6)]"
        >
            {{ toast }}
        </div>
    </div>
</template>
