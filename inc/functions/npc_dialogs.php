<?php


/**
 * Enqueue skryptów potrzebnych do obsługi dialogów NPC
 */
function enqueue_npc_dialog_scripts()
{
    // Zarejestruj i załaduj skrypt
    wp_enqueue_script(
        'npc-dialogs-js',
        get_template_directory_uri() . '/assets/js/npc-dialogs.js',
        ['jquery'],
        filemtime(get_template_directory() . '/assets/js/npc-dialogs.js'),
        true
    );

    // Przekaż dane do JS
    wp_localize_script(
        'npc-dialogs-js',
        'npcDialogsData',
        [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('npc_dialog_nonce')
        ]
    );
}
add_action('wp_enqueue_scripts', 'enqueue_npc_dialog_scripts');

/**
 * Funkcja AJAX do pobierania dialogów NPC na podstawie ID podstrony
 */
function get_npc_dialogs_by_post_id()
{
    // Sprawdź nonce dla bezpieczeństwa
    if (!wp_verify_nonce($_POST['security'], 'npc_dialog_nonce')) {
        wp_send_json_error([
            'message' => 'Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.'
        ]);
        wp_die();
    }

    // Pobierz ID podstrony z żądania
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (!$post_id) {
        wp_send_json_error([
            'message' => 'Nie podano prawidłowego ID podstrony.'
        ]);
        wp_die();
    }

    // Pobierz post
    $post = get_post($post_id);

    if (!$post) {
        wp_send_json_error([
            'message' => 'Nie znaleziono podstrony o podanym ID.'
        ]);
        wp_die();
    }

    // Pobierz tytuł posta i przekształć go na slug
    $post_title = sanitize_title($post->post_title);

    // Pobierz sceny dla posta
    $scenes = get_field('scenes', $post_id);

    if (!$scenes || !is_array($scenes)) {
        wp_send_json_error([
            'message' => 'Nie znaleziono scen dla tej podstrony.'
        ]);
        wp_die();
    }

    $dialogs_data = [];

    // Iteruj przez każdą scenę i zbierz dialogi
    foreach ($scenes as $scene_index => $scene) {
        if (empty($scene['maska'])) {
            continue;
        }

        // Pobierz rozmowy dla danej sceny
        $field_name = "scene_{$scene_index}_rozmowy";
        $rozmowy = get_field($field_name, $post_id);

        if (!$rozmowy || !is_array($rozmowy)) {
            continue;
        }

        $scene_dialogs = [];

        foreach ($rozmowy as $rozmowa) {
            $slug = isset($rozmowa['rozmowy_slug']) ? $rozmowa['rozmowy_slug'] : '';

            if (empty($slug) || empty($rozmowa['rozmowy_dialogow'])) {
                continue;
            }

            $dialogi = [];

            foreach ($rozmowa['rozmowy_dialogow'] as $dialog) {
                $npc_id = isset($dialog['dialog_npc']) ? intval($dialog['dialog_npc']) : 0;
                $wiadomosc = isset($dialog['wiadomosc']) ? $dialog['wiadomosc'] : '';

                if ($npc_id && $wiadomosc) {
                    $dialogi[] = [
                        'npc_id' => $npc_id,
                        'message' => $wiadomosc
                    ];
                }
            }

            // Dodajemy informacje o zakończeniu dialogu
            $koniec_dialogu = [
                'akcja' => isset($rozmowa['koniec_dialogu_akcja']) ? $rozmowa['koniec_dialogu_akcja'] : 'nic'
            ];

            // Jeśli akcja to "otworz_chat", dodajemy ID NPC do chatu
            if ($koniec_dialogu['akcja'] === 'otworz_chat' && isset($rozmowa['koniec_dialogu_npc'])) {
                $koniec_dialogu['npc_id'] = intval($rozmowa['koniec_dialogu_npc']);
            }

            // Jeśli akcja to "misja", dodajemy nazwę funkcji i parametry
            if ($koniec_dialogu['akcja'] === 'misja') {
                if (isset($rozmowa['koniec_dialogu_misja_nazwa'])) {
                    $koniec_dialogu['funkcja'] = $rozmowa['koniec_dialogu_misja_nazwa'];
                }

                if (isset($rozmowa['koniec_dialogu_misja_parametry']) && is_array($rozmowa['koniec_dialogu_misja_parametry'])) {
                    $parametry = [];
                    foreach ($rozmowa['koniec_dialogu_misja_parametry'] as $param) {
                        if (isset($param['parametr_nazwa']) && isset($param['parametr_wartosc'])) {
                            $parametry[$param['parametr_nazwa']] = $param['parametr_wartosc'];
                        }
                    }
                    $koniec_dialogu['parametry'] = $parametry;
                }
            }

            $scene_dialogs[$slug] = [
                'dialogi' => $dialogi,
                'koniec_dialogu' => $koniec_dialogu
            ];
        }

        if (!empty($scene_dialogs)) {
            $scene_id = isset($scene['id_sceny']) ? $scene['id_sceny'] : 'scene_' . ($scene_index + 1);
            $dialogs_data[$scene_id] = $scene_dialogs;
        }
    }

    wp_send_json_success([
        'post_id' => $post_id,
        'dialogs' => $dialogs_data
    ]);

    wp_die();
}

// Dodaj akcje AJAX dla zalogowanych i niezalogowanych użytkowników
add_action('wp_ajax_get_npc_dialogs', 'get_npc_dialogs_by_post_id');
add_action('wp_ajax_nopriv_get_npc_dialogs', 'get_npc_dialogs_by_post_id');
