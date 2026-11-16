<script setup lang="ts">
import type { Map as LeafletMap, Marker } from 'leaflet';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

export interface LatLng {
    lat: number | null;
    lng: number | null;
}

const props = withDefaults(defineProps<{ modelValue: LatLng; address: string; label?: string; error?: string }>(), { label: 'Váš podnik', error: '' });
const emit = defineEmits<{ 'update:modelValue': [value: LatLng] }>();

// Banská Bystrica – pilot pri UMB.
const DEFAULT: [number, number] = [48.7363, 19.1462];

const el = ref<HTMLDivElement | null>(null);
const status = ref<'idle' | 'searching' | 'found' | 'notfound' | 'manual'>('idle');
let map: LeafletMap | null = null;
let marker: Marker | null = null;
let L: typeof import('leaflet') | null = null;

const pinHtml = (label: string) =>
    `<div style="transform:translate(-50%,-100%);display:flex;flex-direction:column;align-items:center;pointer-events:auto">
        <span style="white-space:nowrap;height:32px;padding:0 12px;border-radius:16px;background:#DB3B22;color:#fff;font:800 13px Archivo,system-ui,sans-serif;display:flex;align-items:center;box-shadow:0 8px 18px -8px rgba(120,30,10,.7)">${label.replace(/</g, '&lt;')}</span>
        <span style="width:3px;height:10px;background:#DB3B22"></span>
        <span style="width:10px;height:10px;border-radius:5px;background:#DB3B22;border:2px solid #fff;margin-top:-2px"></span>
    </div>`;

const set = (lat: number, lng: number, pan = true) => {
    if (!map || !L) return;
    const pos: [number, number] = [lat, lng];
    if (!marker) {
        marker = L.marker(pos, { draggable: true, icon: L.divIcon({ className: '', html: pinHtml(props.label), iconSize: [0, 0] }), keyboard: true, title: 'Poloha podniku' }).addTo(map);
        marker.on('dragend', () => {
            const p = marker!.getLatLng();
            status.value = 'manual';
            emit('update:modelValue', { lat: +p.lat.toFixed(7), lng: +p.lng.toFixed(7) });
        });
    } else marker.setLatLng(pos);
    if (pan) map.setView(pos, Math.max(map.getZoom(), 16));
    emit('update:modelValue', { lat: +lat.toFixed(7), lng: +lng.toFixed(7) });
};

/** Nájde adresu cez OpenStreetMap (Nominatim) a presunie špendlík. */
const locate = async () => {
    const q = props.address.trim();
    if (q.length < 4) return;
    status.value = 'searching';
    try {
        const url = 'https://nominatim.openstreetmap.org/search?' + new URLSearchParams({ q, format: 'jsonv2', limit: '1', countrycodes: 'sk', 'accept-language': 'sk' });
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const data: { lat: string; lon: string }[] = await res.json();
        if (!data.length) {
            status.value = 'notfound';
            return;
        }
        set(+data[0].lat, +data[0].lon);
        status.value = 'found';
    } catch {
        status.value = 'notfound';
    }
};

// Po zmene adresy hľadáme s oneskorením (Nominatim povoľuje najviac 1 dotaz za sekundu).
let timer: ReturnType<typeof setTimeout> | undefined;
watch(
    () => props.address,
    () => {
        clearTimeout(timer);
        timer = setTimeout(locate, 1200);
    },
);
watch(
    () => props.label,
    (l) => marker?.setIcon(L!.divIcon({ className: '', html: pinHtml(l || 'Váš podnik'), iconSize: [0, 0] })),
);

onMounted(async () => {
    L = (await import('leaflet')).default;
    await import('leaflet/dist/leaflet.css');
    const { lat, lng } = props.modelValue;
    map = L.map(el.value!, { scrollWheelZoom: false, attributionControl: true }).setView(lat && lng ? [lat, lng] : DEFAULT, lat && lng ? 16 : 13);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    map.on('click', (e) => {
        status.value = 'manual';
        set(e.latlng.lat, e.latlng.lng, false);
    });
    if (lat && lng) set(lat, lng);
    else if (props.address.trim().length >= 4) locate();
});
onBeforeUnmount(() => {
    clearTimeout(timer);
    map?.remove();
});
</script>

<template>
    <div class="flex flex-col gap-2">
        <div ref="el" class="relative z-0 h-[220px] overflow-hidden rounded-3xl bg-[#F6E3CF] lg:h-[260px]" role="application" aria-label="Mapa – poloha podniku" />
        <div class="flex flex-wrap items-center justify-between gap-2 text-[12.5px]">
            <span :class="error || status === 'notfound' ? 'font-bold text-cb-red' : 'text-cb-taupe'">
                {{
                    error ||
                    {
                        idle: 'Kliknite na mapu alebo zadajte adresu – špendlík sa dá potiahnuť.',
                        searching: 'Hľadám adresu na mape…',
                        found: 'Skontrolujte polohu a prípadne potiahnite špendlík na presné miesto.',
                        notfound: 'Adresu sme na mape nenašli. Kliknite na miesto podniku.',
                        manual: 'Poloha nastavená ručne.',
                    }[status]
                }}
            </span>
            <button type="button" class="font-bold text-cb-red disabled:opacity-50" :disabled="address.trim().length < 4 || status === 'searching'" @click="locate">Nájsť adresu na mape</button>
        </div>
    </div>
</template>
