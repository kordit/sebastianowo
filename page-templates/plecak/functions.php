<?php

/**
 * Funkcje pomocnicze dla szablonu plecaka
 */

if (!defined('ABSPATH')) {
    exit; // Zabezpieczenie przed bezpośrednim dostępem
}

/**
 * Funkcja scalająca zduplikowane przedmioty w ekwipunku użytkownika
 * 
 * @param int $user_id ID użytkownika
 * @return array Informacje o scalonych przedmiotach
 */
function merge_duplicate_items($user_id)
{
    // Pobierz przedmioty użytkownika
    $user_items = get_field('items', 'user_' . $user_id);

    if (!$user_items || !is_array($user_items) || count($user_items) <= 1) {
        return [
            'merged' => false,
            'message' => 'Brak przedmiotów do scalenia',
            'count' => 0
        ];
    }

    // Utwórz tablicę do śledzenia przedmiotów według ID
    $item_tracker = [];
    $merged_items = [];
    $duplicate_count = 0;

    // Pierwsza iteracja - identyfikacja duplikatów
    foreach ($user_items as $index => $item_entry) {
        if (!isset($item_entry['item']) || !$item_entry['item'] instanceof WP_Post) {
            continue;
        }

        $item_id = $item_entry['item']->ID;
        $quantity = isset($item_entry['quantity']) ? intval($item_entry['quantity']) : 1;

        if (!isset($item_tracker[$item_id])) {
            // Pierwszy raz widzimy ten przedmiot
            $item_tracker[$item_id] = [
                'indexes' => [$index],
                'total_quantity' => $quantity
            ];
        } else {
            // Znaleziono duplikat
            $item_tracker[$item_id]['indexes'][] = $index;
            $item_tracker[$item_id]['total_quantity'] += $quantity;
            $duplicate_count++;
        }
    }

    // Jeśli nie znaleziono duplikatów, zakończ
    if ($duplicate_count === 0) {
        return [
            'merged' => false,
            'message' => 'Nie znaleziono duplikatów',
            'count' => 0
        ];
    }

    // Druga iteracja - stworzenie nowej tablicy z scalonymi przedmiotami
    foreach ($item_tracker as $item_id => $item_data) {
        if (count($item_data['indexes']) === 1) {
            // Jeśli nie ma duplikatów, po prostu dodaj istniejący element
            $index = $item_data['indexes'][0];
            $merged_items[] = $user_items[$index];
        } else {
            // Mamy duplikaty, bierzemy pierwszy element i aktualizujemy jego ilość
            $first_index = $item_data['indexes'][0];
            $merged_item = $user_items[$first_index];
            $merged_item['quantity'] = $item_data['total_quantity']; // Aktualizacja ilości
            $merged_items[] = $merged_item;
        }
    }

    // Aktualizuj bazę danych z nową scaloną tablicą
    $update_success = update_field('items', $merged_items, 'user_' . $user_id);

    return [
        'merged' => $update_success,
        'message' => $update_success ? "Scalono $duplicate_count zduplikowanych przedmiotów" : 'Wystąpił błąd podczas aktualizacji ekwipunku',
        'count' => $duplicate_count
    ];
}
