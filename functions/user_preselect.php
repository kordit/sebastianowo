<?php
// add_action('user_register', function ($user_id) {
//     update_field('avatar', 86, 'user_' . $user_id);
//     // update_field('village', 73, 'user_' . $user_id);
// });
add_action('acf/include_fields', function () {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_67aa20e8d3095',
        'title' => 'Świat gry',
        'fields' => array(
            array(
                'key' => 'field_67acf8e748137',
                'label' => 'Sceny',
                'name' => 'scenes',
                'type' => 'repeater',
                'layout' => 'table',
                'pagination' => 0,
                'button_label' => 'Dodaj scenę',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    array(
                        'key' => 'field_67acf8f448138',
                        'label' => 'Tło',
                        'name' => 'tlo',
                        'type' => 'image',
                        'required' => 1,
                        'return_format' => 'id',
                        'preview_size' => 'medium',
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
                    'operator' => '!=',
                    'value' => 'post',
                ),
                array(
                    'param' => 'post_type',
                    'operator' => '!=',
                    'value' => 'npc',
                ),
            ),
        ),
        'menu_order' => 0,
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ));
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
                                    'npc' => 'NPC',
                                    'scena' => 'Scena',
                                    'page' => 'Przekieruj',
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
                                'key'   => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_page",
                                'label' => 'Link',
                                'name' => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_page",
                                'type' => 'link',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '33',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'return_format' => 'url',
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field'    => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_select",
                                            'operator' => '==',
                                            'value'    => 'page',
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
