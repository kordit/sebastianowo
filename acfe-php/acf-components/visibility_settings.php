<?php

/**
 * ACF Component: Visibility Settings
 * 
 * Komponent reprezentujący ustawienia widoczności dla dialogów i odpowiedzi.
 * Pozwala na kontrolę wyświetlania elementów w zależności od stanu gry.
 * 
 * @package Game
 * @since 1.0.0
 */

if (!function_exists('game_get_visibility_settings_component')) {
    /**
     * Zwraca konfigurację komponentu ustawień widoczności
     * 
     * @return array Konfiguracja pola ACF
     */
    function game_get_visibility_settings_component(): array
    {
        return array(
            'key' => 'field_visibility_settings',
            'label' => 'Ustawienia widoczności',
            'name' => 'visibility_settings',
            'type' => 'flexible_content',
            'instructions' => 'Określ warunki, w których ten element będzie widoczny.',
            'required' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'layouts' => array(
                'layout_condition_location' => array(
                    'key' => 'layout_condition_location',
                    'name' => 'condition_location',
                    'label' => 'Warunek: Lokalizacja',
                    'display' => 'block',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_visibility_location_condition',
                            'label' => 'Warunek',
                            'name' => 'condition',
                            'type' => 'select',
                            'instructions' => '',
                            'required' => 0,
                            'wrapper' => array(
                                'width' => '30',
                                'class' => '',
                                'id' => '',
                            ),
                            'choices' => array(
                                'is' => 'Jest w lokalizacji',
                                'is_not' => 'Nie jest w lokalizacji',
                            ),
                            'default_value' => 'is',
                            'allow_null' => 0,
                            'multiple' => 0,
                            'ui' => 0,
                            'return_format' => 'value',
                            'ajax' => 0,
                            'placeholder' => '',
                        ),
                        array(
                            'key' => 'field_visibility_location_type',
                            'label' => 'Typ lokalizacji',
                            'name' => 'location_type',
                            'type' => 'select',
                            'instructions' => 'Wybierz czy chcesz określić lokalizację jako teren czy jako tekst',
                            'required' => 0,
                            'wrapper' => array(
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ),
                            'choices' => array(
                                'area' => 'Teren (wybór z listy)',
                                'text' => 'Tekst (wprowadź ręcznie)',
                            ),
                            'default_value' => 'area',
                            'allow_null' => 0,
                            'multiple' => 0,
                            'ui' => 0,
                            'return_format' => 'value',
                            'ajax' => 0,
                            'placeholder' => '',
                        ),
                        array(
                            'key' => 'field_visibility_location_area',
                            'label' => 'Wybierz lokalizację',
                            'name' => 'area',
                            'type' => 'post_object',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_visibility_location_type',
                                        'operator' => '==',
                                        'value' => 'area',
                                    ),
                                ),
                            ),
                            'wrapper' => array(
                                'width' => '50',
                                'class' => '',
                                'id' => '',
                            ),
                            'post_type' => array(
                                0 => 'tereny',
                            ),
                            'taxonomy' => '',
                            'allow_null' => 0,
                            'multiple' => 0,
                            'return_format' => 'id',
                            'ui' => 1,
                        ),
                        array(
                            'key' => 'field_visibility_location_text',
                            'label' => 'Wprowadź nazwę lokalizacji',
                            'name' => 'location_text',
                            'type' => 'text',
                            'instructions' => 'Wpisz dokładną nazwę lokalizacji',
                            'required' => 0,
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_visibility_location_type',
                                        'operator' => '==',
                                        'value' => 'text',
                                    ),
                                ),
                            ),
                            'wrapper' => array(
                                'width' => '50',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'placeholder' => 'np. Osiedle Kolejowa',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                    ),
                ),
                'layout_condition_mission' => array(
                    'key' => 'layout_condition_mission',
                    'name' => 'condition_mission',
                    'label' => 'Warunek: Misja',
                    'display' => 'block',

                ),
                'layout_condition_task' => array(
                    'key' => 'layout_condition_task',
                    'name' => 'condition_task',
                    'label' => 'Warunek: Zadanie',
                    'display' => 'block',

                ),
                'layout_condition_npc_relation' => array(
                    'key' => 'layout_condition_npc_relation',
                    'name' => 'condition_npc_relation',
                    'label' => 'Warunek: Relacja z NPC',
                    'display' => 'block',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_visibility_relation_condition',
                            'label' => 'Warunek',
                            'name' => 'condition',
                            'type' => 'select',
                            'instructions' => '',
                            'required' => 0,
                            'wrapper' => array(
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ),
                            'choices' => array(
                                'is_known' => 'Jest znanym NPC',
                                'is_not_known' => 'Nie jest znanym NPC',
                                'relation_above' => 'Relacja powyżej wartości',
                                'relation_below' => 'Relacja poniżej wartości',
                                'relation_equal' => 'Relacja równa wartości',
                            ),
                            'default_value' => 'is_known',
                            'allow_null' => 0,
                            'multiple' => 0,
                            'ui' => 0,
                            'return_format' => 'value',
                            'ajax' => 0,
                            'placeholder' => '',
                        ),
                        array(
                            'key' => 'field_visibility_npc_select',
                            'label' => 'Wybierz NPC',
                            'name' => 'npc_id',
                            'type' => 'post_object',
                            'instructions' => '',
                            'required' => 0,
                            'wrapper' => array(
                                'width' => '40',
                                'class' => '',
                                'id' => '',
                            ),
                            'post_type' => array(
                                0 => 'npc',
                            ),
                            'taxonomy' => '',
                            'allow_null' => 0,
                            'multiple' => 0,
                            'return_format' => 'id',
                            'ui' => 1,
                        ),
                        array(
                            'key' => 'field_visibility_relation_value',
                            'label' => 'Wartość relacji',
                            'name' => 'relation_value',
                            'type' => 'number',
                            'instructions' => 'Podaj wartość relacji (-100 do 100)',
                            'required' => 0,
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_visibility_relation_condition',
                                        'operator' => '==',
                                        'value' => 'relation_above',
                                    ),
                                ),
                                array(
                                    array(
                                        'field' => 'field_visibility_relation_condition',
                                        'operator' => '==',
                                        'value' => 'relation_below',
                                    ),
                                ),
                                array(
                                    array(
                                        'field' => 'field_visibility_relation_condition',
                                        'operator' => '==',
                                        'value' => 'relation_equal',
                                    ),
                                ),
                            ),
                            'wrapper' => array(
                                'width' => '40',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => 50,
                            'min' => -100,
                            'max' => 100,
                            'step' => 1,
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                        ),
                    ),
                ),
                'layout_condition_inventory' => array(
                    'key' => 'layout_condition_inventory',
                    'name' => 'condition_inventory',
                    'label' => 'Warunek: Przedmiot w ekwipunku',
                    'display' => 'block',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_visibility_inventory_condition',
                            'label' => 'Warunek',
                            'name' => 'condition',
                            'type' => 'select',
                            'instructions' => '',
                            'required' => 0,
                            'wrapper' => array(
                                'width' => '30',
                                'class' => '',
                                'id' => '',
                            ),
                            'choices' => array(
                                'has_item' => 'Posiada przedmiot',
                                'has_not_item' => 'Nie posiada przedmiotu',
                                'quantity_above' => 'Ilość przedmiotu powyżej',
                                'quantity_below' => 'Ilość przedmiotu poniżej',
                                'quantity_equal' => 'Ilość przedmiotu równa',
                            ),
                            'default_value' => 'has_item',
                            'allow_null' => 0,
                            'multiple' => 0,
                            'ui' => 0,
                            'return_format' => 'value',
                            'ajax' => 0,
                            'placeholder' => '',
                        ),
                        array(
                            'key' => 'field_visibility_inventory_item',
                            'label' => 'Wybierz przedmiot',
                            'name' => 'item_id',
                            'type' => 'post_object',
                            'instructions' => '',
                            'required' => 0,
                            'wrapper' => array(
                                'width' => '40',
                                'class' => '',
                                'id' => '',
                            ),
                            'post_type' => array(
                                0 => 'item',
                            ),
                            'taxonomy' => '',
                            'allow_null' => 0,
                            'multiple' => 0,
                            'return_format' => 'id',
                            'ui' => 1,
                        ),
                        array(
                            'key' => 'field_visibility_inventory_quantity',
                            'label' => 'Ilość',
                            'name' => 'quantity',
                            'type' => 'number',
                            'instructions' => 'Podaj ilość przedmiotu',
                            'required' => 0,
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_visibility_inventory_condition',
                                        'operator' => '==',
                                        'value' => 'quantity_above',
                                    ),
                                ),
                                array(
                                    array(
                                        'field' => 'field_visibility_inventory_condition',
                                        'operator' => '==',
                                        'value' => 'quantity_below',
                                    ),
                                ),
                                array(
                                    array(
                                        'field' => 'field_visibility_inventory_condition',
                                        'operator' => '==',
                                        'value' => 'quantity_equal',
                                    ),
                                ),
                            ),
                            'wrapper' => array(
                                'width' => '30',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => 1,
                            'min' => 1,
                            'max' => '',
                            'step' => 1,
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                        ),
                    ),
                ),
            ),
            'button_label' => 'Dodaj nowy warunek',
            'min' => 0,
            'max' => '',
        );
    }
}

/**
 * Zwraca ustawienia widoczności w formacie odpowiednim dla acfe_flexible_settings
 * 
 * @return string Klucz grupy pól widoczności
 */
if (!function_exists('game_get_visibility_settings_for_flexible')) {
    function game_get_visibility_settings_for_flexible(): string
    {
        // Klucz grupy pól z ustawieniami widoczności
        return 'group_visibility_settings';
    }
}

/**
 * Rejestruje grupę pól dla ustawień widoczności
 */
if (!function_exists('game_register_visibility_settings_group')) {
    function game_register_visibility_settings_group(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        $visibility_component = game_get_visibility_settings_component();

        // Jeśli grupa pól już istnieje, nie rejestruj jej ponownie
        if (acf_get_field_group('group_visibility_settings')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_visibility_settings',
            'title' => 'Ustawienia widoczności',
            'fields' => [
                [
                    'key' => 'field_visibility_tab',
                    'label' => 'Widoczność',
                    'name' => 'visibility_tab',
                    'type' => 'tab',
                    'placement' => 'top',
                ],
                [
                    'key' => 'field_visibility_logic_operator',
                    'label' => 'Operator logiczny między warunkami',
                    'name' => 'logic_operator',
                    'type' => 'select',
                    'instructions' => 'Wybierz operator logiczny dla wszystkich warunków',
                    'required' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'choices' => array(
                        'and' => 'I (wszystkie warunki muszą być spełnione)',
                        'or' => 'LUB (wystarczy jeden spełniony warunek)',
                    ),
                    'default_value' => 'and',
                    'allow_null' => 0,
                    'multiple' => 0,
                    'ui' => 0,
                    'return_format' => 'value',
                    'ajax' => 0,
                    'placeholder' => '',
                ],
                $visibility_component
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'acf-field-group',
                    ],
                ],
            ],
            'active' => true,
        ]);
    }

    // Rejestruj grupę przy inicjalizacji
    add_action('acf/init', 'game_register_visibility_settings_group');
}
