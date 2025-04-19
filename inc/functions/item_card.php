<?php

/**
 * Funkcja generująca HTML dla karty przedmiotu
 * 
 * @param WP_Post $item Obiekt przedmiotu
 * @param int $quantity Ilość przedmiotów
 * @param bool $equipped Czy przedmiot jest założony
 * @param string $slot Slot, w którym jest założony przedmiot (jeśli jest założony)
 * @return string HTML karty przedmiotu
 */
function generate_item_card($item, $quantity = 1, $equipped = false, $slot = '')
{
    ob_start();

    // Pobierz typy przedmiotu, aby określić, do jakich slotów może być przypisany
    $item_types = wp_get_post_terms($item->ID, 'item_type', ['fields' => 'ids']);
    $can_equip_chest = in_array(3, $item_types);
    $can_equip_bottom = in_array(4, $item_types);
    $can_equip_legs = in_array(7, $item_types);
?>
    <div class="item-card <?php echo $equipped ? 'equipped' : ''; ?>" data-item-id="<?php echo $item->ID; ?>">

        <div class="item-image">
            <?php
            $thumbnail = get_the_post_thumbnail($item->ID, 'full');
            if ($thumbnail) {
                echo $thumbnail;
            } else {
                echo '<img src="' . get_template_directory_uri() . '/assets/images/png/plecak.png" alt="Przedmiot">';
            }
            ?>
        </div>
        <div class="item-info">
            <h4 class="item-name"><?php echo $item->post_title; ?></h4>
            <?php if (!$equipped) : ?>
                <div class="item-quantity">Ilość: <?php echo $quantity; ?></div>
            <?php endif; ?>
            <?php if ($equipped) : ?>
                <div class="item-equipped">Założony</div>
            <?php endif; ?>
        </div>
        <div class="item-actions">
            <?php if ($equipped) : ?>
                <button class="item-unequip" data-item-id="<?php echo $item->ID; ?>" data-slot="<?php echo $slot; ?>">Zdejmij</button>
            <?php else : ?>
                <?php if ($can_equip_chest || $can_equip_bottom || $can_equip_legs) : ?>
                    <div class="equipment-options">
                        <?php if ($can_equip_chest) : ?>
                            <button class="item-equip" data-item-id="<?php echo $item->ID; ?>" data-slot="chest_item">Załóż</button>
                        <?php endif; ?>
                        <?php if ($can_equip_bottom) : ?>
                            <button class="item-equip" data-item-id="<?php echo $item->ID; ?>" data-slot="bottom_item">Załóż</button>
                        <?php endif; ?>
                        <?php if ($can_equip_legs) : ?>
                            <button class="item-equip" data-item-id="<?php echo $item->ID; ?>" data-slot="legs_item">Załóż</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}
