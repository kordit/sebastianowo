<?php
// Rejestracja Custom Post Type dla misji
add_action('init', function () {
    register_post_type('mission', [
        'labels' => [
            'name'               => 'Misje',
            'singular_name'      => 'Misja',
            'add_new'            => 'Dodaj nową',
            'add_new_item'       => 'Dodaj nową misję',
            'edit_item'          => 'Edytuj misję',
            'new_item'           => 'Nowa misja',
            'view_item'          => 'Zobacz misję',
            'search_items'       => 'Szukaj misji',
            'not_found'          => 'Nie znaleziono misji',
            'not_found_in_trash' => 'Nie znaleziono misji w koszu'
        ],
        'public'              => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-yes-alt',
        'supports'            => ['title', 'editor'],
        'has_archive'         => true,
        'rewrite'             => ['slug' => 'missions'],
        'show_in_rest'        => false,
        'show_in_menu'        => true
    ]);
});

require_once('register_fields.php');
// require_once('admin_viewer.php');
