<?php
function filter_conversation_by_conditions($conversation, $npc_dat_state)
{
    $filtered_conversation = [];
    foreach ($conversation as $entry) {
        $filtered_answers = [];
        if (!isset($entry['answers']) || !is_array($entry['answers'])) {
            continue;
        }
        foreach ($entry['answers'] as $answer) {
            if ($answer['answer_visibility'] === 'always') {
                $filtered_answers[] = $answer;
                continue;
            }
            if ($answer['answer_visibility'] === 'conditional' && !empty($answer['answer_conditions'])) {
                foreach ($answer['answer_conditions'] as $condition) {
                    if (
                        isset($condition['if'], $npc_dat_state['TypePage']) &&
                        $condition['if'] === $npc_dat_state['TypePage'] &&
                        isset($condition[$npc_dat_state['TypePage']]) &&
                        $condition[$npc_dat_state['TypePage']] === $npc_dat_state['value']
                    ) {
                        $filtered_answers[] = $answer;
                        break;
                    }
                    if (isset($condition['if']) && $condition['if'] === 'relation') {
                        $relation_data = $condition['relation_select'] ?? [];
                        if (!empty($relation_data)) {
                            $relNPC = $relation_data['relacja'] ?? null;
                            $operator = $relation_data['operator'] ?? null;
                            $value = $relation_data['value'] ?? null;
                            if ($relNPC && $operator) {
                                $user_id = get_current_user_id();
                                $relation_field_key = "npc-relation-user-{$user_id}";
                                $relation_meet_field_key = "npc-meet-user-{$user_id}";
                                $relation_meet_value = get_field($relation_meet_field_key, $relNPC);
                                $npc_relation_value = get_field($relation_field_key, $relNPC);
                                if ($npc_relation_value !== null || $relation_meet_value !== null) {
                                    $comparison_passed = false;
                                    switch ($operator) {
                                        case '=':
                                            $comparison_passed = ($npc_relation_value == $value);
                                            break;
                                        case '>':
                                            $comparison_passed = ($npc_relation_value > $value);
                                            break;
                                        case '>=':
                                            $comparison_passed = ($npc_relation_value >= $value);
                                            break;
                                        case '<':
                                            $comparison_passed = ($npc_relation_value < $value);
                                            break;
                                        case '<=':
                                            $comparison_passed = ($npc_relation_value <= $value);
                                            break;
                                        case 'exist':
                                            $comparison_passed = ($relation_meet_value == $value);
                                            break;
                                    }
                                    if ($comparison_passed) {
                                        $filtered_answers[] = $answer;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!empty($filtered_answers)) {
            $filtered_entry = $entry;
            $filtered_entry['answers'] = $filtered_answers;
            $filtered_conversation[] = $filtered_entry;
        }
    }
    return $filtered_conversation;
}

function get_npc_data_for_popup(array $npc_data)
{
    $npc_id = $npc_data['npc_id'] ?? 0;
    $post = get_post($npc_id);
    if (!$post) {
        return [];
    }
    $npc_thumbnail_id = get_post_thumbnail_id($npc_id);
    $thumbnail_url = wp_get_attachment_image_url($npc_thumbnail_id, 'full');
    $wrapper_chat = get_field('wrapper_chat', $npc_id) ?: [];
    $scena_szukana = null;
    if (!empty($npc_data['current_state_array'])) {
        $npc_dat_state = $npc_data['current_state_array'];
        $npc_dat_state_type_page = $npc_dat_state['TypePage'];
        $npc_dat_state_type_value = $npc_dat_state['value'];
        $conversation_start = get_field('conversation_start', $npc_id);
        if (is_array($conversation_start)) {
            foreach ($conversation_start as $conversation) {
                if (
                    isset($conversation['if'], $conversation['mission_select']) &&
                    $conversation['if'] === 'mission'
                ) {
                    $ms = $conversation['mission_select'];
                    if (
                        isset($ms['mission'], $ms['is_end']) &&
                        $ms['mission'] === $npc_dat_state_type_value
                    ) {
                        $scena_szukana = $conversation['when_start_conversation'] ?? null;
                        break;
                    }
                }
                if (
                    isset($conversation['if'], $conversation['scena']) &&
                    $conversation['if'] === 'scena' &&
                    $conversation['scena'] === $npc_dat_state_type_value
                ) {
                    $scena_szukana = $conversation['when_start_conversation'] ?? null;
                    break;
                }
                if (
                    isset($conversation['if'], $conversation['instation']) &&
                    $conversation['if'] === 'instance' &&
                    $conversation['instation'] === $npc_dat_state_type_value
                ) {
                    $scena_szukana = $conversation['when_start_conversation'] ?? null;
                    break;
                }
                if (
                    isset($conversation['if'], $conversation['relation_select']) &&
                    $conversation['if'] === 'relation'
                ) {
                    $relation_data = $conversation['relation_select'];
                    $relNPC = $relation_data['relacja'] ?? null;
                    $operator = $relation_data['operator'] ?? null;
                    $value = $relation_data['value'] ?? null;
                    if ($relNPC && $operator && $value !== null) {
                        $user_id = get_current_user_id();
                        $relation_field_key = "npc-relation-user-{$user_id}";
                        $relation_meet_field_key = "npc-meet-user-{$user_id}";
                        $relation_meet_value = get_field($relation_meet_field_key, $relNPC);
                        $npc_relation_value = get_field($relation_field_key, $relNPC);
                        if ($npc_relation_value !== null || $relation_meet_value !== null) {
                            $comparison_passed = false;
                            switch ($operator) {
                                case '=':
                                    $comparison_passed = ($npc_relation_value == $value);
                                    break;
                                case '>':
                                    $comparison_passed = ($npc_relation_value > $value);
                                    break;
                                case '>=':
                                    $comparison_passed = ($npc_relation_value >= $value);
                                    break;
                                case '<':
                                    $comparison_passed = ($npc_relation_value < $value);
                                    break;
                                case '<=':
                                    $comparison_passed = ($npc_relation_value <= $value);
                                    break;
                                case 'exist':
                                    $comparison_passed = ($relation_meet_value == $value);
                                    break;
                            }
                            if ($comparison_passed) {
                                $scena_szukana = $conversation['when_start_conversation'] ?? null;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
    if (!$scena_szukana && !empty($wrapper_chat)) {
        $scena_szukana = $wrapper_chat[0]['scena_dialogowa'] ?? null;
    }
    $wynik = array_filter($wrapper_chat, fn($item) => is_array($item) && ($item['scena_dialogowa'] ?? null) === $scena_szukana);
    $wynik = array_values($wynik);
    $conversation = !empty($wynik[0]['conversation'])
        ? $wynik[0]['conversation']
        : (!empty($wrapper_chat[0]['conversation']) ? $wrapper_chat[0]['conversation'] : []);
    if (!empty($npc_data['current_state_array'])) {
        $conversation = filter_conversation_by_conditions($conversation, $npc_data['current_state_array']);
    }
    $start_index = 0;
    if (!empty($conversation)) {
        foreach ($conversation as $key => $entry) {
            if (!empty($entry['answers'])) {
                $start_index = $key;
                break;
            }
        }
    }
    $user_id = get_current_user_id();
    update_field($relation_meet_field_key, 1, $npc_id);
    return [
        'npc_id'        => $npc_id,
        'npc_name'      => $post->post_name,
        'npc_thumbnail' => $thumbnail_url,
        'conversation'  => $conversation,
        'user_id'       => $user_id,
        'start_index'   => $start_index
    ];
}

add_action('wp_ajax_get_npc_popup', 'ajax_get_npc_popup');
add_action('wp_ajax_nopriv_get_npc_popup', 'ajax_get_npc_popup');
function ajax_get_npc_popup()
{
    $npc_id = intval($_POST['npc_id'] ?? 0);
    $current_url = isset($_POST['current_url']) ? esc_url_raw($_POST['current_url']) : '';
    $page_id = isset($_POST['page_id']) ? json_decode(stripslashes($_POST['page_id']), true) : [];
    if (!$npc_id) {
        wp_send_json_error('Brak ID NPC');
    }
    $npc_data = [
        'npc_id'             => $npc_id,
        'popup_id'           => 'npc-popup',
        'active'             => false,
        'current_url'        => $current_url,
        'current_state_array' => $page_id
    ];
    $npc_json_data = get_npc_data_for_popup($npc_data);
    if (empty($npc_json_data)) {
        wp_send_json_error('Nie znaleziono wybranego NPC');
    }
    if (!empty($npc_json_data['conversation'])) {
        $npc_json_data['conversation'] = filter_conversation_by_conditions(
            $npc_json_data['conversation'],
            $npc_data['current_state_array']
        );
    }
    wp_send_json_success([
        'npc_data' => $npc_json_data
    ]);
}
