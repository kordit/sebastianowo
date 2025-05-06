<?php

/**
 * Klasa MissionConditionChecker
 *
 * Sprawdza warunki stanu misji dla dialogów NPC.
 *
 * @package Game
 * @since 1.0.0
 */

class MissionConditionChecker extends ConditionChecker
{
    /**
     * Sprawdza warunek stanu misji użytkownika
     *
     * @param array $condition Warunek misji
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    public function check_condition(array $condition, array $criteria): bool
    {
        $user_id = $criteria['user_id'] ?? 0;
        $condition_op = $condition['condition'] ?? '';
        $mission_id = isset($condition['mission_id']) ? absint($condition['mission_id']) : 0;
        $required_status = $condition['status'] ?? '';

        $this->logger->debug_log("Sprawdzanie warunku misji:");
        $this->logger->debug_log("- User ID: {$user_id}");
        $this->logger->debug_log("- Operator warunku: {$condition_op}");
        $this->logger->debug_log("- ID misji: {$mission_id}");
        $this->logger->debug_log("- Wymagany status: {$required_status}");

        // Jeśli użytkownik nie jest zalogowany lub brak ID misji, warunek nie jest spełniony
        if (!$user_id || !$mission_id) {
            $this->logger->debug_log("- Brak User ID lub Mission ID - warunek niespełniony");
            return false;
        }

        // Pobierz dane misji użytkownika
        $mission_field_name = "mission_{$mission_id}";
        $user_mission_data = get_field($mission_field_name, "user_{$user_id}");
        $this->logger->debug_log("- Dane misji użytkownika:", $user_mission_data);

        // Sprawdź czy misja istnieje w danych użytkownika
        if (!$user_mission_data || !is_array($user_mission_data)) {
            $this->logger->debug_log("- Misja nie istnieje w danych użytkownika - warunek niespełniony");
            return false;
        }

        // Pobierz aktualny status misji
        $current_status = isset($user_mission_data['status']) ? $user_mission_data['status'] : '';
        $this->logger->debug_log("- Aktualny status misji: {$current_status}");

        // Sprawdź warunek misji w zależności od operatora
        switch ($condition_op) {
            case 'is':
                // Sprawdź czy misja ma dokładnie taki status jak wymagany
                $result = ($current_status === $required_status);
                $this->logger->debug_log("- Warunek 'is' ({$current_status} === {$required_status}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'is_not':
                // Sprawdź czy misja ma inny status niż wymagany
                $result = ($current_status !== $required_status);
                $this->logger->debug_log("- Warunek 'is_not' ({$current_status} !== {$required_status}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'is_at_least':
                // Sprawdź czy misja ma status co najmniej taki jak wymagany (np. started >= not_started)
                $status_hierarchy = [
                    'not_assigned' => 0,
                    'not_started' => 1,
                    'started' => 2,
                    'completed' => 3,
                    'failed' => 4
                ];

                $current_level = isset($status_hierarchy[$current_status]) ? $status_hierarchy[$current_status] : -1;
                $required_level = isset($status_hierarchy[$required_status]) ? $status_hierarchy[$required_status] : -1;

                $result = ($current_level >= $required_level);
                $this->logger->debug_log("- Warunek 'is_at_least' ({$current_status} [poziom: {$current_level}] >= {$required_status} [poziom: {$required_level}]): " . ($result ? 'SPEŁNIONY' : 'NIESPEłNIONY'));
                return $result;

            case 'has_task_completed':
                // Sprawdź czy zadanie w misji jest ukończone
                $task_id = $required_status; // W tym przypadku required_status to ID zadania

                if (isset($user_mission_data['tasks']) && is_array($user_mission_data['tasks']) && isset($user_mission_data['tasks'][$task_id])) {
                    $task_status = $user_mission_data['tasks'][$task_id];
                    $result = ($task_status === 'completed');
                    $this->logger->debug_log("- Warunek 'has_task_completed' (zadanie {$task_id} ma status: {$task_status}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                    return $result;
                }

                $this->logger->debug_log("- Zadanie {$task_id} nie istnieje w danych misji - warunek niespełniony");
                return false;

            default:
                $this->logger->debug_log("- Nieznany operator warunku misji: {$condition_op}");
                return false;
        }
    }
}
