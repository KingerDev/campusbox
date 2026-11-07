<script setup lang="ts">
import CampusLogo from '@/components/landing/CampusLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ title: string; kicker?: string; sub?: string }>();
</script>

<template>
    <Head :title="title" />
    <div class="flex min-h-screen flex-wrap bg-[#FFF2E4] font-archivo text-cb-ink antialiased">
        <div class="hidden flex-[0_0_380px] flex-col gap-7 bg-[rgba(42,29,22,.92)] px-9 py-10 text-cb-cream lg:flex">
            <div class="flex flex-col gap-1.5">
                <Link href="/" aria-label="CampusBox"><CampusLogo :size="40" :text-size="26" inverted /></Link>
                <span class="text-[13px] font-semibold text-cb-beige">pre podniky</span>
            </div>
            <span class="text-4xl font-black leading-[1.02] tracking-[-.03em] [text-wrap:pretty]">Jedlo, ktoré vám zostane, si kúpia študenti.</span>
            <div class="flex-1" />
            <span class="text-[13px] leading-normal text-cb-beige"
                >Potrebujete pomôcť? Napíšte na <a href="mailto:ahoj@campusbox.sk" class="font-bold text-cb-cream">ahoj@campusbox.sk</a></span
            >
        </div>
        <div class="box-border flex w-full max-w-[560px] flex-[1_1_320px] flex-col gap-[18px] px-[18px] py-6 lg:px-14 lg:py-14">
            <span class="flex items-center gap-2 lg:hidden"
                ><CampusLogo :size="28" :text-size="18" /><span class="text-[13px] font-bold text-cb-taupe">pre podniky</span></span
            >
            <div class="mt-2 flex flex-col gap-1.5">
                <span v-if="kicker" class="text-[13px] font-bold uppercase tracking-[.06em] text-cb-red">{{ kicker }}</span>
                <h1 class="m-0 text-[30px] font-black leading-[1.02] tracking-[-.03em] lg:text-[40px]">{{ title }}</h1>
                <span v-if="sub" class="text-[15px] leading-[1.45] text-cb-taupe">{{ sub }}</span>
            </div>
            <slot />
        </div>
    </div>
</template>
