<?php
add_action('acf/include_fields', function () {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_6793e69170911',
        'title' => 'User',
        'fields' => array(
            // PODSTAWOWE INFORMACJE
            array(
                'key' => 'field_679a2b8sfnick',
                'label' => 'Nick',
                'name' => 'nick',
                'aria-label' => '',
                'type' => 'text',
                'instructions' => 'Nazwa gracza',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
            ),
            array(
                'key' => 'field_6794b4c86bae4',
                'label' => 'Avatar',
                'name' => 'avatar',
                'aria-label' => '',
                'type' => 'image',
                'instructions' => 'Obrazek postaci',
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
                'key' => 'field_679a2b8ab83a2',
                'label' => 'Story',
                'name' => 'story',
                'aria-label' => '',
                'type' => 'wysiwyg',
                'instructions' => 'Tło fabularne postaci',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 0,
                'delay' => 0,
            ),
            array(
                'key' => 'field_679a2d06adef9',
                'label' => 'Klasa',
                'name' => 'user_class',
                'aria-label' => '',
                'type' => 'select',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'choices' => array(
                    'zadymiarz' => 'Zadymiarz',
                    'zawijacz' => 'Zawijacz',
                    'kombinator' => 'Kombinator',
                ),
                'default_value' => false,
                'return_format' => 'array',
                'multiple' => 0,
                'allow_null' => 1,
                'ui' => 0,
                'ajax' => 0,
                'placeholder' => '',
            ),
            array(
                'key' => 'field_679a251fed9a5',
                'label' => 'Kreator ukończony?',
                'name' => 'creator_end',
                'aria-label' => '',
                'type' => 'true_false',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'message' => '',
                'default_value' => 0,
                'ui' => 0,
                'ui_on_text' => '',
                'ui_off_text' => '',
            ),

            // STATYSTYKI
            array(
                'key' => 'field_6793e6cb83c38',
                'label' => 'Statystyki',
                'name' => 'stats',
                'aria-label' => '',
                'type' => 'group',
                'instructions' => 'Podstawowe statystyki gracza',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_stats_strength',
                        'label' => 'Siła',
                        'name' => 'strength',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Zwiększa obrażenia w walce wręcz i dominację w bójkach',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '1',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_stats_vitality',
                        'label' => 'Wytrzymałość',
                        'name' => 'vitality',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Zwiększa maksymalne życie i odporność na używki, obrażenia',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '1',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_stats_dexterity',
                        'label' => 'Zręczność',
                        'name' => 'dexterity',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Odpowiada za skuteczność kradzieży, uniki, ucieczki',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '1',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_stats_perception',
                        'label' => 'Percepcja',
                        'name' => 'perception',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Szansa na wykrycie ukrytych przedmiotów, NPC, opcji',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '1',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_stats_technical',
                        'label' => 'Zdolności manualne',
                        'name' => 'technical',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Efektywność produkcji (używki, towary), hakowanie, techniczne akcje',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '1',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_stats_charisma',
                        'label' => 'Cwaniactwo',
                        'name' => 'charisma',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Umiejętność bajerowania NPC, prowadzenia układów, handlu',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '1',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                ),
            ),

            // UMIEJĘTNOŚCI
            array(
                'key' => 'field_skills_group',
                'label' => 'Umiejętności',
                'name' => 'skills',
                'aria-label' => '',
                'type' => 'group',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_skills_combat',
                        'label' => 'Walka',
                        'name' => 'combat',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Zwiększa obrażenia, inicjatywę',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_skills_steal',
                        'label' => 'Kradzież',
                        'name' => 'steal',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Większa skuteczność, mniejsze ryzyko',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_skills_craft',
                        'label' => 'Produkcja',
                        'name' => 'craft',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Krótszy czas, więcej towaru',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_skills_trade',
                        'label' => 'Handel',
                        'name' => 'trade',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Lepsze ceny, więcej zarobku',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_skills_relations',
                        'label' => 'Relacje',
                        'name' => 'relations',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Bonusy, unikalne misje',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_skills_street',
                        'label' => 'Uliczna wiedza',
                        'name' => 'street',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Dostęp do sekretnych przejść, schowków',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                ),
            ),

            // PLECAK
            array(
                'key' => 'field_6793e69183c31',
                'label' => 'Plecak',
                'name' => 'backpack',
                'aria-label' => '',
                'type' => 'group',
                'instructions' => 'Kontener na przedmioty gracza',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_6793e6a783c33',
                        'label' => 'Złote',
                        'name' => 'gold',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Główna waluta',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e69b83c32',
                        'label' => 'Papierosy',
                        'name' => 'cigarettes',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Alternatywna waluta',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6bc83c36',
                        'label' => 'Piwo',
                        'name' => 'beer',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Używka',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6c283c37',
                        'label' => 'Bimber',
                        'name' => 'moonshine',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Mocna używka',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6c283c37a1',
                        'label' => 'Zioło',
                        'name' => 'weed',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Marihuana – wpływa na staty/efekty',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6c283c37a2',
                        'label' => 'Grzyby',
                        'name' => 'mushrooms',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Halucynogen – boost z ryzykiem',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6c283c37a3',
                        'label' => 'Klej',
                        'name' => 'glue',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Tania używka – obniża reputację',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                ),
            ),

            // WITALNOŚĆ
            array(
                'key' => 'field_vitality_group',
                'label' => 'Witalność',
                'name' => 'vitality',
                'aria-label' => '',
                'type' => 'group',
                'instructions' => 'Kontener na witalność gracza',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_6793e6cb83c39',
                        'label' => 'Życie',
                        'name' => 'life',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Aktualne życie gracza',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '100',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6cb83c3a',
                        'label' => 'Maksymalne życie',
                        'name' => 'max_life',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Limit życia',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '100',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6cb83c3b',
                        'label' => 'Energia',
                        'name' => 'energy',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Aktualna energia',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '100',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6cb83c3b_max',
                        'label' => 'Maksymalna energia',
                        'name' => 'max_energy',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Limit energii',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '100',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                ),
            ),

            // POSTĘP
            array(
                'key' => 'field_progress_group',
                'label' => 'Postęp',
                'name' => 'progress',
                'aria-label' => '',
                'type' => 'group',
                'instructions' => 'Kontener na progress gracza',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_progress_exp',
                        'label' => 'Doświadczenie',
                        'name' => 'exp',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Całkowity exp zdobyty przez gracza',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_progress_learning_points',
                        'label' => 'Punkty nauki',
                        'name' => 'learning_points',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Punkty do rozdania na statystyki',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '3',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_progress_reputation',
                        'label' => 'Reputacja',
                        'name' => 'reputation',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Reputacja gracza w mieście',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '1',
                        'min' => -100,
                        'max' => 100,
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                ),
            ),

            // MISJE
            array(
                'key' => 'field_missions_active',
                'label' => 'Aktywne misje',
                'name' => 'active_missions',
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
                'layout' => 'table',
                'min' => 0,
                'max' => '',
                'collapsed' => '',
                'button_label' => 'Dodaj misję',
                'sub_fields' => array(
                    array(
                        'key' => 'field_mission_relation',
                        'label' => 'Misja',
                        'name' => 'mission',
                        'type' => 'post_object',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'post_type' => array(
                            0 => 'mission',
                        ),
                        'taxonomy' => '',
                        'allow_null' => 0,
                        'multiple' => 0,
                        'return_format' => 'object',
                        'ui' => 1,
                    ),
                    array(
                        'key' => 'field_mission_progress',
                        'label' => 'Progres misji',
                        'name' => 'progress',
                        'type' => 'checkbox',
                        'instructions' => 'Zaznacz ukończone kroki misji',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'choices' => array(
                            'step1' => 'Krok 1',
                            'step2' => 'Krok 2',
                            'step3' => 'Krok 3',
                            'step4' => 'Krok 4',
                            'step5' => 'Krok 5',
                        ),
                        'allow_custom' => 0,
                        'save_custom' => 0,
                        'default_value' => array(),
                        'layout' => 'vertical',
                        'toggle' => 0,
                        'return_format' => 'value',
                    ),
                ),
            ),

            array(
                'key' => 'field_mission_current',
                'label' => 'Aktualna misja',
                'name' => 'active_mission',
                'aria-label' => '',
                'type' => 'post_object',
                'instructions' => 'Aktualnie wykonywana misja',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'post_type' => array(
                    0 => 'mission',
                ),
                'taxonomy' => '',
                'allow_null' => 1,
                'multiple' => 0,
                'return_format' => 'object',
                'ui' => 1,
            ),

            array(
                'key' => 'field_missions_completed',
                'label' => 'Ukończone misje',
                'name' => 'completed_missions',
                'aria-label' => '',
                'type' => 'relationship',
                'instructions' => 'Lista ukończonych misji (dla historii i blokad powtórzeń)',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'post_type' => array(
                    0 => 'mission',
                ),
                'taxonomy' => '',
                'filters' => array(
                    0 => 'search',
                    1 => 'post_type',
                    2 => 'taxonomy',
                ),
                'elements' => '',
                'min' => '',
                'max' => '',
                'return_format' => 'object',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'user_role',
                    'operator' => '==',
                    'value' => 'all',
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
});
