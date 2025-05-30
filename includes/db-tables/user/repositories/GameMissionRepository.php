<?php

/**
 * Repozytorium dla tabeli game_user_mission_tasks
 * Obsługuje wszystkie operacje na misjach użytkowników
 */
class GameMissionRepository
{
    private $wpdb;
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'game_user_mission_tasks';
    }

    /**
     * Pobiera wszystkie misje dla użytkownika
     */
    public function getUserMissions($user_id)
    {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE user_id = %d ORDER BY mission_id, task_id", $user_id),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Pobiera konkretną misję użytkownika
     */
    public function getUserMission($user_id, $mission_id)
    {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE user_id = %d AND mission_id = %d ORDER BY task_id", $user_id, $mission_id),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Pobiera konkretne zadanie użytkownika
     */
    public function getUserTask($user_id, $mission_id, $task_id)
    {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE user_id = %d AND mission_id = %d AND task_id = %s", $user_id, $mission_id, $task_id),
            ARRAY_A
        );

        return $result;
    }

    /**
     * Pobiera zadanie po ID rekordu
     */
    public function getById($id)
    {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );

        return $result;
    }

    /**
     * Aktualizuje status misji
     */
    public function updateMissionStatus($user_id, $mission_id, $status, $data = [])
    {
        $update_data = [
            'mission_status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Dodaj dodatkowe pola jeśli są podane
        if (isset($data['mission_started_at'])) {
            $update_data['mission_started_at'] = $data['mission_started_at'];
        }
        if (isset($data['mission_completed_at'])) {
            $update_data['mission_completed_at'] = $data['mission_completed_at'];
        }
        if (isset($data['mission_expires_at'])) {
            $update_data['mission_expires_at'] = $data['mission_expires_at'];
        }

        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            [
                'user_id' => $user_id,
                'mission_id' => $mission_id
            ]
        );

        if ($result === false) {
            throw new Exception('Nie udało się zaktualizować statusu misji: ' . $this->wpdb->last_error);
        }

        return $result;
    }

    /**
     * Aktualizuje status zadania
     */
    public function updateTaskStatus($user_id, $mission_id, $task_id, $status, $data = [])
    {
        $update_data = [
            'task_status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Dodaj dodatkowe pola jeśli są podane
        if (isset($data['task_completed_at'])) {
            $update_data['task_completed_at'] = $data['task_completed_at'];
        }
        if (isset($data['task_attempts'])) {
            $update_data['task_attempts'] = $data['task_attempts'];
        }
        if (isset($data['task_wins'])) {
            $update_data['task_wins'] = $data['task_wins'];
        }
        if (isset($data['task_losses'])) {
            $update_data['task_losses'] = $data['task_losses'];
        }
        if (isset($data['task_draws'])) {
            $update_data['task_draws'] = $data['task_draws'];
        }

        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            [
                'user_id' => $user_id,
                'mission_id' => $mission_id,
                'task_id' => $task_id
            ]
        );

        if ($result === false) {
            throw new Exception('Nie udało się zaktualizować statusu zadania: ' . $this->wpdb->last_error);
        }

        return $result;
    }

    /**
     * Aktualizuje zadanie po ID rekordu
     */
    public function updateTask($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        $result = $this->wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id]
        );

        if ($result === false) {
            throw new Exception('Nie udało się zaktualizować zadania: ' . $this->wpdb->last_error);
        }

        return $result;
    }

    /**
     * Aktualizuje limit czasu misji
     */
    public function updateMissionTimeLimit($user_id, $mission_id, $time_limit)
    {
        $result = $this->wpdb->update(
            $this->table_name,
            [
                'mission_time_limit' => $time_limit,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'user_id' => $user_id,
                'mission_id' => $mission_id
            ]
        );

        if ($result === false) {
            throw new Exception('Nie udało się zaktualizować limitu czasu misji: ' . $this->wpdb->last_error);
        }

        return $result;
    }

    /**
     * Aktualizuje daty misji
     */
    public function updateMissionDates($user_id, $mission_id, $dates)
    {
        $update_data = ['updated_at' => date('Y-m-d H:i:s')];

        if (isset($dates['mission_started_at'])) {
            $update_data['mission_started_at'] = $dates['mission_started_at'];
        }
        if (isset($dates['mission_completed_at'])) {
            $update_data['mission_completed_at'] = $dates['mission_completed_at'];
        }
        if (isset($dates['mission_expires_at'])) {
            $update_data['mission_expires_at'] = $dates['mission_expires_at'];
        }

        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            [
                'user_id' => $user_id,
                'mission_id' => $mission_id
            ]
        );

        if ($result === false) {
            throw new Exception('Nie udało się zaktualizować dat misji: ' . $this->wpdb->last_error);
        }

        return $result;
    }

    /**
     * Kończy zadanie
     */
    public function completeTask($user_id, $mission_id, $task_id)
    {
        $completed_at = date('Y-m-d H:i:s');

        return $this->updateTaskStatus($user_id, $mission_id, $task_id, 'completed', [
            'task_completed_at' => $completed_at
        ]);
    }

    /**
     * Zwiększa liczbę prób zadania
     */
    public function incrementTaskAttempts($user_id, $mission_id, $task_id)
    {
        $task = $this->getUserTask($user_id, $mission_id, $task_id);
        if (!$task) {
            throw new Exception('Zadanie nie zostało znalezione');
        }

        $new_attempts = (int)$task['task_attempts'] + 1;

        return $this->updateTaskStatus($user_id, $mission_id, $task_id, $task['task_status'], [
            'task_attempts' => $new_attempts
        ]);
    }

    /**
     * Aktualizuje statystyki walki zadania
     */
    public function updateTaskCombatStats($user_id, $mission_id, $task_id, $wins = 0, $losses = 0, $draws = 0)
    {
        $task = $this->getUserTask($user_id, $mission_id, $task_id);
        if (!$task) {
            throw new Exception('Zadanie nie zostało znalezione');
        }

        $update_data = [
            'task_wins' => (int)$task['task_wins'] + $wins,
            'task_losses' => (int)$task['task_losses'] + $losses,
            'task_draws' => (int)$task['task_draws'] + $draws,
            'task_attempts' => (int)$task['task_attempts'] + 1
        ];

        return $this->updateTaskStatus($user_id, $mission_id, $task_id, $task['task_status'], $update_data);
    }

    /**
     * Aktualizuje konfigurację zadania (pola edytowalne)
     */
    public function updateTaskConfig($id, $config)
    {
        $allowed_fields = [
            'task_optional',
            'task_attempt_limit',
            'task_type',
            'task_location',
            'task_location_scene',
            'task_checkpoint_npc',
            'task_defeat_enemies'
        ];

        $update_data = [];
        foreach ($config as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $update_data[$field] = $value;
            }
        }

        if (empty($update_data)) {
            throw new Exception('Brak prawidłowych pól do aktualizacji');
        }

        return $this->updateTask($id, $update_data);
    }

    /**
     * Pobiera misje wygasające wkrótce
     */
    public function getExpiringMissions($hours = 24)
    {
        $expiry_time = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT DISTINCT user_id, mission_id, mission_title, mission_expires_at 
                 FROM {$this->table_name} 
                 WHERE mission_expires_at IS NOT NULL 
                 AND mission_expires_at <= %s 
                 AND mission_status IN ('not_started', 'in_progress') 
                 ORDER BY mission_expires_at ASC",
                $expiry_time
            ),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Pobiera aktywne misje użytkownika
     */
    public function getActiveMissions($user_id)
    {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT DISTINCT mission_id, mission_title, mission_description, mission_status, mission_started_at, mission_expires_at
                 FROM {$this->table_name} 
                 WHERE user_id = %d 
                 AND mission_status IN ('not_started', 'in_progress')
                 ORDER BY mission_id",
                $user_id
            ),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Pobiera ukończone misje użytkownika
     */
    public function getCompletedMissions($user_id)
    {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT DISTINCT mission_id, mission_title, mission_description, mission_completed_at
                 FROM {$this->table_name} 
                 WHERE user_id = %d 
                 AND mission_status = 'completed'
                 ORDER BY mission_completed_at DESC",
                $user_id
            ),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Sprawdza czy wszystkie zadania misji są ukończone
     */
    public function isMissionCompleted($user_id, $mission_id)
    {
        $total_tasks = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} 
                 WHERE user_id = %d AND mission_id = %d AND task_optional = 0",
                $user_id,
                $mission_id
            )
        );

        $completed_tasks = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} 
                 WHERE user_id = %d AND mission_id = %d AND task_optional = 0 AND task_status = 'completed'",
                $user_id,
                $mission_id
            )
        );

        return (int)$total_tasks === (int)$completed_tasks;
    }

    /**
     * Zlicza misje użytkownika według statusu
     */
    public function countUserMissionsByStatus($user_id, $status = null)
    {
        $sql = "SELECT COUNT(DISTINCT mission_id) FROM {$this->table_name} WHERE user_id = %d";
        $params = [$user_id];

        if ($status) {
            $sql .= " AND mission_status = %s";
            $params[] = $status;
        }

        return (int)$this->wpdb->get_var($this->wpdb->prepare($sql, $params));
    }
}
