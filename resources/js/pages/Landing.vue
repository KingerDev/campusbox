<script setup lang="ts">
import CampusLogo from '@/components/landing/CampusLogo.vue';
import IllustrationHero from '@/components/landing/IllustrationHero.vue';
import IllustrationIsic from '@/components/landing/IllustrationIsic.vue';
import IllustrationPickup from '@/components/landing/IllustrationPickup.vue';
import IllustrationReserve from '@/components/landing/IllustrationReserve.vue';
import LandingPhone, { type PhoneScreen } from '@/components/landing/LandingPhone.vue';
import VideoPhone from '@/components/landing/VideoPhone.vue';
import { useClock } from '@/composables/useClock';
import { useDemo } from '@/composables/useDemo';
import { useLandingMotion } from '@/composables/useLandingMotion';
import { usePauseOffscreen } from '@/composables/usePauseOffscreen';
import { MOBILE_BREAKPOINT, storyMetrics, useScrollStory } from '@/composables/useScrollStory';
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

const props = withDefaults(defineProps<{ audience?: 'studenti' | 'podniky'; appUrl?: string }>(), {
    audience: 'studenti',
    appUrl: '#',
});

const biz = computed(() => props.audience === 'podniky');
const registerUrl = route('panel.registration');
const loginUrl = route('login');
const { startDemo, starting: demoStarting, demoError } = useDemo();
// Odkazy, ktoré zatiaľ nikam nevedú (href „#“, napr. appka), neskáču na začiatok stránky.
const preventPlaceholder = (e: MouseEvent) => {
    if ((e.currentTarget as HTMLAnchorElement).getAttribute('href') === '#') e.preventDefault();
};

const hero = computed(() =>
    biz.value
        ? {
              kicker: 'Pre reštaurácie, bistrá, kaviarne a pekárne',
              title: 'Zarobte na jedle, ktoré by ste vyhodili.',
              accent: '',
              sub: 'Zverejnite nepredané jedlo alebo študentské menu za minútu. Bez mesačného poplatku – províziu platíte len z vydaných objednávok.',
              cta1: { label: 'Zaregistrovať podnik', href: registerUrl },
              cta2: { label: 'Ceny a provízie', href: '#podniky' },
              alt: { label: 'Ste študent? Pozrite appku →', href: '#ako' },
          }
        : {
              kicker: 'Pilot v Banskej Bystrici',
              title: 'Zachráň dobré jedlo.',
              accent: 'Za študentské ceny.',
              sub: 'CampusBox spája študentov s bistrami, pekárňami a kaviarňami v celom meste. Zachráň nepredané jedlo až o 70 % lacnejšie alebo si daj zvýhodnené obedové menu.',
              cta1: { label: 'Stiahnuť appku', href: props.appUrl },
              cta2: { label: 'Ako to funguje', href: '#ako' },
              alt: { label: 'Máte podnik? Pridajte sa →', href: '#podniky' },
          },
);

const steps = [
    {
        key: 'overenie' as PhoneScreen,
        dx: -40,
        r: -2.5,
        arrow: 'right',
        n: '01',
        title: 'Over sa ako študent',
        text: 'Stačí preukaz ISIC alebo univerzitný e‑mail. Ceny v CampusBoxe sú len pre aktívnych vysokoškolákov.',
        bg: '#F09A72',
        art: IllustrationIsic,
    },
    {
        key: 'platba' as PhoneScreen,
        dx: 40,
        r: 2.5,
        arrow: 'left',
        n: '02',
        title: 'Vyber a rezervuj',
        text: 'Pozri, čo je dnes v okolí, a zaplať v appke kartou alebo cez Apple Pay či Google Pay.',
        bg: '#F6B77E',
        art: IllustrationReserve,
    },
    {
        key: 'qr' as PhoneScreen,
        dx: -40,
        r: -2.5,
        arrow: 'right',
        n: '03',
        title: 'Vyzdvihni alebo si nechaj doniesť',
        text: 'Pri pulte ukážeš QR kód. Ak podnik doručuje, jedlo ti prinesú za poplatok, ktorý určí sám.',
        bg: '#9ED3AE',
        art: IllustrationPickup,
    },
];

const pillars = [
    { label: 'Pre planétu', text: 'Každé zachránené jedlo je o jedno menej vyhodené.' },
    { label: 'Pre podnik', text: 'Zarobí na tom, čo by inak na konci dňa prepadlo.' },
    { label: 'Pre študenta', text: 'Dobré jedlo za cenu, ktorú si môže dovoliť.' },
];

const benefits = [
    'Dodatočný príjem z jedla, ktoré by ste vyhodili',
    'Noví pravidelní zákazníci cez študentské menu',
    'Cenu, množstvo a čas výdaja určujete vy',
    'Doručenie len ak chcete – cenu aj okruh nastavíte sami',
];

const faq = [
    [
        'Kto môže CampusBox používať?',
        'Vysokoškolskí študenti s overeným statusom – cez preukaz ISIC alebo univerzitný e‑mail. Status overujeme raz za rok, na začiatku akademického roka.',
    ],
    [
        'Je nepredané jedlo bezpečné?',
        'Áno. Podniky ponúkajú len jedlo vhodné na konzumáciu a zodpovedajú zaň rovnako ako pri bežnom predaji. Ak niečo nesedí, nahlásiš to v appke a peniaze ti vrátime.',
    ],
    [
        'Doručujete?',
        'Najčastejšie si jedlo vyzdvihneš osobne – preto sú ceny nízke. Niektoré podniky ponúkajú aj doručenie za poplatok, ktorý si určia samé.',
    ],
    [
        'Čo ak nestihnem vyzdvihnutie?',
        'Rezerváciu zrušíš zadarmo kedykoľvek do potvrdenia podnikom, potom do 30 minút pred výdajom. Ak podnik jedlo nevydá, peniaze vrátime automaticky.',
    ],
    [
        'Koľko to stojí podnik?',
        'Žiadny mesačný poplatok. Provízia 10–12 % pri záchrane jedla a 5–7 % pri študentskom menu, len z vydaných objednávok.',
    ],
];
const openFaq = ref(0);
const toggleFaq = (i: number) => (openFaq.value = openFaq.value === i ? -1 : i);

const root = ref<HTMLElement | null>(null);
const videoPhone = ref<InstanceType<typeof VideoPhone> | null>(null);
useLandingMotion(root);
usePauseOffscreen(root);

// ---- Putujúci mobil (desktop ≥ 980 px; na mobile < 640 px zmenšený, vedľa kariet) ----
const story = ref<HTMLElement | null>(null);
const mover = ref<HTMLElement | null>(null);
const clipRect = ref<SVGRectElement | null>(null);
const { desk, mob, heroPad, metrics, screen, route: storyRoute, dots, refresh } = useScrollStory(story, mover, clipRect);
/** Putujúci mobil beží na desktope aj na mobile; medzi tým (tablet) je stránka statická. */
const on = computed(() => desk.value || mob.value);
watch([desk, mob], () => nextTick(refresh));

const active = (k: string) => on.value && screen.value === k;
const phoneScreen = computed<PhoneScreen>(() => (screen.value === 'video' ? 'qr' : (screen.value as PhoneScreen)));

/**
 * Počiatočná poloha mobilu pred prvým výpočtom (vpravo v hero).
 * Zapisuje sa do DOM len raz pri pripojení – nie cez :style. Vue by inak pri každom prekreslení
 * (zmena obrazovky, krúžky trasy) vrátil transform na túto hodnotu a mobil by pri prechode medzi kartami poskakoval.
 */
const initialTransform = () => {
    const w = window.innerWidth;
    const pad = Math.min(48, Math.max(20, w * 0.04));
    const inner = Math.min(w, 1200) - 2 * pad;
    const span0 = w < MOBILE_BREAKPOINT ? (inner - 300) / 2 : Math.max(0, inner - 300);
    const m = storyMetrics(window.innerHeight);
    return `translate3d(${Math.round(span0)}px,${-(612 - m.ph) / 2}px,0) rotate(-2deg)`;
};
watch(mover, (el) => {
    if (!el || el.style.transform) return;
    el.style.transform = initialTransform();
    const zm = el.querySelector<HTMLElement>('[data-lp-zoom]');
    if (zm) zm.style.zoom = String(storyMetrics(window.innerHeight).sc);
});

// ---- Video na poslednej zastávke ----
const clock = useClock();
const overlayVideo = ref<HTMLVideoElement | null>(null);
const overlayMuted = ref(true);
watch(screen, (k) => k !== 'video' && (overlayMuted.value = true));
const playWithSound = () => {
    const v = overlayVideo.value;
    if (!v) return;
    overlayMuted.value = false;
    v.currentTime = 0;
    v.muted = false;
    v.play().catch(() => {});
};
const openFullscreen = () => {
    if (on.value && overlayVideo.value) {
        playWithSound();
        overlayVideo.value.requestFullscreen?.().catch(() => window.open('/video/campusbox-promo.mp4', '_blank'));
    } else if (videoPhone.value) videoPhone.value.fullscreen();
    else window.open('/video/campusbox-promo.mp4', '_blank');
};

const ring = (k: string, color: string) =>
    active(k)
        ? `0 0 0 3px #FFF6EA, 0 0 0 6px ${color}, 0 34px 60px -28px rgba(80,25,5,.5)`
        : '0 0 0 0 rgba(255,246,234,0), 0 0 0 0 rgba(0,0,0,0), 0 0 0 0 rgba(0,0,0,0)';
const glass = (k: string) =>
    active(k)
        ? 'inset 0 1px 0 rgba(255,255,255,.9),0 0 0 2px #DB3B22,0 30px 50px -24px rgba(60,25,5,.45)'
        : 'inset 0 1px 0 rgba(255,255,255,.9),0 0 0 0 rgba(219,59,34,0),0 18px 40px -22px rgba(60,25,5,.4)';
</script>

<template>
    <Head :title="biz ? 'Pre podniky' : 'Jedlo za študentské ceny'" />

    <div ref="root" class="cb-landing min-h-screen bg-cb-cream font-archivo text-cb-ink antialiased">
        <!-- Header -->
        <header
            class="sticky top-0 z-20 border-b-[1.5px] border-[rgba(120,80,50,.14)] bg-[rgba(255,246,234,.78)] backdrop-blur-[18px] backdrop-saturate-[1.6]"
        >
            <div class="cb-container flex h-[72px] items-center justify-between gap-5">
                <a href="#top" class="no-underline" aria-label="CampusBox – domov"><CampusLogo /></a>
                <nav class="flex flex-wrap items-center justify-end gap-[clamp(12px,2.4vw,28px)]">
                    <span class="hidden gap-[clamp(12px,2.4vw,28px)] min-[761px]:flex">
                        <a href="#ako" class="text-[15px] font-semibold text-cb-ink no-underline hover:text-cb-red">Ako to funguje</a>
                        <a href="#podniky" class="text-[15px] font-semibold text-cb-ink no-underline hover:text-cb-red">Pre podniky</a>
                        <a href="#faq" class="text-[15px] font-semibold text-cb-ink no-underline hover:text-cb-red">Otázky</a>
                    </span>
                    <!-- Na úzkom mobile sa popri logu a tlačidle nezmestí; odkaz je aj v sekcii Pre podniky a v pätičke. -->
                    <a :href="loginUrl" class="hidden text-[15px] font-bold text-cb-ink no-underline hover:text-cb-red min-[560px]:block">
                        Prihlásenie pre podniky
                    </a>
                    <a
                        :href="appUrl"
                        class="flex h-[42px] items-center rounded-[21px] bg-cb-red px-[18px] text-[14.5px] font-bold text-white no-underline transition duration-200 hover:-translate-y-[3px] hover:text-white hover:shadow-[0_12px_24px_-10px_rgba(219,59,34,.6)]"
                    >
                        Stiahnuť appku
                    </a>
                </nav>
            </div>
        </header>

        <div ref="story" data-lp-story class="relative">
            <!-- Trasa za putujúcim mobilom -->
            <!-- Vlastná vrstva (will-change): trasa sa pri scrolle prekresľuje každý snímok a bez nej by Safari na iPhone prekresľoval celú stránku pod ňou. -->
            <svg v-if="on" aria-hidden="true" class="pointer-events-none absolute left-0 top-0 z-[1] h-full w-full overflow-visible [will-change:transform]">
                <defs>
                    <clipPath id="lpDone"><rect ref="clipRect" x="0" y="0" width="100%" height="0" /></clipPath>
                </defs>
                <path :d="storyRoute" fill="none" stroke="rgba(42,29,22,.26)" stroke-width="3" stroke-linecap="round" stroke-dasharray="0.1 12.9" />
                <path
                    :d="storyRoute"
                    clip-path="url(#lpDone)"
                    fill="none"
                    stroke="#DB3B22"
                    stroke-width="4.5"
                    stroke-linecap="round"
                    stroke-dasharray="0.1 12.9"
                    class="lp-march"
                />
                <circle
                    v-for="(o, i) in dots"
                    :key="i"
                    :cx="o.x"
                    :cy="o.y"
                    :r="mob ? 5.5 : 7"
                    :fill="o.on ? '#DB3B22' : '#FFF6EA'"
                    :stroke="o.on ? '#FFF6EA' : 'rgba(42,29,22,.35)'"
                    stroke-width="3"
                    class="transition-[fill,stroke] duration-300"
                />
            </svg>

            <!-- Hero -->
            <section id="top" data-step="home" data-x="1" data-r="-2" class="hero-bg relative overflow-hidden">
                <div class="lp-wrap lp-hero-grid" :style="{ minHeight: desk ? metrics.H - 72 + 'px' : undefined }">
                    <div data-stagger="1" class="flex max-w-[600px] flex-col gap-[22px]">
                        <span class="cb-glass flex h-8 items-center gap-2 self-start rounded-2xl px-3.5 text-[13px] font-bold">
                            <span class="h-2 w-2 rounded-full bg-cb-green" />{{ hero.kicker }}
                        </span>
                        <h1 class="m-0 text-[clamp(44px,6.4vw,84px)] font-black leading-[.95] tracking-[-.045em] [text-wrap:pretty]">
                            <span class="block [text-wrap:pretty]">{{ hero.title }}</span>
                            <span v-if="hero.accent" class="block text-cb-red [text-wrap:balance]">{{ hero.accent }}</span>
                        </h1>
                        <p class="m-0 max-w-[520px] text-[clamp(17px,1.6vw,20px)] leading-normal text-cb-brown [text-wrap:pretty]">{{ hero.sub }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-2.5">
                            <a :href="hero.cta1.href" @click="preventPlaceholder" class="cb-btn cb-btn-red min-w-[220px]"
                                ><span>{{ hero.cta1.label }}</span
                                ><span>→</span></a
                            >
                            <a :href="hero.cta2.href" class="cb-btn cb-btn-ink min-w-[200px]"
                                ><span>{{ hero.cta2.label }}</span
                                ><span>→</span></a
                            >
                        </div>
                        <a :href="hero.alt.href" class="text-[15px] font-bold text-cb-red no-underline hover:text-cb-red-dark">{{
                            hero.alt.label
                        }}</a>
                    </div>

                    <!-- Desktop: prázdna bunka (mobil je v overlayi), vľavo od nej maskot s taškou -->
                    <div v-if="desk" data-reveal="1" class="relative flex items-center justify-center" :style="{ minHeight: metrics.ph + 'px' }">
                        <div
                            class="lp-hero-box pointer-events-none absolute bottom-5 flex h-[190px] w-[200px] items-end justify-center"
                            aria-hidden="true"
                        >
                            <div class="aspect-[160/245] h-[170px] flex-none"><IllustrationHero part="mascot" /></div>
                            <div class="-ml-3 aspect-[140/236] h-[160px] flex-none"><IllustrationHero part="bag" /></div>
                        </div>
                    </div>
                    <!-- Tablet: mobil priamo v bunke. Mobil: bunka je len miesto, odkiaľ putujúci mobil vyráža. Maskot a taška pod ním. -->
                    <div
                        v-else
                        data-reveal="1"
                        data-step-anchor
                        class="relative mb-20 flex min-h-[560px] justify-center sm:mb-0"
                        :style="mob ? { height: metrics.ph + 'px', minHeight: '0' } : undefined"
                    >
                        <div
                            class="pointer-events-none absolute -bottom-[104px] right-[min(calc(50%+122px),calc(100%-92px))] z-10 aspect-[160/245] w-[92px] sm:-bottom-12 sm:right-[min(calc(50%+122px),calc(100%-112px))] sm:w-[112px]"
                        >
                            <IllustrationHero part="mascot" />
                        </div>
                        <div
                            class="pointer-events-none absolute -bottom-[100px] left-[min(calc(50%+126px),calc(100%-84px))] z-10 aspect-[140/236] w-[84px] sm:-bottom-12 sm:left-[min(calc(50%+126px),calc(100%-100px))] sm:w-[100px]"
                        >
                            <IllustrationHero part="bag" />
                        </div>
                        <LandingPhone v-if="!mob" screen="home" mode="live" />
                    </div>
                </div>
            </section>

            <!-- Dve kategórie -->
            <section class="border-t-2 border-[rgba(120,80,50,.14)]">
                <div class="lp-wrap cb-section-pad flex flex-col gap-9">
                    <div data-reveal="1" class="lp-head flex max-w-[760px] flex-col gap-3">
                        <span class="cb-eyebrow">Dve kategórie</span>
                        <h2 class="cb-h2">Večer záchrana, cez obed menu.</h2>
                        <p class="m-0 text-lg leading-normal text-cb-brown [text-wrap:pretty]">
                            V appke sú oddelené, aby si hneď videl, čo je časovo obmedzená ponuka a čo pravidelné študentské stravovanie.
                        </p>
                    </div>
                    <div class="lp-kat-grid">
                        <div class="lp-step" data-step="zachrana" data-side="l" data-mgo=".04,.34" data-x=".5" data-dx="-24" data-s=".94" data-r="-3" data-reveal="1">
                            <div
                                class="lp-card lp-lift relative flex flex-col gap-3.5 rounded-[32px] bg-cb-red p-[clamp(24px,3vw,36px)] text-white"
                                :style="{ boxShadow: ring('zachrana', '#DB3B22') }"
                            >
                                <span class="lp-chip lp-chip-pos" aria-hidden="true" :style="{ opacity: active('zachrana') ? 1 : 0 }"
                                    >Teraz v appke →</span
                                >
                                <span class="text-sm font-bold opacity-85">Hlavná služba</span>
                                <span class="lp-t text-[clamp(30px,3vw,40px)] font-black leading-none tracking-[-.03em]">Záchrana jedla</span>
                                <span class="lp-b text-[16.5px] leading-normal [text-wrap:pretty]">
                                    Čo podniku na konci dňa zostane – bezpečné a čerstvé, len lacnejšie. Balíčky prekvapenia aj konkrétne jedlá.
                                </span>
                                <div class="mt-1.5 flex flex-wrap items-end justify-between gap-3 border-t-2 border-white/35 pt-4">
                                    <span class="flex flex-col">
                                        <span class="text-[13px] font-bold opacity-85">od</span>
                                        <span class="lp-price text-[44px] font-black leading-none tracking-[-.04em]" data-count="9|1.9|2| €">1,90 €</span>
                                    </span>
                                    <span class="text-sm font-bold">Večer · kým sa neminie</span>
                                </div>
                            </div>
                        </div>
                        <div class="lp-step" data-step="menu" data-side="r" data-x=".5" data-dx="24" data-s=".94" data-r="3" data-reveal="1">
                            <div
                                class="lp-card lp-lift relative flex flex-col gap-3.5 rounded-[32px] bg-cb-mint p-[clamp(24px,3vw,36px)] text-cb-ink"
                                :style="{ boxShadow: ring('menu', '#15803D') }"
                            >
                                <span class="lp-chip lp-chip-pos" aria-hidden="true" :style="{ opacity: active('menu') ? 1 : 0 }"
                                    >← Teraz v appke</span
                                >
                                <span class="text-sm font-bold text-cb-green">Každý pracovný deň</span>
                                <span class="lp-t text-[clamp(30px,3vw,40px)] font-black leading-none tracking-[-.03em]">Študentské menu</span>
                                <span class="lp-b text-[16.5px] leading-normal text-[#2F3A2F] [text-wrap:pretty]">
                                    Bežné obedové menu so študentskou cenou. Polievka a hlavné jedlo v podnikoch pri fakultách a internátoch.
                                </span>
                                <div class="mt-1.5 flex flex-wrap items-end justify-between gap-3 border-t-2 border-[rgba(21,128,61,.25)] pt-4">
                                    <span class="flex flex-col">
                                        <span class="text-[13px] font-bold text-cb-green">od</span>
                                        <span class="lp-price text-[44px] font-black leading-none tracking-[-.04em] text-cb-green" data-count="8.5|5.5|2| €"
                                            >5,50 €</span
                                        >
                                    </span>
                                    <span class="text-sm font-bold">Obed · 11:00–15:00</span>
                                </div>
                            </div>
                        </div>
                        <div class="lp-stk" aria-hidden="true" data-reveal="1">
                            <span class="block rotate-[6deg]"
                                ><span
                                    class="lp-float flex h-12 items-center gap-2.5 whitespace-nowrap rounded-[14px] bg-cb-green pl-3.5 pr-[18px] text-[21px] font-black tracking-[-.02em] text-white shadow-[0_16px_30px_-14px_rgba(21,128,61,.65)] [animation-duration:3.6s]"
                                    ><span class="h-[9px] w-[9px] rounded-full bg-cb-mint" />až −70 %</span
                                ></span
                            >
                            <span class="mr-9 block -rotate-[4deg]"
                                ><span class="lp-pill lp-float [animation-delay:.6s] [animation-duration:4.2s]"
                                    ><span class="lp-dot bg-cb-red" />Len pre vysokoškolákov</span
                                ></span
                            >
                        </div>
                    </div>
                </div>
            </section>

            <!-- Ako to funguje -->
            <section id="ako" class="scroll-mt-[72px] border-t-2 border-[rgba(120,80,50,.14)] bg-cb-sand">
                <div class="lp-wrap cb-section-pad flex flex-col gap-9">
                    <div data-reveal="1" class="lp-head lp-head-r flex max-w-[760px] flex-col gap-3">
                        <span class="cb-eyebrow">Ako to funguje</span>
                        <h2 class="cb-h2">Tri kroky od prednášky k jedlu.</h2>
                    </div>
                    <div class="lp-ako-grid">
                        <div
                            v-for="s in steps"
                            :key="s.n"
                            class="lp-step"
                            :data-step="s.key"
                            :data-side="s.dx < 0 ? 'r' : 'l'"
                            data-x=".5"
                            :data-dx="s.dx"
                            data-s=".9"
                            :data-r="s.r"
                            data-reveal="1"
                        >
                            <div
                                class="lp-card lp-lift relative flex flex-col overflow-hidden rounded-[32px] backdrop-blur-[22px] backdrop-saturate-[1.7] [transition:box-shadow_.35s_ease,background_.35s_ease,transform_.2s_ease]"
                                :style="{ background: active(s.key) ? 'rgba(255,255,255,.94)' : 'rgba(255,255,255,.6)', boxShadow: glass(s.key) }"
                            >
                                <div class="lp-ako-vis relative overflow-hidden" :style="{ background: s.bg }">
                                    <component :is="s.art" v-if="on" />
                                    <div v-else class="absolute left-1/2 top-[26px] -ml-[150px] h-[612px] w-[300px] origin-top scale-[.62]">
                                        <LandingPhone :screen="s.key" mode="preview" />
                                    </div>
                                </div>
                                <span class="lp-chip absolute right-4 top-4" aria-hidden="true" :style="{ opacity: active(s.key) ? 1 : 0 }">
                                    {{ s.arrow === 'right' ? 'Teraz v appke →' : '← Teraz v appke' }}
                                </span>
                                <div class="lp-ako-txt flex min-w-0 flex-1 flex-col justify-center gap-2 px-6 pb-[26px] pt-[22px]">
                                    <span class="text-sm font-extrabold text-cb-red">{{ s.n }}</span>
                                    <span class="lp-t text-2xl font-black leading-[1.1] tracking-[-.02em]">{{ s.title }}</span>
                                    <span class="lp-b text-base leading-normal text-cb-brown [text-wrap:pretty]">{{ s.text }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="lp-stk lp-stk-a" aria-hidden="true" data-reveal="1">
                            <span class="block rotate-[4deg]"
                                ><span
                                    class="lp-float flex items-center gap-2 rounded-[22px] bg-cb-red px-[17px] py-[11px] text-[15px] font-extrabold leading-[1.2] text-white shadow-[0_16px_30px_-14px_rgba(168,42,21,.6)] [animation-duration:3.8s]"
                                    ><span class="lp-dot bg-[#FFD9C7]" />Overenie raz za rok</span
                                ></span
                            >
                            <span class="mr-7 block -rotate-[3deg]"
                                ><span class="lp-pill lp-float [animation-delay:.5s] [animation-duration:4.4s]"
                                    ><span class="lp-dot bg-cb-green" />ISIC alebo školský e‑mail</span
                                ></span
                            >
                        </div>
                        <div class="lp-stk lp-stk-b" aria-hidden="true" data-reveal="1">
                            <span class="block -rotate-[3deg]"
                                ><span
                                    class="lp-float flex items-center gap-2 rounded-[20px] bg-cb-ink px-4 py-2.5 text-sm font-extrabold leading-[1.2] text-cb-cream shadow-[0_16px_30px_-14px_rgba(42,29,22,.6)] [animation-delay:.3s] [animation-duration:4s]"
                                    ><span class="lp-dot bg-[#4ADE80]" />Apple Pay · Google Pay · karta</span
                                ></span
                            >
                            <span class="ml-6 block rotate-[2deg]"
                                ><span class="lp-pill lp-float [animation-delay:.9s] [animation-duration:4.6s]"
                                    ><span class="lp-dot bg-cb-red" />Zrušíš zadarmo do potvrdenia</span
                                ></span
                            >
                        </div>
                        <div class="lp-stk lp-stk-c" aria-hidden="true" data-reveal="1">
                            <span class="block rotate-[3deg]"
                                ><span
                                    class="lp-float flex items-center gap-2 rounded-[22px] bg-cb-green px-[17px] py-[11px] text-[15px] font-extrabold leading-[1.2] text-white shadow-[0_16px_30px_-14px_rgba(21,128,61,.6)] [animation-delay:.4s] [animation-duration:3.9s]"
                                    ><span class="lp-dot bg-cb-mint" />Doručenie, ak ho podnik ponúka</span
                                ></span
                            >
                        </div>
                    </div>
                </div>
            </section>

            <!-- Video -->
            <section id="video" class="scroll-mt-[72px] border-t-2 border-[rgba(120,80,50,.14)]">
                <div class="lp-wrap lp-vid-grid cb-section-pad items-center">
                    <div data-reveal="1" class="lp-vid-txt flex max-w-[480px] flex-col gap-4">
                        <span class="cb-eyebrow">Video</span>
                        <h2 class="m-0 text-[clamp(34px,4.6vw,60px)] font-black leading-[.98] tracking-[-.04em] [text-wrap:balance]">
                            CampusBox za 30 sekúnd
                        </h2>
                        <p class="m-0 text-lg leading-[1.55] text-cb-brown [text-wrap:pretty]">
                            Koniec mesiaca, prázdny účet a plné vitríny v bistrách. Pozri si, ako si cez appku rezervuješ zachránené jedlo a
                            vyzdvihneš ho cestou z prednášky.
                        </p>
                        <div class="mt-2 flex flex-wrap gap-3">
                            <a
                                :href="appUrl"
                                @click="preventPlaceholder"
                                class="lp-vid-btn flex h-[52px] items-center rounded-[26px] bg-cb-red px-6 text-base font-extrabold text-white no-underline hover:text-white"
                            >
                                Stiahnuť appku
                            </a>
                            <button
                                type="button"
                                class="lp-vid-btn flex h-[52px] items-center rounded-[26px] border-2 border-[rgba(42,29,22,.18)] px-[22px] text-base font-bold text-cb-ink transition-colors hover:border-cb-ink"
                                @click="openFullscreen"
                            >
                                Pozrieť na celú obrazovku
                            </button>
                        </div>
                    </div>
                    <div data-reveal="1" class="flex justify-center">
                        <!-- Zástupné miesto, kam dosadne putujúci mobil -->
                        <div v-if="on" data-step="video" data-mgo=".84,1" data-x="1" data-r="0" class="w-[300px] flex-none" :style="{ height: metrics.ph + 'px' }" />
                        <VideoPhone v-else ref="videoPhone" src="/video/campusbox-promo.mp4" poster="/video/campusbox-promo-poster.jpg" />
                    </div>
                </div>
            </section>

            <!-- Overlay s putujúcim mobilom -->
            <!-- Na mobile bez orezania: Safari na iPhone s `overflow-x: clip` na predkovi neposúva prilepený prvok plynulo a mobil pri scrolle seká. -->
            <div v-if="on" class="pointer-events-none absolute inset-0 z-[5]" :class="mob ? '' : 'overflow-x-clip'" :aria-hidden="screen !== 'video'">
                <div class="lp-wrap h-full pb-[clamp(56px,8vw,104px)]" :style="{ paddingTop: (mob ? heroPad : 0) + 'px' }">
                    <div data-lp-stick class="sticky w-[300px]" :style="{ top: metrics.top + 'px', height: metrics.ph + 'px' }">
                        <div class="lp-phone-in">
                            <div
                                ref="mover"
                                data-lp-mover
                                class="relative h-[612px] w-[300px] origin-center [will-change:transform]"
                            >
                                <!-- Stála mierka cez zoom: prehliadač telefón vykreslí ostro v cieľovej veľkosti (scale v transforme by ho len roztiahol z bitmapy). -->
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div data-lp-zoom class="relative h-[612px] w-[300px] flex-none">
                                        <LandingPhone :screen="phoneScreen" mode="live" />
                                        <div
                                            v-if="screen === 'video'"
                                            class="lp-video-in pointer-events-auto absolute left-[9px] top-[9px] box-border flex h-[594px] w-[282px] flex-col gap-2.5 overflow-hidden rounded-[40px] bg-[#FFF2E4] pt-[14px] text-cb-ink"
                                        >
                                            <div class="relative flex h-[26px] flex-none items-center justify-between px-[26px] text-xs font-bold">
                                                <span>{{ clock }}</span>
                                                <span class="absolute left-1/2 top-[2px] h-[26px] w-[92px] -translate-x-1/2 rounded-[14px] bg-black" />
                                                <span class="text-[10px]">5G</span>
                                            </div>
                                            <div class="relative h-[502px] w-[282px] flex-none">
                                                <video
                                                    ref="overlayVideo"
                                                    class="block h-full w-full object-cover"
                                                    src="/video/campusbox-promo.mp4"
                                                    poster="/video/campusbox-promo-poster.jpg"
                                                    muted
                                                    playsinline
                                                    autoplay
                                                    loop
                                                    preload="metadata"
                                                    aria-label="Promo video CampusBox"
                                                />
                                                <button
                                                    v-if="overlayMuted"
                                                    type="button"
                                                    class="absolute right-2.5 top-2.5 flex h-8 items-center gap-1.5 whitespace-nowrap rounded-2xl bg-cb-red pl-2.5 pr-3 text-xs font-bold text-white shadow-[0_10px_22px_-10px_rgba(120,30,10,.6)] transition-transform hover:-translate-y-px"
                                                    @click="playWithSound"
                                                >
                                                    <svg
                                                        aria-hidden="true"
                                                        width="15"
                                                        height="15"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2.2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    >
                                                        <path d="M11 5 6 9H3v6h3l5 4z" />
                                                        <path d="M15.5 8.5a5 5 0 0 1 0 7" />
                                                        <path d="M18.5 5.5a9 9 0 0 1 0 13" />
                                                    </svg>
                                                    Pustiť so zvukom
                                                </button>
                                            </div>
                                            <div class="flex flex-1 items-center justify-center">
                                                <span class="h-[5px] w-[110px] rounded-[3px] bg-cb-ink" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manifest -->
        <section class="bg-cb-red text-white">
            <div data-stagger="1" class="cb-container cb-section-pad flex flex-col gap-[clamp(36px,5vw,64px)]">
                <h2 class="m-0 text-[clamp(40px,7vw,96px)] font-black leading-[.92] tracking-[-.045em] [text-wrap:balance]">
                    Menej jedla v koši.<br /><span class="text-[#FFD9C7]">Viac na tanieri študenta.</span>
                </h2>
                <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,240px),1fr))] border-t-2 border-white/90">
                    <div v-for="p in pillars" :key="p.label" class="flex flex-col gap-2 pr-6 pt-5">
                        <span class="text-[13px] font-extrabold uppercase tracking-[.08em]">{{ p.label }}</span>
                        <span class="text-[17px] leading-normal [text-wrap:pretty]">{{ p.text }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pre podniky -->
        <section id="podniky" class="scroll-mt-[72px]">
            <div
                data-stagger="1"
                class="cb-container cb-section-pad grid grid-cols-[repeat(auto-fit,minmax(min(100%,460px),1fr))] items-start gap-10"
            >
                <div class="flex flex-col gap-[22px]">
                    <span class="cb-eyebrow">Pre podniky</span>
                    <h2 class="cb-h2">Jedlo, ktoré vám zostane, si kúpia študenti.</h2>
                    <p class="m-0 text-lg leading-normal text-cb-brown [text-wrap:pretty]">
                        Reštaurácie, bistrá, kaviarne aj pekárne. Ponuku zverejníte za minútu a výdaj overíte QR kódom.
                    </p>
                    <div data-stagger="1" class="flex flex-col border-t-2 border-cb-ink">
                        <div
                            v-for="b in benefits"
                            :key="b"
                            class="grid grid-cols-[32px_1fr] gap-2.5 border-b-[1.5px] border-[rgba(120,80,50,.18)] py-4 text-[17px] font-semibold"
                        >
                            <span class="font-black text-cb-green">✓</span><span>{{ b }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2.5">
                        <a :href="registerUrl" class="cb-btn cb-btn-red min-w-[250px]"><span>Zaregistrovať podnik</span><span>→</span></a>
                        <button type="button" class="cb-btn cb-btn-ink min-w-[220px]" :disabled="demoStarting" @click="startDemo">
                            <span>{{ demoStarting ? 'Pripravujeme…' : 'Vyskúšať demo panel' }}</span
                            ><span>→</span>
                        </button>
                    </div>
                    <span v-if="demoError" class="text-sm font-bold text-cb-red" role="alert">{{ demoError }}</span>
                    <span v-else class="text-sm text-cb-taupe">Demo otvorí ukážkové bistro bez registrácie – nič sa neposiela ani neúčtuje.</span>
                    <span class="text-[15px] font-semibold text-cb-taupe">
                        Už máte účet podniku?
                        <a :href="loginUrl" class="font-bold text-cb-red hover:text-cb-red-dark">Prihlásiť sa do panela →</a>
                    </span>
                </div>

                <div class="cb-glass flex flex-col gap-5 rounded-[32px] !bg-white p-[clamp(24px,3vw,36px)]">
                    <span class="text-xl font-extrabold">Platíte len z toho, čo predáte</span>
                    <div class="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-[18px]">
                        <div class="flex flex-col gap-1">
                            <span class="text-5xl font-black leading-none tracking-[-.04em]" data-count="29|0|0| €">0 €</span>
                            <span class="text-sm text-cb-taupe">mesačný poplatok</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-5xl font-black leading-none tracking-[-.04em]" data-count="0|14|0| dní">14 dní</span>
                            <span class="text-sm text-cb-taupe">interval výplat</span>
                        </div>
                    </div>
                    <div class="flex flex-col border-t-2 border-[rgba(120,80,50,.16)]">
                        <div class="flex items-baseline justify-between gap-3 border-b-[1.5px] border-[rgba(120,80,50,.16)] py-4">
                            <span class="flex flex-col gap-0.5">
                                <span class="text-[17px] font-extrabold">Záchrana jedla</span>
                                <span class="text-[13.5px] text-cb-taupe">provízia z vydanej objednávky</span>
                            </span>
                            <span class="whitespace-nowrap text-[30px] font-black tracking-[-.03em] text-cb-red">10–12 %</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-4">
                            <span class="flex flex-col gap-0.5">
                                <span class="text-[17px] font-extrabold">Študentské menu</span>
                                <span class="text-[13.5px] text-cb-taupe">nižšia provízia, aby sa menu oplatilo</span>
                            </span>
                            <span class="whitespace-nowrap text-[30px] font-black tracking-[-.03em] text-cb-green">5–7 %</span>
                        </div>
                    </div>
                    <span class="text-[13px] leading-normal text-cb-taupe">Konečnú výšku provízie stanovíme po pilotnej prevádzke.</span>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="scroll-mt-[72px] border-t-2 border-[rgba(120,80,50,.14)]">
            <div
                data-stagger="1"
                class="cb-container cb-section-pad grid grid-cols-[repeat(auto-fit,minmax(min(100%,360px),1fr))] items-start gap-10"
            >
                <div class="flex flex-col gap-3">
                    <span class="cb-eyebrow">Otázky</span>
                    <h2 class="cb-h2">Čo sa nás pýtate.</h2>
                    <span class="text-base text-cb-brown">
                        Niečo chýba? Napíš na
                        <a href="mailto:ahoj@campusbox.sk" class="font-bold text-cb-red hover:text-cb-red-dark">ahoj@campusbox.sk</a>
                    </span>
                </div>
                <div data-stagger="1" class="flex flex-col border-t-2 border-cb-ink">
                    <div v-for="([q, a], i) in faq" :key="q" class="border-b-[1.5px] border-[rgba(120,80,50,.2)]">
                        <button
                            type="button"
                            class="box-border grid w-full cursor-pointer grid-cols-[1fr_32px] items-center gap-3 py-5 text-left text-lg font-extrabold"
                            :aria-expanded="openFaq === i"
                            @click="toggleFaq(i)"
                        >
                            <span>{{ q }}</span>
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-full text-xl font-bold transition-colors"
                                :class="openFaq === i ? 'bg-cb-red text-white' : 'bg-[rgba(120,80,50,.12)] text-cb-ink'"
                            >
                                {{ openFaq === i ? '−' : '+' }}
                            </span>
                        </button>
                        <p v-if="openFaq === i" class="m-0 pb-[22px] pr-11 text-base leading-[1.55] text-cb-brown [text-wrap:pretty]">{{ a }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-cb-ink text-cb-cream">
            <div class="cb-container flex flex-col gap-9 pb-10 pt-14">
                <div class="flex flex-wrap items-start justify-between gap-7">
                    <div class="flex max-w-[340px] flex-col gap-3">
                        <CampusLogo :size="44" :text-size="28" inverted />
                        <span class="text-[14.5px] leading-normal text-cb-beige">
                            Cenovo dostupné jedlo pre vysokoškolákov a menej potravinového odpadu v gastre.
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-12 text-[15px]">
                        <div class="flex flex-col gap-2.5">
                            <span class="text-[13px] font-extrabold uppercase tracking-[.06em] text-cb-beige">Študenti</span>
                            <a href="#ako" class="text-cb-cream no-underline hover:text-white">Ako to funguje</a>
                            <a :href="appUrl" class="text-cb-cream no-underline hover:text-white">Stiahnuť appku</a>
                            <a href="#faq" class="text-cb-cream no-underline hover:text-white">Otázky</a>
                        </div>
                        <div class="flex flex-col gap-2.5">
                            <span class="text-[13px] font-extrabold uppercase tracking-[.06em] text-cb-beige">Podniky</span>
                            <a href="#podniky" class="text-cb-cream no-underline hover:text-white">Ceny a provízie</a>
                            <a :href="registerUrl" class="text-cb-cream no-underline hover:text-white">Registrácia podniku</a>
                            <button type="button" class="text-left text-cb-cream hover:text-white" @click="startDemo">Demo panel</button>
                            <a :href="route('login')" class="text-cb-cream no-underline hover:text-white">Prihlásenie do panela</a>
                        </div>
                        <div class="flex flex-col gap-2.5">
                            <span class="text-[13px] font-extrabold uppercase tracking-[.06em] text-cb-beige">Kontakt</span>
                            <a href="mailto:ahoj@campusbox.sk" class="text-cb-cream no-underline hover:text-white">ahoj@campusbox.sk</a>
                            <span>Banská Bystrica</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap justify-between gap-3 border-t-[1.5px] border-[rgba(255,246,234,.16)] pt-5 text-[13px] text-[#9C8574]">
                    <span>© {{ new Date().getFullYear() }} CampusBox</span>
                    <span>Obchodné podmienky · Ochrana osobných údajov</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* ---- Putujúci mobil: rozloženie (desktop ≥ 980 px, rovnaký breakpoint ako STORY_BREAKPOINT) ---- */
.lp-wrap {
    max-width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
    padding-left: clamp(20px, 4vw, 48px);
    padding-right: clamp(20px, 4vw, 48px);
}
.lp-hero-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 440px), 1fr));
    gap: 48px;
    align-items: center;
    padding-top: clamp(40px, 7vw, 88px);
    padding-bottom: clamp(48px, 7vw, 96px);
}
.lp-kat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr));
    gap: 16px;
}
.lp-ako-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
    gap: 16px;
}
.lp-step {
    display: flex;
    flex-direction: column;
}
.lp-step > .lp-card {
    flex: 1;
}
.lp-ako-vis {
    height: 280px;
    flex: none;
}
.lp-vid-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr));
    gap: clamp(32px, 5vw, 72px);
}
.lp-chip,
.lp-stk {
    display: none;
}
.lp-chip {
    height: 28px;
    padding: 0 12px;
    border-radius: 14px;
    background: #2a1d16;
    color: #fff6ea;
    font-size: 12.5px;
    font-weight: 700;
    align-items: center;
    gap: 6px;
    pointer-events: none;
    transition: opacity 0.3s ease;
    z-index: 3;
}
.lp-chip-pos {
    position: absolute;
    top: clamp(18px, 2.4vw, 28px);
    right: clamp(18px, 2.4vw, 28px);
}
.lp-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 20px;
    background: #fffdf8;
    border: 2px solid rgba(120, 80, 50, 0.16);
    color: #2a1d16;
    font-weight: 800;
    font-size: 14px;
    line-height: 1.2;
    box-shadow: 0 14px 28px -16px rgba(60, 25, 5, 0.45);
}
.lp-dot {
    width: 8px;
    height: 8px;
    border-radius: 4px;
    flex: none;
}
.lp-float {
    animation-name: cbFloat2;
    animation-timing-function: ease-in-out;
    animation-iteration-count: infinite;
}
.lp-lift {
    transition:
        box-shadow 0.35s ease,
        transform 0.2s ease;
}
.lp-lift:hover {
    transform: translateY(-6px);
}
.lp-march {
    animation: lpMarch 1.1s linear infinite;
}
/* Mobil: pochodujúce bodky by prekresľovali trasu každý snímok aj bez scrollu – na iPhone to seká putujúci mobil. */
@media (max-width: 639.98px) {
    .lp-march {
        animation: none;
    }
}
.lp-phone-in {
    animation: lpPhoneIn 0.9s cubic-bezier(0.2, 0.8, 0.2, 1) 0.15s both;
}
.lp-video-in {
    animation: lpVideoIn 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}
@keyframes lpMarch {
    to {
        stroke-dashoffset: -26;
    }
}
@keyframes lpPhoneIn {
    from {
        opacity: 0;
        transform: translateY(28px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}
@keyframes lpVideoIn {
    from {
        opacity: 0;
        transform: translateY(14px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

/* Mobil (rovnaký breakpoint ako MOBILE_BREAKPOINT): karta na jednej strane, na druhej miesto pre zmenšený putujúci mobil (36 % = MOBILE_PHONE_SHARE). */
@media (max-width: 639.98px) {
    .lp-kat-grid,
    .lp-ako-grid {
        gap: 44px;
    }
    .lp-step {
        position: relative;
        z-index: 2;
    }
    .lp-step[data-side='l'],
    .lp-head {
        margin-right: calc(36% + 12px);
    }
    .lp-step[data-side='r'],
    .lp-head-r,
    .lp-vid-txt {
        margin-left: calc(36% + 12px);
    }
    .lp-head-r {
        margin-right: 0;
    }
    .lp-head :deep(h2),
    .lp-vid-txt h2 {
        font-size: 29px;
        line-height: 1;
    }
    .lp-head p,
    .lp-vid-txt p {
        font-size: 15px;
    }
    .lp-vid-btn {
        box-sizing: border-box;
        width: 100%;
        height: auto;
        min-height: 48px;
        justify-content: center;
        padding: 10px 14px;
        font-size: 14.5px;
        line-height: 1.2;
        text-align: center;
    }
    .lp-step > .lp-card {
        flex: none;
        border-radius: 26px;
    }
    .lp-kat-grid .lp-card {
        padding: 18px;
        gap: 10px;
    }
    .lp-step .lp-t {
        font-size: 23px;
        line-height: 1.05;
    }
    .lp-ako-grid .lp-t {
        font-size: 19px;
        line-height: 1.12;
    }
    .lp-step .lp-b {
        font-size: 14.5px;
        line-height: 1.45;
    }
    .lp-step .lp-price {
        font-size: 36px;
    }
    .lp-ako-vis {
        height: 132px;
    }
    .lp-ako-txt {
        padding: 16px 16px 20px;
    }
}

@media (min-width: 980px) {
    .lp-hero-grid {
        grid-template-columns: minmax(0, 1fr) 300px;
        column-gap: clamp(48px, 6vw, 96px);
        padding-top: 40px;
        padding-bottom: 40px;
    }
    .lp-hero-box {
        left: calc(clamp(150px, 22vw - 60px, 224px) * -1);
    }
    .lp-head {
        max-width: calc((100% - 370px) / 2 - clamp(20px, 2.5vw, 40px)) !important;
    }
    .lp-step {
        position: relative;
        z-index: 2;
    }
    .lp-step > .lp-card {
        flex: none;
    }
    .lp-kat-grid,
    .lp-ako-grid {
        grid-template-columns: minmax(0, 1fr) 370px minmax(0, 1fr);
        column-gap: clamp(20px, 2.5vw, 40px);
        row-gap: 0;
        align-items: start;
    }
    .lp-kat-grid > [data-step='zachrana'] {
        grid-column: 1;
        grid-row: 1;
    }
    .lp-kat-grid > [data-step='menu'] {
        grid-column: 3;
        grid-row: 1;
        margin-top: 240px;
    }
    .lp-kat-grid > .lp-stk {
        grid-column: 3;
        grid-row: 1;
        margin-top: 20px;
    }
    .lp-ako-grid {
        grid-auto-rows: 150px;
    }
    .lp-ako-grid > [data-step='overenie'] {
        grid-column: 1;
        grid-row: 1 / span 3;
    }
    .lp-ako-grid > [data-step='platba'] {
        grid-column: 3;
        grid-row: 3 / span 3;
    }
    .lp-ako-grid > [data-step='qr'] {
        grid-column: 1;
        grid-row: 5 / span 3;
    }
    .lp-ako-grid > .lp-stk-a {
        grid-column: 3;
        grid-row: 1 / span 2;
        margin-top: 28px;
    }
    .lp-ako-grid > .lp-stk-b {
        grid-column: 1;
        grid-row: 4;
        align-self: end;
        align-items: flex-start;
        margin-bottom: 22px;
    }
    .lp-ako-grid > .lp-stk-c {
        grid-column: 3;
        grid-row: 6 / span 2;
        margin-top: 56px;
    }
    .lp-vid-grid {
        grid-template-columns: minmax(0, 1fr) 300px;
        column-gap: clamp(48px, 6vw, 96px);
    }
    .lp-ako-vis {
        height: 190px;
    }
    .lp-chip {
        display: flex;
    }
    .lp-stk {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 14px;
        position: relative;
        z-index: 2;
        pointer-events: none;
    }
}

.hero-bg {
    background-color: #fff6ea;
    background-image:
        linear-gradient(to bottom, rgba(255, 246, 234, 0) 55%, #fff6ea 100%),
        radial-gradient(60% 70% at 85% 15%, rgba(255, 197, 154, 0.85) 0%, rgba(255, 197, 154, 0) 70%),
        radial-gradient(55% 60% at 8% 55%, rgba(255, 214, 170, 0.7) 0%, rgba(255, 214, 170, 0) 70%),
        radial-gradient(45% 55% at 100% 70%, rgba(207, 239, 217, 0.8) 0%, rgba(207, 239, 217, 0) 70%);
}
</style>
