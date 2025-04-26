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
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
    }

    $mission_id = isset($_POST['mission_id']) ? intval($_POST['mission_id']) : 0;
    if (!$mission_id) {
        wp_send_json_error(['message' => 'Nieprawidłowe ID misji']);
    }

    mission_debug_log('Przetwarzanie misji ID', $mission_id);

    $mission = get_post($mission_id);
    if (!$mission || $mission->post_type !== 'mission') {
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


    // Przygotowanie odpowiedzi
    $response = [
        'mission_id' => $mission_id,
        'mission_title' => $mission->post_title,
        'mission_description' => $mission->post_content,
        'task_id' => $task_id_to_return,
        'task_name' => $task_name
    ];


    // Uproszczona odpowiedź JSON - użyj task_id z przycisku zamiast z bazy danych
    wp_send_json_success($response);
}

/**
 * Przypisuje misję do użytkownika (AJAX)
 */
function ajax_assign_mission_to_user()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
        return;
    }

    $mission_id = isset($_POST['mission_id']) ? intval($_POST['mission_id']) : 0;
    $npc_id = isset($_POST['npc_id']) ? intval($_POST['npc_id']) : 0;
    $mission_status = isset($_POST['mission_status']) ? sanitize_text_field($_POST['mission_status']) : 'in_progress';
    $mission_task_id = isset($_POST['mission_task_id']) ? sanitize_text_field($_POST['mission_task_id']) : null;
    $mission_task_status = isset($_POST['mission_task_status']) ? sanitize_text_field($_POST['mission_task_status']) : 'in_progress';


    if (!$mission_id) {
        mission_debug_log('BŁĄD: Nieprawidłowe ID misji');
        wp_send_json_error(['message' => 'Nieprawidłowe ID misji']);
        return;
    }

    $user_id = get_current_user_id();


    $mission = get_post($mission_id);
    if (!$mission || $mission->post_type !== 'mission') {
        mission_debug_log('BŁĄD: Misja o ID ' . $mission_id . ' nie istnieje');
        wp_send_json_error(['message' => 'Misja nie istnieje']);
        return;
    }

    $result = assign_mission_to_user($user_id, $mission_id, $mission_status, $mission_task_id, $mission_task_status, $npc_id);

    if ($result['success']) {
        $response = [
            'message' => 'Misja została zaktualizowana',
            'mission_id' => $mission_id,
            'mission_title' => $mission->post_title,
            'task_id' => $result['first_task_id'],
            'npc_id' => $npc_id
        ];


        wp_send_json_success($response);
    } else {

        wp_send_json_error(['message' => $result['message']]);
    }
}

/**
 * Przypisuje lub aktualizuje misję dla użytkownika
 * 
 * @param int    $user_id           ID użytkownika
 * @param int    $mission_id        ID misji
 * @param string $mission_status    Status misji (in_progress, completed, failed, not_started)
 * @param string $mission_task_id   ID zadania w misji (opcjonalne)
 * @param string $mission_task_status Status zadania (in_progress, completed, failed, not_started)
 * @param int    $npc_id            ID NPC (opcjonalne)
 * @return array Rezultat operacji
 */
function assign_mission_to_user($user_id, $mission_id, $mission_status = 'in_progress', $mission_task_id = null, $mission_task_status = 'in_progress', $npc_id = 0)
{
    // 1. Klucz misji i pobieranie istniejącej misji
    $mission_meta_key = 'mission_' . $mission_id;
    $existing_mission = get_field($mission_meta_key, 'user_' . $user_id);

    // 2. Przygotowanie podstawowej struktury misji
    $mission_data = is_array($existing_mission) ? $existing_mission : [
        'status' => 'not_started',
        'assigned_date' => '',
        'completion_date' => '',
        'tasks' => []
    ];

    // 3. Ustawienie statusu misji
    $mission_data['status'] = $mission_status;

    // 4. Ustawienie daty przypisania (tylko jeśli nie była ustawiona)
    if (empty($mission_data['assigned_date'])) {
        $mission_data['assigned_date'] = current_time('mysql');
    }

    // 5. Ustawienie daty zakończenia (jeśli status to completed)
    if ($mission_status === 'completed') {
        $mission_data['completion_date'] = current_time('mysql');
    }

    // 6. Obsługa zadania
    $first_task_id = null;

    // Sprawdzanie statusów NPC
    $npc_status = null;
    $updated_npc = false;

    if ($mission_task_status === 'completed_npc' || $mission_task_status === 'failed_npc') {
        if ($npc_id > 0) {
            $npc_status = ($mission_task_status === 'completed_npc') ? 'completed' : 'failed';
            $updated_npc = true;
            $mission_task_status = 'in_progress'; // Zadanie pozostaje in_progress
            mission_debug_log('Aktualizacja statusu NPC ' . $npc_id . ' na ' . $npc_status . ' dla zadania ' . $mission_task_id);
        } else {
            $mission_task_status = 'in_progress';
            mission_debug_log('Zmieniono status z ' . $mission_task_status . ' na in_progress dla zadania ' . $mission_task_id . ' (brak ID NPC)');
        }
    }

    if ($mission_task_id) {
        // Jeśli podano ID zadania, aktualizujemy je
        if (!isset($mission_data['tasks']) || !is_array($mission_data['tasks'])) {
            $mission_data['tasks'] = [];
        }

        // Aktualizacja lub dodanie zadania
        if (!isset($mission_data['tasks'][$mission_task_id])) {
            $mission_data['tasks'][$mission_task_id] = [
                'status' => $mission_task_status,
                'start_date' => current_time('mysql')
            ];
        } else {
            $mission_data['tasks'][$mission_task_id]['status'] = $mission_task_status;

            // Aktualizacja statusu dla konkretnego NPC, jeśli potrzebne
            if ($updated_npc && $npc_id > 0) {
                $npc_key = 'npc_' . $npc_id;
                $mission_data['tasks'][$mission_task_id][$npc_key] = $npc_status;
                mission_debug_log('Zaktualizowano status NPC ' . $npc_id . ' na ' . $npc_status . ' dla zadania ' . $mission_task_id, $mission_data['tasks'][$mission_task_id]);
            }
        }

        $first_task_id = $mission_task_id;
    } else {
        // Jeśli nie podano ID zadania, używamy pierwszego zadania z misji
        $mission_tasks = get_field('mission_tasks', $mission_id);

        if (is_array($mission_tasks) && !empty($mission_tasks)) {
            $first_task = $mission_tasks[0];
            $first_task_id = isset($first_task['task_id']) ? $first_task['task_id'] : 'task_0';

            if (!isset($mission_data['tasks'][$first_task_id])) {
                $mission_data['tasks'][$first_task_id] = [
                    'status' => $mission_task_status,
                    'start_date' => current_time('mysql')
                ];
            } else {
                $mission_data['tasks'][$first_task_id]['status'] = $mission_task_status;
            }
        }
    }

    // 7. Zapisanie danych
    // Używamy bezpośrednio update_user_meta, które jest bardziej niezawodne niż update_field
    $success = update_user_meta($user_id, $mission_meta_key, $mission_data);

    // 8. Zapewnienie kompatybilności z ACF i czyszczenie cache
    update_field($mission_meta_key, $mission_data, 'user_' . $user_id);

    // 9. Usuwamy cache, by zmiany były od razu widoczne
    clean_user_cache($user_id);
    wp_cache_delete($user_id, 'user_meta');

    // 10. Zwracamy wynik
    return [
        'success' => $success,
        'message' => $success ? 'Misja została zaktualizowana' : 'Błąd podczas aktualizacji misji',
        'first_task_id' => $first_task_id
    ];
}
