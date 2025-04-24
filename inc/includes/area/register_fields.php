<?php
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
                'key' => 'field_67ffcb196ea22',
                'label' => 'Zdarzenia',
                'name' => '',
                'aria-label' => '',
                'type' => 'accordion',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'open' => 0,
                'multi_expand' => 0,
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_67b0b36aea475',
                'label' => 'Zdarzenia',
                'name' => 'events',
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
                'layout' => 'block',
                'pagination' => 0,
                'min' => 0,
                'max' => 0,
                'collapsed' => '',
                'button_label' => 'Dodaj zdarzenie',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    array(
                        'key' => 'field_67b0b399ea477',
                        'label' => 'Typ zdarzenia',
                        'name' => 'events_type',
                        'aria-label' => '',
                        'type' => 'select',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '33',
                            'class' => '',
                            'id' => '',
                        ),
                        'choices' => array(
                            'event' => 'zdarzenie',
                            'npc' => 'NPC',
                        ),
                        'default_value' => false,
                        'return_format' => 'value',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'ui' => 0,
                        'ajax' => 0,
                        'placeholder' => '',
                        'parent_repeater' => 'field_67b0b36aea475',
                    ),
                    array(
                        'key' => 'field_67b0b40aea479',
                        'label' => 'Zdarzenie',
                        'name' => 'event',
                        'aria-label' => '',
                        'type' => 'post_object',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_67b0b399ea477',
                                    'operator' => '==',
                                    'value' => 'event',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '33',
                            'class' => '',
                            'id' => '',
                        ),
                        'post_type' => array(
                            0 => 'events',
                        ),
                        'post_status' => '',
                        'taxonomy' => '',
                        'return_format' => 'id',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'bidirectional' => 0,
                        'ui' => 1,
                        'bidirectional_target' => array(),
                        'parent_repeater' => 'field_67b0b36aea475',
                    ),
                    array(
                        'key' => 'field_67b0b383ea476',
                        'label' => 'NPC',
                        'name' => 'npc',
                        'aria-label' => '',
                        'type' => 'post_object',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_67b0b399ea477',
                                    'operator' => '==',
                                    'value' => 'npc',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '33',
                            'class' => '',
                            'id' => '',
                        ),
                        'post_type' => array(
                            0 => 'npc',
                        ),
                        'post_status' => '',
                        'taxonomy' => '',
                        'return_format' => 'id',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'bidirectional' => 0,
                        'ui' => 1,
                        'bidirectional_target' => array(),
                        'parent_repeater' => 'field_67b0b36aea475',
                    ),
                    array(
                        'key' => 'field_67b0b3fcea478',
                        'label' => 'Liczba zdarzeń',
                        'name' => 'liczba_zdarzen',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '33',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_67b0b36aea475',
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
