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
     * Pobiera dialogi dla NPC w określonej lokalizacji
     */
    public function get_by_npc_id_and_location($npc_id, $location = null, $status = 'active')
    {
        if ($location === null) {
            return $this->get_by_npc_id($npc_id, $status);
        }

        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE npc_id = %d AND location = %s AND status = %s 
             ORDER BY dialog_order ASC, id ASC",
            $npc_id,
            $location,
            $status
        );

        return $this->wpdb->get_results($sql);
    }

    /**
     * Pobiera dostępne lokalizacje dla dialogów NPC
     */
    public function get_locations_by_npc_id($npc_id)
    {
        $sql = $this->wpdb->prepare(
            "SELECT DISTINCT location FROM {$this->table_name} 
             WHERE npc_id = %d AND location IS NOT NULL AND location != '' 
             ORDER BY location ASC",
            $npc_id
        );

        return $this->wpdb->get_col($sql);
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
     * 
     * Zwraca pierwszy dialog według kolejności ustalonej przez drag and drop
     * Teraz system nie używa flagi is_starting_dialog - zamiast tego
     * pierwszy dialog to ten, który ma najniższą wartość dialog_order
     */
    public function get_starting_dialog($npc_id)
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE npc_id = %d AND status = 'active'
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
                'location' => $data['location'] ?? null,
                'conditions' => isset($data['conditions']) ? json_encode($data['conditions']) : null,
                'actions' => isset($data['actions']) ? json_encode($data['actions']) : null,
                'status' => $data['status'] ?? 'active'
            ],
            [
                '%d',
                '%s',
                '%s',
                '%d',
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
        if (isset($data['location'])) {
            $update_data['location'] = $data['location'];
            $format[] = '%s';
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
     * Ta metoda została usunięta, ponieważ nie korzystamy już z flagi is_starting_dialog
     * Zamiast tego ustalamy dialog początkowy na podstawie kolejności (dialog_order)
     * 
     * @deprecated
     */

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

    /**
     * Inicjalizuje lub naprawia wartości dialog_order dla wszystkich dialogów NPC
     * 
     * Ta funkcja zapewnia, że każdy dialog ma poprawną wartość dialog_order,
     * co zapobiega resetowaniu kolejności po odświeżeniu strony.
     * 
     * @param int $npc_id ID postaci NPC
     * @return bool Czy operacja się powiodła
     */
    public function initialize_dialog_order($npc_id)
    {
        // Pobierz wszystkie dialogi dla NPC posortowane według aktualnej wartości dialog_order
        $sql = $this->wpdb->prepare(
            "SELECT id, dialog_order FROM {$this->table_name} 
             WHERE npc_id = %d AND status = 'active' 
             ORDER BY dialog_order ASC, id ASC",
            $npc_id
        );

        $dialogs = $this->wpdb->get_results($sql);
        if (empty($dialogs)) {
            return true; // Brak dialogów do zaktualizowania
        }

        $success = true;

        // Aktualizuj kolejność dialogów, przypisując im kolejne numery od 0
        foreach ($dialogs as $index => $dialog) {
            $new_order = $index; // Zaczynamy od 0

            if ($dialog->dialog_order !== $new_order) {
                $result = $this->wpdb->update(
                    $this->table_name,
                    ['dialog_order' => $new_order],
                    ['id' => $dialog->id],
                    ['%d'],
                    ['%d']
                );

                if ($result === false) {
                    $success = false;
                }
            }
        }

        // Pierwszy dialog jest automatycznie dialogiem początkowym dzięki kolejności dialog_order

        return $success;
    }
}
