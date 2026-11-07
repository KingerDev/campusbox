<script setup lang="ts">
import DishForm, { type MenuDish } from '@/components/panel/DishForm.vue';
import { eur } from '@/composables/usePanel';
import PanelLayout from '@/layouts/PanelLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

interface Section {
    id: number;
    name: string;
    dishes: MenuDish[];
}

const props = defineProps<{
    sections: Section[];
    categories: string[];
    allergens: string[];
    placeholders: Record<string, string>;
}>();

const sections = ref<Section[]>([]);
watch(
    () => props.sections,
    (s) => (sections.value = JSON.parse(JSON.stringify(s))),
    { immediate: true },
);

const filter = ref<number | 'all'>('all');
const visible = computed(() => (filter.value === 'all' ? sections.value : sections.value.filter((s) => s.id === filter.value)));
const dishCount = computed(() => sections.value.reduce((a, s) => a + s.dishes.length, 0));

// ---- formulár jedla ----
const form = ref<{ dish: MenuDish | null; sectionId: number } | null>(null);
const openDish = (dish: MenuDish) => (form.value = { dish, sectionId: dish.sectionId });
const newDish = (sectionId: number) => (form.value = { dish: null, sectionId });

// ---- sekcie ----
const editing = ref<number | null>(null);
const editName = ref('');
const startEdit = (s: Section) => {
    editing.value = s.id;
    editName.value = s.name;
};
const saveEdit = (s: Section) => {
    const name = editName.value.trim();
    editing.value = null;
    if (!name || name === s.name) return;
    s.name = name;
    router.put(route('panel.sections.update', s.id), { name }, { preserveScroll: true, preserveState: true });
};
const addSection = () =>
    router.post(
        route('panel.sections.store'),
        { name: 'Nová sekcia' },
        {
            preserveScroll: true,
            onSuccess: () => nextTick(() => startEdit(sections.value[sections.value.length - 1])),
        },
    );
const deleteSection = (s: Section) => router.delete(route('panel.sections.destroy', s.id), { preserveScroll: true });

// ---- poradie ----
const persistOrder = () =>
    router.put(
        route('panel.menu.reorder'),
        { sections: sections.value.map((s) => ({ id: s.id, dishes: s.dishes.map((d) => d.id) })) },
        { preserveScroll: true, preserveState: true },
    );
const move = <T,>(list: T[], from: number, to: number) => {
    if (to < 0 || to >= list.length) return false;
    list.splice(to, 0, list.splice(from, 1)[0]);
    return true;
};
const moveSection = (i: number, dir: number) => move(sections.value, i, i + dir) && persistOrder();
const moveDish = (s: Section, i: number, dir: number) => move(s.dishes, i, i + dir) && persistOrder();

// Drag & drop (desktop).
const drag = ref<{ t: 's' | 'd'; id: number } | null>(null);
const over = ref<string | null>(null);
const startDrag = (t: 's' | 'd', id: number, e: DragEvent) => {
    drag.value = { t, id };
    e.dataTransfer?.setData('text/plain', t + id);
    if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
};
const endDrag = () => {
    drag.value = null;
    over.value = null;
};
const dropOnSection = (target: Section) => {
    const d = drag.value;
    if (!d) return;
    if (d.t === 's') {
        const from = sections.value.findIndex((s) => s.id === d.id);
        const to = sections.value.findIndex((s) => s.id === target.id);
        if (from !== to) move(sections.value, from, to) && persistOrder();
    } else {
        // jedlo pustené na sekciu → na koniec sekcie
        const src = sections.value.find((s) => s.dishes.some((x) => x.id === d.id))!;
        const dish = src.dishes.splice(
            src.dishes.findIndex((x) => x.id === d.id),
            1,
        )[0];
        dish.sectionId = target.id;
        target.dishes.push(dish);
        persistOrder();
    }
    endDrag();
};
const dropOnDish = (target: Section, index: number) => {
    const d = drag.value;
    if (!d || d.t !== 'd') return;
    const src = sections.value.find((s) => s.dishes.some((x) => x.id === d.id))!;
    const from = src.dishes.findIndex((x) => x.id === d.id);
    const dish = src.dishes.splice(from, 1)[0];
    dish.sectionId = target.id;
    target.dishes.splice(src === target && from < index ? index - 1 : index, 0, dish);
    persistOrder();
    endDrag();
};

const metaL = (d: MenuDish) =>
    [
        d.popular ? 'Obľúbené' : '',
        d.groups.length ? (d.groups.length === 1 ? '1 výber' : d.groups.length + ' výbery') : '',
        d.allergens.length ? 'alergény ' + d.allergens.length : '',
        d.vegetarian ? 'vegetariánske' : '',
    ]
        .filter(Boolean)
        .join(' · ');
const countL = (n: number) => (n === 1 ? '1 jedlo' : n >= 2 && n <= 4 ? n + ' jedlá' : n + ' jedál');
</script>

<template>
    <PanelLayout title="Menu podniku" :sub="countL(dishCount) + ' v zozname'">
        <div class="-mb-1 flex gap-0.5 overflow-x-auto border-b-[1.5px] border-cb-line">
            <button
                v-for="t in [
                    { id: 'all' as const, name: 'Všetky', n: dishCount },
                    ...sections.map((s) => ({ id: s.id, name: s.name, n: s.dishes.length })),
                ]"
                :key="t.id"
                type="button"
                class="box-border flex h-11 flex-none items-center gap-1.5 border-b-[3px] px-3.5 text-[13px] font-extrabold uppercase tracking-[.05em]"
                :class="filter === t.id ? 'border-cb-red text-cb-red' : 'border-transparent'"
                @click="filter = t.id"
            >
                {{ t.name }}<span class="text-[11.5px] font-bold text-cb-taupe">{{ t.n }}</span>
            </button>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2.5">
            <span class="max-w-[560px] text-[13.5px] leading-[1.45] text-cb-taupe">
                Toto je stále menu podniku – sekcie, jedlá a ich poradie. Študenti ho v appke uvidia rovnako. Čo a koľko predávate dnes, nastavíte v
                <Link :href="route('panel.offer')" class="font-bold text-cb-ink">Dnešnej ponuke</Link>.
            </span>
            <button type="button" class="flex h-10 items-center gap-2 rounded-full bg-cb-ink px-4 text-sm font-bold text-white" @click="addSection">
                <span class="text-lg leading-none">+</span>Nová sekcia
            </button>
        </div>

        <div
            v-for="(s, si) in visible"
            :key="s.id"
            class="flex flex-col rounded-3xl bg-white px-5 pb-1.5 pt-4 transition-shadow"
            :class="over === 's' + s.id ? 'shadow-[0_0_0_3px_#DB3B22]' : 'shadow-[0_0_0_1px_rgba(120,80,50,.1),0_2px_6px_-2px_rgba(60,25,5,.08)]'"
            @dragover.prevent="over = 's' + s.id"
            @dragleave="over === 's' + s.id && (over = null)"
            @drop.prevent="dropOnSection(s)"
        >
            <div class="flex flex-wrap items-center gap-2 pb-3">
                <template v-if="editing === s.id">
                    <input
                        v-model="editName"
                        class="h-11 min-w-0 flex-[1_1_220px] rounded-[14px] border-2 border-cb-red bg-white px-3.5 text-xl font-black uppercase tracking-[.02em] outline-none"
                        maxlength="60"
                        autofocus
                        @keydown.enter="saveEdit(s)"
                        @keydown.esc="editing = null"
                    />
                    <button
                        type="button"
                        class="flex h-11 items-center rounded-full bg-cb-green px-4 text-sm font-bold text-white"
                        @click="saveEdit(s)"
                    >
                        Hotovo
                    </button>
                </template>
                <template v-else>
                    <span class="flex min-w-0 flex-[1_1_200px] items-center gap-2.5">
                        <span
                            v-if="filter === 'all'"
                            draggable="true"
                            title="Potiahnite a zmeňte poradie sekcie"
                            class="hidden cursor-grab px-1 text-lg tracking-[-3px] text-cb-taupe lg:inline"
                            @dragstart="startDrag('s', s.id, $event)"
                            @dragend="endDrag"
                            >⋮⋮</span
                        >
                        <span class="text-[22px] font-black uppercase tracking-[.01em]">{{ s.name }}</span>
                        <span class="text-[13px] font-semibold text-cb-taupe">{{ countL(s.dishes.length) }}</span>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <template v-if="filter === 'all'">
                            <button
                                type="button"
                                aria-label="Sekciu vyššie"
                                class="flex h-11 w-11 items-center justify-center rounded-full border-[1.5px] border-cb-line bg-cb-panel text-sm font-extrabold lg:hidden"
                                :class="si === 0 && 'opacity-30'"
                                @click="moveSection(si, -1)"
                            >
                                ↑
                            </button>
                            <button
                                type="button"
                                aria-label="Sekciu nižšie"
                                class="flex h-11 w-11 items-center justify-center rounded-full border-[1.5px] border-cb-line bg-cb-panel text-sm font-extrabold lg:hidden"
                                :class="si === visible.length - 1 && 'opacity-30'"
                                @click="moveSection(si, 1)"
                            >
                                ↓
                            </button>
                        </template>
                        <button
                            type="button"
                            aria-label="Premenovať"
                            title="Premenovať"
                            class="flex h-11 w-11 items-center justify-center rounded-full border-[1.5px] border-cb-line bg-cb-panel"
                            @click="startEdit(s)"
                        >
                            <svg
                                width="15"
                                height="15"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                            </svg>
                        </button>
                        <button
                            v-if="!s.dishes.length"
                            type="button"
                            aria-label="Odstrániť sekciu"
                            title="Odstrániť sekciu"
                            class="flex h-11 w-11 items-center justify-center rounded-full border-[1.5px] border-cb-line bg-cb-panel text-sm font-extrabold text-cb-red"
                            @click="deleteSection(s)"
                        >
                            ✕
                        </button>
                        <button
                            type="button"
                            class="ml-1 flex h-11 items-center gap-1.5 rounded-full bg-cb-red px-4 text-sm font-bold text-white"
                            @click="newDish(s.id)"
                        >
                            <span class="text-base leading-none">+</span>Jedlo
                        </button>
                    </div>
                </template>
            </div>

            <div v-if="!s.dishes.length" class="border-t-[1.5px] border-cb-line pb-3.5 pt-[18px] text-sm text-cb-taupe">
                Sekcia je prázdna. Pridajte jedlo tlačidlom „+ Jedlo“.
            </div>

            <div
                v-for="(d, di) in s.dishes"
                :key="d.id"
                draggable="true"
                class="grid grid-cols-[36px_minmax(0,1fr)_64px] items-center gap-3.5 py-3.5 lg:grid-cols-[24px_minmax(0,1fr)_auto_104px]"
                :class="over === 'd' + d.id ? 'border-t-[3px] border-cb-red' : 'border-t-[1.5px] border-cb-line'"
                @dragstart.stop="startDrag('d', d.id, $event)"
                @dragend="endDrag"
                @dragover.prevent.stop="drag?.t === 'd' && (over = 'd' + d.id)"
                @drop.prevent.stop="dropOnDish(s, di)"
            >
                <span title="Potiahnite a zmeňte poradie" class="hidden cursor-grab justify-center text-lg tracking-[-3px] text-cb-taupe lg:flex"
                    >⋮⋮</span
                >
                <div class="flex flex-col gap-1 lg:hidden">
                    <button
                        type="button"
                        aria-label="Vyššie"
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-cb-panel text-[15px] font-extrabold"
                        :class="di === 0 && 'opacity-30'"
                        @click="moveDish(s, di, -1)"
                    >
                        ↑
                    </button>
                    <button
                        type="button"
                        aria-label="Nižšie"
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-cb-panel text-[15px] font-extrabold"
                        :class="di === s.dishes.length - 1 && 'opacity-30'"
                        @click="moveDish(s, di, 1)"
                    >
                        ↓
                    </button>
                </div>
                <button type="button" class="flex min-w-0 flex-col gap-[3px] text-left" @click="openDish(d)">
                    <span class="text-[17px] font-extrabold leading-[1.2]"
                        >{{ d.name }}<span v-if="d.popular" class="text-cb-red">&nbsp; ★</span></span
                    >
                    <span v-if="d.description" class="text-[13.5px] leading-[1.4] text-cb-taupe">{{ d.description }}</span>
                    <span class="mt-0.5 flex flex-wrap items-baseline gap-2.5">
                        <span class="text-base font-extrabold">{{ eur(d.price) }}</span>
                        <span class="text-[12.5px] text-cb-taupe">{{ metaL(d) }}</span>
                    </span>
                    <span class="text-[12.5px] font-bold lg:hidden" :class="d.today ? 'text-cb-green' : 'text-cb-taupe'">{{
                        d.today ? `Dnes ${d.today} ks` : 'Dnes nepredávate'
                    }}</span>
                </button>
                <Link
                    :href="route('panel.offer')"
                    title="Porcie na dnes nastavíte v Dnešnej ponuke"
                    class="hidden h-11 items-center whitespace-nowrap rounded-full px-3 text-[13px] font-bold hover:bg-cb-panel lg:flex"
                    :class="d.today ? 'text-cb-green' : 'text-cb-taupe'"
                >
                    {{ d.today ? `Dnes ${d.today} ks →` : 'Dnes nepredávate →' }}
                </Link>
                <button
                    type="button"
                    class="box-border flex h-14 items-end overflow-hidden rounded-[14px] p-1.5 lg:h-20"
                    :style="{
                        backgroundColor: d.ph,
                        backgroundImage: d.photo
                            ? `url(${d.photo})`
                            : 'repeating-linear-gradient(135deg,rgba(255,255,255,.22) 0 10px,transparent 10px 20px)',
                        backgroundSize: 'cover',
                        backgroundPosition: 'center',
                    }"
                    :aria-label="'Upraviť ' + d.name"
                    @click="openDish(d)"
                >
                    <span v-if="!d.photo" class="rounded-[5px] bg-white/75 px-[5px] py-0.5 font-mono text-[9.5px]">foto</span>
                </button>
            </div>
        </div>

        <DishForm
            v-if="form"
            :dish="form.dish"
            :section-id="form.sectionId"
            :sections="sections.map((s) => ({ id: s.id, name: s.name }))"
            :categories="categories"
            :allergens="allergens"
            :placeholders="placeholders"
            @close="form = null"
        />
    </PanelLayout>
</template>
