<?php

/**
 * Szablon edytora gracza.
 *
 * Dostępne zmienne:
 * $playerExists (bool) - Czy gracz istnieje w systemie gry.
 * $userData (array|null) - Dane gracza z tabel gry.
 * $user (WP_User) - Obiekt użytkownika WordPress.
 * $userId (int) - ID użytkownika.
 * $this (GameAdminPanel) - Instancja klasy GameAdminPanel, aby móc wywoływać metody pomocnicze.
 */

if (!$playerExists) : ?>
    <div class="notice notice-warning">
        <p>Ten użytkownik nie ma jeszcze utworzonych danych w systemie gry.</p>
    </div>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="game_update_player">
        <input type="hidden" name="user_id" value="<?php echo esc_attr($userId); ?>">
        <?php wp_nonce_field('game_update_player'); ?>
        <p>
            <input type="submit" class="button button-primary" value="Utwórz dane gracza">
        </p>
    </form>
<?php else : ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="game_update_player">
        <input type="hidden" name="user_id" value="<?php echo esc_attr($userId); ?>">
        <?php wp_nonce_field('game_update_player'); ?>

        <div class="postbox">
            <h2 class="hndle">Dane podstawowe</h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><label for="display_name">Nazwa wyświetlana</label></th>
                        <td><input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" class="regular-text" readonly></td>
                    </tr>
                    <tr>
                        <th><label for="user_email">Email</label></th>
                        <td><input type="email" id="user_email" name="user_email" value="<?php echo esc_attr($user->user_email); ?>" class="regular-text" readonly></td>
                    </tr>
                    <!-- Dodaj więcej pól podstawowych jeśli potrzebne -->
                </table>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle">Statystyki</h2>
            <div class="inside">
                <table class="form-table">
                    <?php
                    $stats = $userData['stats'] ?? [];
                    // Przykładowe statystyki, dostosuj do swoich potrzeb
                    $stat_keys = ['health' => 'Zdrowie', 'mana' => 'Mana', 'strength' => 'Siła', 'defense' => 'Obrona'];
                    foreach ($stat_keys as $key => $label) : ?>
                        <tr>
                            <th><label for="stat_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                            <td><input type="number" id="stat_<?php echo esc_attr($key); ?>" name="stats[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($stats[$key] ?? 0); ?>" class="small-text"></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle">Umiejętności</h2>
            <div class="inside">
                <table class="form-table">
                    <?php
                    $skills = $userData['skills'] ?? [];
                    // Przykładowe umiejętności, dostosuj do swoich potrzeb
                    $skill_keys = ['sword' => 'Walka mieczem', 'magic' => 'Magia', 'archery' => 'Łucznictwo'];
                    foreach ($skill_keys as $key => $label) : ?>
                        <tr>
                            <th><label for="skill_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                            <td><input type="number" id="skill_<?php echo esc_attr($key); ?>" name="skills[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($skills[$key] ?? 0); ?>" class="small-text"></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <p>
            <input type="submit" class="button button-primary" value="Zapisz podstawowe dane">
        </p>
    </form>

    <hr>

    <h2>Rozszerzone zarządzanie</h2>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="game_update_player_extended">
        <input type="hidden" name="user_id" value="<?php echo esc_attr($userId); ?>">
        <?php wp_nonce_field('game_update_player_extended'); ?>

        <?php
        // Sekcja Przedmiotów
        if (isset($gameAdminPanel) && method_exists($gameAdminPanel, 'renderPlayerItems')) {
            $gameAdminPanel->renderPlayerItems($userId);
        } else {
            echo "<p>Problem z załadowaniem sekcji przedmiotów.</p>";
        }
        ?>

        <?php
        // Sekcja Rejonów
        if (isset($gameAdminPanel) && method_exists($gameAdminPanel, 'renderPlayerAreas')) {
            $gameAdminPanel->renderPlayerAreas($userId);
        } else {
            echo "<p>Problem z załadowaniem sekcji rejonów.</p>";
        }
        ?>

        <?php
        // Sekcja Relacji NPC
        if (isset($gameAdminPanel) && method_exists($gameAdminPanel, 'renderPlayerRelations')) {
            $gameAdminPanel->renderPlayerRelations($userId);
        } else {
            echo "<p>Problem z załadowaniem sekcji relacji NPC.</p>";
        }
        ?>

        <?php
        // Sekcja Wyników Walk - do implementacji, jeśli potrzebne
        // if (isset($gameAdminPanel) && method_exists($gameAdminPanel, 'renderPlayerFightResults')) {
        //     $gameAdminPanel->renderPlayerFightResults($userId);
        // } else {
        //     echo "<p>Problem z załadowaniem sekcji wyników walk.</p>";
        // }
        ?>

        <p>
            <input type="submit" class="button button-primary" value="Zapisz rozszerzone dane">
        </p>
    </form>

    <hr>
    <h2>Podgląd danych (bezpośrednio z bazy)</h2>
    <?php
    // Te sekcje będą renderowane przez metody w GameAdminPanel, które z kolei będą używać szablonów
    if (isset($gameAdminPanel)) {
        $gameAdminPanel->renderPlayerItems($userId);
        $gameAdminPanel->renderPlayerAreas($userId);
        $gameAdminPanel->renderPlayerRelations($userId);
    } else {
        echo "<p>Nie można załadować podglądu danych - brak instancji GameAdminPanel.</p>";
    }
    ?>
    <p><em>Sekcje podglądu przedmiotów, rejonów i relacji zostaną dodane po utworzeniu odpowiednich szablonów.</em></p>


<?php endif; ?>
<p><a href="<?php echo esc_url(admin_url('admin.php?page=game-players')); ?>" class="button">&laquo; Wróć do listy graczy</a></p>