<?php
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}
function display_user_stats($user_id)
{
    // Pobierz statystyki użytkownika
    $stats = get_user_stats($user_id);

    if (empty($stats)) {
        return '<p class="empty-message">Brak dostępnych statystyk.</p>';
    }

    // Pobierz punkty nauki
    $progress = get_field('progress', 'user_' . $user_id);
    $learning_points = isset($progress['learning_points']) ? $progress['learning_points'] : 0;

    // Określ które statystyki można zwiększać (wszystkie oprócz punktów nauki, życia i energii)
    $upgradeable_stats = ['strength', 'vitality_stat', 'dexterity', 'perception', 'technical', 'charisma'];

    $output = '';

    // Dodaj informację o dostępnych punktach nauki
    if ($learning_points > 0) {
        $output .= '<div class="learning-points-info">';
        $output .= '<strong>Dostępne punkty nauki:</strong> ' . esc_html($learning_points);
        $output .= '</div>';
    }

    $output .= '<div class="stats-container">';
    $output .= '<div class="stats-section">';
    $output .= '<h3>Atrybuty</h3>';
    $output .= '<div class="stats-grid">';

    // Sortuj statystyki, aby najpierw wyświetlić atrybuty, a później punkty życia i energii
    $attribute_stats = [];
    $other_stats = [];

    foreach ($stats as $key => $value) {
        if (in_array($key, $upgradeable_stats)) {
            $attribute_stats[$key] = $value;
        } else if ($key !== 'learning_points') {
            $other_stats[$key] = $value;
        }
    }

    // Wyświetl atrybuty
    foreach ($attribute_stats as $key => $value) {
        $output .= '<div class="stat-item" data-stat="' . esc_attr($key) . '">';
        $output .= '<span class="stat-label">' . esc_html($value['label']) . ':</span>';
        $output .= '<span class="stat-value">' . esc_html($value['value']) . '</span>';

        // Dodaj przycisk "plus" dla statystyk, które można zwiększyć, jeśli są dostępne punkty nauki
        if ($learning_points > 0) {
            $output .= '<button class="stat-upgrade-btn" data-stat="' . esc_attr($key) . '">+</button>';
        }

        $output .= '</div>';
    }

    // Wyświetl życie i energię
    foreach ($other_stats as $key => $value) {
        $output .= '<div class="stat-item" data-stat="' . esc_attr($key) . '">';
        $output .= '<span class="stat-label">' . esc_html($value['label']) . ':</span>';
        $output .= '<span class="stat-value">' . esc_html($value['value']) . '</span>';
        $output .= '</div>';
    }

    // Dodaj nonce dla bezpieczeństwa AJAX i ID użytkownika
    $output .= '</div>'; // .stats-grid
    $output .= '</div>'; // .stats-section
    $output .= '</div>'; // .stats-container
    $output .= '<input type="hidden" id="stats_upgrade_nonce" value="' . wp_create_nonce('stats_upgrade_nonce') . '">';
    $output .= '<input type="hidden" id="current_user_id" value="' . esc_attr($user_id) . '">';

    return $output;
}

$user_class = get_field('user_class', 'user_' . $user_id);

function get_user_stats($user_id)
{
    $stats = [];

    if (function_exists('get_field')) {
        // Pobierz punkty nauki
        $progress = get_field('progress', 'user_' . $user_id);
        $learning_points = isset($progress['learning_points']) ? $progress['learning_points'] : 0;

        $stats['learning_points'] = [
            'label' => 'Punkty nauki',
            'value' => $learning_points
        ];

        // Pobierz statystyki z pola stats (nowa struktura)
        $user_stats = get_field('stats', 'user_' . $user_id);

        if (is_array($user_stats)) {
            $stats['strength'] = [
                'label' => 'Siła',
                'value' => isset($user_stats['strength']) ? $user_stats['strength'] : 1
            ];
            $stats['vitality_stat'] = [
                'label' => 'Wytrzymałość',
                'value' => isset($user_stats['vitality']) ? $user_stats['vitality'] : 1
            ];
            $stats['dexterity'] = [
                'label' => 'Zręczność',
                'value' => isset($user_stats['dexterity']) ? $user_stats['dexterity'] : 1
            ];
            $stats['perception'] = [
                'label' => 'Percepcja',
                'value' => isset($user_stats['perception']) ? $user_stats['perception'] : 1
            ];
            $stats['technical'] = [
                'label' => 'Zdolności manualne',
                'value' => isset($user_stats['technical']) ? $user_stats['technical'] : 1
            ];
            $stats['charisma'] = [
                'label' => 'Cwaniactwo',
                'value' => isset($user_stats['charisma']) ? $user_stats['charisma'] : 1
            ];
        }

        // Pobierz dane witalności
        $vitality_data = get_field('vitality', 'user_' . $user_id);

        if (is_array($vitality_data)) {
            $stats['life'] = [
                'label' => 'Życie',
                'value' => isset($vitality_data['life']) ? $vitality_data['life'] . ' / ' . $vitality_data['max_life'] : '100 / 100'
            ];
            $stats['energy'] = [
                'label' => 'Energia',
                'value' => isset($vitality_data['energy']) ? $vitality_data['energy'] . ' / ' . $vitality_data['max_energy'] : '100 / 100'
            ];
        }
    }

    return $stats;
}

/**
 * Wyświetla umiejętności użytkownika
 */
function display_user_skills($user_id)
{
    $output = '<div class="skills-grid">';

    // Pobierz umiejętności użytkownika
    $skills = get_user_skills($user_id);

    if (empty($skills)) {
        return '<p class="empty-message">Nie posiadasz żadnych umiejętności.</p>';
    }

    foreach ($skills as $skill) {
        $output .= '<div class="skill-item">';
        if (!empty($skill['icon'])) {
            $output .= '<div class="skill-icon"><img src="' . esc_url($skill['icon']) . '" alt="' . esc_attr($skill['name']) . '"></div>';
        }
        $output .= '<div class="skill-details">';
        $output .= '<h3>' . esc_html($skill['name']) . '</h3>';
        if (!empty($skill['description'])) {
            $output .= '<p class="skill-description">' . esc_html($skill['description']) . '</p>';
        }
        if (isset($skill['level'])) {
            $output .= '<div class="skill-level">Poziom: ' . esc_html($skill['level']) . '</div>';
        }
        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
}

function get_user_skills($user_id)
{
    $skills = [];

    if (function_exists('get_field')) {
        // Pobieranie umiejętności z nowej struktury ACF
        $user_skills = get_field('skills', 'user_' . $user_id);

        if (is_array($user_skills)) {
            $skills[] = [
                'name' => 'Walka',
                'description' => 'Zwiększa obrażenia, inicjatywę',
                'level' => isset($user_skills['combat']) ? $user_skills['combat'] : 0,
                'icon' => ''
            ];

            $skills[] = [
                'name' => 'Kradzież',
                'description' => 'Większa skuteczność, mniejsze ryzyko',
                'level' => isset($user_skills['steal']) ? $user_skills['steal'] : 0,
                'icon' => ''
            ];

            $skills[] = [
                'name' => 'Produkcja',
                'description' => 'Krótszy czas, więcej towaru',
                'level' => isset($user_skills['craft']) ? $user_skills['craft'] : 0,
                'icon' => ''
            ];

            $skills[] = [
                'name' => 'Handel',
                'description' => 'Lepsze ceny, więcej zarobku',
                'level' => isset($user_skills['trade']) ? $user_skills['trade'] : 0,
                'icon' => ''
            ];

            $skills[] = [
                'name' => 'Relacje',
                'description' => 'Bonusy, unikalne misje',
                'level' => isset($user_skills['relations']) ? $user_skills['relations'] : 0,
                'icon' => ''
            ];

            $skills[] = [
                'name' => 'Uliczna wiedza',
                'description' => 'Dostęp do sekretnych przejść, schowków',
                'level' => isset($user_skills['street']) ? $user_skills['street'] : 0,
                'icon' => ''
            ];
        }

        // Zachowujemy kompatybilność ze starym systemem umiejętności
        $user_skills_relations = get_field('user_skills', 'user_' . $user_id);

        // Pobieranie umiejętności z relacji (posty typu "skill")
        if (!empty($user_skills_relations) && is_array($user_skills_relations)) {
            foreach ($user_skills_relations as $skill_post) {
                $skill_id = is_object($skill_post) && isset($skill_post->ID) ? $skill_post->ID : $skill_post;

                if ($skill_id) {
                    // Pobieramy poziom umiejętności specyficzny dla tego użytkownika
                    $skill_level = get_field('skill_level', 'user_' . $user_id . '_skill_' . $skill_id) ?: 1;

                    $skills[] = [
                        'name' => get_field('skill_name', $skill_id) ?: get_the_title($skill_id),
                        'description' => get_field('skill_description', $skill_id) ?: '',
                        'icon' => get_field('skill_icon', $skill_id),
                        'level' => $skill_level
                    ];
                }
            }
        }

        // Pobieranie umiejętności z pola repeater (starszy sposób)
        $user_skills_repeater = get_field('skills', 'user_' . $user_id);
        if (!empty($user_skills_repeater) && is_array($user_skills_repeater)) {
            foreach ($user_skills_repeater as $skill) {
                if (isset($skill['skill_reference']) && !empty($skill['skill_reference'])) {
                    // Jeśli w repeaterze jest odniesienie do postu umiejętności
                    $skill_id = $skill['skill_reference'];
                    $skills[] = [
                        'name' => get_field('skill_name', $skill_id) ?: get_the_title($skill_id),
                        'description' => get_field('skill_description', $skill_id) ?: '',
                        'icon' => get_field('skill_icon', $skill_id),
                        'level' => isset($skill['level']) ? $skill['level'] : 1
                    ];
                } else {
                    // Jeśli bezpośrednio w repeaterze
                    $skills[] = [
                        'name' => isset($skill['name']) ? $skill['name'] : 'Umiejętność',
                        'description' => isset($skill['description']) ? $skill['description'] : '',
                        'icon' => isset($skill['icon']) && is_array($skill['icon']) ? $skill['icon']['url'] : '',
                        'level' => isset($skill['level']) ? $skill['level'] : 1
                    ];
                }
            }
        }
    }

    return $skills;
}
require_once 'template.php';
