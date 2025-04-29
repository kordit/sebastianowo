<?php

/**
 * Funkcja AJAX do pobierania dialogów NPC na podstawie ID podstrony
 */
function get_npc_dialogs_by_post_id()
{
    // Sprawdź nonce dla bezpieczeństwa
    // if (!wp_verify_nonce($_POST['security'], 'npc_dialog_nonce')) {
    //     wp_send_json_error([
    //         'message' => 'Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.'
    //     ]);
    //     wp_die();
    // }

    // Pobierz ID podstrony z żądania
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    // Pobierz scenę z żądania (jeśli została przekazana)
    $scene_id = isset($_POST['scene_id']) ? sanitize_text_field($_POST['scene_id']) : '';

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

        // Pobierz ID sceny - jeśli nie istnieje, użyj indeksu
        $current_scene_id = isset($scene['id_sceny']) && !empty($scene['id_sceny'])
            ? $scene['id_sceny']
            : 'scene_' . ($scene_index + 1);

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
            $dialogs_data[$current_scene_id] = $scene_dialogs;
        }
    }

    wp_send_json_success([
        'post_id' => $post_id,
        'selected_scene' => !empty($scene_id) ? $scene_id : 'main',
        'dialogs' => $dialogs_data
    ]);

    wp_die();
}

// Dodaj akcje AJAX dla zalogowanych i niezalogowanych użytkowników
add_action('wp_ajax_get_npc_dialogs', 'get_npc_dialogs_by_post_id');
add_action('wp_ajax_nopriv_get_npc_dialogs', 'get_npc_dialogs_by_post_id');

/**
 * Funkcja AJAX do pobierania informacji o rejonie
 */
function get_area_info()
{
    // Sprawdzenie, czy mamy ID rejonu
    if (!isset($_POST['area_id']) || empty($_POST['area_id'])) {
        wp_send_json_error(array('message' => 'Nie podano ID rejonu'));
        wp_die();
    }

    $area_id = intval($_POST['area_id']);

    // Pobierz dane rejonu
    $area = get_post($area_id);

    if (!$area || $area->post_type !== 'tereny') {
        wp_send_json_error(array('message' => 'Nie znaleziono rejonu o podanym ID'));
        wp_die();
    }

    // Przygotuj dane rejonu do wysłania
    $area_data = array(
        'id' => $area->ID,
        'name' => $area->post_title,
        'slug' => $area->post_name,
        'description' => get_field('teren_opis', $area->ID)
    );

    wp_send_json_success($area_data);
    wp_die();
}
add_action('wp_ajax_get_area_info', 'get_area_info');
add_action('wp_ajax_nopriv_get_area_info', 'get_area_info');

/**
 * Funkcja AJAX do odblokowywania rejonu dla użytkownika
 */
function unlock_area_for_user()
{
    // Sprawdzenie, czy mamy ID rejonu
    if (!isset($_POST['area_id']) || empty($_POST['area_id'])) {
        wp_send_json_error(array('message' => 'Nie podano ID rejonu'));
        wp_die();
    }

    $area_id = intval($_POST['area_id']);

    // Sprawdź czy rejon istnieje
    $area = get_post($area_id);
    if (!$area || $area->post_type !== 'tereny') {
        wp_send_json_error(array('message' => 'Nie znaleziono rejonu o podanym ID'));
        wp_die();
    }

    // Pobierz aktualnego użytkownika
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error(array('message' => 'Użytkownik nie jest zalogowany'));
        wp_die();
    }

    // Pobierz dostępne rejony użytkownika
    $available_areas = get_field('available_areas', 'user_' . $current_user_id);

    // Jeśli pole nie istnieje, utwórz je jako pustą tablicę
    if (!is_array($available_areas)) {
        $available_areas = array();
    }

    // Sprawdź, czy rejon jest już odblokowany
    if (in_array($area_id, $available_areas)) {
        wp_send_json_success(array(
            'message' => 'Ten rejon jest już odblokowany',
            'already_unlocked' => true
        ));
        wp_die();
    }

    // Dodaj nowy rejon do listy odblokowanych
    $available_areas[] = $area_id;

    // Zaktualizuj pole ACF dla użytkownika
    update_field('available_areas', $available_areas, 'user_' . $current_user_id);

    wp_send_json_success(array(
        'message' => 'Rejon został odblokowany',
        'area_id' => $area_id,
        'area_name' => $area->post_title
    ));
    wp_die();
}
add_action('wp_ajax_unlock_area_for_user', 'unlock_area_for_user');
// Nie dodajemy wp_ajax_nopriv_unlock_area_for_user, ponieważ tylko zalogowani użytkownicy mogą odblokowywać rejony

/**
 * Funkcja AJAX do zmiany aktualnego rejonu użytkownika
 */
function change_current_area_for_user()
{
    // Sprawdzenie, czy mamy ID rejonu
    if (!isset($_POST['area_id']) || empty($_POST['area_id'])) {
        wp_send_json_error(array('message' => 'Nie podano ID rejonu'));
        wp_die();
    }

    $area_id = intval($_POST['area_id']);

    // Sprawdź czy rejon istnieje
    $area = get_post($area_id);
    if (!$area || $area->post_type !== 'tereny') {
        wp_send_json_error(array('message' => 'Nie znaleziono rejonu o podanym ID'));
        wp_die();
    }

    // Pobierz aktualnego użytkownika
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error(array('message' => 'Użytkownik nie jest zalogowany'));
        wp_die();
    }

    // Pobierz dostępne rejony użytkownika
    $available_areas = get_field('available_areas', 'user_' . $current_user_id);

    // Sprawdź, czy rejon jest odblokowany dla użytkownika
    if (!is_array($available_areas) || !in_array($area_id, $available_areas)) {
        wp_send_json_error(array(
            'message' => 'Ten rejon nie jest odblokowany dla tego użytkownika',
        ));
        wp_die();
    }

    // Zaktualizuj pole current_area dla użytkownika
    update_field('current_area', $area_id, 'user_' . $current_user_id);

    wp_send_json_success(array(
        'message' => 'Aktualny rejon został zmieniony',
        'area_id' => $area_id,
        'area_name' => $area->post_title
    ));
    wp_die();
}
add_action('wp_ajax_change_current_area', 'change_current_area_for_user');
// Nie dodajemy wp_ajax_nopriv_change_current_area, ponieważ tylko zalogowani użytkownicy mogą zmieniać rejon
