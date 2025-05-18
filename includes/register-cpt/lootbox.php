<?php
// Rejestracja CPT "lootbox" dla gry
add_action('init', 'register_cpt_lootbox');
function register_cpt_lootbox()
{
    $labels = array(
        'name' => 'Lootboxy',
        'singular_name' => 'Lootbox',
        'add_new' => 'Dodaj nowy',
        'add_new_item' => 'Dodaj nowy lootbox',
        'edit_item' => 'Edytuj lootbox',
        'new_item' => 'Nowy lootbox',
        'view_item' => 'Zobacz lootbox',
        'search_items' => 'Szukaj lootboxów',
        'not_found' => 'Nie znaleziono lootboxów',
        'not_found_in_trash' => 'Brak lootboxów w koszu',
        'menu_name' => 'Lootboxy',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-archive',
        'supports' => array('title', 'thumbnail'),
        'show_in_rest' => false,
    );

    register_post_type('lootbox', $args);
}
