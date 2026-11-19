<script setup lang="ts">
import PChip from '@/components/panel/PChip.vue';
import PStepper from '@/components/panel/PStepper.vue';
import PToggle from '@/components/panel/PToggle.vue';
import SurpriseBagIcon from '@/components/panel/SurpriseBagIcon.vue';
import { eur, usePanel } from '@/composables/usePanel';
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
    menuSlot: string;
    rescueUntil: string;
    today: { open: string; close: string; closed: boolean };
    rates: Record<TypeKey, number>;
    delivery: { on: boolean; fee: string; radius: string; by: string };
}>();

// Neoverený podnik ponuku len pripravuje – študenti ju uvidia až po overení.
const { panel } = usePanel();
const live = computed(() => panel.value.business.approved);

const type = ref<TypeKey>(props.type);
const rows = reactive<Record<TypeKey, Row[]>>(JSON.parse(JSON.stringify(props.rows)));
const menu = computed(() => type.value === 'menu');
const VALID = /^\d\d:\d\d$/;
const mins = (t: string) => +t.slice(0, 2) * 60 + +t.slice(3, 5);
const hhmm = (m: number) => String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');
// Nevyzdvihnuté objednávky sa samy zrušia 15 minút po konci výdaja (ExpireOrders).
const cutoffOf = (end: string) => hhmm(Math.min(mins(end) + 15, 23 * 60 + 59));

// Študentské menu: okno výdaja od–do.
const from = ref(props.menuSlot.split('–')[0] ?? '');
const to = ref(props.menuSlot.split('–')[1] ?? '');
const slotValid = computed(() => VALID.test(from.value) && VALID.test(to.value) && to.value > from.value);
const duration = computed(() => {
    if (!slotValid.value) return '';
    const d = mins(to.value) - mins(from.value);
    const h = Math.floor(d / 60);
    return [h ? `${h} h` : '', d % 60 ? `${d % 60} min` : ''].filter(Boolean).join(' ');
});

// Záchrana jedla: objednáva sa od zverejnenia, vyzdvihnúť sa dá do času „do“ (predvolene záverečná).
const until = ref(props.rescueUntil);
const untilValid = computed(() => VALID.test(until.value));
const nowTime = () => hhmm(new Date().getHours() * 60 + new Date().getMinutes());
const untilPast = computed(() => untilValid.value && until.value <= nowTime());

const timeError = computed(() => {
    if (menu.value)
        return form.errors.slot || (from.value && to.value && to.value <= from.value ? 'Koniec výdaja musí byť neskôr ako začiatok.' : '');
    return form.errors.until || (untilPast.value ? 'Tento čas už prešiel. Zadajte, dokedy si študenti jedlo dnes ešte vyzdvihnú.' : '');
});
const hoursNote = computed(() => {
    const t = props.today;
    if (t.closed)
        return { warn: true, text: 'Dnes máte podľa profilu zatvorené. Ak výnimočne vydávate, čas nechajte; inak ponuku dnes nezverejňujte.' };
    const base = `Dnes máte otvorené ${t.open} – ${t.close}.`;
    const outside = menu.value ? slotValid.value && (from.value < t.open || to.value > t.close) : untilValid.value && until.value > t.close;
    if (outside) return { warn: true, text: `${base} Výdaj je mimo otváracích hodín – študenti by mohli prísť k zatvoreným dverám.` };
    return { warn: false, text: base };
});
const timeLabel = computed(() => (menu.value ? `${from.value}–${to.value}` : `do ${until.value}`));

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
// Bežná hodnota balíčka prekvapenie (po 50 centoch); cena pre študenta ju nesmie presiahnuť.
const valueStep = (r: Row, by: number) => {
    r.regular = Math.min(99, Math.max(1, Math.round((r.regular + by) * 2) / 2));
    r.price = Math.min(r.price, r.regular);
};

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
            slot: menu.value ? `${from.value}–${to.value}` : null,
            until: menu.value ? null : until.value,
            rows: current.value.map((r) => ({
                key: r.key,
                on: r.on,
                count: r.count,
                price: r.price,
                ...(r.key === 'surprise' ? { regular: r.regular } : {}),
            })),
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

        <div class="pcard flex flex-col gap-3.5 rounded-[26px] px-[18px] py-4">
            <span class="flex flex-col gap-1">
                <span class="text-[15.5px] font-extrabold">{{ menu ? 'Kedy vydávate študentské menu' : 'Dokedy si študenti jedlo vyzdvihnú' }}</span>
                <span class="text-[13px] leading-[1.45] text-cb-taupe [text-wrap:pretty]">
                    {{
                        menu
                            ? 'Študenti si menu môžu objednať hneď po zverejnení, pokojne už ráno. Vyzdvihnú si ho v čase, ktorý tu nastavíte.'
                            : 'Hneď ako ponuku zverejníte, študenti môžu objednávať – až kým sa nevypredá alebo nezavriete. Prísť si po jedlo môžu kedykoľvek do času, ktorý tu nastavíte.'
                    }}
                </span>
            </span>

            <div v-if="menu" class="flex flex-wrap items-center gap-x-3 gap-y-2.5 text-sm font-bold">
                <span>Menu vydávame od</span>
                <input v-model="from" type="time" aria-label="Výdaj menu od" class="pinput h-11 w-[116px] rounded-full px-3.5 font-bold" />
                <span>do</span>
                <input v-model="to" type="time" aria-label="Výdaj menu do" class="pinput h-11 w-[116px] rounded-full px-3.5 font-bold" />
                <span v-if="duration" class="rounded-full bg-cb-panel px-3 py-1.5 text-[12.5px] text-cb-taupe">spolu {{ duration }}</span>
            </div>
            <div v-else class="flex flex-wrap items-center gap-x-3 gap-y-2.5 text-sm font-bold">
                <span>Jedlo vydávame dnes do</span>
                <input v-model="until" type="time" aria-label="Výdaj záchrany do" class="pinput h-11 w-[116px] rounded-full px-3.5 font-bold" />
                <span v-if="!today.closed && until === today.close" class="rounded-full bg-cb-panel px-3 py-1.5 text-[12.5px] text-cb-taupe"
                    >do záverečnej</span
                >
                <button v-else-if="!today.closed" type="button" class="text-[13px] font-bold text-cb-red" @click="until = today.close">
                    Nastaviť na záverečnú ({{ today.close }})
                </button>
            </div>
            <span v-if="timeError" class="text-sm font-bold text-cb-red" role="alert">{{ timeError }}</span>

            <span
                class="text-[13px] font-semibold leading-[1.45]"
                :class="hoursNote.warn ? 'rounded-2xl bg-[rgba(255,122,26,.14)] px-3.5 py-2.5 text-cb-or-dark' : 'text-cb-taupe'"
            >
                {{ hoursNote.text }}
                <Link v-if="hoursNote.warn" :href="route('panel.profile')" class="font-bold underline">Upraviť otváracie hodiny</Link>
            </span>

            <div v-if="menu ? slotValid : untilValid" class="flex flex-col gap-2 rounded-[20px] bg-cb-panel px-4 py-3">
                <span class="text-[11.5px] font-extrabold uppercase tracking-[.06em] text-cb-taupe">Čo to znamená</span>
                <ul v-if="menu" class="m-0 flex list-none flex-col gap-1.5 p-0 text-[13.5px] leading-[1.4]">
                    <li>
                        <span class="font-extrabold">Študent uvidí</span> pri ponuke aj v potvrdení objednávky:
                        <span class="font-extrabold text-cb-red">Vyzdvihni si dnes {{ from }} – {{ to }}</span>
                    </li>
                    <li><span class="font-extrabold">Objednávky</span> chodia od zverejnenia až do {{ to }} alebo do vypredania.</li>
                    <li>
                        <span class="font-extrabold">Kto nepríde do {{ cutoffOf(to) }},</span> tomu sa objednávka sama zruší.
                    </li>
                </ul>
                <ul v-else class="m-0 flex list-none flex-col gap-1.5 p-0 text-[13.5px] leading-[1.4]">
                    <li>
                        <span class="font-extrabold">Študent uvidí</span> pri ponuke aj v potvrdení objednávky:
                        <span class="font-extrabold text-cb-red">Vyzdvihni si dnes do {{ until }}</span>
                    </li>
                    <li><span class="font-extrabold">Objednávky</span> chodia od zverejnenia až do {{ until }} alebo do vypredania.</li>
                    <li>
                        <span class="font-extrabold">Kedy si študent príde,</span> je na ňom – stačí, že to stihne do {{ until }}. Jedlo mu dovtedy
                        držíte.
                    </li>
                    <li>
                        <span class="font-extrabold">Kto nepríde do {{ cutoffOf(until) }},</span> tomu sa objednávka sama zruší.
                    </li>
                </ul>
            </div>
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
                    <SurpriseBagIcon v-if="r.key === 'surprise'" />
                    <img v-else-if="r.photo" :src="r.photo" alt="" class="h-12 w-12 flex-none rounded-full object-cover" />
                    <div v-else class="h-12 w-12 flex-none rounded-full" :style="{ background: r.ph }" />
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <span class="text-[15.5px] font-extrabold">{{ r.name }}</span>
                        <span v-if="r.key !== 'surprise'" class="text-[12.5px] text-cb-taupe">{{ r.sub }} · bežne {{ eur(r.regular) }}</span>
                        <span v-else class="text-[12.5px] leading-[1.4] text-cb-taupe [text-wrap:pretty]">
                            Študent kúpi balíček za pevnú cenu a vopred nevie, čo v ňom bude. Naplníte ho tým, čo vám na konci dňa zostane, takže
                            nemusíte rozpisovať každé jedlo zvlášť.
                        </span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3.5">
                    <PStepper
                        v-if="r.key === 'surprise'"
                        label="Bežná hodnota obsahu"
                        :display="eur(r.regular)"
                        min-width="64px"
                        @down="valueStep(r, -0.5)"
                        @up="valueStep(r, 0.5)"
                    />
                    <PStepper
                        :label="r.key === 'surprise' ? 'Balíčkov' : 'Porcií'"
                        :display="String(r.count)"
                        @down="countDown(r)"
                        @up="countUp(r)"
                    />
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
                <span class="text-[12.5px] text-cb-taupe">{{ type === 'menu' ? 'Študentské menu' : 'Záchrana jedla' }} · {{ timeLabel }}</span>
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

        <div v-if="!live" class="flex items-start gap-2.5 rounded-[20px] bg-[rgba(255,122,26,.14)] px-4 py-3 text-sm font-semibold text-cb-or-dark">
            <span class="font-black">◷</span>
            <span>Podnik ešte nie je overený. Ponuku si môžete pripraviť a uložiť, študenti ju však v appke uvidia až po overení.</span>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button" class="pbtn pbtn-red flex-[0_1_340px]" :disabled="form.processing" @click="publish">
                <span>{{ live ? 'Zverejniť' : 'Uložiť' }} {{ offered }} porcií</span><span>→</span>
            </button>
            <Link :href="route('panel.menu')" class="px-2 text-sm font-bold text-cb-taupe hover:text-cb-ink"
                >Chýba jedlo? Pridajte ho do zoznamu →</Link
            >
        </div>
    </PanelLayout>
</template>
