<?php

/**
 * Klasa LocationExtractor
 * 
 * Odpowiada za wyodrębnianie informacji o lokalizacji z URL w systemie dialogów NPC.
 * 
 * @package Game
 * @subpackage NpcPopup
 * @since 1.0.0
 */
class LocationExtractor
{
    /**
     * Ekstrahuje informacje o lokalizacji z URL
     *
     * @param string $url URL, z którego wyodrębniamy dane lokalizacji
     * @return array Tablica z informacjami o lokalizacji (area_slug, area_name, area_id)
     */
    public function extract_from_url(string $url): array
    {
        $location = [
            'area_slug' => '',
            'area_name' => '',
            'area_id' => 0
        ];

        // Sprawdzamy, czy URL zawiera segment 'tereny'
        if (preg_match('/\/tereny\/([^\/]+)\/?/', $url, $matches)) {
            $location['area_slug'] = sanitize_title($matches[1]);

            // Pobierz ID i nazwę terenu na podstawie sluga
            $area = $this->get_area_by_slug($location['area_slug']);
            if ($area) {
                $location['area_id'] = $area->ID;
                $location['area_name'] = $area->post_title;
            }
        }

        return $location;
    }

    /**
     * Alias metody extract_from_url dla kompatybilności z istniejącym kodem
     *
     * @param string $url URL, z którego wyodrębniamy dane lokalizacji
     * @return array Tablica z informacjami o lokalizacji
     */
    public function extract_location_from_url(string $url): array
    {
        return $this->extract_from_url($url);
    }

    /**
     * Pobiera obiekt terenu na podstawie sluga
     *
     * @param string $slug Slug terenu
     * @return WP_Post|null Obiekt terenu lub null w przypadku braku
     */
    private function get_area_by_slug(string $slug): ?\WP_Post
    {
        $args = [
            'name' => $slug,
            'post_type' => 'tereny',
            'post_status' => 'publish',
            'numberposts' => 1
        ];

        $area_posts = get_posts($args);

        if (!empty($area_posts)) {
            return $area_posts[0];
        }

        return null;
    }
}
