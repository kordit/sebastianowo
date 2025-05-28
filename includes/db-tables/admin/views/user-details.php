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
        Szczegóły gracza: <?php echo esc_html($game_user['nick'] ?: $wp_user->display_name); ?>
        <a href="<?php echo admin_url('admin.php?page=game-users'); ?>" class="page-title-action">← Powrót do listy</a>
    </h1>

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
                        <td><?php echo esc_html($game_user['nick'] ?: '—'); ?></td>
                    </tr>
                    <tr>
                        <th>Klasa postaci:</th>
                        <td><?php echo esc_html($game_user['user_class'] ?: '—'); ?></td>
                    </tr>
                    <tr>
                        <th>Poziom:</th>
                        <td><?php echo $level; ?></td>
                    </tr>
                    <tr>
                        <th>Doświadczenie:</th>
                        <td><?php echo number_format($game_user['exp']); ?></td>
                    </tr>
                    <tr>
                        <th>Punkty nauki:</th>
                        <td><?php echo $game_user['learning_points']; ?></td>
                    </tr>
                    <tr>
                        <th>Reputacja:</th>
                        <td><?php echo $game_user['reputation']; ?></td>
                    </tr>
                </table>
            </div>

            <!-- Statystyki postaci -->
            <div class="details-card">
                <h3>Statystyki postaci</h3>
                <table class="form-table">
                    <tr>
                        <th>Siła:</th>
                        <td><?php echo $game_user['strength']; ?></td>
                    </tr>
                    <tr>
                        <th>Obrona:</th>
                        <td><?php echo $game_user['defense']; ?></td>
                    </tr>
                    <tr>
                        <th>Zręczność:</th>
                        <td><?php echo $game_user['dexterity']; ?></td>
                    </tr>
                    <tr>
                        <th>Percepcja:</th>
                        <td><?php echo $game_user['perception']; ?></td>
                    </tr>
                    <tr>
                        <th>Technika:</th>
                        <td><?php echo $game_user['technical']; ?></td>
                    </tr>
                    <tr>
                        <th>Charyzma:</th>
                        <td><?php echo $game_user['charisma']; ?></td>
                    </tr>
                </table>
            </div>

            <!-- Umiejętności -->
            <div class="details-card">
                <h3>Umiejętności</h3>
                <table class="form-table">
                    <tr>
                        <th>Walka:</th>
                        <td><?php echo $game_user['combat']; ?></td>
                    </tr>
                    <tr>
                        <th>Kradzież:</th>
                        <td><?php echo $game_user['steal']; ?></td>
                    </tr>
                    <tr>
                        <th>Rzemiosło:</th>
                        <td><?php echo $game_user['craft']; ?></td>
                    </tr>
                    <tr>
                        <th>Handel:</th>
                        <td><?php echo $game_user['trade']; ?></td>
                    </tr>
                    <tr>
                        <th>Znajomości:</th>
                        <td><?php echo $game_user['relations']; ?></td>
                    </tr>
                    <tr>
                        <th>Ulica:</th>
                        <td><?php echo $game_user['street']; ?></td>
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
                            <div class="progress-bar-large">
                                <div class="progress-fill health" style="width: <?php echo $game_user['max_life'] > 0 ? round(($game_user['life'] / $game_user['max_life']) * 100) : 0; ?>%;"></div>
                                <span class="progress-text"><?php echo $game_user['life']; ?>/<?php echo $game_user['max_life']; ?></span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Energia:</th>
                        <td>
                            <div class="progress-bar-large">
                                <div class="progress-fill energy" style="width: <?php echo $game_user['max_energy'] > 0 ? round(($game_user['energy'] / $game_user['max_energy']) * 100) : 0; ?>%;"></div>
                                <span class="progress-text"><?php echo $game_user['energy']; ?>/<?php echo $game_user['max_energy']; ?></span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Złoto:</th>
                        <td><?php echo number_format($game_user['gold']); ?></td>
                    </tr>
                    <tr>
                        <th>Papierosy:</th>
                        <td><?php echo number_format($game_user['cigarettes']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Lokalizacja -->
            <div class="details-card">
                <h3>Lokalizacja</h3>
                <table class="form-table">
                    <tr>
                        <th>Obecny teren:</th>
                        <td><?php echo $game_user['current_area_id'] ? 'Teren #' . $game_user['current_area_id'] : 'Brak'; ?></td>
                    </tr>
                    <tr>
                        <th>Obecna scena:</th>
                        <td><?php echo esc_html($game_user['current_scene_id'] ?: '—'); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Historia postaci -->
            <div class="details-card full-width">
                <h3>Historia postaci</h3>
                <div class="story-text">
                    <?php if ($game_user['story_text']): ?>
                        <?php echo nl2br(esc_html($game_user['story_text'])); ?>
                    <?php else: ?>
                        <em>Brak historii postaci</em>
                    <?php endif; ?>
                </div>
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
    </div>
</div>