<?php
// Dodajemy pola ACF dla mission
add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // Główna grupa pól dla misji
    acf_add_local_field_group(array(
        'key' => 'group_mission_details',
        'title' => 'Szczegóły misji',
        'fields' => array(
            // Typ misji
            array(
                'key' => 'field_mission_type',
                'label' => 'Typ misji',
                'name' => 'type',
                'type' => 'select',
                'instructions' => 'Określa typ misji i jej powtarzalność',
                'required' => 1,
                'choices' => array(
                    'fabularna' => 'Fabularna',
                    'poboczna' => 'Poboczna',
                    'daily' => 'Dzienna',
                    'weekly' => 'Tygodniowa',
                ),
                'default_value' => 'poboczna',
                'return_format' => 'value',
                'multiple' => 0,
                'allow_null' => 0,
            ),
            // Zadania misji (repeater)
            array(
                'key' => 'field_mission_tasks',
                'label' => 'Zadania misji',
                'name' => 'tasks',
                'type' => 'repeater',
                'instructions' => 'Lista zadań do wykonania w misji',
                'required' => 0,
                'min' => 1,
                'max' => 0,
                'layout' => 'block',
                'button_label' => 'Dodaj zadanie',
                'sub_fields' => array(
                    // Opis zadania
                    array(
                        'key' => 'field_mission_task_description',
                        'label' => 'Opis zadania',
                        'name' => 'description',
                        'type' => 'wysiwyg',
                        'instructions' => 'Co gracz ma zrobić?',
                        'required' => 1,
                        'default_value' => '',
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 0,
                    ),
                    // Warunki zaliczenia zadania (Flexible Content)
                    array(
                        'key' => 'field_mission_task_requirements',
                        'label' => 'Warunki zaliczenia',
                        'name' => 'requirements',
                        'type' => 'flexible_content',
                        'instructions' => 'Warunki, które muszą być spełnione (wszystkie), aby zaliczyć zadanie',
                        'required' => 1,
                        'layouts' => array(
                            // Warunek: Odwiedzenie lokacji
                            'layout_visited_location' => array(
                                'key' => 'layout_visited_location',
                                'name' => 'visited_location',
                                'label' => 'Odwiedzona lokacja',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_location_id',
                                        'label' => 'ID lokacji',
                                        'name' => 'location_id',
                                        'type' => 'text',
                                        'required' => 1,
                                        'instructions' => 'Wprowadź ID lokacji, którą gracz musi odwiedzić'
                                    ),
                                ),
                            ),
                            // Warunek: Wartość statystyki powyżej
                            'layout_stat_above' => array(
                                'key' => 'layout_stat_above',
                                'name' => 'stat_above',
                                'label' => 'Statystyka powyżej',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_stat_above_type',
                                        'label' => 'Typ umiejętności',
                                        'name' => 'stat_type',
                                        'type' => 'select',
                                        'required' => 1,
                                        'choices' => array(
                                            'combat' => 'Walka',
                                            'steal' => 'Kradzież',
                                            'craft' => 'Produkcja',
                                            'trade' => 'Handel',
                                            'relations' => 'Relacje',
                                            'street' => 'Uliczna wiedza',
                                            'technical' => 'Zdolności manualne',
                                            'charisma' => 'Cwaniactwo',
                                        ),
                                        'return_format' => 'value',
                                    ),
                                    array(
                                        'key' => 'field_stat_above_value',
                                        'label' => 'Minimalna wartość',
                                        'name' => 'min_value',
                                        'type' => 'number',
                                        'required' => 1,
                                        'min' => 1,
                                        'default_value' => 5,
                                    ),
                                ),
                            ),
                            // Warunek: Wartość statystyki poniżej
                            'layout_stat_below' => array(
                                'key' => 'layout_stat_below',
                                'name' => 'stat_below',
                                'label' => 'Statystyka poniżej',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_stat_below_type',
                                        'label' => 'Typ umiejętności',
                                        'name' => 'stat_type',
                                        'type' => 'select',
                                        'required' => 1,
                                        'choices' => array(
                                            'combat' => 'Walka',
                                            'steal' => 'Kradzież',
                                            'craft' => 'Produkcja',
                                            'trade' => 'Handel',
                                            'relations' => 'Relacje',
                                            'street' => 'Uliczna wiedza',
                                            'technical' => 'Zdolności manualne',
                                            'charisma' => 'Cwaniactwo',
                                        ),
                                        'return_format' => 'value',
                                    ),
                                    array(
                                        'key' => 'field_stat_below_value',
                                        'label' => 'Maksymalna wartość',
                                        'name' => 'max_value',
                                        'type' => 'number',
                                        'required' => 1,
                                        'min' => 0,
                                        'default_value' => 10,
                                    ),
                                ),
                            ),

                            // Warunek: Relacja z NPC
                            'layout_relation_with_npc' => array(
                                'key' => 'layout_relation_with_npc',
                                'name' => 'relation_with_npc',
                                'label' => 'Relacja z NPC',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_npc_id',
                                        'label' => 'NPC',
                                        'name' => 'npc_id',
                                        'type' => 'post_object',
                                        'required' => 1,
                                        'post_type' => array('npc'),
                                        'return_format' => 'id',
                                        'multiple' => 0,
                                    ),
                                    array(
                                        'key' => 'field_npc_relation_value',
                                        'label' => 'Wartość relacji',
                                        'name' => 'relation_value',
                                        'type' => 'number',
                                        'required' => 1,
                                        'default_value' => 0,
                                    ),
                                ),
                            ),
                            // Warunek: Posiadanie przedmiotu (perk)
                            'layout_has_perk' => array(
                                'key' => 'layout_has_perk',
                                'name' => 'has_perk',
                                'label' => 'Posiada przedmiot (perk)',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_perk_id',
                                        'label' => 'Przedmiot',
                                        'name' => 'perk_id',
                                        'type' => 'post_object',
                                        'required' => 1,
                                        'post_type' => array('perks'),
                                        'return_format' => 'id',
                                        'multiple' => 0,
                                    ),
                                    array(
                                        'key' => 'field_perk_amount',
                                        'label' => 'Ilość',
                                        'name' => 'amount',
                                        'type' => 'number',
                                        'required' => 1,
                                        'default_value' => 1,
                                        'min' => 1,
                                    ),
                                ),
                            ),
                            // Warunek: Inna misja ukończona
                            'layout_mission_completed' => array(
                                'key' => 'layout_mission_completed',
                                'name' => 'mission_completed',
                                'label' => 'Misja ukończona',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_required_mission_id',
                                        'label' => 'Wymagana misja',
                                        'name' => 'required_mission',
                                        'type' => 'post_object',
                                        'required' => 1,
                                        'post_type' => array('mission'),
                                        'return_format' => 'id',
                                        'multiple' => 0,
                                    ),
                                ),
                            ),
                            // Warunek: Inna misja nie oblana
                            'layout_not_failed_mission' => array(
                                'key' => 'layout_not_failed_mission',
                                'name' => 'not_failed_mission',
                                'label' => 'Misja nie oblana',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_not_failed_mission_id',
                                        'label' => 'Misja',
                                        'name' => 'not_failed_mission',
                                        'type' => 'post_object',
                                        'required' => 1,
                                        'post_type' => array('mission'),
                                        'return_format' => 'id',
                                        'multiple' => 0,
                                    ),
                                ),
                            ),
                        ),
                        'min' => 1,
                        'button_label' => 'Dodaj warunek',
                    ),
                    // Nagrody za zadanie (Flexible Content)
                    array(
                        'key' => 'field_mission_task_rewards',
                        'label' => 'Nagrody',
                        'name' => 'rewards',
                        'type' => 'flexible_content',
                        'instructions' => 'Nagrody przyznawane po zaliczeniu zadania',
                        'required' => 0,
                        'layouts' => array(
                            // Nagroda: Dodaj przedmiot do plecaka
                            'layout_add_item' => array(
                                'key' => 'layout_add_item',
                                'name' => 'add_item',
                                'label' => 'Dodaj przedmiot',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_reward_item_type',
                                        'label' => 'Typ przedmiotu',
                                        'name' => 'item_type',
                                        'type' => 'select',
                                        'required' => 1,
                                        'choices' => array(
                                            'gold' => 'Złote',
                                            'cigarettes' => 'Papierosy',
                                            'beer' => 'Piwo',
                                            'moonshine' => 'Bimber',
                                            'weed' => 'Zioło',
                                            'mushrooms' => 'Grzyby',
                                            'glue' => 'Klej',
                                        ),
                                        'return_format' => 'value',
                                    ),
                                    array(
                                        'key' => 'field_reward_item_amount',
                                        'label' => 'Ilość',
                                        'name' => 'amount',
                                        'type' => 'number',
                                        'required' => 1,
                                        'min' => 1,
                                        'default_value' => 1,
                                    ),
                                ),
                            ),
                            // Nagroda: Modyfikuj relację z NPC
                            'layout_add_relation_with_npc' => array(
                                'key' => 'layout_add_relation_with_npc',
                                'name' => 'add_relation_with_npc',
                                'label' => 'Zmień relację z NPC',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_reward_npc_id',
                                        'label' => 'NPC',
                                        'name' => 'npc_id',
                                        'type' => 'post_object',
                                        'required' => 1,
                                        'post_type' => array('npc'),
                                        'return_format' => 'id',
                                        'multiple' => 0,
                                    ),
                                    array(
                                        'key' => 'field_reward_relation_change',
                                        'label' => 'Zmiana relacji',
                                        'name' => 'relation_change',
                                        'type' => 'number',
                                        'required' => 1,
                                        'default_value' => 1,
                                    ),
                                ),
                            ),
                            // Nagroda: Dodaj przedmiot (perk)
                            'layout_add_perk' => array(
                                'key' => 'layout_add_perk',
                                'name' => 'add_perk',
                                'label' => 'Dodaj przedmiot (perk)',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_reward_perk_id',
                                        'label' => 'Przedmiot',
                                        'name' => 'perk_id',
                                        'type' => 'post_object',
                                        'required' => 1,
                                        'post_type' => array('perks'),
                                        'return_format' => 'id',
                                        'multiple' => 0,
                                    ),
                                    array(
                                        'key' => 'field_reward_perk_amount',
                                        'label' => 'Ilość',
                                        'name' => 'amount',
                                        'type' => 'number',
                                        'required' => 1,
                                        'min' => 1,
                                        'default_value' => 1,
                                    ),
                                ),
                            ),
                            // Nagroda: Dodaj doświadczenie
                            'layout_add_exp' => array(
                                'key' => 'layout_add_exp',
                                'name' => 'add_exp',
                                'label' => 'Dodaj doświadczenie',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_reward_exp_amount',
                                        'label' => 'Ilość EXP',
                                        'name' => 'exp_amount',
                                        'type' => 'number',
                                        'required' => 1,
                                        'min' => 1,
                                        'default_value' => 10,
                                    ),
                                ),
                            ),
                            // Nagroda: Dodaj/Odejmij reputację
                            'layout_add_reputation' => array(
                                'key' => 'layout_add_reputation',
                                'name' => 'add_reputation',
                                'label' => 'Zmień reputację',
                                'display' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_reward_reputation_change',
                                        'label' => 'Zmiana reputacji',
                                        'name' => 'reputation_change',
                                        'type' => 'number',
                                        'required' => 1,
                                        'min' => -100,
                                        'max' => 100,
                                        'default_value' => 5,
                                    ),
                                ),
                            ),
                        ),
                        'min' => 0,
                        'button_label' => 'Dodaj nagrodę',
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
        'description' => 'Pola konfiguracyjne dla misji',
        'show_in_rest' => 0,
    ));
});
