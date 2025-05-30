<?php

/**
 * Bezpieczny menedżer zasobów gry
 * Centralny punkt zarządzania wszystkimi repositories z walidacją i autoryzacją
 */
class GameResourceManager
{
    private $repositories = [];
    private $current_user_id = null;
    private $authorization_level = 'none';
    private $operation_log = [];

    // Definicje zasobów i ich limitów
    private $resource_limits = [
        'gold' => [
            'min' => 0,
            'max' => null,
            'field' => 'gold'
        ],
        'cigarettes' => [
            'min' => 0,
            'max' => null,
            'field' => 'cigarettes'
        ],
        'exp' => [
            'min' => 0,
            'max' => null,
            'field' => 'exp'
        ],
        'learning_points' => [
            'min' => 0,
            'max' => null,
            'field' => 'learning_points'
        ],
        'life' => [
            'min' => 0,
            'max' => null, // Sprawdzane względem max_life
            'field' => 'life'
        ],
        'energy' => [
            'min' => 0,
            'max' => null, // Sprawdzane względem max_energy
            'field' => 'energy'
        ]
    ];

    /**
     * Konstruktor - inicjalizacja menedżera
     */
    public function __construct()
    {
        $this->initializeRepositories();
        $this->setCurrentUser();
    }

    /**
     * Inicjalizuje wszystkie repositories
     */
    private function initializeRepositories()
    {
        $this->repositories = [
            'users' => new GameUserRepository(),
            'items' => new GameUserItemRepository(),
            'areas' => new GameAreaRepository(),
            'missions' => new GameMissionRepository(),
            'npc_relations' => new GameNPCRelationRepository()
        ];
    }

    /**
     * Ustawia aktualnego użytkownika i jego poziom autoryzacji
     */
    private function setCurrentUser()
    {
        // Sprawdź czy funkcje WordPress są dostępne
        if (!function_exists('is_user_logged_in')) {
            return;
        }

        if (is_user_logged_in()) {
            $this->current_user_id = get_current_user_id();

            if (current_user_can('manage_options')) {
                $this->authorization_level = 'admin';
            } else {
                $this->authorization_level = 'gracz';
            }
        }
    }

    /**
     * Autoryzuje użytkownika do wykonania operacji
     */
    private function authorize($operation, $target_user_id = null, $required_level = 'gracz')
    {
        // Sprawdź czy użytkownik jest zalogowany
        if (!$this->current_user_id) {
            throw new Exception([
                'message' => 'Brak autoryzacji - użytkownik nie jest zalogowany',
                'operation' => $operation,
                'user_id' => null
            ]);
        }

        // Sprawdź poziom uprawnień
        $levels = ['none' => 0, 'gracz' => 1, 'admin' => 2];

        if ($levels[$this->authorization_level] < $levels[$required_level]) {
            throw new Exception([
                'message' => 'Niewystarczające uprawnienia do wykonania operacji',
                'operation' => $operation,
                'required_level' => $required_level,
                'current_level' => $this->authorization_level
            ]);
        }

        // Sprawdź czy użytkownik może wykonać operację na docelowym użytkowniku
        if ($target_user_id && $this->authorization_level === 'gracz' && $target_user_id !== $this->current_user_id) {
            throw new Exception([
                'message' => 'Brak uprawnień do modyfikacji danych innego użytkownika',
                'operation' => $operation,
                'target_user_id' => $target_user_id,
                'current_user_id' => $this->current_user_id
            ]);
        }

        // Loguj operację
        $this->logOperation($operation, $target_user_id);

        return true;
    }

    /**
     * Loguje wykonane operacje
     */
    private function logOperation($operation, $target_user_id = null)
    {
        $log_entry = [
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'user_id' => $this->current_user_id,
            'operation' => $operation,
            'target_user_id' => $target_user_id,
            'authorization_level' => $this->authorization_level
        ];

        $this->operation_log[] = $log_entry;

        // Loguj do pliku w głównym katalogu
        $log_file = ABSPATH . 'game_operations.log';
        $log_line = json_encode($log_entry) . "\n";
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Waliduje dane przed operacją
     */
    private function validateData($type, $data, $user_id = null)
    {
        switch ($type) {
            case 'resource_modification':
                return $this->validateResourceModification($data, $user_id);
            case 'item_operation':
                return $this->validateItemOperation($data, $user_id);
            default:
                throw new Exception([
                    'message' => 'Nieznany typ walidacji',
                    'type' => $type
                ]);
        }
    }

    /**
     * Waliduje modyfikację zasobów użytkownika
     */
    private function validateResourceModification($data, $user_id)
    {
        // Pobierz aktualne dane użytkownika
        $current_user = $this->repositories['users']->getByUserId($user_id);
        if (!$current_user) {
            throw new Exception([
                'message' => 'Użytkownik nie istnieje',
                'user_id' => $user_id
            ]);
        }

        foreach ($data as $resource => $value) {
            if (!isset($this->resource_limits[$resource])) {
                throw new Exception([
                    'message' => 'Nieznany zasób',
                    'resource' => $resource,
                    'available_resources' => array_keys($this->resource_limits)
                ]);
            }

            $limits = $this->resource_limits[$resource];
            $current_value = isset($current_user[$limits['field']]) ? (int)$current_user[$limits['field']] : 0;
            $new_value = $current_value + $value;

            // Sprawdź minimalną wartość
            if ($new_value < $limits['min']) {
                throw new Exception([
                    'message' => 'Niewystarczająca ilość zasobu',
                    'resource' => $resource,
                    'current_value' => $current_value,
                    'change_value' => $value,
                    'resulting_value' => $new_value,
                    'minimum_required' => $limits['min']
                ]);
            }

            // Sprawdź maksymalną wartość
            if ($limits['max'] !== null && $new_value > $limits['max']) {
                throw new Exception([
                    'message' => 'Przekroczono maksymalną ilość zasobu',
                    'resource' => $resource,
                    'limit' => $limits['max'],
                    'attempted_value' => $new_value
                ]);
            }

            // Specjalne sprawdzenia dla życia i energii
            if ($resource === 'life' && $new_value > (int)$current_user['max_life']) {
                throw new Exception([
                    'message' => 'Życie nie może przekroczyć maksymalnego poziomu',
                    'resource' => 'life',
                    'current_value' => $current_value,
                    'attempted_value' => $new_value,
                    'max_allowed' => (int)$current_user['max_life']
                ]);
            }

            if ($resource === 'energy' && $new_value > (int)$current_user['max_energy']) {
                throw new Exception([
                    'message' => 'Energia nie może przekroczyć maksymalnego poziomu',
                    'resource' => 'energy',
                    'current_value' => $current_value,
                    'attempted_value' => $new_value,
                    'max_allowed' => (int)$current_user['max_energy']
                ]);
            }
        }

        return true;
    }

    /**
     * Bezpiecznie modyfikuje zasoby użytkownika
     */
    public function modifyUserResources($user_id, $resources, $reason = '')
    {
        $this->authorize('modify_resources', $user_id);
        $this->validateData('resource_modification', $resources, $user_id);

        try {
            // Rozpocznij transakcję (jeśli dostępna)
            $this->beginTransaction();

            // Pobierz aktualne dane
            $current_data = $this->repositories['users']->getByUserId($user_id);
            $update_data = [];

            foreach ($resources as $resource => $change) {
                $field = $this->resource_limits[$resource]['field'];
                $current_value = (int)$current_data[$field];
                $update_data[$field] = $current_value + $change;
            }

            // Wykonaj aktualizację
            $result = $this->repositories['users']->update($user_id, $update_data);

            // Zatwierdź transakcję
            $this->commitTransaction();

            // Loguj szczegóły operacji
            $this->logDetailedOperation('resource_modification', $user_id, [
                'resources' => $resources,
                'reason' => $reason,
                'result' => $result
            ]);

            return $result;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Bezpiecznie dodaje przedmiot użytkownikowi
     */
    public function addItemToUser($user_id, $item_id, $quantity = 1, $equipped = false)
    {
        $this->authorize('add_item', $user_id);
        $this->validateData('item_operation', $item_id, $user_id);

        // Użyj metody addItem z GameUserItemRepository
        return $this->repositories['items']->addItem($user_id, $item_id, $quantity, $equipped ? 1 : 0);
    }

    /**
     * Bezpiecznie usuwa przedmiot od użytkownika
     */
    public function removeItemFromUser($user_id, $item_id, $quantity = 1)
    {
        $this->authorize('remove_item', $user_id);

        $existing_item = $this->repositories['items']->getUserItem($user_id, $item_id);
        if (!$existing_item) {
            throw new Exception([
                'message' => 'Użytkownik nie posiada tego przedmiotu',
                'user_id' => $user_id,
                'item_id' => $item_id
            ]);
        }

        if ($existing_item['amount'] < $quantity) {
            throw new Exception([
                'message' => 'Niewystarczająca ilość przedmiotu',
                'current_amount' => $existing_item['amount'],
                'requested_amount' => $quantity
            ]);
        }

        return $this->repositories['items']->removeItem($user_id, $item_id, $quantity);
    }



    /**
     * Pobiera dane użytkownika z autoryzacją
     */
    public function getUserData($user_id)
    {
        $this->authorize('get_user_data', $user_id);
        return $this->repositories['users']->getByUserId($user_id);
    }

    /**
     * Pobiera przedmioty użytkownika z autoryzacją
     */
    public function getUserItems($user_id)
    {
        $this->authorize('get_user_items', $user_id);
        return $this->repositories['items']->getUserItems($user_id);
    }

    /**
     * Pobiera misje użytkownika z autoryzacją
     */
    public function getUserMissions($user_id, $status = null)
    {
        $this->authorize('get_user_missions', $user_id);

        if ($status) {
            return $this->repositories['missions']->getUserMissionsByStatus($user_id, $status);
        }

        return $this->repositories['missions']->getUserMissions($user_id);
    }



    /**
     * Loguje szczegółową operację
     */
    private function logDetailedOperation($type, $user_id, $details)
    {
        // Tutaj można dodać szczegółowe logowanie do bazy danych lub pliku
        error_log("GameResourceManager: {$type} - User: {$user_id} - " . json_encode($details));
    }

    /**
     * Rozpoczyna transakcję (jeśli obsługiwana)
     */
    private function beginTransaction()
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
    }

    /**
     * Zatwierdza transakcję
     */
    private function commitTransaction()
    {
        global $wpdb;
        $wpdb->query('COMMIT');
    }

    /**
     * Cofa transakcję
     */
    private function rollbackTransaction()
    {
        global $wpdb;
        $wpdb->query('ROLLBACK');
    }

    /**
     * Pobiera logi operacji (tylko admin)
     */
    public function getOperationLogs()
    {
        $this->authorize('get_logs', null, 'admin');
        return $this->operation_log;
    }

    /**
     * Sprawdza czy użytkownik może wykonać operację bez autoryzacji
     */
    public function canPerformOperation($operation, $target_user_id = null, $required_level = 'user')
    {
        try {
            $this->authorize($operation, $target_user_id, $required_level);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pobiera aktualny poziom autoryzacji
     */
    public function getAuthorizationLevel()
    {
        return $this->authorization_level;
    }

    /**
     * Pobiera ID aktualnego użytkownika
     */
    public function getCurrentUserId()
    {
        return $this->current_user_id;
    }

    /**
     * Bezpiecznie zmienia status misji użytkownika (uproszczone zarządzanie misjami)
     */
    public function changeMissionStatus($user_id, $mission_id, $task_id, $new_status)
    {
        $this->authorize('change_mission_status', $user_id);

        // Waliduj status
        $valid_statuses = ['not_started', 'in_progress', 'completed', 'failed', 'expired'];
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception([
                'message' => 'Nieprawidłowy status misji',
                'provided_status' => $new_status,
                'valid_statuses' => $valid_statuses
            ]);
        }

        try {
            $result = $this->repositories['missions']->updateTaskStatus($user_id, $mission_id, $task_id, $new_status);

            $this->logOperation('mission_status_change', $user_id, [
                'mission_id' => $mission_id,
                'task_id' => $task_id,
                'new_status' => $new_status,
                'result' => $result
            ]);

            return $result;
        } catch (Exception $e) {
            throw new Exception([
                'message' => 'Błąd podczas zmiany statusu misji',
                'error' => $e->getMessage(),
                'mission_id' => $mission_id,
                'task_id' => $task_id
            ]);
        }
    }



    /**
     * Bezpiecznie dodaje przedmiot z walidacją istnienia
     */
    public function addValidatedItemToUser($user_id, $item_id, $quantity = 1, $equipped = false, $reason = '')
    {
        $this->authorize('add_item', $user_id);
        $this->validateData('item_operation', ['item_id' => $item_id], $user_id);

        try {
            $result = $this->repositories['items']->addItem($user_id, $item_id, $quantity, $equipped ? 1 : 0);

            $this->logDetailedOperation('item_added', $user_id, [
                'item_id' => $item_id,
                'quantity' => $quantity,
                'equipped' => $equipped,
                'reason' => $reason,
                'result' => $result
            ]);

            return $result;
        } catch (Exception $e) {
            throw new Exception([
                'message' => 'Błąd podczas dodawania przedmiotu',
                'error' => $e->getMessage(),
                'item_id' => $item_id,
                'user_id' => $user_id
            ]);
        }
    }

    /**
     * Bezpiecznie zmienia status wyposażenia przedmiotu
     */
    public function toggleItemEquipped($user_id, $item_id, $equipped = true, $slot = '')
    {
        $this->authorize('toggle_item_equipped', $user_id);

        // Sprawdź czy użytkownik ma ten przedmiot
        $existing_item = $this->repositories['items']->getUserItem($user_id, $item_id);
        if (!$existing_item) {
            throw new Exception([
                'message' => 'Użytkownik nie posiada tego przedmiotu',
                'user_id' => $user_id,
                'item_id' => $item_id
            ]);
        }

        try {
            $result = $this->repositories['items']->setEquipped($user_id, $item_id, $equipped, $slot);

            $this->logDetailedOperation('item_equipped_changed', $user_id, [
                'item_id' => $item_id,
                'equipped' => $equipped,
                'slot' => $slot,
                'result' => $result
            ]);

            return $result;
        } catch (Exception $e) {
            throw new Exception([
                'message' => 'Błąd podczas zmiany statusu wyposażenia',
                'error' => $e->getMessage(),
                'user_id' => $user_id,
                'item_id' => $item_id
            ]);
        }
    }

    /**
     * Bezpiecznie ustawia konkretną ilość przedmiotu
     */
    public function setItemAmount($user_id, $item_id, $amount, $reason = '')
    {
        $this->authorize('set_item_amount', $user_id);
        $this->validateData('item_operation', ['item_id' => $item_id], $user_id);

        if ($amount < 0) {
            throw new Exception([
                'message' => 'Ilość przedmiotu nie może być ujemna',
                'provided_amount' => $amount,
                'item_id' => $item_id,
                'user_id' => $user_id
            ]);
        }

        try {
            $result = $this->repositories['items']->setItemAmount($user_id, $item_id, $amount);

            $this->logDetailedOperation('item_amount_set', $user_id, [
                'item_id' => $item_id,
                'amount' => $amount,
                'reason' => $reason,
                'result' => $result
            ]);

            return $result;
        } catch (Exception $e) {
            throw new Exception([
                'message' => 'Błąd podczas ustawiania ilości przedmiotu',
                'error' => $e->getMessage(),
                'item_id' => $item_id,
                'user_id' => $user_id,
                'amount' => $amount
            ]);
        }
    }

    /**
     * Pobiera statystyki przedmiotów użytkownika
     */
    public function getUserItemStats($user_id)
    {
        $this->authorize('get_user_item_stats', $user_id);

        try {
            $stats = $this->repositories['items']->getUserItemStats($user_id);
            return $stats;
        } catch (Exception $e) {
            throw new Exception([
                'message' => 'Błąd podczas pobierania statystyk przedmiotów',
                'error' => $e->getMessage(),
                'user_id' => $user_id
            ]);
        }
    }
}
