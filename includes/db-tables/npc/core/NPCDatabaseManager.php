<?php

/**
 * NPC Database Manager
 * Zarządza tabelami bazy danych dla systemu NPC
 */

if (!defined('ABSPATH')) {
    exit;
}

class NPC_DatabaseManager
{
    private $wpdb;
    private $npc_table;
    private $dialog_table;
    private $answer_table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Nazwy tabel
        $this->npc_table = $this->wpdb->prefix . 'npc_entities';
        $this->dialog_table = $this->wpdb->prefix . 'npc_dialogs';
        $this->answer_table = $this->wpdb->prefix . 'npc_answers';
    }

    /**
     * Tworzy wszystkie tabele systemu NPC
     */
    public function create_tables()
    {
        $this->create_npc_table();
        $this->create_dialog_table();
        $this->create_answer_table();
    }

    /**
     * Tworzy tabelę NPC
     */
    private function create_npc_table()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->npc_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            image_url varchar(500),
            location varchar(255),
            status enum('active', 'inactive') DEFAULT 'active',
            metadata json,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_status (status),
            INDEX idx_location (location)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tworzy tabelę dialogów
     */
    private function create_dialog_table()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->dialog_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            npc_id int(11) NOT NULL,
            title varchar(255) NOT NULL,
            content text NOT NULL,
            dialog_order int(11) DEFAULT 0,
            conditions json,
            actions json,
            status enum('active', 'inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (npc_id) REFERENCES {$this->npc_table}(id) ON DELETE CASCADE,
            INDEX idx_npc_id (npc_id),
            INDEX idx_status (status),
            INDEX idx_dialog_order (dialog_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Tworzy tabelę odpowiedzi
     */
    private function create_answer_table()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->answer_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            dialog_id int(11) NOT NULL,
            text varchar(500) NOT NULL,
            next_dialog_id int(11) NULL,
            answer_order int(11) DEFAULT 0,
            conditions json,
            actions json,
            status enum('active', 'inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (dialog_id) REFERENCES {$this->dialog_table}(id) ON DELETE CASCADE,
            FOREIGN KEY (next_dialog_id) REFERENCES {$this->dialog_table}(id) ON DELETE SET NULL,
            INDEX idx_dialog_id (dialog_id),
            INDEX idx_next_dialog (next_dialog_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Usuwa wszystkie tabele systemu NPC
     */
    public function drop_tables()
    {
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->answer_table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->dialog_table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->npc_table}");
    }

    /**
     * Sprawdza czy tabele istnieją
     */
    public function tables_exist()
    {
        $npc_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->npc_table}'") == $this->npc_table;
        $dialog_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->dialog_table}'") == $this->dialog_table;
        $answer_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->answer_table}'") == $this->answer_table;

        return $npc_exists && $dialog_exists && $answer_exists;
    }

    /**
     * Pobiera nazwy tabel
     */
    public function get_table_names()
    {
        return [
            'npc' => $this->npc_table,
            'dialog' => $this->dialog_table,
            'answer' => $this->answer_table
        ];
    }
}
