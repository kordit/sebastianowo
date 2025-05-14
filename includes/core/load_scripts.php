<?php

/**
 * Load external and custom scripts
 */
function game_load_scripts()
{
    // Lokalny Axios - ładowany pierwszy, aby Alpine.js mógł go użyć
    wp_register_script(
        'axios',
        get_template_directory_uri() . '/assets/js/vendors/axios.min.js',
        array(),
        '1.0.0',
        true
    );
    wp_enqueue_script('axios');

    // Pomocnik API z funkcjami do komunikacji z REST API
    wp_register_script(
        'api-helper',
        get_template_directory_uri() . '/js/utils/api-helper.js',
        array('axios'),
        filemtime(get_template_directory() . '/js/utils/api-helper.js'),
        true
    );
    wp_enqueue_script('api-helper');

    // Globalne dane dla wszystkich skryptów
    wp_localize_script('api-helper', 'userManagerData', array(
        'nonce'  => wp_create_nonce('wp_rest'),
        'apiUrl' => admin_url('admin-ajax.php'),
        'restUrl' => esc_url_raw(rest_url('game/v1')),
        'userID' => get_current_user_id(),
        'isLoggedIn' => is_user_logged_in()
    ));

    // Własny skrypt inicjalizujący Alpine.js
    wp_register_script(
        'alpine-init',
        get_template_directory_uri() . '/assets/js/alpine-init.js',
        array('axios'),
        '1.0.0',
        true
    );
    wp_enqueue_script('alpine-init');

    // Lokalny Alpine.js - ładowany po inicjalizacji
    wp_register_script(
        'alpinejs',
        get_template_directory_uri() . '/assets/js/vendors/alpine.min.js',
        array('axios', 'alpine-init'),
        '3.12.3',
        true
    );
    wp_enqueue_script('alpinejs');

    // Moduł interakcji SVG - ładowany domyślnie
    wp_register_script(
        'svg-interactions',
        get_template_directory_uri() . '/js/modules/areas/svg-interactions.js',
        array('axios'),
        '1.0.0',
        true
    );
    wp_enqueue_script('svg-interactions');

    // Moduł obsługi dialogów NPC
    wp_register_script(
        'npc-dialogs',
        get_template_directory_uri() . '/js/modules/npc/buildNpcPopup.js',
        array('axios'),
        '1.0.0',
        true
    );
    wp_enqueue_script('npc-dialogs');

    if (is_author()) {
        wp_register_script(
            'stat-upgrade',
            get_template_directory_uri() . '/js/modules/user/stat-upgrade.js',
            array('axios', 'api-helper'),
            '1.0.0',
            true
        );
        wp_enqueue_script('stat-upgrade');
    }

    wp_register_script(
        'notifications',
        get_template_directory_uri() . '/js/core/notifications.js',
        array('axios'),
        '1.0.0',
        true
    );
    wp_enqueue_script('notifications');

    // wp_register_script(
    //     'npc-debug',
    //     get_template_directory_uri() . '/js/modules/npc/npc-debug.js',
    //     array('axios'),
    //     '1.0.0',
    //     true
    // );
    // wp_enqueue_script('npc-debug');

    wp_register_script(
        'ui-helpers',
        get_template_directory_uri() . '/js/core/ui-helpers.js',
        array('axios'),
        '1.0.0',
        true
    );
    wp_enqueue_script('ui-helpers');
}

add_action('wp_enqueue_scripts', 'game_load_scripts');
