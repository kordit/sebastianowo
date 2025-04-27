<?php

/**
 * Funkcje obsługujące mechanizm spaceru po terenie
 */

/**
 * Obsługuje losowanie sceny dla parametru spacer
 * 
 * @return array Tablica z danymi sceny lub false w przypadku braku sceny
 */
function handle_spacer_parameter($post_id)
{
    $post_id = get_the_ID();
    $post_type = get_post_type();
    $post_name = get_post()->post_name;
    $get_scenes = get_field('events', $post_id);
    $current_user_id = get_current_user_id();
    $post_title = sanitize_title(get_the_title($post_id));

    if (empty($get_scenes)) {
        return false;
    }

    $expanded = [];

    foreach ($get_scenes as $scene) {
        if (isset($scene['event'], $scene['liczba_zdarzen'])) {
            for ($i = 0; $i < (int) $scene['liczba_zdarzen']; $i++) {
                $expanded[] = (int) $scene['event'];
            }
        }
    }

    if (empty($expanded)) {
        return false;
    }

    $random_key = array_rand($expanded);
    $event_id = $expanded[$random_key];

    // Pobierz dane sceny z losowo wybranego wydarzenia
    $event_scenes = get_field('scenes', $event_id);
    if (!isset($event_scenes[0])) {
        return false;
    }

    $scene = $event_scenes[0];
    $background = isset($scene['tlo']) ? $scene['tlo'] : null;
    $svg_url = isset($scene['maska']) ? $scene['maska'] : null;
    $autostart = isset($scene['autostart']) ? $scene['autostart'] : null;


    // Przetwarzaj ścieżki SVG tylko jeśli URL maski jest dostępny
    $selected_paths = $svg_url ? process_svg_paths($svg_url, $event_id, sanitize_title(get_the_title($event_id)), 0, $current_user_id, $post_type, $post_name, $autostart) : [];

    return [
        'event_id' => $event_id,
        'background' => $background,
        'svg_url' => $svg_url,
        'selected_paths' => $selected_paths,
    ];
}
