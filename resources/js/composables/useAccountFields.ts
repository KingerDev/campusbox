/**
 * Pomôcky pre polia účtu (telefón, e‑mail, heslo) – čisté funkcie, aby sa dali použiť v rôznych formulároch.
 */

/**
 * Telefón v medzinárodnom tvare so skupinami po troch: „0903123456“ aj „903 123 456“ → „+421 903 123 456“.
 * Bez predvoľby predpokladáme Slovensko. Medzery sa dopĺňajú len medzi skupinami, takže mazanie funguje prirodzene.
 */
export function formatPhone(raw: string): string {
    let s = raw.replace(/[^\d+]/g, '');
    if (!s) return '';
    const plus = s.startsWith('+');
    let d = s.replace(/\D/g, '');
    if (!plus) {
        if (d.startsWith('00')) d = d.slice(2);
        else if (d.startsWith('0')) d = '421' + d.slice(1);
        else if (!d.startsWith('421')) d = '421' + d;
    }
    // Slovensko: +421 a 9 číslic. Iné krajiny: najviac 15 číslic spolu.
    if (d.startsWith('421')) {
        const rest = d.slice(3, 12).replace(/^0+/, '');
        return ['+421', ...(rest.match(/.{1,3}/g) ?? [])].join(' ');
    }
    d = d.slice(0, 15);
    s = '+' + d.slice(0, 3);

    return [s, ...(d.slice(3).match(/.{1,3}/g) ?? [])].join(' ');
}

export function phoneProblem(value: string): string {
    const d = value.replace(/\D/g, '');
    if (!d) return '';
    if (d.startsWith('421')) return d.length === 12 ? '' : 'Slovenské číslo má po +421 deväť číslic, napr. +421 903 123 456.';

    return d.length >= 8 ? '' : 'Číslo je príliš krátke. Zadajte ho s predvoľbou krajiny.';
}

const EMAIL = /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i;
const DOMAIN_TYPOS: Record<string, string> = {
    'gmial.com': 'gmail.com',
    'gmai.com': 'gmail.com',
    'gmal.com': 'gmail.com',
    'gnail.com': 'gmail.com',
    'gmail.con': 'gmail.com',
    'gmail.co': 'gmail.com',
    'gmail.sk': 'gmail.com',
    'azet.com': 'azet.sk',
    'zoznam.com': 'zoznam.sk',
    'centrum.com': 'centrum.sk',
    'outlok.com': 'outlook.com',
    'outlook.con': 'outlook.com',
    'hotmial.com': 'hotmail.com',
    'yaho.com': 'yahoo.com',
    'icloud.con': 'icloud.com',
};

export const cleanEmail = (raw: string) => raw.trim().replace(/\s+/g, '').toLowerCase();

export function emailProblem(value: string): string {
    if (!value) return '';
    if (!value.includes('@')) return 'V e‑maile chýba zavináč, napr. meno@podnik.sk.';

    return EMAIL.test(value) ? '' : 'E‑mail nevyzerá úplne, napr. meno@podnik.sk.';
}

/** „peter@gmial.com“ → „peter@gmail.com“; inak null. */
export function emailSuggestion(value: string): string | null {
    const [name, domain] = value.split('@');
    const fixed = domain && DOMAIN_TYPOS[domain.toLowerCase()];

    return name && fixed ? `${name}@${fixed}` : null;
}

export interface PasswordStrength {
    score: 0 | 1 | 2 | 3 | 4;
    label: string;
    hint: string;
}

const COMMON = ['password', 'heslo', '12345678', '123456789', 'qwerty', 'qwertz', 'asdfgh', '11111111', 'campusbox', 'iloveyou', 'admin', 'bistro'];

/**
 * Orientačná sila hesla (0 = prázdne/krátke … 4 = silné). Dĺžka váži najviac, potom pestrosť znakov;
 * známe heslá, opakovanie a vlastné meno či e‑mail ju zrážajú.
 */
export function passwordStrength(password: string, personal: string[] = []): PasswordStrength {
    if (!password) return { score: 0, label: '', hint: 'Aspoň 8 znakov. Dlhšia veta je lepšia než krátke zložité heslo.' };
    const missing = 8 - password.length;
    if (password.length < 8)
        return {
            score: 0,
            label: 'Príliš krátke',
            hint: `Ešte ${missing} ${missing === 1 ? 'znak' : missing < 5 ? 'znaky' : 'znakov'} do minima.`,
        };

    const lower = password.toLowerCase();
    const classes = [/[a-z]/, /[A-Z]/, /\d/, /[^A-Za-z0-9]/].filter((r) => r.test(password)).length;
    let points = (password.length >= 16 ? 3 : password.length >= 12 ? 2 : 1) + (classes >= 3 ? 2 : classes === 2 ? 1 : 0);

    const words = personal.flatMap((p) => p.toLowerCase().split(/[^a-z0-9áäčďéíĺľňóôŕšťúýž]+/i)).filter((w) => w.length >= 4);
    const weak =
        COMMON.some((c) => lower.includes(c)) ||
        words.some((w) => lower.includes(w)) ||
        /^(.)\1+$/.test(password) ||
        /^(?:0123456789|1234567890|abcdefgh)/.test(lower) ||
        new Set(password).size <= 4;
    if (weak) points = Math.min(points, 1);

    if (points <= 1)
        return {
            score: 1,
            label: 'Slabé',
            hint: weak
                ? 'Obsahuje bežné slovo, vaše meno alebo opakovanie. Skúste niečo menej uhádnuteľné.'
                : 'Pridajte čísla, veľké písmená alebo ho predĺžte.',
        };
    if (points === 2) return { score: 2, label: 'Priemerné', hint: 'Ujde. Dlhšie heslo alebo ďalší typ znakov ho posilní.' };
    if (points === 3) return { score: 3, label: 'Dobré', hint: 'Dobré heslo.' };

    return { score: 4, label: 'Silné', hint: 'Silné heslo.' };
}
