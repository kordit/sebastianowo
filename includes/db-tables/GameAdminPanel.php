<?php

/**
 * Panel administracyjny dla danych gracza
 * 
 * Dodaje strony w admin panelu do przeglądania
 * danych użytkowników z customowych tabel
 */
class GameAdminPanel
{
    private static $instance = null;

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_init', [$this, 'handle_form_submissions']);
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Dodaje menu w panelu administracyjnym
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'Dane Graczy',
            'Dane Graczy',
            'manage_options',
            'game-users-data',
            [$this, 'render_users_list'],
            'dashicons-groups',
            25
        );

        add_submenu_page(
            'game-users-data',
            'Lista Graczy',
            'Lista Graczy',
            'manage_options',
            'game-users-data',
            [$this, 'render_users_list']
        );

        add_submenu_page(
            'game-users-data',
            'Szczegóły Gracza',
            'Szczegóły Gracza',
            'manage_options',
            'game-user-details',
            [$this, 'render_user_details']
        );

        add_submenu_page(
            'game-users-data',
            'Ustawienia Bazy',
            'Ustawienia Bazy',
            'manage_options',
            'game-database-settings',
            [$this, 'render_database_settings']
        );
    }

    /**
     * Ładuje skrypty CSS/JS dla admin panelu
     */
    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'game-') !== false) {
            wp_enqueue_style('game-admin-css', get_template_directory_uri() . '/includes/db-tables/admin-style.css');
        }
    }

    /**
     * Obsługuje przesyłanie formularzy w panelu administracyjnym
     */
    public function handle_form_submissions()
    {
        // Sprawdź czy jesteśmy w panelu admina
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        // Obsługa aktualizacji danych użytkownika
        if (isset($_POST['action']) && $_POST['action'] === 'update_user_data') {
            $this->handle_user_data_update();
            return;
        }

        // Obsługa aktualizacji danych misji
        if (isset($_POST['action']) && $_POST['action'] === 'update_mission_tasks') {
            $this->handle_mission_update();
            return;
        }

        // Obsługa tworzenia nowej misji dla użytkownika
        if (isset($_POST['action']) && $_POST['action'] === 'create_user_mission') {
            $this->handle_create_mission();
            return;
        }
    }
    /**
     * Obsługuje aktualizację danych użytkownika
     */
    private function handle_user_data_update()
    {
        // Sprawdź nonce dla bezpieczeństwa
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update_user_data')) {
            wp_die('Błąd bezpieczeństwa');
        }

        // Sprawdź dane
        if (empty($_POST['user_id'])) {
            wp_die('Brakujące dane użytkownika');
        }

        $user_id = intval($_POST['user_id']);
        $game_user = new GameUserModel($user_id);

        // Przygotuj dane do aktualizacji podstawowych informacji
        $basic_data = [];
        $allowed_basic_fields = [
            'nick',
            'user_class',
            'strength',
            'defense',
            'dexterity',
            'perception',
            'technical',
            'charisma',
            'max_life',
            'current_life',
            'max_energy',
            'current_energy',
            'exp',
            'learning_points',
            'reputation',
            'gold',
            'cigarettes',
            'graphic_1',
            'graphic_2'
        ];

        foreach ($allowed_basic_fields as $field) {
            if (isset($_POST[$field])) {
                $basic_data[$field] = sanitize_text_field($_POST[$field]);
            }
        }

        // Przygotuj dane umiejętności
        $skills_data = [];
        $allowed_skill_fields = ['combat', 'steal', 'craft', 'trade', 'relations', 'street'];

        foreach ($allowed_skill_fields as $field) {
            if (isset($_POST[$field])) {
                $skills_data[$field] = intval($_POST[$field]);
            }
        }

        // Aktualizuj dane
        $basic_result = !empty($basic_data) ? $game_user->update_basic_data($basic_data) : true;
        $skills_result = !empty($skills_data) ? $game_user->update_skills_data($skills_data) : true;

        // Przekieruj z komunikatem
        $redirect_url = add_query_arg([
            'page' => 'game-user-details',
            'user_id' => $user_id,
            'updated' => ($basic_result !== false && $skills_result !== false) ? 'success' : 'error'
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Obsługuje aktualizację danych misji użytkownika
     */
    private function handle_mission_update()
    {
        // Sprawdź nonce dla bezpieczeństwa
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update_mission_tasks')) {
            wp_die('Błąd bezpieczeństwa');
        }

        // Sprawdź dane
        if (empty($_POST['mission_id']) || empty($_POST['user_id'])) {
            wp_die('Brakujące dane');
        }

        $mission_id = intval($_POST['mission_id']);
        $user_id = intval($_POST['user_id']);

        // Pobierz dane misji
        global $wpdb;
        $tables = GameDatabaseManager::get_instance()->get_table_names();
        $mission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tables['user_missions']} WHERE id = %d AND user_id = %d",
            $mission_id,
            $user_id
        ), ARRAY_A);

        if (!$mission) {
            wp_die('Misja nie istnieje');
        }

        // Pobierz aktualne dane zadań
        $tasks_data = json_decode($mission['tasks_data'], true);
        if (!is_array($tasks_data)) {
            $tasks_data = [];
        }

        // Aktualizuj dane zadań
        $updated_tasks = [];
        if (!empty($_POST['tasks']) && is_array($_POST['tasks'])) {
            foreach ($_POST['tasks'] as $task_index => $task_update) {
                if (!isset($tasks_data[$task_index]) || $tasks_data[$task_index]['task_id'] !== $task_update['task_id']) {
                    continue; // Bezpieczeństwo - upewnij się, że indeksy się zgadzają
                }

                $task = $tasks_data[$task_index];

                // Aktualizuj status
                if (isset($task_update['status'])) {
                    $task['status'] = sanitize_text_field($task_update['status']);
                }

                // Aktualizuj licznik prób
                if (isset($task_update['attempts'])) {
                    $task['attempts'] = intval($task_update['attempts']);
                }

                // Aktualizuj odwiedzenie lokacji
                if ($task['task_type'] == 'checkpoint' && isset($task_update['location_visited'])) {
                    $task['location_visited'] = (bool) $task_update['location_visited'];
                }

                // Aktualizuj statusy NPC
                if ($task['task_type'] == 'checkpoint_npc' && !empty($task_update['npcs']) && is_array($task_update['npcs'])) {
                    foreach ($task_update['npcs'] as $npc_index => $npc_update) {
                        if (isset($task['npcs'][$npc_index]) && $task['npcs'][$npc_index]['npc_id'] == $npc_update['npc_id']) {
                            $task['npcs'][$npc_index]['current_status'] = sanitize_text_field($npc_update['current_status']);
                        }
                    }
                }

                // Aktualizuj status pokonania przeciwników
                if ($task['task_type'] == 'defeat_enemies' && !empty($task_update['enemies']) && is_array($task_update['enemies'])) {
                    foreach ($task_update['enemies'] as $enemy_index => $enemy_update) {
                        if (isset($task['enemies'][$enemy_index]) && $task['enemies'][$enemy_index]['enemy_id'] == $enemy_update['enemy_id']) {
                            $task['enemies'][$enemy_index]['defeated'] = (bool) $enemy_update['defeated'];
                        }
                    }
                }

                $updated_tasks[$task_index] = $task;
            }
        }

        // Połącz zaktualizowane dane z oryginalnymi
        foreach ($updated_tasks as $index => $task) {
            $tasks_data[$index] = $task;
        }

        // Aktualizuj statystyki walk
        $wins = isset($_POST['mission_stats']['wins']) ? intval($_POST['mission_stats']['wins']) : $mission['wins'];
        $losses = isset($_POST['mission_stats']['losses']) ? intval($_POST['mission_stats']['losses']) : $mission['losses'];
        $draws = isset($_POST['mission_stats']['draws']) ? intval($_POST['mission_stats']['draws']) : $mission['draws'];

        // Aktualizuj status misji
        $status = isset($_POST['mission_status']) ? sanitize_text_field($_POST['mission_status']) : $mission['status'];

        // Ustaw datę zakończenia jeśli misja została ukończona lub nieudana
        $end_date = null;
        if (in_array($status, ['completed', 'failed']) && empty($mission['end_date'])) {
            $end_date = current_time('mysql');
        }

        // Zapisz zmiany
        $update_data = [
            'tasks_data' => json_encode($tasks_data),
            'status' => $status,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws
        ];

        if ($end_date) {
            $update_data['end_date'] = $end_date;
        }

        $result = $wpdb->update(
            $tables['user_missions'],
            $update_data,
            ['id' => $mission_id, 'user_id' => $user_id]
        );

        // Przekieruj z komunikatem
        $redirect_url = add_query_arg([
            'page' => 'game-user-details',
            'user_id' => $user_id,
            'updated' => $result !== false ? 'success' : 'error'
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Obsługuje tworzenie nowej misji dla użytkownika
     */
    private function handle_create_mission()
    {
        // Sprawdź nonce dla bezpieczeństwa
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'create_user_mission')) {
            wp_die('Błąd bezpieczeństwa');
        }

        // Sprawdź dane
        if (empty($_POST['mission_post_id']) || empty($_POST['user_id'])) {
            wp_die('Brakujące dane');
        }

        $mission_post_id = intval($_POST['mission_post_id']);
        $user_id = intval($_POST['user_id']);

        // Użyj klasy MissionUserModel do utworzenia misji
        require_once(get_template_directory() . '/includes/db-tables/MissionUserModel.php');
        $mission_model = new GameMissionUserModel($user_id);
        $result = $mission_model->create_mission($mission_post_id);

        // Przekieruj z komunikatem
        $redirect_url = add_query_arg([
            'page' => 'game-user-details',
            'user_id' => $user_id,
            'mission_created' => $result !== false ? 'success' : 'error'
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Renderuje listę wszystkich graczy
     */
    public function render_users_list()
    {
        global $wpdb;

        // Pobierz wszystkich użytkowników WordPress
        $wp_users = get_users(['fields' => ['ID', 'user_login', 'user_email', 'display_name']]);

        // Pobierz dane z customowych tabel
        $db_manager = GameDatabaseManager::get_instance();
        $tables = $db_manager->get_table_names();

        // Sprawdź czy tabele istnieją
        if (!$db_manager->tables_exist()) {
            echo '<div class="notice notice-warning"><p>Tabele gry nie istnieją. <a href="?page=game-database-settings">Utwórz je tutaj</a>.</p></div>';
            return;
        }

        $game_users = $wpdb->get_results(
            "SELECT * FROM {$tables['users']} ORDER BY updated_at DESC",
            ARRAY_A
        );

        // Konwertuj do tablicy indeksowanej po ID
        $game_users_by_id = [];
        foreach ($game_users as $game_user) {
            $game_users_by_id[$game_user['id']] = $game_user;
        }

?>
        <div class="wrap game-admin-wrap">
            <h1>Lista Graczy</h1>

            <div class="game-stats-overview">
                <div class="stat-box">
                    <h3>Użytkownicy WordPress</h3>
                    <span class="stat-number"><?php echo count($wp_users); ?></span>
                </div>
                <div class="stat-box">
                    <h3>Gracze z danymi</h3>
                    <span class="stat-number"><?php echo count($game_users); ?></span>
                </div>
                <div class="stat-box">
                    <h3>Aktywność</h3>
                    <span class="stat-number"><?php echo $this->get_active_users_count(); ?></span>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Login</th>
                        <th>Nick w grze</th>
                        <th>Klasa</th>
                        <th>Level</th>
                        <th>Reputacja</th>
                        <th>Ostatnia aktywność</th>
                        <th>Status</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wp_users as $wp_user): ?>
                        <?php
                        $has_game_data = isset($game_users_by_id[$wp_user->ID]);
                        $game_data = $has_game_data ? $game_users_by_id[$wp_user->ID] : null;
                        ?>
                        <tr class="<?php echo $has_game_data ? 'has-game-data' : 'no-game-data'; ?>">
                            <td><?php echo $wp_user->ID; ?></td>
                            <td>
                                <strong><?php echo esc_html($wp_user->user_login); ?></strong><br>
                                <small><?php echo esc_html($wp_user->user_email); ?></small>
                            </td>
                            <td>
                                <?php if ($has_game_data): ?>
                                    <?php echo esc_html($game_data['nick'] ?: 'Brak nicku'); ?>
                                <?php else: ?>
                                    <span class="no-data">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($has_game_data): ?>
                                    <span class="user-class <?php echo esc_attr($game_data['user_class']); ?>">
                                        <?php echo esc_html($game_data['user_class'] ?: 'Brak klasy'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="no-data">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($has_game_data): ?>
                                    <?php echo $this->calculate_level($game_data['exp']); ?>
                                <?php else: ?>
                                    <span class="no-data">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($has_game_data): ?>
                                    <span class="reputation-badge reputation-<?php echo $this->get_reputation_class($game_data['reputation']); ?>">
                                        <?php echo $game_data['reputation']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="no-data">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($has_game_data): ?>
                                    <?php echo $this->format_time_ago($game_data['updated_at']); ?>
                                <?php else: ?>
                                    <span class="no-data">Nigdy</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($has_game_data): ?>
                                    <span class="status-active">Aktywny</span>
                                <?php else: ?>
                                    <span class="status-inactive">Nieaktywny</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?page=game-user-details&user_id=<?php echo $wp_user->ID; ?>" class="button button-small">
                                    Szczegóły
                                </a>
                                <?php if (!$has_game_data): ?>
                                    <br><small><a href="?page=game-user-details&user_id=<?php echo $wp_user->ID; ?>&action=init">Inicjalizuj</a></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php
    }

    /**
     * Renderuje szczegóły konkretnego gracza
     */
    public function render_user_details()
    {
        $user_id = intval($_GET['user_id'] ?? 0);
        $action = $_GET['action'] ?? '';

        if (!$user_id) {
            echo '<div class="notice notice-error"><p>Nieprawidłowe ID użytkownika.</p></div>';
            return;
        }

        $wp_user = get_user_by('ID', $user_id);
        if (!$wp_user) {
            echo '<div class="notice notice-error"><p>Użytkownik nie istnieje.</p></div>';
            return;
        }

        $game_user = new GameUserModel($user_id);

        // Wyświetl komunikaty z URL
        if (isset($_GET['updated'])) {
            if ($_GET['updated'] === 'success') {
                echo '<div class="notice notice-success"><p>Dane zostały pomyślnie zaktualizowane.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Wystąpił błąd podczas aktualizacji danych.</p></div>';
            }
        }

        // Obsługa inicjalizacji
        if ($action === 'init') {
            if ($game_user->initialize_new_user()) {
                echo '<div class="notice notice-success"><p>Dane gracza zostały zainicjalizowane.</p></div>';
            } else {
                echo '<div class="notice notice-info"><p>Gracz już ma dane w systemie.</p></div>';
            }
        }

        $user_data = $game_user->get_user_data();

    ?>
        <div class="wrap game-admin-wrap">
            <h1>Szczegóły Gracza: <?php echo esc_html($wp_user->display_name); ?></h1>

            <div class="user-details-header">
                <div class="user-avatar">
                    <?php echo get_avatar($user_id, 64); ?>
                </div>
                <div class="user-info">
                    <h2><?php echo esc_html($wp_user->user_login); ?></h2>
                    <p><?php echo esc_html($wp_user->user_email); ?></p>
                    <p><strong>WordPress ID:</strong> <?php echo $user_id; ?></p>
                </div>
            </div>

            <?php if ($user_data['basic']): ?>

                <!-- Podstawowe dane z witalnoscia -->
                <div class="game-data-section">
                    <h3>Dane Podstawowe</h3>
                    <form method="post" action="" class="user-data-form">
                        <?php wp_nonce_field('update_user_data'); ?>
                        <input type="hidden" name="action" value="update_user_data">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                        <table class="form-table">
                            <tr>
                                <th>Nick w grze</th>
                                <td>
                                    <input type="text" name="nick" value="<?php echo esc_attr($user_data['basic']['nick'] ?: ''); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th>Klasa</th>
                                <td>
                                    <select name="user_class">
                                        <option value="">Brak klasy</option>
                                        <option value="zadymiarz" <?php selected($user_data['basic']['user_class'], 'zadymiarz'); ?>>Zadymiarz</option>
                                        <option value="kombinator" <?php selected($user_data['basic']['user_class'], 'kombinator'); ?>>Kombinator</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Level</th>
                                <td>
                                    <strong><?php echo $this->calculate_level($user_data['basic']['exp']); ?></strong>
                                    <small>(na podstawie doświadczenia)</small>
                                </td>
                            </tr>
                            <tr>
                                <th>Doświadczenie</th>
                                <td>
                                    <input type="number" name="exp" value="<?php echo $user_data['basic']['exp']; ?>" min="0" class="small-text"> XP
                                </td>
                            </tr>
                            <tr>
                                <th>Punkty nauki</th>
                                <td>
                                    <input type="number" name="learning_points" value="<?php echo $user_data['basic']['learning_points']; ?>" min="0" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th>Reputacja</th>
                                <td>
                                    <input type="number" name="reputation" value="<?php echo $user_data['basic']['reputation']; ?>" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th>Życie</th>
                                <td>
                                    <input type="number" name="current_life" value="<?php echo $user_data['basic']['current_life']; ?>" min="0" class="small-text"> /
                                    <input type="number" name="max_life" value="<?php echo $user_data['basic']['max_life']; ?>" min="1" class="small-text">
                                    <div class="vitality-progress">
                                        <div class="progress-bar life" style="width: <?php echo ($user_data['basic']['current_life'] / $user_data['basic']['max_life']) * 100; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Energia</th>
                                <td>
                                    <input type="number" name="current_energy" value="<?php echo $user_data['basic']['current_energy']; ?>" min="0" class="small-text"> /
                                    <input type="number" name="max_energy" value="<?php echo $user_data['basic']['max_energy']; ?>" min="1" class="small-text">
                                    <div class="vitality-progress">
                                        <div class="progress-bar energy" style="width: <?php echo ($user_data['basic']['current_energy'] / $user_data['basic']['max_energy']) * 100; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Złoto</th>
                                <td>
                                    <input type="number" name="gold" value="<?php echo $user_data['basic']['gold'] ?? 0; ?>" min="0" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th>Papierosy</th>
                                <td>
                                    <input type="number" name="cigarettes" value="<?php echo $user_data['basic']['cigarettes'] ?? 0; ?>" min="0" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th>Grafika 1</th>
                                <td>
                                    <div class="media-upload-field">
                                        <input type="hidden" name="graphic_1" value="<?php echo esc_attr($user_data['basic']['graphic_1'] ?? ''); ?>" id="graphic_1_id">
                                        <button type="button" class="button media-upload-btn" data-target="graphic_1_id">Wybierz obraz</button>
                                        <div class="media-preview" id="graphic_1_preview">
                                            <?php if (!empty($user_data['basic']['graphic_1'])): ?>
                                                <?php echo wp_get_attachment_image($user_data['basic']['graphic_1'], 'thumbnail'); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Grafika 2</th>
                                <td>
                                    <div class="media-upload-field">
                                        <input type="hidden" name="graphic_2" value="<?php echo esc_attr($user_data['basic']['graphic_2'] ?? ''); ?>" id="graphic_2_id">
                                        <button type="button" class="button media-upload-btn" data-target="graphic_2_id">Wybierz obraz</button>
                                        <div class="media-preview" id="graphic_2_preview">
                                            <?php if (!empty($user_data['basic']['graphic_2'])): ?>
                                                <?php echo wp_get_attachment_image($user_data['basic']['graphic_2'], 'thumbnail'); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="submit" name="submit" class="button-primary" value="Zapisz zmiany">
                        </p>
                    </form>
                </div>

                <!-- Statystyki -->
                <div class="game-data-section">
                    <h3>Statystyki</h3>
                    <form method="post" action="" class="user-data-form">
                        <?php wp_nonce_field('update_user_data'); ?>
                        <input type="hidden" name="action" value="update_user_data">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                        <table class="form-table">
                            <tr>
                                <th>Siła</th>
                                <td><input type="number" name="strength" value="<?php echo $user_data['basic']['strength']; ?>" min="1" class="small-text"></td>
                            </tr>
                            <tr>
                                <th>Obrona</th>
                                <td><input type="number" name="defense" value="<?php echo $user_data['basic']['defense']; ?>" min="1" class="small-text"></td>
                            </tr>
                            <tr>
                                <th>Zręczność</th>
                                <td><input type="number" name="dexterity" value="<?php echo $user_data['basic']['dexterity']; ?>" min="1" class="small-text"></td>
                            </tr>
                            <tr>
                                <th>Percepcja</th>
                                <td><input type="number" name="perception" value="<?php echo $user_data['basic']['perception']; ?>" min="1" class="small-text"></td>
                            </tr>
                            <tr>
                                <th>Technika</th>
                                <td><input type="number" name="technical" value="<?php echo $user_data['basic']['technical']; ?>" min="1" class="small-text"></td>
                            </tr>
                            <tr>
                                <th>Charyzma</th>
                                <td><input type="number" name="charisma" value="<?php echo $user_data['basic']['charisma']; ?>" min="1" class="small-text"></td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="submit" name="submit" class="button-primary" value="Zapisz statystyki">
                        </p>
                    </form>
                </div>

                <!-- Umiejętności -->
                <div class="game-data-section">
                    <h3>Umiejętności</h3>
                    <form method="post" action="">
                        <?php wp_nonce_field('update_user_data'); ?>
                        <input type="hidden" name="action" value="update_user_data">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                        <table class="form-table">
                            <tr>
                                <th>Walka</th>
                                <td>
                                    <input type="number" name="combat" value="<?php echo $user_data['skills']['combat']; ?>" min="0" class="small-text">
                                    <p class="description">Zwiększa obrażenia, inicjatywę</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Kradzież</th>
                                <td>
                                    <input type="number" name="steal" value="<?php echo $user_data['skills']['steal']; ?>" min="0" class="small-text">
                                    <p class="description">Większa skuteczność, mniejsze ryzyko</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Produkcja</th>
                                <td>
                                    <input type="number" name="craft" value="<?php echo $user_data['skills']['craft']; ?>" min="0" class="small-text">
                                    <p class="description">Krótszy czas, więcej towaru</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Handel</th>
                                <td>
                                    <input type="number" name="trade" value="<?php echo $user_data['skills']['trade']; ?>" min="0" class="small-text">
                                    <p class="description">Lepsze ceny, więcej zarobku</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Relacje</th>
                                <td>
                                    <input type="number" name="relations" value="<?php echo $user_data['skills']['relations']; ?>" min="0" class="small-text">
                                    <p class="description">Bonusy, unikalne misje</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Uliczna wiedza</th>
                                <td>
                                    <input type="number" name="street" value="<?php echo $user_data['skills']['street']; ?>" min="0" class="small-text">
                                    <p class="description">Dostęp do sekretnych przejść, schowków</p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="submit" name="submit" class="button-primary" value="Zapisz umiejętności">
                        </p>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Zarządzanie przedmiotami -->
            <div class="game-data-section">
                <h3>Zarządzanie przedmiotami</h3>

                <!-- Waluty (już obsługiwane w podstawowych danych) -->
                <div class="items-currencies">
                    <h4>Waluty</h4>
                    <table class="form-table">
                        <tr>
                            <th>Złoto</th>
                            <td><strong><?php echo $user_data['basic']['gold'] ?? 0; ?></strong> (edytowalne w sekcji podstawowych danych)</td>
                        </tr>
                        <tr>
                            <th>Papierosy</th>
                            <td><strong><?php echo $user_data['basic']['cigarettes'] ?? 0; ?></strong> (edytowalne w sekcji podstawowych danych)</td>
                        </tr>
                    </table>
                </div>

                <!-- Założone przedmioty -->
                <div class="items-equipped">
                    <h4>Założone przedmioty</h4>
                    <?php
                    $equipped_items = get_field('equipped_items', 'user_' . $user_id) ?: [];
                    $equipped_slots = ['chest_item' => 'Na klatę', 'bottom_item' => 'Na poślady', 'legs_item' => 'Na giczuły'];
                    ?>
                    <table class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th>Slot</th>
                                <th>Przedmiot</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipped_slots as $slot_key => $slot_name): ?>
                                <tr>
                                    <td><strong><?php echo $slot_name; ?></strong></td>
                                    <td>
                                        <?php if (!empty($equipped_items[$slot_key])): ?>
                                            <?php
                                            $equipped_item = get_post($equipped_items[$slot_key]);
                                            if ($equipped_item): ?>
                                                <strong><?php echo esc_html($equipped_item->post_title); ?></strong>
                                                <br><small>ID: <?php echo $equipped_item->ID; ?></small>
                                            <?php else: ?>
                                                <em>Przedmiot nie istnieje (ID: <?php echo $equipped_items[$slot_key]; ?>)</em>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <em>Pusty slot</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($equipped_items[$slot_key])): ?>
                                            <button type="button" class="button item-unequip-btn"
                                                data-user-id="<?php echo $user_id; ?>"
                                                data-item-id="<?php echo $equipped_items[$slot_key]; ?>"
                                                data-slot="<?php echo $slot_key; ?>">
                                                Zdejmij
                                            </button>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Wszystkie przedmioty w ekwipunku -->
                <div class="items-inventory">
                    <h4>Ekwipunek</h4>
                    <?php
                    $user_items = get_field('items', 'user_' . $user_id) ?: [];
                    if (!empty($user_items)): ?>
                        <table class="wp-list-table widefat">
                            <thead>
                                <tr>
                                    <th>Przedmiot</th>
                                    <th>Ilość</th>
                                    <th>Kategoria</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_items as $index => $user_item): ?>
                                    <?php
                                    $item = $user_item['item'];
                                    $quantity = $user_item['quantity'] ?? 1;
                                    $item_categories = wp_get_post_terms($item->ID, 'item_type', ['fields' => 'names']);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($item->post_title); ?></strong>
                                            <br><small>ID: <?php echo $item->ID; ?></small>
                                        </td>
                                        <td>
                                            <input type="number"
                                                class="item-quantity-input small-text"
                                                value="<?php echo $quantity; ?>"
                                                min="0"
                                                data-user-id="<?php echo $user_id; ?>"
                                                data-item-id="<?php echo $item->ID; ?>"
                                                data-index="<?php echo $index; ?>">
                                        </td>
                                        <td><?php echo !empty($item_categories) ? implode(', ', $item_categories) : 'Brak kategorii'; ?></td>
                                        <td>
                                            <button type="button" class="button button-small item-remove-btn"
                                                data-user-id="<?php echo $user_id; ?>"
                                                data-item-id="<?php echo $item->ID; ?>"
                                                data-index="<?php echo $index; ?>">
                                                Usuń
                                            </button>
                                            <?php if (in_array(3, wp_get_post_terms($item->ID, 'item_type', ['fields' => 'ids']))): ?>
                                                <button type="button" class="button button-small item-equip-btn"
                                                    data-user-id="<?php echo $user_id; ?>"
                                                    data-item-id="<?php echo $item->ID; ?>"
                                                    data-slot="chest_item">
                                                    Załóż na klatę
                                                </button>
                                            <?php endif; ?>
                                            <?php if (in_array(4, wp_get_post_terms($item->ID, 'item_type', ['fields' => 'ids']))): ?>
                                                <button type="button" class="button button-small item-equip-btn"
                                                    data-user-id="<?php echo $user_id; ?>"
                                                    data-item-id="<?php echo $item->ID; ?>"
                                                    data-slot="bottom_item">
                                                    Załóż na poślady
                                                </button>
                                            <?php endif; ?>
                                            <?php if (in_array(7, wp_get_post_terms($item->ID, 'item_type', ['fields' => 'ids']))): ?>
                                                <button type="button" class="button button-small item-equip-btn"
                                                    data-user-id="<?php echo $user_id; ?>"
                                                    data-item-id="<?php echo $item->ID; ?>"
                                                    data-slot="legs_item">
                                                    Załóż na giczuły
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><em>Ekwipunek jest pusty</em></p>
                    <?php endif; ?>
                </div>

                <!-- Dodawanie nowych przedmiotów -->
                <div class="items-add">
                    <h4>Dodaj przedmiot</h4>
                    <form method="post" class="item-management-form" style="margin-top: 15px;">
                        <?php wp_nonce_field('manage_user_items', 'item_management_nonce'); ?>
                        <input type="hidden" name="action" value="add_item">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                        <table class="form-table">
                            <tr>
                                <th>Przedmiot</th>
                                <td>
                                    <select name="item_id" required style="min-width: 300px;">
                                        <option value="">Wybierz przedmiot...</option>
                                        <?php
                                        $all_items = get_posts([
                                            'post_type' => 'item',
                                            'posts_per_page' => -1,
                                            'post_status' => 'publish',
                                            'orderby' => 'title',
                                            'order' => 'ASC'
                                        ]);
                                        foreach ($all_items as $item): ?>
                                            <option value="<?php echo $item->ID; ?>"><?php echo esc_html($item->post_title); ?> (ID: <?php echo $item->ID; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Ilość</th>
                                <td>
                                    <input type="number" name="quantity" value="1" min="1" max="999" class="small-text" required>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="submit" name="submit" class="button-primary" value="Dodaj przedmiot">
                        </p>
                    </form>
                </div>
            </div>

            <!-- Misje -->
            <?php if (!empty($user_data['missions'])): ?>
                <div class="game-data-section">
                    <h3>Misje (<?php echo count($user_data['missions']); ?>)</h3>
                    <table class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th>Misja</th>
                                <th>Status</th>
                                <th>Typ</th>
                                <th>Rozpoczęta</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_data['missions'] as $mission): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($mission['mission_name'] ?: 'Nieznana misja'); ?></strong>
                                        <br><small>ID: <?php echo $mission['mission_id']; ?></small>
                                    </td>
                                    <td>
                                        <span class="mission-status status-<?php echo esc_attr($mission['status']); ?>">
                                            <?php
                                            $statuses = [
                                                'not_started' => 'Niezaczęta',
                                                'in_progress' => 'W trakcie',
                                                'completed' => 'Ukończona',
                                                'failed' => 'Nieudana'
                                            ];
                                            echo esc_html($statuses[$mission['status']] ?? $mission['status']);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $types = [
                                            'one-time' => 'Jednorazowa',
                                            'daily' => 'Dzienna',
                                            'weekly' => 'Tygodniowa'
                                        ];
                                        echo esc_html($types[$mission['mission_type']] ?? $mission['mission_type']);
                                        ?>
                                    </td>
                                    <td><?php echo $mission['start_date'] ? date('d.m.Y H:i', strtotime($mission['start_date'])) : $this->format_time_ago($mission['created_at']); ?></td>
                                    <td>
                                        <button type="button" class="button button-primary show-mission-details" data-mission-id="<?php echo $mission['id']; ?>">
                                            Szczegóły
                                        </button>
                                    </td>
                                </tr>
                                <tr class="mission-details-row hidden" id="mission-details-<?php echo $mission['id']; ?>">
                                    <td colspan="5">
                                        <div class="mission-details-container">
                                            <h4>Szczegóły misji "<?php echo esc_html($mission['mission_name']); ?>"</h4>

                                            <div class="mission-stats">
                                                <p><strong>Bilans walk:</strong> Wygrane: <?php echo $mission['wins']; ?>, Przegrane: <?php echo $mission['losses']; ?>, Remisy: <?php echo $mission['draws']; ?></p>
                                            </div>

                                            <?php if (!empty($mission['tasks_data'])): ?>
                                                <div class="mission-tasks">
                                                    <h5>Zadania:</h5>
                                                    <?php
                                                    $tasks = json_decode($mission['tasks_data'], true);
                                                    if (is_array($tasks)):
                                                    ?>
                                                        <form method="post" class="mission-tasks-form">
                                                            <input type="hidden" name="action" value="update_mission_tasks">
                                                            <input type="hidden" name="mission_id" value="<?php echo $mission['id']; ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                                            <table class="wp-list-table widefat striped mission-tasks-table">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Zadanie</th>
                                                                        <th>Typ</th>
                                                                        <th>Status</th>
                                                                        <th>Szczegóły</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($tasks as $task_index => $task): ?>
                                                                        <tr>
                                                                            <td>
                                                                                <strong><?php echo esc_html($task['task_title']); ?></strong>
                                                                                <?php if (!empty($task['task_optional'])): ?>
                                                                                    <span class="optional-tag">(Opcjonalne)</span>
                                                                                <?php endif; ?>
                                                                                <br>
                                                                                <small><?php echo esc_html($task['task_description']); ?></small>
                                                                            </td>
                                                                            <td>
                                                                                <?php
                                                                                $task_types = [
                                                                                    'checkpoint' => 'Checkpoint',
                                                                                    'checkpoint_npc' => 'Rozmowa z NPC',
                                                                                    'defeat_enemies' => 'Walka'
                                                                                ];
                                                                                echo $task_types[$task['task_type']] ?? $task['task_type'];
                                                                                ?>
                                                                            </td>
                                                                            <td>
                                                                                <select name="tasks[<?php echo $task_index; ?>][status]" class="task-status-select">
                                                                                    <option value="not_started" <?php selected($task['status'], 'not_started'); ?>>Niezaczęte</option>
                                                                                    <option value="in_progress" <?php selected($task['status'], 'in_progress'); ?>>W trakcie</option>
                                                                                    <option value="completed" <?php selected($task['status'], 'completed'); ?>>Ukończone</option>
                                                                                    <option value="failed" <?php selected($task['status'], 'failed'); ?>>Nieudane</option>
                                                                                </select>
                                                                                <input type="hidden" name="tasks[<?php echo $task_index; ?>][task_id]" value="<?php echo $task['task_id']; ?>">
                                                                            </td>
                                                                            <td>
                                                                                <?php if ($task['task_type'] == 'checkpoint'): ?>
                                                                                    <p>Lokalizacja: <?php echo $task['location_id'] ? get_the_title($task['location_id']) : '—'; ?></p>
                                                                                    <p>Scena: <?php echo $task['scene'] ?: '—'; ?></p>
                                                                                    <p>
                                                                                        <input type="checkbox" name="tasks[<?php echo $task_index; ?>][location_visited]" value="1" <?php checked(!empty($task['location_visited'])); ?>>
                                                                                        Lokacja odwiedzona
                                                                                    </p>
                                                                                <?php elseif ($task['task_type'] == 'checkpoint_npc'): ?>
                                                                                    <?php if (!empty($task['npcs']) && is_array($task['npcs'])): ?>
                                                                                        <table class="wp-list-table widefat">
                                                                                            <thead>
                                                                                                <tr>
                                                                                                    <th>NPC</th>
                                                                                                    <th>Wymagany status</th>
                                                                                                    <th>Obecny status</th>
                                                                                                </tr>
                                                                                            </thead>
                                                                                            <tbody>
                                                                                                <?php foreach ($task['npcs'] as $npc_index => $npc): ?>
                                                                                                    <tr>
                                                                                                        <td><?php echo get_the_title($npc['npc_id']); ?></td>
                                                                                                        <td>
                                                                                                            <?php
                                                                                                            $npc_statuses = [
                                                                                                                'not_started' => 'Niezaczęte',
                                                                                                                'in_progress' => 'W trakcie',
                                                                                                                'completed' => 'Ukończone',
                                                                                                                'failed' => 'Nieudane'
                                                                                                            ];
                                                                                                            echo $npc_statuses[$npc['required_status']] ?? $npc['required_status'];
                                                                                                            ?>
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <select name="tasks[<?php echo $task_index; ?>][npcs][<?php echo $npc_index; ?>][current_status]">
                                                                                                                <option value="not_started" <?php selected($npc['current_status'], 'not_started'); ?>>Niezaczęte</option>
                                                                                                                <option value="in_progress" <?php selected($npc['current_status'], 'in_progress'); ?>>W trakcie</option>
                                                                                                                <option value="completed" <?php selected($npc['current_status'], 'completed'); ?>>Ukończone</option>
                                                                                                                <option value="failed" <?php selected($npc['current_status'], 'failed'); ?>>Nieudane</option>
                                                                                                            </select>
                                                                                                            <input type="hidden" name="tasks[<?php echo $task_index; ?>][npcs][<?php echo $npc_index; ?>][npc_id]" value="<?php echo $npc['npc_id']; ?>">
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                <?php endforeach; ?>
                                                                                            </tbody>
                                                                                        </table>
                                                                                    <?php else: ?>
                                                                                        <p>Brak zdefiniowanych NPC</p>
                                                                                    <?php endif; ?>
                                                                                <?php elseif ($task['task_type'] == 'defeat_enemies'): ?>
                                                                                    <?php if (!empty($task['enemies']) && is_array($task['enemies'])): ?>
                                                                                        <table class="wp-list-table widefat">
                                                                                            <thead>
                                                                                                <tr>
                                                                                                    <th>Przeciwnik</th>
                                                                                                    <th>Status</th>
                                                                                                </tr>
                                                                                            </thead>
                                                                                            <tbody>
                                                                                                <?php foreach ($task['enemies'] as $enemy_index => $enemy): ?>
                                                                                                    <tr>
                                                                                                        <td><?php echo get_the_title($enemy['enemy_id']); ?></td>
                                                                                                        <td>
                                                                                                            <input type="checkbox" name="tasks[<?php echo $task_index; ?>][enemies][<?php echo $enemy_index; ?>][defeated]" value="1" <?php checked(!empty($enemy['defeated'])); ?>>
                                                                                                            Pokonany
                                                                                                            <input type="hidden" name="tasks[<?php echo $task_index; ?>][enemies][<?php echo $enemy_index; ?>][enemy_id]" value="<?php echo $enemy['enemy_id']; ?>">
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                <?php endforeach; ?>
                                                                                            </tbody>
                                                                                        </table>
                                                                                    <?php else: ?>
                                                                                        <p>Brak zdefiniowanych przeciwników</p>
                                                                                    <?php endif; ?>
                                                                                <?php endif; ?>

                                                                                <?php if ($task['task_attempt_limit'] > 0): ?>
                                                                                    <p>Limit prób: <?php echo $task['task_attempt_limit']; ?></p>
                                                                                    <p>
                                                                                        Wykonane próby:
                                                                                        <input type="number" name="tasks[<?php echo $task_index; ?>][attempts]" value="<?php echo isset($task['attempts']) ? $task['attempts'] : 0; ?>" min="0" max="<?php echo $task['task_attempt_limit']; ?>">
                                                                                    </p>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                            <div class="mission-battle-stats">
                                                                <h5>Statystyki walk:</h5>
                                                                <div class="stats-inputs">
                                                                    <label>
                                                                        Wygrane:
                                                                        <input type="number" name="mission_stats[wins]" value="<?php echo $mission['wins']; ?>" min="0">
                                                                    </label>
                                                                    <label>
                                                                        Przegrane:
                                                                        <input type="number" name="mission_stats[losses]" value="<?php echo $mission['losses']; ?>" min="0">
                                                                    </label>
                                                                    <label>
                                                                        Remisy:
                                                                        <input type="number" name="mission_stats[draws]" value="<?php echo $mission['draws']; ?>" min="0">
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="mission-status-update">
                                                                <h5>Status misji:</h5>
                                                                <select name="mission_status">
                                                                    <option value="not_started" <?php selected($mission['status'], 'not_started'); ?>>Niezaczęta</option>
                                                                    <option value="in_progress" <?php selected($mission['status'], 'in_progress'); ?>>W trakcie</option>
                                                                    <option value="completed" <?php selected($mission['status'], 'completed'); ?>>Ukończona</option>
                                                                    <option value="failed" <?php selected($mission['status'], 'failed'); ?>>Nieudana</option>
                                                                </select>
                                                            </div>
                                                            <?php wp_nonce_field('update_mission_tasks'); ?>
                                                            <div class="submit-container">
                                                                <button type="submit" class="button button-primary">Zapisz zmiany</button>
                                                            </div>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <p>Brak danych o zadaniach dla tej misji.</p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <script>
                    jQuery(document).ready(function($) {
                        $('.show-mission-details').on('click', function() {
                            const missionId = $(this).data('mission-id');
                            $('#mission-details-' + missionId).toggleClass('hidden');
                        });
                    });
                </script>
                <style>
                    .mission-details-row.hidden {
                        display: none;
                    }

                    .mission-details-container {
                        padding: 15px;
                        background: #f9f9f9;
                        border: 1px solid #e5e5e5;
                        margin: 10px 0;
                    }

                    .mission-tasks-table {
                        margin-bottom: 15px;
                    }

                    .mission-status {
                        font-weight: bold;
                        padding: 3px 8px;
                        border-radius: 3px;
                    }

                    .status-not_started {
                        background-color: #e0e0e0;
                        color: #666;
                    }

                    .status-in_progress {
                        background-color: #c5e1f5;
                        color: #0073aa;
                    }

                    .status-completed {
                        background-color: #d0e8c3;
                        color: #46b450;
                    }

                    .status-failed {
                        background-color: #f1c9c9;
                        color: #dc3232;
                    }

                    .optional-tag {
                        font-style: italic;
                        color: #888;
                        font-size: 0.9em;
                    }

                    .mission-battle-stats,
                    .mission-status-update {
                        margin: 15px 0;
                        padding: 10px;
                        background: #fff;
                        border: 1px solid #e5e5e5;
                    }

                    .stats-inputs {
                        display: flex;
                        gap: 20px;
                    }

                    .stats-inputs label {
                        display: flex;
                        align-items: center;
                        gap: 5px;
                    }

                    .submit-container {
                        margin-top: 15px;
                    }

                    .submit-container .success {
                        color: #46b450;
                    }

                    .submit-container .error {
                        color: #dc3232;
                    }
                </style>
            <?php endif; ?>

            <!-- Relacje z NPC -->
            <?php if (!empty($user_data['relations'])): ?>
                <div class="game-data-section">
                    <h3>Relacje z NPC (<?php echo count($user_data['relations']); ?>)</h3>
                    <table class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th>NPC</th>
                                <th>Relacja</th>
                                <th>Poznany</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_data['relations'] as $relation): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($relation['npc_name'] ?: 'Nieznany NPC'); ?></strong>
                                        <br><small>ID: <?php echo $relation['npc_id']; ?></small>
                                    </td>
                                    <td>
                                        <span class="relation-value relation-<?php echo $this->get_relation_class($relation['relation_value']); ?>">
                                            <?php echo $relation['relation_value']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($relation['is_known']): ?>
                                            <span class="known">✓ Poznany</span>
                                        <?php else: ?>
                                            <span class="unknown">Nieznany</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Obszary/Rejony -->
                <div class="areas-management">
                    <h3>Zarządzanie obszarami/rejonami</h3>

                    <?php
                    // Pobierz aktualne pole current_area z ACF
                    $current_area = get_field('current_area', 'user_' . $user_id);
                    $available_areas = get_field('available_areas', 'user_' . $user_id);

                    // Pobierz wszystkie dostępne tereny
                    $all_areas = get_posts([
                        'post_type' => 'tereny',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ]);
                    ?>

                    <!-- Aktualny rejon -->
                    <div class="areas-current">
                        <h4>Aktualny rejon</h4>
                        <?php if ($current_area): ?>
                            <div class="current-area-display">
                                <?php if (has_post_thumbnail($current_area->ID)): ?>
                                    <div class="area-thumbnail">
                                        <?php echo get_the_post_thumbnail($current_area->ID, [60, 60]); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="current-area-info">
                                    <h4><?php echo esc_html($current_area->post_title); ?></h4>
                                    <p>ID: <?php echo $current_area->ID; ?></p>
                                    <?php
                                    $area_description = get_field('teren_opis', $current_area->ID);
                                    if ($area_description): ?>
                                        <p><?php echo esc_html(wp_trim_words($area_description, 15)); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p><em>Brak przypisanego rejonu</em></p>
                        <?php endif; ?>

                        <!-- Formularz zmiany aktualnego rejonu -->
                        <form method="post" action="" class="area-select-form">
                            <?php wp_nonce_field('update_user_data'); ?>
                            <input type="hidden" name="action" value="update_user_area">
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                            <div class="form-field">
                                <label for="current_area">Zmień aktualny rejon:</label>
                                <select name="current_area" id="current_area">
                                    <option value="">-- Brak rejonu --</option>
                                    <?php foreach ($all_areas as $area): ?>
                                        <option value="<?php echo $area->ID; ?>" <?php selected($current_area ? $current_area->ID : '', $area->ID); ?>>
                                            <?php echo esc_html($area->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <input type="submit" class="button-primary" value="Zmień rejon">
                        </form>
                    </div>

                    <!-- Dostępne rejony -->
                    <div class="areas-available">
                        <h4>Dostępne rejony dla gracza</h4>
                        <?php if (!empty($available_areas)): ?>
                            <div class="available-areas-list">
                                <?php foreach ($available_areas as $area_id): ?>
                                    <?php
                                    $area = get_post($area_id);
                                    if ($area):
                                        $is_current = ($current_area && $current_area->ID == $area_id);
                                        $area_description = get_field('teren_opis', $area_id);
                                        $events = get_field('events', $area_id);
                                    ?>
                                        <div class="available-area-item <?php echo $is_current ? 'current' : ''; ?>">
                                            <?php if (has_post_thumbnail($area_id)): ?>
                                                <div class="area-thumb">
                                                    <?php echo get_the_post_thumbnail($area_id, [40, 40]); ?>
                                                </div>
                                            <?php endif; ?>
                                            <h5><?php echo esc_html($area->post_title); ?></h5>
                                            <p>ID: <?php echo $area_id; ?></p>
                                            <?php if ($area_description): ?>
                                                <p><?php echo esc_html(wp_trim_words($area_description, 10)); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($events)): ?>
                                                <p><small>Zdarzenia: <?php echo count($events); ?></small></p>
                                            <?php endif; ?>
                                            <?php if ($is_current): ?>
                                                <span class="current-badge">Aktualny</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>

                            <!-- Formularz zarządzania dostępnymi rejonami -->
                            <form method="post" action="" style="margin-top: 20px;">
                                <?php wp_nonce_field('update_user_data'); ?>
                                <input type="hidden" name="action" value="update_available_areas">
                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                                <h5>Zarządzaj dostępnymi rejonami:</h5>
                                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                    <?php foreach ($all_areas as $area): ?>
                                        <label style="display: block; margin: 5px 0;">
                                            <input type="checkbox"
                                                name="available_areas[]"
                                                value="<?php echo $area->ID; ?>"
                                                <?php checked(in_array($area->ID, $available_areas ?: [])); ?>>
                                            <?php echo esc_html($area->post_title); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <p class="submit" style="margin-top: 15px;">
                                    <input type="submit" class="button-primary" value="Zaktualizuj dostępne rejony">
                                </p>
                            </form>

                        <?php else: ?>
                            <p><em>Gracz nie ma dostępu do żadnych rejonów</em></p>

                            <!-- Formularz dodawania pierwszych rejonów -->
                            <form method="post" action="" style="margin-top: 15px;">
                                <?php wp_nonce_field('update_user_data'); ?>
                                <input type="hidden" name="action" value="update_available_areas">
                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                                <h5>Dodaj dostępne rejony:</h5>
                                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                    <?php foreach ($all_areas as $area): ?>
                                        <label style="display: block; margin: 5px 0;">
                                            <input type="checkbox" name="available_areas[]" value="<?php echo $area->ID; ?>">
                                            <?php echo esc_html($area->post_title); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <p class="submit" style="margin-top: 15px;">
                                    <input type="submit" class="button-primary" value="Dodaj rejony">
                                </p>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>

                <div class="notice notice-warning">
                    <p>
                        Ten użytkownik nie ma jeszcze danych w grze.
                        <a href="?page=game-user-details&user_id=<?php echo $user_id; ?>&action=init" class="button">
                            Inicjalizuj dane gracza
                        </a>
                    </p>
                </div>

            <?php endif; ?>

        </div>
    <?php
    }

    /**
     * Renderuje ustawienia bazy danych
     */
    public function render_database_settings()
    {
        $db_manager = GameDatabaseManager::get_instance();
        $action = $_GET['action'] ?? '';

        // Obsługa akcji
        if ($action === 'create_tables') {
            $db_manager->init_tables();
            echo '<div class="notice notice-success"><p>Tabele zostały utworzone pomyślnie.</p></div>';
        } elseif ($action === 'drop_tables') {
            $db_manager->drop_all_tables();
            echo '<div class="notice notice-success"><p>Tabele zostały usunięte.</p></div>';
        } elseif ($action === 'build_missions_from_cpt') {
            $result = $this->build_missions_from_cpt();
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            }
        }

        $tables_exist = $db_manager->tables_exist();
        $table_names = $db_manager->get_table_names();

    ?>
        <div class="wrap game-admin-wrap">
            <h1>Ustawienia Bazy Danych</h1>

            <div class="database-status">
                <h3>Status tabel</h3>
                <p>
                    <?php if ($tables_exist): ?>
                        <span class="status-ok">✓ Tabele istnieją i są gotowe do użycia</span>
                    <?php else: ?>
                        <span class="status-error">✗ Tabele nie istnieją</span>
                    <?php endif; ?>
                </p>
            </div>

            <div class="database-actions">
                <h3>Akcje</h3>

                <?php if (!$tables_exist): ?>
                    <p>
                        <a href="?page=game-database-settings&action=create_tables" class="button button-primary">
                            Utwórz tabele
                        </a>
                    </p>
                <?php else: ?>
                    <p>
                        <a href="?page=game-database-settings&action=drop_tables" class="button button-secondary"
                            onclick="return confirm('Czy na pewno chcesz usunąć wszystkie tabele? Ta operacja jest nieodwracalna!')">
                            Usuń wszystkie tabele
                        </a>
                    </p>
                    <p>
                        <a href="?page=game-database-settings&action=build_missions_from_cpt" class="button button-primary"
                            onclick="return confirm('Czy chcesz automatycznie utworzyć misje z CPT dla wszystkich użytkowników? Ta operacja może trwać chwilę.')">
                            Buduj misje z CPT
                        </a>
                        <small style="display: block; margin-top: 5px; color: #666;">
                            Tworzy misje użytkowników na podstawie opublikowanych misji CPT
                        </small>
                    </p>
                <?php endif; ?>
            </div>

            <div class="tables-list">
                <h3>Lista tabel</h3>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>Nazwa tabeli</th>
                            <th>Status</th>
                            <th>Rekordy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($table_names as $key => $table_name): ?>
                            <tr>
                                <td><code><?php echo esc_html($table_name); ?></code></td>
                                <td>
                                    <?php
                                    global $wpdb;
                                    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
                                    if ($exists): ?>
                                        <span class="status-ok">Istnieje</span>
                                    <?php else: ?>
                                        <span class="status-error">Nie istnieje</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($exists): ?>
                                        <?php echo number_format($wpdb->get_var("SELECT COUNT(*) FROM $table_name")); ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php
    }

    /**
     * Buduje misje użytkowników na podstawie opublikowanych misji CPT
     */
    private function build_missions_from_cpt()
    {
        global $wpdb;

        try {
            // Sprawdź czy tabele istnieją
            $db_manager = GameDatabaseManager::get_instance();
            if (!$db_manager->tables_exist()) {
                return [
                    'success' => false,
                    'message' => 'Tabele gry nie istnieją. Utwórz je najpierw.'
                ];
            }

            // Pobierz wszystkie opublikowane misje
            $missions = get_posts([
                'post_type' => 'mission',
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ]);

            if (empty($missions)) {
                return [
                    'success' => false,
                    'message' => 'Brak opublikowanych misji CPT w systemie.'
                ];
            }

            // Pobierz wszystkich użytkowników WordPress
            $users = get_users(['fields' => 'ID']);
            if (empty($users)) {
                return [
                    'success' => false,
                    'message' => 'Brak użytkowników w systemie.'
                ];
            }

            require_once(get_template_directory() . '/includes/db-tables/MissionUserModel.php');

            $created_count = 0;
            $skipped_count = 0;
            $error_count = 0;

            foreach ($users as $user_id) {
                $mission_model = new GameMissionUserModel($user_id);

                foreach ($missions as $mission) {
                    // Sprawdź czy użytkownik już ma tę misję
                    $existing = $mission_model->get_mission($mission->ID);

                    if ($existing) {
                        $skipped_count++;
                        continue;
                    }

                    // Utwórz misję dla użytkownika
                    $result = $mission_model->create_mission($mission->ID);

                    if ($result !== false) {
                        $created_count++;
                    } else {
                        $error_count++;
                    }
                }
            }

            $message = sprintf(
                'Budowanie misji zakończone. Utworzono: %d, Pominięto (już istnieją): %d, Błędy: %d',
                $created_count,
                $skipped_count,
                $error_count
            );

            return [
                'success' => true,
                'message' => $message
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Błąd podczas budowania misji: ' . $e->getMessage()
            ];
        }
    }

    // Metody pomocnicze

    private function get_active_users_count()
    {
        global $wpdb;
        $db_manager = GameDatabaseManager::get_instance();
        $tables = $db_manager->get_table_names();

        if (!$db_manager->tables_exist()) {
            return 0;
        }

        return $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tables['users']} 
             WHERE updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
    }

    private function calculate_level($exp)
    {
        return floor($exp / 1000) + 1; // Proste obliczenie levelu
    }

    private function get_reputation_class($reputation)
    {
        if ($reputation >= 100) return 'excellent';
        if ($reputation >= 50) return 'good';
        if ($reputation >= 20) return 'neutral';
        if ($reputation >= 0) return 'poor';
        return 'bad';
    }

    private function get_relation_class($value)
    {
        if ($value >= 80) return 'excellent';
        if ($value >= 60) return 'good';
        if ($value >= 40) return 'neutral';
        if ($value >= 20) return 'poor';
        return 'bad';
    }

    private function format_time_ago($timestamp)
    {
        $time = time() - strtotime($timestamp);

        if ($time < 60) return 'przed chwilą';
        if ($time < 3600) return floor($time / 60) . ' min temu';
        if ($time < 86400) return floor($time / 3600) . ' godz temu';
        return floor($time / 86400) . ' dni temu';
    }
}
