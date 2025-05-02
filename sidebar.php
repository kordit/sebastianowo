<?php
$current_user = wp_get_current_user();
$author_url = get_author_posts_url($current_user->ID);
$current_user_id = get_current_user_id();
$avatar_id = get_field('avatar', 'user_' . $current_user_id);
$avatar = get_field('avatar', 'user_' . $current_user_id);
$nick = get_field('nick', 'user_' . $current_user_id);
$stats = get_field('vitality', 'user_' . $current_user_id);
$current_area = get_field('current_area', 'user_' . get_current_user_id());

?>
<aside>
    <div class="wrapper">
        <div class="avatar-wrapper">
            <div class="polaroid">
                <?php
                if ($avatar && isset($avatar['ID'])) {
                    echo wp_get_attachment_image($avatar['ID'], 'thumbnail'); // Możesz zmienić 'thumbnail' na inny rozmiar
                } else {
                    echo wp_get_attachment_image(78, 'thumbnail'); // Możesz zmienić 'thumbnail' na inny rozmiar
                }
                ?>
            </div>
        </div>
        <div class="content-sidebar">
            <h4 id="get-user-id" data-id="<?= $current_user_id; ?>"><?= $nick; ?></h4>
            <div class="wrap-bar">
                <h6>Życie</h6>
                <div class="bar-game" data-bar-max="<?= $stats['max_life']; ?>" data-bar-current="<?= $stats['life']; ?>" data-bar-color="#4caf50" data-bar-type="life"></div>

            </div>
            <div class="wrap-bar">
                <h6>Energia</h6>
                <div class="bar-game" data-bar-max="<?= $stats['max_energy']; ?>" data-bar-current="<?= $stats['energy']; ?>" data-bar-color="#ff5733" data-bar-type="energy"></div>
            </div>
        </div>
        <h5 class="navigation-title">Nawigacja</h5>
        <div class="icons">
            <a href="<?= get_permalink($current_area->ID); ?>">
                <img src="<?= esc_url(PNG . '/spacer.png'); ?>" alt="">
                <span class="icon-label">Rejon</span>
            </a>
            <a href="/plecak">
                <img src="<?= esc_url(PNG . '/plecak.png'); ?>" alt="">
                <span class="icon-label">Plecak</span>
            </a>
            <a href="/user/me">
                <img src="<?= esc_url(PNG . '/postac.png'); ?>" alt="">
                <span class="icon-label">Postać</span>
            </a> <a href="/zadania">
                <img src="<?= esc_url(PNG . '/interes.png'); ?>" alt="">
                <span class="icon-label">Zadania</span>
            </a>
            </a> <a href="">
                <img src="<?= esc_url(PNG . '/ustawienia.png'); ?>" alt="">
                <span class="icon-label">Ustawienia</span>
            </a>
        </div>
    </div>
</aside>