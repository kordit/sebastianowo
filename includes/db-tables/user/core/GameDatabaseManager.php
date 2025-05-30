<?php

/**
 * Zarządca bazy danych gry
 * Odpowiedzialny za tworzenie, aktualizację i usuwanie tabel
 */
class GameDatabaseManager
{
    private $wpdb;
    private $charset_collate;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    /**
     * Tworzy wszystkie tabele gry
     */
    public function createTables()
    {
        $this->createGameUsersTable();
        $this->createGameUserItemsTable();
        $this->createGameUserAreasTable();
        $this->createGameUserRelationsTable();
        $this->createGameUserFightTokensTable();
        $this->createGameUserMissionTasksTable();
    }

    /**
     * Sprawdza czy tabele istnieją
     */
    public function tablesExist()
    {
        $table_name = $this->wpdb->prefix . 'game_users';
        return $this->wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    }

    /**
     * Zwraca listę wszystkich tabel gry
     */
    public function getGameTables()
    {
        return [
            'game_users' => 'Dane graczy',
            'game_user_items' => 'Ekwipunek graczy',
            'game_user_areas' => 'Dostępne rejony i sceny',
            'game_user_relations' => 'Relacje z NPC',
            'game_user_fight_tokens' => 'Tokeny walk',
            'game_user_mission_tasks' => 'Zadania misji'
        ];
    }

    /**
     * Sprawdza status wszystkich tabel (istnienie i liczba rekordów)
     */
    public function getTablesStatus()
    {
        $tables = $this->getGameTables();
        $status = [];

        foreach ($tables as $table_key => $table_desc) {
            $full_table_name = $this->wpdb->prefix . $table_key;

            // Sprawdź czy tabela istnieje
            $exists = $this->wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;

            $count = 0;
            if ($exists) {
                // Policz rekordy tylko jeśli tabela istnieje
                $count = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
            }

            $status[$table_key] = [
                'name' => $table_key,
                'description' => $table_desc,
                'full_name' => $full_table_name,
                'exists' => $exists,
                'count' => $count
            ];
        }

        return $status;
    }

    /**
     * Sprawdza czy wszystkie tabele istnieją
     */
    public function allTablesExist()
    {
        $status = $this->getTablesStatus();
        foreach ($status as $table) {
            if (!$table['exists']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Usuwa wszystkie tabele gry
     */
    public function dropTables()
    {
        // Wyłączenie sprawdzania foreign key constraints
        $this->wpdb->query("SET foreign_key_checks = 0");

        // Usuwanie tabel w odwrotnej kolejności niż tworzenie foreign keys
        $tables = [
            $this->wpdb->prefix . 'game_user_mission_tasks',
            $this->wpdb->prefix . 'game_user_fight_tokens',
            $this->wpdb->prefix . 'game_user_relations',
            $this->wpdb->prefix . 'game_user_areas',
            $this->wpdb->prefix . 'game_user_items',
            $this->wpdb->prefix . 'game_users'  // Główna tabela jako ostatnia
        ];

        foreach ($tables as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS $table");
        }

        // Włączenie z powrotem sprawdzania foreign key constraints
        $this->wpdb->query("SET foreign_key_checks = 1");
    }

    /**
     * Tworzy główną tabelę graczy
     */
    private function createGameUsersTable()
    {
        $table_name = $this->wpdb->prefix . 'game_users';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            user_id bigint(20) unsigned NOT NULL PRIMARY KEY,
            nick varchar(64) DEFAULT '',
            user_class varchar(32) DEFAULT '',
            avatar int DEFAULT 0,
            avatar_full int DEFAULT 0,
            story_text text,
            strength int DEFAULT 1,
            defense int DEFAULT 1,
            dexterity int DEFAULT 1,
            perception int DEFAULT 1,
            technical int DEFAULT 1,
            charisma int DEFAULT 1,
            combat int DEFAULT 0,
            steal int DEFAULT 0,
            craft int DEFAULT 0,
            trade int DEFAULT 0,
            relations int DEFAULT 0,
            street int DEFAULT 0,
            exp int DEFAULT 0,
            learning_points int DEFAULT 3,
            reputation int DEFAULT 1,
            life int DEFAULT 100,
            max_life int DEFAULT 100,
            energy int DEFAULT 100,
            max_energy int DEFAULT 100,
            gold int DEFAULT 0,
            cigarettes int DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES {$this->wpdb->users}(ID) ON DELETE CASCADE
        ) {$this->charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tworzy tabelę przedmiotów graczy
     */
    private function createGameUserItemsTable()
    {
        $table_name = $this->wpdb->prefix . 'game_user_items';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id bigint(20) unsigned NOT NULL,
            item_id int NOT NULL,
            amount int DEFAULT 1,
            is_equipped boolean DEFAULT 0,
            slot varchar(32) DEFAULT '',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}game_users(user_id) ON DELETE CASCADE,
            INDEX idx_user_items (user_id, item_id),
            INDEX idx_equipped (user_id, is_equipped)
        ) {$this->charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tworzy tabelę dostępnych rejonów i scen
     */
    private function createGameUserAreasTable()
    {
        $table_name = $this->wpdb->prefix . 'game_user_areas';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id bigint(20) unsigned NOT NULL,
            area_id int NOT NULL,
            scene_id varchar(64) NOT NULL,
            unlocked_area_id int DEFAULT 0,
            unlocked boolean DEFAULT 1,
            viewed boolean DEFAULT 0,
            is_current boolean DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}game_users(user_id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_area_scene (user_id, area_id, scene_id),
            INDEX idx_user_areas (user_id, area_id, unlocked),
            INDEX idx_scene_status (scene_id, unlocked, viewed),
            INDEX idx_current_location (user_id, is_current)
        ) {$this->charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tworzy tabelę relacji z NPC
     */
    private function createGameUserRelationsTable()
    {
        $table_name = $this->wpdb->prefix . 'game_user_relations';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id bigint(20) unsigned NOT NULL,
            npc_id int NOT NULL,
            relation_value int DEFAULT 0 COMMENT 'Poziom relacji od -100 do 100',
            is_known boolean DEFAULT 0 COMMENT 'Czy gracz poznał tego NPC',
            fights_won int DEFAULT 0 COMMENT 'Wygrane walki z tym NPC',
            fights_lost int DEFAULT 0 COMMENT 'Przegrane walki z tym NPC',
            fights_draw int DEFAULT 0 COMMENT 'Remisy z tym NPC',
            last_interaction timestamp NULL COMMENT 'Ostatnia interakcja',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}game_users(user_id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_npc (user_id, npc_id),
            INDEX idx_user_relations (user_id, is_known),
            INDEX idx_npc_relations (npc_id, relation_value)
        ) {$this->charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tworzy tabelę tokenów walk
     */
    private function createGameUserFightTokensTable()
    {
        $table_name = $this->wpdb->prefix . 'game_user_fight_tokens';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            user_id bigint(20) unsigned NOT NULL PRIMARY KEY,
            tokens_json text,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}game_users(user_id) ON DELETE CASCADE
        ) {$this->charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tworzy tabelę zadań misji użytkowników (główna tabela misji)
     */
    private function createGameUserMissionTasksTable()
    {
        $table_name = $this->wpdb->prefix . 'game_user_mission_tasks';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id bigint(20) unsigned NOT NULL,
            mission_id int NOT NULL COMMENT 'ID misji z WordPress',
            mission_title varchar(255) DEFAULT '' COMMENT 'Tytuł misji',
            mission_description text COMMENT 'Opis misji',
            mission_time_limit int DEFAULT 0 COMMENT 'Limit czasowy w godzinach (0 = bez limitu)',
            mission_type varchar(32) DEFAULT 'one-time' COMMENT 'Typ: one-time, repeatable, daily, weekly',
            mission_status varchar(32) DEFAULT 'not_started' COMMENT 'Status misji: not_started, in_progress, completed, failed, expired',
            task_id varchar(64) NOT NULL COMMENT 'ID zadania np. pierwszy-trop',
            task_title varchar(255) DEFAULT '' COMMENT 'Tytuł zadania',
            task_description text COMMENT 'Opis zadania',
            task_optional boolean DEFAULT 0 COMMENT 'Czy zadanie jest opcjonalne',
            task_attempt_limit int DEFAULT 0 COMMENT 'Limit prób (0 = bez limitu)',
            task_type varchar(32) DEFAULT '' COMMENT 'Typ: checkpoint, checkpoint_npc, defeat_enemies',
            task_location int DEFAULT 0 COMMENT 'ID lokacji (dla checkpoint)',
            task_location_scene varchar(64) DEFAULT '' COMMENT 'Nazwa sceny (dla checkpoint)',
            task_checkpoint_npc json NULL COMMENT 'Array NPC dla checkpoint_npc',
            task_defeat_enemies json NULL COMMENT 'Array wrogów do pokonania',
            task_status varchar(32) DEFAULT 'not_started' COMMENT 'Status zadania: not_started, in_progress, completed, failed',
            task_attempts int DEFAULT 0 COMMENT 'Liczba prób zadania',
            task_wins int DEFAULT 0 COMMENT 'Wygrane (dla zadań walki)',
            task_losses int DEFAULT 0 COMMENT 'Przegrane (dla zadań walki)',
            task_draws int DEFAULT 0 COMMENT 'Remisy (dla zadań walki)',
            mission_started_at datetime NULL,
            mission_completed_at datetime NULL,
            mission_expires_at datetime NULL COMMENT 'Kiedy misja wygasa (na podstawie time_limit)',
            task_completed_at datetime NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}game_users(user_id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_mission_task (user_id, mission_id, task_id),
            INDEX idx_user_missions (user_id, mission_id, mission_status),
            INDEX idx_user_tasks (user_id, mission_id, task_status),
            INDEX idx_task_type (task_type, task_status),
            INDEX idx_location_scene (task_location, task_location_scene),
            INDEX idx_mission_expires (mission_expires_at)
        ) {$this->charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }


}
