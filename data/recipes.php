<?php

function recipe_catalog(): array
{
    return [
        [
            'id' => 1,
            'title' => 'Salad Ayam Spesial',
            'slug' => 'special-salad-chicken',
            'image' => '../assets/img/recipe-salad-hero.png',
            'author' => 'Nayaka',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '20 menit',
            'servings' => '2 porsi',
            'difficulty' => 'Mudah',
            'rating' => 4.5,
            'summary' => 'Perpaduan dada ayam panggang kaya protein dengan sayuran segar.',
            'description' => 'Perpaduan dada ayam panggang kaya protein dengan sayuran segar seperti selada romaine, tomat ceri, mentimun, dan kubis ungu. Disajikan dengan dressing ringan, menu ini terasa segar, mengenyangkan, dan mudah dibuat di rumah.',
            'ingredients' => [
                '2 fillet dada ayam',
                '2 cangkir selada romaine',
                '1 cangkir tomat ceri',
                '1 mentimun, iris',
                '1/4 bawang merah',
                '2 sendok makan dressing salad',
            ],
            'tools' => [
                'Mangkuk aduk',
                'Pisau tajam',
                'Wajan',
                'Piring saji',
            ],
            'steps' => [
                'Bumbui ayam dengan garam, lada, dan sedikit minyak zaitun.',
                'Panggang ayam sampai matang, lalu iris memanjang.',
                'Cuci sayuran dan tata di mangkuk atau piring.',
                'Tambahkan ayam di atasnya, tuang dressing, lalu sajikan.',
            ],
            'related' => [2, 3, 4],
        ],
        [
            'id' => 2,
            'title' => 'Matcha Jepang',
            'slug' => 'japanese-macha',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'Tim ResepKu',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '15 menit',
            'servings' => '1 porsi',
            'difficulty' => 'Mudah',
            'rating' => 4.3,
            'summary' => 'Minuman cepat dan segar untuk jeda sore.',
            'description' => 'Minuman matcha Jepang yang cepat dibuat, bertekstur creamy, dan memiliki rasa manis ringan.',
            'ingredients' => [
                '1 sendok teh bubuk matcha',
                '2 sendok teh air panas',
                '200 ml susu',
                '1 sendok teh gula atau madu',
            ],
            'tools' => [
                'Pengocok',
                'Gelas',
                'Mangkuk kecil',
            ],
            'steps' => [
                'Campur bubuk matcha dengan air panas sampai halus.',
                'Panaskan atau buihkan susu sesuai selera.',
                'Campurkan susu dengan larutan matcha.',
                'Tambahkan pemanis dan sajikan dingin atau hangat.',
            ],
            'related' => [1, 3, 5],
        ],
        [
            'id' => 3,
            'title' => 'Kue Stroberi Manis',
            'slug' => 'sweet-strawberry-cake',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'Tim ResepKu',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '45 menit',
            'servings' => '6 porsi',
            'difficulty' => 'Sedang',
            'rating' => 4.7,
            'summary' => 'Sponge cake lembut dengan stroberi segar dan krim.',
            'description' => 'Sponge cake lembut berlapis whipped cream dan stroberi segar. Camilan manis sederhana untuk acara keluarga.',
            'ingredients' => [
                '2 butir telur',
                '100 g tepung terigu',
                '80 g gula',
                '150 ml whipped cream',
                'Stroberi segar',
            ],
            'tools' => [
                'Mangkuk aduk',
                'Oven',
                'Spatula',
            ],
            'steps' => [
                'Kocok telur dan gula sampai mengembang.',
                'Masukkan tepung perlahan, lalu panggang adonan sponge.',
                'Kocok krim sampai membentuk puncak lembut.',
                'Susun sponge, krim, dan stroberi berlapis.',
            ],
            'related' => [1, 2, 4],
        ],
        [
            'id' => 4,
            'title' => 'Sup Tomat Creamy',
            'slug' => 'creamy-tomato-soup',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'Tim ResepKu',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '30 menit',
            'servings' => '4 porsi',
            'difficulty' => 'Mudah',
            'rating' => 4.4,
            'summary' => 'Sup hangat berbahan dasar tomat halus dengan tekstur creamy.',
            'description' => 'Sup tomat hangat dan lembut dengan sentuhan creamy serta rasa yang nyaman disantap.',
            'ingredients' => [
                '5 tomat matang',
                '1 bawang bombai',
                '2 siung bawang putih',
                '200 ml kaldu',
                '100 ml krim',
            ],
            'tools' => [
                'Panci',
                'Blender',
                'Sendok kayu',
            ],
            'steps' => [
                'Tumis bawang bombai dan bawang putih sampai harum.',
                'Tambahkan tomat dan kaldu, lalu masak perlahan.',
                'Blender sampai halus dan creamy.',
                'Tambahkan krim, lalu bumbui sesuai selera.',
            ],
            'related' => [1, 3, 5],
        ],
        [
            'id' => 5,
            'title' => 'Nasi Ayam Herba',
            'slug' => 'herb-chicken-bowl',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'Nayaka',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '25 menit',
            'servings' => '2 porsi',
            'difficulty' => 'Mudah',
            'rating' => 4.6,
            'summary' => 'Nasi ayam sederhana dengan herba dan rasa seimbang.',
            'description' => 'Nasi ayam seimbang dengan nasi hangat, herba segar, dan saus gurih ringan.',
            'ingredients' => [
                '1 cangkir beras',
                '2 paha ayam',
                'Peterseli segar',
                'Saus bawang putih',
            ],
            'tools' => [
                'Penanak nasi',
                'Wajan',
                'Mangkuk saji',
            ],
            'steps' => [
                'Masak nasi sampai pulen.',
                'Goreng ayam dengan herba dan bumbu.',
                'Letakkan nasi di mangkuk, lalu tambahkan ayam di atasnya.',
                'Lengkapi dengan saus dan herba segar.',
            ],
            'related' => [1, 2, 6],
        ],
        [
            'id' => 6,
            'title' => 'Parfait Buah',
            'slug' => 'fruit-parfait',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'Tim ResepKu',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '10 menit',
            'servings' => '1 porsi',
            'difficulty' => 'Mudah',
            'rating' => 4.1,
            'summary' => 'Dessert yogurt berlapis buah dan granola renyah.',
            'description' => 'Dessert dingin berlapis yogurt, buah, dan granola untuk camilan manis yang cepat dibuat.',
            'ingredients' => [
                '1 cangkir yogurt',
                'Irisan pisang',
                'Stroberi',
                'Granola',
            ],
            'tools' => [
                'Gelas',
                'Sendok',
            ],
            'steps' => [
                'Masukkan yogurt ke dasar gelas.',
                'Tambahkan lapisan buah dan granola.',
                'Ulangi lapisan sampai gelas penuh.',
                'Dinginkan sebentar, lalu sajikan.',
            ],
            'related' => [3, 4, 7],
        ],
        [
            'id' => 7,
            'title' => 'Nasi Tuna Pedas',
            'slug' => 'spicy-tuna-rice',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'Nayaka',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '22 menit',
            'servings' => '2 porsi',
            'difficulty' => 'Sedang',
            'rating' => 4.2,
            'summary' => 'Nasi mangkuk dengan tuna pedas dan bahan sederhana.',
            'description' => 'Nasi mangkuk cepat dengan tuna pedas, saus gurih, dan rasa yang kuat.',
            'ingredients' => [
                '1 cangkir nasi hangat',
                '1 kaleng tuna',
                'Saus cabai',
                'Daun bawang',
            ],
            'tools' => [
                'Mangkuk',
                'Sendok',
                'Wajan',
            ],
            'steps' => [
                'Campur tuna dengan saus cabai dan bumbu.',
                'Siapkan nasi hangat di mangkuk.',
                'Tambahkan tuna pedas di atasnya.',
                'Beri daun bawang, lalu sajikan.',
            ],
            'related' => [1, 5, 8],
        ],
        [
            'id' => 8,
            'title' => 'Pasta Bawang Putih',
            'slug' => 'garlic-pasta-bowl',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'Tim ResepKu',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '18 menit',
            'servings' => '2 porsi',
            'difficulty' => 'Mudah',
            'rating' => 4.3,
            'summary' => 'Pasta cepat dengan bawang putih, herba, dan minyak zaitun ringan.',
            'description' => 'Pasta bawang putih cepat dengan minyak zaitun, herba, dan rasa rumahan yang ringan.',
            'ingredients' => [
                '200 g pasta',
                '3 siung bawang putih',
                'Minyak zaitun',
                'Peterseli',
            ],
            'tools' => [
                'Panci',
                'Wajan',
                'Penjepit makanan',
            ],
            'steps' => [
                'Rebus pasta sampai al dente.',
                'Tumis bawang putih dengan minyak zaitun.',
                'Campur pasta dengan minyak bawang dan herba.',
                'Sajikan hangat dengan bumbu tambahan jika perlu.',
            ],
            'related' => [2, 4, 7],
        ],
    ];
}

function recipe_find(int $id): ?array
{
    foreach (recipe_catalog() as $recipe) {
        if ((int) $recipe['id'] === $id) {
            return $recipe;
        }
    }

    return null;
}

function recipe_related(array $recipe, int $limit = 2): array
{
    $related = [];

    foreach ($recipe['related'] ?? [] as $relatedId) {
        $item = recipe_find((int) $relatedId);
        if ($item && (int) $item['id'] !== (int) $recipe['id']) {
            $related[] = $item;
        }

        if (count($related) >= $limit) {
            return $related;
        }
    }

    foreach (recipe_catalog() as $item) {
        if ((int) $item['id'] === (int) $recipe['id']) {
            continue;
        }

        $related[] = $item;

        if (count($related) >= $limit) {
            break;
        }
    }

    return $related;
}

function recipe_fallback(): array
{
    return [
        'id' => 0,
        'title' => 'Pratinjau Resep',
        'slug' => 'recipe-preview',
        'image' => '../assets/img/recipe-salad-hero.png',
        'author' => 'ResepKu',
        'author_avatar' => '../assets/img/home-profile.png',
        'cook_time' => '20 menit',
        'servings' => '2 porsi',
        'difficulty' => 'Mudah',
        'rating' => 4.0,
        'summary' => 'Contoh konten resep untuk tampilan frontend.',
        'description' => 'Contoh konten resep untuk tampilan frontend.',
        'ingredients' => ['Contoh bahan'],
        'tools' => ['Contoh alat'],
        'steps' => ['Contoh langkah'],
        'related' => [],
    ];
}
