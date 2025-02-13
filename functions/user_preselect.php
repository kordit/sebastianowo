<?php
// add_action('user_register', function ($user_id) {
//     update_field('avatar', 86, 'user_' . $user_id);
//     // update_field('village', 73, 'user_' . $user_id);
// });
add_action('acf/include_fields', function () {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_67aa20e8d3095',
        'title' => 'Świat gry',
        'fields' => array(
            array(
                'key' => 'field_67aa20e878c49',
                'label' => 'SVG',
                'name' => 'svg',
                'aria-label' => '',
                'type' => 'image',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'return_format' => 'url',
                'library' => 'all',
                'mime_types' => 'svg',
                'preview_size' => 'medium',
            ),
            array(
                'key' => 'npc_leader',
                'label' => 'Lider wpisu',
                'name' => 'npc_leaader',
                'type' => 'post_object',
                'return_format' => 'id',
                'post_type' => array('npc'),
                'allow_null' => 0,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '!=',
                    'value' => 'post',
                ),
                array(
                    'param' => 'post_type',
                    'operator' => '!=',
                    'value' => 'npc',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ));
});
