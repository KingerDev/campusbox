import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import type { PanelOrder } from './usePanel';

/** Zdieľaný stav: ktorá objednávka sa práve potvrdzuje (modal je v layoute). */
export const confirmState = reactive<{ order: PanelOrder | null }>({ order: null });

const post = (order: PanelOrder, action: string, data: Record<string, unknown> = {}) =>
    router.post(route(`panel.orders.${action}`, order.id), data, { preserveScroll: true });

export function useOrderActions() {
    const openConfirm = (order: PanelOrder) => (confirmState.order = order);
    const closeConfirm = () => (confirmState.order = null);
    const confirm = (order: PanelOrder, eta: string | null) => post(order, 'confirm', { eta });
    const reject = (order: PanelOrder, reason: string) => post(order, 'reject', { reason });
    const ready = (order: PanelOrder) => post(order, 'ready');
    const handover = (order: PanelOrder) => post(order, 'handover');
    const issue = (order: PanelOrder) => post(order, 'issue');
    const goIssue = (order: PanelOrder) => router.get(route('panel.issue'), { kod: order.code.slice(3) });

    /** Hlavná akcia pre riadok objednávky podľa stavu (ako `actOf` v dizajne). */
    const actionFor = (order: PanelOrder): { label: string; bg: string; run: () => void } | null => {
        if (order.delivery && order.status === 'hot') return { label: 'Odovzdať kuriérovi', bg: '#2A1D16', run: () => handover(order) };
        switch (order.status) {
            case 'cesta':
                return { label: 'Doručené', bg: '#15803D', run: () => issue(order) };
            case 'nova':
                return { label: 'Potvrdiť', bg: '#DB3B22', run: () => openConfirm(order) };
            case 'prip':
                return { label: 'Pripravené', bg: '#C2560C', run: () => ready(order) };
            case 'hot':
                return { label: 'Vydať', bg: '#2A1D16', run: () => goIssue(order) };
            default:
                return null;
        }
    };

    return { openConfirm, closeConfirm, confirm, reject, ready, handover, issue, goIssue, actionFor };
}
