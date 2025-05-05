<?php

/**
 * ACF Component: Unlock Area Action
 * 
 * Komponent reprezentujący akcję odblokowania rejonu w odpowiedzi dialogowej.
 */

return array(
    'key' => 'layout_unlock_area',
    'name' => 'unlock_area',
    'label' => 'Odblokuj Rejon',
    'display' => 'block',
    'sub_fields' => array(
        array(
            'key' => 'field_area_select',
            'label' => 'Wybierz rejon do odblokowania',
            'name' => 'area',
            'aria-label' => '',
            'type' => 'post_object',
            'instructions' => 'Wybierz rejon, który zostanie odblokowany dla użytkownika',
            'required' => 1,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '100',
                'class' => '',
                'id' => '',
            ),
            'post_type' => array(
                0 => 'tereny',
            ),
            'post_status' => '',
            'taxonomy' => '',
            'return_format' => 'id',
            'multiple' => 0,
            'allow_null' => 0,
            'allow_in_bindings' => 0,
            'ui' => 1,
            'bidirectional' => 0,
            'bidirectional_target' => array(),
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
