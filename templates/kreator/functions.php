<?php
add_action('wp_ajax_update_user_character', 'handle_update_user_character');
add_action('wp_ajax_nopriv_update_user_character', 'handle_update_user_character');

function handle_update_user_character()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Brak autoryzacji'], 400);
    }

    $user_id = intval($_POST['user_id']);
    if (!$user_id || $user_id !== get_current_user_id()) {
        wp_send_json_error(['message' => 'Nie masz uprawnień'], 400);
    }

    if (!isset($_POST['nickname']) || empty($_POST['nickname'])) {
        wp_send_json_error(['message' => 'Brak pseudonimu'], 400);
    }

    if (!isset($_POST['avatar']) || empty($_POST['avatar'])) {
        wp_send_json_error(['message' => 'Brak avatara'], 400);
    }

    if (!isset($_POST['klasa_postaci']) || empty($_POST['klasa_postaci'])) {
        wp_send_json_error(['message' => 'Brak klasy postaci'], 400);
    }

    wp_update_user(['ID' => $user_id, 'nickname' => sanitize_text_field($_POST['nickname'])]);
    update_field('avatar', $_POST['avatar'], 'user_' . $user_id);
    update_field('klasa_postaci', $_POST['klasa_postaci'], 'user_' . $user_id);
    update_field('story', $_POST['story'], 'user_' . $user_id);

    $user = get_user_by('ID', $user_id);
    wp_send_json_success([
        'message' => 'Postać zapisana',
        'redirect_url' => home_url("/user/{$user->user_nicename}") // Dodaj URL przekierowania
    ]);
}
