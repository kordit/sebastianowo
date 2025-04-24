<?php

/**
 * Rejestracja pól dla misji użytkownika
 */
add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // Grupa pól do zarządzania misjami użytkownika
    acf_add_local_field_group(array(
        'key' => 'group_user_missions',
        'title' => 'Misje Użytkownika',
        'fields' => array(
            // Status misji (aktywne/ukończone/nieudane)
            array(
                'key' => 'field_user_missions',
                'label' => 'Status misji',
                'name' => 'user_missions',
                'type' => 'group',
                'instructions' => 'Status misji użytkownika',
                'required' => 0,
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_active_missions',
                        'label' => 'Aktywne misje',
                        'name' => 'active_missions',
                        'type' => 'relationship',
                        'instructions' => 'Misje aktualnie aktywne dla gracza',
                        'required' => 0,
                        'post_type' => array(
                            0 => 'mission',
                        ),
                        'filters' => array(
                            0 => 'search',
                        ),
                        'return_format' => 'id',
                        'wrapper' => array(
                            'width' => '100',
                            'class' => '',
                            'id' => '',
                        ),
                    ),
                    array(
                        'key' => 'field_completed_missions',
                        'label' => 'Ukończone misje',
                        'name' => 'completed',
                        'type' => 'relationship',
                        'instructions' => 'Misje ukończone przez gracza',
                        'required' => 0,
                        'post_type' => array(
                            0 => 'mission',
                        ),
                        'filters' => array(
                            0 => 'search',
                        ),
                        'return_format' => 'id',
                        'wrapper' => array(
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ),
                    ),
                    array(
                        'key' => 'field_failed_missions',
                        'label' => 'Nieudane misje',
                        'name' => 'failed',
                        'type' => 'relationship',
                        'instructions' => 'Misje, których gracz nie ukończył',
                        'required' => 0,
                        'post_type' => array(
                            0 => 'mission',
                        ),
                        'filters' => array(
                            0 => 'search',
                        ),
                        'return_format' => 'id',
                        'wrapper' => array(
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ),
                    ),
                ),
            ),

            // Postęp w zadaniach misji
            array(
                'key' => 'field_mission_tasks_progress',
                'label' => 'Postęp w zadaniach',
                'name' => 'mission_tasks_progress',
                'type' => 'repeater',
                'instructions' => 'Status ukończenia zadań w misjach',
                'required' => 0,
                'layout' => 'block',
                'button_label' => 'Dodaj postęp zadania',
                'sub_fields' => array(
                    array(
                        'key' => 'field_task_mission_id',
                        'label' => 'ID Misji',
                        'name' => 'mission_id',
                        'type' => 'post_object',
                        'instructions' => '',
                        'required' => 1,
                        'post_type' => array('mission'),
                        'return_format' => 'id',
                        'wrapper' => array(
                            'width' => '25',
                            'class' => '',
                            'id' => '',
                        ),
                    ),

                    array(
                        'key' => 'field_task_key',
                        'label' => 'ID Zadania',
                        'name' => 'task_id',
                        'type' => 'text',
                        'instructions' => 'np. task_0, task_1, itp.',
                        'required' => 1,
                        'wrapper' => array(
                            'width' => '25',
                            'class' => '',
                            'id' => '',
                        ),
                    ),
                    array(
                        'key' => 'field_task_status',
                        'label' => 'Status zadania',
                        'name' => 'status',
                        'type' => 'select',
                        'instructions' => 'Status zadania: niezaczęta, rozpoczęta, ukończona',
                        'required' => 1,
                        'choices' => array(
                            'not_started' => 'Niezaczęta',
                            'in_progress' => 'Rozpoczęta',
                            'completed' => 'Ukończona',
                        ),
                        'default_value' => array('not_started'),
                        'allow_null' => 0,
                        'multiple' => 0,
                        'ui' => 1,
                        'ajax' => 0,
                        'return_format' => 'value',
                        'placeholder' => '',
                        'wrapper' => array(
                            'width' => '30',
                            'class' => '',
                            'id' => '',
                        ),
                    ),

                    array(
                        'key' => 'field_task_completion_date',
                        'label' => 'Data ukończenia',
                        'name' => 'completion_date',
                        'type' => 'date_time_picker',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_task_status',
                                    'operator' => '==',
                                    'value' => 'completed',
                                ),
                            ),
                        ),
                        'display_format' => 'd/m/Y g:i a',
                        'return_format' => 'Y-m-d H:i:s',
                        'wrapper' => array(
                            'width' => '15',
                            'class' => '',
                            'id' => '',
                        ),
                    ),
                    array(
                        'key' => 'field_task_type',
                        'label' => 'Typ zadania',
                        'name' => 'task_type',
                        'type' => 'text',
                        'instructions' => 'np. dialog, item, sell, place, custom',
                        'required' => 0,
                        'wrapper' => array(
                            'width' => '40',
                            'class' => '',
                            'id' => '',
                        ),
                    ),
                    array(
                        'key' => 'field_task_details',
                        'label' => 'Szczegóły zadania',
                        'name' => 'task_details',
                        'type' => 'repeater',
                        'instructions' => 'Szczegółowy stan zadania',
                        'required' => 0,
                        'layout' => 'table',
                        'button_label' => 'Dodaj szczegół',
                        'wrapper' => array(
                            'width' => '100',
                            'class' => '',
                            'id' => '',
                        ),
                        'sub_fields' => array(
                            array(
                                'key' => 'field_detail_key',
                                'label' => 'Klucz',
                                'name' => 'key',
                                'type' => 'text',
                                'instructions' => 'np. npc_id, item_count, etc.',
                                'required' => 1,
                                'wrapper' => array(
                                    'width' => '30',
                                    'class' => '',
                                    'id' => '',
                                ),
                            ),
                            array(
                                'key' => 'field_detail_value',
                                'label' => 'Wartość',
                                'name' => 'value',
                                'type' => 'text',
                                'instructions' => 'Stan (wartość) danego szczegółu',
                                'required' => 1,
                                'wrapper' => array(
                                    'width' => '70',
                                    'class' => '',
                                    'id' => '',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'user_role',
                    'operator' => '==',
                    'value' => 'all',
                ),
            ),
        ),
        'menu_order' => 1,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ));
});
