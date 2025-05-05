<?php
function et_r($var, $class = '')
{
    if ($class) {
        $class_tag = 'class="' . $class . '" ';
    } else {
        $class_tag = '';
    }
    echo '<pre ' . $class_tag .  '>';
    print_r($var);
    echo '</pre>';
}


function count_svg_paths($url)
{
    if (is_admin()) {
        $url = preg_replace('/https?\:\/\/[^\/]*\//', '', '../' . $url);
    } else {
        $url = preg_replace('/http?\:\/\/[^\/]*\//', '', $url);
    }
    if ($url) {
        $count = new SimpleXMLElement($url, 0, TRUE);
        $mycount = count($count->children());
        $mycount = count($count->path);
        $mycount += count($count->polygon);
    } else {
        $mycount = 0;
    }
    return $mycount;
}


function mission_debug_log($message, $data = null)
{
    $log_file = ABSPATH . '/wp-content/themes/game/temp-log.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $log_message = $timestamp . ' ' . $message;

    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_message .= "\n" . print_r($data, true);
        } else {
            $log_message .= ': ' . $data;
        }
    }

    $log_message .= "\n\n";

    // Zapisz do pliku
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

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
add_filter('show_admin_bar', '__return_false');
function modify_user_roles()
{
    global $wp_roles;
    foreach ($wp_roles->roles as $role_name => $role_info) {
        if ($role_name !== 'administrator') {
            remove_role($role_name);
        }
    }

    add_role(
        'gracz',
        __('Gracz'),
        array(
            'read' => true,

        )
    );
}
add_action('init', 'modify_user_roles');


add_filter('manage_users_columns', function ($columns) {
    $new = array();
    $new['cb']         = $columns['cb'];
    $new['username']   = $columns['username'];
    $new['name']       = $columns['name'];
    $new['email']      = $columns['email'];
    $new['nick']       = 'Nick';
    $new['user_class'] = 'Klasa postaci';
    $new['bag']        = 'Plecak';
    $new['stats']      = 'Statystyki';
    $new['my_group']   = 'Moja grupa';
    $new['role']       = $columns['role'];
    $new['posts']      = $columns['posts'];
    return $new;
});

add_action('manage_users_custom_column', function ($value, $column_name, $user_id) {
    if ($column_name === 'nick') {
        return get_field('nick', 'user_' . $user_id);
    }
    if ($column_name === 'user_class') {
        $field = get_field('user_class', 'user_' . $user_id);
        return is_array($field) && isset($field['label']) ? $field['label'] : $field;
    }
    if ($column_name === 'bag') {
        $bag = get_field('bag', 'user_' . $user_id);
        if ($bag && is_array($bag)) {
            $output = '';
            foreach ($bag as $key => $val) {
                $output .= $key . ': ' . $val . '<br>';
            }
            return $output;
        }
    }
    if ($column_name === 'stats') {
        $stats = get_field('stats', 'user_' . $user_id);
        if ($stats && is_array($stats)) {
            $output = '';
            foreach ($stats as $key => $val) {
                $output .= $key . ': ' . $val . '<br>';
            }
            return $output;
        }
    }
    if ($column_name === 'my_group') {
        $group = get_field('my_group', 'user_' . $user_id);
        if ($group && is_object($group)) return get_the_title($group->ID);
    }
    return $value;
}, 10, 3);

function et_svg_with_data($linksvg, $data = [])
{
    $link = preg_replace('/https?\:\/\/[^\/]*\//', '', $linksvg);
    if (!file_exists($link)) {
        if (is_admin()) {
            $link = "/" . $link;
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $link)) {
                return;
            }
        } else {
            return;
        }
    }

    libxml_use_internal_errors(true);
    $svg = new DOMDocument();
    $svg->load($link);

    // Usuwanie wszystkich <title>
    $titles = $svg->getElementsByTagName('title');
    while ($titles->length > 0) {
        $titles->item(0)->parentNode->removeChild($titles->item(0));
    }

    // Pobranie wszystkich <path>
    $paths = $svg->getElementsByTagName('path');

    if ($paths->length === 0) {
        echo "Nie znaleziono żadnych path.";
        return;
    }

    // Iteracja i dodawanie `data-*` z tablicy terenów
    foreach ($paths as $index => $path) {
        if (!isset($data[$index])) {
            break;
        }

        $teren = $data[$index];

        foreach ($teren as $key => $value) {
            if (!empty($value)) {
                $path->setAttribute("data-$key", $value);
            }
        }
    }

    // Wyświetlenie poprawionego SVG
    echo $svg->saveXML();
}
