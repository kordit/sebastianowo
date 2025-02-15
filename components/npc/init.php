<?php
// function clean_conversation($conversation)
// {
//     foreach ($conversation as &$q) {
//         if (isset($q['answers']) && is_array($q['answers'])) {
//             foreach ($q['answers'] as &$a) {
//                 if (!isset($a['question_type']) || $a['question_type'] !== 'function') {
//                     unset($a['function']);
//                 }
//                 if (!isset($a['question_type']) || $a['question_type'] !== 'transaction') {
//                     unset($a['transaction']);
//                 } else {
//                     if (isset($a['transaction']) && is_array($a['transaction'])) {
//                         foreach ($a['transaction'] as &$t) {
//                             if (isset($t['transaction_type'])) {
//                                 if ($t['transaction_type'] === 'bag') {
//                                     unset($t['relation_target'], $t['target_npc'], $t['target_user'], $t['relation_change']);
//                                 } elseif ($t['transaction_type'] === 'relation') {
//                                     unset($t['bag'], $t['add_remove']);
//                                     if (isset($t['relation_target'])) {
//                                         if ($t['relation_target'] === 'npc') {
//                                             unset($t['target_user']);
//                                         } elseif ($t['relation_target'] === 'user') {
//                                             unset($t['target_npc']);
//                                         }
//                                     }
//                                 }
//                             }
//                         }
//                     }
//                 }
//             }
//         }
//     }
//     return $conversation;
// }


function filter_conversation_by_conditions($conversation, $npc_dat_state)
{
    $filtered_conversation = [];

    foreach ($conversation as $entry) {
        $filtered_answers = [];

        foreach ($entry['answers'] as $answer) {
            // Jeśli odpowiedź jest zawsze widoczna, nie trzeba jej filtrować
            if ($answer['answer_visibility'] === 'always') {
                $filtered_answers[] = $answer;
                continue;
            }

            // Jeśli odpowiedź ma warunki, sprawdź je
            if ($answer['answer_visibility'] === 'conditional' && !empty($answer['answer_conditions'])) {
                foreach ($answer['answer_conditions'] as $condition) {
                    if (
                        isset($condition['if'], $condition[$npc_dat_state['TypePage']]) &&
                        $condition['if'] === $npc_dat_state['TypePage'] &&
                        $condition[$npc_dat_state['TypePage']] === $npc_dat_state['value']
                    ) {
                        $filtered_answers[] = $answer;
                        break; // Jeśli warunek jest spełniony, nie sprawdzaj dalszych
                    }

                    // Sprawdzanie relacji
                    if (
                        isset($condition['if'], $condition['relation_select']) &&
                        $condition['if'] === 'relation'
                    ) {
                        $relation_data = $condition['relation_select'];
                        $npc_id = $relation_data['relacja'] ?? null;
                        $operator = $relation_data['operator'] ?? null;
                        $value = $relation_data['value'] ?? null;

                        if ($npc_id && $operator && $value !== null) {
                            $user_id = get_current_user_id();
                            $relation_field_key = "npc-relation-user-{$user_id}";
                            $npc_relation_value = get_field($relation_field_key, $npc_id);

                            if ($npc_relation_value !== null) {
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

        // Jeśli przynajmniej jedna odpowiedź przeszła filtr, dodaj pytanie do rozmowy
        if (!empty($filtered_answers)) {
            $filtered_entry = $entry;
            $filtered_entry['answers'] = $filtered_answers;
            $filtered_conversation[] = $filtered_entry;
        }
    }

    return $filtered_conversation;
}

function get_npc(array $npc_data)
{
    $npc_id = $npc_data['npc_id'] ?? 0;
    $id_popup = $npc_data['popup_id'] ?? 'npc-popup';
    $active = $npc_data['active'] ?? false;
    $post = get_post($npc_id);
    if (!$post) return;
    $npc_thumbnail = get_post_thumbnail_id($npc_id);
    $wrapper_chat = get_field('wrapper_chat', $npc_id) ?: [];

    // Pobranie wartości repeatera ACF i ustawienie `scena_szukana`
    $scena_szukana = null;

    if (!empty($npc_data['current_state_array'])) {
        $npc_dat_state = $npc_data['current_state_array'];
        $npc_dat_state_type_page = $npc_dat_state['TypePage'];
        $npc_dat_state_type_value = $npc_dat_state['value'];

        $conversation_start = get_field('conversation_start', $npc_id);

        if (is_array($conversation_start)) {
            foreach ($conversation_start as $conversation) {
                // Sprawdzenie standardowych warunków (mission, scena, instance)
                if (
                    isset($conversation['if'], $conversation[$npc_dat_state_type_page]) &&
                    $conversation['if'] === $npc_dat_state_type_page &&
                    $conversation[$npc_dat_state_type_page] === $npc_dat_state_type_value
                ) {
                    $scena_szukana = $conversation['when_start_conversation'] ?? null;
                    break;
                }

                // Sprawdzenie warunku instance
                if (
                    isset($conversation['if'], $conversation['instation']) &&
                    $conversation['if'] === 'instance' &&
                    $conversation['instation'] === $npc_dat_state_type_value
                ) {
                    $scena_szukana = $conversation['when_start_conversation'] ?? null;
                    break;
                }

                // Sprawdzenie warunku relation_select
                if (
                    isset($conversation['if'], $conversation['relation_select']) &&
                    $conversation['if'] === 'relation'
                ) {
                    $relation_data = $conversation['relation_select'];
                    $npc_id = $relation_data['relacja'] ?? null;
                    $operator = $relation_data['operator'] ?? null;
                    $value = $relation_data['value'] ?? null;

                    if ($npc_id && $operator && $value !== null) {
                        $user_id = get_current_user_id();
                        $relation_field_key = "npc-relation-user-{$user_id}";
                        $npc_relation_value = get_field($relation_field_key, $npc_id);

                        if ($npc_relation_value !== null) {
                            // Porównaj wartość zgodnie z operatorem
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

    // Jeśli `scena_szukana` nie została ustawiona, weź pierwszą dostępną scenę
    if (!$scena_szukana && !empty($wrapper_chat)) {
        $scena_szukana = $wrapper_chat[0]['scena_dialogowa'] ?? null;
    }

    // Filtruj wrapper_chat, aby znaleźć pasującą scenę
    $wynik = array_filter($wrapper_chat, fn($item) => is_array($item) && ($item['scena_dialogowa'] ?? null) === $scena_szukana);
    $wynik = array_values($wynik);

    // Pobranie konwersacji – jeśli brak dopasowania, weź pierwszą dostępną
    $conversation = !empty($wynik[0]['conversation'])
        ? $wynik[0]['conversation']
        : (!empty($wrapper_chat[0]['conversation']) ? $wrapper_chat[0]['conversation'] : []);

    $conversation = filter_conversation_by_conditions($conversation, $npc_dat_state);
    // et_r($conversation);

    $current_user_id = get_current_user_id();
    $active_class = $active ? 'active' : '';
    $conversation_json = json_encode($conversation, JSON_UNESCAPED_UNICODE);

?>
    <div data-id-start-conversation="" class="controler-popup <?= esc_attr($active_class); ?>" id="<?= esc_attr($id_popup); ?>" data-npc-id="<?= esc_attr($npc_id); ?>" data-conversation='<?= esc_attr($conversation_json); ?>'>
        <div class="person">
            <div class="wrappers-chat">
                <div class="chat-me">
                    <div class="inner">
                        <h1><?= $post->post_name; ?> mówi:</h1>
                        <div data-current-user-id="<?= esc_attr($current_user_id); ?>" id="conversation"></div>
                    </div>
                </div>
            </div>
            <?= wp_get_attachment_image($npc_thumbnail, 'full'); ?>
        </div>
    </div>
<?php
}

add_action('wp_ajax_get_npc_popup', 'ajax_get_npc_popup');
add_action('wp_ajax_nopriv_get_npc_popup', 'ajax_get_npc_popup');

function ajax_get_npc_popup()
{
    $npc_id = intval($_POST['npc_id']);
    $current_url = isset($_POST['current_url']) ? esc_url_raw($_POST['current_url']) : '';
    $page_id = isset($_POST['page_id']) ? json_decode(stripslashes($_POST['page_id']), true) : [];

    if (!$npc_id) {
        wp_send_json_error('Brak ID NPC');
    }

    // ✅ Debugowanie, co faktycznie przychodzi z JavaScript
    error_log("=== Otrzymane page_id ===");
    error_log(print_r($page_id, true));

    $npc_data = [
        'npc_id'   => $npc_id,
        'popup_id' => 'npc-popup',
        'active'   => false,
        'current_url' => $current_url, // ✅ Pełny URL w razie potrzeby
        'current_state_array'  => $page_id // ✅ Zawiera teraz poprawnie tylko ostatni segment URL
    ];

    ob_start();
    get_npc($npc_data);
    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
        'npc_data' => $npc_data
    ]);
}
