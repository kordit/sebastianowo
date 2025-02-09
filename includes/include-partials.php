<?php
function include_init_files_in_directories($base_directory)
{
    // Upewnij się, że katalog istnieje
    if (!is_dir($base_directory)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo "Directory does not exist: " . $base_directory;
        }
        return;
    }

    // Otwórz katalog
    $dir = opendir($base_directory);
    if (!$dir) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo "Failed to open directory: " . $base_directory;
        }
    }

    // Iteruj przez wszystkie elementy w katalogu
    while (($file = readdir($dir)) !== false) {
        // Pomijamy '.' i '..'
        if ($file == '.' || $file == '..') {
            continue;
        }

        $full_path = $base_directory . '/' . $file;

        // Sprawdź, czy to katalog
        if (is_dir($full_path)) {
            $init_file = $full_path . '/init.php';

            // Sprawdź, czy plik init.php istnieje
            if (file_exists($init_file)) {
                // include $init_file;
                include($init_file);
            } else {
                // Jeśli plik nie istnieje i debugowanie jest włączone, wyświetl komunikat
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    echo "Brak pliku init.php w katalogu: " . $full_path . "<br>";
                }
            }
        }
    }

    // // Zamknij katalog
    closedir($dir);
}

// Wywołanie funkcji dla określonego katalogu
include_init_files_in_directories(get_template_directory() . '/includes');

$directory = get_template_directory() . '/templates';
$folders = glob($directory . '/*', GLOB_ONLYDIR);
foreach ($folders as $folder) {
    $file = $folder . '/functions.php';
    if (file_exists($file)) {
        include $file;
    }
}
