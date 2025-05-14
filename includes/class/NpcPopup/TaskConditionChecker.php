<?php

/**
 * Klasa TaskConditionChecker
 * 
 * Implementacja sprawdzania warunków zadań (tasks) dla dialogów NPC.
 * 
 * @package Game
 * @subpackage NpcPopup
 * @since 1.0.0
 */
class TaskConditionChecker implements ConditionChecker
{
    /**
     * ID użytkownika
     *
     * @var int
     */
    private int $user_id;

    /**
     * Logger
     *
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Konstruktor klasy TaskConditionChecker
     *
     * @param int $user_id ID użytkownika
     */
    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
        $this->logger = new NpcLogger();
    }

    /**
     * Sprawdza, czy określone warunki są spełnione
     *
     * @param array $conditions Warunki do sprawdzenia
     * @return bool Czy warunki są spełnione
     */
    public function check_conditions(array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $acf_fc_layout = $condition['acf_fc_layout'] ?? '';

            if ($acf_fc_layout === 'condition_task') {
                $result = $this->check_task_condition($condition);
                if (!$result) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Sprawdza warunek zadania
     *
     * @param array $condition Warunek do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    private function check_task_condition(array $condition): bool
    {
        $condition_type = $condition['condition'] ?? '';
        $mission_id = $condition['mission_id'] ?? 0;
        $task_id = $condition['task_id'] ?? '';
        $status = $condition['status'] ?? '';

        if (empty($mission_id) || empty($task_id) || empty($status)) {
            $this->logger->debug_log("Niepełne dane warunku zadania: mission_id=$mission_id, task_id=$task_id, status=$status");
            return true; // W przypadku braku danych zakładamy, że warunek jest spełniony
        }

        // Pobierz dane misji dla użytkownika
        $mission_field_key = 'mission_' . $mission_id;
        $mission_data = get_field($mission_field_key, 'user_' . $this->user_id);

        $this->logger->debug_log("Sprawdzanie warunku zadania: mission_id=$mission_id, task_id=$task_id, status=$status, condition_type=$condition_type");
        $this->logger->debug_log("Dane misji:", $mission_data ?: "Brak danych");

        // Sprawdź, czy misja istnieje
        if (!is_array($mission_data) || !isset($mission_data['tasks'])) {
            // Misja nie istnieje lub nie ma zadań
            $this->logger->debug_log("Misja $mission_id nie istnieje dla użytkownika $this->user_id lub nie ma zadań");

            // Jeśli warunek to "is_not", to zadanie na pewno NIE ma statusu completed (bo nie istnieje)
            // więc warunek jest spełniony
            return ($condition_type === 'is_not');
        }

        // Sprawdź, czy zadanie istnieje
        if (!isset($mission_data['tasks'][$task_id])) {
            $this->logger->debug_log("Zadanie $task_id nie istnieje w misji $mission_id dla użytkownika $this->user_id");

            // Podobnie jak wyżej, dla "is_not" warunek jest spełniony
            return ($condition_type === 'is_not');
        }

        // Pobierz aktualny status zadania
        $task_value = $mission_data['tasks'][$task_id];
        $current_status = is_array($task_value) ? ($task_value['status'] ?? '') : $task_value;

        $this->logger->debug_log("Aktualny status zadania: $current_status, oczekiwany: $status, warunek: $condition_type");

        // Sprawdź warunek
        if ($condition_type === 'is') {
            // Zadanie MA status określony w warunku
            return ($current_status === $status);
        } elseif ($condition_type === 'is_not') {
            // Zadanie NIE MA statusu określonego w warunku
            return ($current_status !== $status);
        }

        // Nieznany typ warunku - zakładamy, że jest spełniony
        $this->logger->debug_log("Nieznany typ warunku zadania: $condition_type");
        return true;
    }
}
