<script setup lang="ts">
import AccountLayout from '@/layouts/AccountLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    enabled: boolean;
    required: boolean;
    setup: { svg: string; secret: string } | null;
    recoveryCodes: string[] | null;
    remaining: number | null;
    home: string;
    admin: boolean;
}>();

const start = useForm({});
const confirm = useForm({ code: '' });
const secure = useForm({ password: '' });
const action = ref<'codes' | 'disable' | null>(null);
const copied = ref(false);

const begin = () => start.post(route('two-factor.store'));
const submitCode = () => confirm.post(route('two-factor.confirm'), { preserveScroll: true, onError: () => confirm.reset() });
const open = (a: 'codes' | 'disable') => {
    action.value = action.value === a ? null : a;
    secure.reset();
    secure.clearErrors();
};
const submitSecure = () => {
    const done = { preserveScroll: true, onSuccess: () => ((action.value = null), secure.reset()) };
    if (action.value === 'codes') secure.post(route('two-factor.recovery-codes'), done);
    else secure.delete(route('two-factor.destroy'), done);
};

const secretGroups = computed(() => props.setup?.secret.match(/.{1,4}/g)?.join(' ') ?? '');
const copyCodes = async () => {
    try {
        await navigator.clipboard.writeText((props.recoveryCodes ?? []).join('\n'));
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        /* schránka nemusí byť dostupná – kódy sú na obrazovke */
    }
};

const sub = computed(() =>
    props.enabled
        ? 'Pri každom prihlásení od vás okrem hesla vypýtame aj kód z autentifikačnej appky.'
        : props.required
          ? props.admin
              ? 'Ako správca schvaľujete podniky a vidíte ich údaje, preto je overenie povinné. Nastavenie trvá asi minútu.'
              : 'V paneli pracujete s tržbami a výplatami, preto je pre majiteľa a manažéra povinné. Nastavenie trvá asi minútu.'
          : 'Pri prihlásení od vás okrem hesla vypýtame aj kód z appky v telefóne. Účet tak ochráni, aj keby niekto zistil heslo.',
);
</script>

<template>
    <AccountLayout title="Dvojfaktorové overenie" :sub="sub" :locked="required && !enabled">
        <!-- Záložné kódy: len raz, hneď po vygenerovaní -->
        <div v-if="recoveryCodes" class="flex flex-col gap-3 rounded-3xl bg-cb-panel px-5 py-[18px]">
            <span class="text-[17px] font-extrabold">Uložte si záložné kódy</span>
            <span class="text-sm leading-[1.45] text-cb-taupe">
                Ak stratíte telefón, prihlásite sa jedným z nich. Každý platí raz a znova ich už neukážeme – dajte si ich do správcu hesiel alebo
                vytlačte.
            </span>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 rounded-2xl bg-white px-4 py-3 font-mono text-[15px] font-bold">
                <span v-for="c in recoveryCodes" :key="c">{{ c }}</span>
            </div>
            <button type="button" class="self-start text-sm font-bold text-cb-red" @click="copyCodes">
                {{ copied ? 'Skopírované ✓' : 'Skopírovať kódy' }}
            </button>
            <Link :href="home" class="pbtn pbtn-red mt-1"><span>Mám ich uložené, pokračovať</span><span>→</span></Link>
        </div>

        <!-- Zapnuté -->
        <template v-if="enabled">
            <div class="flex items-center gap-2.5 rounded-2xl bg-[rgba(21,128,61,.12)] px-4 py-3 text-sm font-bold text-cb-green">
                <span class="font-black">✓</span>
                <span>Dvojfaktorové overenie je zapnuté. Zostáva {{ remaining }} z 8 záložných kódov.</span>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <button type="button" class="pbtn pbtn-ghost h-12 text-[15px]" @click="open('codes')">Nové záložné kódy</button>
                <button type="button" class="pbtn pbtn-ghost h-12 text-[15px]" @click="open('disable')">Vypnúť</button>
            </div>
            <form v-if="action" class="flex flex-col gap-3 rounded-3xl bg-cb-panel px-5 py-[18px]" @submit.prevent="submitSecure">
                <span class="text-sm leading-[1.45] text-cb-taupe">
                    {{
                        action === 'codes'
                            ? 'Staré záložné kódy prestanú platiť. Pre potvrdenie zadajte heslo.'
                            : required
                              ? 'Bez dvojfaktorového overenia vás panel nepustí k objednávkam ani výplatám, kým ho znova nezapnete. Pre potvrdenie zadajte heslo.'
                              : 'Účet bude chránený len heslom. Pre potvrdenie zadajte heslo.'
                    }}
                </span>
                <label class="flex flex-col gap-[7px]">
                    <span class="plabel">Heslo</span>
                    <input
                        v-model="secure.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="pinput"
                        :class="secure.errors.password && '!border-cb-red'"
                    />
                </label>
                <span v-if="secure.errors.password" class="text-sm font-bold text-cb-red" role="alert">{{ secure.errors.password }}</span>
                <button type="submit" class="pbtn pbtn-red" :disabled="secure.processing">
                    <span>{{ action === 'codes' ? 'Vygenerovať nové kódy' : 'Vypnúť overenie' }}</span
                    ><span>→</span>
                </button>
            </form>
        </template>

        <!-- Nastavovanie: QR kód a potvrdenie -->
        <template v-else-if="setup">
            <div class="flex flex-col gap-4 rounded-3xl bg-cb-panel px-5 py-[18px]">
                <div class="flex flex-col gap-1">
                    <span class="text-[13px] font-extrabold text-cb-red">Krok 1</span>
                    <span class="text-sm leading-[1.45]">
                        V autentifikačnej appke (Google Authenticator, Microsoft Authenticator, 1Password…) naskenujte QR kód.
                    </span>
                </div>
                <!-- SVG generuje server (TwoFactor::qrSvg), nie je to vstup od používateľa. -->
                <div
                    class="h-[200px] w-[200px] self-center overflow-hidden rounded-2xl bg-white"
                    aria-label="QR kód pre autentifikačnú appku"
                    v-html="setup.svg"
                />
                <div class="flex flex-col gap-1">
                    <span class="text-[13px] text-cb-taupe">Nejde skenovať? Zadajte do appky tento kľúč:</span>
                    <span class="select-all break-all font-mono text-[15px] font-bold">{{ secretGroups }}</span>
                </div>
            </div>
            <form class="flex flex-col gap-3.5" @submit.prevent="submitCode">
                <label class="flex flex-col gap-[7px]">
                    <span class="flex flex-col gap-0.5">
                        <span class="text-[13px] font-extrabold text-cb-red">Krok 2</span>
                        <span class="plabel">Zadajte 6‑miestny kód, ktorý appka ukáže</span>
                    </span>
                    <input
                        v-model="confirm.code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="7"
                        required
                        placeholder="123 456"
                        class="pinput bg-white/75 text-[22px] font-extrabold tracking-[.18em]"
                        :class="confirm.errors.code && '!border-cb-red'"
                    />
                </label>
                <span v-if="confirm.errors.code" class="text-sm font-bold text-cb-red" role="alert">{{ confirm.errors.code }}</span>
                <button type="submit" class="pbtn pbtn-red" :disabled="confirm.processing"><span>Potvrdiť a zapnúť</span><span>→</span></button>
            </form>
        </template>

        <!-- Vypnuté -->
        <template v-else>
            <div
                v-if="required"
                class="flex items-start gap-2.5 rounded-2xl bg-[rgba(255,122,26,.14)] px-4 py-3 text-sm font-semibold text-cb-or-dark"
            >
                <span class="font-black">!</span>
                <span>{{ admin ? 'Do administrácie' : 'Do panela' }} vás pustíme hneď, ako overenie zapnete.</span>
            </div>
            <button type="button" class="pbtn pbtn-red" :disabled="start.processing" @click="begin">
                <span>Zapnúť dvojfaktorové overenie</span><span>→</span>
            </button>
        </template>
    </AccountLayout>
</template>
