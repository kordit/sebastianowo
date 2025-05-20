<?php
function load_theme_files(array $dirs)
{
    foreach ($dirs as $dir) {
        $path = get_template_directory() . '/' . trim($dir, '/');
        foreach (glob($path . '/*.php') as $file) {
            require_once $file;
        }
    }
}

load_theme_files([
    'includes/core',
    'includes/register-cpt',
    'includes/class',
    'includes/functions',
    'includes/dynamic-fields'
]);

add_action('wp_footer', function () {
    $current_area_id = get_field('current_area', 'user_' . get_current_user_id());
    if (!$current_area_id) return;

    $url = get_permalink($current_area_id);

    echo '<script>
        const path = window.location.pathname.replace(/\/+$/, "");
        if (path === "" || path === "/") {
            window.location.href = "' . esc_url($url) . '";
        }
    </script>';
});
