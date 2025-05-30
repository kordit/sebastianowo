<?php

/**
 * Migration - aktualizuje strukturę bazy danych NPC
 */

if (!defined('ABSPATH')) {
    exit;
}

class NPC_Migration
{
    private $wpdb;
    private $dialog_table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->dialog_table = $this->wpdb->prefix . 'npc_dialogs';
    }

    /**
     * Uruchamia migrację usuwającą pole is_starting_dialog
     */
    public function run()
    {
        $this->remove_is_starting_dialog_column();
    }

    /**
     * Usuwa kolumnę is_starting_dialog z tabeli dialogów
     */
    private function remove_is_starting_dialog_column()
    {
        // Sprawdza czy kolumna istnieje
        $column_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->dialog_table} LIKE 'is_starting_dialog'"
        );

        if (!empty($column_exists)) {
            // Kolumna istnieje, usuń ją
            $this->wpdb->query(
                "ALTER TABLE {$this->dialog_table} DROP COLUMN is_starting_dialog"
            );

            return true;
        }

        return false; // Kolumna nie istnieje
    }

    /**
     * Sprawdza czy migracja została wykonana
     */
    public function check_migration_status()
    {
        $column_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->dialog_table} LIKE 'is_starting_dialog'"
        );

        return empty($column_exists);
    }
}
