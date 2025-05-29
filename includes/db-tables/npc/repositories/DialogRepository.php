<?php

/**
 * Dialog Repository
 * Obsługuje operacje CRUD dla dialogów NPC
 */

if (!defined('ABSPATH')) {
    exit;
}

class NPC_DialogRepository
{
    private $wpdb;
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'npc_dialogs';
    }

    /**
     * Pobiera wszystkie dialogi dla NPC
     */
    public function get_by_npc_id($npc_id, $status = 'active')
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE npc_id = %d AND status = %s 
             ORDER BY dialog_order ASC, id ASC",
            $npc_id,
            $status
        );

        return $this->wpdb->get_results($sql);
    }

    /**
     * Pobiera dialog po ID
     */
    public function get_by_id($id)
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );

        return $this->wpdb->get_row($sql);
    }

    /**
     * Pobiera dialog początkowy dla NPC
     */
    public function get_starting_dialog($npc_id)
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE npc_id = %d AND is_starting_dialog = 1 AND status = 'active'
             ORDER BY dialog_order ASC
             LIMIT 1",
            $npc_id
        );

        return $this->wpdb->get_row($sql);
    }

    /**
     * Tworzy nowy dialog
     */
    public function create($data)
    {
        $result = $this->wpdb->insert(
            $this->table_name,
            [
                'npc_id' => $data['npc_id'],
                'title' => $data['title'],
                'content' => $data['content'],
                'dialog_order' => $data['dialog_order'] ?? 0,
                'conditions' => isset($data['conditions']) ? json_encode($data['conditions']) : null,
                'actions' => isset($data['actions']) ? json_encode($data['actions']) : null,
                'is_starting_dialog' => $data['is_starting_dialog'] ?? 0,
                'status' => $data['status'] ?? 'active'
            ],
            [
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
                '%d',
                '%s'
            ]
        );

        if ($result === false) {
            return false;
        }

        return $this->wpdb->insert_id;
    }

    /**
     * Aktualizuje dialog
     */
    public function update($id, $data)
    {
        $update_data = [];
        $format = [];

        if (isset($data['title'])) {
            $update_data['title'] = $data['title'];
            $format[] = '%s';
        }
        if (isset($data['content'])) {
            $update_data['content'] = $data['content'];
            $format[] = '%s';
        }
        if (isset($data['dialog_order'])) {
            $update_data['dialog_order'] = $data['dialog_order'];
            $format[] = '%d';
        }
        if (isset($data['conditions'])) {
            $update_data['conditions'] = json_encode($data['conditions']);
            $format[] = '%s';
        }
        if (isset($data['actions'])) {
            $update_data['actions'] = json_encode($data['actions']);
            $format[] = '%s';
        }
        if (isset($data['is_starting_dialog'])) {
            $update_data['is_starting_dialog'] = $data['is_starting_dialog'];
            $format[] = '%d';
        }
        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
            $format[] = '%s';
        }

        if (empty($update_data)) {
            return false;
        }

        return $this->wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $id],
            $format,
            ['%d']
        );
    }

    /**
     * Usuwa dialog
     */
    public function delete($id)
    {
        return $this->wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );
    }

    /**
     * Sprawdza czy dialog istnieje
     */
    public function exists($id)
    {
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE id = %d",
            $id
        );

        return $this->wpdb->get_var($sql) > 0;
    }

    /**
     * Ustawia dialog jako początkowy (usuwa flagę z innych)
     */
    public function set_as_starting($id, $npc_id)
    {
        // Najpierw usuń flagę z wszystkich dialogów tego NPC
        $this->wpdb->update(
            $this->table_name,
            ['is_starting_dialog' => 0],
            ['npc_id' => $npc_id],
            ['%d'],
            ['%d']
        );

        // Ustaw flagę dla wybranego dialogu
        return $this->wpdb->update(
            $this->table_name,
            ['is_starting_dialog' => 1],
            ['id' => $id],
            ['%d'],
            ['%d']
        );
    }

    /**
     * Pobiera liczbę dialogów dla NPC
     */
    public function count_by_npc($npc_id)
    {
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE npc_id = %d",
            $npc_id
        );

        return $this->wpdb->get_var($sql);
    }
}
