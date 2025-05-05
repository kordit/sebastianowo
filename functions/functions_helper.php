<?php
function handle_create_custom_popup()
{
    check_ajax_referer('dm_nonce', 'nonce');
    $image_id    = intval($_POST['image_id'] ?? 0);
    $header      = sanitize_text_field($_POST['header'] ?? '');
    $description = sanitize_text_field($_POST['description'] ?? '');
    $link        = esc_url_raw($_POST['link'] ?? '');
    $linkLabel   = sanitize_text_field($_POST['linkLabel'] ?? '');
    $status      = sanitize_text_field($_POST['status'] ?? 'success'); // np. success, error itp.
    $closeable   = isset($_POST['closeable']) && $_POST['closeable'] === 'true' ? true : false;

    $image_markup = $image_id > 0 ? wp_get_attachment_image($image_id, 'full') : '';
    $close_button = $closeable ? '<button class="popup-close">×</button>' : '';

    $html  = '<div class="popup-full ' . esc_attr($status) . '">';
    $html .= '<div class="container">';
    if ($image_id) {
        $html .= '<div class="polaroid">' . $image_markup . '</div>';
    }
    $html .= '<h2>' . esc_html($header) . '</h2>';
    $html .= '<p class="description">' . esc_html($description) . '</p>';
    if ($link && $linkLabel) {
        $html .= '<a href="' . esc_url($link) . '" class="btn">' . esc_html($linkLabel) . '</a>';
    }
    $html .= $close_button;
    $html .= '</div>';
    $html .= '</div>';

    wp_send_json_success(['popup' => $html]);
}
add_action('wp_ajax_create_custom_popup', 'handle_create_custom_popup');
