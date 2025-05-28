<?php

/**
 * Menedżer misji - zarządza stanami misji graczy na podstawie danych z ACF
 */
class GameMissionManager
{

    private $wpdb;
    private $dbManager;
    private $deltaManager;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->dbManager = GameDatabaseManager::getInstance();
        $this->deltaManager = new GameDeltaManager();
    }

    /**
     * Buduje misje w bazie danych na podstawie danych z ACF
     */
    public function buildMissionsFromACF()
    {
        // Pobieramy wszystkie posty typu 'misje' (zakładam że tak nazywasz CPT)
        $missions = get_posts([
            'post_type' => 'misje', // dostosuj do nazwy swojego CPT
            'post_status' => 'publish',
            'numberposts' => -1
        ]);

        $builtMissions = [];

        foreach ($missions as $mission) {
            $missionData = $this->extractMissionDataFromACF($mission->ID);
            if ($missionData) {
                $builtMissions[] = [
                    'id' => $mission->ID,
                    'title' => $mission->post_title,
                    'data' => $missionData
                ];
            }
        }

        return $builtMissions;
    }

    /**
     * Wyciąga dane misji z ACF 
     */
    private function extractMissionDataFromACF($missionId)
    {
        $data = [];

        // Pobieramy pola ACF
        $data['description'] = get_field('mission_description', $missionId);
        $data['time_limit'] = get_field('mission_time_limit', $missionId);
        $data['type'] = get_field('mission_type', $missionId);
        $data['tasks'] = get_field('mission_tasks', $missionId);

        if (empty($data['tasks'])) {
            return null; // Misja bez zadań nie ma sensu
        }

        return $data;
    }

    /**
     * Rozpoczyna misję dla gracza
     */
    public function startMission($userId, $missionId)
    {
        $missionsTable = $this->dbManager->getTableName('game_user_missions');

        // Sprawdzamy czy gracz już ma tę misję
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$missionsTable` WHERE user_id = %d AND mission_id = %d",
            $userId,
            $missionId
        ));

        if ($existing) {
            return ['success' => false, 'error' => 'Gracz już ma tę misję'];
        }

        // Pobieramy dane misji z ACF
        $missionData = $this->extractMissionDataFromACF($missionId);
        if (!$missionData) {
            return ['success' => false, 'error' => 'Nie znaleziono danych misji'];
        }

        $this->wpdb->query('START TRANSACTION');

        try {
            // Tworzymy wpis misji
            $expiresAt = null;
            if (!empty($missionData['time_limit'])) {
                $expiresAt = date('Y-m-d H:i:s', time() + ($missionData['time_limit'] * 3600));
            }

            $result = $this->wpdb->insert(
                $missionsTable,
                [
                    'user_id' => $userId,
                    'mission_id' => $missionId,
                    'status' => 'active',
                    'started_at' => current_time('mysql'),
                    'expires_at' => $expiresAt
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );

            if ($result === false) {
                throw new Exception('Błąd tworzenia misji');
            }

            $userMissionId = $this->wpdb->insert_id;

            // Tworzymy zadania misji
            $tasksTable = $this->dbManager->getTableName('game_user_mission_tasks');

            foreach ($missionData['tasks'] as $task) {
                $taskResult = $this->wpdb->insert(
                    $tasksTable,
                    [
                        'user_mission_id' => $userMissionId,
                        'task_id' => $task['task_id'],
                        'status' => 'pending'
                    ],
                    ['%d', '%s', '%s']
                );

                if ($taskResult === false) {
                    throw new Exception('Błąd tworzenia zadania: ' . $task['task_id']);
                }
            }

            $this->wpdb->query('COMMIT');
            return ['success' => true, 'user_mission_id' => $userMissionId];
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sprawdza postęp misji gracza
     */
    public function checkMissionProgress($userId, $missionId)
    {
        $missionsTable = $this->dbManager->getTableName('game_user_missions');
        $tasksTable = $this->dbManager->getTableName('game_user_mission_tasks');

        // Pobieramy misję gracza
        $userMission = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$missionsTable` WHERE user_id = %d AND mission_id = %d",
            $userId,
            $missionId
        ), ARRAY_A);

        if (!$userMission) {
            return ['success' => false, 'error' => 'Gracz nie ma tej misji'];
        }

        // Pobieramy zadania
        $tasks = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM `$tasksTable` WHERE user_mission_id = %d",
            $userMission['id']
        ), ARRAY_A);

        // Pobieramy dane misji z ACF
        $missionData = $this->extractMissionDataFromACF($missionId);

        $progress = [
            'mission' => $userMission,
            'tasks' => $tasks,
            'mission_data' => $missionData,
            'completed_tasks' => 0,
            'total_tasks' => count($tasks),
            'is_completed' => false,
            'can_complete' => true
        ];

        // Sprawdzamy postęp zadań
        foreach ($tasks as $task) {
            if ($task['status'] === 'completed') {
                $progress['completed_tasks']++;
            }
        }

        // Sprawdzamy czy misja jest ukończona
        if ($progress['completed_tasks'] === $progress['total_tasks']) {
            $progress['is_completed'] = true;
        }

        return ['success' => true, 'progress' => $progress];
    }

    /**
     * Oznacza zadanie jako ukończone
     */
    public function completeTask($userId, $missionId, $taskId)
    {
        $missionsTable = $this->dbManager->getTableName('game_user_missions');
        $tasksTable = $this->dbManager->getTableName('game_user_mission_tasks');

        // Znajdujemy misję gracza
        $userMission = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$missionsTable` WHERE user_id = %d AND mission_id = %d AND status = 'active'",
            $userId,
            $missionId
        ), ARRAY_A);

        if (!$userMission) {
            return ['success' => false, 'error' => 'Nie znaleziono aktywnej misji'];
        }

        // Aktualizujemy zadanie
        $result = $this->wpdb->update(
            $tasksTable,
            [
                'status' => 'completed',
                'completed_at' => current_time('mysql')
            ],
            [
                'user_mission_id' => $userMission['id'],
                'task_id' => $taskId
            ],
            ['%s', '%s'],
            ['%d', '%s']
        );

        if ($result === false) {
            return ['success' => false, 'error' => 'Błąd aktualizacji zadania'];
        }

        // Sprawdzamy czy wszystkie zadania są ukończone
        $completedTasks = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM `$tasksTable` WHERE user_mission_id = %d AND status = 'completed'",
            $userMission['id']
        ));

        $totalTasks = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM `$tasksTable` WHERE user_mission_id = %d",
            $userMission['id']
        ));

        // Jeśli wszystkie zadania ukończone, kończymy misję
        if ($completedTasks == $totalTasks) {
            $this->completeMissionInternal($userMission['id']);
        }

        return ['success' => true, 'completed_tasks' => $completedTasks, 'total_tasks' => $totalTasks];
    }

    /**
     * Prywatna metoda do kończenia misji przez ID user_mission
     */
    private function completeMissionInternal($userMissionId)
    {
        $missionsTable = $this->dbManager->getTableName('game_user_missions');

        $this->wpdb->update(
            $missionsTable,
            [
                'status' => 'completed',
                'completed_at' => current_time('mysql')
            ],
            ['id' => $userMissionId],
            ['%s', '%s'],
            ['%d']
        );
    }

    /**
     * Sprawdza warunki zadania na podstawie stanu gracza
     */
    public function checkTaskConditions($userId, $missionId, $taskId, $gameState)
    {
        // Pobieramy dane misji z ACF
        $missionData = $this->extractMissionDataFromACF($missionId);
        if (!$missionData) {
            return false;
        }

        // Znajdujemy zadanie
        $task = null;
        foreach ($missionData['tasks'] as $t) {
            if ($t['task_id'] === $taskId) {
                $task = $t;
                break;
            }
        }

        if (!$task) {
            return false;
        }

        // Sprawdzamy warunki w zależności od typu zadania
        switch ($task['task_type']) {
            case 'checkpoint':
                return $this->checkCheckpointCondition($task, $gameState);

            case 'checkpoint_npc':
                return $this->checkNpcCondition($task, $gameState);

            case 'defeat_enemies':
                return $this->checkDefeatCondition($task, $gameState);

            default:
                return false;
        }
    }

    private function checkCheckpointCondition($task, $gameState)
    {
        // Sprawdzamy czy gracz jest w odpowiednim miejscu i scenie
        return isset($gameState['current_area_id']) &&
            isset($gameState['current_scene']) &&
            $gameState['current_area_id'] == $task['task_location'] &&
            $gameState['current_scene'] == $task['task_location_scene'];
    }

    private function checkNpcCondition($task, $gameState)
    {
        if (empty($task['task_checkpoint_npc'])) {
            return false;
        }

        // Sprawdzamy status interakcji z NPC
        foreach ($task['task_checkpoint_npc'] as $npc) {
            $expectedStatus = $npc['status'] ?? 'completed';
            $npcId = $npc['npc'] ?? 0;

            if (isset($gameState['npc_interactions'][$npcId])) {
                if ($gameState['npc_interactions'][$npcId] === $expectedStatus) {
                    return true;
                }
            }
        }

        return false;
    }

    private function checkDefeatCondition($task, $gameState)
    {
        if (empty($task['task_defeat_enemies'])) {
            return false;
        }

        // Sprawdzamy czy gracz pokonał wymaganych przeciwników
        foreach ($task['task_defeat_enemies'] as $enemyId) {
            if (!isset($gameState['defeated_enemies'][$enemyId])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Pobiera wszystkie aktywne misje gracza
     */
    public function getActiveMissions($userId)
    {
        $missionsTable = $this->dbManager->getTableName('game_user_missions');

        $missions = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM `$missionsTable` WHERE user_id = %d AND status = 'active' ORDER BY started_at",
            $userId
        ), ARRAY_A);

        return $missions ?: [];
    }

    /**
     * Anuluje misję (przekroczony czas)
     */
    public function failMission($userId, $missionId, $reason = 'timeout')
    {
        $missionsTable = $this->dbManager->getTableName('game_user_missions');

        $result = $this->wpdb->update(
            $missionsTable,
            ['status' => 'failed'],
            [
                'user_id' => $userId,
                'mission_id' => $missionId
            ],
            ['%s'],
            ['%d', '%d']
        );

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Sprawdza wygasłe misje i oznacza jako nieudane
     */
    public function checkExpiredMissions()
    {
        $missionsTable = $this->dbManager->getTableName('game_user_missions');

        $expiredCount = $this->wpdb->query($this->wpdb->prepare(
            "UPDATE `$missionsTable` 
             SET status = 'failed' 
             WHERE status = 'active' 
             AND expires_at IS NOT NULL 
             AND expires_at < %s",
            current_time('mysql')
        ));

        return $expiredCount;
    }

    /**
     * Pobiera misje gracza z danymi o postępie
     */
    public function getPlayerMissions($userId)
    {
        $missionsTable = $this->dbManager->getTableName('game_user_missions');
        $tasksTable = $this->dbManager->getTableName('game_user_mission_tasks');

        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT m.*, 
                    COUNT(t.task_id) as total_tasks,
                    COUNT(CASE WHEN t.is_completed = 1 THEN 1 END) as completed_tasks
             FROM `$missionsTable` m
             LEFT JOIN `$tasksTable` t ON m.user_id = t.user_id AND m.mission_id = t.mission_id
             WHERE m.user_id = %d
             GROUP BY m.user_id, m.mission_id
             ORDER BY m.started_at DESC",
            $userId
        ));

        return $results ?: [];
    }

    /**
     * Tłumaczy status misji na polskie nazwy
     */
    public function translateStatus($status)
    {
        $translations = [
            'available' => 'Dostępna',
            'active' => 'Aktywna',
            'completed' => 'Zakończona',
            'failed' => 'Nieudana',
            'expired' => 'Wygasła'
        ];

        return $translations[$status] ?? $status;
    }

    /**
     * Dodaje misję dla gracza
     */
    public function addMissionForPlayer($userId, $missionId, $status = 'available')
    {
        $missionsTable = $this->dbManager->getTableName('game_user_missions');

        // Sprawdź czy misja już istnieje
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$missionsTable` WHERE user_id = %d AND mission_id = %s",
            $userId,
            $missionId
        ));

        if ($existing) {
            return ['success' => false, 'error' => 'Misja już istnieje dla tego gracza'];
        }

        $data = [
            'user_id' => $userId,
            'mission_id' => $missionId,
            'status' => $status
        ];

        if ($status === 'active') {
            $data['started_at'] = gmdate('Y-m-d H:i:s');
        } elseif ($status === 'completed') {
            $data['started_at'] = gmdate('Y-m-d H:i:s');
            $data['completed_at'] = gmdate('Y-m-d H:i:s');
        }

        $result = $this->wpdb->insert(
            $missionsTable,
            $data,
            ['%d', '%s', '%s', '%s', '%s']
        );

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Aktywuje misję (zmienia status z "available" na "active")
     */
    public function activateMission($userId, $missionId)
    {
        $missionsTable = $this->dbManager->getTableName('game_user_missions');

        $result = $this->wpdb->update(
            $missionsTable,
            [
                'status' => 'active',
                'started_at' => gmdate('Y-m-d H:i:s')
            ],
            [
                'user_id' => $userId,
                'mission_id' => $missionId,
                'status' => 'available'
            ],
            ['%s', '%s'],
            ['%d', '%s', '%s']
        );

        return [
            'success' => $result !== false && $result > 0,
            'error' => $result === false ? $this->wpdb->last_error : ($result === 0 ? 'Misja nie jest dostępna lub już rozpoczęta' : null)
        ];
    }

    /**
     * Kończy misję
     */
    public function completeMission($userId, $missionId)
    {
        $missionsTable = $this->dbManager->getTableName('game_user_missions');

        $result = $this->wpdb->update(
            $missionsTable,
            [
                'status' => 'completed',
                'completed_at' => gmdate('Y-m-d H:i:s')
            ],
            [
                'user_id' => $userId,
                'mission_id' => $missionId,
                'status' => 'active'
            ],
            ['%s', '%s'],
            ['%d', '%s', '%s']
        );

        return [
            'success' => $result !== false && $result > 0,
            'error' => $result === false ? $this->wpdb->last_error : ($result === 0 ? 'Misja nie jest aktywna' : null)
        ];
    }
}
