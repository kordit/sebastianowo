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
    wp_send_json_success(['tasks' => $result]);
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
