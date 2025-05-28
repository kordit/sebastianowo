<?php

/**
 * Klasa odpowiedzialna za bezpieczne zmiany wartości liczbowych w bazie danych
 * Używa delta (różnic) zamiast bezpośrednich wartości aby uniknąć problemów z równoczesnością
 */
class GameDeltaManager
{

    private $wpdb;
    private $dbManager;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->dbManager = GameDatabaseManager::getInstance();
    }

    /**
     * Stosuje zmianę delta do wartości w bazie danych
     * 
     * @param string $table Nazwa tabeli
     * @param array $where Warunki WHERE 
     * @param array $deltas Tablica zmian [kolumna => delta]
     * @param array $limits Opcjonalne limity [kolumna => ['min' => val, 'max' => val]]
     * @return array Wynik operacji
     */
    public function applyDeltas($table, $where, $deltas, $limits = [])
    {
        $fullTableName = $this->dbManager->getTableName($table);

        // Budowanie warunków WHERE
        $whereClause = $this->buildWhereClause($where);

        // Budowanie SET z deltami
        $setClause = $this->buildDeltaSetClause($deltas, $limits);

        if (empty($setClause)) {
            return ['success' => false, 'error' => 'Brak poprawnych delt do zastosowania'];
        }

        $sql = "UPDATE `$fullTableName` SET $setClause WHERE $whereClause";

        $result = $this->wpdb->query($sql);

        if ($result === false) {
            return [
                'success' => false,
                'error' => $this->wpdb->last_error,
                'sql' => $sql
            ];
        }

        return [
            'success' => true,
            'affected_rows' => $result,
            'sql' => $sql
        ];
    }

    /**
     * Zwiększa wartość o określoną deltę
     */
    public function increase($table, $where, $column, $delta, $max = null)
    {
        $deltas = [$column => abs($delta)];
        $limits = [];

        if ($max !== null) {
            $limits[$column] = ['max' => $max];
        }

        return $this->applyDeltas($table, $where, $deltas, $limits);
    }

    /**
     * Zmniejsza wartość o określoną deltę
     */
    public function decrease($table, $where, $column, $delta, $min = null)
    {
        $deltas = [$column => -abs($delta)];
        $limits = [];

        if ($min !== null) {
            $limits[$column] = ['min' => $min];
        }

        return $this->applyDeltas($table, $where, $deltas, $limits);
    }

    /**
     * Stosuje wiele zmian jednocześnie (atomic)
     */
    public function applyMultipleDeltas($operations)
    {
        $this->wpdb->query('START TRANSACTION');

        try {
            foreach ($operations as $operation) {
                $result = $this->applyDeltas(
                    $operation['table'],
                    $operation['where'],
                    $operation['deltas'],
                    $operation['limits'] ?? []
                );

                if (!$result['success']) {
                    $this->wpdb->query('ROLLBACK');
                    return $result;
                }
            }

            $this->wpdb->query('COMMIT');
            return ['success' => true, 'message' => 'Wszystkie operacje zakończone sukcesem'];
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return [
                'success' => false,
                'error' => 'Błąd transakcji: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Buduje klauzulę WHERE
     */
    private function buildWhereClause($where)
    {
        $conditions = [];

        foreach ($where as $column => $value) {
            if (is_array($value)) {
                // Obsługa operatorów jak ['>', 10]
                $operator = $value[0];
                $val = $value[1];
                $conditions[] = "`$column` $operator " . $this->wpdb->prepare('%s', $val);
            } else {
                $conditions[] = "`$column` = " . $this->wpdb->prepare('%s', $value);
            }
        }

        return implode(' AND ', $conditions);
    }

    /**
     * Buduje klauzulę SET z deltami i limitami
     */
    private function buildDeltaSetClause($deltas, $limits)
    {
        $setParts = [];

        foreach ($deltas as $column => $delta) {
            if (!is_numeric($delta) || $delta == 0) {
                continue;
            }

            $columnLimit = $limits[$column] ?? [];
            $deltaExpression = "`$column` + " . intval($delta);

            // Aplikowanie limitów
            if (isset($columnLimit['min']) && isset($columnLimit['max'])) {
                $min = intval($columnLimit['min']);
                $max = intval($columnLimit['max']);
                $deltaExpression = "GREATEST($min, LEAST($max, $deltaExpression))";
            } elseif (isset($columnLimit['min'])) {
                $min = intval($columnLimit['min']);
                $deltaExpression = "GREATEST($min, $deltaExpression)";
            } elseif (isset($columnLimit['max'])) {
                $max = intval($columnLimit['max']);
                $deltaExpression = "LEAST($max, $deltaExpression)";
            }

            $setParts[] = "`$column` = $deltaExpression";
        }

        return implode(', ', $setParts);
    }

    /**
     * Przykładowe użycie dla najczęstszych operacji
     */

    // Dodawanie doświadczenia
    public function addExperience($userId, $exp, $maxLevel = 100)
    {
        return $this->increase('game_user_progress', ['user_id' => $userId], 'experience', $exp);
    }

    // Odjęcie życia z limitem
    public function takeDamage($userId, $damage)
    {
        return $this->decrease('game_user_vitality', ['user_id' => $userId], 'current_life', $damage, 0);
    }

    // Leczenie z limitem max życia
    public function heal($userId, $healing)
    {
        // Najpierw pobieramy max życie użytkownika
        $tableName = $this->dbManager->getTableName('game_user_vitality');
        $maxLife = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT max_life FROM `$tableName` WHERE user_id = %d",
            $userId
        ));

        if ($maxLife === null) {
            return ['success' => false, 'error' => 'Nie znaleziono danych gracza'];
        }

        return $this->increase('game_user_vitality', ['user_id' => $userId], 'current_life', $healing, $maxLife);
    }

    // Zmiana relacji z NPC (-50 do +50)
    public function changeRelation($userId, $npcId, $delta)
    {
        return $this->applyDeltas(
            'game_user_relations',
            ['user_id' => $userId, 'npc_id' => $npcId],
            ['relation_value' => $delta],
            ['relation_value' => ['min' => -50, 'max' => 50]]
        );
    }

    // Dodanie przedmiotu
    public function addItem($userId, $itemId, $quantity = 1)
    {
        $tableName = $this->dbManager->getTableName('game_user_items');

        // Sprawdzamy czy przedmiot już istnieje
        $existingItem = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM `$tableName` WHERE user_id = %d AND item_id = %d",
            $userId,
            $itemId
        ));

        if ($existingItem) {
            // Zwiększamy ilość
            return $this->increase(
                'game_user_items',
                ['user_id' => $userId, 'item_id' => $itemId],
                'quantity',
                $quantity
            );
        } else {
            // Tworzymy nowy wpis
            $result = $this->wpdb->insert(
                $tableName,
                [
                    'user_id' => $userId,
                    'item_id' => $itemId,
                    'quantity' => $quantity
                ],
                ['%d', '%d', '%d']
            );

            return [
                'success' => $result !== false,
                'error' => $result === false ? $this->wpdb->last_error : null
            ];
        }
    }
}
