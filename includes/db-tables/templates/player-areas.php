<?php

/**
 * Szablon do wyświetlania dostępnych rejonów gracza (podgląd).
 *
 * Dostępne zmienne:
 * $areas (array) - Tablica rejonów gracza.
 * $userId (int) - ID gracza (opcjonalnie).
 */
?>
<div class="postbox">
    <h3 class="hndle"><span>Dostępne rejony</span></h3>
    <div class="inside">
        <?php if (empty($areas)) : ?>
            <p>Gracz nie ma odblokowanych rejonów.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID rejonu</th>
                        <th>Nazwa rejonu (Placeholder)</th>
                        <th>Scena</th>
                        <th>Odblokowany</th>
                        <th>Data odblokowania</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($areas as $area) : ?>
                        <?php
                        // TODO: Pobierz nazwę rejonu na podstawie area_id, jeśli to możliwe
                        $areaName = 'Nazwa rejonu (' . esc_html($area['area_id']) . ')'; // Placeholder
                        $unlocked = $area['is_unlocked'] ? 'Tak' : 'Nie';
                        $unlockedAt = $area['unlocked_at'] ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($area['unlocked_at'])) : '-';
                        ?>
                        <tr>
                            <td><?php echo esc_html($area['area_id']); ?></td>
                            <td><?php echo esc_html($areaName); ?></td>
                            <td><?php echo esc_html($area['scene_id']); ?></td>
                            <td><?php echo esc_html($unlocked); ?></td>
                            <td><?php echo esc_html($unlockedAt); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>