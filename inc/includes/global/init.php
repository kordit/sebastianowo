<?php
function et_svg_with_data($linksvg, $data = [])
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

    // Usuwanie wszystkich <title>
    $titles = $svg->getElementsByTagName('title');
    while ($titles->length > 0) {
        $titles->item(0)->parentNode->removeChild($titles->item(0));
    }

    // Pobranie wszystkich <path>
    $paths = $svg->getElementsByTagName('path');

    if ($paths->length === 0) {
        echo "Nie znaleziono żadnych path.";
        return;
    }

    // Iteracja i dodawanie `data-*` z tablicy terenów
    foreach ($paths as $index => $path) {
        if (!isset($data[$index])) {
            break;
        }

        $teren = $data[$index];

        foreach ($teren as $key => $value) {
            if (!empty($value)) {
                $path->setAttribute("data-$key", $value);
            }
        }
    }

    // Wyświetlenie poprawionego SVG
    echo $svg->saveXML();
}
