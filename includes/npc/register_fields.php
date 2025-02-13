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
        'public'        => true,
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
                'aria-label' => '',
                'type' => 'image',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'return_format' => 'array',
                'library' => 'all',
                'min_width' => '',
                'min_height' => '',
                'min_size' => '',
                'max_width' => '',
                'max_height' => '',
                'max_size' => '',
                'mime_types' => '',
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
                        'type' => 'textarea',
                        'instructions' => 'Treść pytania.',
                        'required' => 1,
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
                                'aria-label' => '',
                                'type' => 'select',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'choices' => array(
                                    'default'           => 'Nic nie rób',
                                    'bag'               => 'Plecak',
                                    'from_group'        => 'Z grupy',
                                    'relation_with_npc' => 'Relacja z NPC',
                                ),
                                'default_value' => false,
                                'return_format' => 'value',
                                'multiple'    => 0,
                                'allow_null'  => 0,
                                'ui'          => 0,
                                'ajax'        => 0,
                                'placeholder' => '',
                                'parent_repeater' => 'field_67ab9613ea303',
                            ),
                            array(
                                'key' => 'field_67abaa789f3b3',
                                'label' => 'Wskaźnik relacji',
                                'name' => 'slider_relation',
                                'aria-label' => '',
                                'type' => 'range',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field'    => 'field_67ab9655ea305',
                                            'operator' => '==',
                                            'value'    => 'relation_with_npc',
                                        ),
                                    ),
                                ),
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id'    => '',
                                ),
                                'default_value' => 0,
                                'min' => -20,
                                'max' => 20,
                                'step' => '',
                                'prepend' => '',
                                'append' => '',
                                'parent_repeater' => 'field_67ab9613ea303',
                            ),
                            array(
                                'key' => 'field_67ab975e97e31',
                                'label' => 'Transakcja',
                                'name' => 'transaction',
                                'aria-label' => '',
                                'type' => 'repeater',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field'    => 'field_67ab9655ea305',
                                            'operator' => '==',
                                            'value'    => 'bag',
                                        ),
                                    ),
                                ),
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id'    => '',
                                ),
                                'layout' => 'block',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_67ab9961cd299',
                                        'label' => 'Towar',
                                        'name' => 'bag',
                                        'aria-label' => '',
                                        'type' => 'select',
                                        'instructions' => '',
                                        'required' => 0,
                                        'conditional_logic' => 0,
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
                                        'multiple'    => 0,
                                        'allow_null'  => 0,
                                        'ui'          => 0,
                                        'ajax'        => 0,
                                        'placeholder' => '',
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
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id'    => '',
                                        ),
                                        'default_value' => '',
                                        'min' => '',
                                        'max' => '',
                                        'placeholder' => '',
                                        'step' => '',
                                        'prepend' => '',
                                        'append' => '',
                                    ),
                                ),
                                'parent_repeater' => 'field_67ab9613ea303',
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
