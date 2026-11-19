<script setup lang="ts">
import CampusLogo from '@/components/landing/CampusLogo.vue';
import PanelLayout from '@/layouts/PanelLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Nastavenia účtu (údaje, heslo, dvojfaktorové overenie). Člen podniku ich vidí v ráme panela,
 * správca bez podniku v jednoduchom ráme administrácie.
 */
defineProps<{ title: string; sub?: string; locked?: boolean }>();

const page = usePage<{ panel?: object; account: { admin: boolean; home: string; homeLabel: string } }>();
const inPanel = computed(() => !!page.props.panel);
const account = computed(() => page.props.account);

const TABS = [
    { label: 'Moje údaje', route: 'profile.edit', match: 'profile.*' },
    { label: 'Heslo', route: 'password.edit', match: 'password.*' },
    { label: 'Dvojfaktorové overenie', route: 'two-factor.edit', match: 'two-factor.*' },
];
</script>

<template>
    <component :is="inPanel ? PanelLayout : 'div'" v-bind="inPanel ? { title: 'Môj účet', sub: 'Prihlásenie a zabezpečenie' } : {}">
        <template v-if="!inPanel">
            <Head :title="title" />
            <div class="min-h-screen bg-cb-panel font-archivo text-cb-ink antialiased">
                <header class="border-b-[1.5px] border-cb-line bg-white/70">
                    <div class="mx-auto flex max-w-[1000px] flex-wrap items-center justify-between gap-3 px-4 py-3.5 lg:px-6">
                        <div class="flex items-center gap-2.5">
                            <CampusLogo :size="30" :text-size="19" />
                            <span
                                v-if="account.admin"
                                class="rounded-full bg-cb-ink px-2.5 py-1 text-[11.5px] font-extrabold uppercase tracking-[.06em] text-cb-cream"
                                >Administrácia</span
                            >
                        </div>
                        <div class="flex items-center gap-4 text-[13.5px] font-bold">
                            <Link v-if="!locked" :href="account.home" class="text-cb-red">← {{ account.homeLabel }}</Link>
                            <Link :href="route('logout')" method="post" as="button" class="text-cb-taupe hover:text-cb-ink">Odhlásiť sa</Link>
                        </div>
                    </div>
                </header>
                <main class="mx-auto flex max-w-[1000px] flex-col gap-5 px-4 pb-16 pt-6 lg:px-6 lg:pt-9">
                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-semibold text-cb-taupe">Prihlásenie a zabezpečenie</span>
                        <h1 class="m-0 text-[30px] font-black leading-none tracking-[-.03em] lg:text-[38px]">Môj účet</h1>
                    </div>
                    <nav class="flex flex-wrap gap-1.5" aria-label="Nastavenia účtu">
                        <Link
                            v-for="t in TABS"
                            :key="t.route"
                            :href="route(t.route)"
                            class="flex h-10 items-center rounded-full border-[1.5px] px-4 text-[14px] font-bold"
                            :class="route().current(t.match) ? 'border-cb-ink bg-cb-ink text-cb-cream' : 'border-cb-line bg-white text-cb-ink'"
                            :aria-current="route().current(t.match) ? 'page' : undefined"
                            >{{ t.label }}</Link
                        >
                    </nav>
                    <section class="pcard flex w-full max-w-[620px] flex-col gap-[18px] p-5 lg:p-6">
                        <div class="flex flex-col gap-1">
                            <h2 class="m-0 text-[20px] font-extrabold tracking-[-.01em]">{{ title }}</h2>
                            <span v-if="sub" class="text-sm leading-[1.45] text-cb-taupe">{{ sub }}</span>
                        </div>
                        <slot />
                    </section>
                    <slot name="after" />
                </main>
            </div>
        </template>

        <template v-else>
            <nav class="flex flex-wrap gap-1.5" aria-label="Nastavenia účtu">
                <Link
                    v-for="t in TABS"
                    :key="t.route"
                    :href="route(t.route)"
                    class="flex h-10 items-center rounded-full border-[1.5px] px-4 text-[14px] font-bold"
                    :class="route().current(t.match) ? 'border-cb-ink bg-cb-ink text-cb-cream' : 'border-cb-line bg-white text-cb-ink'"
                    :aria-current="route().current(t.match) ? 'page' : undefined"
                    >{{ t.label }}</Link
                >
            </nav>
            <section class="pcard flex w-full max-w-[620px] flex-col gap-[18px] p-5 lg:p-6">
                <div class="flex flex-col gap-1">
                    <h2 class="m-0 text-[20px] font-extrabold tracking-[-.01em]">{{ title }}</h2>
                    <span v-if="sub" class="text-sm leading-[1.45] text-cb-taupe">{{ sub }}</span>
                </div>
                <slot />
            </section>
            <slot name="after" />
        </template>
    </component>
</template>
