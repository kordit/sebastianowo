<?php
function clean_array($data, $keysToRemove = ['layout_settings', 'acfe_flexible_toggle'])
{
    if (!is_array($data)) return $data;
    foreach ($data as $key => $value) {
        if (in_array($key, $keysToRemove, true)) {
            unset($data[$key]);
        } elseif (is_array($value)) {
            $data[$key] = clean_array($value, $keysToRemove);
        }
    }
    return $data;
}

function evaluate_condition($conv, $npc_relation_value, $npc_relation_meet, $conditions, $npc_id)
{
    if (isset($conv['conversation_start']) && is_array($conv['conversation_start'])) {
        $conv = $conv['conversation_start'][0];
    }
    if (!isset($conv['if'])) {
        return false;
    }
    $type = $conv['if'];
    switch ($type) {
        case 'relation':
            if (!isset($conv['relation_select']['value'])) {
                return false;
            }
            $operator = $conv['relation_select']['operator'];
            $value = $conv['relation_select']['value'];
            $npcVal = (float)$npc_relation_value;
            $condVal = (float)$value;
            switch ($operator) {
                case '>':
                    return $npcVal > $condVal;
                case '>=':
                    return $npcVal >= $condVal;
                case '=':
                    return $npcVal == $condVal;
                case '<':
                    return $npcVal < $condVal;
                case '=>':
                    return $npcVal <= $condVal;
                case 'exist':
                    return ((int)$npc_relation_meet === (int)$value);
                default:
                    return false;
            }
        case 'scena':
            // Porównanie sceny: bierzemy wartość z warunku i porównujemy z kluczem 'scena'
            if (isset($conv['scena'])) {
                return trim($conv['scena']) === trim($conditions['value'] ?? '');
            }
            return false;
        case 'mission':
            ob_start();
            $active_user_missions = get_fields('user_' . get_current_user_id());

            // Pobierz dane warunku misji
            $mission_select = $conv['mission_select'];
            $mission_select_id = $mission_select['mission_id'] ?? null;
            $mission_select_status = $mission_select['mission_status'] ?? null;
            $mission_select_task_id = $mission_select['mission_task_id'] ?? null;
            $mission_select_task_status = $mission_select['mission_task_status'] ?? null;


            // Logika sprawdzania misji
            $result = false;

            // Sprawdź czy podano ID misji i czy istnieje w danych użytkownika
            if ($mission_select_id && isset($active_user_missions['mission_' . $mission_select_id])) {
                $user_mission = $active_user_missions['mission_' . $mission_select_id];

                // Domyślnie zakładamy, że warunek jest spełniony
                $result = true;

                // Sprawdź status misji jeśli został określony w warunku
                if ($mission_select_status && isset($user_mission['status'])) {
                    // Możliwe statusy: not_started, in_progress, completed, failed
                    if ($user_mission['status'] !== $mission_select_status) {
                        $result = false;
                    }
                }

                // Sprawdź status zadania jeśli został określony w warunku
                if ($result && $mission_select_task_id && $mission_select_task_status) {
                    // Sprawdź czy zadanie istnieje w misji
                    if (!isset($user_mission['tasks'][$mission_select_task_id])) {
                        $result = false;
                    } else {
                        $task = $user_mission['tasks'][$mission_select_task_id];

                        // Sprawdź czy status zadania kończy się na "_npc"
                        if (preg_match('/_npc$/', $mission_select_task_status)) {
                            // Jeśli tak, sprawdź status dla konkretnego NPC
                            if (is_array($task) && isset($task['npc_' . $npc_id])) {
                                // Uzyskaj właściwy status NPC (bez "_npc" na końcu)
                                $npc_status_type = str_replace('_npc', '', $mission_select_task_status);
                                // Porównaj status NPC
                                if ($task['npc_' . $npc_id] !== $npc_status_type) {
                                    $result = false;
                                }
                            } else {
                                // Brak statusu dla tego NPC
                                $result = false;
                            }
                        } else {
                            // Standardowa logika dla zwykłych statusów zadań
                            if (is_array($task) && isset($task['status'])) {
                                // Zadanie jest tablicą - sprawdź status w tablicy
                                if ($task['status'] !== $mission_select_task_status) {
                                    $result = false;
                                }
                            } else {
                                // Zadanie jest prostą wartością - bezpośrednie porównanie
                                if ($task !== $mission_select_task_status) {
                                    $result = false;
                                }
                            }
                        }
                    }
                }
            }

            // Zapisz dane do logów
            print_r($npc_id);
            print_r($mission_select);
            print_r($active_user_missions);
            $dump = ob_get_clean();

            file_put_contents(
                '/Users/kordiansasiela/localhost/seb.soeasy.it/public_html/wp-content/themes/game/temp-log.log',
                $dump
            );

            return $result;
        case 'instance':
            if (isset($conv['instation'])) {
                return trim($conv['instation']) === trim($conditions['value'] ?? '');
            }
            return false;
            return false;
    }
}


function get_conversation_element($conversation, $npc_id, $conditions)
{
    $user_id = get_current_user_id();
    $npc_relation_value = get_field('npc-relation-user-' . $user_id, $npc_id);
    $npc_relation_meet  = get_field('npc-meet-user-' . $user_id, $npc_id);

    if (!is_array($conversation)) {
        return null;
    }
    foreach ($conversation as $item) {
        // Jeśli nie ma warunków (pusty string lub brak klucza) – traktuj jako rozmowę startową
        if (!isset($item['layout_settings']['conversation_start']) || empty($item['layout_settings']['conversation_start'])) {
            return $item;
        }
        // Jeśli conversation_start jest tablicą, iteruj po warunkach
        if (is_array($item['layout_settings']['conversation_start'])) {
            foreach ($item['layout_settings']['conversation_start'] as $conv) {
                if (evaluate_condition($conv, $npc_relation_value, $npc_relation_meet, $conditions, $npc_id)) {
                    return $item;
                }
            }
        }
    }
    return null;
}

function get_filtered_answers($conversation_item, $npc_id, $conditions)
{
    $user_id = get_current_user_id();
    $npc_relation_value = get_field('npc-relation-user-' . $user_id, $npc_id);
    $npc_relation_meet  = get_field('npc-meet-user-' . $user_id, $npc_id);
    $filtered = [];

    if (isset($conversation_item['anwsers']) && is_array($conversation_item['anwsers'])) {
        foreach ($conversation_item['anwsers'] as $answer) {
            $include = true;
            if (isset($answer['layout_settings']['conversation_start'])) {
                $start = $answer['layout_settings']['conversation_start'];
                if (is_array($start)) {
                    $include = false;
                    foreach ($start as $ans_conv) {
                        if (evaluate_condition($ans_conv, $npc_relation_value, $npc_relation_meet, $conditions, $npc_id)) {
                            $include = true;
                            break;
                        }
                    }
                } elseif (!empty($start)) {
                    $include = true;
                }
            }
            if ($include) {
                $filtered[] = clean_array($answer);
            }
        }
    }
    return $filtered;
}

function get_dialogue(array $npc_data, $id_conversation = null, $conditions = [])
{
    $npc_id = $npc_data['npc_id'] ?? 0;
    $post = get_post($npc_id);
    if (!$post) {
        return [];
    }
    $npc_thumbnail_id = get_post_thumbnail_id($npc_id);
    $thumbnail_url = wp_get_attachment_image_url($npc_thumbnail_id, 'full');
    $conversation = get_field('anwser', $npc_id) ?: [];
    $conversation_item = null;
    if (!$id_conversation) {
        if (empty($conditions)) {
            $conditions = [
                'scena'     => '',
                'mission'   => '',
                'instation' => ''
            ];
        }
        $conversation_item = get_conversation_element($conversation, $npc_id, $conditions);
    } else {
        foreach ($conversation as $item) {
            if (isset($item['id_pola']) && $item['id_pola'] === $id_conversation) {
                $conversation_item = $item;
                break;
            }
        }
    }
    if (!$conversation_item) {
        foreach ($conversation as $item) {
            if (isset($item['id_pola']) && $item['id_pola'] === 'default') {
                $conversation_item = $item;
                break;
            }
        }
    }
    if (!$conversation_item) {
        return [];
    }

    $filtered_answers = get_filtered_answers($conversation_item, $npc_id, $conditions);
    $result = [
        'question'    => $conversation_item['question'] ?? '',
        'answers'     => array_values($filtered_answers),
        'start_index' => $conversation_item['id_pola'] ?? ''
    ];

    return [
        'npc_id'        => $npc_id,
        'npc_name'      => $post->post_name,
        'npc_thumbnail' => $thumbnail_url,
        'conversation'  => $result
    ];
}

add_action('wp_ajax_get_npc_popup', 'ajax_get_npc_popup');
function ajax_get_npc_popup()
{
    // Pobierz dane z żądania POST
    $npc_id = isset($_POST['npc_id']) ? intval($_POST['npc_id']) : 0;
    $current_url = isset($_POST['current_url']) ? esc_url_raw($_POST['current_url']) : '';
    $conditions = isset($_POST['page_id']) ? json_decode(stripslashes($_POST['page_id']), true) : [];

    $id_conversation = isset($_POST['id_conversation']) ? sanitize_text_field($_POST['id_conversation']) : null;

    if (!$npc_id) {
        wp_send_json_error('Brak ID NPC');
    }

    $npc_data = [
        'npc_id'              => $npc_id,
        'popup_id'            => 'npc-popup',
        'active'              => false,
        'current_url'         => $current_url,
        'current_state_array' => $conditions
    ];

    $dialogue = get_dialogue($npc_data, $id_conversation, $conditions);
    if (empty($dialogue)) {
        wp_send_json_error('Nie znaleziono dialogu dla NPC');
    }
    $dialogue['npc_post_title'] = get_the_title($npc_id);
    wp_send_json_success(['npc_data' => $dialogue]);
}

function ajax_get_dialogue()
{
    $npc_data = isset($_POST['npc_data']) ? json_decode(stripslashes($_POST['npc_data']), true) : [];
    $id_conversation = $_POST['id_conversation'] ?? null;
    $conditions = isset($_POST['conditions']) ? json_decode(stripslashes($_POST['conditions']), true) : [];
    $result = get_dialogue($npc_data, $id_conversation, $conditions);
    wp_send_json_success($result);
}
add_action('wp_ajax_get_dialogue', 'ajax_get_dialogue');
