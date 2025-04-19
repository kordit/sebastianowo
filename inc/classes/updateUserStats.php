<?php
add_action('wp_ajax_upgrade_user_stat', 'upgrade_user_stat');
function upgrade_user_stat()
{

    $user_id = get_current_user_id();
    $stat_to_upgrade = isset($_POST['stat']) ? sanitize_text_field($_POST['stat']) : '';

    // Pobierz punkty nauki użytkownika
    $progress = get_field('progress', 'user_' . $user_id);
    $learning_points = isset($progress['progress']['learning_points']) ?
        $progress['progress']['learning_points'] : (isset($progress['learning_points']) ? $progress['learning_points'] : 0);

    // Sprawdź, czy użytkownik ma wystarczającą liczbę punktów
    if ($learning_points <= 0) {
        wp_send_json_error('Nie masz wystarczającej liczby punktów nauki.');
    }

    // Pobierz aktualne statystyki
    $user_stats = get_field('stats', 'user_' . $user_id);
    if (!is_array($user_stats)) {
        $user_stats = [];
    }

    // Przygotuj nazwę pola ACF
    $acf_field_name = isset($stat_mapping[$stat_to_upgrade]) ? $stat_mapping[$stat_to_upgrade] : $stat_to_upgrade;

    // Zwiększ wybraną statystykę
    $current_value = isset($user_stats[$acf_field_name]) ? $user_stats[$acf_field_name] : 1;
    $user_stats[$acf_field_name] = $current_value + 1;

    // Zapisz zaktualizowane statystyki
    update_field('stats', $user_stats, 'user_' . $user_id);

    // Aktualizuj punkty nauki (zmniejsz o 1)
    if (isset($progress['progress'])) {
        $progress['progress']['learning_points'] = $learning_points - 1;
        update_field('progress', $progress['progress'], 'user_' . $user_id);
    } else {
        $progress['learning_points'] = $learning_points - 1;
        update_field('progress', $progress, 'user_' . $user_id);
    }

    // Zwróć aktualizowane dane
    wp_send_json_success([
        'stat' => $stat_to_upgrade,
        'new_value' => $current_value + 1,
        'remaining_points' => $learning_points - 1
    ]);
}
