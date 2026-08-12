# Igniss — Digital Menu

A QR-code digital menu for **Igniss**, a restaurant & cocktail bar in Nice, France. Built with Laravel + Livewire + Alpine.js — no database, no frontend build step.

Scan the QR code on the table → animated intro → scrollable menu in French, English, Spanish, or Italian → tap dishes to build a running total to show your server.

## Features

- **Animated intro** — Ken Burns hero photo, rising embers, auto-advances after ~4s (tap to skip).
- **Photo-first menu** — dishes with a confirmed photo get a full card with tap-to-zoom; everything else is a clean text listing (never a photo of the wrong dish).
- **4 languages** — auto-detects the visitor's browser language on first load, remembers their choice after.
- **"Show to your server" selection list** — tap to build a running total with an included-VAT breakdown; entirely client-side (`localStorage`), never submitted anywhere.
- **Dietary filter** — Vegetarian / Vegan / Gluten-Free / Dairy-Free / Nut-Free, instant client-side filtering. Tags are illustrative demo data (see [`CLAUDE.md`](CLAUDE.md#content-accuracy--read-before-adding-real-menu-items)), not kitchen-verified.
- **Staff photo manager** (`/manager`, password-gated) — upload or replace a dish photo without touching code or redeploying.

## Requirements

- PHP 8.3+
- Composer

No Node.js, no npm, no database.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set `MENU_MANAGER_PASSWORD` in `.env` before using `/manager` anywhere but locally.

## Run

```bash
php artisan serve
```

Visit `http://localhost:8000` for the menu, `http://localhost:8000/manager` for the staff photo uploader.

## Editing the menu

All menu content lives in [`config/menu.php`](config/menu.php) (structure: slug, name, price, image, category, dietary tags) and [`lang/{fr,en,es,it}/menu.php`](lang/fr/menu.php) (per-language descriptions and UI text — dish *names* stay constant across languages, matching how real multilingual menus work). No database, no admin panel for text — edit the PHP arrays directly.

Photos can be added through `/manager` without editing code, or by dropping a file in `public/img/` and pointing an item's `image` field at it in `config/menu.php`.

See [`CLAUDE.md`](CLAUDE.md) for the full architecture writeup.

## License

Private project — not licensed for reuse.
