<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<title>{{ config('menu.restaurant.name') }} — Menu</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍽️</text></svg>" />
<link rel="preload" href="{{ asset('fonts/fraunces-variable.woff2') }}" as="font" type="font/woff2" crossorigin />
<link rel="stylesheet" href="{{ asset('css/app.css') }}" />
@livewireStyles
</head>
<body>
<livewire:menu />
@livewireScripts
</body>
</html>
