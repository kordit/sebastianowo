<?php

/**
 * Funkcje pomocnicze dla systemu bazy danych gry
 * 
 * Globalne funkcje do łatwego dostępu do danych gracza
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pobiera model danych aktualnego użytkownika
 * 
 * @param int|null $user_id ID użytkownika, null = aktualny użytkownik
 * @return GameUserModel|null
 */
function get_game_user($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return null;
    }

    return new GameUserModel($user_id);
}

/**
 * Pobiera statystykę aktualnego gracza
 * 
 * @param string $stat_name Nazwa statystyki (strength, defense, etc.)
 * @param int|null $user_id ID użytkownika
 * @return int
 */
function get_game_stat($stat_name, $user_id = null)
{
    $game_user = get_game_user($user_id);
    return $game_user ? $game_user->get_stat($stat_name) : 0;
}

/**
 * Pobiera umiejętność aktualnego gracza
 * 
 * @param string $skill_name Nazwa umiejętności (combat, steal, etc.)
 * @param int|null $user_id ID użytkownika
 * @return int
 */
function get_game_skill($skill_name, $user_id = null)
{
    $game_user = get_game_user($user_id);
    return $game_user ? $game_user->get_skill($skill_name) : 0;
}

/**
 * Pobiera podstawowe dane gracza
 * 
 * @param int|null $user_id ID użytkownika
 * @return array|null
 */
function get_game_user_data($user_id = null)
{
    $game_user = get_game_user($user_id);
    return $game_user ? $game_user->get_basic_data() : null;
}

/**
 * Sprawdza czy gracz ma dany przedmiot
 * 
 * @param int $item_id ID przedmiotu
 * @param int $quantity Wymagana ilość
 * @param int|null $user_id ID użytkownika
 * @return bool
 */
function game_user_has_item($item_id, $quantity = 1, $user_id = null)
{
    $game_user = get_game_user($user_id);
    if (!$game_user) {
        return false;
    }

    $items = $game_user->get_items_data();
    foreach ($items as $item) {
        if (intval($item['item_id']) === intval($item_id)) {
            return intval($item['quantity']) >= $quantity;
        }
    }

    return false;
}

/**
 * Sprawdza relację z NPC
 * 
 * @param int $npc_id ID NPC
 * @param int|null $user_id ID użytkownika
 * @return array|null
 */
function get_game_npc_relation($npc_id, $user_id = null)
{
    $game_user = get_game_user($user_id);
    if (!$game_user) {
        return null;
    }

    $relations = $game_user->get_relations_data();
    foreach ($relations as $relation) {
        if (intval($relation['npc_id']) === intval($npc_id)) {
            return $relation;
        }
    }

    return null;
}

/**
 * Sprawdza czy gracz poznał NPC
 * 
 * @param int $npc_id ID NPC
 * @param int|null $user_id ID użytkownika
 * @return bool
 */
function game_user_knows_npc($npc_id, $user_id = null)
{
    $relation = get_game_npc_relation($npc_id, $user_id);
    return $relation && $relation['is_known'];
}

/**
 * Sprawdza czy gracz ma dostęp do obszaru
 * 
 * @param int $area_id ID obszaru
 * @param int|null $user_id ID użytkownika
 * @return bool
 */
function game_user_has_area_access($area_id, $user_id = null)
{
    $game_user = get_game_user($user_id);
    if (!$game_user) {
        return false;
    }

    $areas = $game_user->get_areas_data();
    foreach ($areas as $area) {
        if (intval($area['area_id']) === intval($area_id)) {
            return true;
        }
    }

    return false;
}

/**
 * Aktualizuje statystykę gracza
 * 
 * @param string $stat_name Nazwa statystyki
 * @param int $value Nowa wartość
 * @param int|null $user_id ID użytkownika
 * @return bool
 */
function update_game_stat($stat_name, $value, $user_id = null)
{
    $game_user = get_game_user($user_id);
    if (!$game_user) {
        return false;
    }

    return $game_user->update_basic_data([$stat_name => intval($value)]) !== false;
}

/**
 * Aktualizuje umiejętność gracza
 * 
 * @param string $skill_name Nazwa umiejętności
 * @param int $value Nowa wartość
 * @param int|null $user_id ID użytkownika
 * @return bool
 */
function update_game_skill($skill_name, $value, $user_id = null)
{
    $game_user = get_game_user($user_id);
    if (!$game_user) {
        return false;
    }

    return $game_user->update_skills_data([$skill_name => intval($value)]) !== false;
}

/**
 * Pobiera dane plecaka aktualnego gracza (waluty)
 * 
 * @param int|null $user_id ID użytkownika
 * @return array Tablica z walutami
 */
function get_game_backpack($user_id = null)
{
    $game_user = get_game_user($user_id);
    if (!$game_user) {
        return ['gold' => 0, 'cigarettes' => 0];
    }

    $basic_data = $game_user->get_basic_data();
    return [
        'gold' => $basic_data['gold'] ?? 0,
        'cigarettes' => $basic_data['cigarettes'] ?? 0
    ];
}

/**
 * Pobiera konkretną walutę użytkownika
 * 
 * @param string $currency_name Nazwa waluty (gold, cigarettes)
 * @param int|null $user_id ID użytkownika
 * @return int
 */
function get_game_currency($currency_name, $user_id = null)
{
    $backpack = get_game_backpack($user_id);
    return $backpack[$currency_name] ?? 0;
}

/**
 * Aktualizuje walutę użytkownika
 * 
 * @param string $currency_name Nazwa waluty
 * @param int $value Nowa wartość
 * @param int|null $user_id ID użytkownika
 * @return bool
 */
function update_game_currency($currency_name, $value, $user_id = null)
{
    $game_user = get_game_user($user_id);
    if (!$game_user) {
        return false;
    }

    $update_data = [$currency_name => $value];
    return $game_user->update_basic_data($update_data);
}

/**
 * Dodaje lub odejmuje walutę użytkownika
 * 
 * @param string $currency_name Nazwa waluty
 * @param int $amount Wartość do dodania (ujemna wartość = odejmij)
 * @param int|null $user_id ID użytkownika
 * @return bool
 */
function modify_game_currency($currency_name, $amount, $user_id = null)
{
    $current_value = get_game_currency($currency_name, $user_id);
    $new_value = max(0, $current_value + $amount); // Nie pozwalamy na ujemne wartości
    return update_game_currency($currency_name, $new_value, $user_id);
}

/**
 * Pobiera klasę użytkownika w formacie zgodnym z ACF
 * 
 * @param int|null $user_id ID użytkownika
 * @return array|string
 */
function get_game_user_class($user_id = null)
{
    $game_user = get_game_user($user_id);
    if (!$game_user) {
        return '';
    }

    $basic_data = $game_user->get_basic_data();
    return $basic_data['user_class'] ?? '';
}
