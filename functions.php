<?php
function redirect_non_logged_users()
{
    if (!is_user_logged_in() && !is_admin() && !is_front_page()) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('template_redirect', 'redirect_non_logged_users');

define('TEMPLATE_PATH', get_stylesheet_directory() . '/templates/');
define('SITE_URL', get_home_url());
define('THEME_URL', get_stylesheet_directory_uri());
define('THEME_SRC', get_stylesheet_directory());
define('ASSETS_DIR', get_stylesheet_directory() . '/assets');
define('ASSETS_URL', get_stylesheet_directory_uri() . '/assets');

define('IMAGES', ASSETS_DIR . "/images/");
define('PNG', ASSETS_URL . "/images/png");
define('SVG', ASSETS_URL . "/images/svg/");
define('CSS', ASSETS_DIR . "/css/");
define('JS', ASSETS_DIR . "/js/");
require_once('inc/includes/include-partials.php');
require_once('inc/includes/global/support.php');
require_once('inc/functions/npc_dialogs.php');
require_once('inc/functions/item_management.php');
require_once('inc/functions/inventory.php');

function et_image($acffield, $size = "full", $url = false, $class = '')
{

    if (get_sub_field($acffield)) {
        $typeacf = get_sub_field($acffield);
    } elseif (get_field($acffield)) {
        $typeacf = get_field($acffield);
    } elseif (get_sub_field($acffield, "options")) {
        $typeacf = get_sub_field($acffield, "options");
    } elseif (get_field($acffield, "options")) {
        $typeacf = get_field($acffield, "options");
    } else {
        $webimage = NULL;
    }
    if (isset($typeacf) && is_array($typeacf)) {
        $typeacf = $typeacf['ID'];
    }
    if (isset($typeacf)) {
        $webimage = wp_get_attachment_image_url($typeacf, $size);

        if ($url == true) {
            return $webimage;
        } else {
            $pieces = explode("/", $webimage);
            $pathend = end($pieces);
            $newstring = substr($pathend, -3);
            $newstring4 = substr($pathend, -4);
            if ($newstring == 'jpg' || $newstring == 'png' || $newstring4 == 'jpeg' || $newstring4 == 'webp') {
                echo wp_get_attachment_image($typeacf, $size, false, array("class" => $class));
            } elseif ($newstring == 'svg') {
                $webimage = preg_replace('/https?\:\/\/[^\/]*\//', '', $webimage);
                echo et_svg($webimage);
            }
        }
    }
}

add_image_size('full', 1920, 1920);

function get_all_instance()
{
    global $wpdb;
    $option_name_pattern = 'options_instance_%_nazwa_instancji';
    $query = $wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name LIKE %s", $option_name_pattern);
    $results = $wpdb->get_results($query, ARRAY_A); // Zwracanie wyników jako tablica asocjacyjna

    // Wyciągnij tylko wartości z wyników
    $instances = array_map(function ($result) {
        return $result['option_value']; // Pobieramy tylko wartość opcji
    }, $results);

    return $instances; // Zwracamy tablicę wartości
}

function load_all_files_php_from_directory($directory, $priority_files = [])
{
    if (is_dir($directory)) {
        // Najpierw ładuj pliki z tablicy $priority_files
        foreach ($priority_files as $file) {
            $filename = $directory . '/' . $file;
            if (file_exists($filename)) {
                require_once $filename;
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    echo "File does not exist: " . $filename . "<br>";
                }
            }
        }

        // Ładuj pozostałe pliki alfabetycznie, z pominięciem już załadowanych
        $loaded_files = array_map(function ($file) use ($directory) {
            return $directory . '/' . $file;
        }, $priority_files);

        foreach (glob($directory . '/*.php') as $filename) {
            if (!in_array($filename, $loaded_files)) {
                require_once $filename;
            }
        }
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo "Directory does not exist: " . $directory . "<br>";
        }
    }
}


load_all_files_php_from_directory(get_template_directory() . '/inc/functions');

add_action('init', function () {
    // Ładowanie wszystkich klas z katalogu 'inc/classes'
    load_all_files_php_from_directory(get_template_directory() . '/inc/classes');
    new InstanceManager('walka', true);
    new InstanceManager('plecak', true);

    $all_instance = get_all_instance();
    foreach ($all_instance as $istance) {
        new InstanceManager(sanitize_title($istance), true); // Ustawienie parametru load_assets na true dla wszystkich instancji
    }
});
flush_rewrite_rules();

add_action('after_setup_theme', function () {
    if (!current_user_can('administrator')) {
        show_admin_bar(false);
    }
});

add_action('admin_init', function () {
    if (!current_user_can('administrator') && !wp_doing_ajax()) {
        wp_redirect(home_url());
        exit;
    }
});

add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || current_user_can('administrator')) return;
    $request_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $user_id = get_current_user_id();
    if (!$user_id) return;
    $klasa_postaci = get_field('user_class', 'user_' . $user_id);
    $user = get_user_by('ID', $user_id);
    $user_url = 'user/' . $user->user_nicename;

    if ($request_path === 'kreator') {
        if (!empty($klasa_postaci)) {
            wp_redirect(home_url($user_url));
            exit;
        }
    } elseif ($request_path === $user_url) {
        if (empty($klasa_postaci)) {
            wp_redirect(home_url('kreator'));
            exit;
        }
    } else {
        if (empty($klasa_postaci)) {
            wp_redirect(home_url('kreator'));
            exit;
        }
    }
});

add_action('init', function () {
    add_rewrite_rule('user/me/?$', 'index.php?user_me=1', 'top');
    flush_rewrite_rules(false);
});

add_filter('query_vars', function ($query_vars) {
    $query_vars[] = 'user_me';
    return $query_vars;
});

function custom_query_vars($vars)
{
    $vars[] = 'scene_id';
    return $vars;
}
add_filter('query_vars', 'custom_query_vars');

function custom_rewrite_rules()
{
    add_rewrite_rule(
        '^([^/]+)/([^/]+)/([^/]+)/?$',
        'index.php?post_type=$matches[1]&name=$matches[2]&scene_id=$matches[3]',
        'top'
    );
}
add_action('init', 'custom_rewrite_rules');

function redirect_main_segment()
{
    $request_uri = $_SERVER['REQUEST_URI'];

    // Sprawdza, czy URL kończy się na "/main/" i ma dokładnie dwa segmenty przed nim
    if (preg_match('#^/[^/]+/[^/]+/main/?$#', $request_uri)) {
        $new_url = preg_replace('#/main/?$#', '', $request_uri);
        wp_redirect($new_url, 301);
        exit;
    }
}
add_action('template_redirect', 'redirect_main_segment');



add_action('template_redirect', function () {
    if (get_query_var('user_me')) {
        if (is_user_logged_in()) {
            wp_redirect(get_author_posts_url(get_current_user_id()));
        } else {
            wp_redirect(wp_login_url());
        }
        exit;
    }
});

require_once('inc/includes/svg-group.php');
