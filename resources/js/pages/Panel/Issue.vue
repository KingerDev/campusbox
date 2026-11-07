<script setup lang="ts">
import StatusPill from '@/components/panel/StatusPill.vue';
import { useOrderActions } from '@/composables/useOrderActions';
import { eur, fulfilmentLabel, usePanel, type PanelOrder } from '@/composables/usePanel';
import PanelLayout from '@/layouts/PanelLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{ code: string; found: PanelOrder | null; error: string | null; slot: string }>();

const { can } = usePanel();
const { issue } = useOrderActions();

const code = ref(props.code);
watch(
    () => props.code,
    (c) => (code.value = c),
);

const lookup = (value = code.value) => {
    const digits = value.replace(/\D/g, '').slice(-4);
    if (!digits) return;
    router.get(route('panel.issue'), { kod: digits }, { preserveState: true, preserveScroll: true, replace: true });
};

const canIssue = computed(() => !!props.found && ['prip', 'hot', 'cesta'].includes(props.found.status));
const foundLabel = computed(() => {
    const f = props.found;
    if (!f) return '';
    if (f.status === 'vyd') return 'Už vydané';
    if (f.status === 'nova') return 'Najprv potvrďte objednávku';
    return canIssue.value ? 'Zaplatené · čaká' : f.statusLabel;
});

// ---- kamera / QR ----
const video = ref<HTMLVideoElement | null>(null);
const scanning = ref(false);
const camError = ref<string | null>(null);
let scanner: { stop: () => void; destroy: () => void } | null = null;

async function startCamera() {
    camError.value = null;
    try {
        const { default: QrScanner } = await import('qr-scanner');
        if (!(await QrScanner.hasCamera())) {
            camError.value = 'Zariadenie nemá kameru. Zadajte kód ručne.';
            return;
        }
        scanning.value = true;
        await new Promise((r) => requestAnimationFrame(r));
        const s = new QrScanner(
            video.value!,
            (result) => {
                const m = result.data.match(/(\d{4})\s*$/);
                if (!m) return;
                stopCamera();
                code.value = m[1];
                lookup(m[1]);
            },
            { preferredCamera: 'environment', highlightScanRegion: false, maxScansPerSecond: 8 },
        );
        scanner = s;
        await s.start();
    } catch {
        scanning.value = false;
        camError.value = 'Kameru sa nepodarilo zapnúť. Povoľte prístup ku kamere alebo zadajte kód ručne.';
    }
}
function stopCamera() {
    scanner?.stop();
    scanner?.destroy();
    scanner = null;
    scanning.value = false;
}
onBeforeUnmount(stopCamera);
</script>

<template>
    <PanelLayout title="Výdaj" :sub="'Okno výdaja ' + slot">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(280px,1fr))] items-start gap-4">
            <div
                class="relative flex h-[300px] flex-col items-start justify-end gap-2.5 overflow-hidden rounded-[30px] bg-[#1D1410] p-5 lg:h-[380px]"
            >
                <video v-show="scanning" ref="video" class="absolute inset-0 h-full w-full object-cover" muted playsinline />
                <div class="absolute left-1/2 top-[42%] h-[170px] w-[170px] -translate-x-1/2 -translate-y-1/2 rounded-[32px] border-4 border-cb-or" />
                <span class="relative text-[14.5px] font-semibold text-[#FBEFE3]">
                    {{ scanning ? 'Namierte kameru na QR kód študenta' : (camError ?? 'Naskenujte QR kód z appky študenta') }}
                </span>
                <button
                    type="button"
                    class="relative flex h-[42px] items-center rounded-full bg-white/[.18] px-[18px] text-sm font-bold text-white backdrop-blur-xl"
                    @click="scanning ? stopCamera() : startCamera()"
                >
                    {{ scanning ? 'Vypnúť kameru' : 'Zapnúť kameru' }}
                </button>
            </div>

            <div class="flex flex-col gap-3.5">
                <span class="text-[17px] font-extrabold">Alebo zadajte kód ručne</span>
                <form class="flex gap-2" @submit.prevent="lookup()">
                    <label
                        class="flex h-[60px] min-w-0 flex-1 items-center gap-1 rounded-full border-[1.5px] bg-white/80 px-[18px] font-mono text-2xl font-extrabold tracking-[.1em]"
                        :class="error ? 'border-cb-red' : 'border-cb-line'"
                    >
                        <span class="text-cb-taupe">KV‑</span>
                        <input
                            v-model="code"
                            inputmode="numeric"
                            maxlength="4"
                            placeholder="0000"
                            aria-label="Kód objednávky"
                            class="w-full min-w-0 border-none bg-transparent p-0 font-mono text-2xl font-extrabold tracking-[.1em] outline-none focus:ring-0"
                            @input="code = code.replace(/\D/g, '').slice(0, 4)"
                        />
                    </label>
                    <button type="submit" class="flex h-[60px] items-center rounded-full bg-cb-ink px-5 text-[15px] font-bold text-white">
                        Overiť
                    </button>
                </form>
                <span v-if="error" class="text-sm font-bold text-cb-red" role="alert">{{ error }}</span>

                <div
                    v-if="found"
                    class="flex flex-col gap-3 rounded-[26px] border-2 bg-white p-[18px]"
                    :class="canIssue ? 'border-cb-green' : 'border-[#EAD6BE]'"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-[19px] font-extrabold">{{ found.code }}</span>
                        <StatusPill :order="{ ...found, statusLabel: foundLabel }" />
                    </div>
                    <div class="flex flex-col gap-1.5 text-[15px]">
                        <span
                            ><span class="text-cb-taupe">Študent: </span><b>{{ found.name }}</b></span
                        >
                        <span
                            ><span class="text-cb-taupe">Obsah: </span><b>{{ found.items }}</b></span
                        >
                        <span class="text-cb-taupe"
                            >{{ fulfilmentLabel(found) }} · Zaplatené v appke<template v-if="can('money')"> · {{ eur(found.sum) }}</template></span
                        >
                    </div>
                    <button v-if="canIssue" type="button" class="pbtn !bg-cb-green text-white" @click="issue(found)">
                        <span>{{ found.status === 'cesta' ? 'Potvrdiť doručenie' : 'Potvrdiť výdaj' }}</span
                        ><span>✓</span>
                    </button>
                </div>
            </div>
        </div>
    </PanelLayout>
</template>
