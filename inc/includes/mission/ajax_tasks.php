<?php
// Plik: inc/includes/mission/ajax_tasks.php
// AJAX: Zwraca zadania (task_id => task_title) dla wybranej misji

add_action('wp_ajax_get_mission_tasks', 'ajax_get_mission_tasks');
add_action('wp_ajax_nopriv_get_mission_tasks', 'ajax_get_mission_tasks');

// Dodatkowy endpoint do pobierania wybranych zadań i statusów z tablicy anwser
add_action('wp_ajax_get_selected_mission_data', 'ajax_get_selected_mission_data');
add_action('wp_ajax_nopriv_get_selected_mission_data', 'ajax_get_selected_mission_data');

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

    // Pobierz zadania misji z uwzględnieniem nowej struktury zadań
    $tasks = get_field('mission_tasks', $mission_id);
    $result = array();

    if ($tasks && is_array($tasks)) {
        foreach ($tasks as $task) {
            // Sprawdź czy task_id istnieje lub wygeneruj go na podstawie tytułu i indeksu
            $task_id = isset($task['task_id']) && !empty($task['task_id'])
                ? $task['task_id']
                : (isset($task['task_title']) ? sanitize_title($task['task_title']) . '_' . array_search($task, $tasks) : '');

            $task_title = isset($task['task_title']) ? $task['task_title'] : '';

            if ($task_id && $task_title) {
                // Dodaj informację o typie zadania (może być przydatne dla interfejsu)
                $task_type = isset($task['task_type']) ? $task['task_type'] : 'checkpoint';

                // Obsługa zadań typu checkpoint_npc - możemy dodać znacznik
                if ($task_type == 'checkpoint_npc' && !empty($task['task_checkpoint_npc'])) {
                    $result[$task_id] = $task_title . ' (NPC)';
                } else {
                    $result[$task_id] = $task_title;
                }
            }
        }
    }

    // Zwróć zarówno zadania jak i wybraną wartość
    wp_send_json_success([
        'tasks' => $result,
        'selected_task' => $selected_task
    ]);
}

/**
 * Funkcja obsługująca endpoint pobierania danych misji i zadań z tablicy anwser
 */
function ajax_get_selected_mission_data()
{
    // Sprawdź wymagane parametry
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $field_path = isset($_POST['field_path']) ? sanitize_text_field($_POST['field_path']) : '';

    if (!$post_id) {
        wp_send_json_error(['message' => 'Brak ID postu']);
    }

    // Domyślna pusta odpowiedź
    $result = array(
        'mission_id' => '',
        'mission_status' => '',
        'mission_task_id' => '',
        'mission_task_status' => ''
    );

    // Bezpośrednie pozyskanie metadanych postu dla lepszego debugowania
    $all_meta = get_post_meta($post_id);

    // 1. METODA STANDARDOWA: Poszukaj danych anwser przy użyciu get_field
    $anwser_data = get_field('anwser', $post_id);

    // Logowanie do debugowania
    error_log('POST_ID: ' . $post_id);
    error_log('FIELD_PATH: ' . $field_path);

    // 2. METODA BEZPOŚREDNIA: Szukaj w tablicy type_anwser
    // Iteruj po wszystkich meta polach postu
    foreach ($all_meta as $meta_key => $meta_value) {
        if (strpos($meta_key, 'type_anwser') !== false) {
            $type_anwser_data = maybe_unserialize($meta_value[0]);
            if (is_array($type_anwser_data)) {
                foreach ($type_anwser_data as $entry) {
                    if (isset($entry['acf_fc_layout']) && $entry['acf_fc_layout'] === 'mission') {
                        if (isset($entry['mission_id'])) $result['mission_id'] = $entry['mission_id'];
                        if (isset($entry['mission_status'])) $result['mission_status'] = $entry['mission_status'];
                        if (isset($entry['mission_task_id'])) $result['mission_task_id'] = $entry['mission_task_id'];
                        if (isset($entry['mission_task_status'])) $result['mission_task_status'] = $entry['mission_task_status'];
                        break;
                    }
                }
            }
        }
    }

    // 3. METODA ANWSER: Szukaj w standardowej tablicy anwser
    // Jeśli mamy dane anwser z get_field
    if ($anwser_data && is_array($anwser_data) && empty($result['mission_id'])) {
        // Sprawdź czy istnieje klucz type_anwser
        if (isset($anwser_data['type_anwser']) && is_array($anwser_data['type_anwser'])) {
            foreach ($anwser_data['type_anwser'] as $anwser_entry) {
                if (isset($anwser_entry['acf_fc_layout']) && $anwser_entry['acf_fc_layout'] === 'mission') {
                    if (isset($anwser_entry['mission_id'])) $result['mission_id'] = $anwser_entry['mission_id'];
                    if (isset($anwser_entry['mission_status'])) $result['mission_status'] = $anwser_entry['mission_status'];
                    if (isset($anwser_entry['mission_task_id'])) $result['mission_task_id'] = $anwser_entry['mission_task_id'];
                    if (isset($anwser_entry['mission_task_status'])) $result['mission_task_status'] = $anwser_entry['mission_task_status'];
                    break;
                }
            }
        }

        // Jeśli nie znaleziono, przeszukaj rekurencyjnie całą strukturę
        if (empty($result['mission_id'])) {
            find_mission_recursive($anwser_data, $result);
        }
    }

    // 4. METODA STAREJ ŚCIEŻKI: Próbuj wykorzystać przekazaną ścieżkę (dla kompatybilności)
    if (empty($result['mission_id']) && $anwser_data && $field_path) {
        $is_acf_path = (strpos($field_path, 'acf') === 0);
        if ($is_acf_path) {
            $path_components = explode('][', trim($field_path, '[]'));
            array_shift($path_components); // Usuń pierwszy element 'acf'

            $current_data = $anwser_data;

            // Próbuj nawigować przez komponenty ścieżki
            foreach ($path_components as $component) {
                if (isset($current_data[$component])) {
                    $current_data = $current_data[$component];
                } else {
                    $current_data = null;
                    break;
                }
            }

            // Jeśli udało się dotrzeć do wartości, sprawdź czy to misja
            if (is_array($current_data) && isset($current_data['mission_id'])) {
                $result['mission_id'] = $current_data['mission_id'];
                if (isset($current_data['mission_status'])) $result['mission_status'] = $current_data['mission_status'];
                if (isset($current_data['mission_task_id'])) $result['mission_task_id'] = $current_data['mission_task_id'];
                if (isset($current_data['mission_task_status'])) $result['mission_task_status'] = $current_data['mission_task_status'];
            }
        }
    }

    // Logowanie do debugowania
    error_log('GET_SELECTED_MISSION_DATA RESULT: ' . json_encode($result));

    // Zwróć znalezione dane
    wp_send_json_success($result);
}

/**
 * Funkcja pomocnicza do rekurencyjnego przeszukiwania struktury danych w poszukiwaniu misji
 * 
 * @param array $data_array Tablica danych do przeszukania
 * @param array &$result Tablica z wynikami do wypełnienia (przekazana przez referencję)
 * @return bool True jeśli znaleziono dane, false w przeciwnym wypadku
 */
function find_mission_recursive($data_array, &$result)
{
    // Podstawowa walidacja
    if (!is_array($data_array)) {
        return false;
    }

    // Sprawdź czy bieżący element zawiera informacje o misji
    if (isset($data_array['mission_id']) && !empty($data_array['mission_id'])) {
        $result['mission_id'] = $data_array['mission_id'];
        if (isset($data_array['mission_status'])) $result['mission_status'] = $data_array['mission_status'];
        if (isset($data_array['mission_task_id'])) $result['mission_task_id'] = $data_array['mission_task_id'];
        if (isset($data_array['mission_task_status'])) $result['mission_task_status'] = $data_array['mission_task_status'];
        return true;
    }

    // Sprawdź czy to jest wpis misji w flexible content
    if (isset($data_array['acf_fc_layout']) && $data_array['acf_fc_layout'] === 'mission') {
        if (isset($data_array['mission_id'])) {
            $result['mission_id'] = $data_array['mission_id'];
            if (isset($data_array['mission_status'])) $result['mission_status'] = $data_array['mission_status'];
            if (isset($data_array['mission_task_id'])) $result['mission_task_id'] = $data_array['mission_task_id'];
            if (isset($data_array['mission_task_status'])) $result['mission_task_status'] = $data_array['mission_task_status'];
            return true;
        }
    }

    // Przeszukaj wszystkie elementy tablicy rekurencyjnie
    foreach ($data_array as $key => $value) {
        if (is_array($value)) {
            $found = find_mission_recursive($value, $result);
            if ($found) {
                return true;
            }
        }
    }

    return false;
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
