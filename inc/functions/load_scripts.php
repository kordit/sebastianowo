<?php

/**
 * System ładowania skryptów JavaScript
 * 
 * Ten plik definiuje sposób ładowania skryptów w zależności od aktualnej strony.
 * Skrypty są podzielone na moduły i ładowane tylko wtedy, gdy są potrzebne.
 * 
 * UWAGA: Wszystkie pliki JavaScript muszą znajdować się WYŁĄCZNIE w katalogu /js
 * zgodnie ze strukturą projektu.
 */

/**
 * Główna funkcja ładująca skrypty JavaScript
 */
function game_load_scripts()
{
    // Ścieżki do katalogów z plikami JavaScript (tylko katalog /js)
    $js_path = get_template_directory() . '/js';
    $js_url = get_template_directory_uri() . '/js';

    // Określenie aktualnego szablonu i typu widoku (archive vs single)
    $current_template = '';
    $view_type = '';
    $post_type = get_post_type();

    // Ustalenie typu zawartości i widoku
    if (is_author()) {
        $current_template = 'author';
    } elseif (is_archive()) {
        $current_template = $post_type;
        $view_type = 'archive';
    } elseif (is_single()) {
        $current_template = $post_type;
        $view_type = 'single';
    } elseif (is_page()) {
        // Sprawdzanie szablonu strony
        $template_slug = get_page_template_slug();
        if ($template_slug) {
            $current_template = basename($template_slug, '.php');
        } else {
            $current_template = 'page';
        }
    } elseif (is_front_page()) {
        $current_template = 'front-page';
    }

    // Ładowanie skryptów podstawowych (core)
    $core_scripts = [
        'ajax-helper' => '/core/ajax-helper.js',
        'common' => '/core/common.js',
        'notifications' => '/core/notifications.js',
        'ui-helpers' => '/core/ui-helpers.js'
    ];

    // Rejestrowanie i ładowanie skryptów core
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

    // Zawsze ładuj acf-helpers, niezależnie od kontekstu
    $acf_helpers_path = $js_path . '/utils/acf-helpers.js';
    if (file_exists($acf_helpers_path)) {
        wp_enqueue_script(
            'game-utils-acf-helpers',
            $js_url . '/utils/acf-helpers.js',
            ['jquery', 'game-ajax-helper'],
            filemtime($acf_helpers_path),
            true
        );
    }

    // Ładowanie skryptów specyficznych dla danego typu zawartości i widoku
    if (!empty($post_type) && !empty($view_type)) {
        // Automatyczne ładowanie skryptów z /js/pages/[archive|single]/[post_type].js
        $js_page_path = $js_path . '/pages/' . $view_type . '/' . $post_type . '.js';

        if (file_exists($js_page_path)) {
            wp_enqueue_script(
                'game-' . $post_type . '-' . $view_type,
                $js_url . '/pages/' . $view_type . '/' . $post_type . '.js',
                ['jquery', 'game-ajax-helper'], // Zależności
                filemtime($js_page_path),
                true
            );
        }
    }

    // Ładowanie skryptu aplikacji
    $app_path = $js_path . '/app.js';
    if (file_exists($app_path)) {
        // Podstawowe zależności
        $deps = ['game-ajax-helper', 'game-notifications', 'game-ui-helpers', 'game-common'];

        wp_enqueue_script(
            'game-app',
            $js_url . '/app.js',
            $deps,
            filemtime($app_path),
            true
        );

        // Przekazanie danych do JS
        wp_localize_script('game-app', 'gameData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'homeUrl' => home_url(),
            'themeUrl' => get_template_directory_uri(),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in(),
            'nonce' => wp_create_nonce('game_ajax_nonce'),
            'dataManagerNonce' => wp_create_nonce('data_manager_nonce'),
            'missionNonce' => wp_create_nonce('mission_ajax_nonce'),
        ]);
    }

    // Warunkowe ładowanie modułów w zależności od kontekstu strony
    conditional_module_loading($current_template, $post_type, $view_type);

    // Dodaj informację o debugowaniu, jeśli WP_DEBUG jest włączone
    if (defined('WP_DEBUG') && WP_DEBUG) {
        wp_localize_script('game-app', 'GameDebug', [
            'template' => $current_template,
            'viewType' => $view_type,
            'postType' => $post_type
        ]);
    }
}

/**
 * Funkcja warunkowego ładowania modułów w zależności od kontekstu strony
 */
function conditional_module_loading($current_template, $post_type, $view_type)
{
    $instance_name = get_query_var('instance_name', '');

    // Moduł Character - ładowany na wszystkich stronach
    if (register_module_script('character', 'character-manager', ['jquery', 'game-ajax-helper'])) {
        wp_enqueue_script('game-character-character-manager');
    }

    // Moduł Inventory
    if ($instance_name === 'plecak' || is_page_template('page-templates/plecak/template.php')) {
        if (register_module_script('inventory', 'inventory-utils', ['jquery'])) {
            wp_enqueue_script('game-inventory-inventory-utils');
        }
        if (register_module_script('inventory', 'inventory-component', ['jquery', 'game-ajax-helper', 'game-inventory-inventory-utils'])) {
            wp_enqueue_script('game-inventory-inventory-component');
        }
    }    // Moduł Areas dla archiwów i pojedynczych terenów
    if ($post_type === 'tereny') {
        // Najpierw załaduj npc-handler, który zawiera funkcję buildNpcPopup
        if (register_module_script('npc', 'npc-handler', ['jquery', 'game-ajax-helper'])) {
            wp_enqueue_script('game-npc-npc-handler');
        }

        if (register_module_script('utils', 'acf-helpers', ['jquery', 'game-ajax-helper'])) {
            wp_enqueue_script('game-utils-acf-helpers');
        }

        // Następnie załaduj svg-interactions z zależnością od npc-handler
        if (register_module_script('areas', 'svg-interactions', ['jquery', 'game-ajax-helper', 'game-npc-npc-handler'])) {
            wp_enqueue_script('game-areas-svg-interactions');
        }

        // Dla widoków archiwum terenów
        if (is_archive()) {
            if (register_module_script('areas', 'archive', ['jquery', 'game-ajax-helper', 'game-areas-svg-interactions'])) {
                wp_enqueue_script('game-areas-archive');
            }
        }

        // Dla pojedynczych terenów
        if (is_single()) {
            if (register_module_script('areas', 'single', ['jquery', 'game-ajax-helper', 'game-areas-svg-interactions'])) {
                wp_enqueue_script('game-areas-single');
            }
        }
    }

    // Aktywuj svg-interactions również dla szablonów powiązanych z terenami
    if (strpos($current_template, 'tereny') !== false && $post_type !== 'tereny') {
        // Najpierw upewnij się, że mamy załadowane acf-helpers
        if (register_module_script('utils', 'acf-helpers', ['jquery', 'game-ajax-helper'])) {
            wp_enqueue_script('game-utils-acf-helpers');
        }

        // Następnie załaduj npc-handler, który zawiera funkcję buildNpcPopup
        if (register_module_script('npc', 'npc-handler', ['jquery', 'game-ajax-helper', 'game-utils-acf-helpers'])) {
            wp_enqueue_script('game-npc-npc-handler');
        }

        // Na koniec załaduj svg-interactions z zależnością od npc-handler
        if (register_module_script('areas', 'svg-interactions', ['jquery', 'game-ajax-helper', 'game-npc-npc-handler'])) {
            wp_enqueue_script('game-areas-svg-interactions');
        }
    }

    // Ładuj skrypty NPC na wszystkich stronach single
    if (is_single()) {
        // Najpierw upewnij się, że mamy załadowane acf-helpers
        if (register_module_script('utils', 'acf-helpers', ['jquery', 'game-ajax-helper'])) {
            wp_enqueue_script('game-utils-acf-helpers');
        }

        // Załaduj npc-handler, który zawiera funkcje obsługi NPC
        if (register_module_script('npc', 'npc-handler', ['jquery', 'game-ajax-helper', 'game-utils-acf-helpers'])) {
            wp_enqueue_script('game-npc-npc-handler');
        }

        // Załaduj npc-dialogs dla obsługi dialogów
        if (register_module_script('npc', 'npc-dialogs', ['jquery', 'game-ajax-helper', 'game-npc-npc-handler'])) {
            wp_enqueue_script('game-npc-npc-dialogs');
        }
    }
}

/**
 * Rejestracja skryptu modułowego zgodnie ze strukturą projektu
 * 
 * @param string $module_name Nazwa modułu (np. 'inventory', 'missions')
 * @param string $component_name Nazwa komponentu (domyślnie 'main')
 * @param array $deps Zależności skryptu (domyślnie jQuery i game-ajax-helper)
 * @return bool True jeśli skrypt został zarejestrowany, false w przeciwnym przypadku
 */
function register_module_script($module_name, $component_name = 'main', $deps = ['jquery', 'game-ajax-helper'])
{
    $file_path = get_template_directory() . '/js/modules/' . $module_name . '/' . $component_name . '.js';

    // Użyj error_log zamiast echo do debugowania
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Rejestracja skryptu: $module_name/$component_name");
    }

    if (file_exists($file_path)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Ścieżka do pliku: $file_path");
        }        // Rejestruj skrypt jako moduł ES6 bezpośrednio modyfikując tag script
        $script_url = get_template_directory_uri() . '/js/modules/' . $module_name . '/' . $component_name . '.js';
        $script_handle = 'game-' . $module_name . '-' . $component_name;
        $script_version = filemtime($file_path);

        // Rejestracja skryptu jako modułu ES6
        wp_register_script($script_handle, '', array(), $script_version, true);

        // Ręczne dodanie skryptu do kolejki jako moduł
        add_filter('script_loader_tag', function ($tag, $handle, $src) use ($script_handle, $script_url, $script_version) {
            if ($handle === $script_handle) {
                $tag = '<script type="module" src="' . $script_url . '?ver=' . $script_version . '"></script>';
            }
            return $tag;
        }, 10, 3);
        return true;
    }
    return false;
}

/**
 * Funkcja pomocnicza do rejestrowania skryptów niestandardowych
 */
function game_register_custom_script($name, $path, $deps = ['jquery'], $in_footer = true)
{
    if (file_exists(get_template_directory() . $path)) {
        wp_register_script(
            'game-' . $name,
            get_template_directory_uri() . $path,
            $deps,
            filemtime(get_template_directory() . $path),
            $in_footer
        );
        return true;
    }
    return false;
}

// Podpięcie funkcji do akcji WordPress
add_action('wp_enqueue_scripts', 'game_load_scripts');
