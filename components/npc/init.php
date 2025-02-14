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

function get_npc($npc_id, $id_popup = 'npc-popup', $active = false)
{
    $post = get_post($npc_id);
    if (!$post) return;
    $npc_id = $post->ID;
    $npc_thumbnail = get_post_thumbnail_id($npc_id);
    $conversation = get_field('conversation', $npc_id);
    // $conversation = clean_conversation($conversation);

    $current_user_id = get_current_user_id();
    $active_class = $active ? 'active' : '';
    $conversation_json = json_encode($conversation, JSON_UNESCAPED_UNICODE);
?>
    <div class="controler-popup <?= esc_attr($active_class); ?>" id="<?= esc_attr($id_popup); ?>" data-npc-id="<?= esc_attr($npc_id); ?>" data-conversation='<?= esc_attr($conversation_json); ?>'>
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
    if (!$npc_id) {
        wp_send_json_error('Brak ID NPC');
    }
    ob_start();
    get_npc($npc_id, 'npc-popup', false);
    $html = ob_get_clean();
    wp_send_json_success($html);
}
