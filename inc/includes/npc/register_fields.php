<?php

function register_cpt_npc()
{
    register_post_type('npc', array(
        'labels' => array(
            'name'               => 'NPC',
            'singular_name'      => 'NPC',
            'menu_name'          => 'NPC',
            'all_items'          => 'Wszystkie NPC',
            'edit_item'          => 'Edytuj NPC',
            'view_item'          => 'Zobacz NPC',
            'add_new_item'       => 'Dodaj nowy NPC',
            'new_item'           => 'Nowy NPC',
            'search_items'       => 'Szukaj NPCów',
            'not_found'          => 'Nie znaleziono NPCów',
            'not_found_in_trash' => 'Nie znaleziono NPCów w koszu',
        ),
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'supports'      => array('title', 'thumbnail'),
        'has_archive'   => false,
        'menu_position' => 19,
        'menu_icon'     => 'dashicons-admin-users',
        'show_in_rest'  => false,
    ));
}
add_action('init', 'register_cpt_npc');

/**
 * Automatycznie zaznacz, że NPC poznał gracza gdy relacja się zmieni
 * 
 * @param mixed $value - Nowa wartość relacji
 * @param int $post_id - ID postu NPC
 * @param array $field - Dane pola ACF
 * @return mixed - Wartość relacji (niezmieniona)
 */
function auto_mark_npc_met_user($value, $post_id, $field)
{
    // Sprawdź czy pole jest typu 'relation' (dla relacji z użytkownikiem)
    if (strpos($field['name'], 'npc-relation-user-') === 0) {
        // Pobierz ID użytkownika z nazwy pola
        $user_id = str_replace('npc-relation-user-', '', $field['name']);

        // Pobierz obecną wartość pola "poznania"
        $meet_field = 'npc-meet-user-' . $user_id;
        $has_met = get_field($meet_field, $post_id);

        // Jeśli relacja jest inna niż 0 i NPC jeszcze nie poznał gracza, zaznacz że poznał
        if ($value != 0 && !$has_met) {
            update_field($meet_field, true, $post_id);
            error_log("NPC (ID: {$post_id}) poznał gracza (ID: {$user_id}) - zmiana relacji na {$value}");
        }
    }

    return $value;
}
add_filter('acf/update_value', 'auto_mark_npc_met_user', 10, 3);
