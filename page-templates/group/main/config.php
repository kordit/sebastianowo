<?php
$svg_paths = get_fields('option'); // Pobranie wszystkich opcji ACF


$selected_tereny = [];

if ($svg_paths) {
    foreach ($svg_paths as $key => $value) {
        if (strpos($key, 'svg_path_') === 0 && !empty($value) && is_object($value)) {
            $fields = get_fields($value->ID);

            // Sprawdzenie, czy siedziba_grupy istnieje i jest obiektem
            $lokalizacja = isset($fields['siedziba_grupy']) && is_object($fields['siedziba_grupy'])
                ? $fields['siedziba_grupy']
                : null;

            // Tworzenie tablicy z danymi
            $selected_tereny[] = [
                'id' => $value->ID,
                'title' => $value->post_title,
                'link' => get_permalink($value->ID),
                'availability_village_id' => $lokalizacja ? $lokalizacja->ID : 'none',
                'color' => $lokalizacja ? get_field('color_district', $lokalizacja->ID) : 'none',
                'availability' => $lokalizacja ? $lokalizacja->post_title : 'none',
                'availability_link' => $lokalizacja ? get_permalink($lokalizacja->ID) : 'none',
            ];
        }
    }
}
