<?php

use Livewire\Component;

new class extends Component
{
    public bool $showIntro = true;

    public function skipIntro(): void
    {
        $this->showIntro = false;
    }

    public function with(): array
    {
        $items = collect(config('menu.items'));

        return [
            'restaurant' => config('menu.restaurant'),
            'heroImage' => config('menu.hero_image'),
            'categories' => config('menu.categories'),
            'itemsByCategory' => collect(config('menu.categories'))
                ->mapWithKeys(fn ($category) => [
                    $category['id'] => $items->where('category', $category['id'])->values(),
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
            <div class="intro-content">
                <div class="brand">
                    <div class="brand-badge">BV</div>
                    <h1>{{ $restaurant['name'] }}</h1>
                    <p class="muted">{{ $restaurant['tagline'] }}</p>
                </div>
            </div>
        </section>

        <script>
            setTimeout(() => { $wire.skipIntro() }, 3500)
        </script>
    @else
        <div class="menu-page" wire:key="menu-page">
            <header class="menu-header">
                <div class="menu-header-top">
                    <h2>{{ $restaurant['name'] }}</h2>
                    <p class="eyebrow">{{ $restaurant['tagline'] }}</p>
                </div>
                <nav class="categories">
                    @foreach ($categories as $category)
                        <a href="#cat-{{ $category['id'] }}" class="cat-chip">{{ $category['label'] }}</a>
                    @endforeach
                </nav>
            </header>

            <main class="menu-list">
                @foreach ($categories as $category)
                    @continue($itemsByCategory[$category['id']]->isEmpty())
                    <section id="cat-{{ $category['id'] }}" class="menu-section">
                        <h3 class="section-label">{{ $category['label'] }}</h3>

                        @foreach ($itemsByCategory[$category['id']] as $item)
                            <article class="item-card">
                                <div class="item-photo-wrap">
                                    <img class="item-photo" src="{{ asset($item['image']) }}" alt="{{ $item['name'] }}" loading="lazy" width="96" height="96" />
                                </div>
                                <div class="item-info">
                                    <div class="item-info-top">
                                        <h4>{{ $item['name'] }}</h4>
                                        <span class="item-price">${{ number_format($item['price'], 2) }}</span>
                                    </div>
                                    <p>{{ $item['desc'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </section>
                @endforeach
            </main>
        </div>
    @endif
</div>
