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
                                    <th>Rozpoczęta</th>
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
                                                <?php echo esc_html($mission['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $this->format_time_ago($mission['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
