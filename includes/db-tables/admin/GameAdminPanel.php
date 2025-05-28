<?php

/**
 * Panel administracyjny gry
 */
class GameAdminPanel
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'handleFormSubmissions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Dodaje menu w panelu admina
     */
    public function addAdminMenu()
    {
        add_menu_page(
            'Lista graczy',
            'Gracze',
            'manage_options',
            'game-users',
            [$this, 'displayUsersPage'],
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'game-users',
            'Konfiguracja bazy danych',
            'Konfiguracja bazy danych',
            'manage_options',
            'game-database',
            [$this, 'displayDatabasePage']
        );
    }

    /**
     * Ładuje CSS i JS dla panelu
     */
    public function enqueueAssets($hook)
    {
        // Ładuj tylko na stronach naszego panelu
        if (strpos($hook, 'game-') === false) {
            return;
        }

        $assets_url = plugin_dir_url(__FILE__) . 'assets/';

        wp_enqueue_style(
            'game-admin-css',
            $assets_url . 'css/admin.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'game-admin-js',
            $assets_url . 'js/admin.js',
            [],
            '1.0.0',
            true
        );
    }

    /**
     * Obsługuje formularze POST
     */
    public function handleFormSubmissions()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $db_manager = new GameDatabaseManager();
        $user_sync = new GameUserSyncService();

        // Tworzenie tabel
        if (isset($_POST['create_tables']) && wp_verify_nonce($_POST['_wpnonce'], 'create_tables')) {
            $db_manager->createTables();
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> Tabele zostały utworzone/zaktualizowane!</p></div>';
            });
        }

        // Usuwanie tabel
        if (isset($_POST['drop_tables']) && wp_verify_nonce($_POST['_wpnonce'], 'drop_tables')) {
            $db_manager->dropTables();
            add_action('admin_notices', function () {
                echo '<div class="notice notice-warning is-dismissible"><p><strong>Uwaga!</strong> Wszystkie tabele zostały usunięte!</p></div>';
            });
        }

        // Import użytkowników
        if (isset($_POST['import_users']) && wp_verify_nonce($_POST['_wpnonce'], 'import_users')) {
            $result = $user_sync->importAllUsers();

            if ($result['success']) {
                add_action('admin_notices', function () use ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> ' . esc_html($result['message']) . ' Zaimportowano: ' . $result['imported'] . '</p></div>';
                });
            } else {
                add_action('admin_notices', function () use ($result) {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> ' . esc_html($result['message']) . '</p></div>';
                });
            }
        }

        // Edycja gracza
        if (isset($_POST['update_game_user']) && wp_verify_nonce($_POST['_wpnonce'], 'update_game_user')) {
            $user_id = intval($_POST['user_id']);
            $user_repo = new GameUserRepository();

            $update_data = [
                'nick' => sanitize_text_field($_POST['nick']),
                'user_class' => sanitize_text_field($_POST['user_class']),
                'exp' => intval($_POST['exp']),
                'learning_points' => intval($_POST['learning_points']),
                'reputation' => intval($_POST['reputation']),
                'strength' => intval($_POST['strength']),
                'defense' => intval($_POST['defense']),
                'dexterity' => intval($_POST['dexterity']),
                'perception' => intval($_POST['perception']),
                'technical' => intval($_POST['technical']),
                'charisma' => intval($_POST['charisma']),
                'combat' => intval($_POST['combat']),
                'steal' => intval($_POST['steal']),
                'craft' => intval($_POST['craft']),
                'trade' => intval($_POST['trade']),
                'relations' => intval($_POST['relations']),
                'street' => intval($_POST['street']),
                'life' => intval($_POST['life']),
                'max_life' => intval($_POST['max_life']),
                'energy' => intval($_POST['energy']),
                'max_energy' => intval($_POST['max_energy']),
                'gold' => intval($_POST['gold']),
                'cigarettes' => intval($_POST['cigarettes']),
                'current_area_id' => intval($_POST['current_area_id']),
                'current_scene_id' => sanitize_text_field($_POST['current_scene_id']),
                'story_text' => sanitize_textarea_field($_POST['story_text'])
            ];

            try {
                $user_repo->update($user_id, $update_data);
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> Dane gracza zostały zaktualizowane!</p></div>';
                });
            } catch (Exception $e) {
                add_action('admin_notices', function () use ($e) {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> ' . esc_html($e->getMessage()) . '</p></div>';
                });
            }
        }
    }

    /**
     * Strona Users
     */
    public function displayUsersPage()
    {
        // Sprawdź czy pokazać szczegóły gracza
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'view' && $user_id > 0) {
            $this->displayUserDetails($user_id);
        } else {
            $this->displayUsersList();
        }
    }

    /**
     * Lista wszystkich graczy
     */
    private function displayUsersList()
    {
        $user_repo = new GameUserRepository();
        $users = $user_repo->getAll();

        // Pobierz statystyki
        $user_sync = new GameUserSyncService();
        $stats = $user_sync->getUsersStats();

        include __DIR__ . '/views/users-list.php';
    }

    /**
     * Szczegóły pojedynczego gracza
     */
    private function displayUserDetails($user_id)
    {
        $user_repo = new GameUserRepository();
        $game_user = $user_repo->getByUserId($user_id);

        if (!$game_user) {
            wp_die('Gracz nie został znaleziony.');
        }

        // Pobierz dane użytkownika WordPress
        $wp_user = get_user_by('ID', $user_id);

        include __DIR__ . '/views/user-details.php';
    }

    /**
     * Strona Database Setup
     */
    public function displayDatabasePage()
    {
        $db_manager = new GameDatabaseManager();
        $user_sync = new GameUserSyncService();

        $tables_exist = $db_manager->allTablesExist();
        $tables_status = $db_manager->getTablesStatus();
        $users_stats = $user_sync->getUsersStats();

        include __DIR__ . '/views/database-page.php';
    }
}
