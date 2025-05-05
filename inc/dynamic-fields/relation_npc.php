<?php
if (function_exists('acf_add_local_field_group')) {
    $fields_relacja = array();

    $npcs = get_posts(array(
        'post_type' => 'npc',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));

    foreach ($npcs as $npc) {
        $fields_relacja[] = array(
            'key' => 'npc-relation-' . $npc->ID,
            'label' => 'Relacja z NPC: ' . $npc->post_title,
            'name' => 'npc-relation-' . $npc->ID,
            'type' => 'range',
            'min' => -100,
            'max' => 100,
            'default_value' => 0,
            'wrapper' => array(
                'width' => '50',
            ),
        );

        $fields_relacja[] = array(
            'key' => 'npc-meet-' . $npc->ID,
            'label' => 'Poznanie NPC: ' . $npc->post_title,
            'name' => 'npc-meet-' . $npc->ID,
            'type' => 'true_false',
            'message' => 'Czy gracz poznał tego NPCa?',
            'ui' => 1,
            'default_value' => 0,
            'wrapper' => array(
                'width' => '50',
            ),
        );
    }

    acf_add_local_field_group(array(
        'key' => 'group_npc_relacje_uzytkownik',
        'title' => 'Relacje z NPC',
        'fields' => $fields_relacja,
        'location' => array(
            array(
                array(
                    'param' => 'user_form',
                    'operator' => '==',
                    'value' => 'all',
                ),
            ),
        ),
        //order 
        'menu_order' => 90,
        'hide_on_screen' => array(),
        'active' => true,
        'show_in_rest' => 0,
    ));
}
