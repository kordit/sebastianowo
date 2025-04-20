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
                    'width' => '50%',
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
                'key' => 'field_6794b4c86bae4_full',
                'label' => 'Avatar Full',
                'name' => 'avatar_full',
                'aria-label' => '',
                'type' => 'image',
                'instructions' => 'Pełny obrazek postaci',
                'required' => 0,
                'conditional_logic' => 0,
                'return_format' => 'array',
                'library' => 'all',
                'wrapper' => array(
                    'width' => '50%',
                    'class' => '',
                    'id' => '',
                ),

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
                        'key' => 'field_stats_defense',
                        'label' => 'Wytrzymałość',
                        'name' => 'defense',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => 'Zwiększa liczbę ciosów jakie mozemy przyjąć',
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
                        'label' => 'Zadania misji',
                        'name' => 'progress',
                        'type' => 'repeater',
                        'instructions' => 'Postęp w zadaniach misji',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'layout' => 'table',
                        'button_label' => '',
                        'min' => 0,
                        'max' => 0,
                        'sub_fields' => array(
                            array(
                                'key' => 'field_mission_task_id',
                                'label' => 'ID zadania',
                                'name' => 'task_id',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 0,
                                'wrapper' => array(
                                    'width' => '30',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'readonly' => 1,
                            ),
                            array(
                                'key' => 'field_mission_task_completed',
                                'label' => 'Ukończone',
                                'name' => 'completed',
                                'type' => 'true_false',
                                'instructions' => '',
                                'required' => 0,
                                'wrapper' => array(
                                    'width' => '70',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'message' => '',
                                'default_value' => 0,
                                'ui' => 1,
                            ),
                        ),
                    ),
                ),
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

            // PRZEDMIOTY ZAŁOŻONE PRZEZ GRACZA
            array(
                'key' => 'field_equipped_items_group',
                'label' => 'Założone przedmioty',
                'name' => 'equipped_items',
                'aria-label' => '',
                'type' => 'group',
                'instructions' => 'Przedmioty aktualnie założone przez gracza',
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
                        'key' => 'field_equipped_item_chest',
                        'label' => 'Na klatę',
                        'name' => 'chest_item',
                        'aria-label' => '',
                        'type' => 'post_object',
                        'instructions' => 'Przedmiot założony na klatę',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'post_type' => array(
                            0 => 'item',
                        ),
                        'taxonomy' => array(
                            0 => 'item_type:3',
                        ),
                        'allow_null' => 1,
                        'multiple' => 0,
                        'return_format' => 'object',
                        'ui' => 1,
                    ),
                    array(
                        'key' => 'field_equipped_item_bottom',
                        'label' => 'Na poślady',
                        'name' => 'bottom_item',
                        'aria-label' => '',
                        'type' => 'post_object',
                        'instructions' => 'Przedmiot założony na poślady',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'post_type' => array(
                            0 => 'item',
                        ),
                        'taxonomy' => array(
                            0 => 'item_type:4',
                        ),
                        'allow_null' => 1,
                        'multiple' => 0,
                        'return_format' => 'object',
                        'ui' => 1,
                    ),
                    array(
                        'key' => 'field_equipped_item_legs',
                        'label' => 'Na giczuły',
                        'name' => 'legs_item',
                        'aria-label' => '',
                        'type' => 'post_object',
                        'instructions' => 'Przedmiot założony na giczuły',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'post_type' => array(
                            0 => 'item',
                        ),
                        'taxonomy' => array(
                            0 => 'item_type:7',
                        ),
                        'allow_null' => 1,
                        'multiple' => 0,
                        'return_format' => 'object',
                        'ui' => 1,
                    ),
                ),
            ),

            // PRZEDMIOTY GRACZA
            array(
                'key' => 'field_items_inventory',
                'label' => 'Przedmioty',
                'name' => 'items',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => 'Lista przedmiotów posiadanych przez gracza',
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
                'button_label' => 'Dodaj przedmiot',
                'sub_fields' => array(
                    array(
                        'key' => 'field_item_relation',
                        'label' => 'Przedmiot',
                        'name' => 'item',
                        'aria-label' => '',
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
                            0 => 'item',
                        ),
                        'taxonomy' => '',
                        'allow_null' => 0,
                        'multiple' => 0,
                        'return_format' => 'object',
                        'ui' => 1,
                    ),
                    array(
                        'key' => 'field_item_quantity',
                        'label' => 'Ilość',
                        'name' => 'quantity',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '1',
                        'min' => 1,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '1',
                        'prepend' => '',
                        'append' => '',
                    ),
                ),
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


function get_mission_tasks($mission_id)
{
    $choices = array();
    $tasks = get_field('tasks', $mission_id);

    if (is_array($tasks) && !empty($tasks)) {
        foreach ($tasks as $index => $task) {
            // Używam indeksu zadania jako klucza
            $task_key = 'task_' . $index;

            // Tworzymy etykietę na podstawie tytułu zadania
            $task_label = '';
            if (!empty($task['title'])) {
                $task_label = $task['title'];
            } else if (!empty($task['description'])) {
                // Fallback na opis jeśli tytuł jest pusty
                $description = strip_tags($task['description']);
                $task_label = (strlen($description) > 40) ?
                    substr($description, 0, 40) . '...' :
                    $description;
            } else {
                $task_label = 'Zadanie ' . ($index + 1);
            }

            $choices[$task_key] = $task_label;
        }
    }

    return $choices;
}

// Dodajemy również akcję dla zapisywania
add_action('acf/save_post', 'update_mission_tasks_in_repeater', 10);

function update_mission_tasks_in_repeater($post_id)
{
    // Sprawdzamy czy edytujemy użytkownika
    if (strpos($post_id, 'user_') !== 0) {
        return;
    }

    // Pobieramy aktywne misje użytkownika
    $active_missions = get_field('active_missions', $post_id);

    if (empty($active_missions) || !is_array($active_missions)) {
        return;
    }

    $updated = false;

    // Dla każdej aktywnej misji
    foreach ($active_missions as $key => $mission_data) {
        // Jeśli mamy obiekt misji
        if (empty($mission_data['mission'])) {
            continue;
        }

        // Pobieramy ID misji
        $mission = $mission_data['mission'];
        if (is_object($mission)) {
            $mission_id = $mission->ID;
        } elseif (is_numeric($mission)) {
            $mission_id = intval($mission);
        } else {
            continue;
        }

        // Pobieramy zadania dla tej misji
        $mission_tasks = get_mission_tasks($mission_id);

        if (empty($mission_tasks)) {
            continue;
        }

        // Sprawdzamy, czy aktualne zadania (progress) odzwierciedlają zadania misji
        // Jeśli nie, aktualizujemy

        $current_progress = $mission_data['progress'] ?? array();
        $missing_tasks = false;
        $task_ids = array();

        // Budujemy tablicę z wszystkimi zadaniami, które powinny być w repeaterze
        $new_progress = array();

        foreach ($mission_tasks as $task_id => $task_title) {
            // Sprawdzamy czy to zadanie już istnieje w progress
            $task_exists = false;
            $completed = false;

            if (is_array($current_progress)) {
                foreach ($current_progress as $progress_item) {
                    if (isset($progress_item['task_id']) && $progress_item['task_id'] === $task_id) {
                        $task_exists = true;
                        $completed = !empty($progress_item['completed']);
                        break;
                    }
                }
            }

            // Dodajemy zadanie do nowej tablicy, zachowując status ukończenia jeśli istnieje
            $new_progress[] = array(
                'task_id' => $task_id,
                'task_title' => $task_title,
                'completed' => $completed ? 1 : 0
            );

            if (!$task_exists) {
                $missing_tasks = true;
            }
        }

        // Jeśli brakuje zadań, aktualizujemy progress
        if ($missing_tasks || count($new_progress) != count($current_progress)) {
            $active_missions[$key]['progress'] = $new_progress;
            $updated = true;
        }
    }

    // Jeśli dokonaliśmy zmian, aktualizujemy pole
    if ($updated) {
        update_field('active_missions', $active_missions, $post_id);
    }
}
