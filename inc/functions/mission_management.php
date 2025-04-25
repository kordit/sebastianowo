<?php

/**
 * Funkcje zarządzania misjami dla użytkowników
 * 
 * @package Game
 */

if (!defined('ABSPATH')) {
    exit; // Zabezpieczenie przed bezpośrednim dostępem
}

/**
 * Inicjalizacja punktów zaczepienia dla zarządzania misjami
 */
function init_mission_management()
{
    add_action('wp_ajax_assign_mission_to_user', 'ajax_assign_mission_to_user');
    add_action('wp_ajax_complete_mission_task', 'ajax_complete_mission_task');
    add_action('wp_ajax_check_mission_task_status', 'ajax_check_mission_task_status');
}
add_action('init', 'init_mission_management');

/**
 * Przypisuje misję do użytkownika i ustawia pierwsze zadanie jako aktywne
 */
function ajax_assign_mission_to_user()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
        return;
    }

    // Sprawdzenie nonce (dodaj później)
    // if (!wp_verify_nonce($_POST['security'], 'mission_management_nonce')) {
    //     wp_send_json_error(['message' => 'Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.']);
    //     wp_die();
    // }

    $mission_id = isset($_POST['mission_id']) ? intval($_POST['mission_id']) : 0;
    $npc_id = isset($_POST['npc_id']) ? intval($_POST['npc_id']) : 0;

    if (!$mission_id) {
        wp_send_json_error(['message' => 'Nieprawidłowe ID misji']);
        return;
    }

    $user_id = get_current_user_id();

    // Sprawdź czy misja istnieje
    $mission = get_post($mission_id);
    if (!$mission || $mission->post_type !== 'mission') {
        wp_send_json_error(['message' => 'Misja nie istnieje']);
        return;
    }

    // Sprawdź czy misja nie jest już przypisana
    $result = assign_mission_to_user($user_id, $mission_id);

    if ($result['success']) {
        wp_send_json_success([
            'message' => 'Misja została przypisana do użytkownika',
            'mission_id' => $mission_id,
            'mission_title' => $mission->post_title,
            'task_id' => $result['first_task_id'],
            'npc_id' => $npc_id,
            'redirect' => home_url('/zadania/') // Opcjonalne przekierowanie
        ]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}

/**
 * Przypisuje misję do konkretnego użytkownika
 * 
 * @param int $user_id ID użytkownika
 * @param int $mission_id ID misji
 * @return array Informacje o wyniku operacji
 */
function assign_mission_to_user($user_id, $mission_id)
{
    // Pobierz całą grupę user_missions
    $user_missions = get_field('user_missions', 'user_' . $user_id);
    if (!is_array($user_missions)) $user_missions = [];
    if (!isset($user_missions['active_missions']) || !is_array($user_missions['active_missions'])) $user_missions['active_missions'] = [];
    if (!isset($user_missions['completed']) || !is_array($user_missions['completed'])) $user_missions['completed'] = [];

    // Sprawdź czy misja już jest przypisana jako aktywna
    if (in_array($mission_id, $user_missions['active_missions'])) {
        return [
            'success' => false,
            'message' => 'Misja jest już aktywna'
        ];
    }

    // Sprawdź czy misja została już ukończona
    if (in_array($mission_id, $user_missions['completed'])) {
        return [
            'success' => false,
            'message' => 'Ta misja została już ukończona'
        ];
    }

    // Dodaj misję do aktywnych
    $user_missions['active_missions'][] = $mission_id;
    $update_missions_success = update_field('user_missions', $user_missions, 'user_' . $user_id);

    // Dodaj postęp zadań do repeatera mission_tasks_progress
    $mission_tasks = get_field('mission_tasks', $mission_id);
    $first_task_id = null;
    $existing_tasks_progress = get_field('mission_tasks_progress', 'user_' . $user_id);
    if (!is_array($existing_tasks_progress)) $existing_tasks_progress = [];
    if (is_array($mission_tasks) && !empty($mission_tasks)) {
        foreach ($mission_tasks as $index => $task) {
            $task_id = isset($task['task_id']) ? $task['task_id'] : 'task_' . $index;
            if ($index === 0) $first_task_id = $task_id;
            $existing_tasks_progress[] = [
                'mission_id' => $mission_id,
                'task_id' => $task_id,
                'task_type' => $task['task_type'] ?? '',
                'completed' => 0,
                'completion_date' => '',
                'status' => $index === 0 ? 'in_progress' : 'not_started',
                'task_details' => [
                    [
                        'key' => 'assigned_at',
                        'value' => current_time('mysql')
                    ]
                ]
            ];
        }
    }
    $update_tasks_success = update_field('mission_tasks_progress', $existing_tasks_progress, 'user_' . $user_id);

    // POBIERZ JESZCZE RAZ user_missions po zapisie i sprawdź czy misja jest na liście
    $user_missions_after = get_field('user_missions', 'user_' . $user_id);
    $is_in = (is_array($user_missions_after) && isset($user_missions_after['active_missions']) && in_array($mission_id, $user_missions_after['active_missions']));

    // Debug log (możesz usunąć po testach)
    // error_log('user_missions_after: ' . print_r($user_missions_after, true));
    // error_log('update_field zwrócił: ' . var_export($update_missions_success, true));

    return [
        'success' => $is_in,
        'message' => $is_in ? 'Misja została przypisana' : 'Błąd podczas przypisywania misji',
        'first_task_id' => $first_task_id
    ];
}

/**
 * Oznacza zadanie misji jako ukończone
 */
function ajax_complete_mission_task()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
        return;
    }

    $mission_id = isset($_POST['mission_id']) ? intval($_POST['mission_id']) : 0;
    $task_id = isset($_POST['task_id']) ? sanitize_text_field($_POST['task_id']) : '';

    if (!$mission_id || !$task_id) {
        wp_send_json_error(['message' => 'Nieprawidłowe ID misji lub zadania']);
        return;
    }

    $user_id = get_current_user_id();
    $result = complete_mission_task($user_id, $mission_id, $task_id);

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}

/**
 * Sprawdza i aktualizuje stan zadania misji
 * 
 * @param int $user_id ID użytkownika
 * @param int $mission_id ID misji
 * @param string $task_id ID zadania
 * @return array Informacje o wyniku operacji
 */
function complete_mission_task($user_id, $mission_id, $task_id)
{
    // Pobierz aktywne misje użytkownika
    $active_missions = get_field('active_missions', 'user_' . $user_id);

    if (!is_array($active_missions)) {
        return [
            'success' => false,
            'message' => 'Brak aktywnych misji dla tego użytkownika'
        ];
    }

    $mission_index = -1;
    $mission_data = null;

    // Znajdź indeks misji w tablicy
    foreach ($active_missions as $index => $data) {
        $current_mission_id = is_object($data['mission']) ? $data['mission']->ID : $data['mission'];
        if ($current_mission_id == $mission_id) {
            $mission_index = $index;
            $mission_data = $data;
            break;
        }
    }

    if ($mission_index === -1) {
        return [
            'success' => false,
            'message' => 'Nie znaleziono wskazanej misji wśród aktywnych misji użytkownika'
        ];
    }

    // Znajdź zadanie i oznacz jako ukończone
    $task_found = false;
    $next_task_id = null;
    $all_tasks_completed = true;
    $task_index = -1;

    foreach ($mission_data['progress'] as $index => &$task) {
        if ($task['task_id'] == $task_id) {
            $task['completed'] = 1;
            $task['status'] = 'completed';
            $task['completed_at'] = current_time('mysql');
            $task_found = true;
            $task_index = $index;
        } elseif (!$task['completed']) {
            $all_tasks_completed = false;

            // Jeśli to następne zadanie po ukończonym, oznacz jako aktywne
            if ($task_found && $next_task_id === null) {
                $next_task_id = $task['task_id'];
                $task['status'] = 'in_progress';
            }
        }
    }

    if (!$task_found) {
        return [
            'success' => false,
            'message' => 'Nie znaleziono wskazanego zadania w misji'
        ];
    }

    // Aktualizuj misję
    $active_missions[$mission_index] = $mission_data;

    // Jeśli wszystkie zadania ukończone, przenieś misję do ukończonych
    if ($all_tasks_completed) {
        $active_missions[$mission_index]['status'] = 'completed';
        $active_missions[$mission_index]['completed_at'] = current_time('mysql');

        // Opcjonalnie: przenieś misję do ukończonych
        move_mission_to_completed($user_id, $mission_id);
    }

    // Zapisz zmiany
    $update_success = update_field('active_missions', $active_missions, 'user_' . $user_id);

    return [
        'success' => $update_success,
        'message' => 'Zadanie zostało oznaczone jako ukończone',
        'next_task_id' => $next_task_id,
        'all_tasks_completed' => $all_tasks_completed
    ];
}

/**
 * Przenosi misję z aktywnych do ukończonych
 * 
 * @param int $user_id ID użytkownika
 * @param int $mission_id ID misji
 * @return bool Sukces operacji
 */
function move_mission_to_completed($user_id, $mission_id)
{
    // Pobierz aktywne misje użytkownika
    $active_missions = get_field('active_missions', 'user_' . $user_id);
    $completed_missions = get_field('completed_missions', 'user_' . $user_id);

    if (!is_array($active_missions)) {
        return false;
    }

    if (!is_array($completed_missions)) {
        $completed_missions = [];
    }

    // Znajdź misję do przeniesienia
    $mission_to_move = null;
    $updated_active_missions = [];

    foreach ($active_missions as $mission_data) {
        $current_mission_id = is_object($mission_data['mission']) ? $mission_data['mission']->ID : $mission_data['mission'];

        if ($current_mission_id == $mission_id) {
            $mission_to_move = get_post($mission_id);
        } else {
            $updated_active_missions[] = $mission_data;
        }
    }

    if (!$mission_to_move) {
        return false;
    }

    // Dodaj misję do ukończonych
    $completed_missions[] = $mission_to_move;

    // Zaktualizuj dane użytkownika
    $update_active = update_field('active_missions', $updated_active_missions, 'user_' . $user_id);
    $update_completed = update_field('completed_missions', $completed_missions, 'user_' . $user_id);

    return $update_active && $update_completed;
}

/**
 * Sprawdza stan zadania misji (czy jest wykonane)
 */
function ajax_check_mission_task_status()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
        return;
    }

    $mission_id = isset($_POST['mission_id']) ? intval($_POST['mission_id']) : 0;
    $task_id = isset($_POST['task_id']) ? sanitize_text_field($_POST['task_id']) : '';

    if (!$mission_id || !$task_id) {
        wp_send_json_error(['message' => 'Nieprawidłowe ID misji lub zadania']);
        return;
    }

    $user_id = get_current_user_id();
    $result = check_mission_task_status($user_id, $mission_id, $task_id);

    wp_send_json_success($result);
}

/**
 * Sprawdza stan zadania misji
 * 
 * @param int $user_id ID użytkownika
 * @param int $mission_id ID misji
 * @param string $task_id ID zadania
 * @return array Informacje o stanie zadania
 */
function check_mission_task_status($user_id, $mission_id, $task_id)
{
    // Pobierz aktywne misje użytkownika
    $active_missions = get_field('active_missions', 'user_' . $user_id);

    if (!is_array($active_missions)) {
        return [
            'exists' => false,
            'completed' => false,
            'active' => false,
            'message' => 'Brak aktywnych misji dla tego użytkownika'
        ];
    }

    // Znajdź misję
    foreach ($active_missions as $mission_data) {
        $current_mission_id = is_object($mission_data['mission']) ? $mission_data['mission']->ID : $mission_data['mission'];

        if ($current_mission_id == $mission_id) {
            // Znajdź zadanie
            foreach ($mission_data['progress'] as $task) {
                if ($task['task_id'] == $task_id) {
                    return [
                        'exists' => true,
                        'completed' => (bool)$task['completed'],
                        'active' => (bool)($task['active'] ?? false),
                        'message' => $task['completed'] ? 'Zadanie zostało ukończone' : 'Zadanie w trakcie realizacji'
                    ];
                }
            }

            return [
                'exists' => false,
                'completed' => false,
                'active' => false,
                'message' => 'Nie znaleziono zadania w tej misji'
            ];
        }
    }

    return [
        'exists' => false,
        'completed' => false,
        'active' => false,
        'message' => 'Nie znaleziono misji'
    ];
}

/**
 * Funkcja pomocnicza do sprawdzania zadania typu "item" (odbiór przedmiotu)
 *
 * @param int $user_id ID użytkownika
 * @param int $mission_id ID misji
 * @param string $task_id ID zadania
 * @return bool Czy zadanie zostało wykonane
 */
function check_item_task_completion($user_id, $mission_id, $task_id)
{
    // Pobierz szczegóły misji
    $mission_tasks = get_field('mission_tasks', $mission_id);

    if (!is_array($mission_tasks)) {
        return false;
    }

    // Znajdź zadanie po ID
    $task_data = null;
    foreach ($mission_tasks as $task) {
        if (isset($task['task_id']) && $task['task_id'] === $task_id) {
            $task_data = $task;
            break;
        }
    }

    if (!$task_data || $task_data['task_type'] !== 'item') {
        return false;
    }

    // Sprawdź, czy użytkownik ma wymagany przedmiot
    $item_id = $task_data['task_item']['task_item_id'] ?? 0;
    $required_count = $task_data['task_item']['task_item_count'] ?? 1;

    if (!$item_id) {
        return false;
    }

    // Pobierz przedmioty użytkownika
    $user_items = get_field('items', 'user_' . $user_id);

    if (!is_array($user_items)) {
        return false;
    }

    // Sprawdź, czy użytkownik ma wystarczającą ilość przedmiotu
    foreach ($user_items as $user_item) {
        $current_item_id = isset($user_item['item']) ?
            (is_object($user_item['item']) ? $user_item['item']->ID : $user_item['item']) : 0;

        if ($current_item_id == $item_id) {
            $quantity = intval($user_item['quantity'] ?? 0);
            return $quantity >= $required_count;
        }
    }

    return false;
}

/**
 * Funkcja pomocnicza do sprawdzania zadania typu "sell" (sprzedaż przedmiotu)
 * 
 * @param int $user_id ID użytkownika
 * @param int $mission_id ID misji
 * @param string $task_id ID zadania
 * @param int $npc_id ID NPC, któremu sprzedano przedmiot
 * @return bool Czy zadanie zostało wykonane
 */
function check_sell_task_completion($user_id, $mission_id, $task_id, $npc_id = null)
{
    // Implementacja sprawdzania, czy zadanie sprzedaży zostało wykonane
    // Ta funkcja powinna być wywoływana po sprzedaży przedmiotu do NPC

    return true; // Uproszczone dla tego przykładu
}
