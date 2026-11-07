<script setup lang="ts">
import CampusLogo from '@/components/landing/CampusLogo.vue';
import PChip from '@/components/panel/PChip.vue';
import PToggle from '@/components/panel/PToggle.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{ types: string[]; categories: string[]; loggedIn: boolean; done: boolean }>();

const form = useForm({
    meno: '',
    email: '',
    tel: '',
    heslo: '',
    nazov: '',
    ico: '',
    typ: [] as string[],
    zost: [] as string[],
    adresa: '',
    pokyny: 'Pri pulte povedzte, že idete po balíček z CampusBox.',
    donaska: false,
    poplatok: '1,49',
    iban: '',
    fakt: '',
    suhlas: false,
});

const step = ref(props.done ? 4 : props.loggedIn ? 1 : 0);
const firstStep = props.loggedIn ? 1 : 0;
// Inertia po odoslaní znovu použije ten istý komponent – krok „Hotovo“ preto nastavíme pri zmene prop.
watch(
    () => props.done,
    (d) => d && (step.value = 4),
);

const STEPS = ['Účet', 'Podnik a kategória', 'Adresa a výdaj', 'Výplaty', 'Hotovo'];
const COPY = [
    ['Krok 1 · Účet', 'Vytvorte si účet podniku', 'Tento účet bude mať rolu Majiteľ. Manažérov a pracovníkov pozvete neskôr.'],
    ['Krok 2 · Podnik a kategória', 'Povedzte nám o podniku', 'Kategória určí, kde vás študenti v appke nájdu.'],
    ['Krok 3 · Adresa a výdaj', 'Kde si študenti jedlo vyzdvihnú', 'Adresa sa zobrazí na mape a v detaile ponuky.'],
    ['Krok 4 · Výplaty', 'Kam vám pošleme peniaze', 'Výplaty chodia na účet každých 14 dní, bez mesačného poplatku.'],
    ['Hotovo', 'Žiadosť je odoslaná', 'Podnik overíme do 24 hodín. Medzitým si môžete pridať jedlá a pozvať tím.'],
];
type Field = [keyof typeof form, string, string?];
const FIELDS: Field[][] = [
    [
        ['meno', 'Meno a priezvisko'],
        ['email', 'Pracovný e‑mail', 'email'],
        ['tel', 'Telefón', 'tel'],
        ['heslo', 'Heslo', 'password'],
    ],
    [
        ['nazov', 'Názov podniku'],
        ['ico', 'IČO'],
    ],
    [['adresa', 'Adresa podniku']],
    [
        ['iban', 'IBAN pre výplaty'],
        ['fakt', 'E‑mail pre faktúry', 'email'],
    ],
];
const AUTOCOMPLETE: Record<string, string> = {
    meno: 'name',
    email: 'email',
    tel: 'tel',
    heslo: 'new-password',
    adresa: 'street-address',
    nazov: 'organization',
};
const TYPE_COLORS = ['#F09A72', '#EFB08A', '#F2C48D', '#D9B08C', '#F4A9A0', '#E88A5B', '#F0B65A', '#B9D7A1', '#E3C3A0'];

const error = computed(() => Object.values(form.errors)[0] ?? '');
const toggle = (key: 'typ' | 'zost', v: string) => (form[key] = form[key].includes(v) ? form[key].filter((x) => x !== v) : [...form[key], v]);

const next = () => {
    if (step.value === 4) return router.visit(route('panel.menu'));
    form.clearErrors();
    if (step.value === 3) {
        form.post(route('panel.registration.store'), { preserveScroll: true });
        return;
    }
    form.transform((d) => ({ ...d, step: step.value })).post(route('panel.registration.validate'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => step.value++,
    });
};
const back = () => {
    form.clearErrors();
    step.value = Math.max(firstStep, step.value - 1);
};
const nextLabel = computed(() => (step.value === 4 ? 'Prejsť do panela' : step.value === 3 ? 'Odoslať žiadosť' : 'Pokračovať'));
</script>

<template>
    <Head title="Registrácia podniku" />
    <div class="flex min-h-screen flex-wrap bg-[#FFF2E4] font-archivo text-cb-ink antialiased">
        <!-- Desktop: ľavý panel -->
        <div class="hidden flex-[0_0_380px] flex-col gap-7 bg-[rgba(42,29,22,.92)] px-9 py-10 text-cb-cream lg:flex">
            <div class="flex flex-col gap-1.5">
                <Link href="/" aria-label="CampusBox"><CampusLogo :size="40" :text-size="26" inverted /></Link>
                <span class="text-[13px] font-semibold text-cb-beige">pre podniky</span>
            </div>
            <span class="text-4xl font-black leading-[1.02] tracking-[-.03em] [text-wrap:pretty]">Jedlo, ktoré vám zostane, si kúpia študenti.</span>
            <ol class="m-0 flex list-none flex-col border-t-2 border-[rgba(255,246,234,.2)] p-0">
                <li
                    v-for="(l, i) in STEPS"
                    :key="l"
                    class="grid grid-cols-[44px_1fr_auto] items-center border-b-2 border-[rgba(255,246,234,.12)] py-3.5 text-[15px]"
                    :class="
                        i === step
                            ? 'font-extrabold text-cb-cream'
                            : i < step
                              ? 'font-semibold text-cb-beige'
                              : 'font-semibold text-[rgba(255,246,234,.45)]'
                    "
                    :aria-current="i === step ? 'step' : undefined"
                >
                    <span class="font-extrabold">{{ String(i + 1).padStart(2, '0') }}</span
                    ><span>{{ l }}</span
                    ><span>{{ i < step ? '✓' : '' }}</span>
                </li>
            </ol>
            <div class="flex-1" />
            <span class="text-[13px] leading-normal text-cb-beige">
                Registrácia trvá asi 5 minút. Bez mesačných poplatkov – províziu platíte len z vydaných objednávok: 10–12 % pri záchrane jedla, 5–7 %
                pri študentskom menu.
            </span>
        </div>

        <form
            class="box-border flex min-w-0 max-w-[720px] flex-[1_1_320px] flex-col gap-[18px] px-[18px] pb-5 pt-5 lg:px-14 lg:pb-10 lg:pt-14"
            @submit.prevent="next"
        >
            <div class="flex flex-col gap-2.5 lg:hidden">
                <div class="flex items-baseline justify-between">
                    <span class="flex items-center gap-2"
                        ><CampusLogo :size="28" :text-size="18" /><span class="text-[13px] font-bold text-cb-taupe">pre podniky</span></span
                    >
                    <span class="text-[13px] font-bold text-cb-taupe">Krok {{ step + 1 }} z 5</span>
                </div>
                <div class="grid grid-cols-5 gap-[5px]">
                    <div
                        v-for="(_, i) in STEPS"
                        :key="i"
                        class="h-[5px] rounded-[3px]"
                        :class="i <= step ? 'bg-cb-red' : 'bg-[rgba(120,80,50,.18)]'"
                    />
                </div>
            </div>

            <div class="mt-2 flex flex-col gap-1.5">
                <span class="text-[13px] font-bold uppercase tracking-[.06em] text-cb-red">{{ COPY[step][0] }}</span>
                <h1 class="m-0 text-[30px] font-black leading-[1.02] tracking-[-.03em] [text-wrap:pretty] lg:text-[40px]">{{ COPY[step][1] }}</h1>
                <span class="text-[15px] leading-[1.45] text-cb-taupe [text-wrap:pretty]">{{ COPY[step][2] }}</span>
            </div>

            <div v-if="step < 4" class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-3.5">
                <label v-for="[k, l, type] in FIELDS[step]" :key="k" class="flex flex-col gap-[7px]">
                    <span class="plabel">{{ l }}</span>
                    <input
                        v-model="form[k] as string"
                        :type="type ?? 'text'"
                        :autocomplete="AUTOCOMPLETE[k] ?? 'off'"
                        class="pinput bg-white/75"
                        :class="form.errors[k] && '!border-cb-red'"
                        :aria-invalid="!!form.errors[k]"
                    />
                </label>
            </div>

            <template v-if="step === 1">
                <div class="flex flex-col gap-2.5">
                    <span class="flex flex-col gap-0.5"
                        ><span class="plabel">Typ podniku</span
                        ><span class="text-[12.5px] text-cb-taupe">Môžete vybrať viac, napr. bistro aj pizzeria.</span></span
                    >
                    <div class="grid grid-cols-[repeat(auto-fill,minmax(140px,1fr))] gap-2">
                        <button
                            v-for="(t, i) in types"
                            :key="t"
                            type="button"
                            class="box-border flex h-16 items-center gap-2.5 rounded-[20px] border-[1.5px] px-3.5 text-left text-sm font-bold"
                            :class="form.typ.includes(t) ? 'border-cb-ink bg-cb-ink text-cb-cream' : 'border-cb-line bg-white'"
                            :aria-pressed="form.typ.includes(t)"
                            @click="toggle('typ', t)"
                        >
                            <span class="h-7 w-7 flex-none rounded-full" :style="{ background: TYPE_COLORS[i] }" />{{
                                form.typ.includes(t) ? '✓ ' : ''
                            }}{{ t }}
                        </button>
                    </div>
                </div>
                <div class="flex flex-col gap-2.5">
                    <span class="flex flex-col gap-0.5"
                        ><span class="plabel">Čo vám zvyčajne zostáva</span
                        ><span class="text-[12.5px] text-cb-taupe">Podľa toho vás študenti nájdu v kategóriách v appke.</span></span
                    >
                    <div class="flex flex-wrap gap-2">
                        <PChip v-for="z in categories" :key="z" size="lg" :on="form.zost.includes(z)" @click="toggle('zost', z)"
                            >{{ form.zost.includes(z) ? '✓ ' : '' }}{{ z }}</PChip
                        >
                    </div>
                </div>
            </template>

            <template v-if="step === 2">
                <div class="relative h-[150px] overflow-hidden rounded-3xl bg-[#F6E3CF]" aria-hidden="true">
                    <div class="absolute -left-5 -right-5 top-[46%] h-4 -rotate-[5deg] bg-white/80" />
                    <div class="absolute -bottom-5 -top-5 left-[38%] w-3.5 -rotate-[8deg] bg-white/80" />
                    <div
                        class="absolute left-[40%] top-[40%] flex h-8 -translate-x-1/2 -translate-y-full items-center rounded-full bg-cb-red px-3 text-[13px] font-extrabold text-white"
                    >
                        {{ form.nazov || 'Váš podnik' }}
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="plabel">Otváracie hodiny</span>
                    <div class="pcard rounded-[20px] px-4 py-1">
                        <div class="flex justify-between border-b-[1.5px] border-cb-line py-3 text-[15px]">
                            <span class="font-semibold">Po – Pi</span><span class="font-bold">10:00 – 21:00</span>
                        </div>
                        <div class="flex justify-between border-b-[1.5px] border-cb-line py-3 text-[15px]">
                            <span class="font-semibold">Sobota</span><span class="font-bold">11:00 – 20:00</span>
                        </div>
                        <div class="flex justify-between py-3 text-[15px]">
                            <span class="font-semibold">Nedeľa</span><span class="font-bold text-cb-taupe">Zatvorené</span>
                        </div>
                    </div>
                    <span class="text-[12.5px] text-cb-taupe">Hodiny upravíte po registrácii v profile podniku.</span>
                </div>
                <label class="flex cursor-pointer items-center gap-3 rounded-[20px] bg-white px-4 py-3.5">
                    <PToggle v-model="form.donaska" label="Ponúkať aj doručenie" />
                    <span class="flex flex-col gap-0.5">
                        <span class="text-[15px] font-extrabold">Ponúkať aj doručenie</span>
                        <span class="text-[12.5px] text-cb-taupe">Osobný odber je vždy dostupný. Okruh nastavíte neskôr.</span>
                    </span>
                </label>
                <label v-if="form.donaska" class="flex flex-col gap-[7px]">
                    <span class="plabel">Cena doručenia pre študenta (€)</span>
                    <input
                        v-model="form.poplatok"
                        inputmode="decimal"
                        placeholder="napr. 1,49 alebo 0 pre zadarmo"
                        class="pinput bg-white/75 font-bold"
                    />
                </label>
                <label class="flex flex-col gap-[7px]">
                    <span class="plabel">Pokyny k výdaju pre študentov</span>
                    <textarea v-model="form.pokyny" rows="2" class="ptextarea bg-white/75" />
                </label>
            </template>

            <template v-if="step === 3">
                <div class="pcard flex flex-col gap-2.5 rounded-3xl px-5 py-[18px]">
                    <div class="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-3.5">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[32px] font-black tracking-[-.03em]">10–12 %</span
                            ><span class="text-[13px] text-cb-taupe">provízia · záchrana nepredaného jedla</span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[32px] font-black tracking-[-.03em]">5–7 %</span
                            ><span class="text-[13px] text-cb-taupe">provízia · študentské menu</span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[32px] font-black tracking-[-.03em]">0 €</span
                            ><span class="text-[13px] text-cb-taupe">mesačný poplatok</span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[32px] font-black tracking-[-.03em]">14 dní</span
                            ><span class="text-[13px] text-cb-taupe">interval výplat</span>
                        </div>
                    </div>
                </div>
                <label class="grid cursor-pointer grid-cols-[28px_1fr] items-start gap-3 text-[14.5px] leading-[1.45]">
                    <input v-model="form.suhlas" type="checkbox" class="peer sr-only" />
                    <span
                        class="box-border flex h-[26px] w-[26px] items-center justify-center rounded-[9px] border-2 text-[15px] font-black text-white peer-focus-visible:ring-2 peer-focus-visible:ring-cb-ink"
                        :class="form.suhlas ? 'border-cb-green bg-cb-green' : 'border-cb-taupe bg-white'"
                        aria-hidden="true"
                        >{{ form.suhlas ? '✓' : '' }}</span
                    >
                    <span
                        >Súhlasím s obchodnými podmienkami a s províziou z vydaných objednávok: 11 % pri záchrane jedla a 6 % pri študentskom menu
                        (pilotná prevádzka Banská Bystrica).</span
                    >
                </label>
            </template>

            <div v-if="step === 4" class="pcard flex flex-col rounded-3xl px-5 py-1">
                <div class="grid grid-cols-[32px_1fr_auto] items-center gap-2.5 border-b-[1.5px] border-cb-line py-3.5 text-[15px]">
                    <span class="font-black text-cb-green">✓</span><span class="font-semibold">Účet majiteľa</span
                    ><span class="text-[13px] text-cb-taupe">vytvorený</span>
                </div>
                <div class="grid grid-cols-[32px_1fr_auto] items-center gap-2.5 border-b-[1.5px] border-cb-line py-3.5 text-[15px]">
                    <span class="font-black text-cb-green">✓</span><span class="font-semibold">Podnik a menu</span
                    ><span class="text-[13px] text-cb-taupe">pripravené</span>
                </div>
                <div class="grid grid-cols-[32px_1fr_auto] items-center gap-2.5 border-b-[1.5px] border-cb-line py-3.5 text-[15px]">
                    <span class="font-black text-cb-green">✓</span><span class="font-semibold">Výplaty nastavené</span
                    ><span class="text-[13px] text-cb-taupe">IBAN</span>
                </div>
                <div class="grid grid-cols-[32px_1fr_auto] items-center gap-2.5 py-3.5 text-[15px]">
                    <span class="font-black text-cb-or">◷</span><span class="font-semibold">Overenie podniku</span
                    ><span class="text-[13px] font-bold text-cb-or">do 24 h</span>
                </div>
            </div>

            <span v-if="error" class="text-sm font-bold text-cb-red" role="alert">{{ error }}</span>
            <div class="flex-1" />
            <div class="flex flex-wrap gap-2.5 pb-2">
                <button v-if="step > firstStep && step < 4" type="button" class="pbtn pbtn-ghost" @click="back">Späť</button>
                <button type="submit" class="pbtn pbtn-red flex-[1_1_200px]" :disabled="form.processing">
                    <span>{{ nextLabel }}</span
                    ><span>→</span>
                </button>
            </div>
            <Link v-if="step === 0" :href="route('login')" class="text-sm font-semibold text-cb-taupe hover:text-cb-ink"
                >Už máte účet? Prihlásiť sa</Link
            >
        </form>
    </div>
</template>
