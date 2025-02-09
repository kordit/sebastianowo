<?php
// Rejestracja CPT "tereny" z wyłączonym Gutenbergem
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
        'menu_position' => 5,
        'menu_icon'     => 'dashicons-admin-site',
        'show_in_rest'  => false, // Wyłącza REST API (i tym samym Gutenberg)
    ));
}
add_action('init', 'register_cpt_tereny');

// Rejestracja pól ACF dla CPT "tereny"
if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group(array(
        'key' => 'group_tereny',
        'title' => 'Dane Terenów',
        'fields' => array(
            array(
                'key' => 'field_teren_opis',
                'label' => 'Opis Terenu',
                'name' => 'teren_opis',
                'type' => 'textarea',
                'new_lines' => 'br',
            ),
            array(
                'key' => 'siedziba_grupy',
                'label' => 'Siedziba grupy',
                'name' => 'siedziba_grupy',
                'type' => 'post_object',
                'return_format' => 'object',
                'post_type' => array('group'),
                'allow_null' => 1,
                'bidirectional_target' => array('teren_grupy'),
            ),
            array(
                'key' => 'field_6793e6a783c33_area',
                'label' => 'Złoto',
                'name' => 'area_cost_gold',
                'type' => 'number',
                'default_value' => '0',
                'min' => 0,
                'wrapper' => array(
                    'width' => '25',
                ),
            ),
            array(
                'key' => 'field_6793e69b83c32_area',
                'label' => 'Żelazo',
                'name' => 'area_cost_iron',
                'type' => 'number',
                'default_value' => '0',
                'min' => 0,
                'wrapper' => array(
                    'width' => '25',
                ),
            ),
            array(
                'key' => 'field_6793e6bc83c36_area',
                'label' => 'Kamień',
                'name' => 'area_cost_stone',
                'type' => 'number',
                'default_value' => '0',
                'min' => 0,
                'wrapper' => array(
                    'width' => '25',
                ),
            ),
            array(
                'key' => 'field_6793e6c283c37_area',
                'label' => 'Drewno',
                'name' => 'area_cost_wood',
                'type' => 'number',
                'default_value' => '0',
                'min' => 0,
                'wrapper' => array(
                    'width' => '25',
                ),
            ),
            array(
                'key' => 'field_teren_zdjecie',
                'label' => 'Zdjęcie',
                'name' => 'teren_zdjecie',
                'type' => 'image',
                'return_format' => 'id',
                'preview_size' => 'medium',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'tereny',
                ),
            ),
        ),
        'hide_on_screen' => array('the_content'),
        'active' => true,
        'show_in_rest' => 1,
    ));
}


function count_svg_paths($url)
{
    if (is_admin()) {
        $url = preg_replace('/https?\:\/\/[^\/]*\//', '', '../' . $url);
    } else {
        $url = preg_replace('/http?\:\/\/[^\/]*\//', '', $url);
    }
    $count = new SimpleXMLElement($url, 0, TRUE);
    $mycount = count($count->children());
    $mycount = count($count->path);
    $mycount += count($count->polygon);
    return $mycount;
}

if (function_exists('acf_add_options_page')) {
    acf_add_options_page([
        'page_title'  => 'SVG Paths Settings',
        'menu_title'  => 'SVG Paths',
        'menu_slug'   => 'theme-svg-options',
        'capability'  => 'edit_theme_options',
        'redirect'    => false,
    ]);
}


add_action('acf/init', function () {
    if (function_exists('acf_add_local_field_group')) {
        $path_count = count_svg_paths(SVG . 'map-2.svg');

        $fields = [];
        for ($i = 0; $i < $path_count; $i++) {
            $fields[] = [
                'key' => 'field_svg_path_' . $i,
                'label' => 'SVG Path ' . ($i + 1),
                'name' => 'svg_path_' . $i,
                'type' => 'post_object',
                'return_format' => 'object',
                'post_type' => array(
                    0 => 'tereny',
                ),
                'allow_null' => 1,

            ];
        }

        acf_add_local_field_group([
            'key' => 'group_svg_paths',
            'title' => 'SVG Paths Settings',
            'fields' => $fields,


            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'theme-svg-options',
                    ],
                ],
            ],
        ]);
    }
});
