<?php

/**
 * Szablon strony zarządzania misjami.
 *
 * Dostępne zmienne:
 * $selectedUserId (int) - ID wybranego użytkownika.
 * $playerExists (bool) - Czy gracz istnieje w systemie gry.
 * $userSelectorHTML (string) - HTML selektora użytkownika.
 * $admin_url_game_players (string) - URL do panelu gracza.
 * $missions (array) - Misje gracza (jeśli $selectedUserId i $playerExists są prawdziwe).
 * $missionManager (GameMissionManager) - Instancja managera misji.
 * $admin_post_url (string) - URL do admin-post.php.
 * $nonce_add_mission (string) - Nonce dla akcji game_add_mission.
 * $nonce_start_mission (string) - Nonce dla akcji game_start_mission.
 * $nonce_complete_mission (string) - Nonce dla akcji game_complete_mission.
 */
?>
<div class="wrap">
    <h1>Zarządzanie misjami</h1>

    <?php if (isset($_GET['message'])) : ?>
        <div id="message" class="updated notice is-dismissible">
            <p>
                <?php
                // Proste mapowanie komunikatów, można rozbudować
                $messages = [
                    'added' => 'Misja została pomyślnie dodana.',
                    'started' => 'Misja została rozpoczęta.',
                    'completed' => 'Misja została zakończona.',
                    'error' => 'Wystąpił błąd podczas operacji na misji.',
                ];
                echo esc_html($messages[$_GET['message']] ?? 'Operacja zakończona.');
                ?>
            </p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Odrzuć tę informację.</span></button>
        </div>
    <?php endif; ?>

    <div class="postbox">
        <h2 class="hndle"><span>Wybierz gracza</span></h2>
        <div class="inside">
            <?php echo $userSelectorHTML; // Wyświetlamy HTML selektora użytkownika 
            ?>
        </div>
    </div>

    <?php if ($selectedUserId) : ?>
        <?php if (!$playerExists) : ?>
            <div class="notice notice-warning">
                <p>Gracz nie ma danych w systemie gry. <a href="<?php echo esc_url($admin_url_game_players); ?>">Przejdź do panelu gracza</a>, aby utworzyć dane.</p>
            </div>
        <?php else : ?>
            <?php // Sekcja misji gracza 
            ?>
            <div class="postbox">
                <h2 class="hndle"><span>Misje gracza (ID: <?php echo esc_html($selectedUserId); ?>)</span></h2>
                <div class="inside">
                    <?php if (empty($missions)) : ?>
                        <p>Gracz nie ma żadnych misji.</p>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>ID Misji</th>
                                    <th>Status</th>
                                    <th>Postęp</th>
                                    <th>Data rozpoczęcia</th>
                                    <th>Data zakończenia</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($missions as $mission) : ?>
                                    <?php
                                    $status = $missionManager->translateStatus($mission['status']);
                                    $progress = $mission['completed_tasks'] . '/' . $mission['total_tasks'];
                                    $startDate = $mission['started_at'] ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($mission['started_at'])) : '-';
                                    $endDate = $mission['completed_at'] ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($mission['completed_at'])) : '-';
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($mission['mission_id']); ?></td>
                                        <td><?php echo esc_html($status); ?></td>
                                        <td><?php echo esc_html($progress); ?></td>
                                        <td><?php echo esc_html($startDate); ?></td>
                                        <td><?php echo esc_html($endDate); ?></td>
                                        <td>
                                            <?php if ($mission['status'] === 'active') : ?>
                                                <a href="#" class="button button-small complete-mission-btn" data-mission-id="<?php echo esc_attr($mission['mission_id']); ?>" data-user-id="<?php echo esc_attr($selectedUserId); ?>" data-nonce="<?php echo esc_attr($nonce_complete_mission); ?>">Zakończ</a>
                                            <?php endif; ?>
                                            <?php if ($mission['status'] === 'available') : ?>
                                                <a href="#" class="button button-small start-mission-btn" data-mission-id="<?php echo esc_attr($mission['mission_id']); ?>" data-user-id="<?php echo esc_attr($selectedUserId); ?>" data-nonce="<?php echo esc_attr($nonce_start_mission); ?>">Rozpocznij</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <?php // Formularz do ręcznego dodawania misji 
            ?>
            <div class="postbox">
                <h2 class="hndle"><span>Dodaj misję</span></h2>
                <div class="inside">
                    <form method="post" action="<?php echo esc_url($admin_post_url); ?>">
                        <input type="hidden" name="action" value="game_add_mission">
                        <input type="hidden" name="user_id" value="<?php echo esc_attr($selectedUserId); ?>">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_add_mission); ?>">

                        <table class="form-table">
                            <tr>
                                <th><label for="mission_id_form">ID Misji</label></th>
                                <td><input type="text" id="mission_id_form" name="mission_id" required class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="mission_status_form">Status</label></th>
                                <td>
                                    <select id="mission_status_form" name="status">
                                        <option value="available">Dostępna</option>
                                        <option value="active">Aktywna</option>
                                        <option value="completed">Zakończona</option>
                                        <option value="failed">Nieudana</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p>
                            <input type="submit" class="button button-primary" value="Dodaj misję">
                        </p>
                    </form>
                </div>
            </div>

            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    const adminPostUrl = '<?php echo esc_js($admin_post_url); ?>';

                    $('.start-mission-btn').on('click', function(e) {
                        e.preventDefault();
                        if (confirm('Czy na pewno chcesz rozpocząć tę misję?')) {
                            const missionId = $(this).data('mission-id');
                            const userId = $(this).data('user-id');
                            const nonce = $(this).data('nonce');
                            window.location.href = adminPostUrl + '?action=game_start_mission&mission_id=' + missionId + '&user_id=' + userId + '&_wpnonce=' + nonce;
                        }
                    });

                    $('.complete-mission-btn').on('click', function(e) {
                        e.preventDefault();
                        if (confirm('Czy na pewno chcesz zakończyć tę misję?')) {
                            const missionId = $(this).data('mission-id');
                            const userId = $(this).data('user-id');
                            const nonce = $(this).data('nonce');
                            window.location.href = adminPostUrl + '?action=game_complete_mission&mission_id=' + missionId + '&user_id=' + userId + '&_wpnonce=' + nonce;
                        }
                    });
                });
            </script>

        <?php endif; ?>
    <?php endif; ?>
</div>