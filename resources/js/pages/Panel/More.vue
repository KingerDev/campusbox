<script setup lang="ts">
import { usePanel } from '@/composables/usePanel';
import PanelLayout from '@/layouts/PanelLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const { panel, can } = usePanel();
const items = computed(() =>
    [
        { label: 'Menu podniku', route: 'panel.menu', ability: 'menu' },
        { label: 'Výplaty a štatistiky', route: 'panel.payouts', ability: 'payouts' },
        { label: 'Tím a pravomoci', route: 'panel.team', ability: 'team' },
        { label: 'Profil podniku', route: 'panel.profile', ability: 'profile' },
        { label: 'Môj účet a heslo', route: 'profile.edit' },
    ].filter((i) => !i.ability || can(i.ability)),
);
</script>

<template>
    <PanelLayout title="Viac" :sub="panel.user.name + ' · ' + panel.role.label">
        <div class="pcard px-[18px] py-1">
            <Link
                v-for="(n, i) in items"
                :key="n.route"
                :href="route(n.route)"
                class="flex w-full items-center justify-between py-4 text-base font-bold"
                :class="i < items.length - 1 && 'border-b-[1.5px] border-cb-line'"
            >
                <span>{{ n.label }}</span
                ><span class="text-cb-taupe">›</span>
            </Link>
        </div>
    </PanelLayout>
</template>
