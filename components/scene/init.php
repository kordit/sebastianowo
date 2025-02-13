<?php
function scene_generator()
{
    $post_id = get_the_ID();
    $post_type = get_post_type();
    $post_name = get_post()->post_name;
    $scene_id = get_query_var('scene_id', $post_id);
    $get_scenes = get_field('scenes');
    $i = 1;

    $instance = get_query_var('instance_name');
    $post_title = sanitize_title(get_the_title($post_id));
    if ($instance == 'kreator') {
        $background = 135;
        $selected_paths = [
            [
                'select' => 'npc',
                'npc'    => '142',
                'title'  => 'droga skina',
            ],
            [
                'select' => 'npc',
                'npc'    => '145',
                'title'  => 'droga skejta',
            ],
            [
                'select' => 'npc',
                'npc'    => '143',
                'title'  => 'droga dresa',
            ],

        ];
        $svg_url = 'wp-content/uploads/2025/02/Nowy-projekt-2.svg';
    } elseif ($scene_id == $post_id) {
        if (isset($get_scenes[0])) {
            $get_scenes = $get_scenes[0];
            $background = $get_scenes['tlo'];
            $svg_url = $get_scenes['maska'];
            $path_count = count_svg_paths($svg_url);

            if ($svg_url) {
                $selected_paths = [];
                for ($i = 0; $i < $path_count; $i++) {
                    $select = get_field("field_{$post_title}_scene_0_svg_path_{$i}_select", $post_id);
                    $path_id = get_field("field_{$post_title}_scene_0_svg_path_{$i}_id", $post_id);
                    $npc     = get_field("field_{$post_title}_scene_0_svg_path_{$i}_npc", $post_id);
                    $name    = get_field("field_{$post_title}_scene_0_svg_path_{$i}_name", $post_id);
                    if (!empty($select) || !empty($path_id) || !empty($npc)) {
                        $selected_paths[] = [
                            'select' => $select,
                            'target' => get_site_url() . '/' . $post_type . '/' . $post_name . '/' . $path_id,
                            'npc'    => $npc ?: NULL,
                            'title'  => $name ?: 'brak tytułu',
                        ];
                    }
                }
            }
        }
    } else {
        $found_scene = null;
        $found_index = null;
        foreach ($get_scenes as $index => $scene) {
            if ($scene['id_sceny'] === $scene_id) {
                $found_scene = $scene;
                $found_index = $index;
                break;
            }
        }
        if ($found_scene) {
            $background = $found_scene['tlo'];
            $svg_url = $found_scene['maska'];
            $path_count = count_svg_paths($svg_url);
            if ($svg_url) {
                $selected_paths = [];
                for ($i = 0; $i < $path_count; $i++) {
                    $select = get_field("field_{$post_title}_scene_{$found_index}_svg_path_{$i}_select", $post_id);
                    $path_id = get_field("field_{$post_title}_scene_{$found_index}_svg_path_{$i}_id", $post_id);
                    $npc     = get_field("field_{$post_title}_scene_{$found_index}_svg_path_{$i}_npc", $post_id);
                    $name    = get_field("field_{$post_title}_scene_{$found_index}_svg_path_{$i}_name", $post_id);
                    if (!empty($select) || !empty($path_id) || !empty($npc)) {
                        $selected_paths[] = [
                            'select' => $select,
                            'target' => get_site_url() . '/' . $post_type . '/' . $post_name . '/' . $path_id,
                            'npc'    => $npc ?: NULL,
                            'title'  => $name ?: 'brak tytułu',
                        ];
                    }
                }
            }
        } else {
            // Możesz obsłużyć przypadek, gdy scena o danym id_sceny nie zostanie znaleziona.
        }
    }

    if (isset($background)) {
        echo wp_get_attachment_image($background, 'full');
    }
    if (isset($svg_url)) {
        echo et_svg_with_data($svg_url, $selected_paths);
    }
}
