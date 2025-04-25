<?php
// Plik: inc/includes/mission/ajax_tasks.php
// AJAX: Zwraca zadania (task_id => task_title) dla wybranej misji

add_action('wp_ajax_get_mission_tasks', 'ajax_get_mission_tasks');
add_action('wp_ajax_nopriv_get_mission_tasks', 'ajax_get_mission_tasks');

function ajax_get_mission_tasks()
{
    $mission_id = isset($_POST['mission_id']) ? intval($_POST['mission_id']) : 0;
    if (!$mission_id) {
        wp_send_json_error(['message' => 'Brak ID misji']);
    }

    // Pobierz wybraną wartość z parametru POST (tylko dla odświeżenia selecta przy zmianie misji)
    $posted_selected_task = isset($_POST['selected_task']) ? sanitize_text_field($_POST['selected_task']) : '';

    // Pobierz obiekt postu, z którego pochodzi zapytanie
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $field_path = isset($_POST['field_path']) ? sanitize_text_field($_POST['field_path']) : '';

    // Zmienna do przechowania wybranego zadania
    $selected_task = $posted_selected_task; // Na początek ustaw wartość z POST (dla przypadków zmiany misji)

    // Jeśli mamy ID postu i ścieżkę pola, spróbuj odczytać wartość z bazy
    if ($post_id && $field_path) {
        // Pobierz pełne dane pola
        $post_data = get_field('anwser', $post_id);

        // Odczytaj wartość z określonej ścieżki
        // Przykład: [row-5][field_67b4e72eec415][row-0][field_67b4ea1e2acbb][row-0][field_mission_task_id]
        if ($post_data && $field_path) {
            $path_components = explode('][', trim($field_path, '[]'));

            $current_data = $post_data;
            $found_value = false;

            // Nawiguj przez zagnieżdżone tablice według ścieżki
            foreach ($path_components as $component) {
                if (isset($current_data[$component])) {
                    $current_data = $current_data[$component];
                    $found_value = true;
                } else {
                    $found_value = false;
                    break;
                }
            }

            if ($found_value && !empty($current_data)) {
                $selected_task = $current_data;
            }
        }
    }

    $tasks = get_field('mission_tasks', $mission_id);
    $result = array();
    if ($tasks && is_array($tasks)) {
        foreach ($tasks as $task) {
            $task_id = isset($task['task_id']) ? $task['task_id'] : '';
            $task_title = isset($task['task_title']) ? $task['task_title'] : '';
            if ($task_id && $task_title) {
                $result[$task_id] = $task_title;
            }
        }
    }

    // Zwróć zarówno zadania jak i wybraną wartość
    wp_send_json_success([
        'tasks' => $result,
        'selected_task' => $selected_task
    ]);
}

// Automatyczne ładowanie JS do selecta z zadaniami misji w panelu admina
add_action('admin_enqueue_scripts', function ($hook) {
    // Ładuj tylko na stronach edycji postów (np. post.php, post-new.php)
    if (in_array($hook, ['post.php', 'post-new.php'])) {
        wp_enqueue_script(
            'mission-tasks-select',
            get_template_directory_uri() . '/inc/includes/mission/mission-tasks-select.js',
            [],
            filemtime(get_template_directory() . '/inc/includes/mission/mission-tasks-select.js'),
            true
        );
    }
});
