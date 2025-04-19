<?php
// Rejestracja ACF Field Group dla CPT "item"
add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key'                   => 'item_fields',
        'title'                 => 'Dodatkowe informacje o przedmiocie',
        'fields'                => array(
            array(
                'key'           => 'field_item_description',
                'label'         => 'Opis przedmiotu',
                'name'          => 'item_description',
                'type'          => 'textarea',
                'instructions'  => 'Krótki opis przedmiotu',
                'required'      => 1,
            ),
            array(
                'key'           => 'field_item_value',
                'label'         => 'Wartość',
                'name'          => 'item_value',
                'type'          => 'number',
                'instructions'  => 'Wartość przedmiotu',
                'required'      => 0,
                'default_value' => 0,
            ),
            array(
                'key'           => 'field_item_weight',
                'label'         => 'Waga',
                'name'          => 'item_weight',
                'type'          => 'number',
                'instructions'  => 'Waga przedmiotu w kg',
                'required'      => 0,
                'default_value' => 0,
            ),
            array(
                'key'           => 'field_item_durability',
                'label'         => 'Wytrzymałość',
                'name'          => 'item_durability',
                'type'          => 'number',
                'instructions'  => 'Wytrzymałość przedmiotu (jeśli dotyczy)',
                'required'      => 0,
                'default_value' => 100,
            ),
            array(
                'key'           => 'field_item_stats',
                'label'         => 'Statystyki',
                'name'          => 'item_stats',
                'type'          => 'repeater',
                'instructions'  => 'Jakie statystyki zwiększa przedmiot',
                'required'      => 0,
                'layout'        => 'table',
                'button_label'  => 'Dodaj statystykę',
                'sub_fields'    => array(
                    array(
                        'key'           => 'field_stat_type',
                        'label'         => 'Typ statystyki',
                        'name'          => 'stat_type',
                        'type'          => 'select',
                        'choices'       => array(
                            'strength'  => 'Siła (obrażenia w walce wręcz, dominacja w bójkach)',
                            'vitality'  => 'Wytrzymałość (życie, odporność na używki, obrażenia)',
                            'dexterity' => 'Zręczność (kradzież, uniki, ucieczki)',
                            'perception' => 'Percepcja (wykrywanie ukrytych elementów)',
                            'technical' => 'Zdolności manualne (produkcja, hakowanie, techniczne akcje)',
                            'charisma'  => 'Cwaniactwo (bajerowanie NPC, układy, handel)'
                        ),
                        'default_value' => 'strength',
                        'required'      => 1,
                    ),
                    array(
                        'key'           => 'field_stat_value',
                        'label'         => 'Wartość bonusu',
                        'name'          => 'stat_value',
                        'type'          => 'number',
                        'default_value' => 1,
                        'min' => 0,
                        'required'      => 1,
                    ),
                ),
            ),
            array(
                'key'           => 'field_item_skills',
                'label'         => 'Umiejętności',
                'name'          => 'item_skills',
                'type'          => 'repeater',
                'instructions'  => 'Jakie umiejętności zwiększa przedmiot',
                'required'      => 0,
                'layout'        => 'table',
                'button_label'  => 'Dodaj umiejętność',
                'sub_fields'    => array(
                    array(
                        'key'           => 'field_skill_type',
                        'label'         => 'Typ umiejętności',
                        'name'          => 'skill_type',
                        'type'          => 'select',
                        'choices'       => array(
                            'combat'    => 'Walka',
                            'steal'     => 'Kradzież',
                            'craft'     => 'Produkcja',
                            'trade'     => 'Handel',
                            'relations' => 'Relacje',
                            'street'    => 'Uliczna wiedza',
                            'technical' => 'Zdolności manualne',
                            'charisma'  => 'Cwaniactwo',
                        ),
                        'default_value' => 'combat',
                        'required'      => 1,
                    ),
                    array(
                        'key'           => 'field_skill_value',
                        'label'         => 'Wartość',
                        'name'          => 'skill_value',
                        'type'          => 'number',
                        'default_value' => 1,
                        'min'           => 0,
                        'required'      => 1,
                    ),
                ),
            ),

        ),
        'location' => array(
            array(
                array(
                    'param'     => 'post_type',
                    'operator'  => '==',
                    'value'     => 'item',
                ),
            ),
        ),
        'menu_order'          => 0,
        'position'            => 'normal',
        'style'               => 'default',
        'label_placement'     => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen'      => array('the_content'),
        'active'              => true,
        'show_in_rest'        => false,
    ));
});
