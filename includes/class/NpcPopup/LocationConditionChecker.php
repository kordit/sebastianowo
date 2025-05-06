<?php

/**
 * Klasa LocationConditionChecker
 *
 * Sprawdza warunki lokalizacji dla dialogów NPC.
 *
 * @package Game
 * @since 1.0.0
 */

class LocationConditionChecker extends ConditionChecker
{
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

        $this->logger->debug_log("Sprawdzanie warunku lokalizacji:");
        $this->logger->debug_log("- Aktualna lokalizacja: {$location}");
        $this->logger->debug_log("- Operator warunku: {$condition_op}");
        $this->logger->debug_log("- Typ lokalizacji: {$location_type}");

        if ($location_type === 'text') {
            $location_text = $condition['location_text'] ?? '';
            $this->logger->debug_log("- Wymagana lokalizacja: {$location_text}");

            if ($condition_op === 'is') {
                $result = ($location === $location_text);
                $this->logger->debug_log("- Porównanie 'is': " . ($result ? 'PRAWDA' : 'FAŁSZ'));
                return $result;
            } else if ($condition_op === 'is_not') {
                $result = ($location !== $location_text);
                $this->logger->debug_log("- Porównanie 'is_not': " . ($result ? 'PRAWDA' : 'FAŁSZ'));
                return $result;
            } else if ($condition_op === 'contains') {
                $result = (strpos($location, $location_text) !== false);
                $this->logger->debug_log("- Porównanie 'contains': " . ($result ? 'PRAWDA' : 'FAŁSZ'));
                return $result;
            }
        }

        $this->logger->debug_log("- Wynik warunku lokalizacji: FAŁSZ (niepasujący typ lub operator)");
        return false;
    }
}
