<?php
// Plik: inc/includes/mission/ajax_tasks.php
// AJAX: Zwraca zadania (task_id => task_title) dla wybranej misji

add_action('wp_ajax_get_mission_tasks', 'ajax_get_mission_tasks');
add_action('wp_ajax_nopriv_get_mission_tasks', 'ajax_get_mission_tasks');

// Dodatkowy endpoint do pobierania wybranych zadań i statusów z tablicy anwser
add_action('wp_ajax_get_selected_mission_data', 'ajax_get_selected_mission_data');
add_action('wp_ajax_nopriv_get_selected_mission_data', 'ajax_get_selected_mission_data');

/**
 * Funkcja rekurencyjnego przeszukiwania danych ACF w poszukiwaniu wybranego zadania dla misji
 */
function find_selected_task_recursive($data, $mission_id)
{
    if (!is_array($data)) {
        return '';
    }

    // Sprawdź czy mamy bezpośrednie dopasowanie
    if (isset($data['mission_id']) && $data['mission_id'] == $mission_id && isset($data['mission_task_id'])) {
        return $data['mission_task_id'];
    }

    // Sprawdź czy mamy mission_select z dopasowaniem
    if (isset($data['mission_select']) && is_array($data['mission_select'])) {
        if (
            isset($data['mission_select']['mission_id']) &&
            $data['mission_select']['mission_id'] == $mission_id &&
            isset($data['mission_select']['mission_task_id'])
        ) {
            return $data['mission_select']['mission_task_id'];
        }
    }

    // Przeszukaj wszystkie pola rekurencyjnie
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $result = find_selected_task_recursive($value, $mission_id);
            if (!empty($result)) {
                return $result;
            }
        }
    }

    return '';
}

function ajax_get_mission_tasks()
{
    $mission_id = isset($_POST['mission_id']) ? $_POST['mission_id'] : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $field_path = isset($_POST['field_path']) ? $_POST['field_path'] : '';
    $selected_task = isset($_POST['selected_task']) ? $_POST['selected_task'] : '';
    $content_index = isset($_POST['content_index']) ? $_POST['content_index'] : null;

    $missions = array();
    $results = array();
    $current_selected_task = '';

    // Debug logging
    error_log('MISSION TASKS REQUEST - Mission ID: ' . $mission_id . ', Post ID: ' . $post_id . ', Field Path: ' . $field_path);

    // Jeśli mamy post_id i field_path, spróbuj pobrać zapisaną wartość zadania
    if ($post_id && $field_path) {
        // Pobierz dane z posta dla tego pola
        $post_data = get_post_meta($post_id, '_' . $field_path, true);
        if (empty($post_data)) {
            // Spróbuj pobrać dane z tablic ACF
            $acf_data = get_field($field_path, $post_id);
            if (!empty($acf_data)) {
                $post_data = $acf_data;
            }
        }

        // Sprawdź czy field_path zawiera mission_id, jeśli tak, znajdź powiązane mission_task_id
        if (strpos($field_path, 'mission_id') !== false) {
            $task_path = str_replace('mission_id', 'mission_task_id', $field_path);
            $task_data = get_post_meta($post_id, '_' . $task_path, true);
            if (!empty($task_data)) {
                $current_selected_task = $task_data;
            }
        }

        // Dodatkowe sprawdzenie dla wybranej misji - próba znalezienia w tablicy anwsers
        if (empty($current_selected_task)) {
            // Wyczyść cache ACF, żeby upewnić się, że mamy najświeższe dane
            wp_cache_delete('acf_get_post_meta_' . $post_id);

            // 1. Najpierw spróbuj znaleźć bezpośrednio w meta danych postu bez cache ACF
            global $wpdb;
            $search_mission_id = $wpdb->prepare('%s', $mission_id);
            $direct_query = $wpdb->prepare(
                "SELECT pm1.meta_value 
                FROM {$wpdb->postmeta} pm1 
                JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
                WHERE pm1.post_id = %d 
                AND pm1.meta_key LIKE %s 
                AND pm2.meta_key LIKE %s 
                AND pm2.meta_value = %s",
                $post_id,
                '%mission_task_id%',
                '%mission_id%',
                $mission_id
            );

            $direct_task = $wpdb->get_var($direct_query);
            if (!empty($direct_task)) {
                $current_selected_task = $direct_task;
                error_log('MISSION TASKS - Found task directly in database: ' . $current_selected_task);
            } else {
                // 2. Pobierz dane bezpośrednio z meta bez cache ACF
                $all_fields = get_fields($post_id, false);

                // Logowanie całej struktury dla debugowania
                error_log('MISSION DEBUG - Post ID: ' . $post_id . ', Mission ID: ' . $mission_id);
                if (isset($all_fields['content'])) {
                    foreach ($all_fields['content'] as $index => $row) {
                        if (isset($row['mission_id'])) {
                            error_log('MISSION DEBUG - Found mission at index ' . $index . ': ' .
                                'mission_id=' . $row['mission_id'] .
                                ', task_id=' . (isset($row['mission_task_id']) ? $row['mission_task_id'] : 'not set'));
                        }
                    }
                }

                // Rekurencyjnie przeszukaj dane w poszukiwaniu dopasowania misji i zadania
                $current_selected_task = find_selected_task_recursive($all_fields, $mission_id);
                error_log('MISSION TASKS - Recursive search result: ' . $current_selected_task);

                // 2. Jeśli nie znaleźliśmy, spróbujmy bardziej bezpośrednio - bez użycia cache
                if (empty($current_selected_task)) {
                    // Użyj bezpośredniego zapytania SQL aby pobrać dane "content"
                    global $wpdb;
                    $meta_key = '_content';
                    $meta_value = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT meta_value FROM $wpdb->postmeta 
                         WHERE post_id = %d AND meta_key = %s",
                            $post_id,
                            $meta_key
                        )
                    );

                    if (!empty($meta_value)) {
                        $content_data = maybe_unserialize($meta_value);
                        error_log('MISSION TASKS - Fetched content data directly from database');
                    } else {
                        // Jeśli nie znaleziono w bezpośrednim meta, spróbuj przez ACF ale z wyłączonym cache
                        $content = get_field('content', $post_id, false);
                        $content_data = $content;
                        error_log('MISSION TASKS - Using ACF get_field without cache');
                    }

                    // Teraz przeszukaj dane content
                    if (!empty($content_data) && is_array($content_data)) {
                        // Jeśli mamy konkretny indeks content, sprawdźmy go bezpośrednio
                        if (!is_null($content_index) && isset($content_data[$content_index])) {
                            // Sprawdzamy bezpośrednio w tym indeksie
                            $row = $content_data[$content_index];
                            if (isset($row['mission_id']) && $row['mission_id'] == $mission_id && isset($row['mission_task_id'])) {
                                $current_selected_task = $row['mission_task_id'];
                                error_log('MISSION TASKS - Found direct task match at index: ' . $content_index . ' = ' . $current_selected_task);
                            }
                        }

                        // Przeszukaj wszystkie elementy content niezależnie od tego czy mamy indeks
                        foreach ($content_data as $index => $row) {
                            // Zapisuj każdą znalezioną parę mission_id/mission_task_id do logów dla diagnostyki
                            if (isset($row['mission_id']) && isset($row['mission_task_id'])) {
                                error_log('MISSION TASKS - Content[' . $index . ']: mission_id=' . $row['mission_id'] . ', task=' . $row['mission_task_id']);

                                if ($row['mission_id'] == $mission_id) {
                                    $current_selected_task = $row['mission_task_id'];
                                    error_log('MISSION TASKS - Found task in content row: ' . $index . ' = ' . $current_selected_task);
                                    break;
                                }
                            }

                            // Sprawdź głębiej w strukturze odpowiedzi
                            if (isset($row['anwsers']) && is_array($row['anwsers'])) {
                                foreach ($row['anwsers'] as $answer_index => $answer) {
                                    if (isset($answer['mission_id']) && $answer['mission_id'] == $mission_id && isset($answer['mission_task_id'])) {
                                        $current_selected_task = $answer['mission_task_id'];
                                        error_log('MISSION TASKS - Found task in answer row: ' . $index . ', answer: ' . $answer_index);
                                        break 2;
                                    }
                                }
                            }

                            // Sprawdź w layout_settings
                            if (isset($row['layout_settings']) && is_array($row['layout_settings'])) {
                                if (isset($row['layout_settings']['conversation_start']) && is_array($row['layout_settings']['conversation_start'])) {
                                    foreach ($row['layout_settings']['conversation_start'] as $warunek) {
                                        if (
                                            isset($warunek['mission_select']['mission_id']) &&
                                            $warunek['mission_select']['mission_id'] == $mission_id &&
                                            isset($warunek['mission_select']['mission_task_id'])
                                        ) {
                                            $current_selected_task = $warunek['mission_select']['mission_task_id'];
                                            error_log('MISSION TASKS - Found task in conversation_start: ' . $index);
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Logowanie do debugowania
        error_log('MISSION TASKS - Post ID: ' . $post_id . ', Path: ' . $field_path . ', Found Task: ' . $current_selected_task);
    }

    // Użyj przekazanego selected_task, jeśli current_selected_task jest pusty
    if (empty($current_selected_task) && !empty($selected_task)) {
        $current_selected_task = $selected_task;
    }

    if ($mission_id) {
        $ids = is_array($mission_id) ? array_map('intval', $mission_id) : [intval($mission_id)];
    } else {
        $ids = get_posts([
            'post_type' => 'mission',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
    }

    foreach ($ids as $id) {
        $tasks = get_field('mission_tasks', $id);
        if (!$tasks || !is_array($tasks)) {
            continue;
        }

        $results[$id]['tasks'] = array();

        foreach ($tasks as $task) {
            $task_id = isset($task['task_id']) && !empty($task['task_id'])
                ? $task['task_id']
                : (isset($task['task_title']) ? sanitize_title($task['task_title']) . '_' . array_search($task, $tasks) : '');

            $task_title = isset($task['task_title']) ? $task['task_title'] : '';

            if ($task_id && $task_title) {
                $task_type = isset($task['task_type']) ? $task['task_type'] : 'checkpoint';
                $title = ($task_type == 'checkpoint_npc' && !empty($task['task_checkpoint_npc']))
                    ? $task_title . ' (NPC)'
                    : $task_title;

                // Sprawdź czy to zadanie jest wybrane
                $is_selected = ($current_selected_task == $task_id);

                // Dodaj zadanie do tablicy z dodatkową informacją o wybraniu
                $results[$id]['tasks'][$task_id] = array(
                    'title' => $title,
                    'selected' => $is_selected
                );
            }
        }

        $results[$id]['title'] = get_the_title($id);
    }

    wp_send_json_success($results);
}

// Automatyczne ładowanie JS do selecta z zadaniami misji w panelu admina
add_action('admin_enqueue_scripts', function ($hook) {
    // Ładuj tylko na stronach edycji postów (np. post.php, post-new.php)
    if (in_array($hook, ['post.php', 'post-new.php'])) {
        wp_enqueue_script(
            'mission-tasks-select',
            get_template_directory_uri() . '/inc/includes/mission/mission-tasks-select.js',
            ['jquery'],
            filemtime(get_template_directory() . '/inc/includes/mission/mission-tasks-select.js'),
            true
        );
    }
});
