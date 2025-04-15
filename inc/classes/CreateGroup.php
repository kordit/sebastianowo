<?php
function handle_create_group()
{
    try {
        check_ajax_referer('dm_nonce', 'nonce');
        if (!is_user_logged_in()) {
            throw new Exception('Wymagane logowanie');
        }
        $user_id = get_current_user_id();
        $title = sanitize_text_field($_POST['title'] ?? '');
        if (empty($title)) {
            throw new Exception('Brak nazwy grupy');
        }
        $teren_id = isset($_POST['teren_id']) ? intval($_POST['teren_id']) : 0;

        // Sprawdź, czy użytkownik już ma przypisaną grupę – zakładamy, że pole użytkownika nazywa się "przynaleznosc_do_grupy"
        $user_group = get_field('my_group', "user_{$user_id}");
        if ($user_group) {
            throw new Exception('Jesteś już przypisany do grupy. Nie możesz założyć nowej.');
        }

        // Sprawdź, czy grupa o podanej nazwie już istnieje
        $existing = get_page_by_title($title, OBJECT, 'group');
        if ($existing) {
            throw new Exception('Grupa o tej nazwie już istnieje.');
        }

        // Tworzymy nową grupę (CPT "group")
        $post_args = array(
            'post_title'  => $title,
            'post_status' => 'publish', // lub 'draft'
            'post_type'   => 'group',
        );
        $post_id = wp_insert_post($post_args);
        if (is_wp_error($post_id)) {
            throw new Exception('Błąd przy tworzeniu grupy');
        }

        // Aktualizujemy pola ACF dla CPT "group"
        // Ustawiamy użytkownika jako lidera – pole ACF "leader"
        update_field('field_leader', $user_id, $post_id);
        // Ustawiamy członków grupy – pole ACF "the_villagers"
        update_field('field_the_villagers', [$user_id], $post_id);
        // Aktualizujemy pole użytkownika, aby przypisać grupę – pole "przynaleznosc_do_grupy"
        update_field('przynaleznosc_do_grupy', $post_id, "user_{$user_id}");
        // Jeśli przekazano ID terenu, aktualizujemy powiązania
        if ($teren_id > 0) {
            // Przykładowo: dodajemy ID terenu do pola ACF "field_teren_grupy" w grupie
            update_field('field_teren_grupy', [$teren_id], $post_id);
            // I opcjonalnie: ustawiamy w CPT "tereny" pole "siedziba_grupy" na to ID grupy
            update_field('siedziba_grupy', $post_id, $teren_id);
        }
        $post_url = get_permalink($post_id);

        wp_send_json_success([
            'message' => 'Grupa została utworzona',
            'post_url'  => $post_url
        ]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_create_group', 'handle_create_group');
add_action('wp_ajax_nopriv_create_group', 'handle_create_group');
