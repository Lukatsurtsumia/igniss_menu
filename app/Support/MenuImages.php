<?php

namespace App\Support;

class MenuImages
{
    protected static function path(): string
    {
        return storage_path('app/menu-image-overrides.json');
    }

    /**
     * Manager-uploaded photo per item slug. A value of null means the manager
     * explicitly removed a photo (including one that shipped in config/menu.php) —
     * distinct from no entry at all, which just means "use the config default".
     */
    public static function all(): array
    {
        if (! file_exists(static::path())) {
            return [];
        }

        return json_decode(file_get_contents(static::path()), true) ?? [];
    }

    public static function set(string $slug, ?string $imagePath): void
    {
        $overrides = static::all();
        $overrides[$slug] = $imagePath;

        file_put_contents(static::path(), json_encode($overrides, JSON_PRETTY_PRINT));
    }

    public static function resolve(string $slug, ?string $default): ?string
    {
        $overrides = static::all();

        return array_key_exists($slug, $overrides) ? $overrides[$slug] : $default;
    }
}
