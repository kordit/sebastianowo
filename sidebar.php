<?php
$current_user = wp_get_current_user();
$author_url = get_author_posts_url($current_user->ID);
$current_user_id = get_current_user_id();

// Pobierz dane z nowego systemu bazy danych
$game_user = get_game_user($current_user_id);
$user_data = $game_user ? $game_user->get_basic_data() : null;

// Przygotuj dane dla kompatybilności
$avatar_id = $user_data ? $user_data['avatar_id'] : 0;
$avatar = $avatar_id ? ['ID' => $avatar_id] : null;
$nick = $user_data ? $user_data['nick'] : $current_user->display_name;

// Przygotuj dane witalności
$vitality_data = [
    'max_life' => $user_data ? $user_data['max_life'] : 100,
    'life' => $user_data ? $user_data['current_life'] : 100,
    'max_energy' => $user_data ? $user_data['max_energy'] : 100,
    'energy' => $user_data ? $user_data['current_energy'] : 100
];
$stats = $vitality_data;

$current_area = $user_data ? $user_data['current_area_id'] : null;
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
            <a href="<?= get_permalink($current_area); ?>">
                <img src="<?= esc_url(PNG . '/spacer.png'); ?>" alt="">
                <span class="icon-label">Rejon</span>
            </a>
            <a href="/backpack">
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