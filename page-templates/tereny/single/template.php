<?php
$fields = get_fields(get_the_ID());
$man_image = get_field('group_man') ?: 87;
?>
<?php if (get_post()->ID == 24): ?>
    <div class="step">
        <div class="inner">
            <div class="container">
                <div class="wrap-left">
                    <h3>Wybierz avatar i uzupełnij informacje</h3>
                    <div id="avatar-selection">
                        <?php
                        $avatar_pairs = [
                            [78, 83],
                            [79, 84],
                            [80, 85],
                            [81, 86],
                            [82, 87]
                        ];
                        foreach ($avatar_pairs as $pair) {
                            $avatar_url = wp_get_attachment_image_url($pair[0], 'full');
                            $preview_url = wp_get_attachment_image_url($pair[1], 'full');
                            echo '<div class="wrapper-image polaroid">';
                            echo '<img src="' . esc_url($avatar_url) . '" class="avatar-option" data-avatar-id="' . esc_attr($pair[0]) . '" data-pair-src="' . esc_url($preview_url) . '" alt="Avatar ' . esc_attr($pair[0]) . '">';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <div class="content-section">
                        <div class="item">
                            <input type="text" id="nickname" placeholder="Wpisz pseudonim">
                        </div>
                        <div class="item">
                            <textarea id="story" placeholder="Opisz swoją postać..."></textarea>
                        </div>
                        <button id="save-character"
                            data-user-id="<?= get_current_user_id(); ?>"
                            data-nonce="<?= wp_create_nonce('update_character_nonce'); ?>">
                            Stwórz postać
                        </button>
                    </div>
                </div>

                <div class="wrapper-preview">
                    <div id="preview"><span></span></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>