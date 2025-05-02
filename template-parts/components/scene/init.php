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

/**
 * Przetwarza ścieżki SVG i zwraca dane potrzebne do renderowania
 * 
 * @param string $svg_url URL pliku SVG
 * @param int $post_id ID aktualnego posta
 * @param string $post_title Tytuł posta (sanityzowany)
 * @param int $scene_index Indeks sceny (0 dla pierwszej sceny lub indeks z tablicy dla pozostałych)
 * @param int $current_user_id ID bieżącego użytkownika
 * @return array Tablica z danymi ścieżek
 */
function process_svg_paths($svg_url, $post_id, $post_title, $scene_index, $current_user_id, $post_type, $post_name, $autostart = false)
{
    if (!$svg_url) {
        return [];
    }

    $path_count = count_svg_paths($svg_url);
    $selected_paths = [];

    for ($i = 0; $i < $path_count; $i++) {
        $select = get_field("field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_select", $post_id);
        $path_id = get_field("field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_id", $post_id);
        $npc     = get_field("field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_npc", $post_id);
        $name    = get_field("field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_name", $post_id);
        $link    = get_field("field_{$post_title}_scene_{$scene_index}_svg_path_{$i}_page", $post_id) ?: '';

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
            $path_data = [
                'select' => $select,
                'target' => get_site_url() . '/' . $post_type . '/' . $post_name . '/' . $path_id,
                'npc'    => $npc ?: NULL,
                'page'   => $link,
                'title'  => $name ?: 'brak tytułu',
                'color'  => $color,
                'npc-name' => get_the_title($npc) ?: NULL,
                'autostart' => $autostart,
            ];

            // Dodaj pole relacji tylko dla głównej sceny (indeks 0)
            if ($scene_index === 0) {
                $path_data['relation'] = $current_user_id;
            }

            $selected_paths[] = $path_data;
        }
    }

    return $selected_paths;
}

function scene_generator()
{
    $post_id = get_the_ID();
    $post_type = get_post_type();

    $post_obj = get_post();
    $post_name = $post_obj ? $post_obj->post_name : '';

    $scene_id = get_query_var('scene_id', $post_id);
    $get_scenes = get_field('scenes');
    $current_user_id = get_current_user_id();

    $instance = get_query_var('instance_name');
    $post_title = sanitize_title(get_the_title($post_id));

    if (isset($_GET['spacer']) && $_GET['spacer'] === 'true') {
        $spacer_data = handle_spacer_parameter($post_id);

        if ($spacer_data) {
            $background = $spacer_data['background'];
            $svg_url = $spacer_data['svg_url'];
            $selected_paths = $spacer_data['selected_paths'];
        }
    } elseif ($scene_id == $post_id) {
        // Obsługa pierwszej sceny
        if (isset($get_scenes[0])) {
            $scene = $get_scenes[0];
            $background = $scene['tlo'];
            $svg_url = $scene['maska'];
            $selected_paths = process_svg_paths($svg_url, $post_id, $post_title, 0, $current_user_id, $post_type, $post_name);
        }
    } else {
        // Obsługa innych scen
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

            $selected_paths = process_svg_paths(
                $svg_url,
                $post_id,
                $post_title,
                $found_index,
                $current_user_id,
                $post_type,
                $post_name,
            );
        } else {
            // Możesz obsłużyć przypadek, gdy scena o danym id_sceny nie zostanie znaleziona.
        }
    }

    // Wyświetlanie tła i SVG
    if (isset($background)) {
        echo wp_get_attachment_image($background, 'full');
    }
    if (isset($svg_url) && isset($selected_paths)) {
        echo et_svg_with_data($svg_url, $selected_paths);
    }
}
