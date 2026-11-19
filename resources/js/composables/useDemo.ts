import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

/**
 * „Vyskúšať demo“: server založí návštevníkovi vlastnú kópiu ukážkového podniku a prihlási ho do nej.
 * Ide o POST, aby kópie nevznikali prednačítaním odkazov ani robotmi.
 */
export function useDemo() {
    const starting = ref(false);
    const page = usePage<{ errors: Record<string, string> }>();
    const demoError = computed(() => page.props.errors?.demo ?? page.props.errors?.throttle ?? '');

    const startDemo = () => {
        if (starting.value) return;
        router.post(
            route('panel.demo'),
            {},
            { preserveScroll: true, onStart: () => (starting.value = true), onFinish: () => (starting.value = false) },
        );
    };

    return { startDemo, starting, demoError };
}
