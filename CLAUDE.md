# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A single-restaurant ("Bella Vista") QR-code menu demo: a static, mobile-app-styled web page rendered inside a phone-frame mockup. The scan screen renders a QR code that encodes the page's own URL, so scanning it on another device (same Wi-Fi/network) opens the same page — there's no separate mobile build.

## Commands

- `npm start` — runs `node server.js`, a zero-dependency static file server (no build step, no dev server framework).
- No test suite, linter, or bundler config exists in this repo.

Server listens on `PORT` env var (default 3000) and prints both the localhost URL and the LAN URL (via network interface lookup) — the LAN URL is what the QR code should encode when testing on a phone.

## Architecture

Everything runs client-side; `server.js` only serves static files (with directory-traversal protection via `path.normalize` + prefix check) and has no API routes or persistence — there is no backend/database, and "placing an order" is purely a client-side state reset with a fabricated order number.

- `index.html` — single-page app shell. All app "screens" (scan/splash, menu, cart, order confirmation) are `<section class="screen">` siblings inside `.phone`, toggled via a `.active` class rather than routed — see `showScreen()` in app.js. The item-detail view is a modal-style sheet (`#item-overlay`), not a screen.
- `js/data.js` — static content only: `RESTAURANT` (name/table number), `CATEGORIES`, and the `MENU_ITEMS` array (id, price, category tags, dietary tags, emoji-as-icon). Adding/editing menu items means editing this array directly — there's no CMS or data file per category.
- `js/app.js` — IIFE, no modules/imports. Holds all UI state in a single `state` object (current category, search query, `cart` as `{id: qty}`, the item-sheet's in-progress qty) and re-renders affected DOM regions on every state change (`renderMenu`, `renderCart`, `renderCartBadge`, etc.) rather than using a framework or virtual DOM. `$`/`$$` are local `querySelector(All)` shorthands, not jQuery.
- `js/vendor/qrcode.bundle.js` — a pre-built bundle of the `qrcode` npm package exposing `window.QRCode`, generated from `build/qr-entry.js` (`import QRCode from 'qrcode'; window.QRCode = QRCode;`). There is no bundler wired into this repo (no esbuild/webpack/rollup dependency) — if `build/qr-entry.js` changes, the bundle in `js/vendor/` must be regenerated manually with an external bundler and the output copied in.
- `css/style.css` — all styling, including the phone-frame chrome (notch, status bar, tab bar) that simulates a native app.

## Conventions to preserve

- Keep `js/app.js` dependency-free and IIFE-scoped (matches `data.js` being loaded as a plain global-scope script before it, with `RESTAURANT`/`CATEGORIES`/`MENU_ITEMS` as implicit globals).
- Screen transitions go through `showScreen(id)`, which also handles tab-bar visibility/active-state — don't toggle `.screen`/`.active` classes directly from event handlers.
- Cart quantities are the source of truth in `state.cart`; totals (`cartSubtotal`, badge count) are derived on read, not cached — keep new cart-affecting code consistent with that (call `renderCartBadge()`/`renderCart()` after mutating `state.cart`).
