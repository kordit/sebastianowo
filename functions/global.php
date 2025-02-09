<?php
function et_r($var, $class = '')
{
    if ($class) {
        $class_tag = 'class="' . $class . '" ';
    } else {
        $class_tag = '';
    }
    echo '<pre ' . $class_tag .  '>';
    print_r($var);
    echo '</pre>';
}

function get_user_game($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return [];
    }

    // Pobieranie danych użytkownika
    $user = get_userdata($user_id);
    if (!$user) {
        return [];
    }

    // Pobieranie pól ACF dla użytkownika
    $acf_fields = get_fields('user_' . $user_id);

    // Dane podstawowe użytkownika
    $user_data = [
        'id' => $user->ID,
        'username' => $user->user_login,
        'display_name' => $user->display_name,
        'nickname' => $user->nickname,
        'email' => $user->user_email,
    ];

    // Łączenie danych użytkownika z polami ACF
    return array_merge($user_data, $acf_fields ? $acf_fields : []);
}

add_action('wp_ajax_create_village', 'create_village');
add_action('wp_ajax_nopriv_create_village', 'create_village');

function create_village()
{
    $village_name = sanitize_text_field($_POST['village_name']);
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error(['message' => 'Musisz być zalogowany, aby stworzyć wioskę.']);
    }

    if (empty($village_name)) {
        wp_send_json_error(['message' => 'Nazwa wioski jest wymagana.']);
    }

    // Sprawdzenie, do której wioski jest przypisany użytkownik
    $current_village = get_field('field_6794b04d1bd06', 'user_' . $user_id);
    if ($current_village && $current_village->ID !== 73) {
        wp_send_json_error(['message' => 'Już jesteś przypisany do innej wioski. Nie możesz utworzyć nowej.']);
    }

    // Tworzenie wioski jako nowy post
    $post_id = wp_insert_post([
        'post_title'  => $village_name,
        'post_status' => 'publish',
        'post_type'   => 'wioska',
        'post_author' => $user_id,
    ]);

    if ($post_id) {
        // Dodaj obecnego użytkownika jako lidera
        update_field('field_6794afae1bd05_leader', $user_id, $post_id);

        // Dodaj obecnego użytkownika do mieszkańców
        $villagers = get_field('field_6794afae1bd05', $post_id) ?: [];
        if (!in_array($user_id, $villagers)) {
            $villagers[] = $user_id;
            update_field('field_6794afae1bd05', $villagers, $post_id);
        }

        // Ustaw pole 'village' dla użytkownika
        update_field('field_6794b04d1bd06', $post_id, 'user_' . $user_id);

        wp_send_json_success(['message' => 'Wioska została pomyślnie stworzona i zostałeś do niej przypisany.']);
    } else {
        wp_send_json_error(['message' => 'Nie udało się stworzyć wioski.']);
    }
}

add_action('wp_ajax_disconnect_relation', 'disconnect_relation_callback');
add_action('wp_ajax_nopriv_disconnect_relation', 'disconnect_relation_callback');

function disconnect_relation_callback()
{
    $user_id = intval($_POST['user_id']);
    $post_id = intval($_POST['post_id']);
    $fields = json_decode(stripslashes($_POST['fields']), true);

    if (!$user_id || !$post_id || empty($fields)) {
        wp_send_json_error(['message' => 'Brak wymaganych danych.']);
    }

    foreach ($fields as $field) {
        $current_values = get_field($field, $post_id);

        if (is_array($current_values)) {
            // Usuń użytkownika z tablicy
            $new_values = array_filter($current_values, function ($item) use ($user_id) {
                return (is_array($item) && $item['ID'] != $user_id) || $item != $user_id;
            });
            update_field($field, $new_values, $post_id);
        } elseif (!is_array($current_values) && $current_values == $user_id) {
            // Usuń wartość, jeśli pole nie jest tablicą
            update_field($field, null, $post_id);
        }
    }

    // Usuń referencję do wioski w użytkowniku
    $village = get_field('village', 'user_' . $user_id);
    if ($village && $village->ID == $post_id) {
        update_field('village', null, 'user_' . $user_id);
    }

    wp_send_json_success(['message' => 'Relacje zostały odłączone.']);
}

add_action('wp_ajax_apply_to_village', 'apply_to_village_callback');
add_action('wp_ajax_nopriv_apply_to_village', 'apply_to_village_callback');

function apply_to_village_callback()
{
    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id']);

    if (!$user_id || !$post_id) {
        wp_send_json_error(['message' => 'Niepoprawne dane.']);
    }

    // Pobierz obecne aplikacje (tablica użytkowników)
    $current_applications = get_field('applications', $post_id);

    // Ekstrakcja ID użytkowników z tablicy
    $current_user_ids = array();
    if (is_array($current_applications)) {
        foreach ($current_applications as $user) {
            if (isset($user['ID'])) {
                $current_user_ids[] = $user['ID'];
            }
        }
    }

    // Sprawdź, czy użytkownik już aplikował
    if (in_array($user_id, $current_user_ids)) {
        wp_send_json_error(['message' => 'Już aplikowałeś do tej wioski.']);
    }

    // Dodaj nowe ID użytkownika do listy
    $current_user_ids[] = $user_id;

    // Zaktualizuj pole ACF z nową listą ID
    $update_success = update_field('applications', $current_user_ids, $post_id);

    if ($update_success) {
        wp_send_json_success(['message' => 'Aplikacja została pomyślnie złożona.', 'my-id' => $user_id]);
    } else {
        wp_send_json_error(['message' => 'Wystąpił problem podczas zapisywania aplikacji.']);
    }
}
add_action('wp_ajax_get_applicants', 'get_applicants_callback');
function get_applicants_callback()
{
    $post_id = intval($_POST['post_id']);
    $user_id = get_current_user_id();

    if (!$post_id || !$user_id) {
        wp_send_json_error(['message' => 'Brak wymaganych danych.']);
    }

    $leader = get_field('leader', $post_id);
    if (!$leader || $leader['ID'] !== $user_id) {
        wp_send_json_error(['message' => 'Nie jesteś liderem tej wioski.']);
    }

    // Pobierz listę aplikantów z pola aplikacji
    $applicants = get_field('applications', $post_id) ?: [];

    // Jeśli lista jest pusta, zwróć komunikat
    if (empty($applicants)) {
        wp_send_json_success(['applicants' => []]);
    }

    // Zwróć listę aplikantów (bez dodatkowego przetwarzania, bo dane już są kompletne)
    $applicant_data = array_map(function ($applicant) {
        return [
            'ID'           => $applicant['ID'],
            'display_name' => $applicant['display_name'],
            'user_email'   => $applicant['user_email'],
        ];
    }, $applicants);

    wp_send_json_success(['applicants' => $applicant_data]);
}



add_action('wp_ajax_update_applicant_status', 'update_applicant_status_callback');
function update_applicant_status_callback()
{
    $post_id = intval($_POST['post_id']);
    $user_id = intval($_POST['applicant_id']);
    $action = sanitize_text_field($_POST['action_type']); // accept/reject
    $current_user_id = get_current_user_id();

    if (!$post_id || !$user_id || !$action) {
        wp_send_json_error(['message' => 'Brak wymaganych danych.']);
    }

    // Sprawdź, czy użytkownik jest liderem
    $leader = get_field('leader', $post_id);
    if (!$leader || $leader['ID'] !== $current_user_id) {
        wp_send_json_error(['message' => 'Nie jesteś liderem tej wioski.']);
    }

    // Pobierz mieszkańców
    $villagers = get_field('the_villagers', $post_id) ?: [];

    if ($action === 'accept') {
        // Sprawdź, czy limit mieszkańców został osiągnięty
        if (count($villagers) >= 5) {
            wp_send_json_error(['message' => 'Nie można dodać więcej mieszkańców. Maksymalna liczba mieszkańców to 5.']);
        }

        // Dodaj aplikanta do mieszkańców
        if (!in_array($user_id, array_column($villagers, 'ID'))) {
            $villagers[] = ['ID' => $user_id];
            update_field('the_villagers', $villagers, $post_id);
        }

        // Usuń aplikanta z listy aplikantów po zaakceptowaniu
        $applicants = get_field('applications', $post_id) ?: [];
        $applicants = array_filter($applicants, function ($applicant) use ($user_id) {
            return $applicant['ID'] !== $user_id;
        });
        update_field('applications', $applicants, $post_id);
    } elseif ($action === 'reject') {
        // Usuń aplikanta z listy aplikantów po odrzuceniu
        $applicants = get_field('applications', $post_id) ?: [];
        $applicants = array_filter($applicants, function ($applicant) use ($user_id) {
            return $applicant['ID'] !== $user_id;
        });
        update_field('applications', $applicants, $post_id);
    }

    wp_send_json_success(['message' => 'Status aplikanta został zaktualizowany.']);
}

add_action('wp_ajax_remove_villager', 'remove_villager_callback');
function remove_villager_callback()
{
    $post_id = intval($_POST['post_id']);
    $user_id = intval($_POST['villager_id']);
    $current_user_id = get_current_user_id();

    if (!$post_id || !$user_id) {
        wp_send_json_error(['message' => 'Brak wymaganych danych.']);
    }

    // Sprawdź, czy bieżący użytkownik jest liderem
    $leader = get_field('leader', $post_id);
    if (!$leader || $leader['ID'] !== $current_user_id) {
        wp_send_json_error(['message' => 'Nie jesteś liderem tej wioski.']);
    }

    // Pobierz listę mieszkańców i usuń wskazanego mieszkańca
    $villagers = get_field('the_villagers', $post_id) ?: [];
    $villagers = array_filter($villagers, function ($villager) use ($user_id) {
        return $villager['ID'] !== $user_id;
    });

    // Zaktualizuj listę mieszkańców
    update_field('the_villagers', $villagers, $post_id);

    // Usuń referencję do wioski u użytkownika
    $village = get_field('village', 'user_' . $user_id);
    if ($village && $village->ID === $post_id) {
        update_field('village', null, 'user_' . $user_id);
    }

    wp_send_json_success(['message' => 'Mieszkaniec został wyrzucony z wioski.']);
}

add_action('wp_ajax_get_villagers', 'get_villagers_callback');
function get_villagers_callback()
{
    $post_id = intval($_POST['post_id']);
    $current_user_id = get_current_user_id();

    if (!$post_id || !$current_user_id) {
        wp_send_json_error(['message' => 'Brak wymaganych danych.']);
    }

    $leader = get_field('leader', $post_id);
    if (!$leader || $leader['ID'] !== $current_user_id) {
        wp_send_json_error(['message' => 'Nie jesteś liderem tej wioski.']);
    }

    $villagers = get_field('the_villagers', $post_id) ?: [];

    $villager_data = array_map(function ($villager) {
        return [
            'ID'           => $villager['ID'],
            'display_name' => $villager['display_name'],
            'user_email'   => $villager['user_email'],
        ];
    }, $villagers);

    wp_send_json_success(['villagers' => $villager_data]);
}
