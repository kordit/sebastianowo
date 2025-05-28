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
    }

    /**
     * Strona Users
     */
    public function displayUsersPage()
    {
        include __DIR__ . '/views/users-page.php';
    }

    /**
     * Strona Database Setup
     */
    public function displayDatabasePage()
    {
        $db_manager = new GameDatabaseManager();
        $tables_exist = $db_manager->allTablesExist();
        $tables_status = $db_manager->getTablesStatus();
        include __DIR__ . '/views/database-page.php';
    }
}
