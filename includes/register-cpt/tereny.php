<?php
function register_cpt_tereny()
{
    register_post_type('tereny', array(
        'labels' => array(
            'name'               => 'Tereny',
            'singular_name'      => 'Teren',
            'menu_name'          => 'Tereny',
            'all_items'          => 'Wszystkie tereny',
            'edit_item'          => 'Edytuj teren',
            'view_item'          => 'Zobacz teren',
            'add_new_item'       => 'Dodaj nowy teren',
            'new_item'           => 'Nowy teren',
            'search_items'       => 'Szukaj terenów',
            'not_found'          => 'Nie znaleziono terenów',
            'not_found_in_trash' => 'Nie znaleziono terenów w koszu',
        ),
        'public'        => true,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'supports'      => array('title', 'thumbnail'), // Wyłączamy "editor", aby Gutenberg nie był używany
        'has_archive'   => true,
        'menu_position' => 18,
        'menu_icon'     => 'dashicons-admin-site',
        'show_in_rest'  => false, // Wyłącza REST API (i tym samym Gutenberg)
    ));
}
add_action('init', 'register_cpt_tereny');
