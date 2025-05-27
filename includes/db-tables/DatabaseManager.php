<?php

/**
 * Zarządca bazy danych dla customowych tabel gry
 * 
 * Odpowiada za:
 * - Tworzenie tabel przy aktywacji
 * - Migracje struktury
 * - Walidację integralności
 */
class GameDatabaseManager
{
    private static $instance = null;
    private $table_prefix;

    public function __construct()
    {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix . 'game_';
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicjalizuje wszystkie tabele
     */
    public function init_tables()
    {
        $this->create_users_table();
        $this->create_user_skills_table();
        $this->create_user_items_table();
        $this->create_user_missions_table();
        $this->create_user_relations_table();
        $this->create_user_areas_table();

        // Dodaj opcję wersji bazy danych
        update_option('game_db_version', '1.0');
    }

    /**
     * Tabela głównych danych użytkownika
     */
    private function create_users_table()
    {
        global $wpdb;

        $table_name = $this->table_prefix . 'users';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL,
            nick varchar(100) DEFAULT '',
            avatar_id bigint(20) DEFAULT 0,
            user_class varchar(50) DEFAULT '',
            current_area_id bigint(20) DEFAULT 0,
            
            -- Statystyki podstawowe
            strength int(11) DEFAULT 1,
            defense int(11) DEFAULT 1,
            dexterity int(11) DEFAULT 1,
            perception int(11) DEFAULT 1,
            technical int(11) DEFAULT 1,
            charisma int(11) DEFAULT 1,
            
            -- Witalność
            max_life int(11) DEFAULT 100,
            current_life int(11) DEFAULT 100,
            max_energy int(11) DEFAULT 100,
            current_energy int(11) DEFAULT 100,
            
            -- Progress
            exp int(11) DEFAULT 0,
            learning_points int(11) DEFAULT 3,
            reputation int(11) DEFAULT 1,
            
            -- Waluty (backpack)
            gold int(11) DEFAULT 0,
            cigarettes int(11) DEFAULT 0,
            
            -- Timestamps
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            KEY current_area_id (current_area_id),
            KEY user_class (user_class),
            KEY reputation (reputation)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tabela umiejętności użytkowników
     */
    private function create_user_skills_table()
    {
        global $wpdb;

        $table_name = $this->table_prefix . 'user_skills';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            user_id bigint(20) unsigned NOT NULL,
            combat int(11) DEFAULT 0,
            steal int(11) DEFAULT 0,
            craft int(11) DEFAULT 0,
            trade int(11) DEFAULT 0,
            relations int(11) DEFAULT 0,
            street int(11) DEFAULT 0,
            
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            PRIMARY KEY (user_id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tabela przedmiotów użytkowników
     */
    private function create_user_items_table()
    {
        global $wpdb;

        $table_name = $this->table_prefix . 'user_items';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            item_id bigint(20) unsigned NOT NULL,
            quantity int(11) DEFAULT 1,
            equipped tinyint(1) DEFAULT 0,
            slot varchar(50) DEFAULT NULL,
            
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY item_id (item_id),
            KEY equipped (equipped),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tabela misji użytkowników
     */
    private function create_user_missions_table()
    {
        global $wpdb;

        $table_name = $this->table_prefix . 'user_missions';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            mission_id bigint(20) unsigned NOT NULL,
            mission_type varchar(50) DEFAULT NULL,
            status varchar(20) DEFAULT 'not_started',
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            tasks_data longtext,
            wins int(11) DEFAULT 0,
            losses int(11) DEFAULT 0,
            draws int(11) DEFAULT 0,
            
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY mission_id (mission_id),
            KEY status (status),
            KEY mission_type (mission_type),
            UNIQUE KEY user_mission (user_id, mission_id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tabela relacji z NPC
     */
    private function create_user_relations_table()
    {
        global $wpdb;

        $table_name = $this->table_prefix . 'user_relations';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            npc_id bigint(20) unsigned NOT NULL,
            relation_value int(11) DEFAULT 0,
            is_known tinyint(1) DEFAULT 0,
            
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY npc_id (npc_id),
            UNIQUE KEY user_npc (user_id, npc_id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tabela dostępnych obszarów
     */
    private function create_user_areas_table()
    {
        global $wpdb;

        $table_name = $this->table_prefix . 'user_areas';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            area_id bigint(20) unsigned NOT NULL,
            
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY area_id (area_id),
            UNIQUE KEY user_area (user_id, area_id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Sprawdza czy tabele istnieją
     */
    public function tables_exist()
    {
        global $wpdb;

        $tables = [
            'users',
            'user_skills',
            'user_items',
            'user_missions',
            'user_relations',
            'user_areas'
        ];

        foreach ($tables as $table) {
            $table_name = $this->table_prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            if (!$exists) {
                return false;
            }
        }

        return true;
    }

    /**
     * Pobiera nazwy wszystkich tabel gry
     */
    public function get_table_names()
    {
        return [
            'users' => $this->table_prefix . 'users',
            'user_skills' => $this->table_prefix . 'user_skills',
            'user_items' => $this->table_prefix . 'user_items',
            'user_missions' => $this->table_prefix . 'user_missions',
            'user_relations' => $this->table_prefix . 'user_relations',
            'user_areas' => $this->table_prefix . 'user_areas'
        ];
    }

    /**
     * Usuwa wszystkie tabele (dla celów deweloperskich)
     */
    public function drop_all_tables()
    {
        global $wpdb;

        $tables = array_reverse($this->get_table_names()); // Odwrócona kolejność dla FK

        foreach ($tables as $table_name) {
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }

        delete_option('game_db_version');
    }
}
