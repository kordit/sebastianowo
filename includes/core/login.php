<?php
function redirect_non_logged_users()
{
    if (!is_user_logged_in() && !is_admin() && !is_front_page()) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('template_redirect', 'redirect_non_logged_users');

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
