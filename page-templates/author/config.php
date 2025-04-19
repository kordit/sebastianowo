<?php
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

// Pobierz klasę użytkownika
$user_class = get_field('user_class', 'user_' . $user_id);

// Pobierz wszystkie dane użytkownika
$get_user_data = get_fields('user_' . $user_id);

// Pobierz punkty nauki
$progress = $get_user_data['progress'] ?? [];
$learning_points = isset($progress['learning_points']) ? $progress['learning_points'] : 0;

// Pobierz statystyki
$vitality_data = $get_user_data['vitality'] ?? [];
$stats = $get_user_data['stats'] ?? [];

// Pobierz umiejętności
$skills = $get_user_data['skills'] ?? [];

// Pobierz historię postaci
$story = $get_user_data['story'] ?? 'Brak historii postaci';

// Pobierz nick
$nick = $get_user_data['nick'] ?? $current_user->display_name;

// Dynamicznie pobierz wszystkie pola i ich definicje
$attributes_data = [];
$skills_data = [];
$additional_stats = [];

// Pobierz definicje pól dla statystyk z ACF
$stats_field_group = acf_get_field_group('group_stats');
if ($stats_field_group) {
    $stats_fields = acf_get_fields($stats_field_group['key']);
    foreach ($stats_fields as $field) {
        if (strpos($field['name'], 'stats_') === 0) {
            $key = str_replace('stats_', '', $field['name']);
            $display_key = ($key === 'vitality') ? 'vitality_stat' : $key;

            $attributes_data[$display_key] = [
                'label' => $field['label'] ?? ucfirst($key),
                'value' => isset($stats[$key]) ? intval($stats[$key]) : 0,
                'instructions' => $field['instructions'] ?? '',
            ];
        }
    }
}

// Jeśli nie znaleziono definicji pól przez ACF, użyj podstawowych (fallback)
if (empty($attributes_data)) {
    $stat_keys = ['strength', 'defense', 'dexterity', 'perception', 'technical', 'charisma'];
    foreach ($stat_keys as $key) {
        $field_object = get_field_object("stats_{$key}", "user_{$user_id}");
        $display_key = $key;

        $attributes_data[$display_key] = [
            'label' => $field_object['label'] ?? ucfirst($key),
            'value' => isset($stats[$key]) ? intval($stats[$key]) : 0,
            'instructions' => $field_object['instructions'] ?? '',
        ];
    }
}

// Pobierz definicje pól dla umiejętności z ACF
$skills_field_group = acf_get_field_group('group_skills');
if ($skills_field_group) {
    $skills_fields = acf_get_fields($skills_field_group['key']);
    foreach ($skills_fields as $field) {
        if (strpos($field['name'], 'skills_') === 0) {
            $key = str_replace('skills_', '', $field['name']);

            $skills_data[$key] = [
                'label' => $field['label'] ?? ucfirst($key),
                'value' => isset($skills[$key]) ? intval($skills[$key]) : 0,
                'instructions' => $field['instructions'] ?? '',
            ];
        }
    }
}

// Jeśli nie znaleziono definicji pól przez ACF, użyj podstawowych (fallback)
if (empty($skills_data)) {
    $skill_keys = ['combat', 'steal', 'craft', 'trade', 'relations', 'street'];
    foreach ($skill_keys as $key) {
        $field_object = get_field_object("skills_{$key}", "user_{$user_id}");

        $skills_data[$key] = [
            'label' => $field_object['label'] ?? ucfirst($key),
            'value' => isset($skills[$key]) ? intval($skills[$key]) : 0,
            'instructions' => $field_object['instructions'] ?? '',
        ];
    }
}

// Dynamicznie pobierz definicje pól dla dodatkowych statystyk (witalność)
$vitality_field_group = acf_get_field_group('group_vitality');
if ($vitality_field_group) {
    $vitality_fields = acf_get_fields($vitality_field_group['key']);
    foreach ($vitality_fields as $field) {
        if (in_array($field['name'], ['vitality_max_life', 'vitality_max_energy'])) {
            $key = str_replace('vitality_', '', $field['name']);

            $additional_stats[$key] = [
                'label' => $field['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                'value' => isset($vitality_data[$key]) ? intval($vitality_data[$key]) : 0,
                'instructions' => $field['instructions'] ?? '',
            ];
        }
    }
}

// Jeśli nie znaleziono definicji pól przez ACF, użyj podstawowych (fallback)
if (empty($additional_stats)) {
    $vitality_keys = ['max_life', 'max_energy'];
    $vitality_labels = [
        'max_life' => 'Maksymalne Życie',
        'max_energy' => 'Maksymalna energia'
    ];

    foreach ($vitality_keys as $key) {
        $field_object = get_field_object("vitality_{$key}", "user_{$user_id}");

        $additional_stats[$key] = [
            'label' => $vitality_labels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
            'value' => isset($vitality_data[$key]) ? intval($vitality_data[$key]) : 0,
            'instructions' => $field_object['instructions'] ?? '',
        ];
    }
};

require_once 'template.php';
