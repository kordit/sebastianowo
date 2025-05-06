<?php

/**
 * Klasa LocationExtractor
 *
 * Odpowiada za wyodrębnianie informacji o lokalizacji z URL.
 *
 * @package Game
 * @since 1.0.0
 */

class LocationExtractor
{
    /**
     * Wyodrębnia informację o lokalizacji z URL
     *
     * @param string $url URL do analizy
     * @return string Wyodrębniona lokalizacja
     */
    public function extract_location_from_url(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));

        // Usuń pierwszą część ścieżki jeśli to nazwa motywu lub witryny
        if (count($parts) > 0) {
            if (in_array($parts[0], ['game', 'wp', 'wordpress'])) {
                array_shift($parts);
            }
        }

        return implode('/', $parts);
    }
}
