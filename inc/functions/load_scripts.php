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
}

// Podpięcie funkcji ładującej skrypty do odpowiedniego hooka WordPress
add_action('wp_enqueue_scripts', 'game_load_scripts');
