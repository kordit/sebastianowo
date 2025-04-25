<?php

/**
 * Rejestracja pól dla misji użytkownika
 */
add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // Standardowe statusy dla misji i zadań
    $status_choices = array(
        'not_started' => 'Niezaczęta',
        'in_progress' => 'Rozpoczęta',
        'completed' => 'Ukończona',
        'failed' => 'Oblana',
    );

    // Przygotowanie pól dla misji użytkownika
    $mission_fields = [];

    // Pobieranie wszystkich misji
    $missions = get_posts([
        'post_type' => 'mission',
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ]);

    // Jeśli istnieją misje, tworzymy dla każdej grupę
    if (!empty($missions)) {
        foreach ($missions as $mission) {
            // Pobierz zadania przypisane do tej misji
            $mission_tasks = get_field('mission_tasks', $mission->ID);
            $task_fields = [];

            // Przygotuj pola dla każdego zadania w misji
            if (!empty($mission_tasks)) {
                foreach ($mission_tasks as $task_index => $task) {
                    if (isset($task['task_title'])) {
                        // Jeśli task_id nie istnieje, wygeneruj go na podstawie tytułu zadania
                        $task_id = isset($task['task_id']) ? $task['task_id'] : sanitize_title($task['task_title']) . '_' . $task_index;
                        $task_title = $task['task_title'];

                        // Określenie typu zadania
                        $task_type = isset($task['task_type']) ? $task['task_type'] : 'checkpoint';

                        // Specjalne traktowanie dla zadań typu checkpoint_npc
                        if ($task_type == 'checkpoint_npc' && !empty($task['task_checkpoint_npc']) && is_array($task['task_checkpoint_npc'])) {
                            // Dla zadań z NPC, tworzymy grupę z checkbox dla każdego NPC
                            $npc_sub_fields = [];

                            foreach ($task['task_checkpoint_npc'] as $npc_index => $npc_info) {
                                if (!empty($npc_info['npc'])) {
                                    $npc_id = $npc_info['npc'];
                                    $npc_post = get_post($npc_id);
                                    $npc_name = $npc_post ? $npc_post->post_title : 'NPC #' . $npc_id;                                    // Pobierz status NPC z danych zadania
                                    $npc_status = isset($npc_info['status']) ? $npc_info['status'] : 'not_started';

                                    $npc_sub_fields[] = array(
                                        'key' => 'field_task_npc_status_' . $mission->ID . '_' . $task_id . '_' . $npc_id,
                                        'label' => $npc_name . ' (status oryginalny)',
                                        'name' => 'npc_status_' . $npc_id,
                                        'type' => 'select',
                                        'instructions' => '',
                                        'choices' => array(
                                            'not_started' => 'Niezaczęte',
                                            'in_progress' => 'Rozpoczęte',
                                            'completed' => 'Ukończone',
                                            'failed' => 'Niepowodzenie',
                                        ),
                                        'default_value' => $npc_status,
                                        'readonly' => 1,
                                        'disabled' => 1,
                                        'ui' => 1,
                                        'ajax' => 0,
                                        'allow_null' => 0,
                                        'return_format' => 'value',
                                        'wrapper' => array(
                                            'width' => '50',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                    );

                                    $npc_sub_fields[] = array(
                                        'key' => 'field_task_npc_' . $mission->ID . '_' . $task_id . '_' . $npc_id,
                                        'label' => $npc_name . ' (wykonane)',
                                        'name' => 'npc_' . $npc_id,
                                        'type' => 'true_false',
                                        'instructions' => '',
                                        'default_value' => 0,
                                        'ui' => 1,
                                        'ui_on_text' => 'Wykonane',
                                        'ui_off_text' => 'Niewykonane',
                                        'wrapper' => array(
                                            'width' => '50',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                    );
                                }
                            }

                            $task_fields[] = array(
                                'key' => 'field_task_' . $mission->ID . '_' . $task_id,
                                'label' => $task_title,
                                'name' => $task_id,
                                'type' => 'group',
                                'instructions' => $task['task_description'] ?? '',
                                'layout' => 'block',
                                'sub_fields' => $npc_sub_fields
                            );
                        } else {
                            // Dla standardowych zadań (checkpoint, place) - pojedynczy przełącznik true/false
                            $task_fields[] = array(
                                'key' => 'field_task_' . $mission->ID . '_' . $task_id,
                                'label' => $task_title,
                                'name' => $task_id,
                                'type' => 'true_false',
                                'instructions' => $task['task_description'] ?? '',
                                'default_value' => 0,
                                'ui' => 1,
                                'ui_on_text' => 'Wykonane',
                                'ui_off_text' => 'Niewykonane',
                            );
                        }
                    }
                }
            }

            // Tworzenie grupy dla misji z jej zadaniami
            $mission_fields[] = array(
                'key' => 'field_mission_' . $mission->ID,
                'label' => $mission->post_title,
                'name' => 'mission_' . $mission->ID,
                'type' => 'group',
                'instructions' => '',
                'required' => 0,
                'layout' => 'block',
                'sub_fields' => array_merge(
                    [
                        array(
                            'key' => 'field_mission_status_' . $mission->ID,
                            'label' => 'Status misji',
                            'name' => 'status',
                            'type' => 'select',
                            'instructions' => 'Status misji',
                            'required' => 1,
                            'choices' => $status_choices,
                            'default_value' => 'not_started',
                            'allow_null' => 0,
                            'multiple' => 0,
                            'ui' => 1,
                            'ajax' => 0,
                            'search_placeholder' => 'Wybierz status',
                            'allow_custom' => 0,
                            'return_format' => 'value',
                            'wrapper' => array(
                                'width' => '30',
                                'class' => '',
                                'id' => '',
                            ),
                        ),
                        array(
                            'key' => 'field_mission_assigned_date_' . $mission->ID,
                            'label' => 'Data przypisania',
                            'name' => 'assigned_date',
                            'type' => 'date_time_picker',
                            'instructions' => 'Data przypisania misji',
                            'required' => 0,
                            'display_format' => 'd/m/Y g:i a',
                            'return_format' => 'Y-m-d H:i:s',
                            'wrapper' => array(
                                'width' => '35',
                                'class' => '',
                                'id' => '',
                            ),
                        ),
                        array(
                            'key' => 'field_mission_completion_date_' . $mission->ID,
                            'label' => 'Data zakończenia',
                            'name' => 'completion_date',
                            'type' => 'date_time_picker',
                            'instructions' => 'Data ukończenia lub oblania misji',
                            'required' => 0,
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_mission_status_' . $mission->ID,
                                        'operator' => '==',
                                        'value' => 'completed',
                                    ),
                                ),
                                array(
                                    array(
                                        'field' => 'field_mission_status_' . $mission->ID,
                                        'operator' => '==',
                                        'value' => 'failed',
                                    ),
                                ),
                            ),
                            'display_format' => 'd/m/Y g:i a',
                            'return_format' => 'Y-m-d H:i:s',
                            'wrapper' => array(
                                'width' => '35',
                                'class' => '',
                                'id' => '',
                            ),
                        ),
                        array(
                            'key' => 'field_mission_tasks_' . $mission->ID,
                            'label' => 'Zadania',
                            'name' => 'tasks',
                            'type' => 'group',
                            'instructions' => 'Zadania w misji',
                            'required' => 0,
                            'layout' => 'block',
                            'sub_fields' => $task_fields,
                        ),
                    ]
                ),
            );
        }
    }

    // Grupa pól do zarządzania misjami użytkownika
    acf_add_local_field_group(array(
        'key' => 'group_user_missions',
        'title' => 'Misje Użytkownika',
        'fields' => $mission_fields,
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
