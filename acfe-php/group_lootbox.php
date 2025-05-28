<?php
// Grupa pól ACF dla CPT "lootbox"
if (function_exists('acf_add_local_field_group')):

    acf_add_local_field_group(array(
        'key' => 'group_lootbox',
        'title' => 'Lootbox',
        'fields' => array(
            array(
                'key' => 'field_lootbox_rewards',
                'label' => 'Nagrody',
                'name' => 'lootbox_rewards',
                'type' => 'repeater',
                'sub_fields' => array(
                    array(
                        'key' => 'field_lootbox_reward_type',
                        'label' => 'Typ nagrody',
                        'name' => 'reward_type',
                        'type' => 'select',
                        'choices' => array(
                            'gold' => 'Złoto',
                            'szlugi' => 'Szlugi',
                            'item' => 'Item',
                        ),
                    ),
                    array(
                        'key' => 'field_lootbox_reward_item',
                        'label' => 'Przedmiot',
                        'name' => 'item',
                        'type' => 'post_object',
                        'post_type' => array('item'),
                        'return_format' => 'id',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_lootbox_reward_type',
                                    'operator' => '==',
                                    'value' => 'item',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_lootbox_reward_min',
                        'label' => 'Minimalna ilość',
                        'name' => 'min',
                        'type' => 'number',
                        'min' => 1,
                    ),
                    array(
                        'key' => 'field_lootbox_reward_max',
                        'label' => 'Maksymalna ilość',
                        'name' => 'max',
                        'type' => 'number',
                        'min' => 1,
                    ),
                    array(
                        'key' => 'field_lootbox_reward_draws',
                        'label' => 'Ilość wrzuceń do puli',
                        'name' => 'draws',
                        'type' => 'number',
                        'min' => 1,
                        'default_value' => 1,
                    ),
                    array(
                        'key' => 'field_lootbox_reward_message',
                        'label' => 'Komunikat przy wylosowaniu',
                        'name' => 'message',
                        'type' => 'textarea',
                        'rows' => 2,
                        'placeholder' => 'Np. "Znalazłeś {{$i}} złota"',
                        'instructions' => 'Użyj {{$i}} jako oznaczenie wylosowanej liczby',
                    ),
                ),
            ),
            array(
                'key' => 'field_lootbox_price',
                'label' => 'Koszt losowania (energia)',
                'name' => 'lootbox_price',
                'type' => 'number',
                'min' => 0,
            ),
            array(
                'key' => 'field_lootbox_rounds_from',
                'label' => 'Liczba rund losowania (od)',
                'name' => 'lootbox_rounds_from',
                'type' => 'number',
                'min' => 1,
                'default_value' => 1,
                'wrapper' => array(
                    'width' => 50,
                ),
            ),

            array(
                'key' => 'field_lootbox_rounds_to',
                'label' => 'Liczba rund losowania (do)',
                'name' => 'lootbox_rounds_to',
                'type' => 'number',
                'min' => 1,
                'default_value' => 1,
                'wrapper' => array(
                    'width' => 50,
                ),
            ),

        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'lootbox',
                ),
            ),
        ),
    ));

endif;
