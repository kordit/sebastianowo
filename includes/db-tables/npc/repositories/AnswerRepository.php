<?php

/**
 * Answer Repository
 * Obsługuje operacje CRUD dla odpowiedzi w dialogach
 */

if (!defined('ABSPATH')) {
    exit;
}

class NPC_AnswerRepository
{
    private $wpdb;
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'npc_answers';
    }

    /**
     * Pobiera wszystkie odpowiedzi dla dialogu
     */
    public function get_by_dialog_id($dialog_id, $status = 'active')
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE dialog_id = %d AND status = %s 
             ORDER BY answer_order ASC, id ASC",
            $dialog_id,
            $status
        );

        return $this->wpdb->get_results($sql);
    }

    /**
     * Pobiera odpowiedź po ID
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
     * Tworzy nową odpowiedź
     */
    public function create($data)
    {
        $result = $this->wpdb->insert(
            $this->table_name,
            [
                'dialog_id' => $data['dialog_id'],
                'text' => $data['text'],
                'next_dialog_id' => $data['next_dialog_id'] ?? null,
                'answer_order' => $data['answer_order'] ?? 0,
                'conditions' => isset($data['conditions']) ? json_encode($data['conditions']) : null,
                'actions' => isset($data['actions']) ? json_encode($data['actions']) : null,
                'status' => $data['status'] ?? 'active'
            ],
            [
                '%d',
                '%s',
                '%d',
                '%d',
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
     * Aktualizuje odpowiedź
     */
    public function update($id, $data)
    {
        $update_data = [];
        $format = [];

        if (isset($data['text'])) {
            $update_data['text'] = $data['text'];
            $format[] = '%s';
        }
        if (isset($data['next_dialog_id'])) {
            $update_data['next_dialog_id'] = $data['next_dialog_id'];
            $format[] = '%d';
        }
        if (isset($data['answer_order'])) {
            $update_data['answer_order'] = $data['answer_order'];
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
     * Usuwa odpowiedź
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
     * Sprawdza czy odpowiedź istnieje
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
     * Pobiera liczbę odpowiedzi dla dialogu
     */
    public function count_by_dialog($dialog_id)
    {
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE dialog_id = %d",
            $dialog_id
        );

        return $this->wpdb->get_var($sql);
    }

    /**
     * Pobiera odpowiedzi które prowadzą do określonego dialogu
     */
    public function get_leading_to_dialog($dialog_id)
    {
        $sql = $this->wpdb->prepare(
            "SELECT a.*, d.title as dialog_title, n.name as npc_name
             FROM {$this->table_name} a
             JOIN {$this->wpdb->prefix}npc_dialogs d ON a.dialog_id = d.id
             JOIN {$this->wpdb->prefix}npc_entities n ON d.npc_id = n.id
             WHERE a.next_dialog_id = %d AND a.status = 'active'",
            $dialog_id
        );

        return $this->wpdb->get_results($sql);
    }
}
