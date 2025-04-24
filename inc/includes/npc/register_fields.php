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


if (function_exists('acf_add_local_field_group')) {
    $fields_relacja = array();

    foreach (get_users() as $user) {
        $fields_relacja[] = array(
            'key'           => 'npc-relation-user-' . $user->ID,
            'label'         => 'Relacja z ' . $user->user_login,
            'name'          => 'npc-relation-user-' . $user->ID,
            'type'          => 'range',
            'min'           => -100,
            'max'           => 100,
            'default_value' => 0,
            'wrapper' => array(
                'width' => '50',
            ),
        );

        $fields_relacja[] = array(
            'key'           => 'npc-meet-user-' . $user->ID,
            'label'         => 'Poznanie ' . $user->user_login,
            'name'          => 'npc-meet-user-' . $user->ID,
            'type'          => 'true_false',
            'message'       => 'Czy NPC poznał tego gracza?',
            'ui'            => 1,
            'default_value' => 0,
            'wrapper' => array(
                'width' => '50',
            ),
        );
    }

    acf_add_local_field_group(array(
        'key' => 'group_relacja_z_graczami',
        'title' => 'Relacja z graczami',
        'fields' => $fields_relacja,
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'npc',
                ),
            ),
        ),
        'hide_on_screen' => array('the_content'),
        'active'        => true,
        'show_in_rest'  => 0,
    ));
}
