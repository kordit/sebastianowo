<?php

/**
 * Repository do zarządzania danymi użytkowników gry
 * Wszystkie operacje CRUD na danych gracza
 */
class GameUserRepository
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

            // 2. Statystyki - domyślne wartości
            $this->wpdb->insert(
                $this->dbManager->getTableName('game_user_stats'),
                [
                    'user_id' => $userId,
                    'strength' => 10,
                    'defense' => 10,
                    'agility' => 10,
                    'intelligence' => 10,
                    'charisma' => 10
                ],
                ['%d', '%d', '%d', '%d', '%d', '%d']
            );

            // 3. Umiejętności - domyślne wartości
            $this->wpdb->insert(
                $this->dbManager->getTableName('game_user_skills'),
                [
                    'user_id' => $userId,
                    'combat' => 1,
                    'steal' => 1,
                    'diplomacy' => 1,
                    'investigation' => 1,
                    'survival' => 1
                ],
                ['%d', '%d', '%d', '%d', '%d', '%d']
            );

            // 4. Progress - początkowy
            $this->wpdb->insert(
                $this->dbManager->getTableName('game_user_progress'),
                [
                    'user_id' => $userId,
                    'experience' => 0,
                    'learning_points' => 5,
                    'reputation' => 0,
                    'level' => 1
                ],
                ['%d', '%d', '%d', '%d', '%d']
            );

            // 5. Witalność - pełne zdrowie
            $this->wpdb->insert(
                $this->dbManager->getTableName('game_user_vitality'),
                [
                    'user_id' => $userId,
                    'max_life' => 100,
                    'current_life' => 100,
                    'max_energy' => 100,
                    'current_energy' => 100
                ],
                ['%d', '%d', '%d', '%d', '%d']
            );

            // 6. Historia postaci - pusta
            $this->wpdb->insert(
                $this->dbManager->getTableName('game_user_story'),
                [
                    'user_id' => $userId,
                    'story_text' => ''
                ],
                ['%d', '%s']
            );

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
     * Pobiera pełne dane gracza
     */
    public function getPlayerData($userId)
    {
        $tables = [
            'main' => 'game_users',
            'stats' => 'game_user_stats',
            'skills' => 'game_user_skills',
            'progress' => 'game_user_progress',
            'vitality' => 'game_user_vitality',
            'story' => 'game_user_story'
        ];

        $data = [];

        foreach ($tables as $key => $tableName) {
            $fullTableName = $this->dbManager->getTableName($tableName);
            $result = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM `$fullTableName` WHERE user_id = %d",
                $userId
            ), ARRAY_A);

            $data[$key] = $result ?: [];
        }

        return $data;
    }

    /**
     * Aktualizuje statystyki gracza
     */
    public function updateStats($userId, $stats)
    {
        $tableName = $this->dbManager->getTableName('game_user_stats');

        $allowedStats = ['strength', 'defense', 'agility', 'intelligence', 'charisma'];
        $updateData = [];
        $formats = [];

        foreach ($stats as $stat => $value) {
            if (in_array($stat, $allowedStats) && is_numeric($value)) {
                $updateData[$stat] = intval($value);
                $formats[] = '%d';
            }
        }

        if (empty($updateData)) {
            return ['success' => false, 'error' => 'Brak poprawnych statystyk do aktualizacji'];
        }

        $result = $this->wpdb->update(
            $tableName,
            $updateData,
            ['user_id' => $userId],
            $formats,
            ['%d']
        );

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null,
            'affected_rows' => $result
        ];
    }

    /**
     * Aktualizuje umiejętności gracza
     */
    public function updateSkills($userId, $skills)
    {
        $tableName = $this->dbManager->getTableName('game_user_skills');

        $allowedSkills = ['combat', 'steal', 'diplomacy', 'investigation', 'survival'];
        $updateData = [];
        $formats = [];

        foreach ($skills as $skill => $value) {
            if (in_array($skill, $allowedSkills) && is_numeric($value)) {
                $updateData[$skill] = intval($value);
                $formats[] = '%d';
            }
        }

        if (empty($updateData)) {
            return ['success' => false, 'error' => 'Brak poprawnych umiejętności do aktualizacji'];
        }

        $result = $this->wpdb->update(
            $tableName,
            $updateData,
            ['user_id' => $userId],
            $formats,
            ['%d']
        );

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null,
            'affected_rows' => $result
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
     * Dodaje relację z NPC
     */
    public function addNpcRelation($userId, $npcId, $relationValue = 0, $isKnown = false)
    {
        $tableName = $this->dbManager->getTableName('game_user_relations');

        // Sprawdzamy czy relacja już istnieje
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$tableName` WHERE user_id = %d AND npc_id = %d",
            $userId,
            $npcId
        ));

        if ($existing) {
            return ['success' => false, 'error' => 'Relacja z tym NPC już istnieje'];
        }

        $result = $this->wpdb->insert(
            $tableName,
            [
                'user_id' => $userId,
                'npc_id' => $npcId,
                'relation_value' => intval($relationValue),
                'is_known' => $isKnown ? 1 : 0,
                'fights_won' => 0,
                'fights_lost' => 0,
                'fights_draw' => 0
            ],
            ['%d', '%d', '%d', '%d', '%d', '%d', '%d']
        );

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }

    /**
     * Aktualizuje relację z NPC
     */
    public function updateNpcRelation($userId, $npcId, $relationValue, $isKnown = null)
    {
        $tableName = $this->dbManager->getTableName('game_user_relations');

        $updateData = ['relation_value' => intval($relationValue)];
        $format = ['%d'];

        if ($isKnown !== null) {
            $updateData['is_known'] = $isKnown ? 1 : 0;
            $format[] = '%d';
        }

        $result = $this->wpdb->update(
            $tableName,
            $updateData,
            ['user_id' => $userId, 'npc_id' => $npcId],
            $format,
            ['%d', '%d']
        );

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
        $allowedResults = ['won', 'lost', 'draw'];

        if (!in_array($result, $allowedResults)) {
            return ['success' => false, 'error' => 'Niepoprawny wynik walki'];
        }

        $column = 'fights_' . $result;

        return $this->deltaManager->increase(
            'game_user_relations',
            ['user_id' => $userId, 'npc_id' => $npcId],
            $column,
            1
        );
    }

    /**
     * Pobiera ranking graczy według doświadczenia
     */
    public function getExperienceRanking($limit = 50)
    {
        $progressTable = $this->dbManager->getTableName('game_user_progress');
        $usersTable = $this->dbManager->getTableName('game_users');

        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT p.user_id, u.nickname, p.experience, p.level, p.reputation 
             FROM `$progressTable` p 
             JOIN `$usersTable` u ON p.user_id = u.user_id 
             ORDER BY p.experience DESC, p.level DESC 
             LIMIT %d",
            $limit
        ));

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
            "SELECT * FROM `$tableName` WHERE user_id = %d AND item_id = %s",
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
                ['%d', '%s']
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
                ['%d', '%s', '%d', '%d']
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
            "SELECT * FROM `$tableName` WHERE user_id = %d AND item_id = %s",
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
                ['%d', '%s']
            );
        } else {
            // Zmniejsz ilość
            $result = $this->wpdb->update(
                $tableName,
                ['quantity' => $existing->quantity - $quantity],
                ['user_id' => $userId, 'item_id' => $itemId],
                ['%d'],
                ['%d', '%s']
            );
        }

        return [
            'success' => $result !== false,
            'error' => $result === false ? $this->wpdb->last_error : null
        ];
    }
}
