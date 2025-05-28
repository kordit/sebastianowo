<?php

/**
 * Menedżer misji - zarządza stanami misji graczy na podstawie danych z ACF
 */
class GameMissionManager
{
    private $wpdb;
    private $dbManager;
    private $userRepo;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->dbManager = GameDatabaseManager::getInstance();
        $this->userRepo = new GameUserRepository();
    }

    /**
     * Pobiera misje gracza
     */
    public function getPlayerMissions($userId)
    {
        return $this->userRepo->getPlayerMissions($userId);
    }

    /**
     * Dodaje misję dla gracza
     */
    public function addMissionForPlayer($userId, $missionId, $status = 'not_started')
    {
        $result = $this->userRepo->addMission($userId, $missionId, 'one-time');

        if ($result['success'] && $status !== 'not_started') {
            $this->userRepo->updateMissionStatus($result['mission_db_id'], $status);
        }

        return $result;
    }

    /**
     * Aktywuje misję
     */
    public function activateMission($userId, $missionId)
    {
        $missions = $this->userRepo->getPlayerMissions($userId);
        $userMission = null;

        foreach ($missions as $mission) {
            if ($mission->mission_id == $missionId) {
                $userMission = $mission;
                break;
            }
        }

        if (!$userMission) {
            return ['success' => false, 'error' => 'Misja nie została znaleziona'];
        }

        return $this->userRepo->updateMissionStatus($userMission->id, 'active');
    }

    /**
     * Kończy misję
     */
    public function completeMission($userId, $missionId)
    {
        $missions = $this->userRepo->getPlayerMissions($userId);
        $userMission = null;

        foreach ($missions as $mission) {
            if ($mission->mission_id == $missionId) {
                $userMission = $mission;
                break;
            }
        }

        if (!$userMission) {
            return ['success' => false, 'error' => 'Misja nie została znaleziona'];
        }

        return $this->userRepo->updateMissionStatus($userMission->id, 'completed');
    }

    /**
     * Buduje misje z ACF (placeholder)
     */
    public function buildMissionsFromACF()
    {
        // Placeholder - tutaj można dodać logikę pobierania misji z ACF
        return [];
    }

    /**
     * Dodaje zadanie do misji
     */
    public function addTaskToMission($userMissionId, $taskId, $taskType, $options = [])
    {
        return $this->userRepo->addMissionTask($userMissionId, $taskId, $taskType, $options);
    }

    /**
     * Pobiera zadania misji
     */
    public function getMissionTasks($userMissionId)
    {
        return $this->userRepo->getMissionTasks($userMissionId);
    }

    /**
     * Aktualizuje status zadania
     */
    public function updateTaskStatus($taskDbId, $status, $incrementAttempts = false)
    {
        return $this->userRepo->updateTaskStatus($taskDbId, $status, $incrementAttempts);
    }
}
