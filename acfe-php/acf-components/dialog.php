<?php

/**
 * ACF Component: Dialog
 * 
 * Komponent reprezentujący strukturę dialogu w grze.
 * 
 * @package Game
 * @since 1.0.0
 */

// Wymagany plik z ustawieniami widoczności
require_once(dirname(__FILE__) . '/visibility_settings.php');

if (!function_exists('get_acf_dialog_component')) {
    /**
     * Zwraca konfigurację komponentu dialogu
     * 
     * @return array Konfiguracja pola ACF
     */
    function get_acf_dialog_component(): array
    {
        return array(
            'key' => 'layout_67b4dae46e98c',
            'name' => 'question',
            'label' => 'Dialog',
            'display' => 'block',
            'sub_fields' => array(
                array(
                    'key' => 'field_67b4ddcb75ddc',
                    'label' => 'ID pola',
                    'name' => 'id_pola',
                    'aria-label' => '',
                    'type' => 'acfe_slug',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                    'maxlength' => '',
                    'allow_in_bindings' => 0,
                ),
                array(
                    'key' => 'field_67b4dcff497d5',
                    'label' => 'Pytanie',
                    'name' => 'question',
                    'aria-label' => '',
                    'type' => 'wysiwyg',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'allow_in_bindings' => 0,
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 0,
                    'delay' => 0,
                ),
                array(
                    'key' => 'field_67b4e72eec415',
                    'label' => 'Odpowiedzi',
                    'name' => 'anwsers',
                    'aria-label' => '',
                    'type' => 'flexible_content',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'acfe_flexible_advanced' => 1,
                    'acfe_flexible_stylised_button' => 0,
                    'acfe_flexible_hide_empty_message' => 0,
                    'acfe_flexible_empty_message' => '',
                    'acfe_flexible_layouts_templates' => 1,
                    'acfe_flexible_layouts_previews' => 0,
                    'acfe_flexible_layouts_placeholder' => 0,
                    'acfe_flexible_layouts_thumbnails' => 0,
                    'acfe_flexible_layouts_settings' => 1,
                    'acfe_flexible_async' => array(),
                    'acfe_flexible_add_actions' => array(),
                    'acfe_flexible_remove_button' => array(),
                    'acfe_flexible_layouts_state' => 'user',
                    'acfe_flexible_modal_edit' => array(
                        'acfe_flexible_modal_edit_enabled' => '0',
                        'acfe_flexible_modal_edit_size' => 'large',
                    ),
                    'acfe_flexible_modal' => array(
                        'acfe_flexible_modal_enabled' => '0',
                        'acfe_flexible_modal_title' => false,
                        'acfe_flexible_modal_size' => 'full',
                        'acfe_flexible_modal_col' => '4',
                        'acfe_flexible_modal_categories' => false,
                    ),
                    'layouts' => array(
                        'layout_67b4e78c15feb' => require dirname(__FILE__) . '/answer.php',
                    ),
                    'min' => '',
                    'max' => '',
                    'button_label' => 'Dodaj odpowiedź',
                ),
            ),
            'min' => '',
            'max' => '',
            'acfe_flexible_render_template' => '',
            'acfe_flexible_render_style' => '',
            'acfe_flexible_render_script' => '',
            'acfe_flexible_settings' => game_get_visibility_settings_for_flexible(),
            'acfe_flexible_settings_size' => 'xlarge',
            'acfe_flexible_thumbnail' => '',
            'acfe_flexible_modal_edit_size' => false,
            'acfe_flexible_category' => false,
        );
    }
}
