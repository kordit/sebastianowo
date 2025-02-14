<?php


// Rejestracja CPT "NPC"
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
                'key' => 'field_6794avatr_npc',
                'label' => 'Avatar',
                'name' => 'big_avatar',
                'type' => 'image',
                'required' => 0,
                'conditional_logic' => 0,
                'return_format' => 'array',
                'library' => 'all',
                'preview_size' => 'medium',
            ),
            array(
                'key' => 'field_conversation',
                'label' => 'Konwersacja',
                'name' => 'conversation',
                'type' => 'repeater',
                'instructions' => 'Dodaj pytania i odpowiedzi do konwersacji.',
                'min' => 0,
                'max' => 0,
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_question',
                        'label' => 'Pytanie',
                        'name' => 'question',
                        'type' => 'wysiwyg',
                        'instructions' => 'Treść pytania.',
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
                        'instructions' => 'Dodaj możliwe odpowiedzi do tego pytania.',
                        'min' => 1,
                        'max' => 5,
                        'layout' => 'table',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_answer_text',
                                'label' => 'Tekst odpowiedzi',
                                'name' => 'answer_text',
                                'type' => 'text',
                                'required' => 1,
                            ),
                            array(
                                'key' => 'field_next_question',
                                'label' => 'ID kolejnego pytania',
                                'name' => 'next_question',
                                'type' => 'number',
                                'instructions' => 'Wpisz ID kolejnego pytania (lub 0, by zakończyć).',
                            ),
                            array(
                                'key' => 'field_67ab9655ea305',
                                'label' => 'Typ pytania',
                                'name' => 'question_type',
                                'type' => 'select',
                                'choices' => array(
                                    'default'           => 'Nic nie rób',
                                    'transaction'       => 'Transakcja',
                                    'from_group'        => 'Z grupy',
                                    'function'          => 'Funkcja',
                                ),
                                'default_value' => 'default',
                                'return_format' => 'value',
                            ),
                            array(
                                'key' => 'field_67ab9655ea305_function',
                                'label' => 'Funkcja',
                                'name' => 'function',
                                'aria-label' => '',
                                'type' => 'select',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'choices' => array(
                                    'default' => 'Nic nie rób',
                                    'SetClass' => 'Wybierz klasę',
                                ),
                                'default_value' => false,
                                'return_format' => 'value',
                                'multiple'    => 0,
                                'allow_null'  => 0,
                                'ui'          => 0,
                                'ajax'        => 0,
                                'placeholder' => '',
                                'parent_repeater' => 'field_67ab9613ea303',
                                'conditional_logic' => array(
                                    array(
                                        'field'    => 'field_67ab9655ea305',
                                        'operator' => '==',
                                        'value'    => 'function',
                                    ),
                                ),
                            ),
                            array(
                                'key' => 'field_67afaa9fdd159',
                                'label' => 'Parametry funkcji',
                                'name' => 'function_parametr',
                                'aria-label' => '',
                                'type' => 'repeater',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'layout' => 'block',
                                'pagination' => 0,
                                'min' => 0,
                                'max' => 0,
                                'collapsed' => '',
                                'button_label' => 'Dodaj parametr',
                                'rows_per_page' => 20,
                                'conditional_logic' => array(
                                    array(
                                        'field'    => 'field_67ab9655ea305',
                                        'operator' => '==',
                                        'value'    => 'function',
                                    ),
                                ),
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_67afaab1dd15a',
                                        'label' => 'Nazwa',
                                        'name' => 'name',
                                        'aria-label' => '',
                                        'type' => 'text',
                                        'instructions' => '',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'default_value' => '',
                                        'maxlength' => '',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'parent_repeater' => 'field_67afaa9fdd159',
                                    ),
                                    array(
                                        'key' => 'field_67afaac3dd15b',
                                        'label' => 'Wartość',
                                        'name' => 'value',
                                        'aria-label' => '',
                                        'type' => 'text',
                                        'instructions' => '',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'default_value' => '',
                                        'maxlength' => '',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'parent_repeater' => 'field_67afaa9fdd159',
                                    ),
                                ),
                            ),
                            array(
                                'key' => 'field_67ab975e97e31',
                                'label' => 'Transakcja',
                                'name' => 'transaction',
                                'type' => 'repeater',
                                'instructions' => 'Zdefiniuj elementy transakcji.',
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field'    => 'field_67ab9655ea305',
                                            'operator' => '==',
                                            'value'    => 'transaction',
                                        ),
                                    ),
                                ),
                                'layout' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_67ab9961cd299',
                                        'label' => 'Rodzaj',
                                        'name' => 'transaction_type',
                                        'type' => 'select',
                                        'allow_null' => 1,
                                        'required' => 1,
                                        'choices' => array(
                                            'bag'    => 'Towar',
                                            'relation' => 'Relacja',
                                        ),
                                        // 'default_value' => 'bag',
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
                                    ),
                                    array(
                                        'key' => 'field_67ab996bagsadas',
                                        'label' => 'Towar',
                                        'name' => 'bag',
                                        'aria-label' => '',
                                        'type' => 'select',
                                        'instructions' => '',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'allow_null' => 1,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id'    => '',
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
                                        'aria-label' => '',
                                        'type' => 'number',
                                        'instructions' => 'Napisz ile chcesz zabrać lub dodać towaru. Minus odbiera, liczba dodatnia dodaje.',
                                        'required' => 0,
                                        'conditional_logic' => 0,
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
                                        'key' => 'field_67abcde456ijk',
                                        'label' => 'Wybierz NPC',
                                        'name' => 'target_npc',
                                        'type' => 'post_object',
                                        'return_format' => 'value',
                                        'post_type' => array('npc'),
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
                                        'min'           => -20,
                                        'max'           => 20,
                                        'default_value' => NULL,
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
// add_filter('acf/load_field/key=field_67abcde456ijk', function ($field) {
//     if (isset($_GET['post']) && is_admin()) {
//         $field['default_value'] = intval($_GET['post']);
//     }
//     return $field;
// });



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
