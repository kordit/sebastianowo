<?php
if (function_exists('acf_add_options_page')) {
    acf_add_options_page(array(
        'page_title'    => 'Ustawienia gry',
        'menu_title'    => 'Ustawienia gry',
        'menu_slug'     => 'theme-general-settings',
        'capability'    => 'edit_posts',
        'redirect'      => false,
        // 'position' => '11',

    ));

    $all_instance = get_all_instance();
    $all_fields = array(); // Zbiera wszystkie grupy pól

    foreach ($all_instance as $instance) {
        acf_add_options_sub_page(array(
            'page_title'    => 'Ustawienia lokacji "' . $instance . '"',
            'menu_title'    => ucfirst($instance),
            'parent_slug'   => 'theme-general-settings',
            'position' => '99',
        ));

        $slug = sanitize_title($instance);

        $fields = array(
            'key' => 'group_' . uniqid(), // Generuj unikalny klucz dla każdej grupy
            'title' => $instance,
            'fields' => array(
                array(
                    'key' => 'instance_img_' . $slug,
                    'label' => 'Grafika lokacji',
                    'name' => 'instance_img_' . $slug,
                    'type' => 'image',
                    'return_format' => 'url',
                    'wrapper' => array(
                        'width' => '30',
                        'class' => '',
                        'id' => '',
                    ),
                ),
                array(
                    'key' => 'instance_description_' . $slug,
                    'label' => 'Opis lokacji',
                    'name' => 'instance_description_' . $slug,
                    'type' => 'wysiwyg',
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 0,
                    'wrapper' => array(
                        'width' => '70',
                        'class' => '',
                        'id' => '',
                    ),
                ),
                array(
                    'key' => 'instance_' . $slug,
                    'label' => 'Ruchy',
                    'name' => 'instance_' . $slug,
                    'type' => 'repeater',
                    'layout' => 'table',
                    'min' => 0,
                    'max' => 0,
                    'button_label' => 'Dodaj ruch',
                    'rows_per_page' => 20,
                    'sub_fields' => array(
                        array(
                            'key' => 'instance_dialog_' . $slug,
                            'label' => 'Komunikat',
                            'name' => 'dialog',
                            'type' => 'text',
                            'required' => 1,


                        ),
                        array(
                            'key' => 'instance_action_' . $slug,
                            'label' => 'Akcja',
                            'name' => 'action',
                            'aria-label' => '',
                            'type' => 'select',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'choices' => array(
                                'damage' => 'Obrażenia',
                                'monster' => 'Potwór',
                                'neutral' => 'Neutralne',
                                'mineral' => 'Zysk',
                            ),

                        ),
                        array(
                            'key' => 'instance_profit_' . $slug,
                            'label' => 'Zysk akcji',
                            'name' => 'profit',
                            'type' => 'select',
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'instance_action_' . $slug,
                                        'operator' => '==',
                                        'value' => 'mineral',
                                    ),
                                ),
                            ),
                            'choices' => array(
                                'iron' => 'Żelazo',
                                'wood' => 'Drewno',
                                'gold' => 'Złoto',
                            ),

                        ),
                        array(
                            'key' => 'instance_monster_' . $slug,
                            'label' => 'Wybierz potwora',
                            'name' => 'monster',
                            'type' => 'post_object',
                            'post_type' => array(
                                0 => 'monster',
                            ),
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'instance_action_' . $slug,
                                        'operator' => '==',
                                        'value' => 'monster',
                                    ),
                                    array(
                                        'field' => 'instance_action_' . $slug,
                                        'operator' => '!=',
                                        'value' => 'neutral',
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'key' => 'instance_from_' . $slug,
                            'label' => 'Od ilu',
                            'name' => 'from',
                            'type' => 'number',
                            'required' => 0,
                            'default_value' => '0',
                            'min' => 0,
                            'step' => '1',
                        ),
                        array(
                            'key' => 'instance_to_' . $slug,
                            'label' => 'do ilu',
                            'name' => 'to',
                            'type' => 'number',
                            'default_value' => '0',
                            'min' => 0,
                            'step' => '1',
                        ),
                        array(
                            'key' => 'instance_frequency_' . $slug,
                            'label' => 'Częstotliwość',
                            'name' => 'frequency',
                            'type' => 'number',
                            'default_value' => 1,
                            'min' => 0,
                            'step' => '1',
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'acf-options-' . $slug,
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
            'description' => '',
            'show_in_rest' => 0,
        );

        $all_fields[] = $fields; // Dodaj grupę pól do tablicy
    }

    // Dodaj wszystkie grupy pól po zakończeniu pętli
    add_action('acf/init', function () use ($all_fields) {
        if (function_exists('acf_add_local_field_group')) {
            foreach ($all_fields as $fields) {
                acf_add_local_field_group($fields);
            }
        }
    });
}
