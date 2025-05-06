<?php

/**
 * Klasa RelationConditionChecker
 *
 * Sprawdza warunki relacji z NPC dla dialogów.
 *
 * @package Game
 * @since 1.0.0
 */

class RelationConditionChecker implements ConditionChecker
{
    /**
     * Logger do zapisywania informacji o działaniu
     *
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Konstruktor klasy RelationConditionChecker
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
        // Może delegować sprawdzanie do innych metod
        foreach ($conditions as $condition) {
            if (isset($condition['type']) && $condition['type'] === 'relation') {
                $criteria = [
                    'user_id' => $condition['user_id'] ?? get_current_user_id()
                ];
                if (!$this->check_condition($condition, $criteria)) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Sprawdza warunek relacji z NPC
     *
     * @param array $condition Warunek do sprawdzenia
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    public function check_condition(array $condition, array $criteria): bool
    {
        // Używaj ID użytkownika z przekazanych kryteriów, a nie pobieraj ponownie
        $user_id = $criteria['user_id'] ?? 0;
        $npc_id = isset($condition['npc_id']) ? absint($condition['npc_id']) : 0;
        $condition_op = $condition['condition'] ?? '';
        $relation_value = isset($condition['relation_value']) ? intval($condition['relation_value']) : 0;

        $this->logger->log("Sprawdzanie warunku relacji z NPC:", 'debug');
        $this->logger->log("- User ID: {$user_id}", 'debug');
        $this->logger->log("- NPC ID: {$npc_id}", 'debug');
        $this->logger->log("- Operator warunku: {$condition_op}", 'debug');
        $this->logger->log("- Wymagana wartość relacji: {$relation_value}", 'debug');

        // Jeśli brak ID NPC, warunek nie jest spełniony
        if (!$npc_id) {
            $this->logger->log("- Brak NPC ID - warunek niespełniony", 'debug');
            return false;
        }

        // Pobierz wartości relacji i spotkania z NPC dla użytkownika
        if ($user_id > 0) {
            $user_relation = intval(get_field('npc-relation-' . $npc_id, 'user_' . $user_id) ?? 0);
            $user_has_met = (bool)(get_field('npc-meet-' . $npc_id, 'user_' . $user_id) ?? false);
        } else {
            // Dla niezalogowanych użytkowników, przyjmij domyślne wartości
            $user_relation = -100; // Domyślna relacja dla niezalogowanych
            $user_has_met = true; // Zakładamy, że niezalogowani "znają" NPC
            $this->logger->log("- Niezalogowany użytkownik, przyjęto domyślną relację: {$user_relation} i znajomość: " . ($user_has_met ? 'TAK' : 'NIE'), 'debug');
        }

        $this->logger->log("- Aktualna wartość relacji: {$user_relation}", 'debug');
        $this->logger->log("- Czy użytkownik spotkał NPC: " . ($user_has_met ? 'TAK' : 'NIE'), 'debug');

        $result = false;

        // Obsłuż różne warunki relacji
        switch ($condition_op) {
            case 'is_known':
                $result = $user_has_met;
                $this->logger->log("- Warunek 'is_known': " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                break;
            case 'is_not_known':
                $result = !$user_has_met;
                $this->logger->log("- Warunek 'is_not_known': " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                break;
            case 'relation_greater_than':
            case 'relation_above': // Obsługuj oba warianty tak samo
                $result = $user_relation > $relation_value;
                $this->logger->log("- Warunek '{$condition_op}' ({$user_relation} > {$relation_value}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                break;
            case 'relation_less_than':
            case 'relation_below': // Obsługuj oba warianty tak samo
                $result = $user_relation < $relation_value;
                $this->logger->log("- Warunek '{$condition_op}' ({$user_relation} < {$relation_value}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                break;
            case 'relation_equal':
            case 'relation_equals':
                $result = ($user_relation == $relation_value);
                $this->logger->log("- Warunek '{$condition_op}' ({$user_relation} == {$relation_value}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                break;
            default:
                $this->logger->log("- Nieznany operator warunku relacji: {$condition_op}", 'debug');
                $result = false;
                break;
        }

        return $result;
    }
}
