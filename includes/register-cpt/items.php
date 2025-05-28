<?php
add_action('init', function () {
    register_post_type('item', array(
        'labels' => array(
            'name'                  => 'Przedmioty',
            'singular_name'         => 'Przedmiot',
            'menu_name'             => 'Przedmioty',
            'all_items'             => 'Wszystkie przedmioty',
            'edit_item'             => 'Edytuj przedmiot',
            'view_item'             => 'Zobacz przedmiot',
            'add_new_item'          => 'Dodaj nowy przedmiot',
            'add_new'               => 'Dodaj nowy',
            'new_item'              => 'Nowy przedmiot',
            'search_items'          => 'Szukaj przedmiotów',
            'not_found'             => 'Nie znaleziono żadnych przedmiotów',
            'not_found_in_trash'    => 'Nie znaleziono żadnych przedmiotów w koszu',
        ),
        'public'              => false,
        'has_archive'         => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'rewrite'             => false,
        'menu_icon'           => 'dashicons-archive',
        'supports'            => array('title', 'thumbnail'),
        'show_in_rest'        => false,
    ));

    // Rejestracja taksonomii dla typów przedmiotów
    register_taxonomy('item_type', 'item', array(
        'labels' => array(
            'name'              => 'Typy przedmiotów',
            'singular_name'     => 'Typ przedmiotu',
            'search_items'      => 'Szukaj typów przedmiotów',
            'all_items'         => 'Wszystkie typy przedmiotów',
            'edit_item'         => 'Edytuj typ przedmiotu',
            'update_item'       => 'Aktualizuj typ przedmiotu',
            'add_new_item'      => 'Dodaj nowy typ przedmiotu',
            'new_item_name'     => 'Nowy typ przedmiotu',
            'menu_name'         => 'Typy przedmiotów',
        ),
        'public'            => false,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_ui'           => true,
        'rewrite'           => false,
    ));
});
