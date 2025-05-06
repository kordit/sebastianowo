<?php

/**
 * ACF Component: Mission Action
 * 
 * Komponent reprezentujący akcję misji w odpowiedzi dialogowej.
 */

return array(
    'key' => 'layout_67b4f17f4d5db',
    'name' => 'mission',
    'label' => 'Misja',
    'display' => 'block',
    'sub_fields' => array(
        array(
            'key' => 'field_mission_id',
            'label' => 'Wybierz misję',
            'name' => 'mission_id',
            'aria-label' => '',
            'type' => 'post_object',
            'instructions' => 'Wybierz misję, która zostanie aktywowana przez NPC',
            'required' => 1,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '25',
                'class' => '',
                'id' => '',
            ),
            'post_type' => array(
                0 => 'mission',
            ),
            'taxonomy' => '',
            'allow_null' => 1,
            'multiple' => 0,
            'return_format' => 'id',
            'ui' => 1,
        ),
        array(
            'key' => 'field_mission_status',
            'label' => 'Status misji',
            'name' => 'mission_status',
            'aria-label' => '',
            'type' => 'select',
            'instructions' => 'Wybierz status misji',
            'required' => 0,
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_mission_id',
                        'operator' => '!=empty',
                    ),
                ),
            ),
            'wrapper' => array(
                'width' => '25',
                'class' => '',
                'id' => '',
            ),
            'choices' => array(
                'not_started' => 'Niezaczęte',
                'in_progress' => 'Rozpoczęte',
                'completed' => 'Ukończone',
                'failed' => 'Niepowodzenie',
            ),
            'default_value' => 'not_started',
            'allow_null' => 0,
            'multiple' => 0,
            'ui' => 1,
            'ajax' => 0,
            'return_format' => 'value',
            'placeholder' => '',
        ),
        array(
            'key' => 'field_mission_task_id',
            'label' => 'Wybierz zadanie z misji',
            'name' => 'mission_task_id',
            'aria-label' => '',
            'type' => 'text',
            'instructions' => 'Wybierz zadanie powiązane z wybraną misją',
            'required' => 0,
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_mission_id',
                        'operator' => '!=empty',
                    ),
                ),
            ),
            'wrapper' => array(
                'width' => '25',
                'class' => '',
                'id' => '',
            ),
        ),
        array(
            'key' => 'field_mission_task_status',
            'label' => 'Status zadania',
            'name' => 'mission_task_status',
            'aria-label' => '',
            'type' => 'select',
            'instructions' => 'Wybierz status zadania',
            'required' => 0,
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_mission_task_id',
                        'operator' => '!=empty',
                    ),
                ),
            ),
            'wrapper' => array(
                'width' => '25',
                'class' => '',
                'id' => '',
            ),
            'choices' => array(
                'not_started' => 'Niezaczęte',
                'in_progress' => 'Rozpoczęte',
                'completed' => 'Ukończone',
                'failed' => 'Niepowodzenie',
                'completed_npc' => 'Zalicz NPC',
                'failed_npc' => 'oblej NPC',
                'in_progress_npc' => 'w trakcie NPC',
                'not_started_npc' => 'Niezaczęte NPC',
            ),
            'default_value' => 'not_started',
            'allow_null' => 0,
            'multiple' => 0,
            'ui' => 0,
            'ajax' => 0,
            'return_format' => 'value',
            'placeholder' => '',
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
