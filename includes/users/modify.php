<?php
function modify_user_roles()
{
    // Usuwanie wszystkich ról poza administratorem
    global $wp_roles;
    foreach ($wp_roles->roles as $role_name => $role_info) {
        if ($role_name !== 'administrator') {
            remove_role($role_name);
        }
    }

    // Dodanie nowej roli "gracz"
    add_role(
        'gracz',
        __('Gracz'),
        array(
            'read' => true,  // Gracz może przeglądać treści
            // Brak uprawnień do edycji, dostępu do admina itp.
        )
    );
}

// Hook do inicjalizacji, aby funkcja została wywołana
add_action('init', 'modify_user_roles');

// function redirect_if_not_logged_in()
// {
//     if (!is_user_logged_in() && !is_admin()) {
//         wp_redirect(home_url());
//         exit;
//     }
// }
// add_action('template_redirect', 'redirect_if_not_logged_in');


// function custom_login_redirect() {

//     return 'home_url()';

//     }

//     add_filter('login_redirect', 'custom_login_redirect');

add_action('wp_ajax_update_user_field', function () {
    if (!isset($_POST['user_id'], $_POST['field'], $_POST['value'])) {
        wp_send_json_error(['message' => 'Brak wymaganych danych'], 400);
    }

    $user_id = intval($_POST['user_id']);
    $field = sanitize_text_field($_POST['field']);
    $value = sanitize_text_field($_POST['value']);

    if ($field === 'minerals') {
        $minerals = get_user_meta($user_id, 'minerals', true);
        parse_str($field, $fieldParts);
        $minerals[$fieldParts[1]] = $value;
        update_user_meta($user_id, 'minerals', $minerals);
    } else {
        $update_result = wp_update_user([
            'ID' => $user_id,
            $field => $value
        ]);

        if (is_wp_error($update_result)) {
            wp_send_json_error(['message' => 'Nie udało się zaktualizować użytkownika'], 500);
        }
    }

    wp_send_json_success(['message' => 'Zaktualizowano poprawnie']);
});
