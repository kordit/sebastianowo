<?php
$svg_paths = get_fields('option'); // Pobranie wszystkich opcji ACF
$selected_tereny = [];

if ($svg_paths) {
    foreach ($svg_paths as $key => $value) {
        if (strpos($key, 'svg_path_') === 0 && !empty($value) && is_object($value)) {
            $fields = get_fields($value->ID);

            // Sprawdzenie, czy lokalizacja_dla_terenow istnieje i jest obiektem
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
function et_svg_with_data($linksvg, $tereny = [])
{
    $link = preg_replace('/https?\:\/\/[^\/]*\//', '', $linksvg);
    if (!file_exists($link)) {
        if (is_admin()) {
            $link = "/" . $link;
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $link)) {
                return;
            }
        } else {
            return;
        }
    }

    libxml_use_internal_errors(true);
    $svg = new DOMDocument();
    $svg->load($link);

    // Pobranie wszystkich <path>
    $paths = $svg->getElementsByTagName('path');

    if ($paths->length === 0) {
        echo "Nie znaleziono żadnych path.";
        return;
    }

    // Iteracja i dodawanie `data-*` z tablicy terenów
    foreach ($paths as $index => $path) {
        if (!isset($tereny[$index])) {
            break;
        }

        $teren = $tereny[$index];

        foreach ($teren as $key => $value) {
            if (!empty($value)) {
                $path->setAttribute("data-$key", $value);
            }
        }
    }

    // Wyświetlenie poprawionego SVG
    echo $svg->saveXML();
}
