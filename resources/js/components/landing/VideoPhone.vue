<script setup lang="ts">
import { useClock } from '@/composables/useClock';
import { ref } from 'vue';

const props = defineProps<{ src: string; poster?: string }>();

const clock = useClock();
const video = ref<HTMLVideoElement | null>(null);
const idle = ref(true);

/** Pustí video od začiatku so zvukom (dovtedy beží potichu v slučke ako náhľad). */
const play = () => {
    const v = video.value;
    if (!v) return;
    idle.value = false;
    v.muted = false;
    v.currentTime = 0;
    v.play().catch(() => {});
};

/** Celá obrazovka so zvukom; ak to prehliadač nevie, otvorí video samostatne. */
const fullscreen = () => {
    const v = video.value as (HTMLVideoElement & { webkitEnterFullscreen?: () => void }) | null;
    if (!v) return window.open(props.src, '_blank');
    play();
    if (v.requestFullscreen) v.requestFullscreen().catch(() => window.open(props.src, '_blank'));
    else if (v.webkitEnterFullscreen) v.webkitEnterFullscreen();
    else window.open(props.src, '_blank');
};

defineExpose({ fullscreen });
</script>

<template>
    <div class="box-border h-[612px] w-[300px] flex-none rounded-[48px] bg-[#0E0B09] p-[9px] shadow-[0_40px_80px_-30px_rgba(42,29,22,.55)]">
        <div class="relative box-border flex h-full w-full flex-col gap-2.5 overflow-hidden rounded-[40px] bg-[#FFF2E4] pt-[14px] text-cb-ink">
            <div class="relative flex h-[26px] flex-none items-center justify-between px-[26px] text-xs font-bold" aria-hidden="true">
                <span>{{ clock }}</span>
                <span class="absolute left-1/2 top-[2px] h-[26px] w-[92px] -translate-x-1/2 rounded-[14px] bg-black" />
                <span class="text-[10px]">5G</span>
            </div>

            <div class="relative h-[502px] w-[282px] flex-none">
                <video
                    ref="video"
                    class="block h-full w-full object-cover"
                    :src="src"
                    :poster="poster"
                    :controls="!idle"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="metadata"
                    aria-label="Promo video CampusBox"
                />
                <button
                    v-if="idle"
                    type="button"
                    class="group absolute inset-0 flex cursor-pointer items-center justify-center bg-[rgba(42,29,22,.28)] transition-colors duration-200 hover:bg-[rgba(42,29,22,.42)]"
                    aria-label="Pustiť video so zvukom"
                    @click="play"
                >
                    <span
                        class="flex h-[76px] w-[76px] items-center justify-center rounded-full bg-cb-red text-white shadow-[0_16px_36px_-10px_rgba(120,30,10,.6)] transition-transform duration-200 group-hover:scale-[1.06]"
                    >
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor" class="block" aria-hidden="true">
                            <path transform="translate(-1.4 0)" d="M7 4.5v15a1 1 0 0 0 1.5.86l12.5-7.5a1 1 0 0 0 0-1.72L8.5 3.64A1 1 0 0 0 7 4.5z" />
                        </svg>
                    </span>
                </button>
            </div>

            <div class="flex flex-1 items-center justify-center" aria-hidden="true">
                <span class="h-[5px] w-[110px] rounded-[3px] bg-cb-ink" />
            </div>
        </div>
    </div>
</template>
