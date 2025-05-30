<?php

/**
 * Action Badges Component
 * Generuje miniaturki akcji dla odpowiedzi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generuje miniaturki akcji dla odpowiedzi
 */
function generate_action_badges($actions)
{
    if (empty($actions)) {
        return '';
    }

    // Jeśli akcje są stringiem JSON, sparsuj je
    if (is_string($actions)) {
        $actions = json_decode($actions, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }
    }

    if (!is_array($actions) || empty($actions)) {
        return '';
    }

    $badges = [];
    $action_types = get_action_types_config();

    foreach ($actions as $action) {
        if (!isset($action['type']) || !isset($action_types[$action['type']])) {
            continue;
        }

        $type = $action['type'];
        $config = $action_types[$type];
        $params = $action['params'] ?? [];

        $badge = generate_single_action_badge($type, $config, $params);
        if (!empty($badge)) {
            $badges[] = $badge;
        }
    }

    if (empty($badges)) {
        return '';
    }

    return '<div class="action-badges">' . implode('', $badges) . '</div>';
}

/**
 * Generuje pojedynczą miniaturkę akcji
 */
function generate_single_action_badge($type, $config, $params)
{
    $label = $config['label'] ?? '';
    $description = '';
    $icon = get_action_icon($type);
    $color_class = get_action_color_class($type);

    // Generuj opis na podstawie parametrów
    switch ($type) {
        case 'modify_gold':
            $amount = $params['amount'] ?? 0;
            if ($amount > 0) {
                $description = "+{$amount} złota";
            } elseif ($amount < 0) {
                $description = "{$amount} złota";
            } else {
                $description = "0 złota";
            }
            break;

        case 'modify_cigarettes':
            $amount = $params['amount'] ?? 0;
            if ($amount > 0) {
                $description = "+{$amount} papierosów";
            } elseif ($amount < 0) {
                $description = "{$amount} papierosów";
            } else {
                $description = "0 papierosów";
            }
            break;

        case 'modify_stat':
            $stat = $params['stat'] ?? '';
            $amount = $params['amount'] ?? 0;
            $stat_name = get_stat_name($stat);
            if ($amount > 0) {
                $description = "+{$amount} {$stat_name}";
            } elseif ($amount < 0) {
                $description = "{$amount} {$stat_name}";
            } else {
                $description = "0 {$stat_name}";
            }
            break;

        case 'modify_skill':
            $skill = $params['skill'] ?? '';
            $amount = $params['amount'] ?? 0;
            $skill_name = get_skill_name($skill);
            if ($amount > 0) {
                $description = "+{$amount} {$skill_name}";
            } elseif ($amount < 0) {
                $description = "{$amount} {$skill_name}";
            } else {
                $description = "0 {$skill_name}";
            }
            break;

        case 'modify_exp':
            $amount = $params['amount'] ?? 0;
            if ($amount > 0) {
                $description = "+{$amount} EXP";
            } elseif ($amount < 0) {
                $description = "{$amount} EXP";
            } else {
                $description = "0 EXP";
            }
            break;

        case 'modify_reputation':
            $amount = $params['amount'] ?? 0;
            if ($amount > 0) {
                $description = "+{$amount} rep.";
            } elseif ($amount < 0) {
                $description = "{$amount} rep.";
            } else {
                $description = "0 rep.";
            }
            break;

        case 'start_combat':
            $enemy_id = $params['enemy_id'] ?? '';
            $combat_type = $params['combat_type'] ?? 'normal';
            if ($enemy_id) {
                $description = "Walka ({$combat_type})";
            } else {
                $description = "Walka";
            }
            break;

        case 'modify_items':
            $item_id = $params['item_id'] ?? '';
            $amount = $params['amount'] ?? 1;
            if ($item_id) {
                $item_name = get_item_name($item_id);
                if ($amount > 0) {
                    $description = "+{$amount} {$item_name}";
                } elseif ($amount < 0) {
                    $description = "{$amount} {$item_name}";
                } else {
                    $description = "0 {$item_name}";
                }
            } else {
                $description = "Przedmiot";
            }
            break;

        case 'change_location':
            $location_id = $params['location_id'] ?? '';
            if ($location_id) {
                $location_name = get_location_name($location_id);
                $description = "→ {$location_name}";
            } else {
                $description = "Zmiana lokalizacji";
            }
            break;

        case 'unlock_location':
            $location_id = $params['location_id'] ?? '';
            if ($location_id) {
                $location_name = get_location_name($location_id);
                $description = "🔓 {$location_name}";
            } else {
                $description = "Odblokuj lokalizację";
            }
            break;

        default:
            $description = $label;
            break;
    }

    if (empty($description)) {
        return '';
    }

    return sprintf(
        '<span class="action-badge %s" title="%s"><span class="action-icon">%s</span><span class="action-text">%s</span></span>',
        esc_attr($color_class),
        esc_attr($config['description'] ?? ''),
        $icon,
        esc_html($description)
    );
}

/**
 * Pobiera konfigurację typów akcji
 */
function get_action_types_config()
{
    return [
        'modify_gold' => [
            'label' => 'złoto',
            'description' => 'Dodaje lub odejmuje złoto graczowi'
        ],
        'modify_cigarettes' => [
            'label' => 'papierosy',
            'description' => 'Dodaje lub odejmuje papierosy graczowi'
        ],
        'modify_stat' => [
            'label' => 'statystyka',
            'description' => 'Modyfikuje wartość wybranej statystyki'
        ],
        'modify_skill' => [
            'label' => 'umiejętność',
            'description' => 'Modyfikuje wartość wybranej umiejętności'
        ],
        'modify_exp' => [
            'label' => 'doświadczenie',
            'description' => 'Dodaje lub odejmuje punkty doświadczenia'
        ],
        'modify_reputation' => [
            'label' => 'reputacja',
            'description' => 'Dodaje lub odejmuje punkty reputacji'
        ],
        'start_combat' => [
            'label' => 'walka',
            'description' => 'Rozpoczyna walkę z wybranym przeciwnikiem'
        ],
        'modify_items' => [
            'label' => 'przedmiot',
            'description' => 'Dodaje lub odejmuje przedmiot graczowi'
        ],
        'change_location' => [
            'label' => 'zmiana lokalizacji',
            'description' => 'Przenosi gracza do wybranej lokalizacji'
        ],
        'unlock_location' => [
            'label' => 'odblokowanie lokalizacji',
            'description' => 'Odblokowuje dostęp do lokalizacji'
        ]
    ];
}

/**
 * Pobiera ikonę dla typu akcji
 */
function get_action_icon($type)
{
    $icons = [
        'modify_gold' => '💰',
        'modify_cigarettes' => '🚬',
        'modify_stat' => '📊',
        'modify_skill' => '🎯',
        'modify_exp' => '⭐',
        'modify_reputation' => '👑',
        'start_combat' => '⚔️',
        'modify_items' => '📦',
        'change_location' => '🚪',
        'unlock_location' => '🔓'
    ];

    return $icons[$type] ?? '🔧';
}

/**
 * Pobiera klasę koloru dla typu akcji
 */
function get_action_color_class($type)
{
    $classes = [
        'modify_gold' => 'action-gold',
        'modify_cigarettes' => 'action-cigarettes',
        'modify_stat' => 'action-stat',
        'modify_skill' => 'action-skill',
        'modify_exp' => 'action-exp',
        'modify_reputation' => 'action-reputation',
        'start_combat' => 'action-combat',
        'modify_items' => 'action-items',
        'change_location' => 'action-location',
        'unlock_location' => 'action-unlock'
    ];

    return $classes[$type] ?? 'action-default';
}

/**
 * Pobiera nazwę statystyki
 */
function get_stat_name($stat)
{
    $stats = [
        'strength' => 'Siła',
        'defence' => 'Obrona',
        'dexterity' => 'Zręczność',
        'perception' => 'Percepcja',
        'technical' => 'Technika',
        'charisma' => 'Charyzma'
    ];

    return $stats[$stat] ?? $stat;
}

/**
 * Pobiera nazwę umiejętności
 */
function get_skill_name($skill)
{
    $skills = [
        'combat' => 'Walka',
        'steal' => 'Kradzież',
        'craft' => 'Rzemiosło',
        'trade' => 'Handel',
        'relations' => 'Relacje',
        'street' => 'Ulica'
    ];

    return $skills[$skill] ?? $skill;
}

/**
 * Pobiera nazwę przedmiotu
 */
function get_item_name($item_id)
{
    // W przyszłości można to rozszerzyć o rzeczywiste pobieranie nazw z bazy danych
    return "Przedmiot #{$item_id}";
}

/**
 * Pobiera nazwę lokalizacji
 */
function get_location_name($location_id)
{
    // W przyszłości można to rozszerzyć o rzeczywiste pobieranie nazw z bazy danych
    return "Lokalizacja #{$location_id}";
}
