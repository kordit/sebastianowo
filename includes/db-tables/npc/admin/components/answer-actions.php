<?php

/**
 * Answer Actions Component
 * Komponent do zarządzania akcjami w odpowiedziach NPC
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderuje interfejs zarządzania akcjami dla odpowiedzi
 */
function render_answer_actions_manager($answer_id = null, $existing_actions = [])
{
    // Dostępne typy akcji
    $action_types = [
        'give_gold' => [
            'label' => 'Daj złoto',
            'description' => 'Dodaje określoną ilość złota do portfela gracza',
            'fields' => [
                'amount' => [
                    'type' => 'number',
                    'label' => 'Ilość złota',
                    'min' => 1,
                    'default' => 100
                ]
            ]
        ],
        'start_combat' => [
            'label' => 'Rozpocznij walkę',
            'description' => 'Rozpoczyna walkę z określonym przeciwnikiem',
            'fields' => [
                'enemy_id' => [
                    'type' => 'select',
                    'label' => 'Przeciwnik',
                    'options' => 'get_enemies_list',
                    'default' => ''
                ],
                'combat_type' => [
                    'type' => 'select',
                    'label' => 'Typ walki',
                    'options' => [
                        'normal' => 'Normalna',
                        'boss' => 'Boss',
                        'arena' => 'Arena'
                    ],
                    'default' => 'normal'
                ]
            ]
        ]
    ];

    $action_types = apply_filters('npc_answer_action_types', $action_types);
?>

    <div class="answer-actions-manager" data-answer-id="<?php echo esc_attr($answer_id); ?>">
        <h4>Akcje odpowiedzi</h4>
        <p class="description">Dodaj akcje, które wykonają się po wybraniu tej odpowiedzi przez gracza. Przeciągnij, aby zmienić kolejność.</p>

        <div class="actions-list sortable" id="actions-list">
            <?php if (!empty($existing_actions)): ?>
                <?php foreach ($existing_actions as $index => $action): ?>
                    <?php render_single_action($action, $index, $action_types); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="add-action-controls">
            <select id="new-action-type" class="action-type-select">
                <option value="">-- Wybierz typ akcji --</option>
                <?php foreach ($action_types as $type => $config): ?>
                    <option value="<?php echo esc_attr($type); ?>">
                        <?php echo esc_html($config['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button button-secondary add-action-btn">
                Dodaj akcję
            </button>
        </div>

        <!-- Hidden input to store actions data -->
        <input type="hidden" name="answer_actions" id="answer-actions-data" value="<?php echo esc_attr(json_encode($existing_actions)); ?>">
    </div>

    <!-- Sortable.js library -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- Action templates (hidden) -->
    <div class="action-templates" style="display: none;">
        <?php foreach ($action_types as $type => $config): ?>
            <div class="action-template" data-action-type="<?php echo esc_attr($type); ?>">
                <?php render_action_template($type, $config); ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script type="application/json" id="action-types-config">
        <?php echo json_encode($action_types); ?>
    </script>

<?php
}

/**
 * Renderuje pojedynczą akcję
 */
function render_single_action($action, $index, $action_types)
{
    $type = $action['type'] ?? '';
    $config = $action_types[$type] ?? null;

    if (!$config) {
        return;
    }
?>

    <div class="action-item" data-index="<?php echo esc_attr($index); ?>" data-type="<?php echo esc_attr($type); ?>">
        <div class="action-header">
            <h5><?php echo esc_html($config['label']); ?></h5>
            <button type="button" class="remove-action-btn">&times;</button>
        </div>
        <div class="action-body">
            <p class="action-description"><?php echo esc_html($config['description']); ?></p>

            <?php foreach ($config['fields'] as $field_name => $field_config): ?>
                <div class="action-field">
                    <label><?php echo esc_html($field_config['label']); ?></label>
                    <?php render_action_field($field_name, $field_config, $action['params'][$field_name] ?? $field_config['default']); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php
}

/**
 * Renderuje template akcji
 */
function render_action_template($type, $config)
{
?>
    <div class="action-item" data-type="<?php echo esc_attr($type); ?>">
        <div class="action-header">
            <h5><?php echo esc_html($config['label']); ?></h5>
            <button type="button" class="remove-action-btn">&times;</button>
        </div>
        <div class="action-body">
            <p class="action-description"><?php echo esc_html($config['description']); ?></p>

            <?php foreach ($config['fields'] as $field_name => $field_config): ?>
                <div class="action-field">
                    <label><?php echo esc_html($field_config['label']); ?></label>
                    <?php render_action_field($field_name, $field_config, $field_config['default'], true); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Renderuje pole formularza dla akcji
 */
function render_action_field($field_name, $field_config, $value = '', $is_template = false)
{
    $name_attr = $is_template ? "template_{$field_name}" : "action_field_{$field_name}";

    switch ($field_config['type']) {
        case 'number':
    ?>
            <input type="number"
                name="<?php echo esc_attr($name_attr); ?>"
                value="<?php echo esc_attr($value); ?>"
                min="<?php echo esc_attr($field_config['min'] ?? 0); ?>"
                max="<?php echo esc_attr($field_config['max'] ?? ''); ?>"
                class="regular-text action-field-input"
                data-field="<?php echo esc_attr($field_name); ?>">
        <?php
            break;

        case 'text':
        ?>
            <input type="text"
                name="<?php echo esc_attr($name_attr); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="regular-text action-field-input"
                data-field="<?php echo esc_attr($field_name); ?>">
        <?php
            break;

        case 'select':
        ?>
            <select name="<?php echo esc_attr($name_attr); ?>"
                class="regular-text action-field-input"
                data-field="<?php echo esc_attr($field_name); ?>">
                <?php
                $options = [];
                if (is_string($field_config['options']) && function_exists($field_config['options'])) {
                    $options = call_user_func($field_config['options']);
                } elseif (is_array($field_config['options'])) {
                    $options = $field_config['options'];
                }

                foreach ($options as $option_value => $option_label): ?>
                    <option value="<?php echo esc_attr($option_value); ?>"
                        <?php selected($value, $option_value); ?>>
                        <?php echo esc_html($option_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
<?php
            break;
    }
}

/**
 * Pobiera listę wrogów (funkcja pomocnicza)
 */
function get_enemies_list()
{
    global $wpdb;

    // Można pobrać z tabeli enemies lub z CPT
    $enemies = $wpdb->get_results(
        "SELECT ID, post_title FROM {$wpdb->posts} 
         WHERE post_type = 'enemy' AND post_status = 'publish'
         ORDER BY post_title ASC"
    );

    $options = ['0' => '-- Wybierz przeciwnika --'];
    foreach ($enemies as $enemy) {
        $options[$enemy->ID] = $enemy->post_title;
    }

    return $options;
}
