<?php
class UniversalPostAjaxHandler
{
    public function __construct()
    {
        add_action('wp_ajax_create_custom_post', [$this, 'handle_create_custom_post']);
        add_action('wp_ajax_nopriv_create_custom_post', [$this, 'handle_create_custom_post']);
    }

    public function handle_create_custom_post()
    {
        try {
            check_ajax_referer('dm_nonce', 'nonce');
            if (!is_user_logged_in()) {
                throw new Exception('Wymagane logowanie');
            }
            $title = sanitize_text_field($_POST['title'] ?? '');
            if (empty($title)) {
                throw new Exception('Brak tytułu');
            }
            $post_type = sanitize_text_field($_POST['post_type'] ?? '');
            if (empty($post_type)) {
                throw new Exception('Nie podano typu wpisu');
            }
            // Dla wpisów typu group sprawdzamy, czy wpis o takiej nazwie już nie istnieje
            if ($post_type === 'group') {
                $existing = get_page_by_title($title, OBJECT, $post_type);
                if ($existing) {
                    throw new Exception('Grupa o tej nazwie już istnieje.');
                }
            }
            $post_id = wp_insert_post([
                'post_title'  => $title,
                'post_status' => 'publish',
                'post_type'   => $post_type,
            ]);
            if (!$post_id || is_wp_error($post_id)) {
                throw new Exception('Błąd przy tworzeniu wpisu');
            }
            $acf_fields = [];
            if (isset($_POST['acf_fields'])) {
                $acf_fields = json_decode(stripslashes($_POST['acf_fields']), true);
                if (!is_array($acf_fields)) {
                    $acf_fields = [];
                }
            }
            foreach ($acf_fields as $field_key => $field_value) {
                update_field($field_key, $field_value, $post_id);
            }
            $post_url = get_permalink($post_id);
            wp_send_json_success([
                'message'  => 'Wpis został utworzony',
                'post_url' => $post_url,
                'post_id'  => $post_id
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}

new UniversalPostAjaxHandler();
