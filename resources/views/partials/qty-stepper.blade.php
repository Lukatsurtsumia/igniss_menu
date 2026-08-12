@php
    $slug = $item['slug'];
@endphp
<div class="qty-stepper">
    <button
        type="button"
        class="qty-btn qty-minus"
        x-show="(selection['{{ $slug }}']||0) > 0"
        x-cloak
        @click="removeItem('{{ $slug }}')"
        aria-label="{{ $t['ui']['remove'] }} — {{ $item['name'] }}"
    >
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12" /></svg>
    </button>
    <span class="qty-value" x-show="(selection['{{ $slug }}']||0) > 0" x-cloak x-text="selection['{{ $slug }}']"></span>
    <button
        type="button"
        class="qty-btn qty-plus"
        :class="{ 'qty-plus-filled': (selection['{{ $slug }}']||0) > 0 }"
        @click="addItem('{{ $slug }}')"
        aria-label="{{ $t['ui']['add'] }} — {{ $item['name'] }}"
    >
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
    </button>
</div>
