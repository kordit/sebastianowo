<div class="row">
    <div id="step-form">
        <div id="step-1" class="step active">
            <h1>Wybierz klasę postaci</h1>
            <div id="class-selection">
                <div class="class-box" data-class="zadymiarz">
                    <div class="polaroid">
                        <?= wp_get_attachment_image(13, 'full'); ?>
                    </div>
                    <h3>ZADYMIARZ</h3>
                    <p>Nie ma co gadać – wjeżdża na pełnej, a potem się zastanawia. Niezależnie, czy to solówka, ustawka, czy spontan na osiedlu – zawsze jest gotów do akcji. Ma ciężką rękę i zero litości, a jak wjeżdża w rewir, to wszyscy robią miejsce.</p>
                    <button class="select-class">Wybierz klasę</button>
                </div>
                <div class="class-box" data-class="zlodziej">
                    <div class="polaroid">
                        <?= wp_get_attachment_image(14, 'full'); ?>
                    </div>
                    <h3>ZŁODZIAJ</h3>
                    <p>Nie pyta, bierze. Niezależnie, czy to fura, rower, czy paczka fajek z kiosku – wszystko ma swoją cenę, a on zawsze wie, komu opchnąć fanty. Śliski typ, co nigdy nie daje się złapać, a jeśli już, to ma dojścia, żeby wyjść na sucho.</p>
                    <button class="select-class">Wybierz klasę</button>
                </div>
                <div class="class-box" data-class="ogarniacz">
                    <div class="polaroid">
                        <?= wp_get_attachment_image(15, 'full'); ?>
                    </div>
                    <h3>OGARNIACZ</h3>
                    <p>Tu nie chodzi o pięści, tylko o głowę na karku. Wie, gdzie załatwić trefny towar, gdzie otworzyć melinę, a gdzie ustawić deal życia. Jak coś się dzieje na osiedlu, to on już o tym wie i pewnie ma w tym swój udział.</p>
                    <button class="select-class">Wybierz klasę</button>
                </div>
            </div>
        </div>

        <div id="step-2" class="step">
            <div class="row">
                <h1>Wybierz avatar i uzupełnij informacje</h1>
                <div id="avatar-selection">
                    <?php
                    $avatar_ids = [16, 17, 18, 19, 20, 21, 22, 23, 24, 16, 17, 18, 19, 20, 21, 22, 23, 24];
                    foreach ($avatar_ids as $avatar_id) {
                        $avatar_url = wp_get_attachment_image_url($avatar_id, 'full');
                        echo '<div class="wrapper-image polaroid">';
                        echo '<img src="' . esc_url($avatar_url) . '" class="avatar-option" data-avatar-id="' . esc_attr($avatar_id) . '" alt="Avatar ' . esc_attr($avatar_id) . '">';
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

</div>