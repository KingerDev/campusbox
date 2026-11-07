<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PChip from './PChip.vue';
import PToggle from './PToggle.vue';

export interface OptionGroup {
    q: string;
    multi: boolean;
    req: boolean;
    max: number | string | null;
    opts: { n: string; p: string }[];
}
export interface MenuDish {
    id: number;
    sectionId: number;
    name: string;
    category: string;
    description: string | null;
    price: number;
    allergens: string[];
    groups: OptionGroup[];
    vegetarian: boolean;
    popular: boolean;
    photo: string | null;
    ph: string;
    today: number;
}

const props = defineProps<{
    dish: MenuDish | null;
    sectionId: number;
    sections: { id: number; name: string }[];
    categories: string[];
    allergens: string[];
    placeholders: Record<string, string>;
}>();
const emit = defineEmits<{ close: [] }>();

const d = props.dish;
const form = useForm({
    name: d?.name ?? '',
    sectionId: d?.sectionId ?? props.sectionId,
    category: d?.category ?? 'Teplé jedlo',
    description: d?.description ?? '',
    price: d ? d.price.toFixed(2).replace('.', ',') : '',
    vegetarian: d?.vegetarian ?? false,
    popular: d?.popular ?? false,
    allergens: [...(d?.allergens ?? [])],
    groups: JSON.parse(JSON.stringify(d?.groups ?? [])) as OptionGroup[],
    photo: null as File | null,
});

const tab = ref<'zaklad' | 'alg' | 'opt'>('zaklad');
const preview = ref<string | null>(d?.photo ?? null);
const onPhoto = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    form.photo = file;
    preview.value = URL.createObjectURL(file);
};

const toggleAlg = (a: string) => (form.allergens = form.allergens.includes(a) ? form.allergens.filter((x) => x !== a) : [...form.allergens, a]);
const addGroup = () => form.groups.push({ q: '', multi: false, req: true, max: null, opts: [{ n: '', p: '' }] });
const rule = (g: OptionGroup) =>
    (g.req ? 'Povinné' : 'Voliteľné') +
    ' · ' +
    (g.multi ? (g.max ? 'vyberte najviac ' + g.max : 'vyberte ľubovoľne') : 'vyberte 1') +
    ' z ' +
    g.opts.length;

const errorList = computed(() => Object.values(form.errors));

const save = () => {
    const opts = { forceFormData: true, preserveScroll: true, onSuccess: () => emit('close') };
    form.transform((data) => ({
        ...data,
        groups: data.groups.map((g) => ({ ...g, max: g.multi && g.max ? Number(g.max) : null })),
        ...(props.dish ? { _method: 'put' } : {}),
    }));
    props.dish ? form.post(route('panel.dishes.update', props.dish.id), opts) : form.post(route('panel.dishes.store'), opts);
};

const confirmDelete = ref(false);
const destroy = () => {
    if (!props.dish) return;
    if (!confirmDelete.value) return (confirmDelete.value = true);
    router.delete(route('panel.dishes.destroy', props.dish.id), { preserveScroll: true, onSuccess: () => emit('close') });
};
</script>

<template>
    <div
        class="fixed inset-0 z-[60] box-border flex items-end justify-center bg-[rgba(42,29,22,.32)] p-3 backdrop-blur-[6px] sm:items-center sm:p-6"
        @click.self="emit('close')"
    >
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="dish-title"
            class="pglass box-border flex max-h-full min-h-[min(640px,100%)] w-full max-w-[680px] flex-col gap-4 overflow-y-auto rounded-[30px] p-[22px]"
        >
            <div class="flex items-center justify-between">
                <span id="dish-title" class="text-2xl font-black tracking-[-.02em]">{{ dish ? 'Upraviť jedlo' : 'Nové jedlo' }}</span>
                <button
                    type="button"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-white/80 text-lg font-bold"
                    aria-label="Zavrieť"
                    @click="emit('close')"
                >
                    ✕
                </button>
            </div>

            <div role="tablist" class="-mt-1 flex gap-1 overflow-x-auto border-b-[1.5px] border-cb-line">
                <button
                    v-for="[k, l, n] in [
                        ['zaklad', 'Základ', ''],
                        ['alg', 'Alergény', form.allergens.length || ''],
                        ['opt', 'Možnosti pre zákazníka', form.groups.length || ''],
                    ] as const"
                    :key="k"
                    role="tab"
                    type="button"
                    :aria-selected="tab === k"
                    class="-mb-[1.5px] box-border flex h-11 flex-none items-center gap-1.5 border-b-[3px] px-3 text-sm font-extrabold"
                    :class="tab === k ? 'border-cb-red text-cb-red' : 'border-transparent'"
                    @click="tab = k"
                >
                    {{ l }}<span class="text-xs font-bold text-cb-taupe">{{ n }}</span>
                </button>
            </div>

            <div v-show="tab === 'zaklad'" class="flex flex-col gap-4">
                <label
                    class="relative flex h-[120px] cursor-pointer flex-col items-start justify-end gap-0.5 overflow-hidden rounded-[22px] border-2 border-dashed border-[rgba(120,80,50,.35)] p-3.5"
                    :style="{
                        backgroundColor: placeholders[form.category] ?? '#E3C3A0',
                        backgroundImage: 'repeating-linear-gradient(135deg,rgba(255,255,255,.25) 0 10px,transparent 10px 20px)',
                    }"
                >
                    <img v-if="preview" :src="preview" alt="" class="absolute inset-0 h-full w-full object-cover" />
                    <span class="relative rounded-lg bg-white/80 px-2 py-0.5 text-sm font-extrabold">{{
                        preview ? 'Zmeniť fotku' : 'Nahrať fotku jedla'
                    }}</span>
                    <span v-if="!preview" class="relative text-xs text-cb-brown">Pekná fotka zvýši predaj. JPG alebo PNG.</span>
                    <input type="file" accept="image/*" class="sr-only" @change="onPhoto" />
                </label>
                <label class="flex flex-col gap-[7px]"
                    ><span class="plabel">Názov jedla</span><input v-model="form.name" class="pinput bg-white" placeholder="napr. Kurací rizoto"
                /></label>
                <div class="flex flex-col gap-2">
                    <span class="plabel">Sekcia v menu</span>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <PChip v-for="s in sections" :key="s.id" :on="form.sectionId === s.id" @click="form.sectionId = s.id">{{ s.name }}</PChip>
                        <button
                            type="button"
                            class="ml-auto box-border flex h-[38px] items-center rounded-full border-[1.5px] border-cb-ink px-3.5 text-[13.5px] font-bold"
                            :class="form.popular ? 'bg-cb-ink text-cb-cream' : 'bg-white'"
                            :aria-pressed="form.popular"
                            @click="form.popular = !form.popular"
                        >
                            {{ form.popular ? '★ Obľúbené' : '☆ Označiť ako obľúbené' }}
                        </button>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="plabel">Typ jedla</span>
                    <div class="flex flex-wrap gap-1.5">
                        <PChip v-for="c in categories" :key="c" :on="form.category === c" @click="form.category = c">{{ c }}</PChip>
                    </div>
                </div>
                <label class="flex flex-col gap-[7px]">
                    <span class="plabel">Krátky popis</span>
                    <textarea v-model="form.description" rows="2" class="ptextarea bg-white" placeholder="Čo jedlo obsahuje, veľkosť porcie" />
                </label>
                <div class="flex flex-wrap items-end gap-3.5">
                    <label class="flex flex-[0_1_180px] flex-col gap-[7px]">
                        <span class="plabel">Bežná cena (€)</span>
                        <input v-model="form.price" inputmode="decimal" class="pinput bg-white font-bold" placeholder="7,90" />
                    </label>
                    <label class="flex h-[50px] cursor-pointer items-center gap-2.5 text-[14.5px] font-semibold">
                        <PToggle v-model="form.vegetarian" label="Vegetariánske" />Vegetariánske
                    </label>
                </div>
            </div>

            <div v-show="tab === 'alg'" class="flex flex-col gap-3">
                <span class="text-[13.5px] leading-[1.45] text-cb-taupe"
                    >Označte všetko, čo jedlo obsahuje. Študenti s alergiou to uvidia pri jedle aj v košíku.</span
                >
                <span class="plabel">Alergény</span>
                <div class="flex flex-wrap gap-1.5">
                    <PChip v-for="a in allergens" :key="a" size="sm" :on="form.allergens.includes(a)" @click="toggleAlg(a)">{{ a }}</PChip>
                </div>
            </div>

            <div v-show="tab === 'opt'" class="flex flex-col gap-2.5">
                <div class="flex flex-wrap items-start justify-between gap-2.5">
                    <span class="flex flex-[1_1_240px] flex-col gap-0.5">
                        <span class="plabel">Možnosti pre zákazníka</span>
                        <span class="text-[12.5px] leading-[1.4] text-cb-taupe"
                            >Napr. výber omáčky, prílohy alebo menu s nápojom. Študent si vyberie pri objednávke v appke.</span
                        >
                    </span>
                    <button
                        type="button"
                        class="flex h-[38px] items-center gap-1.5 rounded-full bg-cb-ink px-3.5 text-[13.5px] font-bold text-white"
                        @click="addGroup"
                    >
                        <span class="text-[17px] leading-none">+</span>Pridať výber
                    </button>
                </div>
                <div
                    v-for="(g, gi) in form.groups"
                    :key="gi"
                    class="flex flex-col gap-2.5 rounded-[20px] border-[1.5px] border-cb-line bg-white p-3.5"
                >
                    <div class="flex items-center gap-2">
                        <input
                            v-model="g.q"
                            placeholder="Otázka, napr. Ktorú omáčku si želáte?"
                            class="pinput h-11 min-w-0 flex-1 rounded-xl text-[15.5px] font-bold"
                        />
                        <button
                            type="button"
                            aria-label="Odstrániť výber"
                            class="flex h-11 w-11 flex-none items-center justify-center rounded-full text-sm font-extrabold text-cb-red"
                            @click="form.groups.splice(gi, 1)"
                        >
                            ✕
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex rounded-full bg-[rgba(120,80,50,.08)] p-[3px]">
                            <button
                                type="button"
                                class="flex h-[38px] items-center rounded-full px-3.5 text-[13px] font-bold"
                                :class="!g.multi ? 'bg-cb-ink text-cb-cream' : ''"
                                @click="g.multi = false"
                            >
                                Jedna možnosť
                            </button>
                            <button
                                type="button"
                                class="flex h-[38px] items-center rounded-full px-3.5 text-[13px] font-bold"
                                :class="g.multi ? 'bg-cb-ink text-cb-cream' : ''"
                                @click="g.multi = true"
                            >
                                Viac možností
                            </button>
                        </div>
                        <label class="flex h-[38px] cursor-pointer items-center gap-2 text-[13.5px] font-semibold"
                            ><PToggle v-model="g.req" small label="Povinné" />Povinné</label
                        >
                        <label v-if="g.multi" class="flex items-center gap-1.5 text-[13.5px] font-semibold">
                            Najviac
                            <input
                                v-model="g.max"
                                inputmode="numeric"
                                placeholder="bez limitu"
                                class="pinput h-9 w-24 rounded-xl px-3 text-[14.5px]"
                            />
                        </label>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <div v-for="(o, oi) in g.opts" :key="oi" class="grid grid-cols-[22px_minmax(0,1fr)_96px_44px] items-center gap-2">
                            <span class="box-border h-[18px] w-[18px] border-2 border-cb-taupe" :class="g.multi ? 'rounded-[5px]' : 'rounded-full'" />
                            <input v-model="o.n" placeholder="Názov, napr. Kečup" class="pinput h-11 rounded-xl px-3 text-[14.5px]" />
                            <span class="relative block">
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[13px] font-bold text-cb-taupe">+ €</span>
                                <input
                                    v-model="o.p"
                                    inputmode="decimal"
                                    placeholder="0,00"
                                    class="pinput h-11 rounded-xl pl-[34px] pr-3 text-[14.5px]"
                                />
                            </span>
                            <button
                                type="button"
                                aria-label="Odstrániť možnosť"
                                class="flex h-11 w-11 items-center justify-center rounded-full text-sm font-extrabold text-cb-taupe"
                                @click="g.opts.splice(oi, 1)"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <button
                            type="button"
                            class="flex h-11 items-center gap-1.5 text-[13.5px] font-bold text-cb-red"
                            @click="g.opts.push({ n: '', p: '' })"
                        >
                            <span class="text-base leading-none">+</span>Pridať možnosť
                        </button>
                        <span class="text-xs font-semibold text-cb-taupe">Študent uvidí: {{ rule(g) }}</span>
                    </div>
                </div>
            </div>

            <ul v-if="errorList.length" class="m-0 list-none p-0 text-sm font-bold text-cb-red">
                <li v-for="e in errorList" :key="e">{{ e }}</li>
            </ul>

            <div class="flex flex-wrap gap-2.5">
                <button type="button" class="pbtn pbtn-red flex-[1_1_200px]" :disabled="form.processing" @click="save">
                    <span>Uložiť jedlo</span><span>✓</span>
                </button>
                <button v-if="dish" type="button" class="pbtn pbtn-ghost !justify-center !px-5 !text-[15px] !text-cb-red" @click="destroy">
                    {{ confirmDelete ? 'Naozaj odstrániť?' : 'Odstrániť' }}
                </button>
            </div>
        </div>
    </div>
</template>
