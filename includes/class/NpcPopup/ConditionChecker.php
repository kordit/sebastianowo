<?php

/**
 * Interfejs ConditionChecker
 * 
 * Definiuje metody dla klas sprawdzających warunki dialogów NPC.
 * 
 * @package Game
 * @subpackage NpcPopup
 * @since 1.0.0
 */
interface ConditionChecker
{
    /**
     * Sprawdza, czy określone warunki są spełnione
     *
     * @param array $conditions Warunki do sprawdzenia
     * @return bool Czy warunki są spełnione
     */
    public function check_conditions(array $conditions): bool;
}
