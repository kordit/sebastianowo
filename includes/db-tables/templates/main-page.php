<div class="wrap">
    <h1>Zarządzanie Grą RPG</h1>

    <div class="postbox">
        <h2>Status systemu</h2>
        <?php if (isset($systemStatus) && is_array($systemStatus)): ?>
            <p><strong>Tabele bazy danych:</strong> <span style="color: <?php echo esc_attr($systemStatus['statusColor']); ?>;"><?php echo esc_html($systemStatus['existingTables']); ?>/<?php echo esc_html($systemStatus['totalTables']); ?></span></p>
            <?php if (!$systemStatus['allTablesExist']): ?>
                <p><em>Niektóre tabele nie istnieją. Przejdź do zarządzania bazą danych aby je utworzyć.</em></p>
            <?php endif; ?>
        <?php else: ?>
            <p>Brak danych o statusie systemu.</p>
        <?php endif; ?>
    </div>

    <div class="postbox">
        <h2>Szybkie akcje</h2>
        <?php if (isset($quickActions) && !empty($quickActions)): ?>
            <p>
                <?php foreach ($quickActions as $action): ?>
                    <a href="<?php echo esc_url($action['url']); ?>" class="button <?php echo esc_attr($action['class']); ?>">
                        <?php echo esc_html($action['text']); ?>
                    </a>
                <?php endforeach; ?>
            </p>
        <?php else: ?>
            <p>Brak zdefiniowanych szybkich akcji.</p>
        <?php endif; ?>
    </div>
</div>