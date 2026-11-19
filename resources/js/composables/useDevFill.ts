import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Vývojárska pomôcka: vzorové údaje do formulárov, aby sa pri testovaní nemuseli vypisovať ručne.
 * Tlačidlo „Vyplniť“ sa zobrazí len lokálne (server posiela devTools len pri APP_ENV=local).
 */
export function useDevTools() {
    const page = usePage<{ devTools?: boolean }>();

    return computed(() => page.props.devTools === true);
}

const pick = <T>(list: T[]): T => list[Math.floor(Math.random() * list.length)];
const suffix = () => Math.random().toString(36).slice(2, 7);

export interface SampleDish {
    name: string;
    category: string;
    description: string;
    price: string;
    vegetarian: boolean;
    popular: boolean;
    allergens: string[];
    groups: { q: string; multi: boolean; req: boolean; max: number | null; opts: { n: string; p: string }[] }[];
}

const size = {
    q: 'Veľkosť porcie',
    multi: false,
    req: true,
    max: null,
    opts: [
        { n: 'Normálna', p: '' },
        { n: 'Veľká', p: '1,50' },
    ],
};
const extras = {
    q: 'Niečo navyše?',
    multi: true,
    req: false,
    max: 2,
    opts: [
        { n: 'Syr', p: '0,80' },
        { n: 'Slanina', p: '1,00' },
        { n: 'Pikantná omáčka', p: '0,40' },
    ],
};

const DISHES: SampleDish[] = [
    {
        name: 'Bryndzové halušky',
        category: 'Teplé jedlo',
        description: 'So slaninou a pažítkou, 350 g',
        price: '7,50',
        vegetarian: false,
        popular: true,
        allergens: ['lepok', 'mlieko', 'vajcia'],
        groups: [size],
    },
    {
        name: 'Vyprážaný syr s hranolkami',
        category: 'Teplé jedlo',
        description: 'Eidam, hranolky, tatárska omáčka',
        price: '8,20',
        vegetarian: true,
        popular: true,
        allergens: ['lepok', 'mlieko', 'vajcia'],
        groups: [],
    },
    {
        name: 'Kuracie stehno na paprike',
        category: 'Teplé jedlo',
        description: 'S domácimi haluškami, 300 g',
        price: '7,90',
        vegetarian: false,
        popular: false,
        allergens: ['lepok', 'mlieko'],
        groups: [size],
    },
    {
        name: 'Pad thai s tofu',
        category: 'Teplé jedlo',
        description: 'Ryžové rezance, zelenina, arašidy',
        price: '8,90',
        vegetarian: true,
        popular: false,
        allergens: ['arašidy', 'sója'],
        groups: [extras],
    },
    {
        name: 'Cesnaková polievka',
        category: 'Polievka',
        description: 'So syrom a krutónmi, 0,33 l',
        price: '2,90',
        vegetarian: true,
        popular: false,
        allergens: ['lepok', 'mlieko'],
        groups: [],
    },
    {
        name: 'Kapustnica',
        category: 'Polievka',
        description: 'S klobásou a hubami, 0,5 l',
        price: '3,90',
        vegetarian: false,
        popular: true,
        allergens: [],
        groups: [],
    },
    {
        name: 'Grécky šalát',
        category: 'Šalát',
        description: 'Paradajky, uhorka, olivy, feta',
        price: '6,40',
        vegetarian: true,
        popular: false,
        allergens: ['mlieko'],
        groups: [],
    },
    {
        name: 'Pizza Margherita',
        category: 'Pizza',
        description: 'Paradajkový základ, mozzarella, bazalka, 32 cm',
        price: '7,20',
        vegetarian: true,
        popular: true,
        allergens: ['lepok', 'mlieko'],
        groups: [extras],
    },
    {
        name: 'Pizza Salámová',
        category: 'Pizza',
        description: 'Paradajkový základ, mozzarella, saláma, 32 cm',
        price: '8,40',
        vegetarian: false,
        popular: false,
        allergens: ['lepok', 'mlieko'],
        groups: [extras],
    },
    {
        name: 'Bageta so šunkou a syrom',
        category: 'Sendvič',
        description: 'Čerstvá bageta, maslo, šalát',
        price: '4,20',
        vegetarian: false,
        popular: false,
        allergens: ['lepok', 'mlieko'],
        groups: [],
    },
    {
        name: 'Škoricový slimák',
        category: 'Pečivo',
        description: 'Kysnuté cesto, škorica, poleva',
        price: '2,20',
        vegetarian: true,
        popular: true,
        allergens: ['lepok', 'mlieko', 'vajcia'],
        groups: [],
    },
    {
        name: 'Makový závin',
        category: 'Pečivo',
        description: 'Domáci, 120 g',
        price: '1,90',
        vegetarian: true,
        popular: false,
        allergens: ['lepok', 'mlieko'],
        groups: [],
    },
    {
        name: 'Čokoládový fondán',
        category: 'Dezert',
        description: 'S vanilkovou zmrzlinou',
        price: '4,90',
        vegetarian: true,
        popular: true,
        allergens: ['lepok', 'mlieko', 'vajcia'],
        groups: [],
    },
    {
        name: 'Tvarohový koláč',
        category: 'Dezert',
        description: 'S lesným ovocím, 130 g',
        price: '3,60',
        vegetarian: true,
        popular: false,
        allergens: ['lepok', 'mlieko', 'vajcia'],
        groups: [],
    },
    {
        name: 'Domáca limonáda',
        category: 'Nápoj',
        description: 'Citrón a mäta, 0,4 l',
        price: '2,50',
        vegetarian: true,
        popular: false,
        allergens: [],
        groups: [],
    },
    {
        name: 'Flat white',
        category: 'Nápoj',
        description: 'Dvojité espresso s mliekom',
        price: '2,80',
        vegetarian: true,
        popular: false,
        allergens: ['mlieko'],
        groups: [
            {
                q: 'Mlieko',
                multi: false,
                req: true,
                max: null,
                opts: [
                    { n: 'Kravské', p: '' },
                    { n: 'Ovsené', p: '0,40' },
                ],
            },
        ],
    },
];

let dishCursor = Math.floor(Math.random() * DISHES.length);
/** Ďalšie vzorové jedlo; každé kliknutie dá iné, kým sa zoznam neminie. */
export function sampleDish(): SampleDish {
    const dish = DISHES[dishCursor++ % DISHES.length];

    return JSON.parse(JSON.stringify(dish));
}

const BUSINESSES: [string, string, string][] = [
    ['Bistro Na Rohu', 'Bistro', 'Námestie SNP 12, 974 01 Banská Bystrica'],
    ['Pekáreň U Babky', 'Pekáreň', 'Horná 25, 974 01 Banská Bystrica'],
    ['Kaviareň Medzi Riadkami', 'Kaviareň', 'Dolná 8, 974 01 Banská Bystrica'],
    ['Pizzeria Urpín', 'Pizzeria', 'Tajovského 12, 974 01 Banská Bystrica'],
    ['Reštaurácia Pod Vežou', 'Reštaurácia', 'Kapitulská 3, 974 01 Banská Bystrica'],
    ['Cukráreň Sladký Kút', 'Cukráreň', 'Skuteckého 5, 974 01 Banská Bystrica'],
];
const PEOPLE = ['Martin Novák', 'Zuzana Kováčová', 'Tomáš Horváth', 'Lucia Baloghová', 'Peter Varga', 'Katarína Tóthová'];

/** Všetky kroky registrácie podniku naraz; e‑mail je zakaždým iný, aby nenarazil na „už existuje“. */
export function sampleRegistration() {
    const [nazov, typ, adresa] = pick(BUSINESSES);
    const id = suffix();

    return {
        meno: pick(PEOPLE),
        email: `test.${id}@example.com`,
        tel: `+421 9${Math.floor(Math.random() * 10)}${Math.floor(Math.random() * 10)} ${String(Math.floor(Math.random() * 1000)).padStart(3, '0')} ${String(Math.floor(Math.random() * 1000)).padStart(3, '0')}`,
        heslo: `Testovacie-${id}-2026`,
        nazov,
        ico: String(10000000 + Math.floor(Math.random() * 89999999)),
        typ,
        adresa,
        // Centrum Banskej Bystrice s malým rozptylom.
        latitude: +(48.7363 + (Math.random() - 0.5) * 0.01).toFixed(5),
        longitude: +(19.1462 + (Math.random() - 0.5) * 0.02).toFixed(5),
        donaska: Math.random() < 0.5,
        poplatok: '1,49',
        iban: 'SK31 1200 0000 1987 4263 7541',
        fakt: `faktury.${id}@example.com`,
        suhlas: true,
    };
}
