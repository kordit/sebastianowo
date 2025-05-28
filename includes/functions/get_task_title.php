<?php

/**
 * Funkcja do pobierania tytułu zadania na podstawie sluga
 * 
 * @param string $slug Slug zadania do wyszukania
 * @return string Tytuł zadania lub pusty string, jeśli nie znaleziono
 */
function get_task_title_by_slug($slug)
{
    if (empty($slug)) {
        return '';
    }

    // Pobierz wszystkie misje
    $args = [
        'post_type' => 'mission',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ];

    $missions_query = new WP_Query($args);

    if (!$missions_query->have_posts()) {
        return '';
    }

    // Przeszukaj wszystkie misje i ich zadania
    while ($missions_query->have_posts()) {
        $missions_query->the_post();
        $mission_id = get_the_ID();
        $mission_tasks = get_field('mission_tasks', $mission_id);

        if (is_array($mission_tasks)) {
            foreach ($mission_tasks as $task_index => $task) {
                // Sprawdź, czy zadanie ma pole task_checkpoint, które jest używane jako slug
                if (isset($task['task_checkpoint']) && $task['task_checkpoint'] === $slug) {
                    wp_reset_postdata();
                    return $task['task_title'];
                }

                // Sprawdź slug wygenerowany na podstawie tytułu zadania (alternatywna metoda)
                $generated_slug = sanitize_title($task['task_title']);
                if ($generated_slug === $slug) {
                    wp_reset_postdata();
                    return '"' . $task['task_title'] . '"';
                }
            }
        }
    }

    // Przywróć oryginalne zapytanie
    wp_reset_postdata();

    // Jeśli nie znaleziono zadania, zwróć sformatowany slug jako awaryjny tytuł
    return ucfirst(str_replace(['-', '_'], ' ', $slug));
}
