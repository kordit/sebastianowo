<?php

/**
 * System ładowania skryptów JavaScript
 * 
 * Ten plik definiuje sposób ładowania skryptów w zależności od aktualnej strony.
 * Skrypty są podzielone na moduły i ładowane tylko wtedy, gdy są potrzebne.
 */

// Funkcja rejestruje i dołącza skrypty
function game_enqueue_scripts_and_styles()
{
    // Zawsze ładujemy główny plik CSS
    wp_enqueue_style(
        'game-main-style',
        get_stylesheet_uri(),
        [],
        filemtime(get_stylesheet_directory() . '/style.css')
    );

    // Ścieżki do katalogów z plikami
    $js_path = get_stylesheet_directory() . '/js';
    $js_url = get_stylesheet_directory_uri() . '/js';

    // Lista kluczowych skryptów, które muszą być załadowane w odpowiedniej kolejności
    $core_scripts = [
        'ajax-helper' => '/core/ajax-helper.js',
        'notifications' => '/core/notifications.js',
        'ui-helpers' => '/core/ui-helpers.js'
    ];

    // Ładowanie skryptów podstawowych
    foreach ($core_scripts as $name => $path) {
        $file_path = $js_path . $path;
        if (file_exists($file_path)) {
            wp_enqueue_script(
                'game-' . $name,
                $js_url . $path,
                [], // bez zależności od jQuery!
                filemtime($file_path),
                true // w stopce
            );
        }
    }

    // System powiadomień - style
    $notification_css_path = get_stylesheet_directory() . '/assets/css/notification-system.css';
    if (file_exists($notification_css_path)) {
        wp_enqueue_style(
            'game-notifications-style',
            get_stylesheet_directory_uri() . '/assets/css/notification-system.css',
            [],
            filemtime($notification_css_path)
        );
    }

    // Moduły aplikacji - zawsze ładuj moduły NPC i misji, bo są używane wszędzie
    $required_modules = [
        'missions' => [
            'mission-manager' => '/modules/missions/mission-manager.js',
            'tracker' => '/modules/missions/tracker.js'
        ],
        'npc' => [
            'npc-handler' => '/modules/npc/npc-handler.js',
            'npc-dialogs' => '/modules/npc/npc-dialogs.js'
        ],
        'areas' => [
            'svg-interactions' => '/modules/areas/svg-interactions.js'
        ],
        'utils' => [
            'acf-helpers' => '/utils/acf-helpers.js'
        ]
    ];

    // Dodaj więcej modułów w zależności od kontekstu strony
    if (is_page_template('page-templates/plecak/template.php')) {
        $required_modules['inventory'] = [
            'inventory-handler' => '/modules/inventory/inventory-handler.js'
        ];
    }

    if (is_page_template('page-templates/walka/template.php')) {
        $required_modules['combat'] = [
            'combat-handler' => '/modules/character/combat-handler.js'
        ];
    }

    // Dołączanie modułów z rejestrowanymi zależnościami
    foreach ($required_modules as $module_group => $scripts) {
        foreach ($scripts as $name => $path) {
            $file_path = $js_path . $path;
            if (file_exists($file_path)) {
                $deps = ['game-ajax-helper', 'game-notifications'];
                wp_enqueue_script(
                    'game-' . $name,
                    $js_url . $path,
                    $deps,
                    filemtime($file_path),
                    true
                );
            }
        }
    }

    // Na końcu dołącz główny plik aplikacji
    $app_path = $js_path . '/app.js';
    if (file_exists($app_path)) {
        $deps = ['game-ajax-helper', 'game-notifications'];

        // Dodaj moduły jako zależności
        foreach ($required_modules as $module_group => $scripts) {
            foreach ($scripts as $name => $path) {
                $deps[] = 'game-' . $name;
            }
        }

        wp_enqueue_script(
            'game-app',
            $js_url . '/app.js',
            $deps,
            filemtime($app_path),
            true
        );

        // Przekazujemy zmienne do JS
        wp_localize_script('game-app', 'gameData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'homeUrl' => home_url(),
            'themeUrl' => get_stylesheet_directory_uri(),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in(),
            'nonce' => wp_create_nonce('game_ajax_nonce'),
            'dataManagerNonce' => wp_create_nonce('data_manager_nonce'),
            'missionNonce' => wp_create_nonce('mission_ajax_nonce'),
        ]);
    }
}

// Podpinamy naszą funkcję 
add_action('wp_enqueue_scripts', 'game_enqueue_scripts_and_styles');
