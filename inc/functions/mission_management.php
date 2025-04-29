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
    mission_debug_log('====== ROZPOCZĘCIE AJAX_ASSIGN_MISSION_TO_USER ======');
    mission_debug_log('Wszystkie dane POST: ', $_POST);

    if (!is_user_logged_in()) {
        mission_debug_log('BŁĄD: Użytkownik nie jest zalogowany');
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
        return;
    }

    $mission_id = isset($_POST['mission_id']) ? intval($_POST['mission_id']) : 0;
    $npc_id = isset($_POST['npc_id']) ? intval($_POST['npc_id']) : 0;

    // Dodajemy logowanie wartości npc_id dla łatwiejszego debugowania
    mission_debug_log('Otrzymana wartość mission_id: ', $_POST['mission_id'] ?? 'brak');
    mission_debug_log('Po konwersji mission_id: ', $mission_id);
    mission_debug_log('Otrzymana wartość npc_id: ', $_POST['npc_id'] ?? 'brak');
    mission_debug_log('Po konwersji npc_id: ', $npc_id);

    $mission_status = isset($_POST['mission_status']) ? sanitize_text_field($_POST['mission_status']) : 'in_progress';
    $mission_task_id = isset($_POST['mission_task_id']) ? sanitize_text_field($_POST['mission_task_id']) : null;
    $mission_task_status = isset($_POST['mission_task_status']) ? sanitize_text_field($_POST['mission_task_status']) : 'in_progress';

    mission_debug_log('mission_status: ', $mission_status);
    mission_debug_log('mission_task_id: ', $mission_task_id);
    mission_debug_log('mission_task_status: ', $mission_task_status);


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
        // Pobierz numer z task_id, jeśli istnieje (np. "dojedz-do-sebastianowa_2" -> "2")
        $task_num = '';
        if ($mission_task_id && preg_match('/_(\d+)$/', $mission_task_id, $matches)) {
            $task_num = $matches[1];
        }

        // Określ sekwencje zadań w misji, aby front-end mógł lepiej obsłużyć powiadomienia
        $mission_tasks = get_field('mission_tasks', $mission_id);
        $task_sequence = [];

        if (is_array($mission_tasks)) {
            foreach ($mission_tasks as $index => $task) {
                $task_id = isset($task['task_id']) ? $task['task_id'] : '';
                $task_title = isset($task['task_title']) ? $task['task_title'] : 'Zadanie ' . ($index + 1);

                if (!empty($task_id)) {
                    $task_sequence[] = [
                        'id' => $task_id,
                        'title' => $task_title,
                        'position' => $index
                    ];
                }
            }
        }

        // Rozszerz odpowiedź o dodatkowe informacje
        $response = [
            'message' => $result['message'], // Używamy komunikatu z funkcji assign_mission_to_user
            'mission_id' => $mission_id,
            'mission_title' => $mission->post_title,
            'task_id' => $result['first_task_id'],
            'task_num' => $task_num,
            'task_name' => $result['task_name'], // Dodajemy nazwę zadania
            'task_sequence' => $task_sequence,
            'npc_id' => $npc_id,
            'messages' => $result['messages'] // Przekaż tablicę wszystkich komunikatów
        ];

        mission_debug_log('Odpowiedź dla sukcesu misji:', $response);
        wp_send_json_success($response);
    } else {
        mission_debug_log('Błąd podczas przypisywania misji:', $result['message']);
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
    mission_debug_log('====== ROZPOCZĘCIE ASSIGN_MISSION_TO_USER ======');
    mission_debug_log('Parametry: user_id=' . $user_id . ', mission_id=' . $mission_id . ', mission_status=' . $mission_status . ', mission_task_id=' . $mission_task_id . ', mission_task_status=' . $mission_task_status . ', npc_id=' . $npc_id);

    // Sprawdzenie typów parametrów
    mission_debug_log('Typy parametrów: user_id=' . gettype($user_id) . ', mission_id=' . gettype($mission_id) .
        ', mission_task_id=' . gettype($mission_task_id) . ', npc_id=' . gettype($npc_id));

    // 1. Klucz misji i pobieranie istniejącej misji
    $mission_meta_key = 'mission_' . $mission_id;
    mission_debug_log('Klucz meta dla misji: ' . $mission_meta_key);

    $existing_mission = get_field($mission_meta_key, 'user_' . $user_id);
    mission_debug_log('Istniejąca misja: ', $existing_mission);

    // 2. Przygotowanie podstawowej struktury misji
    $mission_data = is_array($existing_mission) ? $existing_mission : [
        'status' => 'not_started',
        'assigned_date' => '',
        'completion_date' => '',
        'tasks' => []
    ];
    mission_debug_log('Przygotowana struktura misji: ', $mission_data);

    // Zapewnienie, że tablica tasks istnieje
    if (!isset($mission_data['tasks']) || !is_array($mission_data['tasks'])) {
        $mission_data['tasks'] = [];
        mission_debug_log('Zainicjowano pustą tablicę tasks dla misji ' . $mission_id);
    }

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

    // Obsługa specjalnych statusów NPC
    $updated_npc = false;
    $npc_status = null;

    // Sprawdzanie statusów NPC
    if ($mission_task_status === 'completed_npc' || $mission_task_status === 'failed_npc') {
        $mission_task_status = ($mission_task_status === 'completed_npc') ? 'completed' : 'failed';
        if ($npc_id > 0) {
            $npc_status = $mission_task_status;
            $updated_npc = true;
            mission_debug_log('Aktualizacja statusu NPC ' . $npc_id . ' na ' . $npc_status . ' dla zadania ' . $mission_task_id);
        }
    }

    if ($mission_task_id) {
        // Jeśli podano ID zadania, aktualizujemy je
        if (!isset($mission_data['tasks']) || !is_array($mission_data['tasks'])) {
            $mission_data['tasks'] = [];
        }

        // Debugowanie struktury zadania
        if (isset($mission_data['tasks'][$mission_task_id])) {
            mission_debug_log('Struktura istniejącego zadania ' . $mission_task_id . ':', $mission_data['tasks'][$mission_task_id]);
        }

        // Sprawdzanie czy zadanie jest stringiem zamiast tablicy i naprawianie
        if (isset($mission_data['tasks'][$mission_task_id]) && !is_array($mission_data['tasks'][$mission_task_id])) {
            $old_status = $mission_data['tasks'][$mission_task_id];
            mission_debug_log('Naprawianie nieprawidłowej struktury zadania ' . $mission_task_id . ' (było: ' . $old_status . ')');
            $mission_data['tasks'][$mission_task_id] = [
                'status' => $old_status,
                'start_date' => current_time('mysql')
            ];
        }

        // Aktualizacja lub dodanie zadania
        if (!isset($mission_data['tasks'][$mission_task_id])) {
            $mission_data['tasks'][$mission_task_id] = [
                'status' => $mission_task_status,
                'start_date' => current_time('mysql')
            ];

            // Jeśli tworzymy nowe zadanie i podany jest NPC, ustawiamy status NPC
            if ($npc_id > 0) {
                if ($updated_npc) {
                    // Użyj określonego statusu NPC
                    $npc_key = 'npc_' . $npc_id;
                    $mission_data['tasks'][$mission_task_id][$npc_key] = $npc_status;
                    mission_debug_log('Utworzono nowe zadanie, ustawiono status NPC ' . $npc_id . ' na ' . $npc_status);
                } else {
                    // Domyślnie ustaw not_started dla nowego NPC
                    $npc_key = 'npc_' . $npc_id;
                    $mission_data['tasks'][$mission_task_id][$npc_key] = 'not_started';
                    mission_debug_log('Utworzono nowe zadanie, ustawiono status NPC ' . $npc_id . ' na not_started');
                }
            }

            // Pobierz informacje o misji, aby sprawdzić czy zadanie ma innych NPC
            $mission_tasks = get_field('mission_tasks', $mission_id);
            if (is_array($mission_tasks)) {
                foreach ($mission_tasks as $task) {
                    $task_id = isset($task['task_id']) ? $task['task_id'] : '';
                    if ($task_id === $mission_task_id && isset($task['task_type']) && $task['task_type'] === 'checkpoint_npc') {
                        if (!empty($task['task_checkpoint_npc']) && is_array($task['task_checkpoint_npc'])) {
                            // Ustaw status wszystkich NPC w zadaniu jako not_started
                            foreach ($task['task_checkpoint_npc'] as $npc_info) {
                                if (!empty($npc_info['npc'])) {
                                    $task_npc_id = $npc_info['npc'];
                                    $task_npc_key = 'npc_' . $task_npc_id;
                                    // Nie nadpisujemy już ustawionego NPC
                                    if (!isset($mission_data['tasks'][$mission_task_id][$task_npc_key])) {
                                        $mission_data['tasks'][$mission_task_id][$task_npc_key] = 'not_started';
                                        mission_debug_log('Ustawiono status NPC ' . $task_npc_id . ' na not_started w nowym zadaniu');
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
            }
        } else {
            // Jeśli zadanie już istnieje, aktualizujemy jego status
            $mission_data['tasks'][$mission_task_id]['status'] = $mission_task_status;

            // Jeśli podano NPC, zaktualizuj jego status
            if ($npc_id > 0 && $updated_npc) {
                $npc_key = 'npc_' . $npc_id;
                $mission_data['tasks'][$mission_task_id][$npc_key] = $npc_status;
                mission_debug_log('Zaktualizowano status NPC ' . $npc_id . ' na ' . $npc_status . ' dla zadania ' . $mission_task_id);
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

    // 7. Generowanie komunikatów o zmianach
    $messages = [];
    $mission_post = get_post($mission_id);
    $mission_title = $mission_post ? $mission_post->post_title : 'Nieznana misja';

    // Mapowanie technicznych nazw statusów na przyjazne dla użytkownika
    $status_friendly_names = [
        'not_started' => 'Niezaczęta',
        'in_progress' => 'W trakcie',
        'completed' => 'Ukończona',
        'failed' => 'Nieudana',
        'progress_npc' => 'W trakcie'
    ];

    // Mapowanie technicznych nazw statusów zadań na przyjazne dla użytkownika
    $task_status_friendly_names = [
        'not_started' => 'Niezaczęte',
        'in_progress' => 'W trakcie',
        'completed' => 'Ukończone',
        'failed' => 'Nieudane',
        'progress_npc' => 'W trakcie'
    ];

    // Sprawdź czy zmienił się status misji
    if (isset($existing_mission['status']) && $existing_mission['status'] !== $mission_data['status']) {
        // Użyj przyjaznej nazwy statusu
        $friendly_status = isset($status_friendly_names[$mission_data['status']]) ?
            $status_friendly_names[$mission_data['status']] : $mission_data['status'];

        $messages[] = "Misja \"{$mission_title}\" jest teraz {$friendly_status}";
    }

    // Sprawdź czy zmienił się status zadania
    if ($mission_task_id) {
        // Pobierz nazwę zadania
        $task_name = 'Nieznane zadanie';
        $mission_tasks = get_field('mission_tasks', $mission_id);

        mission_debug_log('Szukam zadania o ID: ' . $mission_task_id);
        mission_debug_log('Zadania misji:', $mission_tasks);

        // Wyodrębnij numer zadania z ID zadania (np. z "dojedz-do-sebastianowa_2" -> 2)
        $task_num = '';
        if (preg_match('/_(\d+)$/', $mission_task_id, $matches)) {
            $task_num = $matches[1];
        }

        if (is_array($mission_tasks)) {
            foreach ($mission_tasks as $index => $task) {
                $task_id = isset($task['task_id']) ? $task['task_id'] : '';
                mission_debug_log('Porównuję task_id: ' . $task_id . ' z mission_task_id: ' . $mission_task_id);

                // Usuń potencjalne numery na końcu ID zadań (np. _0)
                $clean_task_id = preg_replace('/_\d+$/', '', $task_id);
                $clean_mission_task_id = preg_replace('/_\d+$/', '', $mission_task_id);

                mission_debug_log('Po oczyszczeniu: ' . $clean_task_id . ' vs ' . $clean_mission_task_id);

                if (
                    $task_id === $mission_task_id ||
                    $clean_task_id === $clean_mission_task_id ||
                    strpos($task_id, $clean_mission_task_id) !== false ||
                    strpos($mission_task_id, $clean_task_id) !== false
                ) {
                    $task_name = isset($task['task_title']) && !empty($task['task_title']) ?
                        $task['task_title'] : $task_name;
                    mission_debug_log('Znaleziono zadanie: ' . $task_name);
                    break;
                }
            }
        }

        // Pobierz czyste ID zadania bez numerów i podkreślnika na końcu
        $clean_display_id = preg_replace('/_\d+$/', '', $mission_task_id);

        // Pobierz przyjazną nazwę statusu zadania
        $friendly_task_status = isset($task_status_friendly_names[$mission_task_status]) ?
            $task_status_friendly_names[$mission_task_status] : $mission_task_status;

        // Sprawdź czy zadanie istniało wcześniej i czy zmienił się jego status
        if (!isset($existing_mission['tasks'][$mission_task_id])) {
            // Nowe zadanie
            $messages[] = "Zadanie \"{$clean_display_id}\" zostało rozpoczęte";
        } elseif (
            is_array($existing_mission['tasks'][$mission_task_id]) &&
            isset($existing_mission['tasks'][$mission_task_id]['status']) &&
            $existing_mission['tasks'][$mission_task_id]['status'] !== $mission_task_status
        ) {
            // Zmienił się status zadania (tablica)
            $messages[] = "Zadanie \"{$clean_display_id}\" jest teraz {$friendly_task_status}";

            // Dodaj ID zadania i identyfikator misji do odpowiedzi
            $task_data = [
                'task_id' => $mission_task_id,
                'task_name' => $task_name,
                'task_num' => $task_num,
                'status' => $mission_task_status
            ];
        } elseif (
            !is_array($existing_mission['tasks'][$mission_task_id]) &&
            $existing_mission['tasks'][$mission_task_id] !== $mission_task_status
        ) {
            // Zmienił się status zadania (string)
            $messages[] = "Zadanie \"{$clean_display_id}\" jest teraz {$friendly_task_status}";
        }
    }

    // Jeśli nie wykryto zmian, dodaj ogólny komunikat
    if (empty($messages)) {
        $messages[] = "Aktualizacja misji \"{$mission_title}\" zakończona pomyślnie";
    }

    // Poprawienie formatu komunikatu - usuń nazwę misji w komunikacie
    if (count($messages) > 1 && isset($existing_mission['status']) && $existing_mission['status'] !== $mission_data['status']) {
        // Jeśli mamy komunikat o misji i zadaniu, uprość komunikat o misji
        foreach ($messages as $key => $message) {
            if (strpos($message, 'Misja "') === 0) {
                $friendly_status = isset($status_friendly_names[$mission_data['status']]) ?
                    $status_friendly_names[$mission_data['status']] : $mission_data['status'];
                $messages[$key] = "{$friendly_status}";
                break;
            }
        }
    }

    $final_message = implode('. ', $messages);

    // 8. Zapisanie danych
    // Używamy bezpośrednio update_user_meta, które jest bardziej niezawodne niż update_field
    $success = update_user_meta($user_id, $mission_meta_key, $mission_data);

    // 9. Zapewnienie kompatybilności z ACF i czyszczenie cache
    update_field($mission_meta_key, $mission_data, 'user_' . $user_id);

    // 10. Usuwamy cache, by zmiany były od razu widoczne
    clean_user_cache($user_id);
    wp_cache_delete($user_id, 'user_meta');

    // 11. Zwracamy wynik z dodatkowymi informacjami
    return [
        'success' => $success,
        'message' => $success ? $final_message : 'Błąd podczas aktualizacji misji',
        'first_task_id' => $first_task_id,
        'task_name' => $task_name ?? 'Nieznane zadanie', // Dodajemy nazwę zadania
        'messages' => $messages // Dołączamy tablicę wszystkich komunikatów
    ];
}
