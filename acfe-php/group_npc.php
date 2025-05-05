<?php

/**
 * Grupa pól dla typu zawartości "NPC"
 * 
 * Wykorzystuje reużywalne komponenty ACF z katalogu acf-components
 * 
 * @package Game
 * @since 1.0.0
 */

if (function_exists('acf_add_local_field_group')):

	// Załaduj komponenty
	require_once(dirname(__FILE__) . '/acf-components/dialog.php');
	require_once(dirname(__FILE__) . '/acf-components/visibility_settings.php');

	acf_add_local_field_group(array(
		'key' => 'group_npc',
		'title' => 'Dane NPC',
		'fields' => array(
			array(
				'key' => 'field_67b4d8085d3ab',
				'label' => 'Dialog',
				'name' => 'dialogs', // Poprawiona nazwa pola
				'aria-label' => '',
				'type' => 'flexible_content',
				'instructions' => 'Dodaj dialogi dla tego NPC',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'acfe_flexible_advanced' => 1,
				'acfe_flexible_stylised_button' => 1,
				'acfe_flexible_layouts_templates' => 1,
				'acfe_flexible_layouts_previews' => 0,
				'acfe_flexible_layouts_placeholder' => 0,
				'acfe_flexible_layouts_thumbnails' => 1,
				'acfe_flexible_layouts_settings' => 1,
				'acfe_flexible_async' => array(
					0 => 'title',
				),
				'acfe_flexible_add_actions' => array(
					0 => 'title',
					1 => 'toggle',
					2 => 'close',
				),
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
					'layout_67b4dae46e98c' => get_acf_dialog_component(),
				),
				'min' => '',
				'max' => '',
				'button_label' => 'Dodaj dialog',
				'acfe_flexible_hide_empty_message' => false,
				'acfe_flexible_empty_message' => '',
				'visibility_settings' => game_get_visibility_settings_component(), // Poprawiona nazwa funkcji
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'npc',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => array(
			0 => 'the_content',
		),
		'active' => true,
		'description' => '',
		'show_in_rest' => 0,
		'acfe_display_title' => 'Dialog',
		'acfe_autosync' => array(
			0 => 'php',
		),
		'acfe_form' => 0,
		'acfe_meta' => '',
		'acfe_note' => '',
		'modified' => time(),
	));

endif;
