<?php

/**
 * Obsługa AJAX dla systemu misji
 * 
 * Ten plik zawiera wszystkie funkcje AJAX związane z misjami w grze
 */

// Funkcja pobierająca wszystkie aktywne misje użytkownika
function get_user_active_missions_ajax()
{
    // Tymczasowo wyłączamy raportowanie błędów dla debugowania
    $original_error_reporting = error_reporting();
    error_reporting(0);

    try {
        // Pobierz ID zalogowanego użytkownika
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany'], 401);
            exit;
        }

        // Ignorujemy sprawdzanie nonce na razie, aby wykluczyć inne problemy
        // Pobierz aktywne misje użytkownika
        $active_missions = [];
        $active_mission_ids = [];

        if (function_exists('get_field')) {
            $active_mission_ids = get_field('active_missions', 'user_' . $user_id);
        }

        // Jeśli nie ma aktywnych misji lub funkcja get_field nie jest dostępna
        if (!is_array($active_mission_ids)) {
            $active_mission_ids = [];
        }

        if (!empty($active_mission_ids)) {
            foreach ($active_mission_ids as $mission_id) {
                // Upewnij się, że mission_id jest liczbą
                $mission_id = intval($mission_id);
                if (!$mission_id) continue;

                // Pobierz podstawowe dane wpisu zamiast pełnych metadanych
                $mission_post = get_post($mission_id);
                if (!$mission_post) continue;

                // Zbierz tylko podstawowe informacje o misji
                $mission_status = '';
                $mission_tasks = [];

                if (function_exists('get_field')) {
                    $mission_status = get_field('mission_status', $mission_id);
                    $mission_tasks = get_field('mission_tasks', $mission_id);
                }

                $active_missions[$mission_id] = [
                    'id' => $mission_id,
                    'title' => $mission_post->post_title,
                    'status' => $mission_status ?: 'in_progress',
                    'tasks' => is_array($mission_tasks) ? $mission_tasks : [],
                ];
            }
        }

        // Przywróć oryginalne ustawienie raportowania błędów
        error_reporting($original_error_reporting);

        // Zwróć informacje o misjach
        wp_send_json_success([
            'missions' => $active_missions
        ]);
        exit;
    } catch (Exception $e) {
        // Obsługa wyjątków - zapisz błąd i zwróć informację
        error_log('Błąd w get_user_active_missions_ajax: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'Wystąpił błąd serwera',
            'debug' => WP_DEBUG ? $e->getMessage() : null
        ], 500);
        exit;
    }
}

// Rejestracja funkcji obsługującej akcję AJAX
add_action('wp_ajax_get_user_active_missions', 'get_user_active_missions_ajax');

// Funkcja do obsługi zmiany statusu misji
function update_mission_status_ajax()
{
    // Sprawdź token bezpieczeństwa
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mission_ajax_nonce')) {
        wp_send_json_error(['message' => 'Nieprawidłowy token bezpieczeństwa'], 403);
        exit;
    }

    // Sprawdź wymagane parametry
    if (!isset($_POST['mission_id']) || !isset($_POST['status'])) {
        wp_send_json_error(['message' => 'Brak wymaganych parametrów'], 400);
        exit;
    }

    $mission_id = intval($_POST['mission_id']);
    $new_status = sanitize_text_field($_POST['status']);

    // Aktualizuj status misji
    update_field('mission_status', $new_status, $mission_id);

    wp_send_json_success([
        'message' => 'Status misji zaktualizowany',
        'mission_id' => $mission_id,
        'status' => $new_status
    ]);
    exit;
}

add_action('wp_ajax_update_mission_status', 'update_mission_status_ajax');
