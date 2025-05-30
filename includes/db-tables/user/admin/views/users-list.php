<?php
// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ga-container">
    <!-- Header -->
    <div class="ga-header">
        <h1 class="ga-header__title">👥 Gracze</h1>
        <p class="ga-header__subtitle">Zarządzanie kontami graczy</p>
        <?php if (!empty($users)): ?>
            <div class="ga-header__meta">Lista <?php echo count($users); ?> graczy</div>
        <?php endif; ?>
    </div>

    <!-- Status i powiadomienia -->
    <?php if (!$stats['table_exists']): ?>
        <div class="ga-notice ga-notice--danger">
            <div class="ga-notice__icon">⚠️</div>
            <div>
                <p><strong>Uwaga!</strong> Tabela game_users nie istnieje. <a href="<?php echo admin_url('admin.php?page=game-database'); ?>">Przejdź do konfiguracji bazy danych</a></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($users)): ?>
        <div class="ga-notice ga-notice--info">
            <div class="ga-notice__icon">ℹ️</div>
            <div>
                <p>Brak graczy w bazie danych.
                    <?php if ($stats['missing'] > 0): ?>
                        <a href="<?php echo admin_url('admin.php?page=game-database'); ?>" class="ga-button ga-button--primary ga-button--small">Zaimportuj użytkowników WordPress</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <!-- Lista graczy -->
        <div class="ga-card ga-card--full">
            <div class="ga-card__header">
                <h3 class="ga-card__title">Lista graczy</h3>
                <div class="ga-card__meta">
                    <span class="ga-badge ga-badge--info"><?php echo count($users); ?> graczy</span>
                </div>
            </div>
            <div class="ga-card__content ga-card__content--compact">
                <table class="ga-table ga-table--compact">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 150px;">Login</th>
                            <th style="width: 150px;">Nick gracza</th>
                            <th style="width: 120px;">Klasa</th>
                            <th style="width: 80px;">Poziom</th>
                            <th style="width: 100px;">Życie</th>
                            <th style="width: 100px;">Energia</th>
                            <th style="width: 80px;">Złoto</th>
                            <th style="width: 120px;">Ostatnia aktywność</th>
                            <th style="width: 100px;">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php
                            // Oblicz poziom na podstawie doświadczenia (przykładowa formuła)
                            $level = max(1, floor($user['exp'] / 100) + 1);
                            ?>
                            <tr>
                                <td><?php echo esc_html($user['user_id']); ?></td>
                                <td>
                                    <strong><?php echo esc_html($user['user_login'] ?: 'Brak loginu'); ?></strong>
                                    <?php if ($user['user_email']): ?>
                                        <br><small><?php echo esc_html($user['user_email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($user['nick'] ?: '—'); ?></td>
                                <td>
                                    <?php if ($user['user_class']): ?>
                                        <span class="ga-badge ga-badge--info"><?php echo esc_html($user['user_class']); ?></span>
                                    <?php else: ?>
                                        <span class="ga-text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $level; ?></strong></td>
                                <td>
                                    <div class="ga-progress">
                                        <div class="ga-progress__fill ga-progress__fill--health" style="width: <?php echo $user['max_life'] > 0 ? round(($user['life'] / $user['max_life']) * 100) : 0; ?>%;"></div>
                                        <div class="ga-progress__text"><?php echo $user['life']; ?>/<?php echo $user['max_life']; ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="ga-progress">
                                        <div class="ga-progress__fill ga-progress__fill--energy" style="width: <?php echo $user['max_energy'] > 0 ? round(($user['energy'] / $user['max_energy']) * 100) : 0; ?>%;"></div>
                                        <div class="ga-progress__text"><?php echo $user['energy']; ?>/<?php echo $user['max_energy']; ?></div>
                                    </div>
                                </td>
                                <td><strong><?php echo number_format($user['gold']); ?></strong></td>
                                <td>
                                    <?php if ($user['updated_at']): ?>
                                        <?php echo date('d.m.Y H:i', strtotime($user['updated_at'])); ?>
                                    <?php else: ?>
                                        <em class="ga-text-muted">Nigdy</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=game-users&action=view&user_id=' . $user['user_id']); ?>"
                                        class="ga-button ga-button--primary ga-button--small">Szczegóły</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>