<?php

/**
 * Funkcje pomocnicze dla systemu plecaka (backpack)
 */

/**
 * Pobiera przedmioty z plecaka użytkownika
 *
 * @param int $user_id ID użytkownika
 * @return array Tablica przedmiotów w plecaku
 */
function game_get_user_items(int $user_id = null): array
{
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }

    // Pobierz przedmioty użytkownika
    $items = get_field('items', 'user_' . $user_id);

    if (!is_array($items)) {
        return [];
    }

    return $items;
}


/**
 * Pobiera wszystkie typy przedmiotów z taksonomii
 *
 * @return array Tablica typów przedmiotów
 */
function game_get_item_types(): array
{
    $item_types = get_terms([
        'taxonomy' => 'item_type',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ]);

    if (is_wp_error($item_types)) {
        return [];
    }

    return $item_types;
}

/**
 * Grupuje przedmioty według ich typów
 *
 * @param array $items Tablica przedmiotów
 * @return array Tablica przedmiotów pogrupowanych według typów
 */
function game_get_items_by_type(array $items): array
{
    if (empty($items)) {
        return [];
    }

    $items_by_type = [];
    $all_items = []; // Wszystkie przedmioty (bez podziału na typy)

    foreach ($items as $item_data) {
        if (!isset($item_data['item']) || !$item_data['item']) {
            continue;
        }

        $item_id = $item_data['item'];
        $item_quantity = isset($item_data['quantity']) ? (int)$item_data['quantity'] : 1;

        // Pobierz dane przedmiotu
        $item_post = get_post($item_id);
        if (!$item_post) {
            continue;
        }

        // Pobierz typy przedmiotu
        $item_types = get_the_terms($item_id, 'item_type');

        if (empty($item_types) || is_wp_error($item_types)) {
            // Jeśli przedmiot nie ma przypisanego typu, dodaj go do kategorii "Inne"
            $type_id = 'other';
            $type_name = 'Inne';
            $has_subtype = false; // Brak podkategorii
        } else {
            $type = $item_types[0]; // Użyj pierwszego typu (głównego)
            $type_id = $type->term_id;
            $type_name = $type->name;

            // Sprawdź czy to podkategoria
            $parent_id = $type->parent;
            if ($parent_id) {
                $parent = get_term($parent_id, 'item_type');
                if (!is_wp_error($parent)) {
                    $parent_type_id = $parent->term_id;
                    // Użyj ID rodzica jako głównej kategorii
                    $type_id = $parent_type_id;
                    $type_name = $parent->name;

                    // Dodaj informację o podkategorii
                    $has_subtype = true;
                    $subtype_id = $type->term_id;
                    $subtype_name = $type->name;
                } else {
                    $has_subtype = false;
                }
            } else {
                $has_subtype = false; // To kategoria główna, nie podkategoria
            }
        }

        // Pobierz opis i obrazek przedmiotu
        $item_description = get_field('description', $item_id) ?: '';
        $item_image = get_the_post_thumbnail_url($item_id, 'thumbnail') ?: '';

        // Przygotuj dane przedmiotu
        $item_info = [
            'id' => $item_id,
            'title' => get_the_title($item_id),
            'quantity' => $item_quantity,
            'description' => $item_description,
            'image' => $item_image,
        ];

        // Dodaj do listy wszystkich przedmiotów
        $all_items[] = $item_info;

        // Dodaj do odpowiedniej kategorii
        if (!isset($items_by_type[$type_id])) {
            $items_by_type[$type_id] = [
                'name' => $type_name,
                'items' => []
            ];
        }

        $items_by_type[$type_id]['items'][] = $item_info;

        // Jeśli przedmiot ma podkategorię, dodaj go również do niej
        if (isset($has_subtype) && $has_subtype === true && isset($subtype_id)) {
            if (!isset($items_by_type[$type_id]['subtypes'][$subtype_id])) {
                $items_by_type[$type_id]['subtypes'][$subtype_id] = [
                    'name' => $subtype_name,
                    'items' => []
                ];
            }

            $items_by_type[$type_id]['subtypes'][$subtype_id]['items'][] = $item_info;
        }
    }

    // Dodaj kategorię "Wszystkie" na początku
    $result = [
        'all' => [
            'name' => 'Wszystkie',
            'items' => $all_items
        ]
    ];

    return array_merge($result, $items_by_type);
}
