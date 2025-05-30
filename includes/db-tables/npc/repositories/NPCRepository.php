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
        $insert_data = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'image_url' => $data['image_url'] ?? '',
            'avatar' => $data['avatar'] ?? null,
            'avatar_full' => $data['avatar_full'] ?? null,
            'avatar_full_back' => $data['avatar_full_back'] ?? null,
            'strength' => $data['strength'] ?? 0,
            'defence' => $data['defence'] ?? 0,
            'dexterity' => $data['dexterity'] ?? 0,
            'perception' => $data['perception'] ?? 0,
            'technical' => $data['technical'] ?? 0,
            'charisma' => $data['charisma'] ?? 0,
            'combat' => $data['combat'] ?? 0,
            'steal' => $data['steal'] ?? 0,
            'craft' => $data['craft'] ?? 0,
            'trade' => $data['trade'] ?? 0,
            'relations' => $data['relations'] ?? 0,
            'street' => $data['street'] ?? 0,
            'life' => $data['life'] ?? 0,
            'max_life' => $data['max_life'] ?? 0,
            'location' => $data['location'] ?? null,
            'status' => $data['status'] ?? 'active',
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
        ];

        $format = [
            '%s',
            '%s',
            '%s',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s'
        ];

        $result = $this->wpdb->insert($this->table_name, $insert_data, $format);

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
        if (isset($data['avatar'])) {
            $update_data['avatar'] = $data['avatar'];
            $format[] = '%d';
        }
        if (isset($data['avatar_full'])) {
            $update_data['avatar_full'] = $data['avatar_full'];
            $format[] = '%d';
        }
        if (isset($data['avatar_full_back'])) {
            $update_data['avatar_full_back'] = $data['avatar_full_back'];
            $format[] = '%d';
        }
        if (isset($data['strength'])) {
            $update_data['strength'] = $data['strength'];
            $format[] = '%d';
        }
        if (isset($data['defence'])) {
            $update_data['defence'] = $data['defence'];
            $format[] = '%d';
        }
        if (isset($data['dexterity'])) {
            $update_data['dexterity'] = $data['dexterity'];
            $format[] = '%d';
        }
        if (isset($data['perception'])) {
            $update_data['perception'] = $data['perception'];
            $format[] = '%d';
        }
        if (isset($data['technical'])) {
            $update_data['technical'] = $data['technical'];
            $format[] = '%d';
        }
        if (isset($data['charisma'])) {
            $update_data['charisma'] = $data['charisma'];
            $format[] = '%d';
        }
        if (isset($data['combat'])) {
            $update_data['combat'] = $data['combat'];
            $format[] = '%d';
        }
        if (isset($data['steal'])) {
            $update_data['steal'] = $data['steal'];
            $format[] = '%d';
        }
        if (isset($data['craft'])) {
            $update_data['craft'] = $data['craft'];
            $format[] = '%d';
        }
        if (isset($data['trade'])) {
            $update_data['trade'] = $data['trade'];
            $format[] = '%d';
        }
        if (isset($data['relations'])) {
            $update_data['relations'] = $data['relations'];
            $format[] = '%d';
        }
        if (isset($data['street'])) {
            $update_data['street'] = $data['street'];
            $format[] = '%d';
        }
        if (isset($data['life'])) {
            $update_data['life'] = $data['life'];
            $format[] = '%d';
        }
        if (isset($data['max_life'])) {
            $update_data['max_life'] = $data['max_life'];
            $format[] = '%d';
        }
        if (isset($data['location'])) {
            $update_data['location'] = $data['location'];
            $format[] = '%s';
        }
        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
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
