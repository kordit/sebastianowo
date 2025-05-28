<?php

/**
 * Szablon do wyświetlania relacji gracza z NPC (podgląd).
 *
 * Dostępne zmienne:
 * $relations (array) - Tablica relacji gracza.
 * $userId (int) - ID gracza (opcjonalnie).
 */
?>
<div class="postbox">
    <h3 class="hndle"><span>Relacje z NPC</span></h3>
    <div class="inside">
        <?php if (empty($relations)) : ?>
            <p>Gracz nie ma zdefiniowanych relacji z NPC.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID NPC</th>
                        <th>Nazwa NPC (Placeholder)</th>
                        <th>Wartość relacji</th>
                        <th>Znany</th>
                        <th>Walki (W/P/R)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($relations as $relation) : ?>
                        <?php
                        // TODO: Pobierz nazwę NPC na podstawie npc_id, jeśli to możliwe
                        $npcName = 'NPC (' . esc_html($relation['npc_id']) . ')'; // Placeholder
                        $known = $relation['is_known'] ? 'Tak' : 'Nie';
                        $fights = esc_html($relation['fights_won'] . '/' . $relation['fights_lost'] . '/' . $relation['fights_draw']);
                        ?>
                        <tr>
                            <td><?php echo esc_html($relation['npc_id']); ?></td>
                            <td><?php echo esc_html($npcName); ?></td>
                            <td><?php echo esc_html($relation['relation_value']); ?></td>
                            <td><?php echo esc_html($known); ?></td>
                            <td><?php echo $fights; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>