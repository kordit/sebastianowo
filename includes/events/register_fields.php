<?php
// Rejestracja Custom Post Type: Zdarzenia
add_action('init', function () {
    register_post_type('events', array(
        'labels' => array(
            'name'                  => 'Zdarzenia',
            'singular_name'         => 'Zdarzenie',
            'menu_name'             => 'Zdarzenia',
            'all_items'             => 'Wszystkie zdarzenia',
            'edit_item'             => 'Edytuj zdarzenie',
            'view_item'             => 'Zobacz zdarzenie',
            'add_new_item'          => 'Dodaj nowe zdarzenie',
            'add_new'               => 'Dodaj nowe',
            'new_item'              => 'Nowe zdarzenie',
            'search_items'          => 'Szukaj zdarzeń',
            'not_found'             => 'Nie znaleziono żadnych zdarzeń',
            'not_found_in_trash'    => 'Nie znaleziono żadnych zdarzeń w koszu',
        ),
        'public'              => true,
        'has_archive'         => true,
        'rewrite'             => array('slug' => 'zdarzenia'),
        'menu_icon'           => 'dashicons-calendar-alt',
        'supports'            => array('title', 'thumbnail'),
        'show_in_rest'        => false,
    ));
});

// Rejestracja ACF Field Group dla CPT "events"
add_action('acf/include_fields', function () {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key'                   => 'events_fields',
        'title'                 => 'Dodatkowe informacje o zdarzeniu',
        'fields'                => array(
            array(
                'key'           => 'field_content_wysiwyg',
                'label'         => 'Treść',
                'name'          => 'content_wysiwyg',
                'type'          => 'wysiwyg',
                'instructions'  => 'Wpisz szczegóły zdarzenia.',
                'required'      => 0,
            ),
            array(
                'key'           => 'field_repeater_changes',
                'label'         => 'Zmiany zasobów',
                'name'          => 'resource_changes',
                'type'          => 'repeater',
                'instructions'  => 'Dodaj zmiany zasobów dla zdarzenia.',
                'sub_fields'    => array(
                    array(
                        'key'       => 'field_resource_type',
                        'label'     => 'Rodzaj zasobu',
                        'name'      => 'resource_type',
                        'type'      => 'select',
                        'choices'   => array(
                            'gold'      => 'Złote',
                            'papierosy' => 'Papierosy',
                            'piwo'      => 'Piwo',
                            'bimber'    => 'Bimber',
                            'marihuana' => 'Marihuana',
                            'grzyby'    => 'Grzyby',
                            'klej'      => 'Klej',
                        ),
                    ),
                    array(
                        'key'       => 'field_resource_amount',
                        'label'     => 'Ilość',
                        'name'      => 'resource_amount',
                        'type'      => 'number',
                        'default_value' => 0,
                    ),
                ),
            ),
            array(
                'key'           => 'field_repeater_stats',
                'label'         => 'Statystyki',
                'name'          => 'stats',
                'type'          => 'repeater',
                'instructions'  => 'Zarządzaj statystykami gracza.',
                'sub_fields'    => array(
                    array(
                        'key'       => 'field_stat_type',
                        'label'     => 'Typ statystyki',
                        'name'      => 'stat_type',
                        'type'      => 'select',
                        'choices'   => array(
                            'life'        => 'Życie',
                            'max_life'    => 'Maksymalne życie',
                            'energy'      => 'Energia',
                            'max_energy'  => 'Maksymalna energia',
                        ),
                    ),
                    array(
                        'key'       => 'field_stat_value',
                        'label'     => 'Wartość',
                        'name'      => 'stat_value',
                        'type'      => 'number',
                        'default_value' => 0,
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'     => 'post_type',
                    'operator'  => '==',
                    'value'     => 'events',
                ),
            ),
        ),
        'active' => true,
    ));
});
