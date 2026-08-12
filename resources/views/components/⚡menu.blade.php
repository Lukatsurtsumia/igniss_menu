<?php

use App\Support\MenuImages;
use Livewire\Component;

new class extends Component
{
    public bool $showIntro = true;
    public string $locale = 'fr';

    public function mount(): void
    {
        $supported = config('menu.locales');
        $queryLang = request()->query('lang');
        $sessionLang = session('locale');

        $this->locale = match (true) {
            in_array($queryLang, $supported, true) => $queryLang,
            in_array($sessionLang, $supported, true) => $sessionLang,
            default => request()->getPreferredLanguage($supported) ?? config('menu.default_locale'),
        };

        session(['locale' => $this->locale]);
    }

    public function setLocale(string $locale): void
    {
        if (! in_array($locale, config('menu.locales'), true)) {
            return;
        }

        $this->locale = $locale;
        session(['locale' => $locale]);
    }

    public function skipIntro(): void
    {
        $this->showIntro = false;
    }

    public function with(): array
    {
        // Manager-uploaded photos (via /manager) override config('menu.items')'s
        // 'image' — including replacing a curated photo, or explicitly removing
        // one (MenuImages::resolve returns null in that case, not the fallback).
        // array_merge, not `+` — `+` keeps the LEFT side's value for a key that
        // already exists, which for 'image' (already set in config) would have
        // silently discarded every override.
        $items = collect(config('menu.items'))
            ->map(fn ($item) => array_merge($item, ['image' => MenuImages::resolve($item['slug'], $item['image'])]));
        $t = trans('menu', [], $this->locale);

        // fr/es/it use a comma decimal separator with the symbol trailing the
        // amount; en uses a period with the symbol leading — matches how each
        // is actually written on a menu, not just a literal string swap.
        $formatPrice = function (float $price) {
            $decimalSeparator = in_array($this->locale, ['fr', 'es', 'it'], true) ? ',' : '.';
            $formatted = number_format($price, 2, $decimalSeparator, '');

            return $this->locale === 'en' ? '€'.$formatted : $formatted.' €';
        };

        $translateItem = fn ($item) => $item + [
            'desc' => $t['items'][$item['slug']] ?? '',
            'priceFormatted' => $formatPrice($item['price']),
        ];

        return [
            'restaurant' => config('menu.restaurant'),
            'heroImage' => config('menu.hero_image'),
            'locale' => $this->locale,
            'locales' => config('menu.locales'),
            'taxRate' => config('menu.tax_rate'),
            'dietFilters' => config('menu.diet_filters'),
            't' => $t,
            'categories' => collect(config('menu.categories'))
                ->map(fn ($category) => $category + ['label' => $t['categories'][$category['id']] ?? $category['id']])
                ->all(),
            'itemsByCategory' => collect(config('menu.categories'))
                ->mapWithKeys(function ($category) use ($items, $translateItem) {
                    $categoryItems = $items->where('category', $category['id'])
                        ->map($translateItem)
                        ->values();

                    return [
                        $category['id'] => [
                            'featured' => $categoryItems->whereNotNull('image')->values(),
                            'list' => $categoryItems->whereNull('image')->values(),
                        ],
                    ];
                }),
            // Raw name + numeric price + category + diet tags per item, for the
            // client-side selection summary and dietary filter — both run
            // entirely in the browser, no Livewire round-trip per tap.
            'jsItems' => $items->mapWithKeys(fn ($item) => [
                $item['slug'] => [
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'category' => $item['category'],
                    'diet' => $item['diet'] ?? [],
                ],
            ]),
        ];
    }
};
?>

<div>
    @if ($showIntro)
        <section class="intro-screen" wire:click="skipIntro">
            <div class="hero-media" aria-hidden="true">
                <img src="{{ asset($heroImage) }}" alt="" />
                <div class="hero-scrim"></div>
            </div>
            <div class="intro-flash" aria-hidden="true"></div>
            <div class="intro-content">
                <div class="brand">
                    <img class="brand-logo" src="{{ asset('img/logo.png') }}" alt="{{ $restaurant['name'] }}" />
                    <h1 class="flame-text">{{ $restaurant['name'] }}</h1>
                    <p class="muted">{{ $restaurant['tagline'] }}</p>
                </div>
            </div>
            <div class="ember-field" aria-hidden="true">
                @for ($i = 0; $i < 22; $i++)
                    <span class="ember" style="--x: {{ (int) (($i * 47 + 13) % 96) + 2 }}%; --delay: {{ number_format((($i * 37) % 45) / 10, 1) }}s; --dur: {{ number_format((($i * 23) % 35 + 55) / 10, 1) }}s; --size: {{ ($i % 4) + 3 }}px;"></span>
                @endfor
            </div>
        </section>

        <script>
            setTimeout(() => { @this.skipIntro() }, 4200)
        </script>
    @else
        <div
            class="menu-page"
            wire:key="menu-page-{{ $locale }}"
            x-data="{
                lightbox: null,
                activeCategory: '{{ $categories[0]['id'] }}',
                summaryOpen: false,
                filtersOpen: false,
                activeFilters: [],
                locale: '{{ $locale }}',
                taxRate: {{ $taxRate }},
                menuItems: @js($jsItems),
                selection: JSON.parse(localStorage.getItem('igniss-selection') || '{}'),
            }"
            x-effect="document.body.style.overflow = (lightbox || summaryOpen) ? 'hidden' : ''"
            @keydown.escape.window="lightbox = null; summaryOpen = false"
            x-init="
                const persistSelection = () => localStorage.setItem('igniss-selection', JSON.stringify(selection));
                // Bare identifiers (selection, menuItems, locale, activeCategory elsewhere) —
                // not this.selection — same as the rest of this component: Alpine resolves
                // them against the reactive data via the closure these arrow functions were
                // defined in, exactly like activeCategory works below. this.addItem = ... is
                // only how the METHOD ITSELF gets exposed for other expressions to call —
                // it does not mean `this` is the reactive data inside the method body.

                this.addItem = (slug) => {
                    selection[slug] = (selection[slug] || 0) + 1;
                    persistSelection();
                };
                this.removeItem = (slug) => {
                    if (!selection[slug]) return;
                    selection[slug] -= 1;
                    if (selection[slug] <= 0) delete selection[slug];
                    persistSelection();
                };
                this.clearSelection = () => {
                    selection = {};
                    persistSelection();
                };
                this.selectionCount = () => Object.values(selection).reduce((sum, qty) => sum + qty, 0);
                this.selectionTotal = () => Object.entries(selection).reduce(
                    (sum, [slug, qty]) => sum + (menuItems[slug]?.price || 0) * qty, 0
                );
                // Prices are already tax-inclusive — this is the VAT PORTION of the total
                // shown above, for transparency, never an extra amount added on top of it.
                this.taxAmount = () => this.selectionTotal() * (taxRate / (100 + taxRate));
                this.formatPrice = (price) => {
                    const dec = locale === 'en' ? '.' : ',';
                    const formatted = price.toFixed(2).replace('.', dec);
                    return locale === 'en' ? ('€' + formatted) : (formatted + ' €');
                };

                // Dietary filter — demo tags only (see config/menu.php), AND logic:
                // an item must match every active filter, not just one of them.
                this.toggleFilter = (tag) => {
                    activeFilters = activeFilters.includes(tag)
                        ? activeFilters.filter((t) => t !== tag)
                        : [...activeFilters, tag];
                };
                this.itemVisible = (slug) => {
                    if (activeFilters.length === 0) return true;
                    const diet = menuItems[slug]?.diet || [];
                    return activeFilters.every((f) => diet.includes(f));
                };
                this.categoryVisibleCount = (categoryId) => Object.entries(menuItems)
                    .filter(([slug, item]) => item.category === categoryId && this.itemVisible(slug))
                    .length;

                $nextTick(() => {
                    const sections = Array.from($el.querySelectorAll('.menu-section'));
                    let ticking = false;
                    let scrollingToId = null;

                    // Header height changes (webfont swap, 4 vs 5 tabs, viewport width) —
                    // publish it as a CSS var so scroll-padding-top and the threshold below
                    // always agree with the real, current header, never a guessed constant.
                    const syncHeaderHeight = () => {
                        if (!$refs.header) return; // element already torn down (e.g. mid locale switch)
                        document.documentElement.style.setProperty('--header-h', $refs.header.offsetHeight + 'px');
                    };

                    // Pure geometry, no scrolling side effects — safe to call on every scroll frame.
                    const positionIndicator = () => {
                        const chip = $refs['tab-' + activeCategory];
                        if (!chip || !$refs.indicator) return;
                        $refs.indicator.style.width = chip.offsetWidth + 'px';
                        $refs.indicator.style.transform = `translateX(${chip.offsetLeft}px)`;
                    };

                    // Keeps the active tab visible in the horizontally-scrolling nav row.
                    // Scrolls the nav element directly (its own scrollLeft) rather than
                    // chip.scrollIntoView() — scrollIntoView resolves BOTH axes, and a
                    // descendant of a position:sticky header confuses browsers into also
                    // yanking the page's vertical scroll to correct a position that's already
                    // fine. Only call this when the active tab actually changes, not on
                    // every scroll frame — otherwise it fights the user's own scrolling.
                    const centerActiveTab = () => {
                        const chip = $refs['tab-' + activeCategory];
                        const nav = $refs.nav;
                        if (!chip || !nav) return;
                        const target = chip.offsetLeft - (nav.clientWidth - chip.offsetWidth) / 2;
                        nav.scrollTo({ left: Math.max(0, target), behavior: 'smooth' });
                    };

                    const updateActive = () => {
                        // While a tap-triggered smooth scroll is still animating, trust the
                        // tap's target rather than mid-flight geometry — avoids the indicator
                        // flickering across intermediate sections it's scrolling past.
                        if (scrollingToId || !$refs.header) return;
                        const threshold = $refs.header.offsetHeight + 24;
                        let current = sections[0];
                        for (const section of sections) {
                            if (section.getBoundingClientRect().top - threshold <= 0) current = section;
                        }
                        activeCategory = current.id.replace('cat-', '');
                        ticking = false;
                    };

                    let scrollSettleTimer = null;
                    this.goToCategory = (id) => {
                        activeCategory = id; // instant feedback — don't wait for scroll to catch up
                        scrollingToId = id;
                        document.getElementById('cat-' + id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        clearTimeout(scrollSettleTimer);
                        scrollSettleTimer = setTimeout(() => { scrollingToId = null; }, 700);
                    };

                    syncHeaderHeight();
                    updateActive();
                    positionIndicator();

                    // Fraunces (header + section headings) swaps in after first paint and
                    // reflows everything below it — resync once layout has actually settled.
                    document.fonts?.ready?.then(() => { syncHeaderHeight(); updateActive(); positionIndicator(); });

                    const onLoad = () => { syncHeaderHeight(); updateActive(); positionIndicator(); };
                    const onResize = () => { syncHeaderHeight(); updateActive(); positionIndicator(); };
                    const onScroll = () => {
                        if (!ticking) {
                            requestAnimationFrame(() => { updateActive(); positionIndicator(); });
                            ticking = true;
                        }
                    };
                    window.addEventListener('load', onLoad);
                    window.addEventListener('resize', onResize);
                    window.addEventListener('scroll', onScroll, { passive: true });

                    // Switching language remounts this component (wire:key includes the
                    // locale, deliberately — client-side price formatting needs the fresh
                    // locale too), which leaves the OLD instance's listeners above still
                    // attached to window. syncHeaderHeight/updateActive above already guard
                    // on `$refs.header` existing, so a stale listener from a torn-down
                    // instance just no-ops instead of throwing against removed refs.

                    $watch('activeCategory', () => { positionIndicator(); centerActiveTab(); });
                })
            "
        >
            <header class="menu-header" x-ref="header">
                <div class="menu-header-top">
                    <img class="menu-logo" src="{{ asset('img/logo.png') }}" alt="{{ $restaurant['name'] }}" />
                    <div class="menu-titles">
                        <h2>{{ $restaurant['name'] }}</h2>
                        <p class="eyebrow">{{ $restaurant['tagline'] }}</p>
                    </div>
                    <div class="lang-switcher" role="group" aria-label="{{ $t['ui']['language'] }}">
                        @foreach ($locales as $loc)
                            <button
                                type="button"
                                wire:click="setLocale('{{ $loc }}')"
                                @class(['lang-btn' => true, 'is-active' => $loc === $locale])
                            >{{ strtoupper($loc) }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="categories-row">
                    <nav class="categories" x-ref="nav">
                        @foreach ($categories as $category)
                            <a
                                href="#cat-{{ $category['id'] }}"
                                class="cat-chip"
                                :class="{ 'is-active': activeCategory === '{{ $category['id'] }}' }"
                                :aria-current="activeCategory === '{{ $category['id'] }}' ? 'true' : null"
                                x-ref="tab-{{ $category['id'] }}"
                                @click.prevent="goToCategory('{{ $category['id'] }}')"
                            >{{ $category['label'] }}</a>
                        @endforeach
                        <span class="nav-indicator" x-ref="indicator" aria-hidden="true"></span>
                    </nav>
                    <button
                        type="button"
                        class="filter-toggle"
                        :class="{ 'has-active': activeFilters.length > 0 }"
                        @click="filtersOpen = true"
                        aria-label="{{ $t['ui']['filters'] }}"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="4 4 20 4 14 12.5 14 19 10 21 10 12.5 4 4" />
                        </svg>
                        <span class="filter-toggle-badge" x-show="activeFilters.length > 0" x-cloak x-text="activeFilters.length"></span>
                    </button>
                </div>
            </header>

            <main class="menu-list">
                @foreach ($categories as $category)
                    @php $cat = $itemsByCategory[$category['id']]; @endphp
                    @continue($cat['featured']->isEmpty() && $cat['list']->isEmpty())
                    <section id="cat-{{ $category['id'] }}" class="menu-section">
                        <h3 class="section-label">
                            <span class="section-num">{{ $category['roman'] }}</span>
                            {{ $category['label'] }}
                        </h3>

                        @foreach ($cat['featured'] as $item)
                            <article class="item-card" x-show="itemVisible('{{ $item['slug'] }}')">
                                <button
                                    type="button"
                                    class="item-photo-wrap"
                                    @click="lightbox = { src: '{{ asset($item['image']) }}', name: @js($item['name']), desc: @js($item['desc']), price: {{ $item['price'] }} }"
                                    aria-label="{{ str_replace(':name', $item['name'], $t['ui']['view_larger']) }}"
                                >
                                    <img class="item-photo" src="{{ asset($item['image']) }}" alt="{{ $item['name'] }}" loading="lazy" width="480" height="360" />
                                    <span class="zoom-hint" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                                            <circle cx="11" cy="11" r="7" />
                                            <line x1="21" y1="21" x2="16.2" y2="16.2" />
                                            <line x1="11" y1="8" x2="11" y2="14" />
                                            <line x1="8" y1="11" x2="14" y2="11" />
                                        </svg>
                                    </span>
                                </button>
                                <div class="item-info">
                                    <div class="item-info-top">
                                        <h4>{{ $item['name'] }}</h4>
                                        <span class="item-price">{{ $item['priceFormatted'] }}</span>
                                    </div>
                                    <p>{{ $item['desc'] }}</p>
                                    @if (! empty($item['diet']))
                                        <div class="diet-badges">
                                            @foreach ($item['diet'] as $tag)
                                                <span class="diet-badge">{{ $t['ui']['filter_'.$tag] ?? $tag }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="item-actions">
                                        @include('partials.qty-stepper', ['item' => $item, 't' => $t])
                                    </div>
                                </div>
                            </article>
                        @endforeach

                        @if ($cat['list']->isNotEmpty())
                            @if ($cat['featured']->isNotEmpty())
                                <p class="also-label">{{ $t['ui']['also_on_menu'] }}</p>
                            @endif
                            <div class="text-list">
                                @foreach ($cat['list'] as $item)
                                    <div class="text-row" x-show="itemVisible('{{ $item['slug'] }}')">
                                        <div class="text-row-top">
                                            <h4>{{ $item['name'] }}</h4>
                                            <span class="item-price item-price-plain">{{ $item['priceFormatted'] }}</span>
                                        </div>
                                        <p>{{ $item['desc'] }}</p>
                                        @if (! empty($item['diet']))
                                            <div class="diet-badges">
                                                @foreach ($item['diet'] as $tag)
                                                    <span class="diet-badge">{{ $t['ui']['filter_'.$tag] ?? $tag }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="item-actions">
                                            @include('partials.qty-stepper', ['item' => $item, 't' => $t])
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <p class="filter-empty" x-show="activeFilters.length > 0 && categoryVisibleCount('{{ $category['id'] }}') === 0" x-cloak>{{ $t['ui']['no_matches'] }}</p>
                    </section>
                @endforeach
            </main>

            <footer class="menu-footer">
                <img class="footer-mark" src="{{ asset('img/logo.png') }}" alt="{{ $restaurant['name'] }}" />
                <p>{{ $restaurant['tagline'] }}</p>
            </footer>

            <button
                type="button"
                class="selection-bar"
                x-show="selectionCount() > 0"
                x-cloak
                @click="summaryOpen = true"
                x-transition:enter="bar-enter"
                x-transition:enter-start="bar-enter-start"
                x-transition:enter-end="bar-enter-end"
                x-transition:leave="bar-enter"
                x-transition:leave-start="bar-enter-end"
                x-transition:leave-end="bar-enter-start"
            >
                <span class="selection-bar-badge" x-text="selectionCount()"></span>
                <span class="selection-bar-label">{{ $t['ui']['your_selection'] }}</span>
                <span class="selection-bar-total" x-text="formatPrice(selectionTotal())"></span>
            </button>

            <div
                class="lightbox"
                x-show="lightbox"
                x-cloak
                @click.self="lightbox = null"
                x-transition:enter="lightbox-enter"
                x-transition:enter-start="lightbox-enter-start"
                x-transition:enter-end="lightbox-enter-end"
                x-transition:leave="lightbox-leave"
                x-transition:leave-start="lightbox-leave-start"
                x-transition:leave-end="lightbox-leave-end"
            >
                <button type="button" class="lightbox-close" @click="lightbox = null" aria-label="{{ $t['ui']['close'] }}">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                        <line x1="5" y1="5" x2="19" y2="19" />
                        <line x1="19" y1="5" x2="5" y2="19" />
                    </svg>
                </button>
                <div
                    class="lightbox-card"
                    x-transition:enter="lightbox-card-enter"
                    x-transition:enter-start="lightbox-card-enter-start"
                    x-transition:enter-end="lightbox-card-enter-end"
                    x-transition:leave="lightbox-card-leave"
                    x-transition:leave-start="lightbox-card-leave-start"
                    x-transition:leave-end="lightbox-card-leave-end"
                >
                    <img :src="lightbox && lightbox.src" :alt="lightbox && lightbox.name" />
                    <div class="lightbox-info">
                        <div class="lightbox-info-top">
                            <h3 x-text="lightbox && lightbox.name"></h3>
                            <span x-text="lightbox && formatPrice(lightbox.price)"></span>
                        </div>
                        <p x-text="lightbox && lightbox.desc"></p>
                    </div>
                </div>
            </div>

            <div
                class="lightbox summary-panel"
                x-show="summaryOpen"
                x-cloak
                @click.self="summaryOpen = false"
                x-transition:enter="lightbox-enter"
                x-transition:enter-start="lightbox-enter-start"
                x-transition:enter-end="lightbox-enter-end"
                x-transition:leave="lightbox-leave"
                x-transition:leave-start="lightbox-leave-start"
                x-transition:leave-end="lightbox-leave-end"
            >
                <button type="button" class="lightbox-close" @click="summaryOpen = false" aria-label="{{ $t['ui']['close'] }}">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                        <line x1="5" y1="5" x2="19" y2="19" />
                        <line x1="19" y1="5" x2="5" y2="19" />
                    </svg>
                </button>
                <div
                    class="summary-card"
                    x-transition:enter="lightbox-card-enter"
                    x-transition:enter-start="lightbox-card-enter-start"
                    x-transition:enter-end="lightbox-card-enter-end"
                    x-transition:leave="lightbox-card-leave"
                    x-transition:leave-start="lightbox-card-leave-start"
                    x-transition:leave-end="lightbox-card-leave-end"
                >
                    <h3 class="summary-title">{{ $t['ui']['your_selection'] }}</h3>
                    <p class="summary-hint">{{ $t['ui']['selection_hint'] }}</p>

                    <p class="summary-empty" x-show="selectionCount() === 0">{{ $t['ui']['selection_empty'] }}</p>

                    <div class="summary-list">
                        <template x-for="[slug, qty] in Object.entries(selection).filter(([, q]) => q > 0)" :key="slug">
                            <div class="summary-row">
                                <div class="summary-row-main">
                                    <span class="summary-row-qty" x-text="qty + '×'"></span>
                                    <span class="summary-row-name" x-text="menuItems[slug]?.name"></span>
                                </div>
                                <div class="summary-row-actions">
                                    <button type="button" class="qty-btn qty-minus" @click="removeItem(slug)" aria-label="{{ $t['ui']['remove'] }}">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12" /></svg>
                                    </button>
                                    <button type="button" class="qty-btn qty-plus qty-plus-filled" @click="addItem(slug)" aria-label="{{ $t['ui']['add'] }}">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
                                    </button>
                                    <span class="summary-row-price" x-text="formatPrice((menuItems[slug]?.price || 0) * qty)"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="summary-vat-row" x-show="selectionCount() > 0">
                        <span>{{ str_replace(':rate', $taxRate, $t['ui']['vat_included']) }}</span>
                        <span x-text="formatPrice(taxAmount())"></span>
                    </div>

                    <div class="summary-total-row" x-show="selectionCount() > 0">
                        <span>{{ $t['ui']['total'] }}</span>
                        <span x-text="formatPrice(selectionTotal())"></span>
                    </div>

                    <button type="button" class="summary-clear" x-show="selectionCount() > 0" @click="clearSelection()">{{ $t['ui']['clear_selection'] }}</button>
                </div>
            </div>

            <div
                class="lightbox filter-panel"
                x-show="filtersOpen"
                x-cloak
                @click.self="filtersOpen = false"
                x-transition:enter="lightbox-enter"
                x-transition:enter-start="lightbox-enter-start"
                x-transition:enter-end="lightbox-enter-end"
                x-transition:leave="lightbox-leave"
                x-transition:leave-start="lightbox-leave-start"
                x-transition:leave-end="lightbox-leave-end"
            >
                <button type="button" class="lightbox-close" @click="filtersOpen = false" aria-label="{{ $t['ui']['close'] }}">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                        <line x1="5" y1="5" x2="19" y2="19" />
                        <line x1="19" y1="5" x2="5" y2="19" />
                    </svg>
                </button>
                <div
                    class="filter-card"
                    x-transition:enter="lightbox-card-enter"
                    x-transition:enter-start="lightbox-card-enter-start"
                    x-transition:enter-end="lightbox-card-enter-end"
                    x-transition:leave="lightbox-card-leave"
                    x-transition:leave-start="lightbox-card-leave-start"
                    x-transition:leave-end="lightbox-card-leave-end"
                >
                    <h3 class="summary-title">{{ $t['ui']['filters'] }}</h3>
                    <p class="filter-disclaimer">{{ $t['ui']['filters_disclaimer'] }}</p>
                    <div class="filter-chips">
                        @foreach ($dietFilters as $filter)
                            <button
                                type="button"
                                class="filter-chip"
                                :class="{ 'is-active': activeFilters.includes('{{ $filter }}') }"
                                @click="toggleFilter('{{ $filter }}')"
                            >{{ $t['ui']['filter_'.$filter] ?? $filter }}</button>
                        @endforeach
                    </div>
                    <button type="button" class="summary-clear filter-clear" x-show="activeFilters.length > 0" @click="activeFilters = []">{{ $t['ui']['clear_filters'] }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
