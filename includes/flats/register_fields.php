<?php
// Rejestracja Custom Post Type: Mieszkanie
add_action('init', function () {
    register_post_type('flat', array(
        'labels' => array(
            'name'                  => 'Mieszkania',
            'singular_name'         => 'Mieszkanie',
            'menu_name'             => 'Mieszkania',
            'all_items'             => 'Wszystkie mieszkania',
            'edit_item'             => 'Edytuj mieszkanie',
            'view_item'             => 'Zobacz mieszkanie',
            'add_new_item'          => 'Dodaj nowe mieszkanie',
            'add_new'               => 'Dodaj nowe',
            'new_item'              => 'Nowe mieszkanie',
            'search_items'          => 'Szukaj mieszkań',
            'not_found'             => 'Nie znaleziono żadnych mieszkań',
            'not_found_in_trash'    => 'Nie znaleziono żadnych mieszkań w koszu',
        ),
        'public'              => true,
        'has_archive'         => true,
        'rewrite'             => array('slug' => 'mieszkania'),
        'menu_icon'           => 'dashicons-admin-home', // lub użyj własnego SVG
        'supports'            => array('title', 'editor', 'author', 'thumbnail'),
        'show_in_rest'        => false,
    ));
});

// Rejestracja ACF Field Group dla CPT "flat"
add_action('acf/include_fields', function () {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key'                   => 'flat_osiedle_single',
        'title'                 => 'Osiedle - Single',
        'fields'                => array(),
        'location' => array(
            array(
                array(
                    'param'     => 'post_type',
                    'operator'  => '==',
                    'value'     => 'flat',
                ),
            ),
        ),
        'menu_order'          => 0,
        'position'            => 'normal',
        'style'               => 'default',
        'label_placement'     => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen'      => array('the_content', 'excerpt', 'comments'),
        'active'              => true,
        'description'         => '',
        'show_in_rest'        => 0,
    ));
});
