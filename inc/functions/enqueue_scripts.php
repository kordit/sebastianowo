<?php

/**
 * System automatycznego ładowania skryptów i styli na podstawie szablonu strony
 * 
 * Ten plik odpowiada za inteligentne ładowanie JS i CSS tylko na tych podstronach, 
 * gdzie są faktycznie potrzebne, zamiast ładować wszystko na raz.
 */

// Główna funkcja ładująca skrypty i style
function game_enqueue_scripts_and_styles2()
{
    // Zawsze ładowane podstawowe skrypty i style
    // wp_enqueue_style('game-main-style', get_stylesheet_uri(), [], filemtime(get_stylesheet_directory() . '/style.css'));

    // // Najpierw załaduj moduły klasowe, żeby zapobiec podwójnym deklaracjom
    // $class_dir = get_stylesheet_directory() . '/assets/js/class';
    // if (file_exists($class_dir) && is_dir($class_dir)) {
    //     // Rejestrowanie klas osobno - AjaxHelper musi być pierwszy
    //     game_register_custom_script('ajax-helper', '/assets/js/class/AjaxHelper.js', [], true);
    //     wp_enqueue_script('game-ajax-helper');

    //     // Pomijamy index.js, jeśli zawiera duplikat AjaxHelper
    //     // Zamiast tego będziemy ładować potrzebne zależności bezpośrednio
    // }

    // Załaduj system powiadomień
    $notification_css_path = get_stylesheet_directory() . '/assets/css/notification-system.css';
    // $notification_js_path = get_stylesheet_directory() . '/assets/js/notification-system.js';

    if (file_exists($notification_css_path)) {
        $notification_css_version = filemtime($notification_css_path);
        wp_enqueue_style('game-notifications-style', get_stylesheet_directory_uri() . '/assets/css/notification-system.css', [], $notification_css_version);
    }

    if (file_exists($notification_js_path)) {
        $notification_js_version = filemtime($notification_js_path);
        // wp_enqueue_script('game-notifications-js', get_stylesheet_directory_uri() . '/assets/js/notification-system.js', ['jquery'], $notification_js_version, true);
    }

    // Załaduj główny skrypt bez podwójnej deklaracji AjaxHelper - dodajemy wersję z timestamp aby uniknąć cache'owania
    // $main_js_version = filemtime(get_stylesheet_directory() . '/assets/js/main.js');
    // wp_enqueue_script('game-main-js', get_stylesheet_directory_uri() . '/assets/js/main.js', ['jquery', 'game-ajax-helper', 'game-notifications-js'], $main_js_version, true);

    // Załaduj nowy system obsługi misji
    // $mission_handler_path = get_stylesheet_directory() . '/assets/js/mission-handler.js';
    if (file_exists($mission_handler_path)) {
        $mission_handler_version = filemtime($mission_handler_path);
        // wp_enqueue_script('game-mission-handler', get_stylesheet_directory_uri() . '/assets/js/mission-handler.js', ['jquery', 'game-ajax-helper', 'game-notifications-js', 'game-main-js'], $mission_handler_version, true);
    }

    // Załaduj obsługę misji
    // $mission_handler_path = get_stylesheet_directory() . '/assets/js/mission-handler.js';
    if (file_exists($mission_handler_path)) {
        $mission_handler_version = filemtime($mission_handler_path);
        // wp_enqueue_script('game-mission-handler', get_stylesheet_directory_uri() . '/assets/js/mission-handler.js', ['jquery', 'game-ajax-helper', 'game-main-js'], $mission_handler_version, true);
    }

    // Globalne zależności dla skryptów
    $script_dependencies = ['jquery', 'game-ajax-helper', 'game-main-js', 'game-mission-handler'];

    // Określenie aktualnego szablonu i typu widoku (main/archive vs single)
    $current_template = '';
    $view_type = '';
    $post_type = get_post_type();

    // Ustalenie typu zawartości i widoku
    if (is_author()) {
        $current_template = 'author';
        // Bezpośrednie ładowanie plików dla podstrony autora
        $author_style_path = get_stylesheet_directory() . '/page-templates/author/style.css';
        // $author_script_path = get_stylesheet_directory() . '/page-templates/author/main.js';

        if (file_exists($author_style_path)) {
            wp_enqueue_style(
                'game-author-panel-style',
                get_stylesheet_directory_uri() . '/page-templates/author/style.css',
                [],
                filemtime($author_style_path)
            );
        }

        if (file_exists($author_script_path)) {
            wp_enqueue_script(
                'game-author-panel-script',
                // get_stylesheet_directory_uri() . '/page-templates/author/main.js',
                ['jquery'],
                true
            );
            wp_enqueue_script(
                'game-author-stats-upgrade',
                // get_stylesheet_directory_uri() . '/page-templates/author/stats-upgrade.js',
                ['jquery'],
                true
            );
        }
    } elseif (is_archive()) {
        $current_template = $post_type;
        $view_type = 'main';
    } elseif (is_single()) {
        $current_template = $post_type;
        $view_type = 'single';
    } elseif (is_page()) {
        // Sprawdź, czy używany jest szablon strony
        $template_slug = get_page_template_slug();
        if ($template_slug) {
            // Konwersja ścieżki szablonu do prostego identyfikatora
            $current_template = basename($template_slug, '.php');
        } else {
            $current_template = 'page';
        }
    } elseif (is_front_page()) {
        $current_template = 'front-page';
    }

    // Ładowanie skryptów i stylów dla konkretnego szablonu
    if (!empty($current_template)) {
        $template_style_path = '';
        $template_script_path = '';

        // Sprawdź, czy to jest standardowy widok (archive/single) dla CPT
        if (!empty($post_type) && in_array($view_type, ['main', 'single'])) {
            // Zakładamy strukturę katalogów: page-templates/{post_type}/{main|single}/
            $template_dir = get_stylesheet_directory() . '/page-templates/' . $post_type . '/' . $view_type;

            if (is_dir($template_dir)) {
                // Szukamy style.css 
                if (file_exists($template_dir . '/style.css')) {
                    $template_style_path = get_stylesheet_directory_uri() . '/page-templates/' . $post_type . '/' . $view_type . '/style.css';
                }

                // Szukamy script.js lub main.js
                if (file_exists($template_dir . '/script.js')) {
                    $template_script_path = get_stylesheet_directory_uri() . '/page-templates/' . $post_type . '/' . $view_type . '/script.js';
                } elseif (file_exists($template_dir . '/main.js')) {
                    $template_script_path = get_stylesheet_directory_uri() . '/page-templates/' . $post_type . '/' . $view_type . '/main.js';
                }
            }
        } elseif (strpos($current_template, 'template-') === 0) {
            // Dla szablonów stron z page-templates
            $template_name = str_replace('template-', '', $current_template);
            $parent_dir = dirname(str_replace('-', '/', $template_name));

            // Sprawdzanie stylu w katalogu szablonu
            $style_path = get_stylesheet_directory() . '/page-templates/' . $parent_dir . '/style.css';
            if (file_exists($style_path)) {
                $template_style_path = get_stylesheet_directory_uri() . '/page-templates/' . $parent_dir . '/style.css';
            }

            // Sprawdzanie skryptu w katalogu szablonu
            $script_path = get_stylesheet_directory() . '/page-templates/' . $parent_dir . '/main.js';
            if (!file_exists($script_path)) {
                // Próba znalezienia script.js jako alternatywy
                $script_path = get_stylesheet_directory() . '/page-templates/' . $parent_dir . '/script.js';
            }

            if (file_exists($script_path)) {
                $template_script_path = get_stylesheet_directory_uri() . '/page-templates/' . $parent_dir . '/' . basename($script_path);
            }
        } else {
            // Dla standardowych typów stron (page, author, itp.)
            $style_path = get_stylesheet_directory() . '/assets/css/' . $current_template . '.css';
            if (file_exists($style_path)) {
                $template_style_path = get_stylesheet_directory_uri() . '/assets/css/' . $current_template . '.css';
            }

            // Sprawdzanie skryptu
            $script_path = get_stylesheet_directory() . '/assets/js/' . $current_template . '.js';
            if (file_exists($script_path)) {
                $template_script_path = get_stylesheet_directory_uri() . '/assets/js/' . $current_template . '.js';
            }
        }

        // Sprawdź komponenty w template-parts (np. components/npc)
        if (strpos($current_template, 'npc') !== false || is_singular('npc')) {
            $component_script_path = get_stylesheet_directory() . '/template-parts/components/npc/script.js';
            if (file_exists($component_script_path)) {
                wp_enqueue_script(
                    'game-npc-component-script',
                    get_stylesheet_directory_uri() . '/template-parts/components/npc/script.js',
                    $script_dependencies,
                    filemtime($component_script_path),
                    true
                );
            }
        }

        // Załaduj styl dla tego szablonu, jeśli istnieje
        if (!empty($template_style_path)) {
            wp_enqueue_style(
                'game-' . $current_template . ($view_type ? '-' . $view_type : '') . '-style',
                $template_style_path,
                [],
                filemtime(str_replace(get_stylesheet_directory_uri(), get_stylesheet_directory(), $template_style_path))
            );
        }

        // Załaduj skrypt dla tego szablonu, jeśli istnieje
        if (!empty($template_script_path)) {
            // wp_enqueue_script(
            //     'game-' . $current_template . ($view_type ? '-' . $view_type : '') . '-script',
            //     $template_script_path,
            //     $script_dependencies,
            //     filemtime(str_replace(get_stylesheet_directory_uri(), get_stylesheet_directory(), $template_script_path)),
            //     true
            // );
        }
    }

    // Dodaj informację o debugowaniu, jeśli WP_DEBUG jest włączone
    if (defined('WP_DEBUG') && WP_DEBUG) {
        wp_localize_script('game-main-js', 'GameDebug', [
            'template' => $current_template,
            'viewType' => $view_type,
            'postType' => $post_type,
            'loadedStyle' => !empty($template_style_path) ? $template_style_path : 'Brak',
            'loadedScript' => !empty($template_script_path) ? $template_script_path : 'Brak'
        ]);
    }
}
add_action('wp_enqueue_scripts', 'game_enqueue_scripts_and_styles2');

// Funkcja pomocnicza do rejestrowania skryptów niestandardowych
function game_register_custom_script($name, $path, $deps = ['jquery'], $in_footer = true)
{
    if (file_exists(get_stylesheet_directory() . $path)) {
        wp_register_script(
            'game-' . $name,
            get_stylesheet_directory_uri() . $path,
            $deps,
            filemtime(get_stylesheet_directory() . $path),
            $in_footer
        );
    }
}

// Funkcja pomocnicza do rejestrowania stylów niestandardowych
function game_register_custom_style($name, $path, $deps = [])
{
    if (file_exists(get_stylesheet_directory() . $path)) {
        wp_register_style(
            'game-' . $name,
            get_stylesheet_directory_uri() . $path,
            $deps,
            filemtime(get_stylesheet_directory() . $path)
        );
    }
}
