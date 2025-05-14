<?php

// Dodajemy import nowej klasy
require_once get_template_directory() . '/includes/class/NpcPopup/TaskConditionChecker.php';

/**
 * Klasa ConditionCheckerFactory
 * 
 * Fabryka tworząca obiekty sprawdzające warunki dialogów NPC.
 * 
 * @package Game
 * @subpackage NpcPopup
 * @since 1.0.0
 */
class ConditionCheckerFactory
{
    /**
     * Logger do zapisywania informacji o działaniu
     *
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Konstruktor klasy ConditionCheckerFactory
     *
     * @param NpcLogger $logger Logger do zapisywania informacji
     */
    public function __construct(NpcLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Tworzy i zwraca odpowiedni obiekt sprawdzający warunki
     *
     * @param int $user_id ID użytkownika
     * @param int $npc_id ID NPC
     * @param array $criteria Kryteria do sprawdzenia (location, type_page, itp.)
     * @return ConditionChecker Obiekt sprawdzający warunki
     */
    public function create(int $user_id, int $npc_id, array $criteria): ConditionChecker
    {
        $this->logger->debug_log("Tworzenie sprawdzacza warunków dla User ID: {$user_id}, NPC ID: {$npc_id}");

        // Przygotuj informacje o lokalizacji dla DefaultConditionChecker
        $location = [];
        if (isset($criteria['location'])) {
            $location['area_slug'] = $criteria['location'];
        }
        if (isset($criteria['type_page'])) {
            $location['page_type'] = $criteria['type_page'];
        }

        // W przyszłości można rozbudować o różne typy checkerów na podstawie kryteriów
        return new CombinedConditionChecker($user_id, $npc_id, $location, $this->logger);
    }
}

/**
 * Klasa CombinedConditionChecker
 * 
 * Łączy różne implementacje ConditionChecker, aby sprawdzać wszystkie typy warunków.
 * 
 * @package Game
 * @subpackage NpcPopup
 * @since 1.0.0
 */
class CombinedConditionChecker implements ConditionChecker
{
    /**
     * Sprawdzacz warunków domyślnych
     *
     * @var DefaultConditionChecker
     */
    private DefaultConditionChecker $defaultChecker;

    /**
     * Sprawdzacz warunków zadań
     *
     * @var TaskConditionChecker
     */
    private TaskConditionChecker $taskChecker;

    /**
     * Logger
     *
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Konstruktor klasy CombinedConditionChecker
     *
     * @param int $user_id ID użytkownika
     * @param int $npc_id ID NPC
     * @param array $location Informacje o lokalizacji
     * @param NpcLogger $logger Logger do zapisywania informacji
     */
    public function __construct(int $user_id, int $npc_id, array $location, NpcLogger $logger)
    {
        $this->defaultChecker = new DefaultConditionChecker($user_id, $npc_id, $location);
        $this->taskChecker = new TaskConditionChecker($user_id);
        $this->logger = $logger;
    }

    /**
     * Sprawdza, czy określone warunki są spełnione
     * 
     * Deleguje sprawdzanie do odpowiednich sprawdzaczy w zależności od typu warunku.
     *
     * @param array $conditions Warunki do sprawdzenia
     * @return bool Czy warunki są spełnione
     */
    public function check_conditions(array $conditions): bool
    {
        // Jeśli brak warunków, dialog jest dostępny
        if (empty($conditions)) {
            return true;
        }

        // Sprawdzanie warunków widoczności dla layoutu
        if (isset($conditions['visibility_settings']) && is_array($conditions['visibility_settings'])) {
            // Pobierz operator logiczny (domyślnie AND)
            $logic_operator = ($conditions['logic_operator'] ?? 'and') === 'or' ? 'or' : 'and';
            $this->logger->debug_log("Sprawdzanie warunków widoczności z operatorem: $logic_operator");

            // Inicjalizacja wyniku w zależności od operatora
            $result = ($logic_operator === 'and');

            foreach ($conditions['visibility_settings'] as $condition) {
                // Sprawdź typ warunku
                $acf_fc_layout = $condition['acf_fc_layout'] ?? '';

                // Deleguj sprawdzanie do odpowiedniego sprawdzacza
                $condition_result = false;

                if ($acf_fc_layout === 'condition_task') {
                    // Warunek zadania - użyj TaskConditionChecker
                    $condition_result = $this->taskChecker->check_task_condition($condition);
                    $this->logger->debug_log("TaskConditionChecker dla warunku '$acf_fc_layout' zwrócił: " . ($condition_result ? 'TAK' : 'NIE'));
                } else {
                    // Inne typy warunków - przekaż do DefaultConditionChecker
                    $mapped_condition = $this->map_acf_condition_to_default_format($condition);
                    $condition_result = $this->defaultChecker->check_condition($mapped_condition);
                    $this->logger->debug_log("DefaultConditionChecker dla warunku '$acf_fc_layout' zwrócił: " . ($condition_result ? 'TAK' : 'NIE'));
                }

                // Przetwórz wynik zgodnie z operatorem
                if ($logic_operator === 'and') {
                    $result = $result && $condition_result;
                    // Jeśli którykolwiek warunek nie jest spełniony, możemy przerwać
                    if (!$result) {
                        break;
                    }
                } else { // OR
                    $result = $result || $condition_result;
                    // Jeśli którykolwiek warunek jest spełniony, możemy przerwać
                    if ($result) {
                        break;
                    }
                }
            }

            return $result;
        }

        // Dla starszego formatu warunków (bez zagnieżdżenia) - używaj DefaultConditionChecker
        return $this->defaultChecker->check_conditions($conditions);
    }

    /**
     * Mapuje warunek z formatu ACF na format używany przez DefaultConditionChecker
     *
     * @param array $acf_condition Warunek w formacie ACF
     * @return array Warunek w formacie dla DefaultConditionChecker
     */
    private function map_acf_condition_to_default_format(array $acf_condition): array
    {
        $mapped_condition = [];
        $acf_fc_layout = $acf_condition['acf_fc_layout'] ?? '';

        switch ($acf_fc_layout) {
            case 'condition_npc_relation':
                $mapped_condition['type'] = 'npc_relation';
                $mapped_condition['value'] = $acf_condition['npc_id'] ?? 0;
                $mapped_condition['min_value'] = $acf_condition['relation_value'] ?? 0;
                break;

            case 'condition_mission':
                if (($acf_condition['condition'] ?? '') === 'is_completed') {
                    $mapped_condition['type'] = 'mission_completed';
                } else {
                    $mapped_condition['type'] = 'mission_active';
                }
                $mapped_condition['value'] = $acf_condition['mission_id'] ?? 0;
                break;

            case 'condition_item':
                $mapped_condition['type'] = 'has_item';
                $mapped_condition['value'] = $acf_condition['item_id'] ?? 0;
                $mapped_condition['quantity'] = $acf_condition['quantity'] ?? 1;
                break;

            case 'condition_player_level':
                $mapped_condition['type'] = 'player_level';
                $mapped_condition['min_level'] = $acf_condition['level'] ?? 1;
                break;

            default:
                // Dla nieznanych typów - przekaż oryginalny warunek
                $mapped_condition = $acf_condition;
                break;
        }

        return $mapped_condition;
    }
}

/**
 * Klasa DefaultConditionChecker
 * 
 * Domyślna implementacja sprawdzania warunków dialogów NPC.
 * 
 * @package Game
 * @subpackage NpcPopup
 * @since 1.0.0
 */
class DefaultConditionChecker implements ConditionChecker
{
    /**
     * ID użytkownika
     *
     * @var int
     */
    private int $user_id;

    /**
     * ID NPC
     *
     * @var int
     */
    private int $npc_id;

    /**
     * Informacje o lokalizacji
     *
     * @var array
     */
    private array $location;

    /**
     * Logger
     *
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Konstruktor klasy DefaultConditionChecker
     *
     * @param int $user_id ID użytkownika
     * @param int $npc_id ID NPC
     * @param array $location Informacje o lokalizacji
     */
    public function __construct(int $user_id, int $npc_id, array $location)
    {
        $this->user_id = $user_id;
        $this->npc_id = $npc_id;
        $this->location = $location;
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
        if (empty($conditions)) {
            return true; // Brak warunków oznacza, że dialog jest zawsze dostępny
        }

        foreach ($conditions as $condition) {
            $condition_type = $condition['type'] ?? '';
            $condition_value = $condition['value'] ?? '';

            switch ($condition_type) {
                case 'mission_completed':
                    if (!$this->check_mission_completed($condition_value)) {
                        return false;
                    }
                    break;

                case 'mission_active':
                    if (!$this->check_mission_active($condition_value)) {
                        return false;
                    }
                    break;

                case 'has_item':
                    $quantity = $condition['quantity'] ?? 1;
                    if (!$this->check_has_item($condition_value, $quantity)) {
                        return false;
                    }
                    break;

                case 'npc_relation':
                    $min_value = $condition['min_value'] ?? 0;
                    if (!$this->check_npc_relation($condition_value, $min_value)) {
                        return false;
                    }
                    break;

                case 'player_level':
                    $min_level = $condition['min_level'] ?? 1;
                    if (!$this->check_player_level($min_level)) {
                        return false;
                    }
                    break;

                default:
                    $this->logger->log("Nieobsługiwany typ warunku: {$condition_type}", 'warning');
                    // W przypadku nieznanego warunku zakładamy, że jest spełniony
                    break;
            }
        }

        return true;
    }

    /**
     * Sprawdza, czy misja została ukończona
     *
     * @param int|string $mission_id ID misji
     * @return bool Czy misja została ukończona
     */
    private function check_mission_completed($mission_id): bool
    {
        $completed_missions = get_user_meta($this->user_id, 'completed_missions', true);

        if (!is_array($completed_missions)) {
            $completed_missions = [];
        }

        return in_array($mission_id, $completed_missions);
    }

    /**
     * Sprawdza, czy misja jest aktywna
     *
     * @param int|string $mission_id ID misji
     * @return bool Czy misja jest aktywna
     */
    private function check_mission_active($mission_id): bool
    {
        $active_missions = get_user_meta($this->user_id, 'active_missions', true);

        if (!is_array($active_missions)) {
            $active_missions = [];
        }

        return in_array($mission_id, $active_missions);
    }

    /**
     * Sprawdza, czy gracz posiada przedmiot
     *
     * @param int|string $item_id ID przedmiotu
     * @param int $quantity Wymagana ilość
     * @return bool Czy gracz posiada przedmiot
     */
    private function check_has_item($item_id, int $quantity): bool
    {
        $inventory = get_user_meta($this->user_id, 'inventory', true);

        if (!is_array($inventory)) {
            return false;
        }

        foreach ($inventory as $item) {
            if (($item['id'] == $item_id) && ($item['quantity'] >= $quantity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sprawdza, czy gracz ma odpowiedni poziom relacji z NPC
     *
     * @param int|string $npc_id ID NPC
     * @param int $min_value Minimalna wymagana wartość relacji
     * @return bool Czy gracz ma odpowiedni poziom relacji
     */
    private function check_npc_relation($npc_id, int $min_value): bool
    {
        $relations = get_user_meta($this->user_id, 'npc_relations', true);

        if (!is_array($relations) || !isset($relations[$npc_id])) {
            return $min_value <= 0; // Jeśli nie ma relacji, zwracamy true tylko gdy min_value <= 0
        }

        return $relations[$npc_id] >= $min_value;
    }

    /**
     * Sprawdza, czy gracz ma odpowiedni poziom
     *
     * @param int $min_level Minimalny wymagany poziom
     * @return bool Czy gracz ma odpowiedni poziom
     */
    private function check_player_level(int $min_level): bool
    {
        $player_level = (int) get_user_meta($this->user_id, 'player_level', true);

        if (!$player_level) {
            $player_level = 1; // Domyślny poziom
        }

        return $player_level >= $min_level;
    }
}
