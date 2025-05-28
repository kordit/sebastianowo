<?php
// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Oblicz poziom
$level = max(1, floor($game_user['exp'] / 100) + 1);
?>

<div class="wrap ga-container">
    <!-- Header -->
    <div class="ga-header">
        <h1 class="ga-header__title">
            👤 <?php echo esc_html($game_user['nick'] ?: $wp_user->display_name); ?>
        </h1>
        <div class="ga-header__subtitle">Edycja gracza</div>
        <div class="ga-header__meta">
            ID: <?php echo esc_html($game_user['user_id']); ?> | Poziom: <?php echo $level; ?>
        </div>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('update_game_user', '_wpnonce'); ?>
        <input type="hidden" name="user_id" value="<?php echo $game_user['user_id']; ?>">

        <div class="ga-details-grid">
            <!-- Podstawowe informacje -->
            <div class="ga-card ga-card--primary">
                <div class="ga-card__header">
                    <h3 class="ga-card__title">📝 Podstawowe informacje</h3>
                </div>
                <div class="ga-card__content">
                    <table class="ga-form-table">
                        <tr>
                            <th>Login WordPress:</th>
                            <td><?php echo esc_html($wp_user->user_login); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo esc_html($wp_user->user_email); ?></td>
                        </tr>
                        <tr>
                            <th>Nick gracza:</th>
                            <td><input type="text" name="nick" value="<?php echo esc_attr($game_user['nick']); ?>" class="ga-form-control"></td>
                        </tr>
                        <tr>
                            <th>Klasa postaci:</th>
                            <td>
                                <select name="user_class" class="ga-form-select">
                                    <option value="">Wybierz klasę</option>
                                    <option value="zadymiarz" <?php selected($game_user['user_class'], 'zadymiarz'); ?>>🔥 Zadymiarz</option>
                                    <option value="zawijacz" <?php selected($game_user['user_class'], 'zawijacz'); ?>>💨 Zawijacz</option>
                                    <option value="kombinator" <?php selected($game_user['user_class'], 'kombinator'); ?>>⚡ Kombinator</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Poziom:</th>
                            <td><strong><?php echo $level; ?></strong> <small>(obliczany z exp)</small></td>
                        </tr>
                        <tr>
                            <th>Doświadczenie:</th>
                            <td><input type="number" name="exp" value="<?php echo $game_user['exp']; ?>" min="0" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Punkty nauki:</th>
                            <td><input type="number" name="learning_points" value="<?php echo $game_user['learning_points']; ?>" min="0" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Reputacja:</th>
                            <td><input type="number" name="reputation" value="<?php echo $game_user['reputation']; ?>" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Statystyki postaci -->
            <div class="ga-card ga-card--success">
                <div class="ga-card__header">
                    <h3 class="ga-card__title">📊 Statystyki postaci</h3>
                </div>
                <div class="ga-card__content">
                    <table class="ga-form-table">
                        <tr>
                            <th>Siła:</th>
                            <td><input type="number" name="strength" value="<?php echo $game_user['strength']; ?>" min="1" max="50" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Obrona:</th>
                            <td><input type="number" name="defense" value="<?php echo $game_user['defense']; ?>" min="1" max="50" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Zręczność:</th>
                            <td><input type="number" name="dexterity" value="<?php echo $game_user['dexterity']; ?>" min="1" max="50" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Percepcja:</th>
                            <td><input type="number" name="perception" value="<?php echo $game_user['perception']; ?>" min="1" max="50" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Technika:</th>
                            <td><input type="number" name="technical" value="<?php echo $game_user['technical']; ?>" min="1" max="50" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Charyzma:</th>
                            <td><input type="number" name="charisma" value="<?php echo $game_user['charisma']; ?>" min="1" max="50" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                    </table>
                </div>
            </div>
            <!-- Umiejętności -->
            <div class="ga-card ga-card--info">
                <div class="ga-card__header">
                    <h3 class="ga-card__title">🎯 Umiejętności</h3>
                </div>
                <div class="ga-card__content">
                    <table class="ga-form-table">
                        <tr>
                            <th>Walka:</th>
                            <td><input type="number" name="combat" value="<?php echo $game_user['combat']; ?>" min="0" max="100" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Kradzież:</th>
                            <td><input type="number" name="steal" value="<?php echo $game_user['steal']; ?>" min="0" max="100" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Rzemiosło:</th>
                            <td><input type="number" name="craft" value="<?php echo $game_user['craft']; ?>" min="0" max="100" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Handel:</th>
                            <td><input type="number" name="trade" value="<?php echo $game_user['trade']; ?>" min="0" max="100" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Znajomości:</th>
                            <td><input type="number" name="relations" value="<?php echo $game_user['relations']; ?>" min="0" max="100" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Ulica:</th>
                            <td><input type="number" name="street" value="<?php echo $game_user['street']; ?>" min="0" max="100" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Stan gracza -->
            <div class="ga-card ga-card--warning">
                <div class="ga-card__header">
                    <h3 class="ga-card__title">❤️ Stan gracza</h3>
                </div>
                <div class="ga-card__content">
                    <table class="ga-form-table">
                        <tr>
                            <th>Życie:</th>
                            <td>
                                <div class="ga-field-group">
                                    <input type="number" name="life" value="<?php echo $game_user['life']; ?>" min="0" max="<?php echo $game_user['max_life']; ?>" class="ga-form-control ga-form-control--small">
                                    <span>/</span>
                                    <input type="number" name="max_life" value="<?php echo $game_user['max_life']; ?>" min="1" class="ga-form-control ga-form-control--small">
                                    <div class="ga-progress">
                                        <div class="ga-progress__fill ga-progress__fill--health" style="width: <?php echo ($game_user['max_life'] > 0 ? ($game_user['life'] / $game_user['max_life']) * 100 : 0); ?>%"></div>
                                        <div class="ga-progress__text"><?php echo $game_user['life']; ?>/<?php echo $game_user['max_life']; ?></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Energia:</th>
                            <td>
                                <div class="ga-field-group">
                                    <input type="number" name="energy" value="<?php echo $game_user['energy']; ?>" min="0" max="<?php echo $game_user['max_energy']; ?>" class="ga-form-control ga-form-control--small">
                                    <span>/</span>
                                    <input type="number" name="max_energy" value="<?php echo $game_user['max_energy']; ?>" min="1" class="ga-form-control ga-form-control--small">
                                    <div class="ga-progress">
                                        <div class="ga-progress__fill ga-progress__fill--energy" style="width: <?php echo ($game_user['max_energy'] > 0 ? ($game_user['energy'] / $game_user['max_energy']) * 100 : 0); ?>%"></div>
                                        <div class="ga-progress__text"><?php echo $game_user['energy']; ?>/<?php echo $game_user['max_energy']; ?></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Złoto:</th>
                            <td><input type="number" name="gold" value="<?php echo $game_user['gold']; ?>" min="0" class="ga-form-control ga-form-control--medium"></td>
                        </tr>
                        <tr>
                            <th>Papierosy:</th>
                            <td><input type="number" name="cigarettes" value="<?php echo $game_user['cigarettes']; ?>" min="0" class="ga-form-control ga-form-control--medium"></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Lokalizacja -->
            <div class="ga-card ga-card--info">
                <div class="ga-card__header">
                    <h3 class="ga-card__title">📍 Lokalizacja</h3>
                </div>
                <div class="ga-card__content">
                    <table class="ga-form-table">
                        <tr>
                            <th>Obecny teren:</th>
                            <td><input type="number" name="current_area_id" value="<?php echo $game_user['current_area_id']; ?>" min="0" class="ga-form-control ga-form-control--small"></td>
                        </tr>
                        <tr>
                            <th>Obecna scena:</th>
                            <td><input type="text" name="current_scene_id" value="<?php echo esc_attr($game_user['current_scene_id']); ?>" class="ga-form-control"></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Historia postaci -->
            <div class="ga-card ga-card--full">
                <div class="ga-card__header">
                    <h3 class="ga-card__title">📖 Historia postaci</h3>
                </div>
                <div class="ga-card__content">
                    <textarea name="story_text" class="ga-form-textarea"><?php echo esc_textarea($game_user['story_text']); ?></textarea>
                </div>
            </div>

            <!-- Relacje z NPC -->
            <div class="ga-card ga-card--full">
                <div class="ga-header">
                    <h3>🤝 Relacje z NPC</h3>
                    <?php if (!empty($user_npc_relations)): ?>
                        <div class="ga-stats">
                            <div class="ga-stat">
                                <span class="ga-stat-label">Poznanych:</span>
                                <span class="ga-stat-value"><?php echo count(array_filter($user_npc_relations, fn($r) => $r['is_known'])); ?></span>
                            </div>
                            <div class="ga-stat">
                                <span class="ga-stat-label">Pozytywnych:</span>
                                <span class="ga-stat-value"><?php echo count(array_filter($user_npc_relations, fn($r) => $r['relation_value'] > 0)); ?></span>
                            </div>
                            <div class="ga-stat">
                                <span class="ga-stat-label">Negatywnych:</span>
                                <span class="ga-stat-value"><?php echo count(array_filter($user_npc_relations, fn($r) => $r['relation_value'] < 0)); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="ga-card-content">
                    <?php if (empty($user_npc_relations)): ?>
                        <div class="ga-notice ga-notice--info">
                            <p>Brak relacji z NPC. <a href="<?php echo admin_url('admin.php?page=game-buildery'); ?>">Zbuduj relacje w Builderach</a></p>
                        </div>
                    <?php else: ?>
                        <div class="ga-relations">
                            <?php foreach ($user_npc_relations as $relation): ?>
                                <div class="ga-relation <?php echo $relation['is_known'] ? 'ga-relation--known' : 'ga-relation--unknown'; ?>">
                                    <div class="ga-relation__header">
                                        <h4 class="ga-relation__title">
                                            <?php echo esc_html($npcs_by_id[$relation['npc_id']] ?? "NPC #{$relation['npc_id']}"); ?>
                                        </h4>
                                    </div>

                                    <div class="ga-relation__content">
                                        <!-- Czy poznany -->
                                        <div class="ga-relation__field">
                                            <label class="ga-checkbox-label">
                                                <input type="checkbox"
                                                    name="npc_relations[<?php echo $relation['npc_id']; ?>][is_known]"
                                                    value="1"
                                                    <?php checked($relation['is_known'], 1); ?>>
                                                <span class="ga-checkbox-mark"></span>
                                                Poznany przez gracza
                                            </label>
                                        </div>

                                        <!-- Poziom relacji -->
                                        <div class="ga-relation__field">
                                            <label class="ga-relation__label">Poziom relacji (-100 do +100):</label>
                                            <div class="ga-range">
                                                <input type="range"
                                                    name="npc_relations[<?php echo $relation['npc_id']; ?>][relation_value]"
                                                    value="<?php echo $relation['relation_value']; ?>"
                                                    min="-100"
                                                    max="100"
                                                    class="ga-range__slider"
                                                    data-npc="<?php echo $relation['npc_id']; ?>">
                                                <input type="number"
                                                    name="npc_relations[<?php echo $relation['npc_id']; ?>][relation_value]"
                                                    value="<?php echo $relation['relation_value']; ?>"
                                                    min="-100"
                                                    max="100"
                                                    class="ga-range__input"
                                                    data-npc="<?php echo $relation['npc_id']; ?>">
                                            </div>
                                        </div>

                                        <!-- Bilans walk -->
                                        <div class="ga-relation__field">
                                            <label class="ga-relation__label">Bilans walk:</label>
                                            <div class="ga-fight-stats">
                                                <div class="ga-form-row">
                                                    <label>🏆 Wygrane:</label>
                                                    <input type="number"
                                                        name="npc_relations[<?php echo $relation['npc_id']; ?>][fights_won]"
                                                        value="<?php echo $relation['fights_won']; ?>"
                                                        min="0"
                                                        class="ga-form-control ga-form-control--small">
                                                </div>
                                                <div class="ga-form-row">
                                                    <label>💀 Przegrane:</label>
                                                    <input type="number"
                                                        name="npc_relations[<?php echo $relation['npc_id']; ?>][fights_lost]"
                                                        value="<?php echo $relation['fights_lost']; ?>"
                                                        min="0"
                                                        class="ga-form-control ga-form-control--small">
                                                </div>
                                                <div class="ga-form-row">
                                                    <label>🤝 Remisy:</label>
                                                    <input type="number"
                                                        name="npc_relations[<?php echo $relation['npc_id']; ?>][fights_draw]"
                                                        value="<?php echo $relation['fights_draw']; ?>"
                                                        min="0"
                                                        class="ga-form-control ga-form-control--small">
                                                </div>
                                            </div>
                                            <div class="ga-stat">
                                                <span class="ga-stat-label">Łącznie walk:</span>
                                                <span class="ga-stat-value"><?php echo $relation['fights_won'] + $relation['fights_lost'] + $relation['fights_draw']; ?></span>
                                            </div>
                                        </div>

                                        <?php if ($relation['last_interaction']): ?>
                                            <div class="ga-meta-info">
                                                <small>Ostatnia interakcja: <?php echo date('d.m.Y H:i', strtotime($relation['last_interaction'])); ?></small>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Hidden field for NPC ID -->
                                        <input type="hidden" name="npc_relations[<?php echo $relation['npc_id']; ?>][npc_id]" value="<?php echo $relation['npc_id']; ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ga-card-actions">
                <button type="submit" name="update_game_user" class="ga-button ga-button--primary">
                    💾 Zapisz zmiany
                </button>
            </div>
        </div>
    </form>

    <!-- Ekwipunek gracza -->
    <div class="ga-card ga-card--full">
        <div class="ga-header">
            <h3>🎒 Ekwipunek gracza</h3>
            <?php if (!empty($user_items)): ?>
                <div class="ga-stats">
                    <div class="ga-stat">
                        <span class="ga-stat-label">Rodzaje:</span>
                        <span class="ga-stat-value"><?php echo $items_stats['unique_items']; ?></span>
                    </div>
                    <div class="ga-stat">
                        <span class="ga-stat-label">Łącznie:</span>
                        <span class="ga-stat-value"><?php echo $items_stats['total_amount']; ?></span>
                    </div>
                    <div class="ga-stat">
                        <span class="ga-stat-label">Wyposażonych:</span>
                        <span class="ga-stat-value"><?php echo $items_stats['equipped_items']; ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="ga-card-content">
            <?php if (empty($user_items)): ?>
                <div class="ga-notice ga-notice--info">
                    <p>Gracz nie posiada żadnych przedmiotów.</p>
                </div>
            <?php else: ?>
                <?php
                // Grupowanie przedmiotów wg taksonomii item_type
                $items_by_type = [];
                foreach ($user_items as $item) {
                    $type_id = !empty($item['item_type']) ? $item['item_type']->term_id : 0;
                    $type_name = !empty($item['item_type']) ? $item['item_type']->name : 'Inne przedmioty';

                    if (!isset($items_by_type[$type_id])) {
                        $items_by_type[$type_id] = [
                            'name' => $type_name,
                            'items' => []
                        ];
                    }

                    $items_by_type[$type_id]['items'][] = $item;
                }

                // Sortuj typy według nazwy
                uasort($items_by_type, function ($a, $b) {
                    return $a['name'] <=> $b['name'];
                });
                ?>

                <?php foreach ($items_by_type as $type_id => $type_data): ?>
                    <h4 class="ga-section-header"><?php echo esc_html($type_data['name']); ?></h4>
                    <table class="ga-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Ikona</th>
                                <th>Nazwa przedmiotu</th>
                                <th style="width: 120px;">Ilość</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 120px;">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($type_data['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <span class="ga-icon">
                                            <?php
                                            $icon = get_post_meta($item['item_id'], 'item_icon', true);
                                            echo $icon ?: '📦';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($item['item_name']); ?></strong>
                                    </td>
                                    <td>
                                        <form method="post" action="" class="ga-inline-form">
                                            <?php wp_nonce_field('update_item_amount', '_wpnonce_update_item'); ?>
                                            <input type="hidden" name="user_id" value="<?php echo $game_user['user_id']; ?>">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <div class="ga-form-inline">
                                                <input type="number"
                                                    name="item_new_amount"
                                                    value="<?php echo $item['amount']; ?>"
                                                    min="0"
                                                    class="ga-form-control ga-form-control--small">
                                                <button type="submit"
                                                    name="update_item_amount"
                                                    class="ga-button ga-button--small ga-button--primary"
                                                    title="Zapisz nową ilość">
                                                    💾
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" action="" class="ga-inline-form">
                                            <?php wp_nonce_field('update_item_equipped', '_wpnonce_update_equipped'); ?>
                                            <input type="hidden" name="user_id" value="<?php echo $game_user['user_id']; ?>">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <input type="hidden" name="update_item_equipped" value="1">
                                            <div class="ga-form-inline">
                                                <select name="item_equipped_status"
                                                    class="ga-form-select ga-form-select--small"
                                                    onchange="this.form.submit()"
                                                    <?php echo !$item['can_be_equipped'] ? 'disabled title="Ten przedmiot nie może być wyposażony"' : ''; ?>>
                                                    <option value="0" <?php selected($item['is_equipped'], 0); ?>>Nie wyposażony</option>
                                                    <?php if ($item['can_be_equipped']): ?>
                                                        <option value="1" <?php selected($item['is_equipped'], 1); ?>>Wyposażony</option>
                                                    <?php endif; ?>
                                                </select>
                                                <button type="submit"
                                                    class="ga-button ga-button--small ga-button--primary"
                                                    title="Zapisz status wyposażenia"
                                                    style="display: none;">
                                                    💾
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post"
                                            action=""
                                            class="ga-inline-form"
                                            onsubmit="return confirm('Czy na pewno usunąć cały przedmiot?')">
                                            <?php wp_nonce_field('remove_item', '_wpnonce_remove_item'); ?>
                                            <input type="hidden" name="user_id" value="<?php echo $game_user['user_id']; ?>">
                                            <input type="hidden" name="item_id_remove" value="<?php echo $item['item_id']; ?>">
                                            <input type="hidden" name="item_amount_remove" value="<?php echo $item['amount']; ?>">
                                            <button type="submit"
                                                name="remove_item"
                                                class="ga-button ga-button--small ga-button--danger"
                                                title="Usuń całkowicie przedmiot">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Dodawanie przedmiotu -->
            <div class="ga-form-section">
                <h4>Dodaj przedmiot do ekwipunku</h4>
                <form method="post" action="">
                    <?php wp_nonce_field('add_item', '_wpnonce_add_item'); ?>
                    <input type="hidden" name="user_id" value="<?php echo $game_user['user_id']; ?>">
                    <div class="ga-form-inline">
                        <select name="item_id" class="ga-form-select">
                            <option value="">-- Wybierz przedmiot --</option>
                            <?php foreach ($all_items as $item): ?>
                                <option value="<?php echo $item->ID; ?>"><?php echo $item->post_title; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="ga-form-group">
                            <label>Ilość:</label>
                            <input type="number"
                                name="item_amount"
                                value="1"
                                min="1"
                                class="ga-form-control ga-form-control--small">
                        </div>
                        <button type="submit"
                            name="add_item"
                            class="ga-button ga-button--success">
                            ➕ Dodaj do ekwipunku
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sekcja nawigacji -->
    <div class="ga-card-actions">
        <a href="<?php echo admin_url('admin.php?page=game-users'); ?>"
            class="ga-button ga-button--secondary">
            ← Powrót do listy
        </a>
    </div>
</div>