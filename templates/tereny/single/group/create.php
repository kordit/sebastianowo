<div class="controler-popup" id="create-group">
    <div class="person">
        <div class="wrappers-chat">
            <div class="chat-me">

                <h2>Czy masz na tyle odwagi, żeby na terenie "<?php the_title(); ?>" założyć siedzibę swojej grupy?</h2>
                <p class="description">Jeśli tak, to <strong>dawaj 500 złotych, kratę piwa i karton szlugów</strong> i bierz ten sqout. Dzięki niemu, nie musisz jak debil biegać po osiedlu, tylko wiesz kto i od czego jest.</p>
                <label for="group-title" class="bold">

                </label>
                <div class="page-popup">
                    <div class="close"></div>
                    <form id="create-group-form">
                        <input type="text" id="group-title" name="group-title" required>
                        <label class="check-color">Wybierz kolor swojej grupy:</label>
                        <div class="all-labeles">

                            <?php
                            $colors = [
                                "#ff0000" => "Czerwony",
                                "#00ff00" => "Zielony",
                                "#0000ff" => "Niebieski",
                                "#ffff00" => "Żółty",
                                "#ff00ff" => "Różowy",
                                "#00ffff" => "Turkusowy",
                            ];

                            foreach ($colors as $hex => $name) {
                                echo '<label>
                            <input type="radio" name="color-district" value="' . $hex . '" required> ';
                                echo '<div style="background-color: ' . $hex . '" class="preview-color"></div>';
                                echo '</label>';
                            }
                            ?>

                        </div>
                        <!-- Teren (ID terenu pochodzi z wpisu, w którym formularz jest wyświetlony) -->
                        <input type="hidden" id="teren-id" value="<?php echo get_the_ID(); ?>">
                        <!-- Ukryte pole z ID użytkownika -->
                        <input type="hidden" id="user-id" value="<?php echo get_current_user_id(); ?>">
                        <button type="submit">Załóż grupę</button>
                    </form>
                </div>
                <div id="close-controler-popup" class="btn">Wpadłem tu przypadkiem, elo!</div>
            </div>
        </div>
        <?= wp_get_attachment_image($man_image, 'full'); ?>
    </div>
</div>