<div class="step">
    <div class="inner">
        <div class="container">
            <h1>Wybierz avatar i uzupełnij informacje</h1>
            <div id="avatar-selection">
                <?php
                $avatar_pairs = [
                    [116, 119],
                    [117, 118],
                    [136, 137]
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
            <div class="wrapper-preview">
                <div id="preview"><span>Wybierz swój avatar</span></div>
            </div>
            <div class="content-section">
                <div class="item">
                    <label>Pseudonim:</label>
                    <input type="text" id="nickname" placeholder="Wpisz pseudonim">
                </div>
                <div class="item">
                    <label>Opis postaci:</label>
                    <textarea id="story" placeholder="Opisz swoją postać..."></textarea>
                </div>
                <button id="save-character"
                    data-user-id="<?= get_current_user_id(); ?>"
                    data-nonce="<?= wp_create_nonce('update_character_nonce'); ?>">
                    Zapisz
                </button>
            </div>
        </div>
    </div>
</div>