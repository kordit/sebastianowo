<?php

/**
 * Klasa TaskConditionChecker
 *
 * Sprawdza warunki stanu zadań dla dialogów NPC.
 *
 * @package Game
 * @since 1.0.0
 */

class TaskConditionChecker implements ConditionChecker
{
    /**
     * Logger do zapisywania informacji o działaniu
     *
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Konstruktor klasy TaskConditionChecker
     */
    public function __construct()
    {
        $this->logger = new NpcLogger();
    }

    /**
     * Implementacja metody z interfejsu ConditionChecker
     *
     * @param array $conditions Warunki do sprawdzenia
     * @return bool Czy warunki są spełnione
     */
    public function check_conditions(array $conditions): bool
    {
        // Ta metoda jest wymagana przez interfejs ConditionChecker
        foreach ($conditions as $condition) {
            if (isset($condition['type']) && $condition['type'] === 'task') {
                $criteria = [
                    'user_id' => $condition['user_id'] ?? get_current_user_id(),
                    'npc_id' => $condition['npc_id'] ?? 0
                ];
                if (!$this->check_condition($condition, $criteria)) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Sprawdza warunek stanu zadania w misji użytkownika
     *
     * @param array $condition Warunek zadania
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    public function check_condition(array $condition, array $criteria): bool
    {
        $user_id = $criteria['user_id'] ?? 0;
        $condition_op = $condition['condition'] ?? '';
        $mission_id = isset($condition['mission_id']) ? absint($condition['mission_id']) : 0;
        $task_id = $condition['task_id'] ?? '';
        $required_status = $condition['status'] ?? '';

        // Wyciągnij ID NPC z kontekstu dla sprawdzenia statusu NPC-specyficznego
        $npc_id = $criteria['npc_id'] ?? 0;

        $this->logger->log("Sprawdzanie warunku zadania:", 'debug');
        $this->logger->log("- User ID: {$user_id}", 'debug');
        $this->logger->log("- Operator warunku: {$condition_op}", 'debug');
        $this->logger->log("- ID misji: {$mission_id}", 'debug');
        $this->logger->log("- ID zadania: {$task_id}", 'debug');
        $this->logger->log("- ID NPC: {$npc_id}", 'debug');
        $this->logger->log("- Wymagany status: {$required_status}", 'debug');

        // Jeśli użytkownik nie jest zalogowany lub brak ID misji/zadania, warunek nie jest spełniony
        if (!$user_id || !$mission_id || empty($task_id)) {
            $this->logger->log("- Brak User ID, Mission ID lub Task ID - warunek niespełniony", 'debug');
            return false;
        }

        // Pobierz dane misji użytkownika
        $mission_field_name = "mission_{$mission_id}";
        $user_mission_data = get_field($mission_field_name, "user_{$user_id}");
        $this->logger->log("- Pobrano dane misji użytkownika", 'debug');

        // Sprawdź czy misja istnieje w danych użytkownika
        if (!$user_mission_data || !is_array($user_mission_data)) {
            $this->logger->log("- Misja nie istnieje w danych użytkownika - warunek niespełniony", 'debug');
            return false;
        }

        // Sprawdzenie czy mamy do czynienia ze specjalną strukturą statusu NPC
        if (strpos($required_status, 'completed_npc') !== false && $npc_id > 0) {
            // Sprawdź czy zadanie istnieje w misji
            if (!isset($user_mission_data['tasks']) || !is_array($user_mission_data['tasks']) || !isset($user_mission_data['tasks'][$task_id])) {
                $this->logger->log("- Zadanie {$task_id} nie istnieje w danych misji - warunek niespełniony", 'debug');
                return false;
            }

            // Sprawdź czy zadanie ma strukturę tablicową (zawierającą dane NPC)
            $task_data = $user_mission_data['tasks'][$task_id];
            $this->logger->log("- Pobrano dane zadania", 'debug');

            if (is_array($task_data)) {
                // Sprawdź status NPC w zadaniu
                $npc_field = "npc_{$npc_id}";
                if (isset($task_data[$npc_field])) {
                    $npc_status = $task_data[$npc_field];
                    $this->logger->log("- Status NPC {$npc_id} w zadaniu: {$npc_status}", 'debug');

                    // Porównaj status NPC z wymaganym (zazwyczaj "completed")
                    $result = ($npc_status === 'completed');
                    $this->logger->log("- Warunek 'completed_npc' (status {$npc_field}: {$npc_status} === completed): " .
                        ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                    return $result;
                } else {
                    // Sprawdź status dla npc_target_ID
                    $npc_target_field = "npc_target_{$npc_id}";
                    if (isset($task_data[$npc_target_field])) {
                        $npc_status = $task_data[$npc_target_field];
                        $this->logger->log("- Status NPC target {$npc_id} w zadaniu: {$npc_status}", 'debug');

                        // Porównaj status NPC z wymaganym (zazwyczaj "completed")
                        $result = ($npc_status === 'completed');
                        $this->logger->log("- Warunek 'completed_npc' (status {$npc_target_field}: {$npc_status} === completed): " .
                            ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                        return $result;
                    }
                }

                // Sprawdź ogólny status zadania w strukturze tablicowej
                if (isset($task_data['status'])) {
                    $current_task_status = $task_data['status'];
                    $this->logger->log("- Aktualny ogólny status zadania: {$current_task_status}", 'debug');
                } else {
                    $this->logger->log("- Brak ogólnego statusu zadania w strukturze tablicowej - warunek niespełniony", 'debug');
                    return false;
                }
            } else {
                // Jeśli zadanie nie ma struktury tablicowej, uznaj jego wartość za status
                $current_task_status = $task_data;
                $this->logger->log("- Aktualny status zadania (wartość prosta): {$current_task_status}", 'debug');
            }
        } else {
            // Pobierz aktualny status zadania - standardowa obsługa
            if (!isset($user_mission_data['tasks']) || !is_array($user_mission_data['tasks'])) {
                $this->logger->log("- Brak danych zadań w misji - warunek niespełniony", 'debug');
                return false;
            }

            if (!isset($user_mission_data['tasks'][$task_id])) {
                $this->logger->log("- Zadanie {$task_id} nie istnieje w danych misji - warunek niespełniony", 'debug');
                return false;
            }

            $task_data = $user_mission_data['tasks'][$task_id];

            // Sprawdź czy zadanie ma strukturę tablicową czy jest prostą wartością
            if (is_array($task_data)) {
                if (isset($task_data['status'])) {
                    $current_task_status = $task_data['status'];
                    $this->logger->log("- Aktualny status zadania (z tablicy): {$current_task_status}", 'debug');
                } else {
                    $this->logger->log("- Brak pola status w danych zadania - warunek niespełniony", 'debug');
                    return false;
                }
            } else {
                $current_task_status = $task_data;
                $this->logger->log("- Aktualny status zadania (wartość prosta): {$current_task_status}", 'debug');
            }
        }

        // Standardowe sprawdzenie warunku dla statusu zadania
        switch ($condition_op) {
            case 'is':
                // Sprawdź czy zadanie ma dokładnie taki status jak wymagany
                $result = ($current_task_status === $required_status);
                $this->logger->log("- Warunek 'is' ({$current_task_status} === {$required_status}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                return $result;

            case 'is_not':
                // Sprawdź czy zadanie ma inny status niż wymagany
                $result = ($current_task_status !== $required_status);
                $this->logger->log("- Warunek 'is_not' ({$current_task_status} !== {$required_status}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                return $result;

            case 'is_at_least':
                // Sprawdź czy zadanie ma status co najmniej taki jak wymagany
                $status_hierarchy = [
                    'not_started' => 0,
                    'in_progress' => 1,
                    'completed' => 2,
                    'failed' => 3
                ];

                $current_level = isset($status_hierarchy[$current_task_status]) ? $status_hierarchy[$current_task_status] : -1;
                $required_level = isset($status_hierarchy[$required_status]) ? $status_hierarchy[$required_status] : -1;

                $result = ($current_level >= $required_level);
                $this->logger->log("- Warunek 'is_at_least' ({$current_task_status} [poziom: {$current_level}] >= {$required_status} [poziom: {$required_level}]): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                return $result;

            case 'is_before':
                // Sprawdź czy zadanie ma status niższy niż wymagany
                $status_hierarchy = [
                    'not_started' => 0,
                    'in_progress' => 1,
                    'completed' => 2,
                    'failed' => 3
                ];

                $current_level = isset($status_hierarchy[$current_task_status]) ? $status_hierarchy[$current_task_status] : -1;
                $required_level = isset($status_hierarchy[$required_status]) ? $status_hierarchy[$required_status] : -1;

                $result = ($current_level < $required_level);
                $this->logger->log("- Warunek 'is_before' ({$current_task_status} [poziom: {$current_level}] < {$required_status} [poziom: {$required_level}]): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                return $result;

            default:
                $this->logger->log("- Nieznany operator warunku zadania: {$condition_op}", 'debug');
                return false;
        }
    }
}
