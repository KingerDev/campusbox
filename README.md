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
- Migrácie sa automaticky nespúšťajú (`AUTORUN_ENABLED=false`), treba spustiť `php artisan migrate --force`.
- Pri SQLite treba databázu uložiť na trvalý volume.
