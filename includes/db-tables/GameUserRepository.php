<?php

/**
 * Repository do zarządzania danymi użytkowników gry
 * Zaktualizowane do używania nowej tabeli game_user_data
 */
class GameUserRepository
{
    private $wpdb;
    private $dbManager;
    private $deltaManager;

    // Mapowanie kategorii danych
    const DATA_TYPES = [
        'stat' => ['strength', 'defense', 'agility', 'intelligence', 'charisma'],
        'skill' => ['combat', 'steal', 'diplomacy', 'investigation', 'survival'],
        'progress' => ['experience', 'learning_points', 'reputation', 'level'],
        'vitality' => ['max_life', 'current_life', 'max_energy', 'current_energy'],
        'story' => ['story_text']
    ];

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->dbManager = GameDatabaseManager::getInstance();
        $this->deltaManager = new GameDeltaManager();
    }

    /**
     * Tworzy nowego gracza z domyślnymi wartościami
     */
    public function createPlayer($userId, $data = [])
    {
        $this->wpdb->query('START TRANSACTION');

        try {
            // 1. Główne dane gracza
            $mainData = array_merge([
                'user_id' => $userId,
                'nickname' => get_userdata($userId)->display_name,
                'character_class' => 'beginner',
                'avatar' => '',
                'current_area_id' => null,
                'current_scene' => null
            ], $data);

            $result = $this->wpdb->insert(
                $this->dbManager->getTableName('game_users'),
                $mainData,
                ['%d', '%s', '%s', '%s', '%d', '%s']
            );

            if ($result === false) {
                throw new Exception('Błąd tworzenia głównych danych gracza');
            }

            // 2. Domyślne dane w nowej tabeli game_user_data
            $defaultData = [
                // Statystyki
                ['stat', 'strength', 10],
                ['stat', 'defense', 10],
                ['stat', 'agility', 10],
                ['stat', 'intelligence', 10],
                ['stat', 'charisma', 10],

                // Umiejętności
                ['skill', 'combat', 1],
                ['skill', 'steal', 1],
                ['skill', 'diplomacy', 1],
                ['skill', 'investigation', 1],
                ['skill', 'survival', 1],

                // Postęp
                ['progress', 'experience', 0],
                ['progress', 'learning_points', 5],
                ['progress', 'reputation', 0],
                ['progress', 'level', 1],

                // Witalność
                ['vitality', 'max_life', 100],
                ['vitality', 'current_life', 100],
                ['vitality', 'max_energy', 100],
                ['vitality', 'current_energy', 100],

                // Historia
                ['story', 'story_text', '']
            ];

            foreach ($defaultData as $item) {
                $this->setUserData($userId, $item[0], $item[1], $item[2]);
            }

            $this->wpdb->query('COMMIT');
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sprawdza czy gracz istnieje w systemie
     */
    public function playerExists($userId)
    {
        $tableName = $this->dbManager->getTableName('game_users');
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM `$tableName` WHERE user_id = %d",
            $userId
        ));
        return intval($result) > 0;
    }

    /**
     * Ustawia dane użytkownika w tabeli game_user_data
     */
    private function setUserData($userId, $dataType, $dataKey, $value)
    {
        $tableName = $this->dbManager->getTableName('game_user_data');

        $data = [
            'user_id' => $userId,
            'data_type' => $dataType,
            'data_key' => $dataKey,
            'data_value' => is_numeric($value) ? null : $value,
            'numeric_value' => is_numeric($value) ? intval($value) : null
        ];

        // Sprawdź czy już istnieje
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id FROM `$tableName` WHERE user_id = %d AND data_type = %s AND data_key = %s",
            $userId,
            $dataType,
            $dataKey
        ));

        if ($existing) {
            // Aktualizuj
            return $this->wpdb->update(
                $tableName,
                $data,
                ['id' => $existing->id],
                ['%d', '%s', '%s', '%s', '%d'],
                ['%d']
            );
        } else {
            // Wstaw nowy
            return $this->wpdb->insert(
                $tableName,
                $data,
                ['%d', '%s', '%s', '%s', '%d']
            );
        }
    }

    /**
     * Pobiera dane użytkownika z tabeli game_user_data
     */
    private function getUserData($userId, $dataType = null, $dataKey = null)
    {
        $tableName = $this->dbManager->getTableName('game_user_data');

        $where = "user_id = %d";
        $params = [$userId];

        if ($dataType) {
            $where .= " AND data_type = %s";
            $params[] = $dataType;
        }

        if ($dataKey) {
            $where .= " AND data_key = %s";
            $params[] = $dataKey;
        }

        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM `$tableName` WHERE $where",
            ...$params
        ), ARRAY_A);
    }

    /**
     * Pobiera pełne dane gracza
     */
    public function getPlayerData($userId)
    {
        // Główne dane
        $mainTable = $this->dbManager->getTableName('game_users');
        $mainData = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$mainTable` WHERE user_id = %d",
            $userId
        ), ARRAY_A);

        // Dane z game_user_data
        $userData = $this->getUserData($userId);

        // Organizuj dane według typu
        $organized = [
            'main' => $mainData ?: [],
            'stats' => [],
            'skills' => [],
            'progress' => [],
            'vitality' => [],
            'story' => []
        ];

        foreach ($userData as $row) {
            $type = $row['data_type'];
            $key = $row['data_key'];
            $value = $row['numeric_value'] !== null ? $row['numeric_value'] : $row['data_value'];

            switch ($type) {
                case 'stat':
                    $organized['stats'][$key] = $value;
                    break;
                case 'skill':
                    $organized['skills'][$key] = $value;
                    break;
                case 'progress':
                    $organized['progress'][$key] = $value;
                    break;
                case 'vitality':
                    $organized['vitality'][$key] = $value;
                    break;
                case 'story':
                    $organized['story'][$key] = $value;
                    break;
            }
        }

        return $organized;
    }

    /**
     * Aktualizuje statystyki gracza
     */
    public function updateStats($userId, $stats)
    {
        $allowedStats = self::DATA_TYPES['stat'];
        $updateCount = 0;

        foreach ($stats as $stat => $value) {
            if (in_array($stat, $allowedStats) && is_numeric($value)) {
                $result = $this->setUserData($userId, 'stat', $stat, intval($value));
                if ($result !== false) {
                    $updateCount++;
                }
            }
        }

        return [
            'success' => $updateCount > 0,
            'affected_rows' => $updateCount
        ];
    }

    /**
     * Aktualizuje umiejętności gracza
     */
    public function updateSkills($userId, $skills)
    {
        $allowedSkills = self::DATA_TYPES['skill'];
        $updateCount = 0;

        foreach ($skills as $skill => $value) {
            if (in_array($skill, $allowedSkills) && is_numeric($value)) {
                $result = $this->setUserData($userId, 'skill', $skill, intval($value));
                if ($result !== false) {
                    $updateCount++;
                }
            }
        }

        return [
            'success' => $updateCount > 0,
            'affected_rows' => $updateCount
        ];
    }

    /**
     * Pobiera przedmioty gracza
     */
    public function getPlayerItems($userId)
    {
        $tableName = $this->dbManager->getTableName('game_user_items');

        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM `$tableName` WHERE user_id = %d ORDER BY item_id",
            $userId
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Dodaje przedmiot do ekwipunku gracza
     */
    public function addItem($userId, $itemId, $quantity = 1)
    {
        $tableName = $this->dbManager->getTableName('game_user_items');

        // Sprawdź czy przedmiot już istnieje
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$tableName` WHERE user_id = %d AND item_id = %d",
            $userId,
            $itemId
        ));

        if ($existing) {
            // Zwiększ ilość
            $result = $this->wpdb->update(
                $tableName,
                ['quantity' => $existing->quantity + $quantity],
                ['user_id' => $userId, 'item_id' => $itemId],
                ['%d'],
                ['%d', '%d']
            );
        } else {
            // Dodaj nowy przedmiot
            $result = $this->wpdb->insert(
                $tableName,
                [
                    'user_id' => $userId,
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'is_equipped' => 0
                ],
                ['%d', '%d', '%d', '%d']
            );
        }

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Usuwa przedmiot z ekwipunku gracza
     */
    public function removeItem($userId, $itemId, $quantity = null)
    {
        $tableName = $this->dbManager->getTableName('game_user_items');

        // Pobierz obecną ilość
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$tableName` WHERE user_id = %d AND item_id = %d",
            $userId,
            $itemId
        ));

        if (!$existing) {
            return ['success' => false, 'error' => 'Przedmiot nie istnieje'];
        }

        if ($quantity === null || $quantity >= $existing->quantity) {
            // Usuń całkowicie
            $result = $this->wpdb->delete(
                $tableName,
                ['user_id' => $userId, 'item_id' => $itemId],
                ['%d', '%d']
            );
        } else {
            // Zmniejsz ilość
            $result = $this->wpdb->update(
                $tableName,
                ['quantity' => $existing->quantity - $quantity],
                ['user_id' => $userId, 'item_id' => $itemId],
                ['%d'],
                ['%d', '%d']
            );
        }

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Zakłada przedmiot w określonym slocie
     */
    public function equipItem($userId, $itemId, $slot)
    {
        $tableName = $this->dbManager->getTableName('game_user_items');

        // Najpierw zdejmij wszystkie przedmioty z tego slotu
        $this->wpdb->update(
            $tableName,
            ['is_equipped' => 0, 'equipment_slot' => null],
            ['user_id' => $userId, 'equipment_slot' => $slot],
            ['%d', '%s'],
            ['%d', '%s']
        );

        // Teraz załóż nowy przedmiot
        $result = $this->wpdb->update(
            $tableName,
            ['is_equipped' => 1, 'equipment_slot' => $slot],
            ['user_id' => $userId, 'item_id' => $itemId],
            ['%d', '%s'],
            ['%d', '%d']
        );

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Zdejmuje przedmiot
     */
    public function unequipItem($userId, $itemId)
    {
        $tableName = $this->dbManager->getTableName('game_user_items');

        $result = $this->wpdb->update(
            $tableName,
            ['is_equipped' => 0, 'equipment_slot' => null],
            ['user_id' => $userId, 'item_id' => $itemId],
            ['%d', '%s'],
            ['%d', '%d']
        );

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Pobiera dostępne rejony gracza
     */
    public function getPlayerAreas($userId)
    {
        $tableName = $this->dbManager->getTableName('game_user_areas');

        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM `$tableName` WHERE user_id = %d ORDER BY area_id, scene_id",
            $userId
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Odblokuje rejon
     */
    public function unlockArea($userId, $areaId, $sceneId = '')
    {
        $tableName = $this->dbManager->getTableName('game_user_areas');

        // Sprawdź czy już istnieje
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$tableName` WHERE user_id = %d AND area_id = %d",
            $userId,
            $areaId
        ));

        if ($existing) {
            // Aktualizuj istniejący
            $result = $this->wpdb->update(
                $tableName,
                ['is_unlocked' => 1, 'scene_id' => $sceneId, 'unlocked_at' => gmdate('Y-m-d H:i:s')],
                ['user_id' => $userId, 'area_id' => $areaId],
                ['%d', '%s', '%s'],
                ['%d', '%d']
            );
        } else {
            // Dodaj nowy
            $result = $this->wpdb->insert(
                $tableName,
                [
                    'user_id' => $userId,
                    'area_id' => $areaId,
                    'scene_id' => $sceneId,
                    'is_unlocked' => 1,
                    'unlocked_at' => gmdate('Y-m-d H:i:s')
                ],
                ['%d', '%d', '%s', '%d', '%s']
            );
        }

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Blokuje rejon
     */
    public function lockArea($userId, $areaId)
    {
        $tableName = $this->dbManager->getTableName('game_user_areas');

        $result = $this->wpdb->update(
            $tableName,
            ['is_unlocked' => 0],
            ['user_id' => $userId, 'area_id' => $areaId],
            ['%d'],
            ['%d', '%d']
        );

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Pobiera relacje gracza z NPC
     */
    public function getPlayerRelations($userId)
    {
        $tableName = $this->dbManager->getTableName('game_user_relations');

        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM `$tableName` WHERE user_id = %d ORDER BY npc_id",
            $userId
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Aktualizuje relację z NPC
     */
    public function updateNpcRelation($userId, $npcId, $relationValue, $isKnown = true)
    {
        $tableName = $this->dbManager->getTableName('game_user_relations');

        // Sprawdź czy relacja już istnieje
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$tableName` WHERE user_id = %d AND npc_id = %d",
            $userId,
            $npcId
        ));

        if ($existing) {
            // Aktualizuj istniejącą
            $result = $this->wpdb->update(
                $tableName,
                ['relation_value' => $relationValue, 'is_known' => $isKnown ? 1 : 0],
                ['user_id' => $userId, 'npc_id' => $npcId],
                ['%d', '%d'],
                ['%d', '%d']
            );
        } else {
            // Dodaj nową
            $result = $this->wpdb->insert(
                $tableName,
                [
                    'user_id' => $userId,
                    'npc_id' => $npcId,
                    'relation_value' => $relationValue,
                    'is_known' => $isKnown ? 1 : 0
                ],
                ['%d', '%d', '%d', '%d']
            );
        }

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Dodaje wynik walki
     */
    public function addFightResult($userId, $npcId, $result)
    {
        $tableName = $this->dbManager->getTableName('game_user_relations');

        $validResults = ['won', 'lost', 'draw'];
        if (!in_array($result, $validResults)) {
            return ['success' => false, 'error' => 'Nieprawidłowy wynik walki'];
        }

        // Pobierz obecne dane
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$tableName` WHERE user_id = %d AND npc_id = %d",
            $userId,
            $npcId
        ));

        $updateData = [];
        if ($existing) {
            $updateData = [
                'fights_won' => $existing->fights_won,
                'fights_lost' => $existing->fights_lost,
                'fights_draw' => $existing->fights_draw
            ];
        } else {
            $updateData = [
                'user_id' => $userId,
                'npc_id' => $npcId,
                'relation_value' => 0,
                'is_known' => 1,
                'fights_won' => 0,
                'fights_lost' => 0,
                'fights_draw' => 0
            ];
        }

        // Zwiększ odpowiedni licznik
        $updateData['fights_' . $result]++;

        if ($existing) {
            $dbResult = $this->wpdb->update(
                $tableName,
                $updateData,
                ['user_id' => $userId, 'npc_id' => $npcId],
                ['%d', '%d', '%d'],
                ['%d', '%d']
            );
        } else {
            $dbResult = $this->wpdb->insert(
                $tableName,
                $updateData,
                ['%d', '%d', '%d', '%d', '%d', '%d', '%d']
            );
        }

        return [
            'success' => $dbResult !== false,
            'error' => $dbResult === false ? $this->wpdb->last_error : null
        ];
    }

    // ===== METODY DO OBSŁUGI MISJI =====

    /**
     * Dodaje misję dla gracza
     */
    public function addMission($userId, $missionId, $type = 'one-time', $expiresAt = null)
    {
        $tableName = $this->dbManager->getTableName('game_user_missions');

        $data = [
            'user_id' => $userId,
            'mission_id' => $missionId,
            'status' => 'not_started',
            'type' => $type,
            'expires_at' => $expiresAt
        ];

        $result = $this->wpdb->insert(
            $tableName,
            $data,
            ['%d', '%d', '%s', '%s', '%s']
        );

        return [
            'success' => $result !== false,
            'mission_db_id' => $this->wpdb->insert_id,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Pobiera misje gracza
     */
    public function getPlayerMissions($userId, $status = null)
    {
        $tableName = $this->dbManager->getTableName('game_user_missions');

        $where = $this->wpdb->prepare("user_id = %d", $userId);
        if ($status) {
            $where .= $this->wpdb->prepare(" AND status = %s", $status);
        }

        return $this->wpdb->get_results(
            "SELECT * FROM {$tableName} WHERE {$where} ORDER BY created_at DESC"
        );
    }

    /**
     * Aktualizuje status misji
     */
    public function updateMissionStatus($userMissionId, $status, $wins = null, $losses = null, $draws = null)
    {
        $tableName = $this->dbManager->getTableName('game_user_missions');

        $updateData = ['status' => $status];
        $formats = ['%s'];

        if ($status === 'active' && !$this->getMissionStartTime($userMissionId)) {
            $updateData['started_at'] = current_time('mysql');
            $formats[] = '%s';
        }

        if ($status === 'completed') {
            $updateData['completed_at'] = current_time('mysql');
            $formats[] = '%s';
        }

        if ($wins !== null) {
            $updateData['wins'] = intval($wins);
            $formats[] = '%d';
        }

        if ($losses !== null) {
            $updateData['losses'] = intval($losses);
            $formats[] = '%d';
        }

        if ($draws !== null) {
            $updateData['draws'] = intval($draws);
            $formats[] = '%d';
        }

        $result = $this->wpdb->update(
            $tableName,
            $updateData,
            ['id' => $userMissionId],
            $formats,
            ['%d']
        );

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Pobiera czas rozpoczęcia misji
     */
    private function getMissionStartTime($userMissionId)
    {
        $tableName = $this->dbManager->getTableName('game_user_missions');
        return $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT started_at FROM {$tableName} WHERE id = %d", $userMissionId)
        );
    }

    /**
     * Dodaje zadanie do misji
     */
    public function addMissionTask($userMissionId, $taskId, $taskType, $options = [])
    {
        $tableName = $this->dbManager->getTableName('game_user_mission_tasks');

        $data = [
            'user_mission_id' => $userMissionId,
            'task_id' => $taskId,
            'task_type' => $taskType,
            'status' => 'not_started',
            'attempts' => 0,
            'location_id' => $options['location_id'] ?? null,
            'scene_id' => $options['scene_id'] ?? null,
            'npc_ids' => isset($options['npc_ids']) ? json_encode($options['npc_ids']) : null,
            'enemy_ids' => isset($options['enemy_ids']) ? json_encode($options['enemy_ids']) : null
        ];

        $result = $this->wpdb->insert(
            $tableName,
            $data,
            ['%d', '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s']
        );

        return [
            'success' => $result !== false,
            'task_db_id' => $this->wpdb->insert_id,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Pobiera zadania misji
     */
    public function getMissionTasks($userMissionId)
    {
        $tableName = $this->dbManager->getTableName('game_user_mission_tasks');

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$tableName} WHERE user_mission_id = %d ORDER BY id ASC",
                $userMissionId
            )
        );
    }

    /**
     * Aktualizuje status zadania
     */
    public function updateTaskStatus($taskDbId, $status, $incrementAttempts = false)
    {
        $tableName = $this->dbManager->getTableName('game_user_mission_tasks');

        $updateData = ['status' => $status];
        $formats = ['%s'];

        if ($status === 'completed') {
            $updateData['completed_at'] = current_time('mysql');
            $formats[] = '%s';
        }

        if ($incrementAttempts) {
            $updateData['attempts'] = $this->wpdb->get_var(
                $this->wpdb->prepare("SELECT attempts FROM {$tableName} WHERE id = %d", $taskDbId)
            ) + 1;
            $formats[] = '%d';
        }

        $result = $this->wpdb->update(
            $tableName,
            $updateData,
            ['id' => $taskDbId],
            $formats,
            ['%d']
        );

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Usuwa misję gracza (i wszystkie jej zadania)
     */
    public function removeMission($userMissionId)
    {
        $this->wpdb->query('START TRANSACTION');

        try {
            // Usuń zadania
            $tasksTable = $this->dbManager->getTableName('game_user_mission_tasks');
            $this->wpdb->delete(
                $tasksTable,
                ['user_mission_id' => $userMissionId],
                ['%d']
            );

            // Usuń misję
            $missionsTable = $this->dbManager->getTableName('game_user_missions');
            $result = $this->wpdb->delete(
                $missionsTable,
                ['id' => $userMissionId],
                ['%d']
            );

            if ($result === false) {
                throw new Exception('Błąd podczas usuwania misji');
            }

            $this->wpdb->query('COMMIT');
            return ['success' => true];
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
