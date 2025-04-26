<?php

/**
 * System zarządzania misjami - UPROSZCZONA WERSJA
 * 
 * @package Game
 * @version 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Funkcja do logowania debugowania misji - zapisuje do pliku temp-log.log
 * 
 * @param string $message Wiadomość do zalogowania
 * @param mixed $data Opcjonalne dane do zalogowania
 * @return void
 */
function mission_debug_log($message, $data = null)
{
    $log_file = ABSPATH . '/wp-content/themes/game/temp-log.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $log_message = $timestamp . ' ' . $message;

    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_message .= "\n" . print_r($data, true);
        } else {
            $log_message .= ': ' . $data;
        }
    }

    $log_message .= "\n\n";

    // Zapisz do pliku
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Rejestracja akcji AJAX dla systemu misji
 */
function init_mission_management()
{
    add_action('wp_ajax_assign_mission_to_user', 'ajax_assign_mission_to_user');
    add_action('wp_ajax_get_mission_info', 'ajax_get_mission_info');
    add_action('wp_ajax_update_mission_task_status', 'ajax_update_mission_task_status');
}
add_action('init', 'init_mission_management');

/**
 * Pobiera informacje o misji (AJAX)
 */
function ajax_get_mission_info()
{
    // POCZĄTEK LOGOWANIA AJAX
    mission_debug_log('===== ROZPOCZĘTO AJAX get_mission_info =====');
    mission_debug_log('WEJŚCIOWE DANE POST', $_POST);

    if (!is_user_logged_in()) {
        mission_debug_log('BŁĄD: Użytkownik nie jest zalogowany');
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
    }

    $mission_id = isset($_POST['mission_id']) ? intval($_POST['mission_id']) : 0;
    if (!$mission_id) {
        mission_debug_log('BŁĄD: Nieprawidłowe ID misji');
        wp_send_json_error(['message' => 'Nieprawidłowe ID misji']);
    }

    mission_debug_log('Przetwarzanie misji ID', $mission_id);

    $mission = get_post($mission_id);
    if (!$mission || $mission->post_type !== 'mission') {
        mission_debug_log('BŁĄD: Misja nie istnieje');
        wp_send_json_error(['message' => 'Misja nie istnieje']);
    }

    $mission_tasks = get_field('mission_tasks', $mission_id);
    $first_task_id = null;
    $tasks = [];

    // Sprawdź czy mamy wskazane konkretne zadanie
    $specified_task_id = isset($_POST['mission_task_id']) ? sanitize_text_field($_POST['mission_task_id']) : null;
    $found_specified = false;

    if (is_array($mission_tasks) && !empty($mission_tasks)) {
        // Jeśli mamy wskazane konkretne zadanie, znajdź je
        if ($specified_task_id) {
            error_log('Szukam konkretnego zadania: ' . $specified_task_id);
            $clean_specified_id = preg_replace('/_\d+$/', '', $specified_task_id);

            foreach ($mission_tasks as $index => $task) {
                $task_id = isset($task['task_id']) ? $task['task_id'] : 'task_' . $index;
                $clean_task_id = preg_replace('/_\d+$/', '', $task_id);

                // Dokładne dopasowanie lub dopasowanie po usunięciu sufiksów
                if (
                    $task_id === $specified_task_id ||
                    $clean_task_id === $clean_specified_id ||
                    strpos($task_id, $clean_specified_id) !== false ||
                    strpos($specified_task_id, $clean_task_id) !== false
                ) {

                    $first_task_id = $task_id;
                    $tasks[$task_id] = $task['task_title'] ?? ('Zadanie ' . ($index + 1));
                    $found_specified = true;
                    error_log('Znaleziono konkretne zadanie: ' . $task_id);
                    break;
                }
            }

            // Specjalne traktowanie dla zidentyfikuj-potencjalnych-klientow_0
            if (!$found_specified && $specified_task_id === 'zidentyfikuj-potencjalnych-klientow_0' && !empty($mission_tasks[0])) {
                $task = $mission_tasks[0];
                $task_id = isset($task['task_id']) ? $task['task_id'] : 'task_0';
                $first_task_id = $task_id;
                $tasks[$task_id] = $task['task_title'] ?? 'Zadanie 1';
                $found_specified = true;
                error_log('Specjalne dopasowanie dla zadania zidentyfikuj-potencjalnych-klientow_0: ' . $task_id);
            }
        }

        // Jeśli nie znaleziono konkretnego zadania lub nie wskazano żadnego, dodaj wszystkie
        if (!$found_specified) {
            foreach ($mission_tasks as $index => $task) {
                $task_id = isset($task['task_id']) ? $task['task_id'] : 'task_' . $index;
                if ($index === 0) {
                    $first_task_id = $task_id;
                }
                $tasks[$task_id] = $task['task_title'] ?? ('Zadanie ' . ($index + 1));
            }
        }
    }
    error_log('Zwracam dane o misji: task_id=' . $first_task_id);

    // POPRAWIONE: Jeśli mamy mission_task_id z żądania, użyj go zamiast task_id z bazy danych
    $task_id_to_return = $specified_task_id ?: $first_task_id;

    // Pobierz nazwę zadania dla pierwszego/wybranego zadania
    $task_name = '';
    if ($first_task_id && isset($tasks[$first_task_id])) {
        $task_name = $tasks[$first_task_id];
    }

    mission_debug_log('Zadanie do zwrócenia: task_id=' . $task_id_to_return . ' zamiast ' . $first_task_id);
    mission_debug_log('Nazwa zadania: ' . $task_name);

    // Przygotowanie odpowiedzi
    $response = [
        'mission_id' => $mission_id,
        'mission_title' => $mission->post_title,
        'mission_description' => $mission->post_content,
        'task_id' => $task_id_to_return,
        'task_name' => $task_name
    ];

    mission_debug_log('Odpowiedź JSON dla get_mission_info', $response);
    mission_debug_log('===== ZAKOŃCZONO AJAX get_mission_info =====');

    // Uproszczona odpowiedź JSON - użyj task_id z przycisku zamiast z bazy danych
    wp_send_json_success($response);
}

/**
 * Przypisuje misję do użytkownika (AJAX)
 */
function ajax_assign_mission_to_user()
{
    // POCZĄTEK LOGOWANIA AJAX
    mission_debug_log('===== ROZPOCZĘTO AJAX assign_mission_to_user =====');
    mission_debug_log('WEJŚCIOWE DANE POST', $_POST);

    if (!is_user_logged_in()) {
        mission_debug_log('BŁĄD: Użytkownik nie jest zalogowany');
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
        return;
    }

    $mission_id = isset($_POST['mission_id']) ? intval($_POST['mission_id']) : 0;
    $npc_id = isset($_POST['npc_id']) ? intval($_POST['npc_id']) : 0;
    $mission_status = isset($_POST['mission_status']) ? sanitize_text_field($_POST['mission_status']) : 'in_progress';
    $mission_task_id = isset($_POST['mission_task_id']) ? sanitize_text_field($_POST['mission_task_id']) : null;
    $mission_task_status = isset($_POST['mission_task_status']) ? sanitize_text_field($_POST['mission_task_status']) : 'in_progress';

    mission_debug_log('Przetworzone parametry', [
        'mission_id' => $mission_id,
        'npc_id' => $npc_id,
        'mission_status' => $mission_status,
        'mission_task_id' => $mission_task_id,
        'mission_task_status' => $mission_task_status
    ]);

    if (!$mission_id) {
        mission_debug_log('BŁĄD: Nieprawidłowe ID misji');
        wp_send_json_error(['message' => 'Nieprawidłowe ID misji']);
        return;
    }

    $user_id = get_current_user_id();
    mission_debug_log('ID użytkownika: ' . $user_id);

    $mission = get_post($mission_id);
    if (!$mission || $mission->post_type !== 'mission') {
        mission_debug_log('BŁĄD: Misja o ID ' . $mission_id . ' nie istnieje');
        wp_send_json_error(['message' => 'Misja nie istnieje']);
        return;
    }

    mission_debug_log('Tytuł misji: ' . $mission->post_title);
    mission_debug_log('Wywołanie funkcji assign_mission_to_user z parametrami', [
        'user_id' => $user_id,
        'mission_id' => $mission_id,
        'mission_status' => $mission_status,
        'mission_task_id' => $mission_task_id,
        'mission_task_status' => $mission_task_status
    ]);

    $result = assign_mission_to_user($user_id, $mission_id, $mission_status, $mission_task_id, $mission_task_status);

    mission_debug_log('Wynik funkcji assign_mission_to_user', $result);

    if ($result['success']) {
        $response = [
            'message' => 'Misja została przypisana do użytkownika',
            'mission_id' => $mission_id,
            'mission_title' => $mission->post_title,
            'task_id' => $result['first_task_id'],
            'npc_id' => $npc_id
        ];

        mission_debug_log('SUKCES: Odpowiedź JSON', $response);
        mission_debug_log('===== ZAKOŃCZONO AJAX assign_mission_to_user =====');

        wp_send_json_success($response);
    } else {
        mission_debug_log('BŁĄD: ' . $result['message']);
        mission_debug_log('===== ZAKOŃCZONO AJAX assign_mission_to_user =====');

        wp_send_json_error(['message' => $result['message']]);
    }
}

function assign_mission_to_user($user_id, $mission_id, $mission_status = 'in_progress', $mission_task_id = null, $mission_task_status = 'in_progress')
{
    // POCZĄTEK LOGOWANIA FUNKCJI GŁÓWNEJ
    mission_debug_log('=== ROZPOCZĘTO FUNKCJĘ assign_mission_to_user ===');
    mission_debug_log('Parametry wejściowe', [
        'user_id' => $user_id,
        'mission_id' => $mission_id,
        'mission_status' => $mission_status,
        'mission_task_id' => $mission_task_id,
        'mission_task_status' => $mission_task_status
    ]);

    // Sprawdź czy misja już istnieje
    $mission_field_key = 'mission_' . $mission_id;
    $existing_mission = get_field($mission_field_key, 'user_' . $user_id);

    mission_debug_log('Istniejąca misja w bazie', $existing_mission);

    // Sprawdzamy, czy misja już istnieje i jest aktywna
    if (is_array($existing_mission)) {
        // PRIORYTET: Blokujemy ponowne przypisywanie misji, która jest już aktywna lub ukończona
        if (isset($existing_mission['status']) && $existing_mission['status'] === 'in_progress') {
            mission_debug_log('BLOKADA: Misja jest już aktywna - nie można jej ponownie przypisać');

            // Jedyny przypadek kiedy pozwalamy na aktualizację to aktualizacja konkretnego zadania
            if (
                $mission_task_id && isset($existing_mission['tasks'][$mission_task_id]) &&
                is_array($existing_mission['tasks'][$mission_task_id]) &&
                $mission_task_status !== 'in_progress' // Nie pozwalamy na zmianę na "in_progress", bo to byłoby jak ponowne przypisanie
            ) {
            } else {
                // Blokujemy ponowne przypisanie misji, niezależnie od parametrów
                mission_debug_log('BLOKADA: Próba ponownego przypisania już aktywnej misji');
                return [
                    'success' => false,
                    'message' => 'Ta misja jest już aktywna'
                ];
            }
        }

        if (isset($existing_mission['status']) && $existing_mission['status'] === 'completed') {
            mission_debug_log('Misja jest już ukończona');
            return [
                'success' => false,
                'message' => 'Ta misja została już ukończona'
            ];
        }
    }    // Aktualizacja istniejącej misji lub tworzenie nowej
    $first_task_id = null;

    // Przygotowujemy dane misji, zachowując istniejącą strukturę
    $mission_data = is_array($existing_mission) ? $existing_mission : [
        'status' => 'not_started',
        'assigned_date' => '',
        'completion_date' => '',
        'tasks' => []
    ];

    // Aktualizacja głównego statusu misji
    $mission_data['status'] = $mission_status;

    // Ustawienie daty przypisania, jeśli wcześniej była pusta
    if (empty($mission_data['assigned_date'])) {
        $mission_data['assigned_date'] = current_time('mysql');
    }

    // Jeśli podano task_id, aktualizujemy jego status
    if ($mission_task_id) {
        // Zainicjalizuj tablicę zadań, jeśli nie istnieje
        if (!isset($mission_data['tasks']) || !is_array($mission_data['tasks'])) {
            $mission_data['tasks'] = [];
        }

        // Zachowanie istniejącej struktury zadania
        if (isset($mission_data['tasks'][$mission_task_id]) && is_array($mission_data['tasks'][$mission_task_id])) {
            $mission_data['tasks'][$mission_task_id]['status'] = $mission_task_status;
            // Dodaj datę rozpoczęcia, jeśli jej nie ma
            if (!isset($mission_data['tasks'][$mission_task_id]['start_date']) || empty($mission_data['tasks'][$mission_task_id]['start_date'])) {
                $mission_data['tasks'][$mission_task_id]['start_date'] = current_time('mysql');
            }
        } else {
            // Tworzenie nowej struktury dla zadania
            $mission_data['tasks'][$mission_task_id] = [
                'status' => $mission_task_status,
            ];
        }

        $first_task_id = $mission_task_id;
    } else {
        // Jeśli nie podano konkretnego zadania, sprawdzamy pierwsze zadanie
        $mission_tasks = get_field('mission_tasks', $mission_id);
        if (is_array($mission_tasks) && !empty($mission_tasks)) {
            $first_task = $mission_tasks[0];
            $first_task_id = isset($first_task['task_id']) ? $first_task['task_id'] : 'task_0';

            // Zachowanie struktury zadania, jeśli istnieje
            if (isset($mission_data['tasks'][$first_task_id]) && is_array($mission_data['tasks'][$first_task_id])) {
                $mission_data['tasks'][$first_task_id]['status'] = $mission_task_status;
                if (!isset($mission_data['tasks'][$first_task_id]['start_date']) || empty($mission_data['tasks'][$first_task_id]['start_date'])) {
                    $mission_data['tasks'][$first_task_id]['start_date'] = current_time('mysql');
                }
            } else {
                $mission_data['tasks'][$first_task_id] = [
                    'status' => $mission_task_status,
                ];
            }
        }
    }

    mission_debug_log('Dane misji do zapisania', $mission_data);

    // Zapisujemy zaktualizowaną misję do bazy danych - z obsługą błędów
    try {
        // Próba zapisu przez ACF
        $acf_success = update_field($mission_field_key, $mission_data, 'user_' . $user_id);
        mission_debug_log('Wynik zapisu ACF', $acf_success);

        // Sprawdzenie czy rzeczywiście zapisano
        $success = false;

        // Jeśli ACF nie zwrócił błędu, zakładamy sukces
        if ($acf_success !== false) {
            $success = true;
        }
        // Jeśli ACF zwrócił pusty wynik (to często zdarza się w ACF), próbujemy bezpośrednio 
        elseif ($acf_success === false || empty($acf_success)) {
            // Alternatywna metoda zapisu poprzez zwykłe meta
            $meta_key = '_' . $mission_field_key;  // kluczowe meta dla ACF zazwyczaj używa prefiksu _
            $direct_success = update_user_meta($user_id, $meta_key, $mission_data);

            if ($direct_success !== false) {
                $success = true;
                mission_debug_log('Zapisano używając metody alternatywnej poprzez update_user_meta');
            } else {
                mission_debug_log('Szczegóły błędu update_user_meta', error_get_last());
            }
        }

        if (!$success) {
            // Ostatnia próba zapisu - inny prefiks
            $meta_key = 'user_' . $mission_field_key;
            $direct_success = update_user_meta($user_id, $meta_key, $mission_data);

            if ($direct_success !== false) {
                $success = true;
                mission_debug_log('Zapisano używając metody alternatywnej z prefiksem user_');
            }
        }

        mission_debug_log('Zapisuję misję: ' . $mission_field_key . ' dla użytkownika user_' . $user_id);
        if ($success) {
            mission_debug_log('Wynik zapisu misji: SUKCES');
        } else {
            global $wpdb;
            mission_debug_log('Wynik zapisu misji: BŁĄD');
            mission_debug_log('Ostatni błąd DB', $wpdb->last_error);
            mission_debug_log('Ostatni błąd PHP', error_get_last());
        }
    } catch (Exception $e) {
        mission_debug_log('Wyjątek podczas zapisu: ' . $e->getMessage());
        $success = false;
    }

    return [
        'success' => $success,
        'message' => $success ? 'Misja została przypisana' : 'Błąd podczas przypisywania misji',
        'first_task_id' => $first_task_id
    ];
}
