<?php

/**
 * Klasa DialogHandler
 *
 * Klasa odpowiedzialna za obsługę endpointu API dla dialogów NPC.
 *
 * @package Game
 * @since 1.0.0
 */

// Załaduj wymagane klasy
require_once get_template_directory() . '/includes/class/NpcPopup/NpcLogger.php';
require_once get_template_directory() . '/includes/class/NpcPopup/ConditionChecker.php';
require_once get_template_directory() . '/includes/class/NpcPopup/ConditionCheckerFactory.php';
require_once get_template_directory() . '/includes/class/NpcPopup/DialogManager.php';
require_once get_template_directory() . '/includes/class/NpcPopup/LocationExtractor.php';

class DialogHandler {}

// Nie dodajemy tutaj funkcji inicjalizującej, ponieważ będziemy ją dodawać w functions.php