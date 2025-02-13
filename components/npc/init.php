<?php
function get_npc($npc_id, $id_popup = 'npc-popup', $active = false)
{
    $post = get_post($npc_id);
    if (!$post) return;
    $npc_id = $post->ID;
    $npc_thumbnail = get_post_thumbnail_id($npc_id);
    $conversation = get_field('conversation', $npc_id);
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
