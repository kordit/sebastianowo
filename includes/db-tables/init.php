<?php

/**
 * Inicjalizacja systemu bazy danych dla gry
 * 
 * Ładuje wszystkie klasy i rejestruje hooki
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Ładowanie klas
require_once __DIR__ . '/DatabaseManager.php';
require_once __DIR__ . '/GameUserModel.php';
require_once __DIR__ . '/MissionUserModel.php';
require_once __DIR__ . '/GameMissionAPI.php';
require_once __DIR__ . '/GameAdminPanel.php';

/**
 * Główna klasa inicjalizująca system bazy danych
 */
class GameDatabaseInit
{
    private static $instance = null;

    public function __construct()
    {
        add_action('init', [$this, 'init']);
        add_action('user_register', [$this, 'on_user_register']);
        add_action('wp_loaded', [$this, 'auto_initialize_current_user']);

        // Inicjalizuj panel administracyjny
        if (is_admin()) {
            GameAdminPanel::get_instance();
        }

        // Inicjalizuj API misji
        new GameMissionAPI();
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicjalizacja systemu
     */
    public function init()
    {
        // Sprawdź czy tabele istnieją, jeśli nie - utwórz je
        $db_manager = GameDatabaseManager::get_instance();

        if (!$db_manager->tables_exist()) {
            // Automatycznie utwórz tabele przy pierwszym uruchomieniu
            $db_manager->init_tables();
        }
    }

    /**
     * Obsługa rejestracji nowego użytkownika
     */
    public function on_user_register($user_id)
    {
        // Automatycznie inicjalizuj dane gracza dla nowych użytkowników
        $game_user = new GameUserModel($user_id);
        $game_user->initialize_new_user();
    }

    /**
     * Automatyczna inicjalizacja aktualnie zalogowanego użytkownika
     */
    public function auto_initialize_current_user()
    {
        // Sprawdź tylko dla zalogowanych użytkowników na frontend
        if (is_admin() || !is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        // Sprawdź czy użytkownik już ma dane w customowych tabelach
        $game_user = new GameUserModel($user_id);
        if (!$game_user->exists()) {
            // Inicjalizuj dane gracza
            $game_user->initialize_new_user();

            // Opcjonalnie: spróbuj zmigrować dane z ACF
            $this->try_migrate_user_data($user_id);
        } else {
            // Aktualizuj timestamp ostatniej aktywności
            $game_user->touch_activity();
        }
    }

    /**
     * Próbuje zmigrować dane z ACF dla użytkownika
     */
    private function try_migrate_user_data($user_id)
    {
        // Sprawdź czy użytkownik ma jakiekolwiek dane ACF
        $has_acf_data = false;

        $acf_fields = ['nick', 'avatar', 'user_class', 'stats', 'vitality', 'progress', 'skills', 'backpack'];
        foreach ($acf_fields as $field) {
            if (get_field($field, 'user_' . $user_id)) {
                $has_acf_data = true;
                break;
            }
        }

        // Jeśli ma dane ACF, zmigruj je
        if ($has_acf_data) {
            self::migrate_single_user($user_id);
        }
    }

    /**
     * Migruje dane z ACF do customowych tabel
     */
    public static function migrate_acf_data($user_id = null)
    {
        if ($user_id) {
            self::migrate_single_user($user_id);
        } else {
            self::migrate_all_users();
        }
    }

    /**
     * Migruje dane jednego użytkownika
     */
    private static function migrate_single_user($user_id)
    {
        $game_user = new GameUserModel($user_id);

        // Pobierz dane z ACF
        $acf_data = [
            'nick' => get_field('nick', 'user_' . $user_id) ?: '',
            'avatar_id' => get_field('avatar', 'user_' . $user_id) ?: 0,
            'user_class' => self::extract_acf_value(get_field('user_class', 'user_' . $user_id)),
            'current_area_id' => self::extract_acf_value(get_field('current_area', 'user_' . $user_id)),
        ];

        // Statystyki
        $stats = get_field('stats', 'user_' . $user_id) ?: [];
        if ($stats) {
            $acf_data = array_merge($acf_data, [
                'strength' => intval($stats['strength'] ?? 1),
                'defense' => intval($stats['defense'] ?? 1),
                'dexterity' => intval($stats['dexterity'] ?? 1),
                'perception' => intval($stats['perception'] ?? 1),
                'technical' => intval($stats['technical'] ?? 1),
                'charisma' => intval($stats['charisma'] ?? 1),
            ]);
        }

        // Witalność
        $vitality = get_field('vitality', 'user_' . $user_id) ?: [];
        if ($vitality) {
            $acf_data = array_merge($acf_data, [
                'max_life' => intval($vitality['max_life'] ?? 100),
                'current_life' => intval($vitality['current_life'] ?? 100),
                'max_energy' => intval($vitality['max_energy'] ?? 100),
                'current_energy' => intval($vitality['current_energy'] ?? 100),
            ]);
        }

        // Progress
        $progress = get_field('progress', 'user_' . $user_id) ?: [];
        if ($progress) {
            $acf_data = array_merge($acf_data, [
                'exp' => intval($progress['exp'] ?? 0),
                'learning_points' => intval($progress['learning_points'] ?? 3),
                'reputation' => intval($progress['reputation'] ?? 1),
            ]);
        }

        // Backpack (waluty)
        $backpack = get_field('backpack', 'user_' . $user_id) ?: [];
        if ($backpack) {
            $acf_data = array_merge($acf_data, [
                'gold' => intval($backpack['gold'] ?? 0),
                'cigarettes' => intval($backpack['cigarettes'] ?? 0),
            ]);
        }

        // Utwórz lub zaktualizuj rekord podstawowy
        if (!$game_user->exists()) {
            $game_user->create_user_record($acf_data);
        } else {
            $game_user->update_basic_data($acf_data);
        }

        // Migruj umiejętności
        $skills = get_field('skills', 'user_' . $user_id) ?: [];
        if ($skills) {
            $skills_data = [
                'combat' => intval($skills['combat'] ?? 0),
                'steal' => intval($skills['steal'] ?? 0),
                'craft' => intval($skills['craft'] ?? 0),
                'trade' => intval($skills['trade'] ?? 0),
                'relations' => intval($skills['relations'] ?? 0),
                'street' => intval($skills['street'] ?? 0),
            ];

            $game_user->update_skills_data($skills_data);
        }

        // TODO: Migracja przedmiotów, misji, relacji, obszarów

        return true;
    }

    /**
     * Migruje dane wszystkich użytkowników
     */
    private static function migrate_all_users()
    {
        $users = get_users(['fields' => 'ID']);
        $migrated = 0;

        foreach ($users as $user_id) {
            if (self::migrate_single_user($user_id)) {
                $migrated++;
            }
        }

        return $migrated;
    }

    /**
     * Pomocnicza funkcja do wyciągania wartości z ACF
     */
    private static function extract_acf_value($value)
    {
        if (is_array($value) && isset($value['value'])) {
            return $value['value'];
        }
        return $value ?: '';
    }
}

// Inicjalizuj system
GameDatabaseInit::get_instance();
