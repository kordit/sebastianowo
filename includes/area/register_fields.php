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
                'key' => 'field_67acfbf9a67a9',
                'label' => 'npc',
                'name' => 'npc',
                'aria-label' => '',
                'type' => 'relationship',
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
                'post_status' => array(
                    0 => 'publish',
                ),
                'taxonomy' => '',
                'filters' => array(
                    0 => 'search',
                ),
                'return_format' => 'object',
                'min' => '',
                'max' => '',
                'elements' => array(
                    0 => 'featured_image',
                ),
                'bidirectional' => 0,
            ),
            array(
                'key' => 'field_67acf8e748137',
                'label' => 'Sceny',
                'name' => 'scenes',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'table',
                'pagination' => 0,
                'min' => 0,
                'max' => 0,
                'collapsed' => '',
                'button_label' => 'Dodaj scenę',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    array(
                        'key' => 'field_67acf8f448138',
                        'label' => 'Tło',
                        'name' => 'tlo',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'id',
                        'library' => 'all',
                        'preview_size' => 'medium',
                        'parent_repeater' => 'field_67acf8e748137',
                    ),
                    array(
                        'key' => 'field_67acf8fc48139',
                        'label' => 'Maska',
                        'name' => 'maska',
                        'type' => 'image',
                        'required' => 1,
                        'conditional_logic' => 1,
                        'return_format' => 'url',
                        'mime_types' => 'svg',
                        'preview_size' => 'medium',
                    ),
                    array(
                        'key' => 'field_id_sceny',
                        'label' => 'ID sceny',
                        'name' => 'id_sceny',
                        'type' => 'text',
                    ),
                ),
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


add_action('init', function () {
    if (!is_admin() || !function_exists('acf_add_local_field_group')) {
        return;
    }

    $custom_post_types = get_post_types(['_builtin' => false]);

    foreach ($custom_post_types as $cpt) {
        $posts = get_posts([
            'post_type'      => $cpt,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ]);

        foreach ($posts as $post) {
            $post_id    = $post->ID;
            $post_title = sanitize_title($post->post_title);
            $scenes     = get_field('scenes', $post_id);
            if (!$scenes) {
                continue;
            }
            foreach ($scenes as $scene_index => $scene) {
                if (empty($scene['maska'])) {
                    continue;
                }
                $maska     = $scene['maska'];
                $maska_url = is_array($maska) && isset($maska['url']) ? $maska['url'] : $maska;
                $path_count = count_svg_paths($maska_url);
                if ($path_count === 0) {
                    continue;
                }
                for ($i = 0; $i < $path_count; $i++) {
                    acf_add_local_field_group([
                        'key'        => "group_{$post_title}_scene_{$scene_index}_svg_path_{$i}",
                        'title' => "Scena " . ($scene_index + 1) . " Path " . ($i + 1),
                        'fields'     => [
                            array(
                                'key'   => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_name",
                                'label' => 'Nazwa pola',
                                'name' => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_name",
                                'aria-label' => '',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 1,
                                'conditional_logic' => 1,
                                'default_value' => false,
                                'return_format' => 'array',
                                'multiple' => 0,
                                'allow_null' => 1,
                                'ui' => 0,
                                'ajax' => 0,
                                'placeholder' => '',
                                'wrapper' => array(
                                    'width' => '33',
                                    'class' => '',
                                    'id' => '',
                                ),
                            ),
                            array(
                                'key'   => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_select",
                                'label' => 'Wybierz aktywator',
                                'name' => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_select",
                                'aria-label' => '',
                                'type' => 'select',
                                'instructions' => '',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'choices' => array(
                                    'npc' => 'npc',
                                    'scena' => 'scena',
                                ),
                                'default_value' => false,
                                'return_format' => 'array',
                                'multiple' => 0,
                                'allow_null' => 1,
                                'ui' => 0,
                                'ajax' => 0,
                                'placeholder' => '',
                                'wrapper' => array(
                                    'width' => '33',
                                    'class' => '',
                                    'id' => '',
                                ),
                            ),
                            array(
                                'key'   => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_id",
                                'label' => 'ID',
                                'name' => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_id",
                                'aria-label' => '',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '33',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'maxlength' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field'    => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_select",
                                            'operator' => '==',
                                            'value'    => 'scena',
                                        ),
                                    ),
                                ),
                            ),
                            array(
                                'key'   => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_npc",
                                'label' => 'Npc do sceny',
                                'name' => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_npc",
                                'type' => 'post_object',
                                'return_format' => 'object',
                                'post_type' => array('npc'),
                                'allow_null' => 1,
                                'wrapper' => array(
                                    'width' => '33',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field'    => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_select",
                                            'operator' => '==',
                                            'value'    => 'npc',
                                        ),
                                    ),
                                ),
                            ),








                        ],
                        'location'   => [
                            [
                                [
                                    'param'    => 'post',
                                    'operator' => '==',
                                    'value'    => $post_id,
                                ],
                            ],
                        ],
                        // 'position'   => 'side',
                        'style'      => 'default',
                        'menu_order' => 99,
                    ]);
                }
            }
        }
    }
});
