<?php

/**
 * Endpoint REST API do aktualizacji statystyk użytkownika
 */

if (!defined('ABSPATH')) {
    exit; // Zabezpieczenie przed bezpośrednim dostępem
}

/**
 * Rejestracja endpointu REST API do aktualizacji statystyk użytkownika
 */
function register_upgrade_stat_endpoint()
{
    register_rest_route('game/v1', '/upgrade-stat', [
        'methods' => 'POST',
        'callback' => 'handle_upgrade_user_stat',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ]);
}
add_action('rest_api_init', 'register_upgrade_stat_endpoint');

/**
 * Obsługa żądania aktualizacji statystyk użytkownika
 * Używa ManagerUser do zarządzania punktami
 * 
 * @param WP_REST_Request $request Obiekt żądania
 * @return WP_REST_Response
 */
function handle_upgrade_user_stat($request)
{
    // Pobierz dane z żądania
    $stat_name = $request->get_param('stat_name');

    // Sprawdź czy statystyka została określona
    if (empty($stat_name)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Nie określono statystyki do aktualizacji.'
        ], 400);
    }

    // Inicjalizuj ManagerUser
    $user_manager = new ManagerUser();

    // Sprawdź dostępność punktów nauki
    $learning_points_check = $user_manager->checkResourceAvailability('progress_learning_points', 1);

    if ($learning_points_check !== true) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $learning_points_check['message'] ?? 'Brak dostępnych punktów nauki.'
        ], 400);
    }

    // 1. Odejmij punkt nauki
    $remove_learning_point = $user_manager->updateProgress('learning_points', -1);

    if (!$remove_learning_point['success']) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $remove_learning_point['message'] ?? 'Nie udało się odjąć punktu nauki.'
        ], 400);
    }

    // 2. Dodaj punkt do wybranej statystyki
    $add_stat_point = $user_manager->updateStat($stat_name, 1);

    if (!$add_stat_point['success']) {
        // W przypadku błędu przywróć punkt nauki
        $user_manager->updateProgress('learning_points', 1);

        return new WP_REST_Response([
            'success' => false,
            'message' => $add_stat_point['message'] ?? 'Nie udało się zaktualizować statystyki.'
        ], 400);
    }

    // Pobierz aktualną wartość statystyki
    $current_stat_value = $user_manager->getFieldValue('stats_' . $stat_name);

    // Wszystko poszło dobrze, zwróć sukces
    return new WP_REST_Response([
        'success' => true,
        'message' => 'Statystyka została zaktualizowana pomyślnie.',
        'new_stat_value' => $current_stat_value,
        'remaining_points' => $remove_learning_point['new_value']
    ], 200);
}
