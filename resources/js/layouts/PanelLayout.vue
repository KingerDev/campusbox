<script setup lang="ts">
import CampusLogo from '@/components/landing/CampusLogo.vue';
import ConfirmOrderModal from '@/components/panel/ConfirmOrderModal.vue';
import { useOrderActions } from '@/composables/useOrderActions';
import { usePanel, type PanelOrder } from '@/composables/usePanel';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = withDefaults(defineProps<{ title: string; sub?: string; cta?: { label: string; href: string } | null }>(), {
    sub: '',
    cta: null,
});

const page = usePage<{ errors: Record<string, string>; panel: { flash: string | null } }>();
const { panel, can } = usePanel();
const { openConfirm } = useOrderActions();

type NavKey = 'prehlad' | 'ponuka' | 'jedla' | 'vydaj' | 'rez' | 'vyplaty' | 'tim' | 'profil';
const NAV: { key: NavKey; label: string; short: string; route: string; ability?: string }[] = [
    { key: 'prehlad', label: 'Prehľad dňa', short: 'Prehľad', route: 'panel.overview' },
    { key: 'ponuka', label: 'Dnešná ponuka', short: 'Ponuka', route: 'panel.offer', ability: 'offer' },
    { key: 'jedla', label: 'Menu podniku', short: 'Menu', route: 'panel.menu', ability: 'menu' },
    { key: 'vydaj', label: 'Výdaj', short: 'Výdaj', route: 'panel.issue' },
    { key: 'rez', label: 'Objednávky', short: 'Objednávky', route: 'panel.orders' },
    { key: 'vyplaty', label: 'Výplaty a štatistiky', short: 'Výplaty', route: 'panel.payouts', ability: 'payouts' },
    { key: 'tim', label: 'Tím a pravomoci', short: 'Tím', route: 'panel.team', ability: 'team' },
    { key: 'profil', label: 'Profil podniku', short: 'Profil', route: 'panel.profile', ability: 'profile' },
];

// Počty do odznakov (najprv zo servera, potom ich obnovuje polling).
const counts = ref({ ...panel.value.counts });
watch(
    () => panel.value.counts,
    (c) => (counts.value = { ...c }),
);

const nav = computed(() => NAV.filter((n) => !n.ability || can(n.ability)));
const badge = (key: NavKey) =>
    key === 'rez' && counts.value.new ? counts.value.new : key === 'vydaj' && counts.value.ready ? counts.value.ready : null;
const isActive = (r: string) => route().current(r) || route().current(r + '.*');

const TAB_KEYS: NavKey[] = ['prehlad', 'vydaj', 'rez', 'ponuka'];
const tabs = computed(() => nav.value.filter((n) => TAB_KEYS.includes(n.key)).sort((a, b) => TAB_KEYS.indexOf(a.key) - TAB_KEYS.indexOf(b.key)));
const moreActive = computed(() => route().current('panel.more') || nav.value.some((n) => !TAB_KEYS.includes(n.key) && isActive(n.route)));

// ---- toast ----
const toast = ref<{ text: string; error: boolean } | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | undefined;
const showToast = (text: string, error = false) => {
    toast.value = { text, error };
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (toast.value = null), 2800);
};
// Sledujeme celý objekt (pri každej odpovedi je nový), aby sa ukázal aj rovnaký text dvakrát po sebe.
watch(
    () => page.props.panel,
    (p) => p?.flash && showToast(p.flash),
    { immediate: true },
);
watch(
    () => page.props.errors?.order,
    (e) => e && showToast(e, true),
);

// ---- nové objednávky (polling) ----
const notif = ref<PanelOrder | null>(null);
const SEEN_KEY = 'cb-seen-orders';
const seen = new Set<string>(JSON.parse(sessionStorageGet() ?? '[]'));
function sessionStorageGet() {
    try {
        return sessionStorage.getItem(SEEN_KEY);
    } catch {
        return null;
    }
}
const remember = () => {
    try {
        sessionStorage.setItem(SEEN_KEY, JSON.stringify([...seen].slice(-200)));
    } catch {
        /* súkromné okno */
    }
};

let pollTimer: ReturnType<typeof setInterval> | undefined;
let first = true;
let notifTimer: ReturnType<typeof setTimeout> | undefined;

async function poll() {
    try {
        const res = await fetch(route('panel.orders.poll'), { headers: { Accept: 'application/json' } });
        if (!res.ok) return;
        const data: { new: PanelOrder[]; ready: number; active: number } = await res.json();
        counts.value = { new: data.new.length, ready: data.ready, active: data.active };

        const fresh = data.new.filter((o) => !seen.has(o.code));
        fresh.forEach((o) => seen.add(o.code));
        remember();

        // Pri prvom načítaní neupozorňujeme na staré objednávky.
        if (!first && fresh.length) {
            notif.value = fresh[0];
            clearTimeout(notifTimer);
            notifTimer = setTimeout(() => (notif.value = null), 9000);
            if (route().current('panel.orders') || route().current('panel.overview')) router.reload({ preserveScroll: true } as never);
        }
        first = false;
    } catch {
        /* sieť je dočasne nedostupná */
    }
}

onMounted(() => {
    poll();
    pollTimer = setInterval(poll, 15000);
});
onBeforeUnmount(() => {
    clearInterval(pollTimer);
    clearTimeout(toastTimer);
    clearTimeout(notifTimer);
});

const confirmFromNotif = () => {
    if (!notif.value) return;
    openConfirm(notif.value);
    notif.value = null;
};

const logout = () => router.post(route('logout'));
</script>

<template>
    <Head :title="props.title" />

    <div class="min-h-screen bg-cb-panel font-archivo text-cb-ink antialiased lg:flex">
        <!-- Desktop: bočný panel -->
        <aside
            class="pcard sticky top-[14px] m-[14px] mr-0 box-border hidden h-[calc(100vh-28px)] w-[248px] flex-none flex-col gap-5 overflow-y-auto rounded-[28px] px-3.5 py-[22px] lg:flex"
        >
            <div class="flex flex-col gap-1.5 px-2">
                <CampusLogo :size="34" :text-size="21" />
                <span class="text-xs font-semibold text-cb-taupe">pre podniky</span>
            </div>

            <component
                :is="can('profile') ? Link : 'div'"
                :href="can('profile') ? route('panel.profile') : undefined"
                class="grid grid-cols-[40px_1fr] items-center gap-2.5 rounded-[20px] bg-cb-panel p-2.5"
            >
                <img v-if="panel.business.logo" :src="panel.business.logo" alt="" class="h-10 w-10 rounded-full object-cover" />
                <div v-else class="h-10 w-10 rounded-full bg-[#F09A72]" />
                <div class="flex min-w-0 flex-col">
                    <span class="truncate text-sm font-bold">{{ panel.business.name }}</span>
                    <span class="truncate text-xs text-cb-taupe">{{ panel.user.name }} · {{ panel.role.label }}</span>
                </div>
            </component>

            <nav class="flex flex-col gap-[3px]" aria-label="Panel">
                <Link
                    v-for="n in nav"
                    :key="n.key"
                    :href="route(n.route)"
                    class="box-border flex h-11 items-center justify-between rounded-full px-3.5 text-[14.5px] font-semibold transition-colors"
                    :class="isActive(n.route) ? 'bg-cb-ink text-cb-cream' : 'text-cb-ink hover:bg-cb-panel'"
                    :aria-current="isActive(n.route) ? 'page' : undefined"
                >
                    <span>{{ n.label }}</span>
                    <span v-if="badge(n.key)" class="text-xs font-extrabold" :class="isActive(n.route) ? '' : 'text-cb-red'">{{ badge(n.key) }}</span>
                </Link>
            </nav>

            <div class="flex-1" />

            <div class="flex flex-col gap-2 px-1">
                <Link :href="route('profile.edit')" class="py-1 text-[12.5px] font-semibold text-cb-taupe hover:text-cb-ink">Môj účet a heslo →</Link>
                <button
                    type="button"
                    class="mt-1 box-border flex h-11 items-center gap-2 rounded-full border-[1.5px] border-cb-line px-3 text-sm font-bold hover:bg-cb-panel"
                    @click="logout"
                >
                    <svg
                        width="17"
                        height="17"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <path d="m16 17 5-5-5-5" />
                        <path d="M21 12H9" />
                    </svg>
                    Odhlásiť sa
                </button>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Mobil: horná lišta -->
            <div class="flex items-center justify-between bg-[#FFE3CC]/60 px-4 pb-2.5 pt-3 lg:hidden">
                <component
                    :is="can('profile') ? Link : 'div'"
                    :href="can('profile') ? route('panel.profile') : undefined"
                    class="flex min-w-0 items-center gap-2.5"
                >
                    <img v-if="panel.business.logo" :src="panel.business.logo" alt="" class="h-9 w-9 rounded-full object-cover" />
                    <div v-else class="h-9 w-9 flex-none rounded-full bg-[#F09A72]" />
                    <div class="flex min-w-0 flex-col">
                        <span class="truncate text-[15px] font-extrabold">{{ panel.business.name }}</span>
                        <span class="truncate text-xs text-cb-taupe">{{ panel.user.name }}</span>
                    </div>
                </component>
                <div class="flex items-center gap-1">
                    <span class="flex h-7 items-center rounded-full bg-white/75 px-3 text-xs font-extrabold">{{ panel.role.label }}</span>
                    <button type="button" aria-label="Odhlásiť sa" class="flex h-11 w-11 items-center justify-center rounded-full" @click="logout">
                        <svg
                            width="19"
                            height="19"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <path d="m16 17 5-5-5-5" />
                            <path d="M21 12H9" />
                        </svg>
                    </button>
                </div>
            </div>

            <main class="box-border flex min-w-0 flex-1 flex-col gap-5 px-4 pb-[110px] pt-4 lg:px-9 lg:pb-10 lg:pt-8">
                <div
                    v-if="!panel.business.approved"
                    class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-[20px] bg-[rgba(255,122,26,.14)] px-4 py-3 text-sm font-semibold text-cb-or-dark"
                >
                    <span class="font-black">◷</span>
                    Podnik overujeme (zvyčajne do 24 hodín). Medzitým si pripravte menu a tím – v appke sa zobrazíte po overení.
                </div>

                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <span v-if="sub" class="text-sm font-semibold text-cb-taupe">{{ sub }}</span>
                        <h1 class="m-0 text-[30px] font-black leading-none tracking-[-.03em] lg:text-[40px]">{{ title }}</h1>
                    </div>
                    <Link
                        v-if="cta"
                        :href="cta.href"
                        class="flex h-12 items-center justify-between gap-4 rounded-full bg-cb-red px-5 text-[15px] font-bold text-white hover:text-white"
                    >
                        <span>{{ cta.label }}</span
                        ><span>→</span>
                    </Link>
                    <slot name="actions" />
                </div>

                <slot />
            </main>
        </div>

        <!-- Mobil: spodná lišta -->
        <nav
            class="fixed bottom-3.5 left-3 right-3 z-30 box-border grid h-[66px] gap-1 rounded-[33px] border border-white/90 bg-white p-1.5 shadow-[inset_0_1px_0_rgba(255,255,255,.9),0_10px_30px_-14px_rgba(60,25,5,.35)] lg:hidden"
            :style="{ gridTemplateColumns: `repeat(${tabs.length + 1}, 1fr)` }"
            aria-label="Panel"
        >
            <Link
                v-for="t in tabs"
                :key="t.key"
                :href="route(t.route)"
                class="relative flex items-center justify-center rounded-[27px] text-[11.5px] font-bold"
                :class="isActive(t.route) ? 'bg-cb-panel text-cb-red' : 'text-cb-ink'"
            >
                {{ t.short }}
                <span
                    v-if="badge(t.key)"
                    class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-cb-red px-1 text-[10px] font-extrabold text-white"
                    >{{ badge(t.key) }}</span
                >
            </Link>
            <Link
                :href="route('panel.more')"
                class="flex items-center justify-center rounded-[27px] text-xs font-bold"
                :class="moreActive ? 'bg-cb-panel text-cb-red' : 'text-cb-ink'"
                >Viac</Link
            >
        </nav>

        <!-- Toast -->
        <Transition
            enter-from-class="opacity-0 -translate-y-2"
            leave-to-class="opacity-0"
            enter-active-class="transition"
            leave-active-class="transition"
        >
            <div
                v-if="toast"
                role="status"
                class="fixed left-4 right-4 top-4 z-[70] rounded-[22px] px-[18px] py-3.5 text-[15px] font-bold text-white sm:left-auto sm:max-w-md"
                :class="toast.error ? 'bg-cb-red' : 'bg-cb-green'"
            >
                {{ toast.text }}
            </div>
        </Transition>

        <!-- Nová objednávka -->
        <Transition
            enter-from-class="opacity-0 -translate-y-3"
            leave-to-class="opacity-0"
            enter-active-class="transition"
            leave-active-class="transition"
        >
            <div
                v-if="notif"
                role="alert"
                class="fixed left-1/2 top-3.5 z-[65] box-border grid w-[calc(100%-28px)] max-w-[460px] -translate-x-1/2 grid-cols-[44px_1fr_auto] items-center gap-3 rounded-[26px] bg-[rgba(255,248,240,.88)] p-3.5 shadow-[inset_0_1px_0_#fff,0_20px_40px_-16px_rgba(60,25,5,.45)] backdrop-blur-[26px] backdrop-saturate-[1.7]"
            >
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-cb-red text-white">
                    <svg
                        width="22"
                        height="22"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                    </svg>
                </div>
                <div class="flex min-w-0 flex-col gap-0.5">
                    <span class="text-[15px] font-extrabold">Nová objednávka · {{ notif.code }}</span>
                    <span class="truncate text-[13px] text-cb-taupe">{{ notif.name }} · {{ notif.items }}</span>
                </div>
                <div class="flex gap-1.5">
                    <button
                        type="button"
                        class="flex h-[38px] items-center rounded-full bg-cb-red px-4 text-[13.5px] font-bold text-white"
                        @click="confirmFromNotif"
                    >
                        Potvrdiť
                    </button>
                    <button
                        type="button"
                        aria-label="Zavrieť"
                        class="flex h-[38px] w-[38px] items-center justify-center rounded-full bg-white/80 font-bold"
                        @click="notif = null"
                    >
                        ✕
                    </button>
                </div>
            </div>
        </Transition>

        <ConfirmOrderModal />
    </div>
</template>
