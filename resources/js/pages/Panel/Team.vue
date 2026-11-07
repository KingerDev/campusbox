<script setup lang="ts">
import PanelLayout from '@/layouts/PanelLayout.vue';
import { router, useForm } from '@inertiajs/vue3';

defineProps<{ members: { id: number; name: string; email: string; role: string; pending: boolean; me: boolean }[] }>();

const ROLES = [
    ['majitel', 'Majiteľ'],
    ['manazer', 'Manažér'],
    ['pracovnik', 'Pracovník'],
] as const;
const COLORS: Record<string, string> = { majitel: '#DB3B22', manazer: '#FF7A1A', pracovnik: '#15803D' };
const initials = (n: string) =>
    n
        .split(/[\s@.]+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((x) => x[0].toUpperCase())
        .join('');

const invite = useForm({ email: '', role: 'pracovnik' });
const sendInvite = () => invite.post(route('panel.team.invite'), { preserveScroll: true, onSuccess: () => invite.reset('email') });
const setRole = (id: number, role: string) => router.put(route('panel.team.update', id), { role }, { preserveScroll: true });
const remove = (id: number) => router.delete(route('panel.team.destroy', id), { preserveScroll: true });

const MATRIX: [string, number, number, number][] = [
    ['Výdaj a skenovanie QR', 1, 1, 1],
    ['Potvrdenie objednávok a čas prípravy', 1, 1, 1],
    ['Objednávky (bez súm)', 1, 1, 1],
    ['Dnešná ponuka a jedlá', 1, 1, 0],
    ['Tržby dňa a sumy objednávok', 1, 1, 0],
    ['Výplaty a štatistiky', 1, 0, 0],
    ['Tím a pravomoci', 1, 0, 0],
    ['Verejný profil podniku', 1, 1, 0],
    ['Fakturačné údaje a IBAN', 1, 0, 0],
];
</script>

<template>
    <PanelLayout title="Tím a pravomoci" sub="Kto má prístup do panela">
        <div class="pcard px-[18px] py-1">
            <div
                v-for="(m, i) in members"
                :key="m.id"
                class="flex flex-wrap items-center gap-x-4 gap-y-2.5 py-3.5"
                :class="i < members.length - 1 && 'border-b-[1.5px] border-cb-line'"
            >
                <div class="flex min-w-0 flex-[1_1_220px] items-center gap-3">
                    <div
                        class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-sm font-extrabold text-white"
                        :style="{ background: m.pending ? '#9B7B66' : COLORS[m.role] }"
                    >
                        {{ initials(m.name) }}
                    </div>
                    <div class="flex min-w-0 flex-col">
                        <span class="truncate text-[15px] font-bold"
                            >{{ m.name }}<span v-if="m.me" class="font-semibold text-cb-taupe"> · vy</span></span
                        >
                        <span class="truncate text-[12.5px] text-cb-taupe">{{ m.email }}{{ m.pending ? ' · pozvánka čaká na prijatie' : '' }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex gap-[3px] rounded-[18px] bg-cb-panel p-[3px]">
                        <button
                            v-for="[k, l] in ROLES"
                            :key="k"
                            type="button"
                            class="flex h-8 items-center rounded-2xl px-3 text-[12.5px] font-bold disabled:cursor-default"
                            :class="[
                                m.role === k ? 'bg-cb-ink text-cb-cream' : '',
                                (m.role === 'majitel' || m.me || k === 'majitel') && m.role !== k ? 'opacity-35' : '',
                            ]"
                            :disabled="m.role === 'majitel' || m.me || k === 'majitel' || m.role === k"
                            @click="setRole(m.id, k)"
                        >
                            {{ l }}
                        </button>
                    </div>
                    <button
                        v-if="m.role !== 'majitel' && !m.me"
                        type="button"
                        :aria-label="'Odobrať ' + m.name"
                        title="Odobrať z tímu"
                        class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-extrabold text-cb-taupe hover:bg-cb-panel hover:text-cb-red"
                        @click="remove(m.id)"
                    >
                        ✕
                    </button>
                </div>
            </div>
        </div>

        <form class="pcard flex flex-col gap-3 p-[18px]" @submit.prevent="sendInvite">
            <span class="text-[17px] font-extrabold">Pozvať člena tímu</span>
            <div class="flex flex-wrap items-center gap-2.5">
                <input
                    v-model="invite.email"
                    type="email"
                    placeholder="e‑mail pracovníka"
                    aria-label="E‑mail člena tímu"
                    class="h-12 flex-[1_1_220px] rounded-full border-[1.5px] border-cb-line bg-white/80 px-[18px] text-[15px] outline-none focus:border-cb-ink"
                />
                <div class="flex gap-[3px] rounded-[18px] bg-cb-panel p-[3px]">
                    <button
                        v-for="[k, l] in ROLES.slice(1)"
                        :key="k"
                        type="button"
                        class="flex h-9 items-center rounded-full px-3.5 text-[13px] font-bold"
                        :class="invite.role === k ? 'bg-cb-ink text-cb-cream' : ''"
                        @click="invite.role = k"
                    >
                        {{ l }}
                    </button>
                </div>
                <button
                    type="submit"
                    class="flex h-12 items-center rounded-full bg-cb-red px-5 text-[15px] font-bold text-white disabled:opacity-60"
                    :disabled="invite.processing"
                >
                    Pozvať
                </button>
            </div>
            <span v-if="invite.errors.email" class="text-sm font-bold text-cb-red">{{ invite.errors.email }}</span>
            <span class="text-[12.5px] text-cb-taupe">Pozvaný dostane e‑mail s odkazom. Po prijatí sa prihlási vlastným heslom.</span>
        </form>

        <div class="flex flex-col gap-2.5">
            <span class="text-[17px] font-extrabold">Čo ktorá rola vidí</span>
            <div class="pcard overflow-x-auto px-[18px] py-1">
                <div
                    class="grid grid-cols-[minmax(150px,1fr)_repeat(3,72px)] gap-2 border-b-2 border-cb-ink py-3 text-[11.5px] font-extrabold uppercase tracking-[.04em] text-cb-taupe"
                >
                    <span>Oprávnenie</span><span>Majiteľ</span><span>Manažér</span><span>Pracovník</span>
                </div>
                <div
                    v-for="([l, ...cells], i) in MATRIX"
                    :key="l"
                    class="grid grid-cols-[minmax(150px,1fr)_repeat(3,72px)] items-center gap-2 py-[11px] text-sm"
                    :class="i < MATRIX.length - 1 && 'border-b-[1.5px] border-cb-line'"
                >
                    <span class="font-semibold">{{ l }}</span>
                    <span v-for="(c, ci) in cells" :key="ci" class="font-black" :class="c ? 'text-cb-green' : 'text-[#B8A595]'">{{
                        c ? '✓' : '—'
                    }}</span>
                </div>
            </div>
        </div>
    </PanelLayout>
</template>
