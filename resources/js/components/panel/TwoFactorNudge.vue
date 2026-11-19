<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Pás s odporúčaním zapnúť dvojfaktorové overenie. „Neskôr“ ho skryje do zatvorenia prehliadača,
 * potom sa pripomenie znova – kým si ho používateľ nezapne.
 */
defineProps<{ text: string }>();

const KEY = 'cb-2fa-nudge-hidden';
const read = () => {
    try {
        return sessionStorage.getItem(KEY) === '1';
    } catch {
        return false;
    }
};
const hidden = ref(read());
const later = () => {
    hidden.value = true;
    try {
        sessionStorage.setItem(KEY, '1');
    } catch {
        /* bez úložiska sa pás skryje len do ďalšieho načítania */
    }
};
</script>

<template>
    <div
        v-if="!hidden"
        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-[20px] bg-[rgba(47,111,235,.1)] px-4 py-3 text-sm font-semibold text-[#1F3F8F]"
    >
        <span class="min-w-[220px] flex-1 [text-wrap:pretty]"><span class="font-black">Ochráňte si účet.</span> {{ text }}</span>
        <span class="flex flex-none items-center gap-1.5">
            <Link
                :href="route('two-factor.edit')"
                class="flex h-9 items-center gap-2 rounded-full bg-[#2457C5] px-4 text-[13.5px] font-bold text-white hover:text-white"
            >
                <span>Zapnúť</span><span>→</span>
            </Link>
            <button type="button" class="h-9 rounded-full px-3 text-[13.5px] font-bold hover:bg-white/60" @click="later">Neskôr</button>
        </span>
    </div>
</template>
