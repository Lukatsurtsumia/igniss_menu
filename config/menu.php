<?php

return [

    'restaurant' => [
        'name' => 'Igniss',
        'tagline' => 'Restaurant • Cocktail Bar — Nice',
    ],

    'hero_image' => 'img/hero-igniss.jpg',

    'locales' => ['fr', 'en', 'es', 'it'],
    'default_locale' => 'fr',

    // Menu prices are tax-inclusive (standard for a French menu — what's printed
    // is what's paid). This rate is only used to show the included-VAT breakdown
    // on the selection summary, never added on top of a displayed price.
    'tax_rate' => 20,

    // Single shared password gating /manager — proportionate to what's actually
    // at stake (someone swaps a dish photo), not a full user/role system.
    // Change this in .env before this ever goes anywhere near production.
    'manager_password' => env('MENU_MANAGER_PASSWORD', 'igniss-manager'),

    'diet_filters' => ['vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'nut-free'],

    'categories' => [
        ['id' => 'starters', 'roman' => 'I'],
        ['id' => 'mains', 'roman' => 'II'],
        ['id' => 'desserts', 'roman' => 'III'],
        ['id' => 'drinks', 'roman' => 'IV'],
        ['id' => 'hotdrinks', 'roman' => 'V'],
    ],

    // Names are the dish's own name and stay constant across every locale —
    // real menus don't translate proper dish/drink names, only descriptions
    // (translated per-locale in lang/{locale}/menu.php, keyed by slug).
    // 'image' is only set when a real Igniss photo of that exact dish exists —
    // items without a confirmed photo render as a text-only listing rather than
    // being paired with a stand-in image of a different dish.
    //
    // 'diet' is inferred by reading each dish's own listed ingredients (e.g. no
    // dairy mentioned → 'dairy-free') — it is NOT verified by Igniss's kitchen
    // and must be treated as illustrative only. The UI carries this disclaimer
    // visibly; do not remove it if this ever becomes a real menu without first
    // getting every tag confirmed by the restaurant.
    'items' => [

        // ---- Entrées ----
        [
            'slug' => 'agneau-pistache',
            'name' => 'Agneau à la Pistache',
            'price' => 16.0,
            'image' => 'img/agneau-pistache.jpg',
            'category' => 'starters',
            'diet' => ['gluten-free'],
        ],
        [
            'slug' => 'burrata-fumee',
            'name' => 'Burrata Fumée',
            'price' => 17.0,
            'image' => 'img/burrata-fumee.jpg',
            'category' => 'starters',
            'diet' => ['vegetarian', 'gluten-free'],
        ],
        [
            'slug' => 'tartare-daurade',
            'name' => 'Tartare de Daurade Royale',
            'price' => 18.0,
            'image' => 'img/tartare-daurade.jpg',
            'category' => 'starters',
            'diet' => ['gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'ajo-blanco',
            'name' => 'Ajo Blanco',
            'price' => 14.0,
            'image' => null,
            'category' => 'starters',
            'diet' => ['dairy-free'],
        ],
        [
            'slug' => 'dattes-chorizo',
            'name' => 'Dattes au Chorizo',
            'price' => 16.0,
            'image' => null,
            'category' => 'starters',
            'diet' => ['gluten-free'],
        ],
        [
            'slug' => 'taramosalata',
            'name' => 'Taramosalata',
            'price' => 16.0,
            'image' => null,
            'category' => 'starters',
            'diet' => ['gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'tartare-vitello',
            'name' => 'Tartare di Vitello',
            'price' => 19.0,
            'image' => null,
            'category' => 'starters',
            'diet' => ['gluten-free', 'nut-free'],
        ],

        // ---- Plats ----
        [
            'slug' => 'gambas-ajillo',
            'name' => 'Gambas al Ajillo',
            'price' => 30.0,
            'image' => 'img/gambas-ajillo.jpg',
            'category' => 'mains',
            'diet' => ['gluten-free', 'nut-free'],
        ],
        [
            'slug' => 'pave-turbot',
            'name' => 'Pavé de Turbot',
            'price' => 27.0,
            'image' => null,
            'category' => 'mains',
            'diet' => ['gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'coeur-filet-boeuf',
            'name' => 'Cœur de Filet de Bœuf',
            'price' => 32.0,
            'image' => null,
            'category' => 'mains',
            'diet' => ['gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'noix-veau-corse',
            'name' => 'Noix de Veau à la Corse',
            'price' => 29.0,
            'image' => null,
            'category' => 'mains',
            'diet' => ['gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'sfogliata-zucchine',
            'name' => 'Sfogliata di Zucchine',
            'price' => 26.0,
            'image' => null,
            'category' => 'mains',
            'diet' => ['vegetarian', 'nut-free'],
        ],
        [
            'slug' => 'rigatoni-siciliana',
            'name' => 'Mezzi Rigatoni alla Siciliana',
            'price' => 26.0,
            'image' => null,
            'category' => 'mains',
            'diet' => ['vegetarian', 'nut-free'],
        ],
        [
            'slug' => 'accompagnements',
            'name' => 'Accompagnements',
            'price' => 5.0,
            'image' => null,
            'category' => 'mains',
            'diet' => ['vegetarian', 'gluten-free', 'nut-free'],
        ],

        // ---- Desserts ----
        [
            'slug' => 'dolce-fragola',
            'name' => 'La Dolce Fragola',
            'price' => 12.0,
            'image' => 'img/dolce-fragola.jpg',
            'category' => 'desserts',
            'diet' => ['vegetarian'],
        ],
        [
            'slug' => 'le-chocolat',
            'name' => 'Le Chocolat',
            'price' => 12.0,
            'image' => null,
            'category' => 'desserts',
            'diet' => ['vegetarian'],
        ],
        [
            'slug' => 'le-moka',
            'name' => 'Le Moka',
            'price' => 11.0,
            'image' => null,
            'category' => 'desserts',
            'diet' => ['vegetarian', 'gluten-free'],
        ],
        [
            'slug' => 'glaces-sorbets',
            'name' => 'Glaces et Sorbets Artisanaux',
            'price' => 3.0,
            'image' => null,
            'category' => 'desserts',
            'diet' => ['vegetarian', 'gluten-free'],
        ],
        [
            'slug' => 'supplement-chantilly',
            'name' => 'Supplément Chantilly Maison',
            'price' => 2.0,
            'image' => null,
            'category' => 'desserts',
            'diet' => ['vegetarian', 'gluten-free'],
        ],

        // ---- Cocktails ----
        [
            'slug' => 'sangria-provencale',
            'name' => 'Sangria Provençale',
            'price' => 12.0,
            'image' => 'img/sangria-provencale.jpg',
            'category' => 'drinks',
            'diet' => ['vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'old-fashioned-date',
            'name' => 'The Old Fashioned Date',
            'price' => 14.0,
            'image' => 'img/old-fashioned-date.jpg',
            'category' => 'drinks',
            'diet' => ['vegan', 'dairy-free'],
        ],
        [
            'slug' => 'punch-riviera',
            'name' => 'Punch Riviera',
            'price' => 14.0,
            'image' => null,
            'category' => 'drinks',
            'diet' => ['vegan', 'gluten-free', 'dairy-free'],
        ],
        [
            'slug' => 'summer-sbagliato',
            'name' => 'Summer Sbagliato',
            'price' => 14.0,
            'image' => null,
            'category' => 'drinks',
            'diet' => ['vegan', 'gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'bayas-margarita',
            'name' => 'Bayas Margarita',
            'price' => 14.0,
            'image' => null,
            'category' => 'drinks',
            'diet' => ['vegan', 'gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'shrub-me-up',
            'name' => 'Shrub Me Up N°2',
            'price' => 14.0,
            'image' => null,
            'category' => 'drinks',
            'diet' => ['gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'pillow-talk',
            'name' => 'Pillow Talk',
            'price' => 14.0,
            'image' => null,
            'category' => 'drinks',
            'diet' => ['vegan', 'gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'verikoko',
            'name' => 'Verikoko',
            'price' => 14.0,
            'image' => null,
            'category' => 'drinks',
            'diet' => ['gluten-free'],
        ],
        [
            'slug' => 'mediterraneo-smash',
            'name' => 'Mediterraneo Smash',
            'price' => 9.0,
            'image' => null,
            'category' => 'drinks',
            'diet' => ['vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'shrub-me-softly',
            'name' => 'Shrub Me Softly N°2',
            'price' => 9.0,
            'image' => null,
            'category' => 'drinks',
            'diet' => ['gluten-free', 'dairy-free', 'nut-free'],
        ],

        // ---- Boissons Chaudes ----
        [
            'slug' => 'espresso',
            'name' => 'Espresso',
            'price' => 3.5,
            'image' => null,
            'category' => 'hotdrinks',
            'diet' => ['vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'cafe-allonge',
            'name' => 'Café Allongé',
            'price' => 4.0,
            'image' => null,
            'category' => 'hotdrinks',
            'diet' => ['vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'cappuccino',
            'name' => 'Cappuccino',
            'price' => 5.0,
            'image' => null,
            'category' => 'hotdrinks',
            'diet' => ['vegetarian', 'gluten-free', 'nut-free'],
        ],
        [
            'slug' => 'chocolat-chaud',
            'name' => 'Chocolat Chaud',
            'price' => 6.0,
            'image' => null,
            'category' => 'hotdrinks',
            'diet' => ['vegetarian', 'gluten-free', 'nut-free'],
        ],
        [
            'slug' => 'the-infusions',
            'name' => 'Thé et Infusions',
            'price' => 5.0,
            'image' => null,
            'category' => 'hotdrinks',
            'diet' => ['vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'nut-free'],
        ],
        [
            'slug' => 'cafe-gourmand',
            'name' => 'Café Gourmand',
            'price' => 9.0,
            'image' => null,
            'category' => 'hotdrinks',
            'diet' => ['vegetarian'],
        ],
    ],

];
