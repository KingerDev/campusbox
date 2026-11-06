<script setup lang="ts">
import CampusLogo from '@/components/landing/CampusLogo.vue';
import IllustrationHero from '@/components/landing/IllustrationHero.vue';
import IllustrationIsic from '@/components/landing/IllustrationIsic.vue';
import IllustrationPickup from '@/components/landing/IllustrationPickup.vue';
import IllustrationReserve from '@/components/landing/IllustrationReserve.vue';
import PhoneMockup from '@/components/landing/PhoneMockup.vue';
import { useLandingMotion } from '@/composables/useLandingMotion';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = withDefaults(defineProps<{ audience?: 'studenti' | 'podniky'; appUrl?: string }>(), {
    audience: 'studenti',
    appUrl: '#',
});

const biz = computed(() => props.audience === 'podniky');
// Registrácia podnikov zatiaľ nie je spustená – odkazy sú dočasne nefunkčné.
const registerUrl = '#';
const preventPlaceholder = (e: MouseEvent) => {
    if ((e.currentTarget as HTMLAnchorElement).getAttribute('href') === '#') e.preventDefault();
};

const hero = computed(() =>
    biz.value
        ? {
              kicker: 'Pre reštaurácie, bistrá, kaviarne a pekárne',
              title: 'Zarobte na jedle, ktoré by ste vyhodili.',
              sub: 'Zverejnite nepredané jedlo alebo študentské menu za minútu. Bez mesačného poplatku – províziu platíte len z vydaných objednávok.',
              cta1: { label: 'Zaregistrovať podnik', href: registerUrl },
              cta2: { label: 'Ceny a provízie', href: '#podniky' },
              alt: { label: 'Ste študent? Pozrite appku →', href: '#ako' },
          }
        : {
              kicker: 'Pilot v Banskej Bystrici · pri UMB',
              title: 'Dobré jedlo pri kampuse. Za študentské ceny.',
              sub: 'CampusBox spája študentov s bistrami, pekárňami a kaviarňami v okolí. Zachráň nepredané jedlo až o 70 % lacnejšie alebo si daj zvýhodnené obedové menu.',
              cta1: { label: 'Stiahnuť appku', href: props.appUrl },
              cta2: { label: 'Ako to funguje', href: '#ako' },
              alt: { label: 'Máte podnik? Pridajte sa →', href: '#podniky' },
          },
);

const steps = [
    {
        n: '01',
        title: 'Over sa ako študent',
        text: 'Stačí preukaz ISIC alebo univerzitný e‑mail. Ceny v CampusBoxe sú len pre aktívnych vysokoškolákov.',
        bg: '#F09A72',
        art: IllustrationIsic,
    },
    {
        n: '02',
        title: 'Vyber a rezervuj',
        text: 'Pozri, čo je dnes v okolí, a zaplať v appke kartou alebo cez Apple Pay či Google Pay.',
        bg: '#F6B77E',
        art: IllustrationReserve,
    },
    {
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
    ['Kto môže CampusBox používať?', 'Vysokoškolskí študenti s overeným statusom – cez preukaz ISIC alebo univerzitný e‑mail. Status overujeme na začiatku každého semestra.'],
    ['Je nepredané jedlo bezpečné?', 'Áno. Podniky ponúkajú len jedlo vhodné na konzumáciu a zodpovedajú zaň rovnako ako pri bežnom predaji. Ak niečo nesedí, nahlásiš to v appke a peniaze ti vrátime.'],
    ['Doručujete?', 'Najčastejšie si jedlo vyzdvihneš osobne – preto sú ceny nízke. Niektoré podniky ponúkajú aj doručenie za poplatok, ktorý si určia samé.'],
    ['Čo ak nestihnem vyzdvihnutie?', 'Rezerváciu zrušíš zadarmo kedykoľvek do potvrdenia podnikom, potom do 30 minút pred výdajom. Ak podnik jedlo nevydá, peniaze vrátime automaticky.'],
    ['Koľko to stojí podnik?', 'Žiadny mesačný poplatok. Provízia 10–12 % pri záchrane jedla a 5–7 % pri študentskom menu, len z vydaných objednávok.'],
];
const openFaq = ref(0);
const toggleFaq = (i: number) => (openFaq.value = openFaq.value === i ? -1 : i);

const root = ref<HTMLElement | null>(null);
useLandingMotion(root);
</script>

<template>
    <Head :title="biz ? 'Pre podniky' : 'Jedlo za študentské ceny'" />

    <div ref="root" class="cb-landing min-h-screen bg-cb-cream font-archivo text-cb-ink antialiased">
        <!-- Header -->
        <header class="sticky top-0 z-20 border-b-[1.5px] border-[rgba(120,80,50,.14)] bg-[rgba(255,246,234,.78)] backdrop-blur-[18px] backdrop-saturate-[1.6]">
            <div class="cb-container flex h-[72px] items-center justify-between gap-5">
                <a href="#top" class="no-underline" aria-label="CampusBox – domov"><CampusLogo /></a>
                <nav class="flex flex-wrap items-center justify-end gap-[clamp(12px,2.4vw,28px)]">
                    <span class="hidden gap-[clamp(12px,2.4vw,28px)] min-[761px]:flex">
                        <a href="#ako" class="text-[15px] font-semibold text-cb-ink no-underline hover:text-cb-red">Ako to funguje</a>
                        <a href="#podniky" class="text-[15px] font-semibold text-cb-ink no-underline hover:text-cb-red">Pre podniky</a>
                        <a href="#faq" class="text-[15px] font-semibold text-cb-ink no-underline hover:text-cb-red">Otázky</a>
                    </span>
                    <a
                        :href="appUrl"
                        class="flex h-[42px] items-center rounded-[21px] bg-cb-red px-[18px] text-[14.5px] font-bold text-white no-underline transition duration-200 hover:-translate-y-[3px] hover:text-white hover:shadow-[0_12px_24px_-10px_rgba(219,59,34,.6)]"
                    >
                        Stiahnuť appku
                    </a>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section id="top" class="hero-bg relative overflow-hidden">
            <div
                class="cb-container grid grid-cols-[repeat(auto-fit,minmax(min(100%,440px),1fr))] items-center gap-12 pb-[clamp(48px,7vw,96px)] pt-[clamp(40px,7vw,88px)]"
            >
                <div data-stagger="1" class="flex max-w-[600px] flex-col gap-[22px]">
                    <span class="cb-glass flex h-8 items-center gap-2 self-start rounded-2xl px-3.5 text-[13px] font-bold">
                        <span class="h-2 w-2 rounded-full bg-cb-green" />{{ hero.kicker }}
                    </span>
                    <h1 class="m-0 text-[clamp(44px,6.4vw,84px)] font-black leading-[.95] tracking-[-.045em] [text-wrap:pretty]">{{ hero.title }}</h1>
                    <p class="m-0 max-w-[520px] text-[clamp(17px,1.6vw,20px)] leading-normal text-cb-brown [text-wrap:pretty]">{{ hero.sub }}</p>
                    <div class="mt-1.5 flex flex-wrap gap-2.5">
                        <a :href="hero.cta1.href" @click="preventPlaceholder" class="cb-btn cb-btn-red min-w-[220px]"><span>{{ hero.cta1.label }}</span><span>→</span></a>
                        <a :href="hero.cta2.href" class="cb-btn cb-btn-ink min-w-[200px]"><span>{{ hero.cta2.label }}</span><span>→</span></a>
                    </div>
                    <a :href="hero.alt.href" class="text-[15px] font-bold text-cb-red no-underline hover:text-cb-red-dark">{{ hero.alt.label }}</a>
                </div>

                <div data-reveal="1" class="relative flex min-h-[560px] justify-center">
                    <div class="absolute bottom-5 left-[max(0px,calc(50%-300px))] h-[190px] w-[200px]">
                        <IllustrationHero />
                    </div>
                    <PhoneMockup />
                </div>
            </div>
        </section>

        <!-- Dve kategórie -->
        <section class="border-t-2 border-[rgba(120,80,50,.14)]">
            <div class="cb-container cb-section-pad flex flex-col gap-9">
                <div data-reveal="1" class="flex max-w-[760px] flex-col gap-3">
                    <span class="cb-eyebrow">Dve kategórie</span>
                    <h2 class="cb-h2">Večer záchrana, cez obed menu.</h2>
                    <p class="m-0 text-lg leading-normal text-cb-brown [text-wrap:pretty]">
                        V appke sú oddelené, aby si hneď videl, čo je časovo obmedzená ponuka a čo pravidelné študentské stravovanie.
                    </p>
                </div>
                <div data-stagger="1" class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,340px),1fr))] gap-4">
                    <div class="cb-lift flex flex-col gap-3.5 rounded-[32px] bg-cb-red p-[clamp(24px,3vw,36px)] text-white">
                        <span class="text-sm font-bold opacity-85">Hlavná služba</span>
                        <span class="text-[clamp(30px,3vw,40px)] font-black leading-none tracking-[-.03em]">Záchrana jedla</span>
                        <span class="text-[16.5px] leading-normal [text-wrap:pretty]">
                            Čo podniku na konci dňa zostane – bezpečné a čerstvé, len lacnejšie. Balíčky prekvapenia aj konkrétne jedlá.
                        </span>
                        <div class="mt-1.5 flex flex-wrap items-end justify-between gap-3 border-t-2 border-white/35 pt-4">
                            <span class="flex flex-col">
                                <span class="text-[13px] font-bold opacity-85">od</span>
                                <span class="text-[44px] font-black leading-none tracking-[-.04em]" data-count="9|1.9|2| €">1,90 €</span>
                            </span>
                            <span class="text-sm font-bold">Večer · kým sa neminie</span>
                        </div>
                    </div>
                    <div class="cb-lift flex flex-col gap-3.5 rounded-[32px] bg-cb-mint p-[clamp(24px,3vw,36px)] text-cb-ink">
                        <span class="text-sm font-bold text-cb-green">Každý pracovný deň</span>
                        <span class="text-[clamp(30px,3vw,40px)] font-black leading-none tracking-[-.03em]">Študentské menu</span>
                        <span class="text-[16.5px] leading-normal text-[#2F3A2F] [text-wrap:pretty]">
                            Bežné obedové menu so študentskou cenou. Polievka a hlavné jedlo v podnikoch pri fakultách a internátoch.
                        </span>
                        <div class="mt-1.5 flex flex-wrap items-end justify-between gap-3 border-t-2 border-[rgba(21,128,61,.25)] pt-4">
                            <span class="flex flex-col">
                                <span class="text-[13px] font-bold text-cb-green">od</span>
                                <span class="text-[44px] font-black leading-none tracking-[-.04em] text-cb-green" data-count="8.5|5.5|2| €">5,50 €</span>
                            </span>
                            <span class="text-sm font-bold">Obed · 11:00–15:00</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ako to funguje -->
        <section id="ako" class="scroll-mt-[72px] border-t-2 border-[rgba(120,80,50,.14)] bg-cb-sand">
            <div class="cb-container cb-section-pad flex flex-col gap-9">
                <div data-reveal="1" class="flex max-w-[760px] flex-col gap-3">
                    <span class="cb-eyebrow">Ako to funguje</span>
                    <h2 class="cb-h2">Tri kroky od prednášky k jedlu.</h2>
                </div>
                <div data-stagger="1" class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,300px),1fr))] gap-4">
                    <div
                        v-for="s in steps"
                        :key="s.n"
                        class="cb-glass cb-lift step-card flex flex-col overflow-hidden rounded-[32px]"
                    >
                        <div class="relative h-60" :style="{ background: s.bg }">
                            <component :is="s.art" />
                        </div>
                        <div class="flex flex-col gap-2 px-6 pb-[26px] pt-[22px]">
                            <span class="text-sm font-extrabold text-cb-red">{{ s.n }}</span>
                            <span class="text-2xl font-black leading-[1.1] tracking-[-.02em]">{{ s.title }}</span>
                            <span class="text-base leading-normal text-cb-brown [text-wrap:pretty]">{{ s.text }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

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
            <div data-stagger="1" class="cb-container cb-section-pad grid grid-cols-[repeat(auto-fit,minmax(min(100%,460px),1fr))] items-start gap-10">
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
                    <a :href="registerUrl" @click="preventPlaceholder" class="cb-btn cb-btn-red min-w-[280px] self-start"><span>Zaregistrovať podnik</span><span>→</span></a>
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
            <div data-stagger="1" class="cb-container cb-section-pad grid grid-cols-[repeat(auto-fit,minmax(min(100%,360px),1fr))] items-start gap-10">
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
                            <a :href="registerUrl" @click="preventPlaceholder" class="text-cb-cream no-underline hover:text-white">Registrácia podniku</a>
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
.hero-bg {
    background-color: #fff6ea;
    background-image:
        linear-gradient(to bottom, rgba(255, 246, 234, 0) 55%, #fff6ea 100%),
        radial-gradient(60% 70% at 85% 15%, rgba(255, 197, 154, 0.85) 0%, rgba(255, 197, 154, 0) 70%),
        radial-gradient(55% 60% at 8% 55%, rgba(255, 214, 170, 0.7) 0%, rgba(255, 214, 170, 0) 70%),
        radial-gradient(45% 55% at 100% 70%, rgba(207, 239, 217, 0.8) 0%, rgba(207, 239, 217, 0) 70%);
}
.step-card:hover {
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.9),
        0 30px 50px -24px rgba(60, 25, 5, 0.45);
}
</style>
