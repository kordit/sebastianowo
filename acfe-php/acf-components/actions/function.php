<?php

/**
 * ACF Component: Function Action
 * 
 * Komponent reprezentujący akcję wywołania funkcji w odpowiedzi dialogowej.
 */

return array(
    'key' => 'layout_67b4ee4bd07bd',
    'name' => 'function',
    'label' => 'Funkcja',
    'display' => 'block',
    'sub_fields' => array(
        array(
            'key' => 'field_67b4ef5f6a17f',
            'label' => 'Funkcja',
            'name' => 'do_function',
            'aria-label' => '',
            'type' => 'select',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'choices' => array(
                'SetClass' => 'Wybierz klasę',
                'go-to-page' => 'Przejdź do podstrony',
                'start-fight' => 'Zacznij bójkę',
            ),
            'default_value' => false,
            'return_format' => 'value',
            'multiple' => 0,
            'allow_null' => 0,
            'allow_in_bindings' => 1,
            'ui' => 0,
            'ajax' => 0,
            'placeholder' => '',
            'allow_custom' => 0,
            'search_placeholder' => '',
        ),
        array(
            'key' => 'field_68013a77aaa4b',
            'label' => 'Url po slash',
            'name' => 'page_url',
            'aria-label' => '',
            'type' => 'text',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_67b4ef5f6a17f',
                        'operator' => '==',
                        'value' => 'go-to-page',
                    ),
                ),
            ),
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'default_value' => '',
            'maxlength' => '',
            'allow_in_bindings' => 0,
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
        ),
        array(
            'key' => 'field_68013a77aaa4c',
            'label' => 'ID po przegranej',
            'name' => 'lose_id',
            'aria-label' => '',
            'type' => 'acfe_slug',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_67b4ef5f6a17f',
                        'operator' => '==',
                        'value' => 'start-fight',
                    ),
                ),
            ),
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'default_value' => '',
            'maxlength' => '',
            'allow_in_bindings' => 0,
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
        ),
        array(
            'key' => 'field_68013a77aaa4e',
            'label' => 'Wybierz klasę użytkownika',
            'name' => 'user_class',
            'aria-label' => '',
            'type' => 'select',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_67b4ef5f6a17f',
                        'operator' => '==',
                        'value' => 'SetClass',
                    ),
                ),
            ),
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'choices' => array(
                'zadymiarz' => 'Zadymiarz',
                'zawijacz' => 'Zawijacz',
                'kombinator' => 'Kombinator',
            ),
            'default_value' => 'zadymiarz',
            'return_format' => 'value',
            'multiple' => 0,
            'allow_null' => 0,
            'ui' => 0,
            'ajax' => 0,
            'placeholder' => '',
            'allow_custom' => 0,
            'search_placeholder' => '',
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
