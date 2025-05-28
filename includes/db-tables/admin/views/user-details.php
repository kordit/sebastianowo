<?php
// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Oblicz poziom
$level = max(1, floor($game_user['exp'] / 100) + 1);
?>

<div class="wrap">
    <div class="game-user-details">
        <!-- Header z gradientem -->
        <div class="user-details-header">
            <h1>
                Edycja gracza: <?php echo esc_html($game_user['nick'] ?: $wp_user->display_name); ?>
                <div class="user-id">ID: <?php echo esc_html($game_user['user_id']); ?> | Poziom: <?php echo $level; ?></div>
            </h1>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('update_game_user', '_wpnonce'); ?>
            <input type="hidden" name="user_id" value="<?php echo $game_user['user_id']; ?>">

            <div class="details-grid">
                <!-- Podstawowe informacje -->
                <div class="details-card character-card">
                    <div class="card-header character-header">
                        <h3>Podstawowe informacje</h3>
                    </div>
                    <div class="card-content">
                        <table class="form-table">
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
                                <td><input type="text" name="nick" value="<?php echo esc_attr($game_user['nick']); ?>" class="form-control"></td>
                            </tr>
                            <tr>
                                <th>Klasa postaci:</th>
                                <td>
                                    <select name="user_class" class="form-select">
                                        <option value="">Wybierz klasę</option>
                                        <option value="zadymiarz" <?php selected($game_user['user_class'], 'zadymiarz'); ?>>🔥 Zadymiarz</option>
                                        <option value="zawijacz" <?php selected($game_user['user_class'], 'zawijacz'); ?>>💨 Zawijacz</option>
                                        <option value="kombinator" <?php selected($game_user['user_class'], 'kombinator'); ?>>⚡ Kombinator</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Poziom:</th>
                                <td><?php echo $level; ?> <small>(obliczany z exp)</small></td>
                            </tr>
                            <tr>
                                <th>Doświadczenie:</th>
                                <td><input type="number" name="exp" value="<?php echo $game_user['exp']; ?>" min="0" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Punkty nauki:</th>
                                <td><input type="number" name="learning_points" value="<?php echo $game_user['learning_points']; ?>" min="0" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Reputacja:</th>
                                <td><input type="number" name="reputation" value="<?php echo $game_user['reputation']; ?>" class="form-control small"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Statystyki postaci -->
                <div class="details-card stats-card">
                    <div class="card-header stats-header">
                        <h3>Statystyki postaci</h3>
                    </div>
                    <div class="card-content">
                        <table class="form-table">
                            <tr>
                                <th>Siła:</th>
                                <td><input type="number" name="strength" value="<?php echo $game_user['strength']; ?>" min="1" max="50" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Obrona:</th>
                                <td><input type="number" name="defense" value="<?php echo $game_user['defense']; ?>" min="1" max="50" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Zręczność:</th>
                                <td><input type="number" name="dexterity" value="<?php echo $game_user['dexterity']; ?>" min="1" max="50" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Percepcja:</th>
                                <td><input type="number" name="perception" value="<?php echo $game_user['perception']; ?>" min="1" max="50" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Technika:</th>
                                <td><input type="number" name="technical" value="<?php echo $game_user['technical']; ?>" min="1" max="50" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Charyzma:</th>
                                <td><input type="number" name="charisma" value="<?php echo $game_user['charisma']; ?>" min="1" max="50" class="form-control small"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Umiejętności -->
                <div class="details-card skills-card">
                    <div class="card-header skills-header">
                        <h3>Umiejętności</h3>
                    </div>
                    <div class="card-content">
                        <table class="form-table">
                            <tr>
                                <th>Walka:</th>
                                <td><input type="number" name="combat" value="<?php echo $game_user['combat']; ?>" min="0" max="100" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Kradzież:</th>
                                <td><input type="number" name="steal" value="<?php echo $game_user['steal']; ?>" min="0" max="100" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Rzemiosło:</th>
                                <td><input type="number" name="craft" value="<?php echo $game_user['craft']; ?>" min="0" max="100" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Handel:</th>
                                <td><input type="number" name="trade" value="<?php echo $game_user['trade']; ?>" min="0" max="100" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Znajomości:</th>
                                <td><input type="number" name="relations" value="<?php echo $game_user['relations']; ?>" min="0" max="100" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Ulica:</th>
                                <td><input type="number" name="street" value="<?php echo $game_user['street']; ?>" min="0" max="100" class="form-control small"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Stan gracza -->
                <div class="details-card status-card">
                    <div class="card-header status-header">
                        <h3>Stan gracza</h3>
                    </div>
                    <div class="card-content">

                        <table class="form-table">
                            <tr>
                                <th>Życie:</th>
                                <td>
                                    <div class="field-group">
                                        <input type="number" name="life" value="<?php echo $game_user['life']; ?>" min="0" max="<?php echo $game_user['max_life']; ?>" class="form-control small">
                                        <span>/</span>
                                        <input type="number" name="max_life" value="<?php echo $game_user['max_life']; ?>" min="1" class="form-control small">
                                        <div class="progress-bar-large">
                                            <div class="progress-fill health" style="width: <?php echo ($game_user['max_life'] > 0 ? ($game_user['life'] / $game_user['max_life']) * 100 : 0); ?>%"></div>
                                            <div class="progress-text"><?php echo $game_user['life']; ?>/<?php echo $game_user['max_life']; ?></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Energia:</th>
                                <td>
                                    <div class="field-group">
                                        <input type="number" name="energy" value="<?php echo $game_user['energy']; ?>" min="0" max="<?php echo $game_user['max_energy']; ?>" class="form-control small">
                                        <span>/</span>
                                        <input type="number" name="max_energy" value="<?php echo $game_user['max_energy']; ?>" min="1" class="form-control small">
                                        <div class="progress-bar-large">
                                            <div class="progress-fill energy" style="width: <?php echo ($game_user['max_energy'] > 0 ? ($game_user['energy'] / $game_user['max_energy']) * 100 : 0); ?>%"></div>
                                            <div class="progress-text"><?php echo $game_user['energy']; ?>/<?php echo $game_user['max_energy']; ?></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Złoto:</th>
                                <td><input type="number" name="gold" value="<?php echo $game_user['gold']; ?>" min="0" class="form-control medium"></td>
                            </tr>
                            <tr>
                                <th>Papierosy:</th>
                                <td><input type="number" name="cigarettes" value="<?php echo $game_user['cigarettes']; ?>" min="0" class="form-control medium"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Lokalizacja -->
                <div class="details-card location-card">
                    <div class="card-header location-header">
                        <h3>Lokalizacja</h3>
                    </div>
                    <div class="card-content">
                        <table class="form-table">
                            <tr>
                                <th>Obecny teren:</th>
                                <td><input type="number" name="current_area_id" value="<?php echo $game_user['current_area_id']; ?>" min="0" class="form-control small"></td>
                            </tr>
                            <tr>
                                <th>Obecna scena:</th>
                                <td><input type="text" name="current_scene_id" value="<?php echo esc_attr($game_user['current_scene_id']); ?>" class="form-control"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Historia postaci -->
                <div class="details-card full-width story-card">
                    <div class="card-header story-header">
                        <h3>Historia postaci</h3>
                    </div>
                    <div class="card-content">
                        <textarea name="story_text" class="form-textarea"><?php echo esc_textarea($game_user['story_text']); ?></textarea>
                    </div>
                </div>

                <!-- Relacje z NPC -->
                <div class="details-card full-width npc-relations-card">
                    <div class="card-header npc-header">
                        <h3>Relacje z NPC</h3>
                        <?php if (!empty($user_npc_relations)): ?>
                            <div class="npc-stats">
                                <span class="npc-stat">
                                    Poznanych: <strong><?php echo count(array_filter($user_npc_relations, fn($r) => $r['is_known'])); ?></strong>
                                </span>
                                <span class="npc-stat">
                                    Pozytywnych: <strong><?php echo count(array_filter($user_npc_relations, fn($r) => $r['relation_value'] > 0)); ?></strong>
                                </span>
                                <span class="npc-stat">
                                    Negatywnych: <strong><?php echo count(array_filter($user_npc_relations, fn($r) => $r['relation_value'] < 0)); ?></strong>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <?php if (empty($user_npc_relations)): ?>
                            <p class="no-relations">Brak relacji z NPC. <a href="<?php echo admin_url('admin.php?page=game-buildery'); ?>">Zbuduj relacje w Builderach</a></p>
                        <?php else: ?>
                            <div class="npc-relations-grid">
                                <?php foreach ($user_npc_relations as $relation): ?>
                                    <div class="npc-relation-item <?php echo $relation['is_known'] ? 'known' : 'unknown'; ?>">
                                        <div class="npc-relation-header">
                                            <h4>
                                                <?php echo esc_html($npcs_by_id[$relation['npc_id']] ?? "NPC #{$relation['npc_id']}"); ?>
                                            </h4>
                                        </div>

                                        <div class="npc-relation-details">
                                            <!-- Czy poznany -->
                                            <div class="relation-field">
                                                <label class="checkbox-label">
                                                    <input type="checkbox"
                                                        name="npc_relations[<?php echo $relation['npc_id']; ?>][is_known]"
                                                        value="1"
                                                        <?php checked($relation['is_known'], 1); ?>>
                                                    <span class="checkmark"></span>
                                                    Poznany przez gracza
                                                </label>
                                            </div>

                                            <!-- Poziom relacji -->
                                            <div class="relation-field">
                                                <label>Poziom relacji (-100 do +100):</label>
                                                <div class="relation-input-group">
                                                    <input type="range"
                                                        name="npc_relations[<?php echo $relation['npc_id']; ?>][relation_value]"
                                                        value="<?php echo $relation['relation_value']; ?>"
                                                        min="-100"
                                                        max="100"
                                                        class="relation-slider"
                                                        data-npc="<?php echo $relation['npc_id']; ?>">
                                                    <input type="number"
                                                        name="npc_relations[<?php echo $relation['npc_id']; ?>][relation_value]"
                                                        value="<?php echo $relation['relation_value']; ?>"
                                                        min="-100"
                                                        max="100"
                                                        class="relation-number"
                                                        data-npc="<?php echo $relation['npc_id']; ?>">
                                                </div>
                                            </div>

                                            <!-- Bilans walk -->
                                            <div class="fights-editor">
                                                <label>Bilans walk:</label>
                                                <div class="fights-inputs">
                                                    <div class="fight-input-group">
                                                        <label>🏆 Wygrane:</label>
                                                        <input type="number"
                                                            name="npc_relations[<?php echo $relation['npc_id']; ?>][fights_won]"
                                                            value="<?php echo $relation['fights_won']; ?>"
                                                            min="0"
                                                            class="fight-input">
                                                    </div>
                                                    <div class="fight-input-group">
                                                        <label>💀 Przegrane:</label>
                                                        <input type="number"
                                                            name="npc_relations[<?php echo $relation['npc_id']; ?>][fights_lost]"
                                                            value="<?php echo $relation['fights_lost']; ?>"
                                                            min="0"
                                                            class="fight-input">
                                                    </div>
                                                    <div class="fight-input-group">
                                                        <label>🤝 Remisy:</label>
                                                        <input type="number"
                                                            name="npc_relations[<?php echo $relation['npc_id']; ?>][fights_draw]"
                                                            value="<?php echo $relation['fights_draw']; ?>"
                                                            min="0"
                                                            class="fight-input">
                                                    </div>
                                                </div>
                                                <div class="fights-total">
                                                    Łącznie walk: <strong><?php echo $relation['fights_won'] + $relation['fights_lost'] + $relation['fights_draw']; ?></strong>
                                                </div>
                                            </div>

                                            <?php if ($relation['last_interaction']): ?>
                                                <div class="last-interaction">
                                                    Ostatnia interakcja: <?php echo date('d.m.Y H:i', strtotime($relation['last_interaction'])); ?>
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

            </div>

            <!-- Sekcja submit -->
            <div class="submit-section">
                <input type="submit" name="update_game_user" class="button-primary" value="💾 Zapisz zmiany">
                <a href="<?php echo admin_url('admin.php?page=game-users'); ?>" class="button-secondary">← Powrót do listy</a>
            </div>
        </form>
    </div>
</div>