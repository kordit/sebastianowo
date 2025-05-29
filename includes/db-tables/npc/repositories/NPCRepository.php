<?php

/**
 * NPC Repository
 * Obsługuje operacje CRUD dla NPC
 */

if (!defined('ABSPATH')) {
    exit;
}

class NPC_NPCRepository
{
    private $wpdb;
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'npc_entities';
    }

    /**
     * Pobiera wszystkie NPC
     */
    public function get_all()
    {
        $sql = "SELECT * FROM {$this->table_name} ORDER BY name ASC";

        return $this->wpdb->get_results($sql);
    }

    /**
     * Pobiera NPC po ID
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
     * Tworzy nowe NPC
     */
    public function create($data)
    {
        $result = $this->wpdb->insert(
            $this->table_name,
            [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'image_url' => $data['image_url'] ?? '',
                'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );

        if ($result === false) {
            return false;
        }

        return $this->wpdb->insert_id;
    }

    /**
     * Aktualizuje NPC
     */
    public function update($id, $data)
    {
        $update_data = [];
        $format = [];

        if (isset($data['name'])) {
            $update_data['name'] = $data['name'];
            $format[] = '%s';
        }
        if (isset($data['description'])) {
            $update_data['description'] = $data['description'];
            $format[] = '%s';
        }
        if (isset($data['image_url'])) {
            $update_data['image_url'] = $data['image_url'];
            $format[] = '%s';
        }

        if (isset($data['metadata'])) {
            $update_data['metadata'] = json_encode($data['metadata']);
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
     * Usuwa NPC
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
     * Sprawdza czy NPC istnieje
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
     * Pobiera statystyki NPC
     */
    public function get_stats()
    {
        $sql = "SELECT 
                    COUNT(*) as count
                FROM {$this->table_name}";

        $count = $this->wpdb->get_var($sql);

        $stats = ['count' => $count];

        return $stats;
    }
}
