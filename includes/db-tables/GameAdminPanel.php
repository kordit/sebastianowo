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

                <!-- Podstawowe dane -->
                <div class="game-data-section">
                    <h3>Podstawowe dane</h3>
                    <table class="form-table">
                        <tr>
                            <th>Nick w grze</th>
                            <td><?php echo esc_html($user_data['basic']['nick'] ?: 'Brak'); ?></td>
                        </tr>
                        <tr>
                            <th>Klasa</th>
                            <td><?php echo esc_html($user_data['basic']['user_class'] ?: 'Brak'); ?></td>
                        </tr>
                        <tr>
                            <th>Level</th>
                            <td><?php echo $this->calculate_level($user_data['basic']['exp']); ?></td>
                        </tr>
                        <tr>
                            <th>Doświadczenie</th>
                            <td><?php echo number_format($user_data['basic']['exp']); ?> XP</td>
                        </tr>
                        <tr>
                            <th>Punkty nauki</th>
                            <td><?php echo $user_data['basic']['learning_points']; ?></td>
                        </tr>
                        <tr>
                            <th>Reputacja</th>
                            <td>
                                <span class="reputation-badge reputation-<?php echo $this->get_reputation_class($user_data['basic']['reputation']); ?>">
                                    <?php echo $user_data['basic']['reputation']; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Statystyki -->
                <div class="game-data-section">
                    <h3>Statystyki</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <label>Siła</label>
                            <span class="stat-value"><?php echo $user_data['basic']['strength']; ?></span>
                        </div>
                        <div class="stat-item">
                            <label>Obrona</label>
                            <span class="stat-value"><?php echo $user_data['basic']['defense']; ?></span>
                        </div>
                        <div class="stat-item">
                            <label>Zręczność</label>
                            <span class="stat-value"><?php echo $user_data['basic']['dexterity']; ?></span>
                        </div>
                        <div class="stat-item">
                            <label>Percepcja</label>
                            <span class="stat-value"><?php echo $user_data['basic']['perception']; ?></span>
                        </div>
                        <div class="stat-item">
                            <label>Technika</label>
                            <span class="stat-value"><?php echo $user_data['basic']['technical']; ?></span>
                        </div>
                        <div class="stat-item">
                            <label>Charyzma</label>
                            <span class="stat-value"><?php echo $user_data['basic']['charisma']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Witalność -->
                <div class="game-data-section">
                    <h3>Witalność</h3>
                    <div class="vitality-bars">
                        <div class="vitality-item">
                            <label>Życie</label>
                            <div class="progress-bar">
                                <div class="progress-fill life" style="width: <?php echo ($user_data['basic']['current_life'] / $user_data['basic']['max_life']) * 100; ?>%"></div>
                                <span class="progress-text"><?php echo $user_data['basic']['current_life']; ?> / <?php echo $user_data['basic']['max_life']; ?></span>
                            </div>
                        </div>
                        <div class="vitality-item">
                            <label>Energia</label>
                            <div class="progress-bar">
                                <div class="progress-fill energy" style="width: <?php echo ($user_data['basic']['current_energy'] / $user_data['basic']['max_energy']) * 100; ?>%"></div>
                                <span class="progress-text"><?php echo $user_data['basic']['current_energy']; ?> / <?php echo $user_data['basic']['max_energy']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Umiejętności -->
                <?php if ($user_data['skills']): ?>
                    <div class="game-data-section">
                        <h3>Umiejętności</h3>
                        <div class="skills-grid">
                            <div class="skill-item">
                                <label>Walka</label>
                                <span class="skill-value"><?php echo $user_data['skills']['combat']; ?></span>
                            </div>
                            <div class="skill-item">
                                <label>Kradzież</label>
                                <span class="skill-value"><?php echo $user_data['skills']['steal']; ?></span>
                            </div>
                            <div class="skill-item">
                                <label>Rzemiosło</label>
                                <span class="skill-value"><?php echo $user_data['skills']['craft']; ?></span>
                            </div>
                            <div class="skill-item">
                                <label>Handel</label>
                                <span class="skill-value"><?php echo $user_data['skills']['trade']; ?></span>
                            </div>
                            <div class="skill-item">
                                <label>Relacje</label>
                                <span class="skill-value"><?php echo $user_data['skills']['relations']; ?></span>
                            </div>
                            <div class="skill-item">
                                <label>Ulica</label>
                                <span class="skill-value"><?php echo $user_data['skills']['street']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Przedmioty -->
                <?php if (!empty($user_data['items'])): ?>
                    <div class="game-data-section">
                        <h3>Ekwipunek (<?php echo count($user_data['items']); ?> przedmiotów)</h3>
                        <table class="wp-list-table widefat">
                            <thead>
                                <tr>
                                    <th>Przedmiot</th>
                                    <th>Ilość</th>
                                    <th>Założony</th>
                                    <th>Slot</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_data['items'] as $item): ?>
                                    <tr class="<?php echo $item['equipped'] ? 'item-equipped' : ''; ?>">
                                        <td>
                                            <strong><?php echo esc_html($item['item_name'] ?: 'Nieznany przedmiot'); ?></strong>
                                            <br><small>ID: <?php echo $item['item_id']; ?></small>
                                        </td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>
                                            <?php if ($item['equipped']): ?>
                                                <span class="equipped">✓ Założony</span>
                                            <?php else: ?>
                                                <span class="not-equipped">Nie założony</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($item['slot'] ?: '—'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

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
                <?php endif; ?>

                <!-- Obszary -->
                <?php if (!empty($user_data['areas'])): ?>
                    <div class="game-data-section">
                        <h3>Dostępne obszary (<?php echo count($user_data['areas']); ?>)</h3>
                        <div class="areas-grid">
                            <?php foreach ($user_data['areas'] as $area): ?>
                                <div class="area-item">
                                    <strong><?php echo esc_html($area['area_name'] ?: 'Nieznany obszar'); ?></strong>
                                    <small>ID: <?php echo $area['area_id']; ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

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
