<?php

function recipe_catalog(): array
{
    return [
        [
            'id' => 1,
            'title' => 'Special Salad Chicken',
            'slug' => 'special-salad-chicken',
            'image' => '../assets/img/recipe-salad-hero.png',
            'author' => 'Nayaka',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '20 mins',
            'servings' => '2 servings',
            'difficulty' => 'Easy',
            'rating' => 4.5,
            'summary' => 'Enjoy the perfect combination of protein-rich grilled chicken breast and fresh vegetables.',
            'description' => 'Enjoy the perfect combination of protein-rich grilled chicken breast and a selection of fresh vegetables such as romaine lettuce, cherry tomatoes, cucumbers, and purple cabbage. Served with a light dressing, this dish feels fresh, filling, and easy to make at home.',
            'ingredients' => [
                '2 chicken breast fillets',
                '2 cups romaine lettuce',
                '1 cup cherry tomatoes',
                '1 cucumber, sliced',
                '1/4 red onion',
                '2 tablespoons salad dressing',
            ],
            'tools' => [
                'Mixing bowl',
                'Sharp knife',
                'Frying pan',
                'Serving plate',
            ],
            'steps' => [
                'Season the chicken with salt, pepper, and a little olive oil.',
                'Grill the chicken until cooked through and slice it into strips.',
                'Wash the vegetables and arrange them in a bowl or plate.',
                'Add the chicken on top, pour the dressing, and serve immediately.',
            ],
            'related' => [2, 3, 4],
        ],
        [
            'id' => 2,
            'title' => 'Japanese Macha',
            'slug' => 'japanese-macha',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'ResepKu Team',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '15 mins',
            'servings' => '1 serving',
            'difficulty' => 'Easy',
            'rating' => 4.3,
            'summary' => 'A quick and refreshing drink for your afternoon break.',
            'description' => 'A quick and refreshing Japanese matcha drink with a creamy texture and a light sweet finish.',
            'ingredients' => [
                '1 teaspoon matcha powder',
                '2 teaspoons hot water',
                '200 ml milk',
                '1 teaspoon sugar or honey',
            ],
            'tools' => [
                'Whisk',
                'Glass',
                'Small bowl',
            ],
            'steps' => [
                'Mix matcha powder with hot water until smooth.',
                'Heat or froth the milk as desired.',
                'Combine the milk with the matcha mixture.',
                'Add sweetener and serve chilled or warm.',
            ],
            'related' => [1, 3, 5],
        ],
        [
            'id' => 3,
            'title' => 'Sweet Strawberry Cake',
            'slug' => 'sweet-strawberry-cake',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'ResepKu Team',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '45 mins',
            'servings' => '6 servings',
            'difficulty' => 'Medium',
            'rating' => 4.7,
            'summary' => 'Soft sponge cake with fresh strawberries and a creamy finish.',
            'description' => 'Soft sponge cake layered with whipped cream and fresh strawberries. A simple sweet treat for family gatherings.',
            'ingredients' => [
                '2 eggs',
                '100 g flour',
                '80 g sugar',
                '150 ml whipped cream',
                'Fresh strawberries',
            ],
            'tools' => [
                'Mixing bowl',
                'Oven',
                'Spatula',
            ],
            'steps' => [
                'Whisk eggs and sugar until fluffy.',
                'Fold in flour gently and bake the sponge.',
                'Whip the cream until soft peaks form.',
                'Layer the sponge, cream, and strawberries.',
            ],
            'related' => [1, 2, 4],
        ],
        [
            'id' => 4,
            'title' => 'Creamy Tomato Soup',
            'slug' => 'creamy-tomato-soup',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'ResepKu Team',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '30 mins',
            'servings' => '4 servings',
            'difficulty' => 'Easy',
            'rating' => 4.4,
            'summary' => 'Warm soup with a smooth tomato base and creamy texture.',
            'description' => 'Warm, smooth tomato soup with a creamy finish and a comforting flavor.',
            'ingredients' => [
                '5 ripe tomatoes',
                '1 onion',
                '2 cloves garlic',
                '200 ml broth',
                '100 ml cream',
            ],
            'tools' => [
                'Pot',
                'Blender',
                'Wooden spoon',
            ],
            'steps' => [
                'Saute onion and garlic until fragrant.',
                'Add tomatoes and broth, then simmer.',
                'Blend until smooth and creamy.',
                'Finish with cream and season to taste.',
            ],
            'related' => [1, 3, 5],
        ],
        [
            'id' => 5,
            'title' => 'Herb Chicken Bowl',
            'slug' => 'herb-chicken-bowl',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'Nayaka',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '25 mins',
            'servings' => '2 servings',
            'difficulty' => 'Easy',
            'rating' => 4.6,
            'summary' => 'Simple chicken bowl with rice, herbs, and balanced flavor.',
            'description' => 'A balanced chicken bowl with rice, fresh herbs, and a savory light sauce.',
            'ingredients' => [
                '1 cup rice',
                '2 chicken thighs',
                'Fresh parsley',
                'Garlic sauce',
            ],
            'tools' => [
                'Rice cooker',
                'Pan',
                'Serving bowl',
            ],
            'steps' => [
                'Cook the rice until fluffy.',
                'Pan-fry the chicken with herbs and seasoning.',
                'Place rice in a bowl and top with chicken.',
                'Finish with sauce and fresh herbs.',
            ],
            'related' => [1, 2, 6],
        ],
        [
            'id' => 6,
            'title' => 'Fruit Parfait',
            'slug' => 'fruit-parfait',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'ResepKu Team',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '10 mins',
            'servings' => '1 serving',
            'difficulty' => 'Easy',
            'rating' => 4.1,
            'summary' => 'Layered yogurt dessert with fruit and crunchy granola.',
            'description' => 'A cold layered dessert with yogurt, fruit, and granola for a quick sweet snack.',
            'ingredients' => [
                '1 cup yogurt',
                'Banana slices',
                'Strawberries',
                'Granola',
            ],
            'tools' => [
                'Glass cup',
                'Spoon',
            ],
            'steps' => [
                'Add yogurt to the bottom of a glass.',
                'Layer with fruit and granola.',
                'Repeat the layers until full.',
                'Chill briefly and serve.',
            ],
            'related' => [3, 4, 7],
        ],
        [
            'id' => 7,
            'title' => 'Spicy Tuna Rice',
            'slug' => 'spicy-tuna-rice',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'Nayaka',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '22 mins',
            'servings' => '2 servings',
            'difficulty' => 'Medium',
            'rating' => 4.2,
            'summary' => 'Rice bowl with spicy tuna and simple pantry ingredients.',
            'description' => 'A fast rice bowl with spicy tuna, a savory sauce, and a punch of flavor.',
            'ingredients' => [
                '1 cup warm rice',
                '1 can tuna',
                'Chili sauce',
                'Green onion',
            ],
            'tools' => [
                'Bowl',
                'Spoon',
                'Pan',
            ],
            'steps' => [
                'Mix tuna with chili sauce and seasoning.',
                'Prepare warm rice in a bowl.',
                'Add the spicy tuna on top.',
                'Garnish with green onion and serve.',
            ],
            'related' => [1, 5, 8],
        ],
        [
            'id' => 8,
            'title' => 'Garlic Pasta Bowl',
            'slug' => 'garlic-pasta-bowl',
            'image' => '../assets/img/recipe-salad-card.png',
            'author' => 'ResepKu Team',
            'author_avatar' => '../assets/img/home-profile.png',
            'cook_time' => '18 mins',
            'servings' => '2 servings',
            'difficulty' => 'Easy',
            'rating' => 4.3,
            'summary' => 'A quick pasta bowl with garlic, herbs, and light olive oil.',
            'description' => 'Quick garlic pasta with olive oil, herbs, and a clean homemade taste.',
            'ingredients' => [
                '200 g pasta',
                '3 cloves garlic',
                'Olive oil',
                'Parsley',
            ],
            'tools' => [
                'Pot',
                'Pan',
                'Tongs',
            ],
            'steps' => [
                'Boil pasta until al dente.',
                'Saute garlic in olive oil.',
                'Toss pasta with the garlic oil and herbs.',
                'Serve warm with extra seasoning if needed.',
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
        'title' => 'Recipe Preview',
        'slug' => 'recipe-preview',
        'image' => '../assets/img/recipe-salad-hero.png',
        'author' => 'ResepKu',
        'author_avatar' => '../assets/img/home-profile.png',
        'cook_time' => '20 mins',
        'servings' => '2 servings',
        'difficulty' => 'Easy',
        'rating' => 4.0,
        'summary' => 'Sample recipe content for the frontend layout.',
        'description' => 'Sample recipe content for the frontend layout.',
        'ingredients' => ['Sample ingredient'],
        'tools' => ['Sample tool'],
        'steps' => ['Sample step'],
        'related' => [],
    ];
}
