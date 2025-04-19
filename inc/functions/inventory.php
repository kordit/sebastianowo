<?php

/**
 * Całkowicie przeprojektowana funkcja ekwipunku
 * Ta funkcja zastępuje poprzednią wersję display_user_inventory
 */
function display_user_inventory_new($user_id)
{
    // Pobierz wszystkie przedmioty użytkownika z pola ACF
    $user_items = array();

    if (function_exists('get_field')) {
        // Pobierz przedmioty z pola repeater 'items'
        $items_repeater = get_field('items', 'user_' . $user_id);

        if (!empty($items_repeater) && is_array($items_repeater)) {
            foreach ($items_repeater as $item_entry) {
                if (isset($item_entry['item']) && is_object($item_entry['item'])) {
                    $item_post = $item_entry['item'];
                    $item_id = $item_post->ID;

                    // Pobierz podstawowe informacje o przedmiocie
                    $item_name = get_the_title($item_id);
                    $item_desc = get_post_meta($item_id, 'item_description', true);
                    $quantity = isset($item_entry['quantity']) ? intval($item_entry['quantity']) : 1;
                    $equipped = isset($item_entry['equipped']) && $item_entry['equipped'] ? true : false;

                    // Pobierz kategorie przedmiotu (taksonomie)
                    $item_types = wp_get_post_terms($item_id, 'item_type');
                    $item_type = !empty($item_types) ? $item_types[0] : null;

                    // Pobierz obrazek przedmiotu
                    $image_url = '';
                    if (has_post_thumbnail($item_id)) {
                        $image_url = get_the_post_thumbnail_url($item_id, 'thumbnail');
                    }

                    // Dodaj przedmiot do tablicy
                    $user_items[] = array(
                        'id' => $item_id,
                        'name' => $item_name,
                        'description' => $item_desc,
                        'quantity' => $quantity,
                        'equipped' => $equipped,
                        'image' => $image_url,
                        'type' => $item_type
                    );
                }
            }
        }
    }

    // Jeżeli nie ma przedmiotów, wyświetl komunikat
    if (empty($user_items)) {
        return '<p class="empty-message">Twój ekwipunek jest pusty.</p>';
    }

    // Pobierz wszystkie kategorie przedmiotów (taksonomia item_type)
    $item_categories = get_terms(array(
        'taxonomy' => 'item_type',
        'hide_empty' => false
    ));

    // Zorganizuj kategorie według relacji rodzic-dziecko
    $categories_by_parent = array();
    foreach ($item_categories as $category) {
        if (!isset($categories_by_parent[$category->parent])) {
            $categories_by_parent[$category->parent] = array();
        }
        $categories_by_parent[$category->parent][] = $category;
    }

    // Początek kontenera ekwipunku
    $output = '<div class="inventory-container">';

    // Sidebar menu z kategoriami
    $output .= '<div class="inventory-sidebar">';
    $output .= '<ul class="inventory-types">';
    $output .= '<li class="inventory-type-item active" data-type="all">Wszystkie</li>';

    // Funkcja rekurencyjna do wyświetlania zagnieżdżonych kategorii
    function render_category_tree($parent_id, $categories_by_parent)
    {
        if (!isset($categories_by_parent[$parent_id])) {
            return '';
        }

        $output = '';
        foreach ($categories_by_parent[$parent_id] as $category) {
            $has_children = isset($categories_by_parent[$category->term_id]);
            $expand_button = $has_children ? ' <span class="expand-button">+</span>' : '';
            $class = $has_children ? ' has-children collapsed' : '';

            $output .= '<li class="inventory-type-item' . $class . '" data-type="' . esc_attr($category->slug) . '">' .
                esc_html($category->name) . $expand_button;

            if ($has_children) {
                $output .= '<ul class="inventory-subtypes" style="display:none;">';
                $output .= render_category_tree($category->term_id, $categories_by_parent);
                $output .= '</ul>';
            }

            $output .= '</li>';
        }

        return $output;
    }

    // Wygeneruj drzewo kategorii
    if (isset($categories_by_parent[0])) {
        $output .= render_category_tree(0, $categories_by_parent);
    }

    $output .= '</ul>';
    $output .= '</div>'; // Koniec sidebar

    // Prawa strona z przedmiotami
    $output .= '<div class="inventory-content">';
    $output .= '<div class="inventory-items-grid">';

    // Wyświetl wszystkie przedmioty użytkownika
    foreach ($user_items as $item) {
        $type_class = '';
        $type_attr = '';
        $type_name = '';

        if (!empty($item['type'])) {
            $type_class = ' item-' . $item['type']->slug;
            $type_attr = ' data-type="' . esc_attr($item['type']->slug) . '"';
            $type_name = $item['type']->name;
        }

        $equipped_class = $item['equipped'] ? ' equipped' : '';

        // Generuj HTML dla przedmiotu
        $output .= '<div class="inventory-item' . $equipped_class . $type_class . '"' . $type_attr . ' data-id="' . esc_attr($item['id']) . '">';

        // Obrazek przedmiotu
        $output .= '<div class="item-image">';
        if (!empty($item['image'])) {
            $output .= '<img src="' . esc_url($item['image']) . '" alt="' . esc_attr($item['name']) . '">';
        } else {
            $output .= '<div class="no-image">?</div>';
        }
        $output .= '</div>';

        // Szczegóły przedmiotu
        $output .= '<div class="item-details">';
        $output .= '<h3>' . esc_html($item['name']) . '</h3>';

        // Informacje o kategorii
        if (!empty($type_name)) {
            $output .= '<div class="item-taxonomy"><span class="item-type">' . esc_html($type_name) . '</span></div>';
        }

        // Opis przedmiotu
        if (!empty($item['description'])) {
            $output .= '<p class="item-description">' . esc_html($item['description']) . '</p>';
        }

        // Ilość
        $output .= '<p class="item-quantity">Ilość: <strong>' . esc_html($item['quantity']) . '</strong></p>';

        // Status wyposażenia
        if ($item['equipped']) {
            $output .= '<p class="item-equipped">Założony: <span class="equipped-yes">Tak</span></p>';
        }

        $output .= '</div>'; // Koniec item-details
        $output .= '</div>'; // Koniec inventory-item
    }

    // Komunikat o braku przedmiotów z danej kategorii
    $output .= '<p class="no-items-message" style="display:none;">Nie posiadasz przedmiotów z tej kategorii.</p>';

    $output .= '</div>'; // Koniec inventory-items-grid
    $output .= '</div>'; // Koniec inventory-content
    $output .= '</div>'; // Koniec inventory-container

    // Dodaj skrypt JavaScript
    $output .= '<script src="' . get_stylesheet_directory_uri() . '/page-templates/author/inventory.js"></script>';

    return $output;
}
