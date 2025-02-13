<?php
$current_user = wp_get_current_user();
$author_url = get_author_posts_url($current_user->ID);
$current_user_id = get_current_user_id();
$avatar_id = get_field('avatar', 'user_' . $current_user_id);
$avatar = get_field('avatar', 'user_' . $current_user_id);
$nick = get_field('nick', 'user_' . $current_user_id);
$stats = get_field('stats', 'user_' . $current_user_id);

?>

<div class="wrapper">
    <div class="avatar-wrapper">
        <div class="polaroid">
            <?php
            if ($avatar && isset($avatar['ID'])) {
                echo wp_get_attachment_image($avatar['ID'], 'thumbnail'); // Możesz zmienić 'thumbnail' na inny rozmiar
            } else {
                echo 'Brak avatara';
            }
            ?>
        </div>
    </div>
    <div class="content-sidebar">
        <h4><?= $nick; ?></h4>
        <div class="wrap-bar">
            <h6>Życie</h6>
            <div class="bar-game" data-bar-max="<?= $stats['max_life']; ?>" data-bar-current="<?= $stats['life']; ?>" data-bar-color="#4caf50" data-bar-type="life"></div>

        </div>
        <div class="wrap-bar">
            <h6>Energia</h6>
            <div class="bar-game" data-bar-max="<?= $stats['max_energy']; ?>" data-bar-current="<?= $stats['energy']; ?>" data-bar-color="#ff5733" data-bar-type="energy"></div>
        </div>
        <h3 class="navigation-title">Nawigacja</h3>
        <nav>
            <ul>
                <li>
                    <a href="<?= $author_url; ?>">Postać</a>
                </li>
                <li>
                    <a href="/tereny">Dzielnice</a>
                </li>
                <li>
                    <a style="color:red;font-weight:bold;" href="<?php echo wp_logout_url(home_url()); ?>">Wyloguj</a>
                </li>
            </ul>
        </nav>
        <div class="nowt-display">
            <?php
            $user_id = get_current_user_id();
            $minerals = get_field('bag', 'user_' . $user_id);

            $resources = [
                ['name' => 'gold', 'icon' => '&#128176;', 'name_pl' => 'Hajs'],
                ['name' => 'piwo', 'icon' => '&#129516;', 'name_pl' =>  'Browary'],
                ['name' => 'papierosy', 'icon' => '&#129704;', 'name_pl' => 'Szlugi'],
            ];

            foreach ($resources as $resource): ?>
                <div class="resource-item">
                    <span><?= '<strong>' . $resource['name_pl'] . ': </strong>'; ?></span>
                    <span class="ud-bag-<?= $resource['name'] ?>">
                        <?php echo isset($minerals[$resource['name']]) ? esc_html($minerals[$resource['name']]) : 0 ?>
                    </span>
                </div>
            <?php endforeach; ?>
            <div class="ud-user_class-label">
                <?php
                $field = get_field_object('user_class', 'user_' . $user_id);
                if ($field && isset($field['value'])) {
                    // Jeśli wartość jest tablicą, pobieramy 'value'
                    $value = is_array($field['value']) ? ($field['value']['value'] ?? '') : $field['value'];
                    echo isset($field['choices'][$value]) ? $field['choices'][$value] : $value;
                }
                ?>
            </div>

        </div>
    </div>


</div>