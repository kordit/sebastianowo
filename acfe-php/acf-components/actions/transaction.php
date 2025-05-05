<?php

/**
 * ACF Component: Transaction Action
 * 
 * Komponent reprezentujący akcję transakcji w odpowiedzi dialogowej.
 */

return array(
    'key' => 'layout_67b4ea25717d1',
    'name' => 'transaction',
    'label' => 'Transakcja',
    'display' => 'block',
    'sub_fields' => array(
        array(
            'key' => 'field_67b4f2784d5e1',
            'label' => 'Waluta',
            'name' => 'backpack',
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
                'gold' => 'Hajs',
                'papierosy' => 'Szlugi',
            ),
            'default_value' => false,
            'return_format' => '',
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
            'key' => 'field_67b4f2ba4d5e2',
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
