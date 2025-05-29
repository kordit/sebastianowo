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
     * Przypisuje misję użytkownikowi (używa MissionBuilder z nową strukturą)
     */
    public function assignMissionToUser($user_id, $mission_id)
    {
        // Deleguj do MissionBuilder, który już używa nowej struktury
        return $this->mission_builder->assignMissionToUser($user_id, $mission_id);
    }

    /**
     * Pobiera misję użytkownika (z nowej struktury tabeli)
     */
    public function getUserMission($user_id, $mission_id)
    {
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT mission_id, mission_title, mission_description, mission_time_limit, 
                        mission_type, mission_status, mission_started_at, mission_expires_at, mission_completed_at
                 FROM $mission_tasks_table 
                 WHERE user_id = %d AND mission_id = %d 
                 LIMIT 1",
                $user_id,
                $mission_id
            )
        );
    }

    /**
     * Pobiera wszystkie misje użytkownika (deleguje do MissionBuilder)
     */
    public function getUserMissions($user_id, $status = null)
    {
        return $this->mission_builder->getUserMissions($user_id, $status);
    }

    /**
     * Pobiera zadania misji użytkownika (deleguje do MissionBuilder)
     */
    public function getUserMissionTasks($user_id, $mission_id)
    {
        return $this->mission_builder->getUserMissionTasks($user_id, $mission_id);
    }

    /**
     * Aktualizuje status misji (w nowej strukturze tabeli)
     */
    public function updateMissionStatus($user_id, $mission_id, $status)
    {
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        $update_data = ['mission_status' => $status];

        if ($status === 'completed') {
            $update_data['mission_completed_at'] = current_time('mysql');
        }

        return $this->wpdb->update(
            $mission_tasks_table,
            $update_data,
            [
                'user_id' => $user_id,
                'mission_id' => $mission_id
            ],
            ['%s', '%s'],
            ['%d', '%d']
        );
    }

    /**
     * Aktualizuje status zadania (deleguje do MissionBuilder)
     */
    public function updateTaskStatus($user_id, $mission_id, $task_id, $status, $wins = null, $losses = null, $draws = null)
    {
        return $this->mission_builder->updateTaskStatus($user_id, $mission_id, $task_id, $status, $wins, $losses, $draws);
    }

    /**
     * Sprawdza postęp misji i automatycznie aktualizuje status (z nowej struktury)
     */
    public function checkMissionProgress($user_id, $mission_id)
    {
        $tasks = $this->getUserMissionTasks($user_id, $mission_id);

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

            if ($task->task_status === 'completed') {
                $completed_tasks++;
            } elseif ($task->task_status === 'failed') {
                $failed_tasks++;
            }
        }

        // Sprawdź czy misja jest ukończona (wszystkie wymagane zadania)
        $required_completed = 0;
        foreach ($tasks as $task) {
            if ($task->task_optional == 0 && $task->task_status === 'completed') {
                $required_completed++;
            }
        }

        if ($required_completed === $required_tasks) {
            $this->updateMissionStatus($user_id, $mission_id, 'completed');
            return 'completed';
        }

        // Sprawdź czy misja jest nieudana (jakieś wymagane zadanie failed)
        foreach ($tasks as $task) {
            if ($task->task_optional == 0 && $task->task_status === 'failed') {
                $this->updateMissionStatus($user_id, $mission_id, 'failed');
                return 'failed';
            }
        }

        // W przeciwnym razie misja jest w trakcie
        $this->updateMissionStatus($user_id, $mission_id, 'in_progress');
        return 'in_progress';
    }

    /**
     * Zwraca statystyki misji (z nowej struktury tabeli)
     */
    public function getMissionStats($user_id = null)
    {
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        if ($user_id) {
            $where = "WHERE user_id = $user_id";
        } else {
            $where = "";
        }

        $stats = $this->wpdb->get_row("
            SELECT 
                COUNT(DISTINCT CONCAT(user_id, '-', mission_id)) as total,
                SUM(CASE WHEN mission_status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN mission_status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN mission_status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN mission_status = 'not_started' THEN 1 ELSE 0 END) as not_started
            FROM (
                SELECT DISTINCT user_id, mission_id, mission_status 
                FROM $mission_tasks_table $where
            ) as unique_missions
        ");

        return [
            'total' => intval($stats->total ?? 0),
            'completed' => intval($stats->completed ?? 0),
            'in_progress' => intval($stats->in_progress ?? 0),
            'failed' => intval($stats->failed ?? 0),
            'not_started' => intval($stats->not_started ?? 0)
        ];
    }

    /**
     * Pobiera szczegóły misji użytkownika (deleguje do MissionBuilder)
     */
    public function getUserMissionDetails($user_id, $mission_id)
    {
        return $this->mission_builder->getUserMissionDetails($user_id, $mission_id);
    }

    /**
     * Usuwa misję użytkownika (deleguje do MissionBuilder)
     */
    public function removeUserMission($user_id, $mission_id)
    {
        return $this->mission_builder->removeUserMission($user_id, $mission_id);
    }

    /**
     * Buduje wszystkie misje dla wszystkich użytkowników (deleguje do MissionBuilder)
     */
    public function buildAllMissions()
    {
        return $this->mission_builder->buildAllMissions();
    }

    /**
     * Czyści wszystkie misje (deleguje do MissionBuilder)
     */
    public function clearAllMissions()
    {
        return $this->mission_builder->clearAllMissions();
    }
}
