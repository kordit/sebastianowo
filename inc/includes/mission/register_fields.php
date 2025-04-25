<?php
if (function_exists('acf_add_local_field_group')):

    acf_add_local_field_group(array(
        'key' => 'group_mission',
        'title' => 'Misje',
        'fields' => array(
            // Podstawowe informacje o misji
            array(
                'key' => 'field_mission_description',
                'label' => 'Opis misji',
                'name' => 'mission_description',
                'type' => 'textarea',
                'instructions' => 'Krótki opis misji',
                'required' => 1,
                'default_value' => '',
                'rows' => 5,
                'new_lines' => 'br',
            ),

            // Lista zadań - pole powtarzalne
            array(
                'key' => 'field_mission_tasks',
                'label' => 'Zadania',
                'name' => 'mission_tasks',
                'type' => 'repeater',
                'acfe_repeater_stylised_button' => 1,
                'instructions' => 'Lista zadań do wykonania w tej misji',
                'required' => 0,
                'layout' => 'block',
                'button_label' => 'Dodaj zadanie',
                'sub_fields' => array(

                    // Nazwa zadania
                    array(
                        'key' => 'field_task_title',
                        'label' => 'Nazwa zadania',
                        'name' => 'task_title',
                        'type' => 'text',
                        'instructions' => 'Krótka nazwa zadania',
                        'required' => 1,
                        'wrapper' => array(
                            'width' => '100',
                        ),
                    ),

                    // Opis zadania
                    array(
                        'key' => 'field_task_description',
                        'label' => 'Opis zadania',
                        'acfe_textarea_code' => 0,
                        'name' => 'task_description',
                        'type' => 'textarea',
                        'instructions' => 'Szczegółowy opis zadania',
                        'required' => 0,
                        'rows' => 3,
                    ),

                    // Typ zadania - select z trzema opcjami
                    array(
                        'key' => 'field_task_type',
                        'label' => 'Typ zadania',
                        'name' => 'task_type',
                        'type' => 'select',
                        'allow_custom' => 0,
                        'instructions' => 'Wybierz typ zadania',
                        'required' => 1,
                        'search_placeholder' => 'Wybierz typ zadania',
                        'choices' => array(
                            'place' => 'Odwiedź miejsce',
                            'checkpoint' => 'Zalicz checkpoint',
                            'checkpoint_npc' => 'Zalicz checkpoint z NPC',
                        ),
                        'default_value' => 'place',
                    ),

                    // Pole dla "Odwiedź miejsce" - wyświetlane warunkowo
                    array(
                        'key' => 'field_task_place_id',
                        'label' => 'ID miejsca',
                        'name' => 'task_place_id',
                        'type' => 'text',
                        'instructions' => 'Wprowadź identyfikator miejsca do odwiedzenia',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_task_type',
                                    'operator' => '==',
                                    'value' => 'place',
                                ),
                            ),
                        ),
                    ),

                    // Pole dla "Zalicz checkpoint" - wyświetlane warunkowo
                    array(
                        'key' => 'field_task_checkpoint',
                        'label' => 'ID checkpointu',
                        'name' => 'task_checkpoint',
                        'type' => 'acfe_slug',
                        'instructions' => 'Wprowadź identyfikator checkpointu do zaliczenia',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_task_type',
                                    'operator' => '==',
                                    'value' => 'checkpoint',
                                ),
                            ),
                        ),
                    ),

                    // Pole dla "Zalicz checkpoint z NPC" - wyświetlane warunkowo
                    array(
                        'key' => 'field_task_checkpoint_npc',
                        'label' => 'NPC do zadania',
                        'name' => 'task_checkpoint_npc',
                        'type' => 'repeater',
                        'acfe_repeater_stylised_button' => 0,
                        'instructions' => 'Lista NPC powiązana z tym zadaniem',
                        'required' => 0,
                        'layout' => 'table',
                        'button_label' => 'Dodaj NPC',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_task_type',
                                    'operator' => '==',
                                    'value' => 'checkpoint_npc',
                                ),
                            ),
                        ),
                        'sub_fields' => array(
                            array(
                                'key' => 'field_task_checkpoint_npc_select',
                                'label' => 'Wybierz NPC',
                                'name' => 'npc',
                                'type' => 'post_object',
                                'search_placeholder' => 'Wybierz NPC',
                                'save_custom' => 0,
                                'instructions' => 'Wybierz NPC z listy',
                                'required' => 1,
                                'post_type' => array('npc'),
                                'return_format' => 'id',
                                'wrapper' => array(
                                    'width' => '60',
                                ),
                            ),
                            array(
                                'key' => 'field_task_checkpoint_npc_status',
                                'label' => 'Status',
                                'name' => 'status',
                                'type' => 'select',
                                'search_placeholder' => 'Wybierz status',
                                'allow_custom' => 0,
                                'instructions' => 'Wybierz status dla tego NPC',
                                'required' => 1,
                                'choices' => array(
                                    'not_started' => 'Niezaczęte',
                                    'in_progress' => 'Rozpoczęte',
                                    'completed' => 'Ukończone',
                                    'failed' => 'Niepowodzenie',
                                ),
                                'default_value' => 'not_started',
                                'wrapper' => array(
                                    'width' => '40',
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
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'mission',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ));

endif;
