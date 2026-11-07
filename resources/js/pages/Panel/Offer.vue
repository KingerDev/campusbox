<script setup lang="ts">
import PChip from '@/components/panel/PChip.vue';
import PStepper from '@/components/panel/PStepper.vue';
import PToggle from '@/components/panel/PToggle.vue';
import { eur } from '@/composables/usePanel';
import PanelLayout from '@/layouts/PanelLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

type TypeKey = 'zachrana' | 'menu';
interface Row {
    key: string;
    dishId: number | null;
    name: string;
    sub: string;
    regular: number;
    ph: string;
    photo?: string | null;
    on: boolean;
    count: number;
    price: number;
}

const props = defineProps<{
    type: TypeKey;
    rows: Record<TypeKey, Row[]>;
    slots: Record<TypeKey, { options: string[]; value: string }>;
    rates: Record<TypeKey, number>;
    delivery: { on: boolean; fee: string; radius: string; by: string };
}>();

const type = ref<TypeKey>(props.type);
const rows = reactive<Record<TypeKey, Row[]>>(JSON.parse(JSON.stringify(props.rows)));
const slot = reactive<Record<TypeKey, string>>({ zachrana: props.slots.zachrana.value, menu: props.slots.menu.value });
const delivery = reactive({ ...props.delivery });

const current = computed(() => rows[type.value]);
const onRows = computed(() => current.value.filter((r) => r.on));
const offered = computed(() => onRows.value.reduce((a, r) => a + r.count, 0));
const revenue = computed(() => onRows.value.reduce((a, r) => a + r.count * r.price, 0));
const rate = computed(() => props.rates[type.value]);
const pct = (r: Row) => Math.max(0, Math.round((1 - r.price / r.regular) * 100));
const typeCount = (k: TypeKey) => rows[k].filter((r) => r.on).reduce((a, r) => a + r.count, 0);

const round1 = (n: number) => Math.round(n * 10) / 10;
const countUp = (r: Row) => {
    r.on = true;
    r.count++;
};
const countDown = (r: Row) => (r.count = Math.max(1, r.count - 1));
const priceUp = (r: Row) => (r.price = Math.min(r.regular, round1(r.price + 0.1)));
const priceDown = (r: Row) => (r.price = Math.max(0.5, round1(r.price - 0.1)));

const feePreview = computed(() => {
    const n = parseFloat(String(delivery.fee).replace(',', '.'));
    if (!delivery.fee || isNaN(n)) return 'Zadajte cenu';
    return n === 0 ? 'Študenti uvidia: Doručenie zadarmo' : 'Študenti uvidia: Doručenie +' + eur(n);
});

const form = useForm({});
const publish = () =>
    form
        .transform(() => ({
            type: type.value,
            slot: slot[type.value],
            rows: current.value.map((r) => ({ key: r.key, on: r.on, count: r.count, price: r.price })),
            delivery: { ...delivery },
        }))
        .put(route('panel.offer.update'), { preserveScroll: true, preserveState: true });

const TYPES: [TypeKey, string, string][] = [
    ['zachrana', 'Záchrana jedla', 'Nepredané jedlo · časovo obmedzené · provízia 11 %'],
    ['menu', 'Študentské menu', 'Pravidelné obedové menu · zvýhodnená cena · provízia 6 %'],
];
</script>

<template>
    <PanelLayout title="Dnešná ponuka" sub="Zaberie to do minúty">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-2">
            <button
                v-for="[k, l, sub] in TYPES"
                :key="k"
                type="button"
                class="box-border flex flex-col gap-[3px] rounded-[22px] border-[1.5px] px-[18px] py-3.5 text-left transition-colors"
                :class="type === k ? 'border-cb-ink bg-cb-ink text-cb-cream' : 'border-cb-line bg-white'"
                :aria-pressed="type === k"
                @click="type = k"
            >
                <span class="flex justify-between gap-2.5"
                    ><span class="text-base font-extrabold">{{ l }}</span
                    ><span class="text-[12.5px] font-bold">{{ typeCount(k) }} porcií</span></span
                >
                <span class="text-[12.5px] opacity-80">{{ sub }}</span>
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="mr-1 text-[13px] font-bold">Vyzdvihnutie</span>
            <PChip v-for="s in slots[type].options" :key="s" size="lg" :on="slot[type] === s" @click="slot[type] = s">{{ s }}</PChip>
        </div>

        <div class="flex flex-col gap-2.5">
            <div
                v-for="r in current"
                :key="r.key"
                class="pcard flex flex-wrap items-center gap-x-[18px] gap-y-3 rounded-[26px] px-4 py-3.5 transition-opacity"
                :class="r.on ? '' : 'opacity-60'"
            >
                <div class="flex min-w-0 flex-[1_1_220px] items-center gap-3">
                    <PToggle v-model="r.on" :label="'Predávať ' + r.name" />
                    <img v-if="r.photo" :src="r.photo" alt="" class="h-12 w-12 flex-none rounded-full object-cover" />
                    <div v-else class="h-12 w-12 flex-none rounded-full" :style="{ background: r.ph }" />
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <span class="text-[15.5px] font-extrabold">{{ r.name }}</span>
                        <span class="text-[12.5px] text-cb-taupe">{{ r.sub }} · bežne {{ eur(r.regular) }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3.5">
                    <PStepper label="Porcií" :display="String(r.count)" @down="countDown(r)" @up="countUp(r)" />
                    <PStepper
                        :label="`Cena pre študenta · −${pct(r)} %`"
                        :display="eur(r.price)"
                        value-class="text-cb-green"
                        min-width="64px"
                        @down="priceDown(r)"
                        @up="priceUp(r)"
                    />
                </div>
            </div>
            <div v-if="!current.length" class="pcard p-5 text-sm text-cb-taupe">
                V menu zatiaľ nemáte žiadne jedlá. <Link :href="route('panel.menu')" class="font-bold text-cb-red">Pridajte ich →</Link>
            </div>
        </div>

        <div class="pcard flex flex-col gap-3 rounded-[26px] px-[18px] py-4">
            <div class="flex flex-wrap items-baseline justify-between gap-2.5">
                <span class="text-[15.5px] font-extrabold">Ak sa predá všetko</span>
                <span class="text-[12.5px] text-cb-taupe">{{ type === 'menu' ? 'Študentské menu' : 'Záchrana jedla' }} · {{ slot[type] }}</span>
            </div>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(130px,1fr))] gap-3">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[26px] font-black tracking-[-.03em]">{{ eur(revenue) }}</span
                    ><span class="text-[12.5px] text-cb-taupe">tržba</span>
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-[26px] font-black tracking-[-.03em] text-cb-taupe">−{{ eur(revenue * rate) }}</span>
                    <span class="text-[12.5px] text-cb-taupe">provízia CampusBox {{ Math.round(rate * 100) }} %</span>
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-[26px] font-black tracking-[-.03em] text-cb-green">{{ eur(revenue * (1 - rate)) }}</span
                    ><span class="text-[12.5px] text-cb-taupe">vám zostane</span>
                </div>
            </div>
            <span class="text-[12.5px] leading-[1.45] text-cb-taupe"
                >Províziu účtujeme len z vydaných objednávok. Poplatok za doručenie ide celý vám.</span
            >
        </div>

        <div class="pcard flex flex-col gap-3 rounded-[26px] p-4">
            <div class="flex items-center gap-3">
                <PToggle v-model="delivery.on" label="Ponúkať aj doručenie" />
                <div class="flex flex-col gap-0.5">
                    <span class="text-[15.5px] font-extrabold">Ponúkať aj doručenie</span>
                    <span class="text-[12.5px] text-cb-taupe">Osobný odber je vždy zapnutý. Doručenie platí študent.</span>
                </div>
            </div>
            <div v-if="delivery.on" class="flex flex-wrap gap-x-6 gap-y-3">
                <label class="flex flex-col gap-1.5">
                    <span class="text-[11.5px] font-bold text-cb-taupe">Cena doručenia pre študenta</span>
                    <span class="flex items-center gap-2.5">
                        <span class="flex h-10 items-center gap-1 rounded-full border-[1.5px] border-cb-line bg-white/85 px-3.5">
                            <input
                                v-model="delivery.fee"
                                inputmode="decimal"
                                placeholder="0,00"
                                class="w-14 border-none bg-transparent p-0 text-[15px] font-bold outline-none focus:ring-0"
                            />
                            <span class="font-bold text-cb-taupe">€</span>
                        </span>
                        <span class="text-xs font-semibold text-cb-green">{{ feePreview }}</span>
                    </span>
                </label>
                <div class="flex flex-col gap-1.5">
                    <span class="text-[11.5px] font-bold text-cb-taupe">Okruh</span>
                    <div class="flex gap-1.5">
                        <PChip v-for="o in ['1 km', '2 km', '3 km']" :key="o" size="sm" :on="delivery.radius === o" @click="delivery.radius = o">{{
                            o
                        }}</PChip>
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <span class="text-[11.5px] font-bold text-cb-taupe">Kto doručuje</span>
                    <div class="flex gap-1.5">
                        <PChip
                            v-for="o in ['Kuriér CampusBox', 'Vlastný kuriér']"
                            :key="o"
                            size="sm"
                            :on="delivery.by === o"
                            @click="delivery.by = o"
                            >{{ o }}</PChip
                        >
                    </div>
                </div>
            </div>
            <span v-if="form.errors['delivery.fee']" class="text-sm font-bold text-cb-red">{{ form.errors['delivery.fee'] }}</span>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button" class="pbtn pbtn-red flex-[0_1_340px]" :disabled="form.processing" @click="publish">
                <span>Zverejniť {{ offered }} porcií</span><span>→</span>
            </button>
            <Link :href="route('panel.menu')" class="px-2 text-sm font-bold text-cb-taupe hover:text-cb-ink"
                >Chýba jedlo? Pridajte ho do zoznamu →</Link
            >
        </div>
    </PanelLayout>
</template>
