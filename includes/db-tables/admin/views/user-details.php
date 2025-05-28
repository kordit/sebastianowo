<?php
// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Oblicz poziom
$level = max(1, floor($game_user['exp'] / 100) + 1);
?>

<div class="wrap">
    <h1>
        Edycja gracza: <?php echo esc_html($game_user['nick'] ?: $wp_user->display_name); ?>
        <a href="<?php echo admin_url('admin.php?page=game-users'); ?>" class="page-title-action">← Powrót do listy</a>
    </h1>

    <form method="post" action="">
        <?php wp_nonce_field('update_game_user', '_wpnonce'); ?>
        <input type="hidden" name="user_id" value="<?php echo $game_user['user_id']; ?>">

        <div class="game-user-details">
            <div class="details-grid">
                <!-- Podstawowe informacje -->
                <div class="details-card">
                    <h3>Podstawowe informacje</h3>
                    <table class="form-table">
                        <tr>
                            <th>ID gracza:</th>
                            <td><?php echo esc_html($game_user['user_id']); ?></td>
                        </tr>
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
                            <td><input type="text" name="nick" value="<?php echo esc_attr($game_user['nick']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th>Klasa postaci:</th>
                            <td>
                                <select name="user_class">
                                    <option value="">Wybierz klasę</option>
                                    <option value="zadymiarz" <?php selected($game_user['user_class'], 'zadymiarz'); ?>>Zadymiarz</option>
                                    <option value="zawijacz" <?php selected($game_user['user_class'], 'zawijacz'); ?>>Zawijacz</option>
                                    <option value="kombinator" <?php selected($game_user['user_class'], 'kombinator'); ?>>Kombinator</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Poziom:</th>
                            <td><?php echo $level; ?> <small>(obliczany z exp)</small></td>
                        </tr>
                        <tr>
                            <th>Doświadczenie:</th>
                            <td><input type="number" name="exp" value="<?php echo $game_user['exp']; ?>" min="0" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Punkty nauki:</th>
                            <td><input type="number" name="learning_points" value="<?php echo $game_user['learning_points']; ?>" min="0" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Reputacja:</th>
                            <td><input type="number" name="reputation" value="<?php echo $game_user['reputation']; ?>" class="small-text"></td>
                        </tr>
                    </table>
                </div>

                <!-- Statystyki postaci -->
                <div class="details-card">
                    <h3>Statystyki postaci</h3>
                    <table class="form-table">
                        <tr>
                            <th>Siła:</th>
                            <td><input type="number" name="strength" value="<?php echo $game_user['strength']; ?>" min="1" max="50" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Obrona:</th>
                            <td><input type="number" name="defense" value="<?php echo $game_user['defense']; ?>" min="1" max="50" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Zręczność:</th>
                            <td><input type="number" name="dexterity" value="<?php echo $game_user['dexterity']; ?>" min="1" max="50" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Percepcja:</th>
                            <td><input type="number" name="perception" value="<?php echo $game_user['perception']; ?>" min="1" max="50" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Technika:</th>
                            <td><input type="number" name="technical" value="<?php echo $game_user['technical']; ?>" min="1" max="50" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Charyzma:</th>
                            <td><input type="number" name="charisma" value="<?php echo $game_user['charisma']; ?>" min="1" max="50" class="small-text"></td>
                        </tr>
                    </table>
                </div>

                <!-- Umiejętności -->
                <div class="details-card">
                    <h3>Umiejętności</h3>
                    <table class="form-table">
                        <tr>
                            <th>Walka:</th>
                            <td><input type="number" name="combat" value="<?php echo $game_user['combat']; ?>" min="0" max="100" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Kradzież:</th>
                            <td><input type="number" name="steal" value="<?php echo $game_user['steal']; ?>" min="0" max="100" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Rzemiosło:</th>
                            <td><input type="number" name="craft" value="<?php echo $game_user['craft']; ?>" min="0" max="100" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Handel:</th>
                            <td><input type="number" name="trade" value="<?php echo $game_user['trade']; ?>" min="0" max="100" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Znajomości:</th>
                            <td><input type="number" name="relations" value="<?php echo $game_user['relations']; ?>" min="0" max="100" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Ulica:</th>
                            <td><input type="number" name="street" value="<?php echo $game_user['street']; ?>" min="0" max="100" class="small-text"></td>
                        </tr>
                    </table>
                </div>

                <!-- Stan postaci -->
                <div class="details-card">
                    <h3>Stan postaci</h3>
                    <table class="form-table">
                        <tr>
                            <th>Życie:</th>
                            <td>
                                <input type="number" name="life" value="<?php echo $game_user['life']; ?>" min="0" max="<?php echo $game_user['max_life']; ?>" class="small-text"> /
                                <input type="number" name="max_life" value="<?php echo $game_user['max_life']; ?>" min="1" class="small-text">
                            </td>
                        </tr>
                        <tr>
                            <th>Energia:</th>
                            <td>
                                <input type="number" name="energy" value="<?php echo $game_user['energy']; ?>" min="0" max="<?php echo $game_user['max_energy']; ?>" class="small-text"> /
                                <input type="number" name="max_energy" value="<?php echo $game_user['max_energy']; ?>" min="1" class="small-text">
                            </td>
                        </tr>
                        <tr>
                            <th>Złoto:</th>
                            <td><input type="number" name="gold" value="<?php echo $game_user['gold']; ?>" min="0" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th>Papierosy:</th>
                            <td><input type="number" name="cigarettes" value="<?php echo $game_user['cigarettes']; ?>" min="0" class="regular-text"></td>
                        </tr>
                    </table>
                </div>

                <!-- Lokalizacja -->
                <div class="details-card">
                    <h3>Lokalizacja</h3>
                    <table class="form-table">
                        <tr>
                            <th>Obecny teren:</th>
                            <td><input type="number" name="current_area_id" value="<?php echo $game_user['current_area_id']; ?>" min="0" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Obecna scena:</th>
                            <td><input type="text" name="current_scene_id" value="<?php echo esc_attr($game_user['current_scene_id']); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                </div>

                <!-- Historia postaci -->
                <div class="details-card full-width">
                    <h3>Historia postaci</h3>
                    <textarea name="story_text" rows="6" class="large-text"><?php echo esc_textarea($game_user['story_text']); ?></textarea>
                </div>

                <!-- Aktywność -->
                <div class="details-card full-width">
                    <h3>Aktywność</h3>
                    <table class="form-table">
                        <tr>
                            <th>Utworzono:</th>
                            <td><?php echo date('d.m.Y H:i:s', strtotime($game_user['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Ostatnia aktualizacja:</th>
                            <td>
                                <?php if ($game_user['updated_at']): ?>
                                    <?php echo date('d.m.Y H:i:s', strtotime($game_user['updated_at'])); ?>
                                <?php else: ?>
                                    <em>Nigdy</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="submit-section">
                <input type="submit" name="update_game_user" class="button-primary" value="Zapisz zmiany">
                <a href="<?php echo admin_url('admin.php?page=game-users'); ?>" class="button">Anuluj</a>
            </div>
        </div>
    </form>
</div>