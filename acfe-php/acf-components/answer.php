<?php

/**
 * ACF Component: Answer
 * 
 * Komponent reprezentujący strukturę odpowiedzi w dialogu.
 * 
 * @package Game
 * @since 1.0.0
 */

// Wymagany plik z ustawieniami widoczności
require_once(dirname(__FILE__) . '/visibility_settings.php');

return array(
    'key' => 'layout_67b4e78c15feb',
    'name' => 'anwser',
    'label' => 'Odpowiedź',
    'display' => 'block',
    'sub_fields' => array(
        array(
            'key' => 'field_67b4e7a2ec416',
            'label' => 'Treść odpowiedzi',
            'name' => 'anwser_text',
            'aria-label' => '',
            'type' => 'text',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '30',
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
            'key' => 'field_67b4ea1e2acbb',
            'label' => 'Typ odpowiedzi',
            'name' => 'type_anwser',
            'aria-label' => '',
            'type' => 'flexible_content',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '50',
                'class' => '',
                'id' => '',
            ),
            'acfe_flexible_advanced' => 1,
            'acfe_flexible_stylised_button' => 0,
            'acfe_flexible_hide_empty_message' => 1,
            'acfe_flexible_layouts_templates' => 1,
            'acfe_flexible_layouts_previews' => 0,
            'acfe_flexible_layouts_placeholder' => 0,
            'acfe_flexible_layouts_thumbnails' => 0,
            'acfe_flexible_layouts_settings' => 0,
            'acfe_flexible_async' => array(),
            'acfe_flexible_add_actions' => array(
                0 => 'toggle',
                1 => 'close',
            ),
            'acfe_flexible_remove_button' => array(),
            'acfe_flexible_modal_edit' => array(
                'acfe_flexible_modal_edit_enabled' => '1',
                'acfe_flexible_modal_edit_size' => 'xlarge',
            ),
            'acfe_flexible_modal' => array(
                'acfe_flexible_modal_enabled' => '0',
                'acfe_flexible_modal_title' => false,
                'acfe_flexible_modal_size' => 'full',
                'acfe_flexible_modal_col' => '4',
                'acfe_flexible_modal_categories' => false,
            ),
            'layouts' => array(
                'layout_67b4ea25717d1' => require dirname(__FILE__) . '/actions/transaction.php',
                'layout_67b4f3848eb2d' => require dirname(__FILE__) . '/actions/skills.php',
                'layout_67b4edfad07bb' => require dirname(__FILE__) . '/actions/relation.php',
                'layout_67b4ee4bd07bd' => require dirname(__FILE__) . '/actions/function.php',
                'layout_experience_reputation' => require dirname(__FILE__) . '/actions/exp_rep.php',
                'layout_67b4f17f4d5db' => require dirname(__FILE__) . '/actions/mission.php',
                'layout_item_management' => require dirname(__FILE__) . '/actions/item.php',
                'layout_unlock_area' => require dirname(__FILE__) . '/actions/unlock_area.php',
                'layout_change_area' => require dirname(__FILE__) . '/actions/change_area.php',
            ),
            'min' => '',
            'max' => '',
            'button_label' => 'Dodaj wiersz',
            'acfe_flexible_empty_message' => '',
            'acfe_flexible_layouts_state' => false,
        ),
        array(
            'key' => 'field_67b4e9d72acba',
            'label' => 'Po kliknięciu przejdź do ID',
            'name' => 'go_to_id',
            'aria-label' => '',
            'type' => 'acfe_slug',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '20',
                'class' => '',
                'id' => '',
            ),
            'default_value' => '0',
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'maxlength' => '',
            'allow_in_bindings' => 0,
        ),
    ),
    'min' => '',
    'max' => '',
    'acfe_flexible_render_template' => '',
    'acfe_flexible_render_style' => '',
    'acfe_flexible_render_script' => '',
    'acfe_flexible_settings' => game_get_visibility_settings_for_flexible(),
    'acfe_flexible_settings_size' => 'xlarge',
    'acfe_flexible_thumbnail' => false,
    'acfe_flexible_modal_edit_size' => false,
    'acfe_flexible_category' => false,
);
