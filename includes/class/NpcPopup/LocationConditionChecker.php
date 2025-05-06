<?php

/**
 * Klasa LocationConditionChecker
 *
 * Sprawdza warunki lokalizacji dla dialogów NPC.
 *
 * @package Game
 * @since 1.0.0
 */

class LocationConditionChecker implements ConditionChecker
{
    /**
     * Logger do zapisywania informacji o działaniu
     *
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Konstruktor klasy LocationConditionChecker
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
            if (isset($condition['type']) && $condition['type'] === 'location') {
                $criteria = [
                    'location' => $condition['location'] ?? ''
                ];
                if (!$this->check_condition($condition, $criteria)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Sprawdza warunek lokalizacji
     *
     * @param array $condition Warunek do sprawdzenia
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    public function check_condition(array $condition, array $criteria): bool
    {
        $location = $criteria['location'] ?? '';
        $condition_op = $condition['condition'] ?? 'is';
        $location_type = $condition['location_type'] ?? 'text';

        $this->logger->log("Sprawdzanie warunku lokalizacji: {$location} [{$condition_op}]", 'debug');

        if ($location_type === 'text') {
            $location_text = $condition['location_text'] ?? '';
            $this->logger->log("Wymagana lokalizacja: {$location_text}", 'debug');

            if ($condition_op === 'is') {
                $result = ($location === $location_text);
                $this->logger->log("Porównanie 'is': " . ($result ? 'PRAWDA' : 'FAŁSZ'), 'debug');
                return $result;
            } else if ($condition_op === 'is_not') {
                $result = ($location !== $location_text);
                $this->logger->log("Porównanie 'is_not': " . ($result ? 'PRAWDA' : 'FAŁSZ'), 'debug');
                return $result;
            } else if ($condition_op === 'contains') {
                $result = (strpos($location, $location_text) !== false);
                $this->logger->log("Porównanie 'contains': " . ($result ? 'PRAWDA' : 'FAŁSZ'), 'debug');
                return $result;
            }
        }

        $this->logger->log("Wynik warunku lokalizacji: FAŁSZ (niepasujący typ lub operator)", 'debug');
        return false;
    }
}
