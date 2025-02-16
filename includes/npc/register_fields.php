<?php

function register_cpt_npc()
{
    register_post_type('npc', array(
        'labels' => array(
            'name'               => 'NPC',
            'singular_name'      => 'NPC',
            'menu_name'          => 'NPC',
            'all_items'          => 'Wszystkie NPC',
            'edit_item'          => 'Edytuj NPC',
            'view_item'          => 'Zobacz NPC',
            'add_new_item'       => 'Dodaj nowy NPC',
            'new_item'           => 'Nowy NPC',
            'search_items'       => 'Szukaj NPCów',
            'not_found'          => 'Nie znaleziono NPCów',
            'not_found_in_trash' => 'Nie znaleziono NPCów w koszu',
        ),
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'supports'      => array('title', 'thumbnail'),
        'has_archive'   => false,
        'menu_position' => 5,
        'menu_icon'     => 'dashicons-admin-users',
        'show_in_rest'  => false,
    ));
}
add_action('init', 'register_cpt_npc');

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group(array(
        'key' => 'group_NPC',
        'title' => 'Dane NPC',
        'fields' => array(

            array(
                'key'   => 'tab_conversation_start',
                'label' => 'Start rozmowy',
                'type'  => 'tab',
            ),
            array(
                'key' => 'field_67b08ae59dc59',
                'label' => 'Start rozmowy',
                'name' => 'conversation_start',
                'type' => 'repeater',
                'layout' => 'block',
                'pagination' => 0,
                'min' => 0,
                'max' => 0,
                'collapsed' => '',
                'button_label' => 'Dodaj warunek',
                'rows_per_page' => 20,
                'acfe_repeater_collapsed' => 1,
                'acfe_repeater_edit_in_modal' => 1,
                'sub_fields' => array(
                    array(
                        'key' => 'field_67b08af09dc5a',
                        'label' => 'Jeśli',
                        'name' => 'if',
                        'type' => 'select',
                        'wrapper' => array(
                            'width' => '20',
                        ),
                        'choices' => array(
                            'mission' => 'Misja',
                            'scena' => 'Scena',
                            'instance' => 'Instancja',
                            'relation' => 'Relacja',
                        ),
                        'default_value' => false,
                        'return_format' => 'value',
                    ),
                    array(
                        'key' => 'field_67b08fcbf1563',
                        'label' => 'Misja',
                        'name' => 'mission_select',
                        'type' => 'group',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_67b08af09dc5a',
                                    'operator' => '==',
                                    'value' => 'mission',
                                ),
                            ),
                        ),
                        'layout' => 'block',
                        'wrapper' => array(
                            'width' => '40',
                        ),
                        'sub_fields' => array(
                            array(
                                'key' => 'field_67b08ba89dc5b',
                                'label' => 'Misja',
                                'name' => 'mission',
                                'type' => 'post_object',
                                'wrapper' => array(
                                    'width' => '50',
                                ),
                                'post_type' => array(
                                    0 => 'tereny',
                                ),
                                'return_format' => 'object',
                                'ui' => 1,
                            ),
                            array(
                                'key' => 'field_67b08e96fffbe',
                                'label' => 'Jest ukończona',
                                'name' => 'is_end',
                                'type' => 'true_false',
                                'wrapper' => array(
                                    'width' => '50',
                                ),
                                'default_value' => 0,
                                'ui' => 0,
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_67b08bc79dc5c',
                        'label' => 'Scena',
                        'name' => 'scena',
                        'type' => 'text',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_67b08af09dc5a',
                                    'operator' => '==',
                                    'value' => 'scena',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '40',
                        ),
                        'prepend' => 'jest równa',
                    ),
                    array(
                        'key' => 'field_67b08c0c9dc5d',
                        'label' => 'Instancja',
                        'name' => 'instation',
                        'type' => 'text',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_67b08af09dc5a',
                                    'operator' => '==',
                                    'value' => 'instance',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '40',
                        ),
                        'prepend' => 'jest równa',
                    ),
                    array(
                        'key' => 'field_67b0901df1565',
                        'label' => 'Relacja',
                        'name' => 'relation_select',
                        'type' => 'group',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_67b08af09dc5a',
                                    'operator' => '==',
                                    'value' => 'relation',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '40',
                        ),
                        'layout' => 'block',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_67b08c2b9dc5e',
                                'label' => 'Relacja',
                                'name' => 'relacja',
                                'type' => 'post_object',
                                'wrapper' => array(
                                    'width' => '33',
                                ),
                                'post_type' => array(
                                    0 => 'npc',
                                ),
                                'return_format' => 'id',
                                'ui' => 1,
                            ),
                            array(
                                'key' => 'field_67b08c829dc5f',
                                'label' => 'Operator',
                                'name' => 'operator',
                                'type' => 'select',
                                'wrapper' => array(
                                    'width' => '33',
                                ),
                                'choices' => array(
                                    '>' => '>',
                                    '>=' => '>=',
                                    '=' => '=',
                                    '<' => '<',
                                    '=>' => '=>',
                                ),
                                'default_value' => false,
                                'return_format' => 'value',
                            ),
                            array(
                                'key' => 'field_67b08cab9dc60',
                                'label' => 'Wartość',
                                'name' => 'value',
                                'type' => 'text',
                                'wrapper' => array(
                                    'width' => '33',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_67b08bc79dcasdasw2',
                        'label' => 'To zacznij rozmowę od sceny dialogowej',
                        'name' => 'when_start_conversation',
                        'type' => 'text',
                        'wrapper' => array(
                            'width' => '40',
                        ),
                    ),
                ),
            ),

            array(
                'key'   => 'tab_avatar',
                'label' => 'Avatar',
                'type'  => 'tab',
            ),
            array(
                'key' => 'field_6794avatr_npc',
                'label' => 'Avatar',
                'name' => 'big_avatar',
                'type' => 'image',
                'return_format' => 'array',
                'library' => 'all',
                'preview_size' => 'medium',
            ),

            array(
                'key'   => 'tab_wrapper_chat',
                'label' => 'Odpowiedzi',
                'type'  => 'tab',
            ),
            array(
                'key' => 'field_wrapper_chat',
                'label' => 'Odpowiedzi',
                'name' => 'wrapper_chat',
                'type' => 'repeater',
                'button_label' => 'Dodaj konwersację',
                'instructions' => 'Dodaj możliwe odpowiedzi do tego pytania.',
                'min' => 1,
                'max' => 5,
                'layout' => 'block',
                'acfe_repeater_collapsed' => 1,
                'acfe_repeater_edit_in_modal' => 1,
                'sub_fields' => array(
                    array(
                        'key' => 'field_scena_dialogowa',
                        'label' => 'Scena dialogowa',
                        'name' => 'scena_dialogowa',
                        'type' => 'text',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_67afbe46c8395',
                        'label' => 'Konwersacje',
                        'name' => '',
                        'type' => 'accordion',
                    ),
                    array(
                        'key' => 'field_conversation',
                        'label' => 'Konwersacja',
                        'name' => 'conversation',
                        'type' => 'repeater',
                        'button_label' => 'Dodaj pytanie',
                        'instructions' => 'Dodaj pytania i odpowiedzi do konwersacji.',
                        'layout' => 'block',
                        'acfe_repeater_collapsed' => 1,
                        'acfe_repeater_edit_in_modal' => 1,
                        'sub_fields' => array(
                            array(
                                'key' => 'field_question',
                                'label' => 'Pytanie',
                                'name' => 'question',
                                'type' => 'wysiwyg',
                                'required' => 1,
                                'tabs' => 'all',
                                'toolbar' => 'basic',
                                'media_upload' => 0,
                                'delay' => 0,
                            ),
                            array(
                                'key' => 'field_answers',
                                'label' => 'Odpowiedzi',
                                'name' => 'answers',
                                'type' => 'repeater',
                                'button_label' => 'Dodaj odpowiedź',
                                'instructions' => 'Dodaj możliwe odpowiedzi do tego pytania.',
                                'min' => 1,
                                'max' => 5,
                                'layout' => 'block',
                                'acfe_repeater_collapsed' => 1,
                                'acfe_repeater_edit_in_modal' => 1,
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_answer_visibility',
                                        'label' => 'Widoczność odpowiedzi',
                                        'name' => 'answer_visibility',
                                        'type' => 'select',
                                        'choices' => array(
                                            'always' => 'Wyświetlaj zawsze',
                                            'conditional' => 'Wyświetlaj warunkowo',
                                        ),
                                        'default_value' => 'always',
                                        'return_format' => 'value',
                                    ),
                                    array(
                                        'key' => 'field_answer_conditions',
                                        'label' => 'Warunki wyświetlania',
                                        'name' => 'answer_conditions',
                                        'type' => 'repeater',
                                        'button_label' => 'Dodaj warunek',
                                        'instructions' => 'Dodaj warunki, które muszą być spełnione, by odpowiedź była widoczna.',
                                        'layout' => 'block',
                                        'acfe_repeater_collapsed' => 1,
                                        'acfe_repeater_edit_in_modal' => 1,
                                        'conditional_logic' => array(
                                            array(
                                                array(
                                                    'field' => 'field_answer_visibility',
                                                    'operator' => '==',
                                                    'value' => 'conditional',
                                                ),
                                            ),
                                        ),
                                        'sub_fields' => array(
                                            array(
                                                'key' => 'field_answer_if',
                                                'label' => 'Jeśli',
                                                'name' => 'if',
                                                'type' => 'select',
                                                'choices' => array(
                                                    'mission' => 'Misja',
                                                    'scena' => 'Scena',
                                                    'instance' => 'Instancja',
                                                    'relation' => 'Relacja',
                                                ),
                                                'default_value' => false,
                                                'return_format' => 'value',
                                                'wrapper' => array(
                                                    'width' => '30',
                                                ),
                                            ),
                                            array(
                                                'key' => 'field_answer_mission_select',
                                                'label' => 'Misja',
                                                'name' => 'mission_select',
                                                'type' => 'group',
                                                'wrapper' => array(
                                                    'width' => '70',
                                                ),
                                                'conditional_logic' => array(
                                                    array(
                                                        array(
                                                            'field' => 'field_answer_if',
                                                            'operator' => '==',
                                                            'value' => 'mission',
                                                        ),
                                                    ),
                                                ),
                                                'sub_fields' => array(
                                                    array(
                                                        'key' => 'field_answer_mission',
                                                        'label' => 'Misja',
                                                        'name' => 'mission',
                                                        'type' => 'post_object',
                                                        'post_type' => array('misja'),
                                                        'return_format' => 'object',
                                                        'ui' => 1,
                                                        'wrapper' => array(
                                                            'width' => '50',
                                                        ),
                                                    ),
                                                    array(
                                                        'key' => 'field_answer_is_end',
                                                        'label' => 'Jest ukończona',
                                                        'name' => 'is_end',
                                                        'type' => 'true_false',
                                                        'default_value' => 0,
                                                        'wrapper' => array(
                                                            'width' => '50',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'key' => 'field_answer_scena',
                                                'label' => 'Scena',
                                                'name' => 'scena',
                                                'type' => 'text',
                                                'conditional_logic' => array(
                                                    array(
                                                        array(
                                                            'field' => 'field_answer_if',
                                                            'operator' => '==',
                                                            'value' => 'scena',
                                                        ),
                                                    ),
                                                ),
                                                'wrapper' => array(
                                                    'width' => '70',
                                                ),
                                                'prepend' => 'jest równa',
                                            ),
                                            array(
                                                'key' => 'field_answer_instance',
                                                'label' => 'Instancja',
                                                'name' => 'instation',
                                                'type' => 'text',
                                                'conditional_logic' => array(
                                                    array(
                                                        array(
                                                            'field' => 'field_answer_if',
                                                            'operator' => '==',
                                                            'value' => 'instance',
                                                        ),
                                                    ),
                                                ),
                                                'wrapper' => array(
                                                    'width' => '70',
                                                ),
                                                'prepend' => 'jest równa',
                                            ),
                                            array(
                                                'key' => 'field_answer_relation_select',
                                                'label' => 'Relacja',
                                                'name' => 'relation_select',
                                                'type' => 'group',
                                                'wrapper' => array(
                                                    'width' => '70',
                                                ),
                                                'conditional_logic' => array(
                                                    array(
                                                        array(
                                                            'field' => 'field_answer_if',
                                                            'operator' => '==',
                                                            'value' => 'relation',
                                                        ),
                                                    ),
                                                ),
                                                'sub_fields' => array(
                                                    array(
                                                        'key' => 'field_answer_relacja',
                                                        'label' => 'Relacja',
                                                        'name' => 'relacja',
                                                        'type' => 'post_object',
                                                        'post_type' => array('npc'),
                                                        'return_format' => 'object',
                                                        'wrapper' => array(
                                                            'width' => '33',
                                                        ),
                                                    ),
                                                    array(
                                                        'key' => 'field_answer_operator',
                                                        'label' => 'Operator',
                                                        'name' => 'operator',
                                                        'type' => 'select',
                                                        'wrapper' => array(
                                                            'width' => '33',
                                                        ),
                                                        'choices' => array(
                                                            '>' => '>',
                                                            '>=' => '>=',
                                                            '=' => '=',
                                                            '<' => '<',
                                                            '<=' => '<=',
                                                        ),
                                                        'default_value' => '=',
                                                    ),
                                                    array(
                                                        'key' => 'field_answer_value',
                                                        'label' => 'Wartość',
                                                        'name' => 'value',
                                                        'type' => 'text',
                                                        'wrapper' => array(
                                                            'width' => '33',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                    array(
                                        'key' => 'field_answer_text',
                                        'label' => 'Tekst odpowiedzi',
                                        'name' => 'answer_text',
                                        'type' => 'text',
                                        'required' => 1,
                                        'wrapper' => array(
                                            'width' => '33',
                                        ),
                                    ),
                                    array(
                                        'key' => 'field_next_question',
                                        'label' => 'ID kolejnego pytania',
                                        'name' => 'next_question',
                                        'type' => 'number',
                                        'instructions' => 'Wpisz ID kolejnego pytania (lub 0, by zakończyć).',
                                        'wrapper' => array(
                                            'width' => '33',
                                        ),
                                    ),
                                ),
                            ),
                            array(
                                'key' => 'field_answers',
                                'label' => 'Odpowiedzi',
                                'name' => 'answers',
                                'type' => 'repeater',
                                'button_label' => 'Dodaj odpowiedź',
                                'instructions' => 'Dodaj możliwe odpowiedzi do tego pytania.',
                                'min' => 1,
                                'max' => 5,
                                'layout' => 'block',
                                'acfe_repeater_collapsed' => 1,
                                'acfe_repeater_edit_in_modal' => 1,
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_answer_text',
                                        'label' => 'Tekst odpowiedzi',
                                        'name' => 'answer_text',
                                        'type' => 'text',
                                        'required' => 1,
                                        'wrapper' => array(
                                            'width' => '33',
                                        ),
                                    ),
                                    array(
                                        'key' => 'field_next_question',
                                        'label' => 'ID kolejnego pytania',
                                        'name' => 'next_question',
                                        'type' => 'number',
                                        'instructions' => 'Wpisz ID kolejnego pytania (lub 0, by zakończyć).',
                                        'wrapper' => array(
                                            'width' => '33',
                                        ),
                                    ),
                                    array(
                                        'key' => 'field_67ab9655ea305',
                                        'label' => 'Typ pytania',
                                        'name' => 'question_type',
                                        'type' => 'select',
                                        'choices' => array(
                                            'default'    => 'Nic nie rób',
                                            'transaction' => 'Transakcja',
                                            'from_group' => 'Z grupy',
                                            'function'   => 'Funkcja',
                                        ),
                                        'default_value' => 'default',
                                        'return_format' => 'value',
                                        'wrapper' => array(
                                            'width' => '33',
                                        ),
                                    ),
                                    array(
                                        'key' => 'field_67ab9655ea305_function',
                                        'label' => 'Funkcja',
                                        'name' => 'function',
                                        'type' => 'select',
                                        'choices' => array(
                                            'default' => 'Nic nie rób',
                                            'SetClass' => 'Wybierz klasę',
                                        ),
                                        'default_value' => false,
                                        'return_format' => 'value',
                                        'multiple' => 0,
                                        'allow_null' => 0,
                                        'ui' => 0,
                                        'ajax' => 0,
                                        'parent_repeater' => 'field_67ab9613ea303',
                                        'conditional_logic' => array(
                                            array(
                                                array(
                                                    'field'    => 'field_67ab9655ea305',
                                                    'operator' => '==',
                                                    'value'    => 'function',
                                                ),
                                            ),
                                        ),
                                    ),
                                    array(
                                        'key' => 'field_67afaa9fdd159',
                                        'label' => 'Parametry funkcji',
                                        'name' => 'function_parametr',
                                        'type' => 'repeater',
                                        'layout' => 'block',
                                        'pagination' => 0,
                                        'min' => 0,
                                        'max' => 0,
                                        'collapsed' => '',
                                        'button_label' => 'Dodaj parametr',
                                        'rows_per_page' => 20,
                                        'conditional_logic' => array(
                                            array(
                                                array(
                                                    'field' => 'field_67ab9655ea305',
                                                    'operator' => '==',
                                                    'value' => 'function',
                                                ),
                                            ),
                                        ),
                                        'wrapper' => array(
                                            'width' => '50',
                                        ),
                                        'sub_fields' => array(
                                            array(
                                                'key' => 'field_67afaab1dd15a',
                                                'label' => 'Nazwa',
                                                'name' => 'name',
                                                'type' => 'text',
                                                'parent_repeater' => 'field_67afaa9fdd159',
                                            ),
                                            array(
                                                'key' => 'field_67afaac3dd15b',
                                                'label' => 'Wartość',
                                                'name' => 'value',
                                                'type' => 'text',
                                                'parent_repeater' => 'field_67afaa9fdd159',
                                            ),
                                        ),
                                    ),
                                    array(
                                        'key' => 'field_67ab975e97e31',
                                        'label' => 'Transakcja',
                                        'name' => 'transaction',
                                        'type' => 'repeater',
                                        'button_label' => 'Dodaj transakcje',
                                        'instructions' => 'Zdefiniuj elementy transakcji.',
                                        'layout' => 'block',
                                        'conditional_logic' => array(
                                            array(
                                                array(
                                                    'field'    => 'field_67ab9655ea305',
                                                    'operator' => '==',
                                                    'value'    => 'transaction',
                                                ),
                                            ),
                                        ),
                                        'sub_fields' => array(
                                            array(
                                                'key' => 'field_67ab9961cd299',
                                                'label' => 'Rodzaj',
                                                'name' => 'transaction_type',
                                                'type' => 'select',
                                                'allow_null' => 1,
                                                'required' => 1,
                                                'choices' => array(
                                                    'bag'      => 'Towar',
                                                    'relation' => 'Relacja',
                                                ),
                                                'wrapper' => array(
                                                    'width' => '33',
                                                ),
                                            ),
                                            array(
                                                'key' => 'field_67abcde123fgh',
                                                'label' => 'Relacja z',
                                                'name' => 'relation_target',
                                                'type' => 'select',
                                                'allow_null' => 1,
                                                'choices' => array(
                                                    'npc'  => 'NPC',
                                                    'user' => 'Użytkownik',
                                                ),
                                                'conditional_logic' => array(
                                                    array(
                                                        array(
                                                            'field' => 'field_67ab9961cd299',
                                                            'operator' => '==',
                                                            'value' => 'relation',
                                                        ),
                                                    ),
                                                ),
                                                'wrapper' => array(
                                                    'width' => '25',
                                                ),
                                            ),
                                            array(
                                                'key' => 'field_67ab996bagsadas',
                                                'label' => 'Towar',
                                                'name' => 'bag',
                                                'type' => 'select',
                                                'allow_null' => 1,
                                                'wrapper' => array(
                                                    'width' => '25',
                                                ),
                                                'choices' => array(
                                                    'gold'       => 'Hajs',
                                                    'papierosy'  => 'Szlugi',
                                                    'piwo'       => 'Browar',
                                                    'bimber'     => 'Bimber',
                                                    'marihuana'  => 'Marihuana',
                                                    'grzyby'     => 'Grzyby',
                                                    'klej'       => 'Klej',
                                                ),
                                                'default_value' => false,
                                                'return_format' => 'value',
                                                'conditional_logic' => array(
                                                    array(
                                                        array(
                                                            'field' => 'field_67ab9961cd299',
                                                            'operator' => '==',
                                                            'value' => 'bag',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'key' => 'field_67ab98ffcd298',
                                                'label' => 'Dodaj/zabierz',
                                                'name' => 'add_remove',
                                                'type' => 'number',
                                                'instructions' => 'Napisz ile chcesz zabrać lub dodać towaru. Minus odbiera, liczba dodatnia dodaje.',
                                                'conditional_logic' => array(
                                                    array(
                                                        array(
                                                            'field' => 'field_67ab9961cd299',
                                                            'operator' => '==',
                                                            'value' => 'bag',
                                                        ),
                                                    ),
                                                ),
                                                'wrapper' => array(
                                                    'width' => '25',
                                                ),
                                            ),
                                            array(
                                                'key' => 'field_67abcde456ijk',
                                                'label' => 'Wybierz NPC',
                                                'name' => 'target_npc',
                                                'type' => 'post_object',
                                                'return_format' => 'value',
                                                'post_type' => array('npc'),
                                                'wrapper' => array(
                                                    'width' => '50',
                                                ),
                                                'conditional_logic' => array(
                                                    array(
                                                        array(
                                                            'field' => 'field_67abcde123fgh',
                                                            'operator' => '==',
                                                            'value' => 'npc',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'key' => 'field_67abcde789lmn',
                                                'label' => 'Wybierz użytkownika',
                                                'name' => 'target_user',
                                                'type' => 'user',
                                                'wrapper' => array(
                                                    'width' => '25',
                                                ),
                                                'conditional_logic' => array(
                                                    array(
                                                        array(
                                                            'field' => 'field_67abcde123fgh',
                                                            'operator' => '==',
                                                            'value' => 'user',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'key' => 'field_67abaa789f3b3',
                                                'label' => 'Zmiana relacji',
                                                'name' => 'relation_change',
                                                'type' => 'range',
                                                'instructions' => 'Ustaw wartość dodatnią dla poprawienia relacji, ujemną dla pogorszenia.',
                                                'min' => -20,
                                                'max' => 20,
                                                'default_value' => NULL,
                                                'wrapper' => array(
                                                    'width' => '25',
                                                ),
                                                'conditional_logic' => array(
                                                    array(
                                                        array(
                                                            'field' => 'field_67ab9961cd299',
                                                            'operator' => '==',
                                                            'value' => 'relation',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
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
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'npc',
                ),
            ),
        ),
        'hide_on_screen' => array('the_content'),
        'active'        => true,
        'show_in_rest'  => 0,
    ));
}

if (function_exists('acf_add_local_field_group')) {
    $fields_relacja = array();
    foreach (get_users() as $user) {
        $fields_relacja[] = array(
            'key'           => 'npc-relation-user-' . $user->ID,
            'label'         => 'Relacja z ' . $user->user_login,
            'name'          => 'npc-relation-user-' . $user->ID,
            'type'          => 'range',
            'min'           => -100,
            'max'           => 100,
            'default_value' => 0,
        );
    }
    acf_add_local_field_group(array(
        'key' => 'group_relacja_z_graczami',
        'title' => 'relacja z graczami',
        'fields' => $fields_relacja,
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'npc',
                ),
            ),
        ),
        'hide_on_screen' => array('the_content'),
        'active'        => true,
        'show_in_rest'  => 0,
    ));
}
