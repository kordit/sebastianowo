<?php

/**
 * ACF Component: Experience/Reputation Action
 * 
 * Komponent reprezentujący akcję zmiany doświadczenia lub reputacji w odpowiedzi dialogowej.
 */

return array(
    'key' => 'layout_experience_reputation',
    'name' => 'exp_rep',
    'label' => 'Doświadczenie/Reputacja',
    'display' => 'block',
    'sub_fields' => array(
        array(
            'key' => 'field_exp_rep_type',
            'label' => 'Typ',
            'name' => 'type',
            'aria-label' => '',
            'type' => 'select',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '50',
                'class' => '',
                'id' => '',
            ),
            'choices' => array(
                'exp' => 'Doświadczenie',
                'reputation' => 'Reputacja',
            ),
            'default_value' => 'exp',
            'return_format' => 'value',
            'multiple' => 0,
            'allow_null' => 0,
            'allow_in_bindings' => 0,
            'ui' => 0,
            'ajax' => 0,
            'placeholder' => '',
            'allow_custom' => 0,
            'search_placeholder' => '',
        ),
        array(
            'key' => 'field_exp_rep_value',
            'label' => 'Wartość',
            'name' => 'value',
            'aria-label' => '',
            'type' => 'number',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '50',
                'class' => '',
                'id' => '',
            ),
            'default_value' => '',
            'min' => '',
            'max' => '',
            'allow_in_bindings' => 0,
            'placeholder' => '',
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
