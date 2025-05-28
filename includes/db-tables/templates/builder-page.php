<?php

/**
 * Szablon strony budowania danych.
 *
 * Dostępne zmienne:
 * $admin_post_url (string) - URL do admin-post.php.
 * $nonce_build_missions (string) - Nonce dla akcji game_build_missions.
 * $nonce_build_npc_relations (string) - Nonce dla akcji game_build_npc_relations.
 * $nonce_build_areas (string) - Nonce dla akcji game_build_areas.
 */
?>
<div class="wrap">
    <h1>Budowanie danych z ACF</h1>

    <?php if (isset($_GET['built_missions'])) : ?>
        <div id="message" class="updated notice is-dismissible">
            <p>Pomyślnie zbudowano <?php echo esc_html(intval($_GET['built_missions'])); ?> misji.</p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Odrzuć tę informację.</span></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['built_npc']) && isset($_GET['message'])) : ?>
        <div id="message" class="updated notice is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['message'])); ?></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Odrzuć tę informację.</span></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['built_areas']) && isset($_GET['message'])) : ?>
        <div id="message" class="updated notice is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['message'])); ?></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Odrzuć tę informację.</span></button>
        </div>
    <?php endif; ?>

    <div class="postbox">
        <h2 class="hndle"><span>Zbuduj misje</span></h2>
        <div class="inside">
            <p>Tworzy strukturę misji na podstawie Custom Post Types i pól ACF.</p>
            <form method="post" action="<?php echo esc_url($admin_post_url); ?>">
                <input type="hidden" name="action" value="game_build_missions">
                <?php // wp_nonce_field('game_build_missions'); // Nonce jest już w zmiennej 
                ?>
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_build_missions); ?>">
                <p>
                    <input type="submit" class="button button-primary" value="Zbuduj misje">
                </p>
            </form>
        </div>
    </div>

    <div class="postbox">
        <h2 class="hndle"><span>Zbuduj relacje z NPC</span></h2>
        <div class="inside">
            <p>Tworzy bazowe relacje z wszystkimi NPC dla wszystkich graczy.</p>
            <form method="post" action="<?php echo esc_url($admin_post_url); ?>">
                <input type="hidden" name="action" value="game_build_npc_relations">
                <?php // wp_nonce_field('game_build_npc_relations'); 
                ?>
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_build_npc_relations); ?>">
                <p>
                    <input type="submit" class="button button-primary" value="Zbuduj relacje NPC">
                </p>
            </form>
        </div>
    </div>

    <div class="postbox">
        <h2 class="hndle"><span>Zbuduj dostępne rejony</span></h2>
        <div class="inside">
            <p>Tworzy strukturę rejonów i scen na podstawie CPT terenów.</p>
            <form method="post" action="<?php echo esc_url($admin_post_url); ?>">
                <input type="hidden" name="action" value="game_build_areas">
                <?php // wp_nonce_field('game_build_areas'); 
                ?>
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_build_areas); ?>">
                <p>
                    <input type="submit" class="button button-primary" value="Zbuduj rejony">
                </p>
            </form>
        </div>
    </div>
</div>