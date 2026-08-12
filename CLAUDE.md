# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A single-restaurant ("Bella Vista") digital menu on Laravel + Livewire. A customer scans a physical QR code on their table, which opens this site directly on their phone: a ~3.5s animated intro (brand + Ken Burns hero photo) that auto-advances into a scrollable menu grouped by category. There is no ordering/cart flow, search, or account system — browsing only (photo, name, description, price per dish), by design.

## Commands

- `php artisan serve` — runs the dev server (default `http://localhost:8000`).
- `composer install` — install PHP dependencies (no `npm install`/build step — see "No frontend build" below).
- No test suite has been added yet (`tests/` still holds Laravel's default scaffold, unused).

## Critical environment constraint: no database driver

This sandbox's PHP has **no `pdo_sqlite` and no running MySQL server** — only `pdo_mysql` is compiled in with nothing listening. `.env` (and `.env.example`) are deliberately set to `DB_CONNECTION=null`, `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` so the app never touches a database. **Do not switch these back to `database`** (Laravel's own scaffold default) without first confirming a real DB driver is available — that exact mistake previously took down every request with a 500 (`StartSession` middleware failing to open a DB-backed session table), so this is not a hypothetical.

There is no Eloquent model, migration, or seeder in this app at all — the menu is intentionally static content in `config/menu.php`, not database-backed. If a real admin-editable menu is ever needed, that's the point to introduce a DB.

## No frontend build

There is no Vite/Tailwind/npm step — `resources/css`, `resources/js`, `package.json`, and `vite.config.js` were deliberately removed from Laravel's default scaffold. Styling is a single hand-authored stylesheet at `public/css/app.css`, linked directly in `resources/views/menu.blade.php` via `asset()`. Fonts (self-hosted Fraunces `.woff2`) and food photos live under `public/fonts/` and `public/img/`. If you add JS/CSS tooling later, that's a deliberate reintroduction, not a restoration of something accidentally deleted.

## Architecture

- `routes/web.php` — single route, `GET /` → `view('menu')`.
- `resources/views/menu.blade.php` — the HTML shell (`<head>`, font preload, stylesheet, `@livewireStyles`/`@livewireScripts`) wrapping `<livewire:menu />`.
- `resources/views/components/⚡menu.blade.php` — the entire app as one Livewire **single-file component** (Livewire v4 convention: an anonymous class in a `<?php ... ?>` block at the top of the file, Blade markup below; the `⚡` filename prefix is what marks it as a component rather than a plain view). Holds one piece of state, `showIntro`, toggled by `skipIntro()` — that method is called either by `wire:click` on the intro screen (tap-to-skip) or by a `setTimeout(() => $wire.skipIntro(), 3500)` in an inline `<script>` (auto-advance). Everything else — category chips, item cards — is server-rendered Blade with no other Livewire round-trips; category navigation is plain `<a href="#cat-...">` anchor scrolling, not Livewire state, since a menu is naturally full-page content, not a filtered view.
- `config/menu.php` — the only place menu content lives: `restaurant` (name/tagline), `hero_image`, `categories` (id/label), `items` (name/desc/price/image/category). Editing the menu means editing this array directly.
- `public/img/`, `public/fonts/`, `public/css/app.css` — static assets served directly by Laravel's public disk, referenced via Laravel's `asset()` helper (not Vite's `@vite()`).

## Conventions to preserve

- Keep the menu database-free unless the user explicitly asks for an editable/admin backend — `config/menu.php` is the intended source of truth for this app's scope.
- The intro's "video" is actually a CSS Ken Burns pan/zoom on a static photo (`.hero-media img` + `@keyframes hero-kenburns` in `public/css/app.css`), not a literal `<video>` — no stock-video source was reachable from this sandbox when it was built. Swapping in a real video file later is a contained change (replace the `<img>` in the intro block with a `<video autoplay muted loop>`).
- Respect `prefers-reduced-motion` (already handled for the Ken Burns animation) if you add more motion.
