<?php

/**
 * ACF Component: Relation Action
 * 
 * Komponent reprezentujący akcję zmiany relacji w odpowiedzi dialogowej.
 */

return array(
    'key' => 'layout_67b4edfad07bb',
    'name' => 'relation',
    'label' => 'Relacja',
    'display' => 'block',
    'sub_fields' => array(
        array(
            'key' => 'field_67b4f19d4d5dd',
            'label' => 'NPC',
            'name' => 'npc',
            'aria-label' => '',
            'type' => 'post_object',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '50',
                'class' => '',
                'id' => '',
            ),
            'post_type' => array(
                0 => 'npc',
            ),
            'post_status' => '',
            'taxonomy' => '',
            'return_format' => 'id',
            'multiple' => 0,
            'save_custom' => 0,
            'save_post_status' => 'publish',
            'acfe_bidirectional' => array(
                'acfe_bidirectional_enabled' => '0',
            ),
            'allow_null' => 0,
            'allow_in_bindings' => 0,
            'bidirectional' => 0,
            'ui' => 1,
            'bidirectional_target' => array(),
            'save_post_type' => '',
        ),
        array(
            'key' => 'field_67b4f1c44d5de',
            'label' => 'Zmień relację o',
            'name' => 'change_relation',
            'aria-label' => '',
            'type' => 'range',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '50',
                'class' => '',
                'id' => '',
            ),
            'default_value' => 0,
            'min' => -100,
            'max' => 100,
            'allow_in_bindings' => 0,
            'step' => '',
            'prepend' => '',
            'append' => '',
        ),
    ),
    'min' => '',
    'max' => '',
    'acfe_flexible_render_template' => '',
    'acfe_flexible_render_style' => '',
    'acfe_flexible_render_script' => '',
    'acfe_flexible_modal_edit_size' => '',
    'acfe_flexible_thumbnail' => false,
    'acfe_flexible_settings' => false,
    'acfe_flexible_settings_size' => 'medium',
    'acfe_flexible_category' => false,
);
