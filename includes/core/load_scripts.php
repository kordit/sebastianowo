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

    // Lokalizacja danych dla UserManager API
    wp_localize_script('user-manager-api', 'userManagerData', array(
        'nonce'  => wp_create_nonce('wp_rest'),
        'apiUrl' => admin_url('admin-ajax.php'),
        'restUrl' => esc_url_raw(rest_url()),
    ));

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

    //jesli jest to podstrona author
    if (is_author()) {
        wp_register_script(
            'character-manager',
            get_template_directory_uri() . '/js/modules/character/character-manager.js',
            array('axios'),
            '1.0.0',
            true
        );
        wp_enqueue_script('character-manager');
    }
}

add_action('wp_enqueue_scripts', 'game_load_scripts');
