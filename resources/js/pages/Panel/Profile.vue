<script setup lang="ts">
import MapPicker from '@/components/panel/MapPicker.vue';
import OpeningHoursEditor from '@/components/panel/OpeningHoursEditor.vue';
import PChip from '@/components/panel/PChip.vue';
import PhotoEditor from '@/components/panel/PhotoEditor.vue';
import { eur } from '@/composables/usePanel';
import PanelLayout from '@/layouts/PanelLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Hours = [string, string, string, boolean][];
interface ProfileData {
    name: string;
    description: string;
    type: string;
    phone: string;
    public_email: string;
    web: string;
    instagram: string;
    facebook: string;
    linkedin: string;
    address: string;
    latitude: number | null;
    longitude: number | null;
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
// Vybraný obrázok ide najprv do editora (orez, filter, náhľad v appke), nahrá sa až výsledok.
const editing = ref<{ kind: 'cover' | 'logo'; src: string } | null>(null);
const pick = (kind: 'cover' | 'logo', e: Event) => {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = ''; // aby sa dal ten istý súbor vybrať znova
    if (file) editing.value = { kind, src: URL.createObjectURL(file) };
};
const upload = (file: File) => {
    if (!editing.value) return;
    const kind = editing.value.kind;
    uploading.value = kind;
    router.post(
        route('panel.profile.upload'),
        { kind, image: file },
        { forceFormData: true, preserveScroll: true, onSuccess: () => (editing.value = null), onFinish: () => (uploading.value = null) },
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
                <input type="file" accept="image/*" class="sr-only" @change="pick('cover', $event)" />
            </label>
            <label
                class="absolute -bottom-[30px] left-5 box-border flex h-[84px] w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full border-4 border-[#FFF2E4] bg-cb-ink text-[13px] font-black text-cb-cream"
                title="Zmeniť logo"
            >
                <img v-if="logo" :src="logo" alt="Logo podniku" class="h-full w-full object-cover" />
                <span v-else>{{ uploading === 'logo' ? '…' : 'logo' }}</span>
                <input type="file" accept="image/*" class="sr-only" @change="pick('logo', $event)" />
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
                            ><span class="plabel">Typ podniku</span
                            ><span class="text-[12.5px] text-cb-taupe">Vyberte jeden, ktorý vás vystihuje najlepšie.</span></span
                        >
                        <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="Typ podniku">
                            <PChip v-for="t in types" :key="t" :on="form.type === t" @click="form.type = t">{{ t }}</PChip>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <span class="flex flex-col gap-0.5"
                            ><span class="plabel">Kategórie v appke</span
                            ><span class="text-[12.5px] text-cb-taupe"
                                >Nevyberajú sa – berieme ich z jedál vo vašom menu. Pridáte polievku a študenti vás nájdu medzi polievkami.</span
                            ></span
                        >
                        <div v-if="categories.length" class="flex flex-wrap gap-1.5">
                            <span
                                v-for="c in categories"
                                :key="c"
                                class="flex h-[34px] items-center rounded-full bg-cb-panel px-3.5 text-[13.5px] font-bold"
                                >{{ c }}</span
                            >
                        </div>
                        <span v-else class="text-[13.5px] font-semibold text-cb-or-dark">Zatiaľ žiadne – pridajte prvé jedlo do menu.</span>
                        <Link :href="route('panel.menu')" class="self-start text-[13px] font-bold text-cb-red">Upraviť menu →</Link>
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
                    <MapPicker
                        :model-value="{ lat: form.latitude, lng: form.longitude }"
                        :address="form.address"
                        :label="form.name"
                        :error="form.errors.latitude || form.errors.longitude"
                        @update:model-value="(p) => ((form.latitude = p.lat), (form.longitude = p.lng), form.clearErrors('latitude', 'longitude'))"
                    />
                    <label class="flex flex-col gap-[7px]"
                        ><span class="plabel">Pokyny k výdaju</span><textarea v-model="form.pickup_instructions" rows="2" class="ptextarea" />
                    </label>
                    <span class="text-[13px] text-cb-taupe"
                        >Doručenie, cenu a okruh nastavíte v
                        <Link :href="route('panel.offer')" class="font-bold text-cb-red">Dnešnej ponuke</Link>.</span
                    >
                </div>

                <div class="pcard flex flex-col gap-1 p-[18px]">
                    <span class="text-[17px] font-extrabold">Otváracie hodiny</span>
                    <OpeningHoursEditor v-model="form.opening_hours" />
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
                        <span class="text-[11.5px] font-bold uppercase tracking-[.05em] text-cb-red">{{ form.type }}</span>
                        <span class="text-[19px] font-extrabold tracking-[-.01em]">{{ form.name }}</span>
                        <span class="text-[13px] leading-[1.45] text-cb-taupe">{{ form.description }}</span>
                        <div class="mt-1 flex flex-col gap-1 border-t-[1.5px] border-cb-line pt-2.5 text-[13px]">
                            <span class="font-bold">{{ prevToday }}</span>
                            <span>{{ form.address }}</span>
                            <span class="text-cb-taupe">{{ prevDel }}</span>
                            <span v-if="categories.length" class="text-cb-taupe">{{ categories.join(' · ') }}</span>
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
        <PhotoEditor
            v-if="editing"
            :key="editing.src"
            :src="editing.src"
            :kind="editing.kind"
            :name="form.name"
            :type="form.type"
            :cover="cover"
            :logo="logo"
            :busy="uploading !== null"
            @apply="upload"
            @cancel="editing = null"
        />
    </PanelLayout>
</template>
