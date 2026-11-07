import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface PanelShared {
    business: { id: number; name: string; approved: boolean; logo: string | null; rescueSlot: string };
    user: { name: string };
    role: { key: 'majitel' | 'manazer' | 'pracovnik'; label: string };
    abilities: string[];
    counts: { new: number; ready: number; active: number };
    flash: string | null;
}

export interface PanelOrder {
    id: number;
    code: string;
    name: string;
    items: string;
    qty: number;
    sum: number | null;
    at: string;
    status: 'nova' | 'prip' | 'hot' | 'cesta' | 'vyd' | 'zrus' | 'nahl' | 'odm';
    statusLabel: string;
    delivery: boolean;
    address: string | null;
    eta: string | null;
    reason: string | null;
}

export function usePanel() {
    const page = usePage<{ panel: PanelShared }>();
    const panel = computed(() => page.props.panel);
    const can = (ability: string) => panel.value?.abilities.includes(ability) ?? false;

    return { panel, can };
}

/** 3.5 → „3,50 €“ */
export const eur = (n: number | null | undefined) => (n ?? 0).toFixed(2).replace('.', ',') + ' €';

/** Farby stavových štítkov objednávky (podľa dizajnu). */
export const statusTone = (o: PanelOrder): { bg: string; col: string } => {
    const s = o.delivery && o.status === 'hot' ? 'kur' : o.status;
    return (
        {
            nova: { bg: 'rgba(219,59,34,.14)', col: '#DB3B22' },
            prip: { bg: 'rgba(255,122,26,.16)', col: '#C2560C' },
            hot: { bg: 'rgba(21,128,61,.14)', col: '#15803D' },
            kur: { bg: 'rgba(21,128,61,.14)', col: '#15803D' },
            cesta: { bg: 'rgba(47,111,235,.14)', col: '#2457C5' },
            vyd: { bg: 'rgba(120,80,50,.12)', col: '#77604F' },
            zrus: { bg: 'rgba(120,80,50,.12)', col: '#77604F' },
            odm: { bg: 'rgba(120,80,50,.12)', col: '#77604F' },
            nahl: { bg: 'rgba(219,59,34,.14)', col: '#DB3B22' },
        } as Record<string, { bg: string; col: string }>
    )[s];
};

export const fulfilmentLabel = (o: PanelOrder) => (o.delivery ? 'Doručenie · ' + o.address : 'Osobný odber');
