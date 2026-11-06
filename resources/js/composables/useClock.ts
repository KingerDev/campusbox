import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

/** Aktuálny čas vo formáte H:MM, obnovuje sa každých 15 s. */
export function useClock() {
    const now = ref(new Date());
    let timer: ReturnType<typeof setInterval> | undefined;

    onMounted(() => (timer = setInterval(() => (now.value = new Date()), 15000)));
    onBeforeUnmount(() => clearInterval(timer));

    return computed(() => now.value.getHours() + ':' + String(now.value.getMinutes()).padStart(2, '0'));
}
