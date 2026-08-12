<?php

use App\Support\MenuImages;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public bool $authenticated = false;
    public string $password = '';
    public string $loginError = '';
    public array $uploads = [];
    public string $justUpdatedSlug = '';

    public function mount(): void
    {
        $this->authenticated = (bool) session('is_manager');
    }

    public function login(): void
    {
        if (hash_equals((string) config('menu.manager_password'), $this->password)) {
            session(['is_manager' => true]);
            $this->authenticated = true;
            $this->loginError = '';
        } else {
            $this->loginError = 'Incorrect password.';
        }

        $this->password = '';
    }

    public function logout(): void
    {
        session()->forget('is_manager');
        $this->authenticated = false;
    }

    public function uploadPhoto(string $slug): void
    {
        if (! $this->authenticated) {
            return;
        }

        // Reject anything that isn't a real, known item slug — the slug becomes
        // part of a filesystem path below, so this is a security boundary, not
        // just a data check.
        if (! collect(config('menu.items'))->contains('slug', $slug)) {
            return;
        }

        $this->validate([
            "uploads.$slug" => 'image|max:5120',
        ]);

        $file = $this->uploads[$slug];
        $filename = $slug.'.'.$file->extension();
        $targetPath = public_path('img/manager/'.$filename);

        // Not $file->move() — Livewire's TemporaryUploadedFile is a real upload
        // from an EARLIER request, replayed here by path. Symfony's UploadedFile
        // ::move() requires is_uploaded_file() to be true for the CURRENT request
        // as an upload-spoofing guard, which always fails for a replayed Livewire
        // temp file — it throws a generic "could not move the file" every time.
        // A plain copy reads/writes real file contents and has no such check.
        copy($file->getRealPath(), $targetPath);
        @unlink($file->getRealPath());

        MenuImages::set($slug, 'img/manager/'.$filename);

        unset($this->uploads[$slug]);
        $this->justUpdatedSlug = $slug;
    }

    public function removePhoto(string $slug): void
    {
        if (! $this->authenticated) {
            return;
        }

        MenuImages::set($slug, null);
    }

    public function with(): array
    {
        if (! $this->authenticated) {
            return ['restaurant' => config('menu.restaurant')];
        }

        $categoryLabels = trans('menu', [], 'en')['categories'] ?? [];

        $items = collect(config('menu.items'))->map(fn ($item) => $item + [
            'currentImage' => MenuImages::resolve($item['slug'], $item['image']),
        ]);

        return [
            'restaurant' => config('menu.restaurant'),
            'categories' => collect(config('menu.categories'))
                ->map(fn ($category) => $category + ['label' => $categoryLabels[$category['id']] ?? $category['id']])
                ->all(),
            'itemsByCategory' => collect(config('menu.categories'))
                ->mapWithKeys(fn ($category) => [
                    $category['id'] => $items->where('category', $category['id'])->values(),
                ]),
        ];
    }
};
?>

<div class="manager-page">
    @if (! $authenticated)
        <div class="manager-login">
            <h1>{{ $restaurant['name'] }} — Manager</h1>
            <p class="manager-login-hint">Staff only. Log in to add or replace dish photos.</p>
            <form wire:submit.prevent="login">
                <input type="password" wire:model="password" placeholder="Password" autofocus class="manager-input" />
                @if ($loginError)
                    <p class="manager-error">{{ $loginError }}</p>
                @endif
                <button type="submit" class="manager-btn manager-btn-primary">Log in</button>
            </form>
        </div>
    @else
        <header class="manager-header">
            <h1>{{ $restaurant['name'] }} — Photo Manager</h1>
            <button type="button" wire:click="logout" class="manager-btn">Log out</button>
        </header>

        <p class="manager-hint">
            Upload a photo for any dish or drink below — it replaces whatever's currently
            shown (or adds one for an item that doesn't have one yet). JPG, PNG or WebP,
            up to 5MB. Changes appear on the live menu immediately.
        </p>

        @foreach ($categories as $category)
            @php $items = $itemsByCategory[$category['id']]; @endphp
            @continue($items->isEmpty())
            <section class="manager-section">
                <h2>{{ $category['label'] }}</h2>

                @foreach ($items as $item)
                    <div class="manager-row" wire:key="row-{{ $item['slug'] }}">
                        <div class="manager-thumb">
                            @if ($item['currentImage'])
                                <img src="{{ asset($item['currentImage']) }}?v={{ now()->timestamp }}" alt="" />
                            @else
                                <span class="manager-thumb-empty">No photo</span>
                            @endif
                        </div>
                        <div class="manager-row-info">
                            <strong>{{ $item['name'] }}</strong>

                            @if ($justUpdatedSlug === $item['slug'])
                                <p class="manager-success">Photo updated.</p>
                            @endif

                            <div class="manager-row-actions">
                                <input type="file" wire:model="uploads.{{ $item['slug'] }}" accept="image/*" class="manager-file" />
                                <button
                                    type="button"
                                    wire:click="uploadPhoto('{{ $item['slug'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="uploads.{{ $item['slug'] }}, uploadPhoto('{{ $item['slug'] }}')"
                                    class="manager-btn manager-btn-primary"
                                >Upload</button>
                                @if ($item['currentImage'])
                                    <button type="button" wire:click="removePhoto('{{ $item['slug'] }}')" class="manager-btn manager-btn-danger">Remove photo</button>
                                @endif
                            </div>

                            @error('uploads.' . $item['slug'])
                                <p class="manager-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endforeach
            </section>
        @endforeach
    @endif
</div>
