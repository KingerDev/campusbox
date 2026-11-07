# CampusBox

Cenovo dostupné jedlo pre vysokoškolákov a menej potravinového odpadu v gastre.

CampusBox spája študentov s bistrami, pekárňami a kaviarňami v okolí kampusu v dvoch kategóriách:

- **Záchrana jedla** – nepredané jedlo na konci dňa až o 70 % lacnejšie.
- **Študentské menu** – zvýhodnené obedové menu v pracovné dni.

Pilot beží v Banskej Bystrici pri UMB.

## Stack

- Laravel 12 + Inertia.js 2 + Vue 3 (TypeScript) + Tailwind CSS 3
- Laravel Vue starter kit (autentifikácia, nastavenia profilu)
- SQLite (lokálne)
- PHP 8.4, Node 22

## Lokálne spustenie

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate

composer dev   # server + queue + logy + Vite naraz
```

Aplikácia beží na http://127.0.0.1:8000.

## Landing page

| URL               | Obsah                          |
| ----------------- | ------------------------------ |
| `/`               | landing pre študentov          |
| `/?pre=podniky`   | landing pre podniky            |

Súbory:

- `resources/js/pages/Landing.vue` – stránka
- `resources/js/components/landing/` – logo, mockup telefónu a SVG ilustrácie
- `resources/js/composables/useLandingMotion.ts` – zobrazovanie pri scrolle a animované čísla
- `resources/css/app.css` – spoločné triedy `cb-*` a animácie
- `tailwind.config.js` – farby značky (`cb-red`, `cb-ink`, `cb-cream`…) a font Archivo

Dizajn pochádza z projektu v Claude Design (`Landing page.dc.html`).

## Panel pre podniky (`/panel`)

Podľa návrhu „Panel pre podnik v2“ z Claude Design. Funguje na desktope aj mobile (spodná lišta s kartou „Viac“).

| Obrazovka | URL | Kto |
| --- | --- | --- |
| Registrácia podniku (5 krokov) | `/panel/registracia` | ktokoľvek |
| Prehľad dňa | `/panel` | všetci |
| Výdaj (QR kamera alebo kód KV‑xxxx) | `/panel/vydaj` | všetci |
| Objednávky (potvrdenie s časom, pripravené, kuriér) | `/panel/objednavky` | všetci |
| Dnešná ponuka (záchrana jedla / menu, doručenie) | `/panel/ponuka` | majiteľ, manažér |
| Menu podniku (sekcie, jedlá, alergény, voľby, fotky) | `/panel/menu` | majiteľ, manažér |
| Profil podniku | `/panel/profil` | majiteľ, manažér (IBAN len majiteľ) |
| Výplaty a štatistiky, výpis na tlač | `/panel/vyplaty` | majiteľ |
| Tím a pozvánky | `/panel/tim` | majiteľ |

Pracovník nevidí žiadne sumy – server mu ich ani neposiela. Oprávnenia sú v `app/Enums/BusinessRole.php`.

**Demo dáta** (`php artisan migrate:fresh --seed`): Bistro Pod Urpínom, heslo `password`
– `peter@podurpinom.sk` (majiteľ), `jana@podurpinom.sk` (manažér), `samo@podurpinom.sk` (pracovník).

**Príkazy**

```bash
php artisan campusbox:fake-order [id] [--delivery]  # testovacia objednávka, kým nie je študentská appka
php artisan campusbox:approve {id}                    # schváli podnik po overení
php artisan campusbox:expire-orders                   # zruší nevydané objednávky (beží v plánovači každých 5 min)
```

Nové objednávky panel zisťuje pollingom každých 15 s (`/panel/objednavky/nove`).

## Testy

```bash
php artisan test
```

## Docker

Multi-stage `Dockerfile` (composer → build assetov → `serversideup/php:8.4-fpm-nginx`):

```bash
docker build -t campusbox .
docker run -p 8080:8080 \
  -e APP_KEY=base64:... \
  -e APP_ENV=production \
  -e DB_CONNECTION=... \
  campusbox
```

- `.env` sa do obrazu nekopíruje, konfigurácia sa odovzdáva cez premenné prostredia.
- Panel potrebuje databázu (MySQL): nastav `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Fotky jedál a podnikov sa ukladajú do `storage/app/public` – priečinok `storage` musí byť na trvalom volume.
- Automatické rušenie nevydaných objednávok potrebuje plánovač: `php artisan schedule:work` v samostatnom kontajneri alebo cron so `schedule:run`.
- Pozvánky do tímu sa posielajú e‑mailom – nastav `MAIL_*` (napr. Brevo SMTP), inak sa len zapíšu do logu.
- Migrácie sa automaticky nespúšťajú (`AUTORUN_ENABLED=false`), treba spustiť `php artisan migrate --force`.
