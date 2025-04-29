<?php

/**
 * Obsługa AJAX dla pól ACF (Advanced Custom Fields)
 * 
 * Ten plik zawiera funkcje do pobierania i aktualizacji pól ACF
 */

/**
 * Pobiera aktualne pola ACF dla zalogowanego użytkownika
 */
function get_acf_fields_ajax()
{
    // Sprawdź token bezpieczeństwa
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'data_manager_nonce')) {
        wp_send_json_error(['message' => 'Nieprawidłowy token bezpieczeństwa'], 403);
        exit;
    }

    // Pobierz ID zalogowanego użytkownika
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany'], 401);
        exit;
    }

    // Pobierz dane użytkownika z ACF
    $user_data = [
        'user' => [
            'id' => $user_id,
            'name' => get_user_meta($user_id, 'nickname', true),
        ],
        'stats' => get_field('user_stats', 'user_' . $user_id) ?: [],
        'backpack' => get_field('backpack', 'user_' . $user_id) ?: [],
        'equipment' => get_field('equipment', 'user_' . $user_id) ?: [],
        'skills' => get_field('skills', 'user_' . $user_id) ?: [],
        'active_missions' => get_field('active_missions', 'user_' . $user_id) ?: [],
    ];

    // Zwróć dane jako odpowiedź JSON
    wp_send_json_success(['fields' => $user_data]);
    exit;
}

// Rejestracja funkcji obsługującej akcję AJAX
add_action('wp_ajax_get_acf_fields', 'get_acf_fields_ajax');

/**
 * Aktualizuje pola ACF dla zalogowanego użytkownika
 */
function update_acf_fields_ajax()
{
    // Sprawdź token bezpieczeństwa
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'data_manager_nonce')) {
        wp_send_json_error(['message' => 'Nieprawidłowy token bezpieczeństwa'], 403);
        exit;
    }

    // Sprawdź czy pola są dostarczone
    if (!isset($_POST['fields']) || empty($_POST['fields'])) {
        wp_send_json_error(['message' => 'Nie przekazano danych do aktualizacji'], 400);
        exit;
    }

    // Pobierz ID zalogowanego użytkownika
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany'], 401);
        exit;
    }

    // Zdekoduj dane JSON z pól
    $fields = json_decode(stripslashes($_POST['fields']), true);
    if (!$fields || !is_array($fields)) {
        wp_send_json_error(['message' => 'Nieprawidłowy format danych'], 400);
        exit;
    }

    // Aktualizuj każde pole ACF
    $updated_fields = [];
    foreach ($fields as $field_name => $field_value) {
        // Zaktualizuj pole ACF dla użytkownika
        $result = update_field($field_name, $field_value, 'user_' . $user_id);
        $updated_fields[$field_name] = $result;
    }

    // Zwróć odpowiedź z informacją o aktualizacji
    wp_send_json_success([
        'message' => 'Pola zostały zaktualizowane',
        'updated' => $updated_fields
    ]);
    exit;
}

// Rejestracja funkcji obsługującej akcję AJAX
add_action('wp_ajax_update_acf_fields', 'update_acf_fields_ajax');

/**
 * Aktualizuje pola ACF dla konkretnego wpisu
 */
function update_acf_post_fields_reusable_ajax()
{
    // Sprawdź token bezpieczeństwa
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'data_manager_nonce')) {
        wp_send_json_error(['message' => 'Nieprawidłowy token bezpieczeństwa'], 403);
        exit;
    }

    // Sprawdź wymagane parametry
    if (!isset($_POST['post_id']) || !isset($_POST['fields'])) {
        wp_send_json_error(['message' => 'Nieprawidłowe parametry'], 400);
        exit;
    }

    $post_id = intval($_POST['post_id']);
    $fields = json_decode(stripslashes($_POST['fields']), true);

    if (!$fields || !is_array($fields)) {
        wp_send_json_error(['message' => 'Nieprawidłowy format danych'], 400);
        exit;
    }

    // Aktualizuj każde pole ACF dla danego wpisu
    $updated_fields = [];
    foreach ($fields as $field_name => $field_value) {
        $result = update_field($field_name, $field_value, $post_id);
        $updated_fields[$field_name] = $result;
    }

    wp_send_json_success([
        'message' => 'Pola wpisu zostały zaktualizowane',
        'updated' => $updated_fields,
        'post_id' => $post_id
    ]);
    exit;
}

// Rejestracja funkcji obsługującej akcję AJAX
add_action('wp_ajax_update_acf_post_fields_reusable', 'update_acf_post_fields_reusable_ajax');

/**
 * Tworzy nowy wpis niestandardowy wraz z polami ACF
 */
function create_custom_post_ajax()
{
    // Sprawdź token bezpieczeństwa
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'data_manager_nonce')) {
        wp_send_json_error(['message' => 'Nieprawidłowy token bezpieczeństwa'], 403);
        exit;
    }

    // Sprawdź wymagane parametry
    if (!isset($_POST['title']) || !isset($_POST['post_type'])) {
        wp_send_json_error(['message' => 'Brakujące parametry: tytuł lub typ wpisu'], 400);
        exit;
    }

    $title = sanitize_text_field($_POST['title']);
    $post_type = sanitize_text_field($_POST['post_type']);

    // Utwórz nowy wpis
    $post_data = [
        'post_title' => $title,
        'post_status' => 'publish',
        'post_type' => $post_type,
    ];

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => $post_id->get_error_message()], 500);
        exit;
    }

    // Jeśli dostarczone są pola ACF, zaktualizuj je
    if (isset($_POST['acf_fields']) && !empty($_POST['acf_fields'])) {
        $acf_fields = json_decode(stripslashes($_POST['acf_fields']), true);

        if (is_array($acf_fields)) {
            foreach ($acf_fields as $field_name => $field_value) {
                update_field($field_name, $field_value, $post_id);
            }
        }
    }

    wp_send_json_success([
        'message' => 'Wpis został utworzony pomyślnie',
        'post_id' => $post_id,
        'post_title' => $title,
        'post_type' => $post_type
    ]);
    exit;
}

// Rejestracja funkcji obsługującej akcję AJAX
add_action('wp_ajax_create_custom_post', 'create_custom_post_ajax');
