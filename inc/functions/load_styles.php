<?php

/**
 * System ładowania arkuszy stylów CSS
 * 
 * Ten plik definiuje sposób ładowania stylów CSS w zależności od aktualnej strony.
 * Style są ładowane tylko wtedy, gdy są potrzebne na danej podstronie.
 */

/**
 * Funkcja rejestrująca i ładująca style CSS
 */
function game_load_styles()
{
    // Zawsze ładujemy główny plik CSS
    wp_enqueue_style(
        'game-main-style',
        get_stylesheet_uri(),
        [],
        filemtime(get_stylesheet_directory() . '/style.css')
    );

    // Ładujemy główny styl motywu
    wp_enqueue_style(
        'game-theme-style',
        get_stylesheet_directory_uri() . '/theme-style.css',
        [],
        filemtime(get_stylesheet_directory() . '/theme-style.css')
    );

    // Ładujemy system powiadomień
    $notification_css_path = get_stylesheet_directory() . '/assets/css/notification-system.css';
    if (file_exists($notification_css_path)) {
        $notification_css_version = filemtime($notification_css_path);
        wp_enqueue_style(
            'game-notifications-style',
            get_stylesheet_directory_uri() . '/assets/css/notification-system.css',
            [],
            $notification_css_version
        );
    }

    // Określenie aktualnego szablonu i typu widoku (main/archive vs single)
    $current_template = '';
    $view_type = '';
    $post_type = get_post_type();

    // Ustalenie typu zawartości i widoku
    if (is_author()) {
        $current_template = 'author';
        // Bezpośrednie ładowanie stylów dla podstrony autora
        $author_style_path = get_stylesheet_directory() . '/page-templates/author/style.css';
        if (file_exists($author_style_path)) {
            wp_enqueue_style(
                'game-author-panel-style',
                get_stylesheet_directory_uri() . '/page-templates/author/style.css',
                [],
                filemtime($author_style_path)
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

    // Ładowanie stylów dla konkretnego szablonu
    if (!empty($current_template)) {
        $template_style_path = '';

        // Sprawdź, czy to jest standardowy widok (archive/single) dla CPT
        if (!empty($post_type) && in_array($view_type, ['main', 'single'])) {
            // Zakładamy strukturę katalogów: page-templates/{post_type}/{main|single}/
            $template_dir = get_stylesheet_directory() . '/page-templates/' . $post_type . '/' . $view_type;

            if (is_dir($template_dir)) {
                // Szukamy style.css 
                if (file_exists($template_dir . '/style.css')) {
                    $template_style_path = $template_dir . '/style.css';
                    wp_enqueue_style(
                        'game-' . $post_type . '-' . $view_type . '-style',
                        get_stylesheet_directory_uri() . '/page-templates/' . $post_type . '/' . $view_type . '/style.css',
                        [],
                        filemtime($template_style_path)
                    );
                }
            }
        } elseif (strpos($current_template, 'template-') === 0) {
            // Dla szablonów stron z page-templates
            $template_name = str_replace('template-', '', $current_template);
            $parent_dir = dirname(str_replace('-', '/', $template_name));

            // Sprawdzanie stylu w katalogu szablonu
            $style_path = get_stylesheet_directory() . '/page-templates/' . $parent_dir . '/style.css';
            if (file_exists($style_path)) {
                wp_enqueue_style(
                    'game-template-' . str_replace('/', '-', $parent_dir) . '-style',
                    get_stylesheet_directory_uri() . '/page-templates/' . $parent_dir . '/style.css',
                    [],
                    filemtime($style_path)
                );
            }
        } else {
            // Dla standardowych typów stron (page, author, itp.)
            $style_path = get_stylesheet_directory() . '/assets/css/' . $current_template . '.css';
            if (file_exists($style_path)) {
                wp_enqueue_style(
                    'game-' . $current_template . '-style',
                    get_stylesheet_directory_uri() . '/assets/css/' . $current_template . '.css',
                    [],
                    filemtime($style_path)
                );
            }
        }
    }

    // Ładowanie stylów dla szablonów CPT z templates/
    load_template_styles();
}

/**
 * Funkcja ładująca style CSS dla szablonów stron
 */
function load_template_styles()
{
    // Pobierz wszystkie publiczne CPT
    $post_types = get_post_types(array('public' => true), 'names');

    foreach ($post_types as $post_type) {
        // Sprawdzamy, czy aktualna strona to archiwum danego CPT
        if (is_post_type_archive($post_type)) {
            $subfolder = 'main';
        }
        // Lub czy jest to pojedynczy wpis danego CPT
        elseif (is_singular($post_type)) {
            $subfolder = 'single';
        } else {
            continue;
        }

        // Ścieżka do pliku CSS w systemie plików
        $css_path = THEME_SRC . '/templates/' . $post_type . '/' . $subfolder . '/style.css';

        // URL do pliku CSS
        $css_url = get_stylesheet_directory_uri() . '/templates/' . $post_type . '/' . $subfolder . '/style.css';

        // Ładujemy style tylko, jeśli plik istnieje
        if (file_exists($css_path)) {
            wp_enqueue_style(
                "custom-style-{$post_type}-{$subfolder}",
                $css_url,
                [],
                filemtime($css_path)
            );
        }

        // Przerwij pętlę – ładujemy assety tylko dla jednego CPT
        break;
    }
}

// Zarejestruj funkcję ładującą style
add_action('wp_enqueue_scripts', 'game_load_styles');

/**
 * Funkcja pomocnicza do rejestrowania stylów niestandardowych
 */
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
