<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Sebastianowo - <?php the_title(); ?></title>
    <?php wp_head(); ?>
</head>

<body <?php body_class('body'); ?>>
    <?php if (is_user_logged_in()): ?>
        <header>
            <!-- <h3 class="navigation-title">Nawigacja</h3> -->
            <!-- <nav>
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
            </nav> -->
            <div class="nowt-display">
                <?php
                $user_id = get_current_user_id();
                $minerals = get_field('backpack', 'user_' . $user_id);

                $resources = [
                    ['name' => 'gold', 'icon' => '&#128176;', 'name_pl' => 'Hajs', 'icon_url' => PNG . '/hajs.png'],
                    ['name' => 'papierosy', 'icon' => '&#129704;', 'name_pl' => 'Szlugi', 'icon_url' => PNG . '/szlug.png'],
                    ['name' => 'piwo', 'icon' => '&#129516;', 'name_pl' => 'Browary', 'icon_url' => PNG . '/browar.png'],
                    // ['name' => 'marihuana', 'icon' => '&#129704;', 'name_pl' => 'Zioło', 'icon_url' => PNG . '/weed.png'],
                ];

                foreach ($resources as $resource): ?>
                    <div class="resource-item">
                        <img src="<?= esc_url($resource['icon_url']) ?>" alt="<?= esc_attr($resource['name']) ?>" class="resource-icon" />
                        <div class="wrap">
                            <span><?= '<strong>' . $resource['name_pl'] . ': </strong>'; ?></span>
                            <span class="ud-backpack-<?= $resource['name'] ?>">
                                <?= isset($minerals[$resource['name']]) ? esc_html($minerals[$resource['name']]) : 0 ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>


                <!-- <div class="ud-user_class-label">
                    <?php
                    $field = get_field_object('user_class', 'user_' . $user_id);
                    if ($field && isset($field['value'])) {
                        // Jeśli wartość jest tablicą, pobieramy 'value'
                        $value = is_array($field['value']) ? ($field['value']['value'] ?? '') : $field['value'];
                        echo isset($field['choices'][$value]) ? $field['choices'][$value] : $value;
                    }
                    ?>
                </div> -->
            </div>
        </header>

    <?php endif; ?>