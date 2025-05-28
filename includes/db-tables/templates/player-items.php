<?php

/**
 * Szablon do wyświetlania przedmiotów gracza (podgląd).
 *
 * Dostępne zmienne:
 * $items (array) - Tablica przedmiotów gracza.
 * $userId (int) - ID gracza (opcjonalnie, jeśli potrzebne do np. linków).
 */
?>
<div class="postbox">
    <h3 class="hndle"><span>Przedmioty gracza</span></h3>
    <div class="inside">
        <?php if (empty($items)) : ?>
            <p>Gracz nie ma żadnych przedmiotów.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID przedmiotu</th>
                        <th>Nazwa (Placeholder)</th>
                        <th>Ilość</th>
                        <th>Założony</th>
                        <th>Slot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) : ?>
                        <?php
                        // TODO: Pobierz nazwę przedmiotu na podstawie item_id, jeśli to możliwe
                        // $itemName = get_the_title($item['item_id']); // Przykład, jeśli item_id to ID posta
                        $itemName = 'Nazwa przedmiotu (' . $item['item_id'] . ')'; // Placeholder
                        $equipped = $item['is_equipped'] ? 'Tak' : 'Nie';
                        ?>
                        <tr>
                            <td><?php echo esc_html($item['item_id']); ?></td>
                            <td><?php echo esc_html($itemName); ?></td>
                            <td><?php echo esc_html($item['quantity']); ?></td>
                            <td><?php echo esc_html($equipped); ?></td>
                            <td><?php echo esc_html($item['equipment_slot'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>