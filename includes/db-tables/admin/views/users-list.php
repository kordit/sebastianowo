<?php
// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Gracze</h1>

    <div class="game-admin-section">
        <?php if (!$stats['table_exists']): ?>
            <div class="notice notice-error">
                <p><strong>Uwaga!</strong> Tabela game_users nie istnieje. <a href="<?php echo admin_url('admin.php?page=game-database'); ?>">Przejdź do konfiguracji bazy danych</a></p>
            </div>
        <?php endif; ?>

        <?php if (empty($users)): ?>
            <div class="notice notice-info">
                <p>Brak graczy w bazie danych.
                    <?php if ($stats['missing'] > 0): ?>
                        <a href="<?php echo admin_url('admin.php?page=game-database'); ?>">Zaimportuj użytkowników WordPress</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <h2>Lista graczy (<?php echo count($users); ?>)</h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" style="width: 60px;">ID</th>
                        <th scope="col" style="width: 150px;">Login</th>
                        <th scope="col" style="width: 150px;">Nick gracza</th>
                        <th scope="col" style="width: 120px;">Klasa</th>
                        <th scope="col" style="width: 80px;">Poziom</th>
                        <th scope="col" style="width: 100px;">Życie</th>
                        <th scope="col" style="width: 100px;">Energia</th>
                        <th scope="col" style="width: 80px;">Złoto</th>
                        <th scope="col" style="width: 120px;">Ostatnia aktywność</th>
                        <th scope="col" style="width: 100px;">Akcje</th>
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
                            <td><?php echo esc_html($user['user_class'] ?: '—'); ?></td>
                            <td><?php echo $level; ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill health" style="width: <?php echo $user['max_life'] > 0 ? round(($user['life'] / $user['max_life']) * 100) : 0; ?>%;"></div>
                                    <span class="progress-text"><?php echo $user['life']; ?>/<?php echo $user['max_life']; ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill energy" style="width: <?php echo $user['max_energy'] > 0 ? round(($user['energy'] / $user['max_energy']) * 100) : 0; ?>%;"></div>
                                    <span class="progress-text"><?php echo $user['energy']; ?>/<?php echo $user['max_energy']; ?></span>
                                </div>
                            </td>
                            <td><?php echo number_format($user['gold']); ?></td>
                            <td>
                                <?php if ($user['updated_at']): ?>
                                    <?php echo date('d.m.Y H:i', strtotime($user['updated_at'])); ?>
                                <?php else: ?>
                                    <em>Nigdy</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=game-users&action=view&user_id=' . $user['user_id']); ?>"
                                    class="button button-small">Szczegóły</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>