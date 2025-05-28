<?php

/**
 * Szablon tabeli graczy.
 *
 * Dostępne zmienne:
 * $users (array) - Tablica obiektów WP_User.
 * $totalUsers (int) - Całkowita liczba użytkowników pasujących do kryteriów.
 * $totalPages (int) - Całkowita liczba stron paginacji.
 * $currentPage (int) - Aktualna strona.
 * $search (string) - Aktualne wyszukiwane hasło.
 * $pageSlug (string) - Slug strony dla linków paginacji.
 * $this (GameAdminPanel) - Instancja klasy GameAdminPanel.
 */
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Gracze</h1>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($pageSlug); ?>" />
        <?php // search_box('Szukaj graczy', 'player-search-input'); - standardowe pole wyszukiwania WP, można użyć jeśli pasuje 
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="player-search-input">Szukaj graczy:</label>
            <input type="search" id="player-search-input" name="s" value="<?php echo esc_attr($search); ?>">
            <input type="submit" id="search-submit" class="button" value="Szukaj graczy">
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped users">
        <thead>
            <tr>
                <th scope="col" id="username" class="manage-column column-username column-primary sortable desc">
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'login', 'order' => (isset($_GET['orderby']) && $_GET['orderby'] == 'login' && isset($_GET['order']) && $_GET['order'] == 'asc') ? 'desc' : 'asc'])); ?>">
                        <span>Nazwa użytkownika</span><span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" id="name" class="manage-column column-name">Nazwa wyświetlana</th>
                <th scope="col" id="email" class="manage-column column-email sortable desc">
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'email', 'order' => (isset($_GET['orderby']) && $_GET['orderby'] == 'email' && isset($_GET['order']) && $_GET['order'] == 'asc') ? 'desc' : 'asc'])); ?>">
                        <span>Email</span><span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" id="role" class="manage-column column-role">Rola</th>
                <th scope="col" id="game_data" class="manage-column">Dane w grze</th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (!empty($users)) : ?>
                <?php foreach ($users as $user) : ?>
                    <tr>
                        <td class="username column-username column-primary">
                            <?php echo get_avatar($user->ID, 32); ?>
                            <strong><a href="<?php echo esc_url(admin_url('admin.php?page=game-players&user_id=' . $user->ID)); ?>"><?php echo esc_html($user->user_login); ?></a></strong>
                            <br>
                            <div class="row-actions">
                                <span class="edit"><a href="<?php echo esc_url(admin_url('admin.php?page=game-players&user_id=' . $user->ID)); ?>">Edytuj dane gry</a> | </span>
                                <span class="view"><a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>" aria-label="Edytuj profil użytkownika <?php echo esc_attr($user->display_name); ?>">Profil WP</a></span>
                            </div>
                        </td>
                        <td class="name column-name"><?php echo esc_html($user->display_name); ?></td>
                        <td class="email column-email"><a href="mailto:<?php echo esc_attr($user->user_email); ?>"><?php echo esc_html($user->user_email); ?></a></td>
                        <td class="role column-role"><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                        <td class="game_data column-game_data">
                            <?php
                            // Sprawdzenie, czy gracz ma dane w systemie gry
                            // Ta logika powinna być przekazana z GameAdminPanel, np. jako tablica asocjacyjna user_id => has_game_data
                            // Dla uproszczenia, na razie zostawiamy to jako placeholder
                            // if ($this->userRepo->playerExists($user->ID)) {
                            //     echo '<span style="color:green">Posiada</span>';
                            // } else {
                            //     echo '<span style="color:orange">Brak</span>';
                            // }
                            echo '-'; // Placeholder
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr class="no-items">
                    <td class="colspanchange" colspan="5">Nie znaleziono użytkowników.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-username column-primary">Nazwa użytkownika</th>
                <th scope="col" class="manage-column column-name">Nazwa wyświetlana</th>
                <th scope="col" class="manage-column column-email">Email</th>
                <th scope="col" class="manage-column column-role">Rola</th>
                <th scope="col" class="manage-column">Dane w grze</th>
            </tr>
        </tfoot>
    </table>

    <?php if ($totalPages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo esc_html($totalUsers); ?> graczy</span>
                <span class="pagination-links">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $totalPages,
                        'current' => $currentPage,
                        'add_args' => ['s' => $search] // Dodajemy parametr wyszukiwania do linków paginacji
                    ]);
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>