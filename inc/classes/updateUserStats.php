<?php
add_action('wp_ajax_upgrade_user_stat', 'upgrade_user_stat');
function upgrade_user_stat()
{

    $user_id = get_current_user_id();
    $stat_to_upgrade = isset($_POST['stat']) ? sanitize_text_field($_POST['stat']) : '';

    // Sprawdź, czy podano prawidłową statystykę
    $valid_stats = ['strength', 'vitality_stat', 'dexterity', 'perception', 'technical', 'charisma'];
    if (!in_array($stat_to_upgrade, $valid_stats)) {
        wp_send_json_error('Nieprawidłowa statystyka.');
    }

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

    // Mapowanie nazw statystyk z formularza na nazwy pól ACF
    $stat_mapping = [
        'vitality_stat' => 'vitality' // Innych nie trzeba mapować, bo nazwy się zgadzają
    ];

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
