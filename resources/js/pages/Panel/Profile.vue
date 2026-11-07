<script setup lang="ts">
import PChip from '@/components/panel/PChip.vue';
import PToggle from '@/components/panel/PToggle.vue';
import { eur } from '@/composables/usePanel';
import PanelLayout from '@/layouts/PanelLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Hours = [string, string, string, boolean][];
interface ProfileData {
    name: string;
    description: string;
    types: string[];
    categories: string[];
    phone: string;
    public_email: string;
    web: string;
    instagram: string;
    facebook: string;
    linkedin: string;
    address: string;
    pickup_instructions: string;
    opening_hours: Hours;
    ico?: string;
    dic?: string;
    iban?: string;
    billing_email?: string;
}

const props = defineProps<{
    profile: ProfileData;
    cover: string | null;
    logo: string | null;
    canBill: boolean;
    delivery: { on: boolean; fee: number };
    types: string[];
    categories: string[];
}>();

const form = useForm<ProfileData>(JSON.parse(JSON.stringify(props.profile)));

const toggle = (key: 'types' | 'categories', v: string) => {
    const list = form[key];
    form[key] = list.includes(v) ? (list.length > 1 ? list.filter((x) => x !== v) : list) : [...list, v];
};
const copyMonday = () => {
    const [, o, c, closed] = form.opening_hours[0];
    for (let i = 1; i <= 4; i++) form.opening_hours[i] = [form.opening_hours[i][0], o, c, closed];
};
const save = () => form.put(route('panel.profile.update'), { preserveScroll: true, onSuccess: () => form.defaults() });
const discard = () => form.reset();

const CONTACT: [keyof ProfileData, string, string][] = [
    ['phone', 'Telefón pre študentov', ''],
    ['public_email', 'E‑mail pre študentov', ''],
    ['web', 'Web', 'napr. mojpodnik.sk'],
    ['instagram', 'Instagram', '@mojpodnik'],
    ['facebook', 'Facebook', 'facebook.com/mojpodnik'],
    ['linkedin', 'LinkedIn', 'linkedin.com/company/mojpodnik'],
];
const BILL: [keyof ProfileData, string][] = [
    ['ico', 'IČO'],
    ['dic', 'DIČ'],
    ['iban', 'IBAN pre výplaty'],
    ['billing_email', 'E‑mail pre faktúry'],
];

// ---- obrázky ----
const uploading = ref<string | null>(null);
const upload = (kind: 'cover' | 'logo', e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    uploading.value = kind;
    router.post(
        route('panel.profile.upload'),
        { kind, image: file },
        { forceFormData: true, preserveScroll: true, onFinish: () => (uploading.value = null) },
    );
};

const today = computed(() => form.opening_hours[(new Date().getDay() + 6) % 7]);
const prevToday = computed(() => (today.value[3] ? 'Dnes zatvorené' : `Dnes ${today.value[1]}–${today.value[2]}`));
const prevDel = computed(() =>
    props.delivery.on ? 'Osobný odber · Doručenie ' + (props.delivery.fee ? '+' + eur(props.delivery.fee) : 'zadarmo') : 'Len osobný odber',
);
const errorList = computed(() => Object.values(form.errors));
</script>

<template>
    <PanelLayout title="Profil podniku" sub="Takto vás vidia študenti v appke">
        <div
            class="relative mb-[30px] h-[170px] rounded-[30px] bg-[#F09A72] bg-cover bg-center"
            :style="
                cover
                    ? { backgroundImage: `url(${cover})` }
                    : { backgroundImage: 'repeating-linear-gradient(135deg,rgba(255,255,255,.22) 0 14px,transparent 14px 28px)' }
            "
        >
            <span v-if="!cover" class="absolute left-4 top-3.5 rounded-[7px] bg-white/75 px-2 py-[3px] font-mono text-[11px]"
                >titulná fotka · interiér alebo jedlo</span
            >
            <label
                class="absolute right-3.5 top-3.5 flex h-[38px] cursor-pointer items-center rounded-full bg-white/75 px-4 text-[13px] font-bold backdrop-blur-xl"
            >
                {{ uploading === 'cover' ? 'Nahrávam…' : 'Zmeniť fotku' }}
                <input type="file" accept="image/*" class="sr-only" @change="upload('cover', $event)" />
            </label>
            <label
                class="absolute -bottom-[30px] left-5 box-border flex h-[84px] w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full border-4 border-[#FFF2E4] bg-cb-ink text-[13px] font-black text-cb-cream"
                title="Zmeniť logo"
            >
                <img v-if="logo" :src="logo" alt="Logo podniku" class="h-full w-full object-cover" />
                <span v-else>{{ uploading === 'logo' ? '…' : 'logo' }}</span>
                <input type="file" accept="image/*" class="sr-only" @change="upload('logo', $event)" />
            </label>
        </div>

        <div class="flex flex-wrap items-start gap-4">
            <div class="flex min-w-0 flex-[1_1_380px] flex-col gap-3.5">
                <div class="pcard flex flex-col gap-3.5 p-[18px]">
                    <span class="text-[17px] font-extrabold">Základné údaje</span>
                    <label class="flex flex-col gap-[7px]"
                        ><span class="plabel">Názov podniku</span><input v-model="form.name" class="pinput"
                    /></label>
                    <label class="flex flex-col gap-[7px]">
                        <span class="flex justify-between text-[13px] font-bold"
                            ><span>Krátky popis</span><span class="font-semibold text-cb-taupe">{{ form.description.length }} / 200</span></span
                        >
                        <textarea v-model="form.description" maxlength="200" rows="3" class="ptextarea" />
                    </label>
                    <div class="flex flex-col gap-2">
                        <span class="flex flex-col gap-0.5"
                            ><span class="plabel">Typ podniku</span><span class="text-[12.5px] text-cb-taupe">Môžete vybrať viac.</span></span
                        >
                        <div class="flex flex-wrap gap-1.5">
                            <PChip v-for="t in types" :key="t" :on="form.types.includes(t)" @click="toggle('types', t)">{{ t }}</PChip>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <span class="flex flex-col gap-0.5"
                            ><span class="plabel">Kategórie v appke</span
                            ><span class="text-[12.5px] text-cb-taupe">V týchto kategóriách vás študenti nájdu.</span></span
                        >
                        <div class="flex flex-wrap gap-1.5">
                            <PChip v-for="c in categories" :key="c" :on="form.categories.includes(c)" @click="toggle('categories', c)">{{ c }}</PChip>
                        </div>
                    </div>
                </div>

                <div class="pcard flex flex-col gap-3.5 p-[18px]">
                    <span class="text-[17px] font-extrabold">Kontakt</span>
                    <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3">
                        <label v-for="[k, l, ph] in CONTACT" :key="k" class="flex flex-col gap-[7px]">
                            <span class="plabel">{{ l }}</span
                            ><input v-model="form[k] as string" :placeholder="ph" class="pinput" />
                        </label>
                    </div>
                </div>

                <div class="pcard flex flex-col gap-3.5 p-[18px]">
                    <span class="text-[17px] font-extrabold">Adresa a výdaj</span>
                    <label class="flex flex-col gap-[7px]"><span class="plabel">Adresa</span><input v-model="form.address" class="pinput" /></label>
                    <label class="flex flex-col gap-[7px]"
                        ><span class="plabel">Pokyny k výdaju</span><textarea v-model="form.pickup_instructions" rows="2" class="ptextarea" />
                    </label>
                    <span class="text-[13px] text-cb-taupe"
                        >Doručenie, cenu a okruh nastavíte v
                        <Link :href="route('panel.offer')" class="font-bold text-cb-red">Dnešnej ponuke</Link>.</span
                    >
                </div>

                <div class="pcard flex flex-col gap-2.5 p-[18px]">
                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        <span class="text-[17px] font-extrabold">Otváracie hodiny</span>
                        <button type="button" class="text-[13px] font-bold text-cb-red" @click="copyMonday">Skopírovať pondelok na Ut–Pi</button>
                    </div>
                    <div class="flex flex-col">
                        <div
                            v-for="(h, i) in form.opening_hours"
                            :key="h[0]"
                            class="flex flex-wrap items-center gap-x-3 gap-y-2 py-2.5"
                            :class="i < 6 && 'border-b-[1.5px] border-cb-line'"
                        >
                            <span class="flex-[0_0_92px] text-[14.5px] font-bold">{{ h[0] }}</span>
                            <PToggle :model-value="!h[3]" small :label="h[0] + ' otvorené'" @update:model-value="h[3] = !$event" />
                            <span v-if="!h[3]" class="flex items-center gap-1.5">
                                <input v-model="h[1]" placeholder="10:00" class="pinput h-[38px] w-[70px] rounded-xl px-2.5 text-sm font-bold" />
                                <span class="text-cb-taupe">–</span>
                                <input v-model="h[2]" placeholder="21:00" class="pinput h-[38px] w-[70px] rounded-xl px-2.5 text-sm font-bold" />
                            </span>
                            <span v-else class="text-sm font-semibold text-cb-taupe">Zatvorené</span>
                        </div>
                    </div>
                </div>

                <div v-if="canBill" class="pcard flex flex-col gap-3.5 p-[18px]">
                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        <span class="text-[17px] font-extrabold">Fakturácia a výplaty</span>
                        <span class="flex h-[26px] items-center rounded-full bg-[rgba(120,80,50,.12)] px-2.5 text-xs font-extrabold text-cb-taupe"
                            >Vidí len majiteľ</span
                        >
                    </div>
                    <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3">
                        <label v-for="[k, l] in BILL" :key="k" class="flex flex-col gap-[7px]"
                            ><span class="plabel">{{ l }}</span
                            ><input v-model="form[k] as string" class="pinput"
                        /></label>
                    </div>
                </div>
            </div>

            <div class="sticky top-4 flex min-w-[260px] flex-[0_1_320px] flex-col gap-2.5">
                <span class="text-xs font-bold uppercase tracking-[.06em] text-cb-taupe">Náhľad v appke</span>
                <div class="pcard overflow-hidden">
                    <div
                        class="relative h-[110px] bg-[#F09A72] bg-cover bg-center"
                        :style="
                            cover
                                ? { backgroundImage: `url(${cover})` }
                                : { backgroundImage: 'repeating-linear-gradient(135deg,rgba(255,255,255,.22) 0 10px,transparent 10px 20px)' }
                        "
                    >
                        <div
                            class="absolute -bottom-[22px] left-3.5 box-border h-12 w-12 overflow-hidden rounded-full border-[3px] border-white bg-cb-ink"
                        >
                            <img v-if="logo" :src="logo" alt="" class="h-full w-full object-cover" />
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5 px-4 pb-4 pt-[30px]">
                        <span class="text-[11.5px] font-bold uppercase tracking-[.05em] text-cb-red">{{ form.types.join(' · ') }}</span>
                        <span class="text-[19px] font-extrabold tracking-[-.01em]">{{ form.name }}</span>
                        <span class="text-[13px] leading-[1.45] text-cb-taupe">{{ form.description }}</span>
                        <div class="mt-1 flex flex-col gap-1 border-t-[1.5px] border-cb-line pt-2.5 text-[13px]">
                            <span class="font-bold">{{ prevToday }}</span>
                            <span>{{ form.address }}</span>
                            <span class="text-cb-taupe">{{ prevDel }}</span>
                            <span class="text-cb-taupe">{{ form.categories.join(' · ') }}</span>
                            <span v-if="form.instagram" class="text-cb-taupe">{{ form.instagram }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="form.isDirty || errorList.length"
            class="sticky bottom-[92px] z-10 flex flex-wrap items-center justify-between gap-2.5 rounded-[28px] bg-[rgba(42,29,22,.9)] py-2.5 pl-[18px] pr-2.5 text-cb-cream backdrop-blur-[20px] lg:bottom-4"
        >
            <span class="text-[14.5px] font-bold">{{ errorList[0] ?? 'Máte neuložené zmeny' }}</span>
            <span class="flex gap-2">
                <button type="button" class="flex h-[42px] items-center rounded-full bg-white/[.14] px-4 text-sm font-bold" @click="discard">
                    Zahodiť
                </button>
                <button
                    type="button"
                    class="flex h-[42px] items-center rounded-full bg-cb-red px-5 text-sm font-bold text-white disabled:opacity-60"
                    :disabled="form.processing"
                    @click="save"
                >
                    Uložiť zmeny
                </button>
            </span>
        </div>
    </PanelLayout>
</template>
