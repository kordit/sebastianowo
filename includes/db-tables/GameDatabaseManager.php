<?php

class GameDatabaseManager
{

    private static $instance = null;
    private $wpdb;

    // Nazwy tabel
    const TABLES = [
        'game_users' => 'game_users',
        'game_user_stats' => 'game_user_stats',
        'game_user_skills' => 'game_user_skills',
        'game_user_progress' => 'game_user_progress',
        'game_user_vitality' => 'game_user_vitality',
        'game_user_items' => 'game_user_items',
        'game_user_areas' => 'game_user_areas',
        'game_user_fight_tokens' => 'game_user_fight_tokens',
        'game_user_relations' => 'game_user_relations',
        'game_user_story' => 'game_user_story',
        'game_user_missions' => 'game_user_missions',
        'game_user_mission_tasks' => 'game_user_mission_tasks'
    ];

    // Definicje kolumn dla każdej tabeli
    const TABLE_SCHEMAS = [
        'game_users' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'BIGINT UNSIGNED NOT NULL UNIQUE',
            'nickname' => 'VARCHAR(50) NOT NULL',
            'character_class' => 'VARCHAR(30) NOT NULL',
            'avatar' => 'VARCHAR(255)',
            'current_area_id' => 'INT DEFAULT NULL',
            'current_scene' => 'VARCHAR(50)',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],

        'game_user_stats' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'BIGINT UNSIGNED NOT NULL',
            'strength' => 'INT DEFAULT 0',
            'defense' => 'INT DEFAULT 0',
            'agility' => 'INT DEFAULT 0',
            'intelligence' => 'INT DEFAULT 0',
            'charisma' => 'INT DEFAULT 0',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],

        'game_user_skills' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'BIGINT UNSIGNED NOT NULL',
            'combat' => 'INT DEFAULT 0',
            'steal' => 'INT DEFAULT 0',
            'diplomacy' => 'INT DEFAULT 0',
            'investigation' => 'INT DEFAULT 0',
            'survival' => 'INT DEFAULT 0',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],

        'game_user_progress' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'BIGINT UNSIGNED NOT NULL',
            'experience' => 'INT DEFAULT 0',
            'learning_points' => 'INT DEFAULT 0',
            'reputation' => 'INT DEFAULT 0',
            'level' => 'INT DEFAULT 1',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],

        'game_user_vitality' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'BIGINT UNSIGNED NOT NULL',
            'max_life' => 'INT DEFAULT 100',
            'current_life' => 'INT DEFAULT 100',
            'max_energy' => 'INT DEFAULT 100',
            'current_energy' => 'INT DEFAULT 100',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],

        'game_user_items' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'BIGINT UNSIGNED NOT NULL',
            'item_id' => 'INT NOT NULL',
            'quantity' => 'INT DEFAULT 1',
            'is_equipped' => 'BOOLEAN DEFAULT FALSE',
            'equipment_slot' => 'VARCHAR(20) DEFAULT NULL',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],

        'game_user_areas' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'BIGINT UNSIGNED NOT NULL',
            'area_id' => 'INT NOT NULL',
            'scene_id' => 'VARCHAR(50) NOT NULL',
            'is_unlocked' => 'BOOLEAN DEFAULT FALSE',
            'unlocked_at' => 'TIMESTAMP NULL',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],

        'game_user_fight_tokens' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'BIGINT UNSIGNED NOT NULL',
            'token_data' => 'JSON',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'expires_at' => 'TIMESTAMP NULL'
        ],

        'game_user_relations' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'BIGINT UNSIGNED NOT NULL',
            'npc_id' => 'INT NOT NULL',
            'relation_value' => 'INT DEFAULT 0',
            'is_known' => 'BOOLEAN DEFAULT FALSE',
            'fights_won' => 'INT DEFAULT 0',
            'fights_lost' => 'INT DEFAULT 0',
            'fights_draw' => 'INT DEFAULT 0',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],

        'game_user_story' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'BIGINT UNSIGNED NOT NULL',
            'story_text' => 'LONGTEXT',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],

        'game_user_missions' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'BIGINT UNSIGNED NOT NULL',
            'mission_id' => 'INT NOT NULL',
            'status' => 'ENUM("available", "active", "completed", "failed") DEFAULT "available"',
            'started_at' => 'TIMESTAMP NULL',
            'completed_at' => 'TIMESTAMP NULL',
            'expires_at' => 'TIMESTAMP NULL',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],

        'game_user_mission_tasks' => [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_mission_id' => 'INT NOT NULL',
            'task_id' => 'VARCHAR(50) NOT NULL',
            'status' => 'ENUM("pending", "completed", "failed") DEFAULT "pending"',
            'attempts' => 'INT DEFAULT 0',
            'completed_at' => 'TIMESTAMP NULL',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]
    ];

    // Indeksy dla optymalizacji
    const TABLE_INDEXES = [
        'game_users' => [
            'user_id' => 'UNIQUE KEY idx_user_id (user_id)',
            'current_area' => 'KEY idx_current_area (current_area_id)'
        ],
        'game_user_stats' => [
            'user_id' => 'UNIQUE KEY idx_user_id (user_id)'
        ],
        'game_user_skills' => [
            'user_id' => 'UNIQUE KEY idx_user_id (user_id)'
        ],
        'game_user_progress' => [
            'user_id' => 'UNIQUE KEY idx_user_id (user_id)',
            'experience' => 'KEY idx_experience (experience)',
            'level' => 'KEY idx_level (level)'
        ],
        'game_user_vitality' => [
            'user_id' => 'UNIQUE KEY idx_user_id (user_id)'
        ],
        'game_user_items' => [
            'user_item' => 'KEY idx_user_item (user_id, item_id)',
            'equipped' => 'KEY idx_equipped (user_id, is_equipped)'
        ],
        'game_user_areas' => [
            'user_area' => 'UNIQUE KEY idx_user_area_scene (user_id, area_id, scene_id)',
            'unlocked' => 'KEY idx_unlocked (user_id, is_unlocked)'
        ],
        'game_user_fight_tokens' => [
            'user_id' => 'KEY idx_user_id (user_id)',
            'expires' => 'KEY idx_expires (expires_at)'
        ],
        'game_user_relations' => [
            'user_npc' => 'UNIQUE KEY idx_user_npc (user_id, npc_id)',
            'relation' => 'KEY idx_relation (relation_value)'
        ],
        'game_user_story' => [
            'user_id' => 'UNIQUE KEY idx_user_id (user_id)'
        ],
        'game_user_missions' => [
            'user_mission' => 'KEY idx_user_mission (user_id, mission_id)',
            'status' => 'KEY idx_status (status)',
            'expires' => 'KEY idx_expires (expires_at)'
        ],
        'game_user_mission_tasks' => [
            'mission_task' => 'UNIQUE KEY idx_mission_task (user_mission_id, task_id)',
            'status' => 'KEY idx_status (status)'
        ]
    ];

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Tworzy wszystkie tabele gry
     */
    public function createAllTables()
    {
        $results = [];

        foreach (self::TABLES as $tableName) {
            $result = $this->createTable($tableName);
            $results[$tableName] = $result;
        }

        return $results;
    }

    /**
     * Tworzy pojedynczą tabelę
     */
    public function createTable($tableName)
    {
        if (!isset(self::TABLE_SCHEMAS[$tableName])) {
            return ['success' => false, 'error' => "Schemat dla tabeli $tableName nie istnieje"];
        }

        $fullTableName = $this->wpdb->prefix . $tableName;
        $schema = self::TABLE_SCHEMAS[$tableName];
        $indexes = self::TABLE_INDEXES[$tableName] ?? [];

        // Budowanie SQL CREATE TABLE
        $sql = "CREATE TABLE IF NOT EXISTS `$fullTableName` (\n";

        $columns = [];
        foreach ($schema as $column => $definition) {
            $columns[] = "  `$column` $definition";
        }

        $sql .= implode(",\n", $columns);

        // Dodawanie indeksów
        if (!empty($indexes)) {
            $sql .= ",\n";
            $indexDefinitions = [];
            foreach ($indexes as $indexName => $indexDef) {
                $indexDefinitions[] = "  $indexDef";
            }
            $sql .= implode(",\n", $indexDefinitions);
        }

        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $result = $this->wpdb->query($sql);

        if ($result === false) {
            return [
                'success' => false,
                'error' => $this->wpdb->last_error,
                'sql' => $sql
            ];
        }

        return ['success' => true, 'sql' => $sql];
    }

    /**
     * Sprawdza czy tabela istnieje
     */
    public function tableExists($tableName)
    {
        $fullTableName = $this->wpdb->prefix . $tableName;
        $result = $this->wpdb->get_var("SHOW TABLES LIKE '$fullTableName'");
        return $result === $fullTableName;
    }

    /**
     * Usuwa tabelę (ostrożnie!)
     */
    public function dropTable($tableName)
    {
        $fullTableName = $this->wpdb->prefix . $tableName;
        $result = $this->wpdb->query("DROP TABLE IF EXISTS `$fullTableName`");
        return $result !== false;
    }

    /**
     * Zwraca pełną nazwę tabeli z prefixem
     */
    public function getTableName($tableName)
    {
        return $this->wpdb->prefix . $tableName;
    }

    /**
     * Zwraca obiekt wpdb
     */
    public function getWpdb()
    {
        return $this->wpdb;
    }
}
