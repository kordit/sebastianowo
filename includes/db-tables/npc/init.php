<?php

/**
 * NPC Dialog Management System Initialization
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych dla systemu NPC
define('NPC_PLUGIN_VERSION', '1.0.0');
define('NPC_PLUGIN_PATH', get_template_directory() . '/includes/db-tables/npc/');
define('NPC_PLUGIN_URL', get_template_directory_uri() . '/includes/db-tables/npc/');

/**
 * Autoloader dla klas systemu NPC
 */
function npc_system_autoloader($class_name)
{
    $prefix = 'NPC_';

    if (strpos($class_name, $prefix) !== 0) {
        return;
    }

    $class_name = substr($class_name, strlen($prefix));

    $directories = [
        'core',
        'repositories',
        'admin',
        'api',
        'services'
    ];

    foreach ($directories as $dir) {
        $file_path = NPC_PLUGIN_PATH . $dir . '/' . $class_name . '.php';
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }
    }
}

spl_autoload_register('npc_system_autoloader');

/**
 * Klasa główna systemu NPC
 */
class NPCDialogSystem
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init()
    {
        // Inicjalizacja bazy danych
        $db_manager = new NPC_DatabaseManager();

        // Sprawdź czy tabele istnieją, jeśli nie - utwórz je
        if (!$db_manager->tables_exist()) {
            $db_manager->create_tables();
        }

        // Panel administracyjny
        if (is_admin()) {
            new NPC_AdminPanel();
        }

        // API endpoints
        new NPC_APIManager();

        // Usługi
        new NPC_DialogService();
    }


    public function enqueue_scripts()
    {
        wp_enqueue_script(
            'npc-dialog-frontend',
            NPC_PLUGIN_URL . 'assets/js/npc-frontend.js',
            ['jquery'],
            NPC_PLUGIN_VERSION,
            true
        );

        wp_localize_script('npc-dialog-frontend', 'npcAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('npc_dialog_nonce'),
            'rest_url' => rest_url('npc/v1/')
        ]);
    }

    public function admin_enqueue_scripts($hook)
    {
        if (strpos($hook, 'npc-manager') === false) {
            return;
        }
        
        // Dodaj jQuery UI dla funkcji przeciągania i sortowania
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        
        wp_enqueue_script(
            'npc-admin-js',
            NPC_PLUGIN_URL . 'assets/js/npc-admin.js',
            ['jquery', 'wp-api', 'jquery-ui-sortable'],
            NPC_PLUGIN_VERSION,
            true
        );

        wp_enqueue_script(
            'npc-conditions-js',
            NPC_PLUGIN_URL . 'assets/js/npc-conditions.js',
            ['jquery', 'npc-admin-js'],
            NPC_PLUGIN_VERSION,
            true
        );

        wp_enqueue_style(
            'npc-admin-css',
            NPC_PLUGIN_URL . 'assets/css/npc-admin.css',
            [],
            NPC_PLUGIN_VERSION
        );
        
        // Dodaj nowy arkusz CSS dla funkcji sortowania
        wp_enqueue_style(
            'npc-sortable-css',
            NPC_PLUGIN_URL . 'assets/css/npc-sortable.css',
            [],
            NPC_PLUGIN_VERSION
        );

        wp_localize_script('npc-admin-js', 'npcAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('npc_admin_nonce'),
            'rest_url' => rest_url('npc/v1/')
        ]);
    }

    public function activate()
    {
        $db_manager = new NPC_DatabaseManager();
        $db_manager->create_tables();
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }
}

// Inicjalizacja systemu
NPCDialogSystem::getInstance();
