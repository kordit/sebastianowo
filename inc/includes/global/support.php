<?php
add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename, $mimes) {
    $filetype = wp_check_filetype($filename, $mimes);
    return [
        'ext'             => $filetype['ext'],
        'type'            => $filetype['type'],
        'proper_filename' => $data['proper_filename']
    ];
}, 10, 4);

function cc_mime_types($mimes)
{
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

function fix_svg()
{
    echo '<style type="text/css">
	.attachment-266x266, .thumbnail img {
		width: 100% !important;
		height: auto !important;
	}
	</style>';
}
add_action('admin_head', 'fix_svg');

function custom_user_registration()
{
    if (isset($_POST['wp-submit']) && $_POST['wp-submit'] == 'Zarejestruj się') {
        $user_login = sanitize_user($_POST['user_login']);
        $user_email = sanitize_email($_POST['user_email']);

        if (!empty($user_login) && !empty($user_email)) {
            $user_id = register_new_user($user_login, $user_email);
            if (!is_wp_error($user_id)) {
                echo '<p>Rejestracja zakończona sukcesem. Sprawdź swoją skrzynkę e-mail.</p>';
            } else {
                echo '<p>Wystąpił błąd: ' . $user_id->get_error_message() . '</p>';
            }
        } else {
            echo '<p>Proszę wypełnić wszystkie pola.</p>';
        }
    }
}
add_action('init', 'custom_user_registration');

function et_svg($linksvg, $type = 0)
{
    if ($type == 1) {
        $link = $linksvg;
    } elseif ($type == 2) {
        $link = get_site_url() . $linksvg;
    } else {
        $link = $linksvg;
    }
    $link = preg_replace('/https?\:\/\/[^\/]*\//', '', $link);
    if (file_exists($link)) {
        $svg_file = file_get_contents($link);
        $find_string = "<svg";
        $position = strpos($svg_file, $find_string);
        $svg_file_new = substr($svg_file, $position);
        echo $svg_file_new;
    } else {
        if (is_admin()) {
            $link = "/" . $link;
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $link)) {
                $svg_file = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $link);
                $find_string = "<svg";
                $position = strpos($svg_file, $find_string);
                $svg_file_new = substr($svg_file, $position);
                echo $svg_file_new;
            }
        }
    }
}


function remove_admin_menus()
{
    if (current_user_can('administrator')) {
        remove_menu_page('edit.php');          // Wpisy
        remove_menu_page('edit.php?post_type=page'); // Strony
        remove_menu_page('edit-comments.php');  // Komentarze
        remove_menu_page('themes.php');         // Wygląd
        remove_menu_page('plugins.php');        // Wtyczki
        remove_menu_page('tools.php');
    }

    if (current_user_can('administrator')) {
        $user_id = get_current_user_id();

        if ($user_id !== 1) {
            remove_menu_page('options-general.php'); // Ustawienia
        }
    }
}
add_action('admin_menu', 'remove_admin_menus');

if (get_current_user_id() !== 1) {
    add_filter('acf/settings/show_admin', '__return_false');
}


add_theme_support('post-thumbnails');

function mytheme_enqueue_style()
{
    wp_enqueue_style('mytheme-style', get_stylesheet_uri());
    // wp_enqueue_style('game-style', get_stylesheet_directory_uri() .  '/assets/css/style.css');
    wp_enqueue_style('game-style', get_stylesheet_directory_uri() .  '/theme-style.css');
}
add_action('wp_enqueue_scripts',  'mytheme_enqueue_style');

function load_template_assets()
{
    // Pobierz wszystkie publiczne CPT (możesz dodać '_builtin' => false, jeśli chcesz tylko custom post types)
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

        // Ścieżki do plików w systemie plików
        $css_path = THEME_SRC . '/templates/' . $post_type . '/' . $subfolder . '/style.css';
        $js_path  = THEME_SRC . '/templates/' . $post_type . '/' . $subfolder . '/script.js';

        // URL-e do plików
        $css_url = get_stylesheet_directory_uri() . '/templates/' . $post_type . '/' . $subfolder . '/style.css';
        $js_url  = get_stylesheet_directory_uri() . '/templates/' . $post_type . '/' . $subfolder . '/script.js';

        // Ładujemy assety tylko, jeśli pliki istnieją
        if (file_exists($css_path)) {
            wp_enqueue_style("custom-style-{$post_type}-{$subfolder}", $css_url);
        }
        if (file_exists($js_path)) {
            wp_enqueue_script("custom-script-{$post_type}-{$subfolder}", $js_url, array(), false, true);
        }

        // Przerwij pętlę – ładujemy assety tylko dla jednego CPT
        break;
    }
}

add_action('wp_enqueue_scripts', 'load_template_assets');




// add_action('wp_head', 'myplugin_ajaxurl');

function enqueue_dm_scripts()
{
    // Ładujemy skrypt tylko, jeśli użytkownik jest zalogowany i ma prawo modyfikować minerały
    if (is_user_logged_in()) {
        wp_enqueue_script('global-script', get_stylesheet_directory_uri() . '/assets/js/main.js', [], null, true);
        $nonce = wp_create_nonce('dm_nonce');

        wp_localize_script('global-script', 'global', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'dataManagerNonce' => $nonce,
        ]);
        // Skorygowana ścieżka do komponentu NPC w template-parts
        wp_register_script('npc_script', get_template_directory_uri() . '/template-parts/components/npc/script.js', ['jquery'], filemtime(get_template_directory() . '/template-parts/components/npc/script.js'), true);
        wp_enqueue_script('npc_script');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_dm_scripts');

if (is_admin()) {
    remove_action("admin_color_scheme_picker", "admin_color_scheme_picker");
}
function admin_color_scheme()
{
    global $_wp_admin_css_colors;
    $_wp_admin_css_colors = [];
}
add_action('admin_head', 'admin_color_scheme');

add_filter('wp_is_application_passwords_available', '__return_false');

add_action('admin_head', 'hide_avatar_admin_bar');
function hide_avatar_admin_bar()
{
    echo '<style>..edit-attachment-frame .attachment-media-view .details-image {object-fit:contain;}#toplevel_page_theme-general-settings ul li:last-child a { color: #e7ff00; font-weight: bold; }body.user-edit-php h2, .user-url-wrap, .user-admin-bar-front-wrap, .user-syntax-highlighting-wrap, .user-first-name-wrap, .user-last-name-wrap, body.profile-php h2, .user-language-wrap, .user-comment-shortcuts-wrap, .user-rich-editing-wrap, .user-description-wrap, .user-profile-picture {display:none !important;}   </style>';
}

function custom_author_base()
{
    global $wp_rewrite;
    $wp_rewrite->author_base = 'user';
}
add_action('init', 'custom_author_base');
