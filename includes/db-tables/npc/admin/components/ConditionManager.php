<?php

/**
 * Komponent zarządzania warunkami wyświetlania
 * Pozwala na konfigurację warunków dla dialogów i odpowiedzi
 */

if (!defined('ABSPATH')) {
    exit;
}

class NPC_ConditionManager
{
    /**
     * Renderuje interfejs zarządzania warunkami
     */
    public static function render_conditions_ui($conditions = null, $context = 'dialog')
    {
        $conditions_data = $conditions ? json_decode($conditions, true) : [];
        $field_prefix = $context . '_conditions';
?>
        <div class="conditions-manager" data-context="<?php echo esc_attr($context); ?>">
            <div class="conditions-header">
                <h4>Warunki wyświetlania</h4>
                <button type="button" class="button button-secondary add-condition-btn">
                    Dodaj warunek
                </button>
            </div>

            <div class="conditions-list">
                <?php if (!empty($conditions_data)): ?>
                    <?php foreach ($conditions_data as $index => $condition): ?>
                        <?php self::render_single_condition($condition, $field_prefix, $index); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-conditions">
                        <p>Brak warunków. Element będzie zawsze widoczny.</p>
                    </div>
                <?php endif; ?>
            </div>

            <input type="hidden" name="<?php echo $field_prefix; ?>" class="conditions-data"
                value="<?php echo esc_attr($conditions ?: '[]'); ?>">
        </div>

        <!-- Template dla nowego warunku -->
        <script type="text/template" id="condition-template">
            <?php self::render_condition_template($field_prefix); ?>
        </script>
    <?php
    }

    /**
     * Renderuje pojedynczy warunek
     */
    private static function render_single_condition($condition, $field_prefix, $index)
    {
    ?>
        <div class="condition-item" data-index="<?php echo $index; ?>">
            <div class="condition-header">
                <span class="condition-number"><?php echo $index + 1; ?>.</span>
                <select class="condition-type" name="temp_type">
                    <?php self::render_condition_type_options($condition['type'] ?? ''); ?>
                </select>
                <button type="button" class="button-link delete-condition">Usuń</button>
            </div>

            <div class="condition-body">
                <?php self::render_condition_fields($condition); ?>
            </div>
        </div>
    <?php
    }

    /**
     * Renderuje template dla nowego warunku
     */
    private static function render_condition_template($field_prefix)
    {
    ?>
        <div class="condition-item" data-index="{{INDEX}}">
            <div class="condition-header">
                <span class="condition-number">{{NUMBER}}.</span>
                <select class="condition-type" name="temp_type">
                    <?php self::render_condition_type_options(); ?>
                </select>
                <button type="button" class="button-link delete-condition">Usuń</button>
            </div>

            <div class="condition-body">
                <?php self::render_condition_fields_template(); ?>
            </div>
        </div>
    <?php
    }

    /**
     * Renderuje opcje typów warunków
     */
    private static function render_condition_type_options($selected = '')
    {
        $types = [
            '' => 'Wybierz typ warunku...',
            'user_level' => 'Poziom gracza',
            'user_gold' => 'Złoto gracza',
            'user_item' => 'Przedmiot gracza',
            'quest_completed' => 'Ukończone zadanie',
            'user_stat' => 'Statystyka gracza',
            'time_of_day' => 'Pora dnia',
            'custom' => 'Warunek niestandardowy'
        ];

        foreach ($types as $value => $label) {
            $selected_attr = selected($selected, $value, false);
            echo "<option value=\"{$value}\" {$selected_attr}>{$label}</option>";
        }
    }

    /**
     * Renderuje pola warunku
     */
    private static function render_condition_fields($condition = [])
    {
        $type = $condition['type'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? '';
        $field = $condition['field'] ?? '';
    ?>
        <div class="condition-fields">
            <!-- Operator -->
            <div class="field-group operator-group" style="<?php echo $type ? '' : 'display: none;'; ?>">
                <label>Operator:</label>
                <select class="condition-operator">
                    <?php self::render_operator_options($operator, $type); ?>
                </select>
            </div>

            <!-- Wartość -->
            <div class="field-group value-group" style="<?php echo $type ? '' : 'display: none;'; ?>">
                <label>Wartość:</label>
                <?php self::render_value_field($type, $value); ?>
            </div>

            <!-- Pole dodatkowe (dla statystyk) -->
            <div class="field-group field-group-extra" style="<?php echo $type === 'user_stat' ? '' : 'display: none;'; ?>">
                <label>Nazwa statystyki:</label>
                <input type="text" class="condition-field" value="<?php echo esc_attr($field); ?>"
                    placeholder="np. strength, agility">
            </div>

            <!-- Opis warunku -->
            <div class="condition-description">
                <small class="condition-help"></small>
            </div>
        </div>
    <?php
    }

    /**
     * Renderuje template pól warunku
     */
    private static function render_condition_fields_template()
    {
    ?>
        <div class="condition-fields">
            <!-- Operator -->
            <div class="field-group operator-group" style="display: none;">
                <label>Operator:</label>
                <select class="condition-operator">
                    <!-- Opcje będą dodane dynamicznie -->
                </select>
            </div>

            <!-- Wartość -->
            <div class="field-group value-group" style="display: none;">
                <label>Wartość:</label>
                <input type="text" class="condition-value" placeholder="Wprowadź wartość">
            </div>

            <!-- Pole dodatkowe -->
            <div class="field-group field-group-extra" style="display: none;">
                <label>Nazwa statystyki:</label>
                <input type="text" class="condition-field" placeholder="np. strength, agility">
            </div>

            <!-- Opis warunku -->
            <div class="condition-description">
                <small class="condition-help"></small>
            </div>
        </div>
<?php
    }

    /**
     * Renderuje opcje operatorów
     */
    private static function render_operator_options($selected = '==', $type = '')
    {
        $operators = [];

        switch ($type) {
            case 'user_level':
            case 'user_gold':
            case 'user_stat':
            case 'time_of_day':
                $operators = [
                    '==' => 'równe',
                    '!=' => 'różne od',
                    '>' => 'większe niż',
                    '>=' => 'większe lub równe',
                    '<' => 'mniejsze niż',
                    '<=' => 'mniejsze lub równe'
                ];
                break;

            case 'user_item':
                $operators = [
                    'has' => 'posiada',
                    'not_has' => 'nie posiada'
                ];
                break;

            case 'quest_completed':
                $operators = [
                    'completed' => 'ukończone',
                    'not_completed' => 'nie ukończone'
                ];
                break;

            default:
                $operators = [
                    '==' => 'równe',
                    '!=' => 'różne od'
                ];
        }

        foreach ($operators as $value => $label) {
            $selected_attr = selected($selected, $value, false);
            echo "<option value=\"{$value}\" {$selected_attr}>{$label}</option>";
        }
    }

    /**
     * Renderuje pole wartości
     */
    private static function render_value_field($type, $value = '')
    {
        switch ($type) {
            case 'user_level':
            case 'user_gold':
            case 'user_stat':
            case 'time_of_day':
                echo "<input type=\"number\" class=\"condition-value\" value=\"" . esc_attr($value) . "\" min=\"0\">";
                break;

            case 'user_item':
                echo "<input type=\"text\" class=\"condition-value\" value=\"" . esc_attr($value) . "\" placeholder=\"ID przedmiotu\">";
                break;

            case 'quest_completed':
                echo "<input type=\"text\" class=\"condition-value\" value=\"" . esc_attr($value) . "\" placeholder=\"ID zadania\">";
                break;

            case 'custom':
                echo "<input type=\"text\" class=\"condition-value\" value=\"" . esc_attr($value) . "\" placeholder=\"Wartość niestandardowa\">";
                break;

            default:
                echo "<input type=\"text\" class=\"condition-value\" value=\"" . esc_attr($value) . "\" placeholder=\"Wprowadź wartość\">";
        }
    }

    /**
     * Zwraca opis dla typu warunku
     */
    public static function get_condition_descriptions()
    {
        return [
            'user_level' => 'Sprawdza poziom gracza. Użyj liczby całkowitej.',
            'user_gold' => 'Sprawdza ilość złota gracza. Użyj liczby całkowitej.',
            'user_item' => 'Sprawdza czy gracz posiada określony przedmiot. Użyj ID przedmiotu.',
            'quest_completed' => 'Sprawdza czy zadanie zostało ukończone. Użyj ID zadania.',
            'user_stat' => 'Sprawdza statystykę gracza (np. siła, zręczność). Podaj nazwę statystyki.',
            'time_of_day' => 'Sprawdza porę dnia (0-23). Użyj godziny w formacie 24h.',
            'custom' => 'Warunek niestandardowy dla zaawansowanych zastosowań.'
        ];
    }

    /**
     * Waliduje i czyści dane warunków
     */
    public static function sanitize_conditions($conditions_json)
    {
        if (empty($conditions_json)) {
            return null;
        }

        $conditions = json_decode($conditions_json, true);
        if (!is_array($conditions)) {
            return null;
        }

        $sanitized = [];
        foreach ($conditions as $condition) {
            if (!isset($condition['type']) || empty($condition['type'])) {
                continue;
            }

            $sanitized_condition = [
                'type' => sanitize_text_field($condition['type']),
                'operator' => sanitize_text_field($condition['operator'] ?? '=='),
                'value' => sanitize_text_field($condition['value'] ?? ''),
            ];

            // Dodaj pole field tylko dla user_stat
            if ($condition['type'] === 'user_stat' && !empty($condition['field'])) {
                $sanitized_condition['field'] = sanitize_text_field($condition['field']);
            }

            $sanitized[] = $sanitized_condition;
        }

        return !empty($sanitized) ? json_encode($sanitized) : null;
    }
}
