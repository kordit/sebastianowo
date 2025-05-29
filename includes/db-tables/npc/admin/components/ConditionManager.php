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
        $logic_operator = isset($conditions_data['logic']) ? $conditions_data['logic'] : 'AND';
        $conditions_list = isset($conditions_data['conditions']) ? $conditions_data['conditions'] : ($conditions_data ?: []);
?>
        <div class="conditions-manager" data-context="<?php echo esc_attr($context); ?>">
            <div class="conditions-header">
                <h4>Warunki wyświetlania</h4>
                <div class="conditions-logic">
                    <label>Logika warunków:</label>
                    <select class="conditions-logic-operator" name="<?php echo $field_prefix; ?>_logic">
                        <option value="AND" <?php selected($logic_operator, 'AND'); ?>>Wszystkie warunki muszą być spełnione (AND)</option>
                        <option value="OR" <?php selected($logic_operator, 'OR'); ?>>Wystarczy jeden warunek (OR)</option>
                    </select>
                </div>
                <button type="button" class="button button-secondary add-condition-btn">
                    Dodaj warunek
                </button>
            </div>

            <div class="conditions-list">
                <?php if (!empty($conditions_list)): ?>
                    <?php foreach ($conditions_list as $index => $condition): ?>
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
            'user_skill' => 'Umiejętność gracza',
            'user_class' => 'Klasa gracza',
            'user_item' => 'Przedmiot gracza',
            'user_mission' => 'Misja gracza',
            'user_stat' => 'Statystyka gracza'
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

            <!-- Pole dodatkowe -->
            <div class="field-group field-group-extra" style="<?php echo in_array($type, ['user_stat', 'user_skill', 'user_item']) ? '' : 'display: none;'; ?>">
                <?php if ($type === 'user_skill'): ?>
                    <label>Nazwa umiejętności:</label>
                    <select class="condition-field skill-select">
                        <?php self::render_skill_options($field); ?>
                    </select>
                <?php elseif ($type === 'user_stat'): ?>
                    <label>Nazwa statystyki:</label>
                    <select class="condition-field stat-select">
                        <?php self::render_stat_options($field); ?>
                    </select>
                <?php elseif ($type === 'user_item'): ?>
                    <label>Liczba sztuk:</label>
                    <input type="number" class="condition-field item-amount" value="<?php echo esc_attr($field); ?>" min="0" placeholder="Liczba sztuk">
                <?php endif; ?>
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
                <label>Dodatkowe pole:</label>
                <select class="condition-field skill-select" style="display: none;">
                    <?php self::render_skill_options(); ?>
                </select>
                <select class="condition-field stat-select" style="display: none;">
                    <?php self::render_stat_options(); ?>
                </select>
                <input type="number" class="condition-field item-amount" style="display: none;" min="0" placeholder="Liczba sztuk">
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
            case 'user_stat':
            case 'user_skill':
                $operators = [
                    '==' => 'równe',
                    '!=' => 'różne od',
                    '>' => 'większe niż',
                    '>=' => 'większe lub równe',
                    '<' => 'mniejsze niż',
                    '<=' => 'mniejsze lub równe'
                ];
                break;

            case 'user_class':
                $operators = [
                    '==' => 'jest klasą',
                    '!=' => 'nie jest klasą'
                ];
                break;

            case 'user_item':
                $operators = [
                    'has' => 'posiada',
                    'not_has' => 'nie posiada',
                    '==' => 'ma dokładnie',
                    '!=' => 'nie ma dokładnie',
                    '>' => 'ma więcej niż',
                    '>=' => 'ma co najmniej',
                    '<' => 'ma mniej niż',
                    '<=' => 'ma co najwyżej'
                ];
                break;

            case 'user_mission':
                $operators = [
                    'not_started' => 'nie rozpoczęta',
                    'in_progress' => 'w trakcie',
                    'completed' => 'ukończona',
                    'failed' => 'nieudana',
                    'expired' => 'wygasła'
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
                echo "<input type=\"number\" class=\"condition-value\" value=\"" . esc_attr($value) . "\" min=\"0\" placeholder=\"Poziom gracza\">";
                break;

            case 'user_stat':
                echo "<input type=\"number\" class=\"condition-value\" value=\"" . esc_attr($value) . "\" min=\"0\" placeholder=\"Wartość statystyki\">";
                break;

            case 'user_skill':
                echo "<input type=\"number\" class=\"condition-value\" value=\"" . esc_attr($value) . "\" min=\"0\" placeholder=\"Poziom umiejętności\">";
                break;

            case 'user_class':
                echo "<select class=\"condition-value\">";
                self::render_class_options($value);
                echo "</select>";
                break;

            case 'user_item':
                echo "<select class=\"condition-value\">";
                self::render_item_options($value);
                echo "</select>";
                break;

            case 'user_mission':
                echo "<select class=\"condition-value\">";
                self::render_mission_options($value);
                echo "</select>";
                break;

            default:
                echo "<input type=\"text\" class=\"condition-value\" value=\"" . esc_attr($value) . "\" placeholder=\"Wprowadź wartość\">";
        }
    }

    /**
     * Renderuje opcje umiejętności
     */
    private static function render_skill_options($selected = '')
    {
        $skills = [
            '' => 'Wybierz umiejętność...',
            'combat' => 'Walka',
            'steal' => 'Kradzież',
            'craft' => 'Majsterkowanie',
            'trade' => 'Handel',
            'relations' => 'Relacje',
            'street' => 'Ulica'
        ];

        foreach ($skills as $value => $label) {
            $selected_attr = selected($selected, $value, false);
            echo "<option value=\"{$value}\" {$selected_attr}>{$label}</option>";
        }
    }

    /**
     * Renderuje opcje klas gracza
     */
    private static function render_class_options($selected = '')
    {
        $classes = [
            '' => 'Wybierz klasę...',
            'zadymiarz' => '🔥 Zadymiarz',
            'zawijacz' => '💨 Zawijacz',
            'kombinator' => '⚡ Kombinator'
        ];

        foreach ($classes as $value => $label) {
            $selected_attr = selected($selected, $value, false);
            echo "<option value=\"{$value}\" {$selected_attr}>{$label}</option>";
        }
    }

    /**
     * Renderuje opcje statystyk gracza
     */
    private static function render_stat_options($selected = '')
    {
        $stats = [
            '' => 'Wybierz statystykę...',
            'strength' => 'Siła',
            'defense' => 'Obrona',
            'dexterity' => 'Zręczność',
            'perception' => 'Percepcja',
            'technical' => 'Technika',
            'charisma' => 'Charyzma'
        ];

        foreach ($stats as $value => $label) {
            $selected_attr = selected($selected, $value, false);
            echo "<option value=\"{$value}\" {$selected_attr}>{$label}</option>";
        }
    }

    /**
     * Renderuje opcje przedmiotów z CPT items
     */
    private static function render_item_options($selected = '')
    {
        // Spróbujmy alternatywną metodę pobierania postów
        global $wpdb;

        $items = $wpdb->get_results(
            "SELECT ID, post_title FROM {$wpdb->posts} 
             WHERE post_type = 'item' AND post_status = 'publish'
             ORDER BY post_title ASC"
        );

        // Dodajmy debugowanie
        echo '<option value="">Wybierz przedmiot... (' . count($items) . ' znaleziono)</option>';

        foreach ($items as $item) {
            $selected_attr = selected($selected, $item->ID, false);
            echo "<option value=\"{$item->ID}\" {$selected_attr}>{$item->post_title}</option>";
        }
    }

    /**
     * Renderuje opcje misji z tabeli game_user_mission_tasks
     */
    private static function render_mission_options($selected = '')
    {
        global $wpdb;

        $missions = $wpdb->get_results(
            "SELECT DISTINCT mission_id, mission_title 
             FROM {$wpdb->prefix}game_user_mission_tasks 
             WHERE mission_title != '' 
             ORDER BY mission_title ASC"
        );

        echo '<option value="">Wybierz misję...</option>';

        foreach ($missions as $mission) {
            $selected_attr = selected($selected, $mission->mission_id, false);
            $title = esc_html($mission->mission_title);
            echo "<option value=\"{$mission->mission_id}\" {$selected_attr}>{$title}</option>";
        }
    }

    /**
     * Zwraca opis dla typu warunku
     */
    public static function get_condition_descriptions()
    {
        return [
            'user_level' => 'Sprawdza poziom gracza. Podaj wymaganą wartość.',
            'user_skill' => 'Sprawdza poziom wybranej umiejętności gracza. Wybierz umiejętność i podaj wymaganą wartość.',
            'user_class' => 'Sprawdza klasę gracza. Wybierz klasę z listy.',
            'user_item' => 'Sprawdza czy gracz posiada przedmiot. Wybierz przedmiot i określ liczbę sztuk.',
            'user_mission' => 'Sprawdza status misji gracza. Wybierz misję i wymagany status.',
            'user_stat' => 'Sprawdza wybraną statystykę gracza. Wybierz statystykę i podaj wymaganą wartość.'
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

        $data = json_decode($conditions_json, true);
        if (!is_array($data)) {
            return null;
        }

        // Sprawdź czy to nowa struktura z logiką OR/AND
        if (isset($data['logic']) && isset($data['conditions'])) {
            $logic = sanitize_text_field($data['logic']);
            $conditions = $data['conditions'];
        } else {
            // Stara struktura - traktuj jako AND
            $logic = 'AND';
            $conditions = $data;
        }

        if (!in_array($logic, ['AND', 'OR'])) {
            $logic = 'AND';
        }

        $sanitized_conditions = [];
        foreach ($conditions as $condition) {
            if (!isset($condition['type']) || empty($condition['type'])) {
                continue;
            }

            $sanitized_condition = [
                'type' => sanitize_text_field($condition['type']),
                'operator' => sanitize_text_field($condition['operator'] ?? '=='),
                'value' => sanitize_text_field($condition['value'] ?? ''),
            ];

            // Dodaj pole field dla różnych typów warunków
            if (in_array($condition['type'], ['user_stat', 'user_skill']) && !empty($condition['field'])) {
                $sanitized_condition['field'] = sanitize_text_field($condition['field']);
            }

            // Dla przedmiotów dodaj liczbę sztuk jako field
            if (
                $condition['type'] === 'user_item' &&
                !in_array($condition['operator'], ['has', 'not_has']) &&
                isset($condition['field'])
            ) {
                $sanitized_condition['field'] = absint($condition['field']);
            }

            $sanitized_conditions[] = $sanitized_condition;
        }

        if (empty($sanitized_conditions)) {
            return null;
        }

        $result = [
            'logic' => $logic,
            'conditions' => $sanitized_conditions
        ];

        return json_encode($result);
    }

    /**
     * Sprawdza czy warunki są spełnione dla danego gracza
     */
    public static function check_conditions($conditions_json, $user_id)
    {
        if (empty($conditions_json)) {
            return true; // Brak warunków = zawsze spełnione
        }

        $data = json_decode($conditions_json, true);
        if (!is_array($data)) {
            return true;
        }

        // Sprawdź czy to nowa struktura z logiką OR/AND
        if (isset($data['logic']) && isset($data['conditions'])) {
            $logic = $data['logic'];
            $conditions = $data['conditions'];
        } else {
            // Stara struktura - traktuj jako AND
            $logic = 'AND';
            $conditions = $data;
        }

        if (empty($conditions)) {
            return true;
        }

        $results = [];
        foreach ($conditions as $condition) {
            $results[] = self::check_single_condition($condition, $user_id);
        }

        // Zastosuj logikę AND/OR
        if ($logic === 'OR') {
            return in_array(true, $results); // Wystarczy jeden true
        } else {
            return !in_array(false, $results); // Wszystkie muszą być true
        }
    }

    /**
     * Sprawdza pojedynczy warunek
     */
    private static function check_single_condition($condition, $user_id)
    {
        global $wpdb;

        $type = $condition['type'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? '';
        $field = $condition['field'] ?? '';

        // Pobierz dane gracza
        $user_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}game_users WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        if (!$user_data) {
            return false;
        }

        switch ($type) {
            case 'user_level':
                $user_value = (int) $user_data['exp']; // Zakładam że poziom to exp
                return self::compare_values($user_value, $operator, (int) $value);

            case 'user_skill':
                if (empty($field) || !isset($user_data[$field])) {
                    return false;
                }
                $user_value = (int) $user_data[$field];
                return self::compare_values($user_value, $operator, (int) $value);

            case 'user_class':
                $user_value = $user_data['user_class'] ?? '';
                return self::compare_values($user_value, $operator, $value);

            case 'user_stat':
                if (empty($field) || !isset($user_data[$field])) {
                    return false;
                }
                $user_value = (int) $user_data[$field];
                return self::compare_values($user_value, $operator, (int) $value);

            case 'user_item':
                $item_amount = $wpdb->get_var($wpdb->prepare(
                    "SELECT amount FROM {$wpdb->prefix}game_user_items 
                     WHERE user_id = %d AND item_id = %d",
                    $user_id,
                    (int) $value
                )) ?: 0;

                // Sprawdź czy operator to has/not_has czy numeryczny
                if (in_array($operator, ['has', 'not_has'])) {
                    $has_item = $item_amount > 0;
                    return ($operator === 'has') ? $has_item : !$has_item;
                } else {
                    // Dla numerycznych operatorów użyj field jako liczby sztuk
                    $required_amount = (int) $field;
                    return self::compare_values($item_amount, $operator, $required_amount);
                }

            case 'user_mission':
                $mission_status = $wpdb->get_var($wpdb->prepare(
                    "SELECT mission_status FROM {$wpdb->prefix}game_user_mission_tasks 
                     WHERE user_id = %d AND mission_id = %d 
                     ORDER BY created_at DESC LIMIT 1",
                    $user_id,
                    (int) $value
                ));

                if (!$mission_status) {
                    $mission_status = 'not_started';
                }

                return $mission_status === $operator;

            default:
                return true;
        }
    }

    /**
     * Porównuje wartości według operatora
     */
    private static function compare_values($user_value, $operator, $condition_value)
    {
        switch ($operator) {
            case '==':
                return $user_value == $condition_value;
            case '!=':
                return $user_value != $condition_value;
            case '>':
                return $user_value > $condition_value;
            case '>=':
                return $user_value >= $condition_value;
            case '<':
                return $user_value < $condition_value;
            case '<=':
                return $user_value <= $condition_value;
            default:
                return false;
        }
    }
}
