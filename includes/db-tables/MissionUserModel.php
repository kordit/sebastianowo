<?php

/**
 * Model zarządzania misjami użytkownika
 * 
 * Zapewnia interfejs do operacji CRUD na misjach gracza
 */
class GameMissionUserModel
{
    private $user_id;
    private $tables;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->tables = GameDatabaseManager::get_instance()->get_table_names();
    }

    /**
     * Pobiera dane wszystkich misji użytkownika
     * 
     * @return array Dane misji użytkownika
     */
    public function get_all_missions()
    {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT um.*, p.post_title as mission_name 
             FROM {$this->tables['user_missions']} um 
             LEFT JOIN {$wpdb->posts} p ON um.mission_id = p.ID 
             WHERE um.user_id = %d 
             ORDER BY um.created_at DESC",
            $this->user_id
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Pobiera dane konkretnej misji użytkownika
     * 
     * @param int $mission_id ID misji
     * @return array|null Dane misji lub null jeśli nie znaleziono
     */
    public function get_mission($mission_id)
    {
        global $wpdb;

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT um.*, p.post_title as mission_name 
             FROM {$this->tables['user_missions']} um 
             LEFT JOIN {$wpdb->posts} p ON um.mission_id = p.ID 
             WHERE um.user_id = %d AND um.mission_id = %d",
            $this->user_id,
            $mission_id
        ), ARRAY_A);

        return $result ?: null;
    }

    /**
     * Tworzy nową misję dla użytkownika
     * 
     * @param int $mission_id ID misji z CPT
     * @return bool|int False w przypadku błędu lub ID nowej misji
     */
    public function create_mission($mission_id)
    {
        // Sprawdzamy czy misja istnieje
        $mission_post = get_post($mission_id);
        if (!$mission_post || $mission_post->post_type !== 'mission') {
            return false;
        }

        // Pobieramy dane misji z ACF
        $mission_type = get_field('mission_type', $mission_id);
        $tasks_data = get_field('mission_tasks', $mission_id);

        // Przygotowanie danych zadań
        $tasks_for_db = [];
        if (!empty($tasks_data) && is_array($tasks_data)) {
            foreach ($tasks_data as $task) {
                $task_entry = [
                    'task_id' => $task['task_id'],
                    'task_title' => $task['task_title'],
                    'task_description' => $task['task_description'],
                    'task_optional' => !empty($task['task_optional']),
                    'task_attempt_limit' => intval($task['task_attempt_limit']),
                    'task_type' => $task['task_type'],
                    'status' => 'not_started',
                    'attempts' => 0,
                    'location_visited' => false,
                    'current_scene' => '',
                ];

                // Specyficzne pola w zależności od typu zadania
                switch ($task['task_type']) {
                    case 'checkpoint':
                        $task_entry['location_id'] = !empty($task['task_location']) ? $task['task_location'] : null;
                        $task_entry['scene'] = !empty($task['task_location_scene']) ? $task['task_location_scene'] : null;
                        break;

                    case 'checkpoint_npc':
                        $npcs = [];
                        if (!empty($task['task_checkpoint_npc']) && is_array($task['task_checkpoint_npc'])) {
                            foreach ($task['task_checkpoint_npc'] as $npc_data) {
                                $npcs[] = [
                                    'npc_id' => $npc_data['npc'],
                                    'required_status' => $npc_data['status'],
                                    'current_status' => 'not_started'
                                ];
                            }
                        }
                        $task_entry['npcs'] = $npcs;
                        break;

                    case 'defeat_enemies':
                        $enemies = [];
                        if (!empty($task['task_defeat_enemies']) && is_array($task['task_defeat_enemies'])) {
                            foreach ($task['task_defeat_enemies'] as $enemy_id) {
                                $enemies[] = [
                                    'enemy_id' => $enemy_id,
                                    'defeated' => false
                                ];
                            }
                        }
                        $task_entry['enemies'] = $enemies;
                        break;
                }

                $tasks_for_db[] = $task_entry;
            }
        }

        global $wpdb;

        // Sprawdzamy czy misja już istnieje
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables['user_missions']} 
            WHERE user_id = %d AND mission_id = %d",
            $this->user_id,
            $mission_id
        ));

        if ($existing) {
            return false; // Misja już istnieje
        }

        // Zapisujemy dane misji
        $result = $wpdb->insert(
            $this->tables['user_missions'],
            [
                'user_id' => $this->user_id,
                'mission_id' => $mission_id,
                'mission_type' => $mission_type,
                'status' => 'not_started',
                'start_date' => current_time('mysql'),
                'tasks_data' => json_encode($tasks_for_db),
                'wins' => 0,
                'losses' => 0,
                'draws' => 0
            ]
        );

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Aktualizuje status misji użytkownika
     * 
     * @param int $mission_id ID misji
     * @param string $status Nowy status
     * @return bool Sukces operacji
     */
    public function update_mission_status($mission_id, $status)
    {
        global $wpdb;

        $allowed_statuses = ['not_started', 'in_progress', 'completed', 'failed'];
        if (!in_array($status, $allowed_statuses)) {
            return false;
        }

        $result = $wpdb->update(
            $this->tables['user_missions'],
            [
                'status' => $status,
                'end_date' => in_array($status, ['completed', 'failed']) ? current_time('mysql') : null
            ],
            [
                'user_id' => $this->user_id,
                'mission_id' => $mission_id
            ]
        );

        return $result !== false;
    }

    /**
     * Aktualizuje dane zadania misji
     * 
     * @param int $mission_id ID misji
     * @param string $task_id ID zadania
     * @param array $update_data Dane do aktualizacji
     * @return bool Sukces operacji
     */
    public function update_task_data($mission_id, $task_id, $update_data)
    {
        global $wpdb;

        // Pobierz obecne dane misji
        $mission = $this->get_mission($mission_id);
        if (!$mission) {
            return false;
        }

        $tasks_data = json_decode($mission['tasks_data'], true);
        if (!is_array($tasks_data)) {
            return false;
        }

        // Znajdź i zaktualizuj odpowiednie zadanie
        $found = false;
        foreach ($tasks_data as $key => $task) {
            if ($task['task_id'] === $task_id) {
                $tasks_data[$key] = array_merge($task, $update_data);
                $found = true;
                break;
            }
        }

        if (!$found) {
            return false;
        }

        // Zapisz zaktualizowane dane
        $result = $wpdb->update(
            $this->tables['user_missions'],
            [
                'tasks_data' => json_encode($tasks_data)
            ],
            [
                'user_id' => $this->user_id,
                'mission_id' => $mission_id
            ]
        );

        return $result !== false;
    }

    /**
     * Aktualizuje bilans walki dla misji
     * 
     * @param int $mission_id ID misji
     * @param string $result_type Typ wyniku (win, loss, draw)
     * @return bool Sukces operacji
     */
    public function update_battle_result($mission_id, $result_type)
    {
        global $wpdb;

        // Pobierz obecne dane
        $mission = $this->get_mission($mission_id);
        if (!$mission) {
            return false;
        }

        $update_field = '';
        switch ($result_type) {
            case 'win':
                $update_field = 'wins';
                break;
            case 'loss':
                $update_field = 'losses';
                break;
            case 'draw':
                $update_field = 'draws';
                break;
            default:
                return false;
        }

        $current_value = intval($mission[$update_field]);

        // Zwiększ licznik
        $result = $wpdb->update(
            $this->tables['user_missions'],
            [
                $update_field => $current_value + 1
            ],
            [
                'user_id' => $this->user_id,
                'mission_id' => $mission_id
            ]
        );

        return $result !== false;
    }

    /**
     * Usuwa misję użytkownika
     * 
     * @param int $mission_id ID misji
     * @return bool Sukces operacji
     */
    public function delete_mission($mission_id)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->tables['user_missions'],
            [
                'user_id' => $this->user_id,
                'mission_id' => $mission_id
            ]
        );

        return $result !== false;
    }
}
