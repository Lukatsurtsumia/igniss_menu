# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A digital menu for Igniss, a restaurant/cocktail bar in Nice, on Laravel + Livewire. A customer scans a physical QR code on their table, which opens this site directly on their phone: a ~4.2s animated intro (brand + Ken Burns hero photo, ember particles) that auto-advances into a scrollable menu grouped by category, in French/English/Spanish/Italian.

There is no real ordering/checkout/payment flow. There *is* a client-side "show to your server" selection list — customers tap dishes to build a running total and hand their phone to staff — but nothing in it is ever submitted anywhere; it's `localStorage` only. Menu item names, descriptions, and photo pairings are illustrative unless verified against Igniss's actual current menu (see "Content accuracy" below).

## Commands

- `php artisan serve` — runs the dev server (default `http://localhost:8000`).
- `composer install` — install PHP dependencies (no `npm install`/build step — see "No frontend build" below).
- No automated test suite yet (`tests/TestCase.php` is the only file left — ready for real tests, none written).

## Critical environment constraint: no database

`.env` is set to `DB_CONNECTION=null`, `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` so the app never touches a database. **Do not switch these back to `database`** without first confirming a real DB driver is available and configured — a DB-backed session table with no working DB connection takes down every request with a 500 in `StartSession` middleware.

There is no Eloquent model, migration, or seeder in this app — menu content lives in `config/menu.php`, translations in `lang/{locale}/menu.php`, and the one piece of mutable state (manager-uploaded photo overrides) is a flat JSON file at `storage/app/menu-image-overrides.json`. If real multi-admin editing, order history, or anything relational is ever needed, that's the point to introduce a DB — not before.

## No frontend build

There is no Vite/Tailwind/npm step — `resources/css`, `resources/js`, `package.json`, and `vite.config.js` don't exist. Styling is a single hand-authored stylesheet at `public/css/app.css`, linked directly via `asset()`. Fonts (self-hosted Fraunces `.woff2`) and food photos live under `public/fonts/` and `public/img/`. If you add JS/CSS tooling later, that's a deliberate reintroduction.

## Architecture

- `routes/web.php` — two routes: `GET /` → `view('menu')` (the customer-facing menu), `GET /manager` → `view('manager')` (staff photo upload, password-gated).
- `resources/views/components/⚡menu.blade.php` — the customer menu as one Livewire **single-file component** (Livewire v4: anonymous class in a `<?php ... ?>` block, Blade markup below; the `⚡` prefix marks it as a component). Holds `showIntro`, `locale`. Category navigation, the selection list, the dietary filter, and the photo lightbox are all Alpine.js state (`x-data`/`x-init` on the root `.menu-page` div) — no Livewire round-trip per tap; only locale switching (`setLocale()`, a real `wire:click`) re-renders server-side, since translations resolve in PHP.
- `resources/views/components/⚡manager.blade.php` — staff-only photo upload page. Session-based password gate (`config('menu.manager_password')`, from `MENU_MANAGER_PASSWORD` in `.env`) — not a real user/role system, proportionate to what's actually at stake. Uploads are copied (not moved — see the comment in `uploadPhoto()`, Livewire's temp files fail Symfony's `is_uploaded_file()` check) into `public/img/manager/{slug}.{ext}` and recorded in `storage/app/menu-image-overrides.json` via `App\Support\MenuImages`.
- `config/menu.php` — the structural source of truth: `restaurant`, `hero_image`, `locales`, `tax_rate`, `manager_password`, `diet_filters`, `categories` (id/roman numeral), `items` (slug/name/price/image/category/diet). Dish and drink **names** are not translated (matches how real multilingual menus work) — only descriptions are, per-locale, in `lang/{locale}/menu.php` keyed by slug. `image` is only set when a real Igniss photo of that exact dish exists; everything else renders as a text-only row rather than being paired with a stand-in photo of a different dish.
- `lang/{fr,en,es,it}/menu.php` — per-locale `categories`, `items` (descriptions, keyed by slug), and `ui` strings.
- `app/Support/MenuImages.php` — reads/writes the manager-upload override JSON; `resolve()` merges it over `config('menu.items')`'s default image (a value of `null` means the manager explicitly removed a photo, distinct from no override at all).

## Content accuracy — read before adding "real" menu items

The two Igniss PDF menus originally provided both turned out to be the same 52€ shared/set menu (8 dishes, no individual pricing, no cocktail list). The much larger à la carte + cocktail list currently in `config/menu.php` is **not sourced from a verified Igniss document** — it's illustrative. Likewise, `diet` tags per item were inferred by reading each dish's own listed ingredients, not confirmed by the restaurant's kitchen; the UI carries a visible "demo tags" disclaimer in the dietary filter panel and this must not be removed if real, kitchen-verified data isn't in place. Don't present either as verified without checking with Igniss first.

## Conventions to preserve

- Keep content changes in `config/menu.php` / `lang/*/menu.php`, not hardcoded in Blade, so the manager page and translations stay in sync automatically.
- The intro's "video" is a CSS Ken Burns pan/zoom (`.hero-media img` + `@keyframes hero-kenburns`) plus rising ember particles (`.ember-field`), not a literal `<video>`. Respect `prefers-reduced-motion` — already handled throughout; extend the existing reduced-motion block in `public/css/app.css` if you add more motion.
- Alpine methods assigned via `this.someMethod = (...) => {...}` inside `x-init` are how a method becomes callable from *other* Alpine expressions on the page — it does not mean `this` is the reactive data object inside the method body. Reference reactive state with bare identifiers (`selection`, `activeFilters`, `menuItems`, etc.), exactly like the existing methods do — not `this.selection`. Getting this backwards silently breaks the method (see git history for the exact failure mode).
- Never put a literal `"` character inside an `x-init`/`x-data`/any Alpine attribute's double-quoted HTML value — even inside a comment. It prematurely closes the HTML attribute and the remainder of the script leaks onto the page as visible text. Use `'...'` or drop the quote marks.
