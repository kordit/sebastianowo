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


function et_svg_with_one_path($linksvg, $visibleIndex = 0)
{
    $localPath = $linksvg;
    libxml_use_internal_errors(true);

    $dom = new DOMDocument('1.0', 'UTF-8');
    if (!$dom->load($localPath)) {
        return '';
    }

    // Dodajemy klasy "current"/"nocurrent" do path
    $paths = $dom->getElementsByTagName('path');
    if ($paths->length === 0) {
        return $dom->saveXML();
    }
    foreach ($paths as $i => $path) {
        $oldClass = $path->getAttribute('class');
        if ($i === $visibleIndex) {
            $path->setAttribute('class', trim($oldClass . ' current'));
        } else {
            $path->setAttribute('class', trim($oldClass . ' nocurrent'));
        }
    }

    // Dodajemy <style> z regułami dla current i nocurrent
    // w głównym znaczniku <svg>
    $svgs = $dom->getElementsByTagName('svg');
    if ($svgs->length > 0) {
        $svgTag = $svgs->item(0);

        // Dodajemy szerokość 200px (opcjonalnie usuwamy height)
        $svgTag->setAttribute('width', '200');
        if ($svgTag->hasAttribute('height')) {
            $svgTag->removeAttribute('height');
        }
    }

    return $dom->saveXML();
}

function dodaj_style_do_admina()
{
    echo '<style>
        svg path.nocurrent {
            opacity: 0.2;
        }
        svg path.current {
            opacity: 1;
        }
    </style>';
}
add_action('admin_head', 'dodaj_style_do_admina');


function acf_add_allowed_svg_tag($tags, $context)
{
    if ($context === 'acf') {
        $tags['svg'] = array(
            'xmlns'                => true,
            'width'                => true,
            'height'               => true,
            'preserveAspectRatio'  => true,
            'fill'                 => true,
            'viewbox'              => true,
            'role'                 => true,
            'aria-hidden'          => true,
            'focusable'            => true,
        );
        $tags['path'] = array(
            'd'      => true,
            'fill'   => true,
            'style'  => true, // <-- DODAJ to
            'class'  => true, // <-- DODAJ (jeśli potrzebujesz)
            'id'     => true, // <-- DODAJ (jeśli potrzebujesz)
        );
        // Możesz też dodać 'g' => ['style' => true], itd. jeśli potrzebujesz
    }

    return $tags;
}
add_filter('wp_kses_allowed_html', 'acf_add_allowed_svg_tag', 10, 2);



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

                $maska      = $scene['maska'];
                $maska_url  = is_array($maska) && isset($maska['url']) ? $maska['url'] : $maska;
                $path_count = count_svg_paths($maska_url);

                if ($path_count === 0) {
                    continue;
                }

                $all_path_fields = [];

                for ($i = 0; $i < $path_count; $i++) {
                    // Dodajemy zakładkę przed każdym zestawem pól dla kolejnego path,
                    // tak by się nie „gryzła” z pozostałymi polami i nic nie znikało.
                    $all_path_fields[] = array(
                        'key'               => "tab_{$post_title}_scene_{$scene_index}_svg_path_{$i}",
                        'label'             => 'Obszar ' . ($i + 1),
                        'name'              => '',
                        'type'              => 'tab',
                        'instructions'      => '',
                        'required'          => 0,
                        'placement' => 'top',
                        'endpoint'  => 0,
                    );

                    $preview_html = et_svg_with_one_path($maska_url, $i);
                    $all_path_fields[] = array(
                        'key'        => "field_preview_{$post_title}_scene_{$scene_index}_svg_path_{$i}",
                        'label'      => 'Podgląd obszaru ' . ($i + 1),
                        'name'       => '',
                        'type'       => 'message',
                        'message'    => $preview_html ?: '',
                        'esc_html'   => 0,
                        'new_lines'  => '',
                        'formatting' => 'none',
                        'wrapper' => array(
                            'width' => '25',
                        ),
                    );

                    $all_path_fields[] = array(
                        'key'   => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_name",
                        'label' => 'Nazwa pola',
                        'name'  => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_name",
                        'type'  => 'text',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'default_value' => false,
                        'return_format' => 'array',
                        'multiple' => 0,
                        'allow_null' => 1,
                        'ui' => 0,
                        'ajax' => 0,
                        'wrapper' => array(
                            'width' => '25',
                        ),
                    );

                    $all_path_fields[] = array(
                        'key'   => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_select",
                        'label' => 'Wybierz aktywator',
                        'name'  => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_select",
                        'type'  => 'select',
                        'required' => 1,
                        'choices' => array(
                            'npc'   => 'NPC',
                            'scena' => 'Scena',
                            'page'  => 'Przekieruj',
                        ),
                        'default_value' => false,
                        'allow_null' => 1,
                        'wrapper' => array(
                            'width' => '25',
                        ),
                    );

                    $all_path_fields[] = array(
                        'key'   => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_id",
                        'label' => 'ID',
                        'name'  => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_id",
                        'type'  => 'text',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_select",
                                    'operator' => '==',
                                    'value'    => 'scena',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '25',
                        ),
                    );

                    $all_path_fields[] = array(
                        'key'   => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_page",
                        'label' => 'Link',
                        'name'  => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_page",
                        'type'  => 'link',
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
                        'wrapper' => array(
                            'width' => '25',
                        ),
                    );

                    $all_path_fields[] = array(
                        'key'   => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_npc",
                        'label' => 'Npc do sceny',
                        'name'  => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_npc",
                        'type'  => 'post_object',
                        'return_format' => 'object',
                        'post_type' => array('npc'),
                        'allow_null' => 1,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => "field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_select",
                                    'operator' => '==',
                                    'value'    => 'npc',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '25',
                        ),
                    );
                }

                acf_add_local_field_group([
                    'key'        => "group_{$post_title}_scene_{$scene_index}",
                    'title'      => isset($scene['id_sceny']) && $scene['id_sceny']
                        ? 'Scena: ' . $scene['id_sceny']
                        : 'Scena ' . ($scene_index + 1),
                    'fields'     => $all_path_fields,
                    'location'   => [
                        [
                            [
                                'param'    => 'post',
                                'operator' => '==',
                                'value'    => $post_id,
                            ],
                        ],
                    ],
                    'style'      => 'default',
                    'menu_order' => 99,
                ]);
            }
        }
    }
});
