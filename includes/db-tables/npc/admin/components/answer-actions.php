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
        'modify_gold' => [
            'label' => 'złoto',
            'description' => 'Dodaje lub odejmuje złoto graczowi (użyj ujemnej wartości aby odjąć)',
            'fields' => [
                'amount' => [
                    'type' => 'number',
                    'label' => 'Ilość złota',
                    'default' => 0
                ]
            ]
        ],
        'modify_cigarettes' => [
            'label' => 'papierosy',
            'description' => 'Dodaje lub odejmuje papierosy graczowi (użyj ujemnej wartości aby odjąć)',
            'fields' => [
                'amount' => [
                    'type' => 'number',
                    'label' => 'Ilość papierosów',
                    'default' => 0
                ]
            ]
        ],
        'modify_stat' => [
            'label' => 'statystykę',
            'description' => 'Modyfikuje wartość wybranej statystyki',
            'fields' => [
                'stat' => [
                    'type' => 'select',
                    'label' => 'Statystyka',
                    'options' => [
                        'strength' => 'Siła',
                        'defence' => 'Obrona',
                        'dexterity' => 'Zręczność',
                        'perception' => 'Percepcja',
                        'technical' => 'Technika',
                        'charisma' => 'Charyzma'
                    ],
                    'default' => 'strength'
                ],
                'amount' => [
                    'type' => 'number',
                    'label' => 'Zmiana wartości',
                    'default' => 0,
                ]
            ]
        ],
        'modify_skill' => [
            'label' => 'umiejętność',
            'description' => 'Modyfikuje wartość wybranej umiejętności',
            'fields' => [
                'skill' => [
                    'type' => 'select',
                    'label' => 'Umiejętność',
                    'options' => [
                        'combat' => 'Walka',
                        'steal' => 'Kradzież',
                        'craft' => 'Rzemiosło',
                        'trade' => 'Handel',
                        'relations' => 'Relacje',
                        'street' => 'Ulica'
                    ],
                    'default' => 'combat'
                ],
                'amount' => [
                    'type' => 'number',
                    'label' => 'Zmiana wartości',
                    'default' => 0,
                ]
            ]
        ],
        'modify_exp' => [
            'label' => 'doświadczenie',
            'description' => 'Dodaje lub odejmuje punkty doświadczenia',
            'fields' => [
                'amount' => [
                    'type' => 'number',
                    'label' => 'Ilość doświadczenia',
                    'default' => 0
                ]
            ]
        ],
        'modify_reputation' => [
            'label' => 'reputację',
            'description' => 'Dodaje lub odejmuje punkty reputacji',
            'fields' => [
                'amount' => [
                    'type' => 'number',
                    'label' => 'Zmiana reputacji',
                    'default' => 0
                ]
            ]
        ],
        'start_combat' => [
            'label' => 'Rozpocznij walkę',
            'description' => 'Rozpoczyna walkę z wybranym przeciwnikiem',
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
        ],
        'modify_items' => [
            'label' => 'przedmiot',
            'description' => 'Dodaje lub odejmuje przedmiot graczowi (użyj ujemnej wartości aby odebrać)',
            'fields' => [
                'item_id' => [
                    'type' => 'select',
                    'label' => 'Przedmiot',
                    'options' => 'get_items',
                    'default' => ''
                ],
                'amount' => [
                    'type' => 'number',
                    'label' => 'Ilość',
                    'default' => 1
                ]
            ]
        ],
        'change_location' => [
            'label' => 'zmiana lokalizacji',
            'description' => 'Przenosi gracza do wybranej lokalizacji',
            'fields' => [
                'location_id' => [
                    'type' => 'select',
                    'label' => 'Lokalizacja',
                    'options' => 'get_locations_with_scenes',
                    'default' => ''
                ],
                'scene_id' => [
                    'type' => 'select',
                    'label' => 'Scena',
                    'options' => [],
                    'default' => '',
                    'depends_on' => 'location_id'
                ]
            ]
        ],
        'unlock_location' => [
            'label' => 'odblokowanie lokalizacji',
            'description' => 'Odblokowuje dostęp do lokalizacji i określonej sceny',
            'fields' => [
                'location_id' => [
                    'type' => 'select',
                    'label' => 'Lokalizacja',
                    'options' => 'get_locations_with_scenes',
                    'default' => ''
                ],
                'scene_id' => [
                    'type' => 'select',
                    'label' => 'Scena',
                    'options' => [],
                    'default' => '',
                    'depends_on' => 'location_id'
                ]
            ]
        ]
    ];

    $action_types = apply_filters('npc_answer_action_types', $action_types);
?>

    <div class="answer-actions-manager" data-answer-id="<?php echo esc_attr($answer_id); ?>">
        <h4>Akcje odpowiedzi</h4>
        <p class="description">Dodaj akcje, które wykonają się po wybraniu tej odpowiedzi przez gracza. Przeciągnij, aby zmienić kolejność.</p>

        <!-- Configuration script -->
        <script type="application/json" id="action-types-config">
            <?php echo json_encode($action_types); ?>
        </script>

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

/**
 * Pobiera listę przedmiotów (funkcja pomocnicza)
 */
function get_items()
{
    global $wpdb;

    // Można pobrać z tabeli items lub z CPT
    $items = $wpdb->get_results(
        "SELECT ID, post_title FROM {$wpdb->posts} 
         WHERE post_type = 'item' AND post_status = 'publish'
         ORDER BY post_title ASC"
    );

    $options = ['0' => '-- Wybierz przedmiot --'];
    foreach ($items as $item) {
        $options[$item->ID] = $item->post_title;
    }

    return $options;
}

/**
 * Pobiera listę lokalizacji ze scenami (funkcja pomocnicza)
 */
function get_locations_with_scenes()
{
    // Pobierz lokalizacje z post type 'tereny'
    $locations = get_posts([
        'post_type' => 'tereny',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);

    $options = ['0' => '-- Wybierz lokalizację --'];

    foreach ($locations as $location) {
        // Pobierz sceny dla lokalizacji z ACF
        $scenes = [];
        if (have_rows('scenes', $location->ID)) {
            while (have_rows('scenes', $location->ID)) {
                the_row();
                $scenes[] = [
                    'id' => get_sub_field('id_sceny'),
                    'title' => get_sub_field('nazwa') ?: ('Scena ' . get_sub_field('id_sceny'))
                ];
            }
        }

        // Dodaj lokalizację do opcji tylko jeśli ma sceny
        if (!empty($scenes)) {
            $options[$location->post_name] = [
                'title' => $location->post_title,
                'scenes' => $scenes
            ];
        }
    }

    return $options;
}
