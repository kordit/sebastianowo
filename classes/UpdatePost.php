<?php
function update_acf_post_fields_reusable()
{
    // Akceptujemy tylko żądania AJAX
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        wp_send_json_error(['message' => 'Nieautoryzowane żądanie']);
        wp_die();
    }

    check_ajax_referer('dm_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Wymagane logowanie']);
        wp_die();
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(['message' => 'Brak ID wpisu']);
        wp_die();
    }

    // Uwaga: nie sprawdzamy current_user_can('edit_post') – system aktualizuje dane,
    // mimo że użytkownik nie ma uprawnień do bezpośredniej edycji wpisu.

    if (empty($_POST['fields'])) {
        wp_send_json_error(['message' => 'Brak danych do aktualizacji']);
        wp_die();
    }

    $raw_fields = stripslashes($_POST['fields']);
    $fields_data = json_decode($raw_fields, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(['message' => 'Błąd dekodowania JSON: ' . json_last_error_msg()]);
        wp_die();
    }

    $messages = [];
    foreach ($fields_data as $field_key => $delta) {
        $field_object = get_field_object($field_key, $post_id);
        if (!$field_object) {
            $messages[] = "Pole {$field_key} nie istnieje.";
            continue;
        }

        // Tylko pola należące do grupy "group_relacja_z_graczami" mogą być aktualizowane
        if (!isset($field_object['parent']) || $field_object['parent'] !== 'group_relacja_z_graczami') {
            $messages[] = "Nie masz uprawnień do edycji pola {$field_key}.";
            continue;
        }

        $old_value = get_field($field_key, $post_id);
        $new_value = floatval($old_value) + floatval($delta);

        // Upewniamy się, że nowe wartości mieszczą się w dozwolonym zakresie
        $min = isset($field_object['min']) ? floatval($field_object['min']) : null;
        $max = isset($field_object['max']) ? floatval($field_object['max']) : null;

        if ($max !== null && $new_value > $max) {
            $new_value = $max;
        }
        if ($min !== null && $new_value < $min) {
            $new_value = $min;
        }

        update_field($field_key, $new_value, $post_id);
        $messages[] = "Pole {$field_key}: {$old_value} -> {$new_value}";
    }

    wp_send_json_success(['message' => implode('. ', $messages)]);
    wp_die();
}
add_action('wp_ajax_update_acf_post_fields_reusable', 'update_acf_post_fields_reusable');
