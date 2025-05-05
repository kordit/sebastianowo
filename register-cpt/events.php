<?php
add_action('init', function () {
    register_post_type('events', array(
        'labels' => array(
            'name'                  => 'Zdarzenia',
            'singular_name'         => 'Zdarzenie',
            'menu_name'             => 'Zdarzenia',
            'all_items'             => 'Wszystkie zdarzenia',
            'edit_item'             => 'Edytuj zdarzenie',
            'view_item'             => 'Zobacz zdarzenie',
            'add_new_item'          => 'Dodaj nowe zdarzenie',
            'add_new'               => 'Dodaj nowe',
            'new_item'              => 'Nowe zdarzenie',
            'search_items'          => 'Szukaj zdarzeń',
            'not_found'             => 'Nie znaleziono żadnych zdarzeń',
            'not_found_in_trash'    => 'Nie znaleziono żadnych zdarzeń w koszu',
        ),
        'public'              => true,
        'has_archive'         => false,
        'rewrite'             => array('slug' => 'zdarzenia'),
        'menu_icon'           => 'dashicons-calendar-alt',
        'supports'            => array('title', 'thumbnail'),
        'show_in_rest'        => false,
    ));
    register_taxonomy('event_location', 'events', array(
        'labels' => array(
            'name'              => 'Lokalizacje',
            'singular_name'     => 'Lokalizacja',
            'search_items'      => 'Szukaj lokalizacji',
            'all_items'         => 'Wszystkie lokalizacje',
            'edit_item'         => 'Edytuj lokalizację',
            'update_item'       => 'Aktualizuj lokalizację',
            'add_new_item'      => 'Dodaj nową lokalizację',
            'new_item_name'     => 'Nowa lokalizacja',
            'menu_name'         => 'Lokalizacja',
        ),
        'public'            => true,
        'hierarchical'      => false,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'lokalizacja'),
    ));
});
