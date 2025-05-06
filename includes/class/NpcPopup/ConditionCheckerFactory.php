<?php

/**
 * Klasa ConditionCheckerFactory
 *
 * Odpowiada za tworzenie instancji sprawdzaczy warunków.
 *
 * @package Game
 * @since 1.0.0
 */

class ConditionCheckerFactory
{
    /**
     * Logger do zapisywania informacji debugowania
     * 
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Przechowuje już utworzone instancje sprawdzaczy
     * 
     * @var array
     */
    private array $checkers = [];

    /**
     * Konstruktor klasy
     * 
     * @param NpcLogger $logger Logger do zapisywania informacji debugowania
     */
    public function __construct(NpcLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Zwraca odpowiedni sprawdzacz warunków na podstawie typu warunku
     * 
     * @param string $conditionType Typ warunku do sprawdzenia
     * @return ConditionChecker|null Instancja sprawdzacza warunków lub null, jeśli nie znaleziono
     */
    public function get_checker(string $conditionType): ?ConditionChecker
    {
        // Jeśli sprawdzacz już istnieje w cache, zwróć go
        if (isset($this->checkers[$conditionType])) {
            return $this->checkers[$conditionType];
        }

        // Pobierz klasę na podstawie typu warunku
        $checker = null;

        switch ($conditionType) {
            case 'condition_location':
                $checker = new LocationConditionChecker($this->logger);
                break;
            case 'condition_npc_relation':
                $checker = new RelationConditionChecker($this->logger);
                break;
            case 'condition_inventory':
                $checker = new InventoryConditionChecker($this->logger);
                break;
            case 'condition_mission':
                $checker = new MissionConditionChecker($this->logger);
                break;
            case 'condition_task':
                $checker = new TaskConditionChecker($this->logger);
                break;
        }

        // Zapisz sprawdzacz w cache
        if ($checker !== null) {
            $this->checkers[$conditionType] = $checker;
        }

        return $checker;
    }
}
