<?php

/**
 * Abstrakcyjna klasa ConditionChecker
 *
 * Bazowa klasa dla wszystkich typów sprawdzaczy warunków NPC.
 *
 * @package Game
 * @since 1.0.0
 */

abstract class ConditionChecker
{
    /**
     * Logger do zapisywania informacji debugowania
     * 
     * @var NpcLogger
     */
    protected NpcLogger $logger;

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
     * Sprawdza czy warunek jest spełniony
     *
     * @param array $condition Warunek do sprawdzenia
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    abstract public function check_condition(array $condition, array $criteria): bool;
}
