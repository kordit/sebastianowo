<?php
// Rejestracja Custom Post Type: Grupa
add_action('init', function () {
    register_post_type('group', array(
        'labels' => array(
            'name'                  => 'Grupy',
            'singular_name'         => 'Grupa',
            'menu_name'             => 'Grupy',
            'all_items'             => 'Wszystkie grupy',
            'edit_item'             => 'Edytuj grupę',
            'view_item'             => 'Zobacz grupę',
            'add_new_item'          => 'Dodaj nową grupę',
            'add_new'               => 'Dodaj nową',
            'new_item'              => 'Nowa grupa',
            'search_items'          => 'Szukaj grup',
            'not_found'             => 'Nie znaleziono żadnych grup',
            'not_found_in_trash'    => 'Nie znaleziono żadnych grup w koszu',
        ),
        'public'              => true,
        'has_archive'         => true,
        'rewrite'             => array('slug' => 'grupy'),
        'menu_icon'           => 'dashicons-groups', // lub użyj własnego SVG
        'supports'            => array('title', 'editor', 'author', 'thumbnail'),
        'show_in_rest'        => false,
    ));
});

// Rejestracja ACF Field Group dla CPT "group"
add_action('acf/include_fields', function () {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key'                   => 'group_osiedle_single',
        'title'                 => 'Osiedle - Single',
        'fields'                => array(
            array(
                'key'               => 'field_description_village',
                'label'             => 'Opis',
                'name'              => 'description_village',
                'type'              => 'text',
                'default_value'     => 'Tutaj opis wioski',
            ),
            array(
                'key'               => 'field_the_villagers',
                'label'             => 'Członkowie grupy',
                'name'              => 'the_villagers',
                'type'              => 'user',
                'return_format'     => 'array',
                'multiple'        => 1,
                'allow_null'      => 1,
                'bidirectional'   => 1,
            ),
            array(
                'key'               => 'field_applications',
                'label'             => 'Aplikacje',
                'name'              => 'applications',
                'type'              => 'user',
                'return_format'     => 'array',
                'multiple'        => 1,
                'allow_null'      => 1,
                'bidirectional'   => 1,
            ),
            array(
                'key'               => 'field_teren_grupy',
                'label'             => 'Lokalizacja',
                'name'              => 'teren_grupy',
                'type'              => 'post_object',
                'return_format'     => 'object',
                'multiple'        => 1,
                'post_type'         => array('tereny'),
                'allow_null'      => 1,
                'bidirectional_target' => array('siedziba_grupy'),
            ),
            array(
                'key'               => 'field_leader',
                'label'             => 'Lider',
                'name'              => 'leader',
                'type'              => 'user',
                'return_format'     => 'array',
                'multiple'        => 0,
                'allow_null'      => 1,
                'bidirectional'   => 1,
            ),
            array(
                'key'               => 'field_color_district',
                'label'             => 'Kolor osiedla',
                'name'              => 'color_district',
                'type'              => 'color_picker',
                'default_value'     => '',
                'enable_opacity'    => 0,
                'return_format'     => 'string',
            ),
            array(
                'key' => 'field_67abab3abfd91',
                'label' => 'NPC',
                'name' => 'npc',
                'aria-label' => '',
                'type' => 'post_object',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'post_type' => array(
                    0 => 'npc',
                ),
                'post_status' => '',
                'taxonomy' => '',
                'return_format' => 'object',
                'multiple' => 1,
                'allow_null' => 0,
                'bidirectional' => 0,
                'ui' => 1,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'     => 'post_type',
                    'operator'  => '==',
                    'value'     => 'group',
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


function acf_sync_group_teren($value, $post_id, $field)
{
    if (!is_array($value)) {
        $value = !empty($value) ? array($value) : [];
    }

    $old_values = get_field('teren_grupy', $post_id, false);
    if (!is_array($old_values)) {
        $old_values = !empty($old_values) ? array($old_values) : [];
    }

    // Pobranie terenów do usunięcia (stare - nowe)
    $to_remove = array_diff($old_values, $value);
    // Pobranie terenów do dodania (nowe - stare)
    $to_add = array_diff($value, $old_values);

    // Usunięcie powiązań w terenach, które już nie należą do wioski
    foreach ($to_remove as $old_teren_id) {
        $current_tereny = get_field('siedziba_grupy', $old_teren_id, false);
        if (is_array($current_tereny)) {
            $new_tereny = array_diff($current_tereny, [$post_id]);
            remove_filter('acf/update_value/name=siedziba_grupy', 'acf_sync_teren_wioska', 10);
            update_field('siedziba_grupy', empty($new_tereny) ? null : array_values($new_tereny), $old_teren_id);
            add_filter('acf/update_value/name=siedziba_grupy', 'acf_sync_teren_wioska', 10, 3);
        }
    }

    // Dodanie nowych powiązań w terenach
    foreach ($to_add as $teren_id) {
        $current_tereny = get_field('siedziba_grupy', $teren_id, false);
        if (!is_array($current_tereny)) {
            $current_tereny = !empty($current_tereny) ? array($current_tereny) : [];
        }

        if (!in_array($post_id, $current_tereny)) {
            $current_tereny[] = $post_id;
            remove_filter('acf/update_value/name=siedziba_grupy', 'acf_sync_teren_wioska', 10);
            update_field('siedziba_grupy', $current_tereny, $teren_id);
            add_filter('acf/update_value/name=siedziba_grupy', 'acf_sync_teren_wioska', 10, 3);
        }
    }

    return $value;
}

function acf_sync_teren_wioska($value, $post_id, $field)
{
    if (!is_array($value)) {
        $value = !empty($value) ? array($value) : [];
    }

    $old_values = get_field('siedziba_grupy', $post_id, false);
    if (!is_array($old_values)) {
        $old_values = !empty($old_values) ? array($old_values) : [];
    }

    // Pobranie wiosek do usunięcia (stare - nowe)
    $to_remove = array_diff($old_values, $value);
    // Pobranie wiosek do dodania (nowe - stare)
    $to_add = array_diff($value, $old_values);

    // Usunięcie powiązań w wioskach, które już nie należą do terenu
    foreach ($to_remove as $old_wioska_id) {
        $current_wioski = get_field('teren_grupy', $old_wioska_id, false);
        if (is_array($current_wioski)) {
            $new_wioski = array_diff($current_wioski, [$post_id]);
            remove_filter('acf/update_value/name=teren_grupy', 'acf_sync_group_teren', 10);
            update_field('teren_grupy', empty($new_wioski) ? null : array_values($new_wioski), $old_wioska_id);
            add_filter('acf/update_value/name=teren_grupy', 'acf_sync_group_teren', 10, 3);
        }
    }

    // Dodanie nowych powiązań w wioskach
    foreach ($to_add as $wioska_id) {
        $current_wioski = get_field('teren_grupy', $wioska_id, false);
        if (!is_array($current_wioski)) {
            $current_wioski = !empty($current_wioski) ? array($current_wioski) : [];
        }

        if (!in_array($post_id, $current_wioski)) {
            $current_wioski[] = $post_id;
            remove_filter('acf/update_value/name=teren_grupy', 'acf_sync_group_teren', 10);
            update_field('teren_grupy', $current_wioski, $wioska_id);
            add_filter('acf/update_value/name=teren_grupy', 'acf_sync_group_teren', 10, 3);
        }
    }

    return $value;
}

add_filter('acf/update_value/name=teren_grupy', 'acf_sync_group_teren', 10, 3);
add_filter('acf/update_value/name=siedziba_grupy', 'acf_sync_teren_wioska', 10, 3);
