<?php
function getRelationColor($relation)
{
    // Ograniczenie wartości do zakresu -100 do 100
    $relation = max(-100, min(100, $relation));

    // Przeliczamy wartość na zakres 0-1
    $normalized = ($relation + 100) / 200;

    // Interpolacja kolorów (czerwony -> żółty -> zielony)
    $r = (1 - $normalized) * 255; // Od czerwonego do zielonego
    $g = ($normalized) * 255; // Od żółtego do zielonego
    $b = 0; // Brak niebieskiego

    return sprintf("#%02X%02X%02X", $r, $g, $b);
}

function scene_generator()
{
    $post_id = get_the_ID();
    $post_type = get_post_type();
    $post_name = get_post()->post_name;
    $scene_id = get_query_var('scene_id', $post_id);
    $get_scenes = get_field('scenes');
    $i = 1;
    $current_user_id = get_current_user_id();

    $instance = get_query_var('instance_name');
    $post_title = sanitize_title(get_the_title($post_id));
    if ($scene_id == $post_id) {
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
                    $link    = get_field("field_{$post_title}_scene_0_svg_path_{$i}_page", $post_id);
                    $relation = $current_user_id;

                    if ($select == 'scena') {
                        $color = '#fff';
                    } elseif ($select == 'page') {
                        $color = '#fff';
                    } elseif ($select == 'npc') {
                        $color = get_field('npc-relation-user-' . $current_user_id, $npc);
                        $color = getRelationColor($color);
                    } else {
                        $color = '#000';
                    }
                    if (isset($link['url'])) {
                        $link = $link['url'];
                    }
                    if (!empty($select) || !empty($path_id) || !empty($npc) || !empty($name)) {
                        $selected_paths[] = [
                            'select' => $select,
                            'target' => get_site_url() . '/' . $post_type . '/' . $post_name . '/' . $path_id,
                            'npc'    => $npc ?: NULL,
                            'page'  => $link ?: '',
                            'title'  => $name ?: 'brak tytułu',
                            'color'  => $color,
                            'npc-name'    => get_the_title($npc) ?: NULL,
                            'relation' => $relation,
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
                    $link    = get_field("field_{$post_title}_scene_{$found_index}_svg_path_{$i}_page", $post_id) ?: '';

                    if ($select == 'scena') {
                        $color = '#fff';
                    } elseif ($select == 'page') {
                        $color = '#fff';
                    } elseif ($select == 'npc') {
                        $color = get_field('npc-relation-user-' . $current_user_id, $npc);
                        $color = getRelationColor($color);
                    } else {
                        $color = '#000';
                    }
                    if (isset($link['url'])) {
                        $link = $link['url'];
                    }

                    if (!empty($select) || !empty($path_id) || !empty($npc) || !empty($name)) {
                        $selected_paths[] = [
                            'select' => $select,
                            'target' => get_site_url() . '/' . $post_type . '/' . $post_name . '/' . $path_id,
                            'npc'    => $npc ?: NULL,
                            'title'  => $name ?: 'brak tytułu',
                            'page'  => $link,
                            'npc-name'    => get_the_title($npc) ?: NULL,
                            'color'  => $color,
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
