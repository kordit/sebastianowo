<?php

/**
 * Obsługa dialogów misji
 * 
 * @package Game
 */

if (!defined('ABSPATH')) {
    exit; // Zabezpieczenie przed bezpośrednim dostępem
}

/**
 * Inicjalizacja punktów zaczepienia (hooks) dla dialogów misji
 */
function init_mission_dialogs()
{
    add_action('wp_ajax_get_mission_dialog', 'ajax_get_mission_dialog');
    add_action('wp_ajax_update_mission_progress', 'ajax_update_mission_progress');
}
add_action('init', 'init_mission_dialogs');

/**
 * Sprawdza czy użytkownik ma aktywne misje z dialogami powiązanymi z danym NPC
 * Zwraca dane dla okna dialogowego misji
 */
function ajax_get_mission_dialog()
{
    // Sprawdzenie czy użytkownik jest zalogowany
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
        return;
    }

    // Pobierz ID NPC z żądania
    $npc_id = isset($_POST['npc_id']) ? intval($_POST['npc_id']) : 0;
    if (!$npc_id) {
        wp_send_json_error(['message' => 'Nie podano ID NPC']);
        return;
    }

    // Pobierz ID użytkownika
    $user_id = get_current_user_id();

    // Znajdź aktywne misje użytkownika, które zawierają ten NPC w warunkach dialogowych
    $active_missions = get_active_missions_for_user_with_dialog_npc($user_id, $npc_id);

    if (empty($active_missions)) {
        wp_send_json_error(['message' => 'Brak aktywnych misji dla tego NPC']);
        return;
    }

    // Wybieramy pierwszą aktywną misję z NPC
    $mission = $active_missions[0];

    // Przygotowujemy dane misji dla dialogu
    $mission_data = [
        'mission_id' => $mission->ID,
        'mission_title' => $mission->post_title,
        'dialog_task_name' => get_field('dialog_task_name', $mission->ID) ?: 'Przeprowadź dialog',
        'npc_id' => $npc_id,
        'npc_name' => get_the_title($npc_id)
    ];

    wp_send_json_success(['mission_data' => $mission_data]);
}

/**
 * Aktualizuje postęp misji - oznacza dialog z NPC jako przeprowadzony
 */
function ajax_update_mission_progress()
{
    // Sprawdzenie czy użytkownik jest zalogowany
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
        return;
    }

    // Pobierz ID NPC i misji
    $npc_id = isset($_POST['npc_id']) ? intval($_POST['npc_id']) : 0;
    $mission_id = isset($_POST['mission_id']) ? intval($_POST['mission_id']) : 0;

    if (!$npc_id || !$mission_id) {
        wp_send_json_error(['message' => 'Brakujące ID NPC lub misji']);
        return;
    }

    $user_id = get_current_user_id();

    // Pobierz aktualny postęp misji
    $mission_progress = get_user_meta($user_id, 'mission_' . $mission_id . '_dialogs', true);
    if (!is_array($mission_progress)) {
        $mission_progress = [];
    }

    // Dodaj NPC do listy, z którymi przeprowadzono dialog
    if (!in_array($npc_id, $mission_progress)) {
        $mission_progress[] = $npc_id;
        update_user_meta($user_id, 'mission_' . $mission_id . '_dialogs', $mission_progress);
    }

    // Sprawdź, czy zadanie zostało ukończone
    $completed = check_mission_dialog_completion($user_id, $mission_id);

    wp_send_json_success([
        'mission_id' => $mission_id,
        'npc_id' => $npc_id,
        'dialogs_completed' => count($mission_progress),
        'is_complete' => $completed
    ]);
}

/**
 * Znajduje aktywne misje użytkownika, które zawierają warunki dialogowe z danym NPC
 */
function get_active_missions_for_user_with_dialog_npc($user_id, $npc_id)
{
    $active_missions = get_posts(array(
        'post_type' => 'mission',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            // Można dodać filtry dla aktywnych misji użytkownika
        )
    ));

    $matching_missions = [];

    foreach ($active_missions as $mission) {
        // Sprawdzamy, czy misja ma warunek dialogu z tym NPC
        if (has_mission_dialog_with_npc($mission->ID, $npc_id)) {
            $matching_missions[] = $mission;
        }
    }

    return $matching_missions;
}

/**
 * Sprawdza, czy misja ma warunek dialogu z konkretnym NPC
 */
function has_mission_dialog_with_npc($mission_id, $npc_id)
{
    // Pobierz zadania misji
    $tasks = get_field('tasks', $mission_id);

    if (!$tasks || !is_array($tasks)) {
        return false;
    }

    foreach ($tasks as $task) {
        $conditions = isset($task['conditions']) ? $task['conditions'] : [];

        if (!is_array($conditions)) {
            continue;
        }

        foreach ($conditions as $condition) {
            // Sprawdź czy warunek to "przeprowadź dialog"
            if ($condition['acf_fc_layout'] === 'have_dialog') {
                $npc_ids = isset($condition['npc_ids']) ? $condition['npc_ids'] : [];

                // Sprawdź czy NPC jest w warunku dialogu
                if (is_array($npc_ids)) {
                    foreach ($npc_ids as $condition_npc_id) {
                        if (is_object($condition_npc_id) && isset($condition_npc_id->ID)) {
                            if ((int)$condition_npc_id->ID === (int)$npc_id) {
                                return true;
                            }
                        } elseif ((int)$condition_npc_id === (int)$npc_id) {
                            return true;
                        }
                    }
                } elseif ((int)$npc_ids === (int)$npc_id) {
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Sprawdza, czy zadanie dialogowe zostało ukończone
 */
function check_mission_dialog_completion($user_id, $mission_id)
{
    // Pobierz wszystkie zadania misji
    $tasks = get_field('tasks', $mission_id);

    if (!$tasks || !is_array($tasks)) {
        return false;
    }

    foreach ($tasks as $task) {
        $conditions = isset($task['conditions']) ? $task['conditions'] : [];

        if (!is_array($conditions)) {
            continue;
        }

        foreach ($conditions as $condition) {
            // Sprawdź zadanie "przeprowadź dialog"
            if ($condition['acf_fc_layout'] === 'have_dialog') {
                $required_count = isset($condition['dialog_count']) ? intval($condition['dialog_count']) : 1;

                // Pobierz postęp użytkownika
                $progress = get_user_meta($user_id, 'mission_' . $mission_id . '_dialogs', true);
                $completed_count = is_array($progress) ? count($progress) : 0;

                // Sprawdź, czy użytkownik przeprowadził wymaganą liczbę dialogów
                if ($completed_count >= $required_count) {
                    // Zaktualizuj status zadania i dodaj nagrody (opcjonalnie)
                    // update_mission_task_status($user_id, $mission_id, $task['id'], 'completed');
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    return false;
}
