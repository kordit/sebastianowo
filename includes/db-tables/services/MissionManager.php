<?php

/**
 * Manager misji - odpowiedzialny za przypisywanie i zarządzanie misjami użytkowników
 */
class MissionManager
{
    private $wpdb;
    private $mission_builder;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->mission_builder = new MissionBuilder();
    }

    /**
     * Przypisuje misję użytkownikowi
     */
    public function assignMissionToUser($user_id, $mission_id)
    {
        // Pobierz misję z WordPress
        $mission_result = $this->mission_builder->getMissionFromWordPress($mission_id);

        if (!$mission_result['success']) {
            return [
                'success' => false,
                'message' => 'Nie można pobrać misji: ' . $mission_result['message']
            ];
        }

        $mission = $mission_result['mission'];

        // Waliduj misję
        $validation = $this->mission_builder->validateWordPressMission($mission);
        if (!$validation['success']) {
            return [
                'success' => false,
                'message' => 'Misja jest niepoprawna: ' . $validation['message']
            ];
        }

        // Sprawdź czy użytkownik już ma tę misję
        $existing = $this->getUserMission($user_id, $mission_id);
        if ($existing) {
            return [
                'success' => false,
                'message' => 'Użytkownik już ma przypisaną tę misję'
            ];
        }

        // Wstaw misję do bazy
        $missions_table = $this->wpdb->prefix . 'game_user_missions';
        $tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        $this->wpdb->query('START TRANSACTION');

        try {
            // Wstaw główną misję
            $expires_at = null;
            if ($mission['mission_time_limit'] > 0) {
                $expires_at = date('Y-m-d H:i:s', time() + ($mission['mission_time_limit'] * 3600));
            }

            $result = $this->wpdb->insert(
                $missions_table,
                [
                    'user_id' => $user_id,
                    'mission_id' => $mission_id,
                    'mission_description' => $mission['mission_description'],
                    'mission_time_limit' => $mission['mission_time_limit'],
                    'mission_type' => $mission['mission_type'],
                    'status' => 'not_started',
                    'expires_at' => $expires_at,
                    'started_at' => current_time('mysql')
                ]
            );

            if (!$result) {
                throw new Exception('Błąd przy zapisywaniu misji: ' . $this->wpdb->last_error);
            }

            $user_mission_id = $this->wpdb->insert_id;

            // Wstaw zadania
            foreach ($validation['recipe']['mission_tasks'] as $task) {
                $task_result = $this->wpdb->insert(
                    $tasks_table,
                    [
                        'user_mission_id' => $user_mission_id,
                        'task_id' => $task['task_id'],
                        'task_title' => $task['task_title'],
                        'task_description' => $task['task_description'],
                        'task_optional' => $task['task_optional'] ? 1 : 0,
                        'task_attempt_limit' => $task['task_attempt_limit'],
                        'task_type' => $task['task_type'],
                        'task_location' => $task['task_location'],
                        'task_location_scene' => $task['task_location_scene'],
                        'task_checkpoint_npc' => !empty($task['task_checkpoint_npc']) ? json_encode($task['task_checkpoint_npc']) : null,
                        'task_defeat_enemies' => !empty($task['task_defeat_enemies']) ? json_encode($task['task_defeat_enemies']) : null,
                        'status' => 'not_started'
                    ]
                );

                if (!$task_result) {
                    throw new Exception('Błąd przy zapisywaniu zadania ' . $task['task_id'] . ': ' . $this->wpdb->last_error);
                }
            }

            $this->wpdb->query('COMMIT');

            return [
                'success' => true,
                'user_mission_id' => $user_mission_id,
                'tasks_count' => count($validation['recipe']['mission_tasks']),
                'expires_at' => $expires_at
            ];
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Pobiera misję użytkownika
     */
    public function getUserMission($user_id, $mission_id)
    {
        $missions_table = $this->wpdb->prefix . 'game_user_missions';

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $missions_table WHERE user_id = %d AND mission_id = %d",
                $user_id,
                $mission_id
            )
        );
    }

    /**
     * Pobiera wszystkie misje użytkownika
     */
    public function getUserMissions($user_id, $status = null)
    {
        $missions_table = $this->wpdb->prefix . 'game_user_missions';

        $sql = "SELECT * FROM $missions_table WHERE user_id = %d";
        $params = [$user_id];

        if ($status) {
            $sql .= " AND status = %s";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, ...$params)
        );
    }

    /**
     * Pobiera zadania misji użytkownika
     */
    public function getUserMissionTasks($user_mission_id)
    {
        $tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM $tasks_table WHERE user_mission_id = %d ORDER BY id",
                $user_mission_id
            )
        );
    }

    /**
     * Aktualizuje status misji
     */
    public function updateMissionStatus($user_mission_id, $status)
    {
        $missions_table = $this->wpdb->prefix . 'game_user_missions';

        $update_data = ['status' => $status];

        if ($status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
        }

        return $this->wpdb->update(
            $missions_table,
            $update_data,
            ['id' => $user_mission_id]
        );
    }

    /**
     * Aktualizuje status zadania
     */
    public function updateTaskStatus($user_mission_id, $task_id, $status)
    {
        $tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        $update_data = ['status' => $status];

        if ($status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
        }

        return $this->wpdb->update(
            $tasks_table,
            $update_data,
            [
                'user_mission_id' => $user_mission_id,
                'task_id' => $task_id
            ]
        );
    }

    /**
     * Sprawdza postęp misji i automatycznie aktualizuje status
     */
    public function checkMissionProgress($user_mission_id)
    {
        $tasks = $this->getUserMissionTasks($user_mission_id);

        if (empty($tasks)) {
            return false;
        }

        $total_tasks = count($tasks);
        $completed_tasks = 0;
        $failed_tasks = 0;
        $required_tasks = 0;

        foreach ($tasks as $task) {
            if ($task->task_optional == 0) {
                $required_tasks++;
            }

            if ($task->status === 'completed') {
                $completed_tasks++;
            } elseif ($task->status === 'failed') {
                $failed_tasks++;
            }
        }

        // Sprawdź czy misja jest ukończona (wszystkie wymagane zadania)
        $required_completed = 0;
        foreach ($tasks as $task) {
            if ($task->task_optional == 0 && $task->status === 'completed') {
                $required_completed++;
            }
        }

        if ($required_completed === $required_tasks) {
            $this->updateMissionStatus($user_mission_id, 'completed');
            return 'completed';
        }

        // Sprawdź czy misja jest nieudana (jakieś wymagane zadanie failed)
        foreach ($tasks as $task) {
            if ($task->task_optional == 0 && $task->status === 'failed') {
                $this->updateMissionStatus($user_mission_id, 'failed');
                return 'failed';
            }
        }

        // W przeciwnym razie misja jest w trakcie
        $this->updateMissionStatus($user_mission_id, 'in_progress');
        return 'in_progress';
    }

    /**
     * Zwraca statystyki misji
     */
    public function getMissionStats($user_id = null)
    {
        $missions_table = $this->wpdb->prefix . 'game_user_missions';

        if ($user_id) {
            $where = "WHERE user_id = $user_id";
        } else {
            $where = "";
        }

        $stats = $this->wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'not_started' THEN 1 ELSE 0 END) as not_started
            FROM $missions_table $where
        ");

        return [
            'total' => intval($stats->total ?? 0),
            'completed' => intval($stats->completed ?? 0),
            'in_progress' => intval($stats->in_progress ?? 0),
            'failed' => intval($stats->failed ?? 0),
            'not_started' => intval($stats->not_started ?? 0)
        ];
    }
}
