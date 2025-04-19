<?php

/**
 * Funkcje do zarządzania przedmiotami gracza
 * 
 * @package Game
 */

if (!defined('ABSPATH')) {
    exit; // Zabezpieczenie przed bezpośrednim dostępem
}

/**
 * Inicjalizacja punktów zaczepienia (hooks) dla funkcji zarządzania przedmiotami
 */
function init_item_management()
{
    add_action('wp_ajax_handle_item_action', 'ajax_handle_item_action');
    add_action('wp_ajax_get_item_name', 'ajax_get_item_name');
}
add_action('init', 'init_item_management');

/**
 * Pobiera nazwę przedmiotu na podstawie ID
 */
function ajax_get_item_name()
{
    // Sprawdzenie czy użytkownik jest zalogowany
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
        return;
    }

    // Sprawdzenie czy przekazano ID przedmiotu
    if (!isset($_POST['item_id']) || empty($_POST['item_id'])) {
        wp_send_json_error(['message' => 'Nie podano ID przedmiotu']);
        return;
    }

    $item_id = intval($_POST['item_id']);
    $item = get_post($item_id);

    if (!$item || $item->post_type !== 'item') {
        wp_send_json_error(['message' => 'Nieprawidłowy przedmiot']);
        return;
    }

    // Zwracamy nazwę przedmiotu
    wp_send_json_success([
        'name' => $item->post_title,
        'id' => $item_id
    ]);
}

/**
 * Obsługuje akcję dodawania lub odbierania przedmiotu użytkownikowi
 */
function ajax_handle_item_action()
{
    // Sprawdzenie czy użytkownik jest zalogowany
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
        return;
    }

    // Sprawdzenie wymaganych parametrów
    if (!isset($_POST['item_id']) || !isset($_POST['quantity']) || !isset($_POST['operation'])) {
        wp_send_json_error(['message' => 'Brakujące parametry: item_id, quantity lub operation']);
        return;
    }

    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    $operation = sanitize_text_field($_POST['operation']);
    $user_id = get_current_user_id();

    // Sprawdzenie czy przedmiot istnieje
    $item = get_post($item_id);
    if (!$item || $item->post_type !== 'item') {
        wp_send_json_error(['message' => 'Nieprawidłowy przedmiot']);
        return;
    }

    // Obsługa operacji
    if ($operation === 'give') {
        $result = give_item_to_user($user_id, $item_id, $quantity);
        if ($result['success']) {
            wp_send_json_success([
                'message' => sprintf('Dodano %d × %s do ekwipunku', $quantity, $item->post_title),
                'item' => $item->post_title,
                'quantity' => $quantity
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    } elseif ($operation === 'take') {
        $result = take_item_from_user($user_id, $item_id, $quantity);
        if ($result['success']) {
            wp_send_json_success([
                'message' => sprintf('Zabrano %d × %s z ekwipunku', $quantity, $item->post_title),
                'item' => $item->post_title,
                'quantity' => $quantity
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    } elseif ($operation === 'equip') {
        // Założenie przedmiotu (oznaczenie jako "equipped" = true)
        $check_result = check_user_has_item($user_id, $item_id);
        if (!$check_result['exists']) {
            wp_send_json_error(['message' => 'Nie posiadasz tego przedmiotu']);
            return;
        }

        $items = get_field('items', 'user_' . $user_id) ?: [];

        // Sprawdź typ przedmiotu, aby wiedzieć, czy ściągnąć inne założone przedmioty tego typu
        $item_type = '';
        $item_terms = get_the_terms($item_id, 'item_type');
        if (!empty($item_terms)) {
            $item_type = $item_terms[0]->term_id; // Pobierz pierwszy typ przedmiotu
        }

        // Jeśli przedmiot ma określony typ, zdejmij wszystkie inne przedmioty tego typu
        if (!empty($item_type)) {
            foreach ($items as $key => $item_data) {
                $curr_item_terms = get_the_terms($item_data['item'], 'item_type');
                $curr_item_type = '';

                if (!empty($curr_item_terms)) {
                    $curr_item_type = $curr_item_terms[0]->term_id;
                }

                // Jeśli to ten sam typ przedmiotu i jest założony, zdejmij go
                if ($curr_item_type === $item_type && isset($item_data['equipped']) && $item_data['equipped']) {
                    $items[$key]['equipped'] = false;
                }
            }
        }

        // Oznacz wybrany przedmiot jako założony
        $items[$check_result['row_index']]['equipped'] = true;

        // Zapisz zmiany
        update_field('items', $items, 'user_' . $user_id);

        wp_send_json_success([
            'message' => sprintf('Przedmiot %s został założony', $item->post_title),
            'item' => $item->post_title,
            'equipped' => true
        ]);
    } elseif ($operation === 'unequip') {
        // Zdjęcie przedmiotu (oznaczenie jako "equipped" = false)
        $check_result = check_user_has_item($user_id, $item_id);
        if (!$check_result['exists']) {
            wp_send_json_error(['message' => 'Nie posiadasz tego przedmiotu']);
            return;
        }

        $items = get_field('items', 'user_' . $user_id) ?: [];

        // Oznacz przedmiot jako niezałożony
        $items[$check_result['row_index']]['equipped'] = false;

        // Zapisz zmiany
        update_field('items', $items, 'user_' . $user_id);

        wp_send_json_success([
            'message' => sprintf('Przedmiot %s został zdjęty', $item->post_title),
            'item' => $item->post_title,
            'equipped' => false
        ]);
    } else {
        wp_send_json_error(['message' => 'Nieprawidłowa operacja. Dozwolone: give, take, equip, unequip']);
    }
}

/**
 * Sprawdza czy użytkownik posiada przedmiot i w jakiej ilości
 * 
 * @param int $user_id ID użytkownika
 * @param int $item_id ID przedmiotu
 * @return array Informacje o ilości przedmiotu (quantity) i czy przedmiot jest w ekwipunku (exists)
 */
function check_user_has_item($user_id, $item_id)
{
    $items = get_field('items', 'user_' . $user_id);

    if (!$items || !is_array($items)) {
        return [
            'exists' => false,
            'quantity' => 0,
            'row_index' => -1,
        ];
    }

    foreach ($items as $index => $item_entry) {
        if (isset($item_entry['item']) && $item_entry['item'] instanceof WP_Post && $item_entry['item']->ID == $item_id) {
            return [
                'exists' => true,
                'quantity' => intval($item_entry['quantity']),
                'row_index' => $index,
            ];
        }
    }

    return [
        'exists' => false,
        'quantity' => 0,
        'row_index' => -1,
    ];
}

/**
 * Dodaje przedmiot do ekwipunku użytkownika
 * 
 * @param int $user_id ID użytkownika
 * @param int $item_id ID przedmiotu
 * @param int $quantity Ilość przedmiotu do dodania
 * @return array Wynik operacji
 */
function give_item_to_user($user_id, $item_id, $quantity)
{
    if ($quantity <= 0) {
        return [
            'success' => false,
            'message' => 'Nieprawidłowa ilość przedmiotu'
        ];
    }

    // Sprawdź czy użytkownik już ma ten przedmiot
    $check_result = check_user_has_item($user_id, $item_id);
    $items = get_field('items', 'user_' . $user_id) ?: [];

    if ($check_result['exists']) {
        // Aktualizacja istniejącego przedmiotu
        $items[$check_result['row_index']]['quantity'] = $check_result['quantity'] + $quantity;
    } else {
        // Dodanie nowego przedmiotu do ekwipunku
        $items[] = [
            'item' => $item_id,  // ACF automatycznie konwertuje to na obiekt posta
            'quantity' => $quantity,
            'equipped' => false
        ];
    }

    // Aktualizuj pole ACF użytkownika
    update_field('items', $items, 'user_' . $user_id);

    return [
        'success' => true,
        'message' => 'Przedmiot dodany pomyślnie'
    ];
}

/**
 * Odbiera przedmiot z ekwipunku użytkownika
 * 
 * @param int $user_id ID użytkownika
 * @param int $item_id ID przedmiotu
 * @param int $quantity Ilość przedmiotu do odebrania
 * @return array Wynik operacji
 */
function take_item_from_user($user_id, $item_id, $quantity)
{
    if ($quantity <= 0) {
        return [
            'success' => false,
            'message' => 'Nieprawidłowa ilość przedmiotu'
        ];
    }

    // Sprawdź czy użytkownik ma ten przedmiot
    $check_result = check_user_has_item($user_id, $item_id);

    if (!$check_result['exists']) {
        return [
            'success' => false,
            'message' => 'Użytkownik nie posiada tego przedmiotu'
        ];
    }

    if ($check_result['quantity'] < $quantity) {
        return [
            'success' => false,
            'message' => 'Użytkownik nie posiada wystarczającej ilości tego przedmiotu'
        ];
    }

    // Pobierz aktualną listę przedmiotów
    $items = get_field('items', 'user_' . $user_id) ?: [];

    // Aktualizuj ilość lub usuń przedmiot jeśli ilość wyniesie 0
    if ($check_result['quantity'] > $quantity) {
        // Zmniejsz ilość
        $items[$check_result['row_index']]['quantity'] = $check_result['quantity'] - $quantity;
    } else {
        // Usuń przedmiot z tablicy
        array_splice($items, $check_result['row_index'], 1);
    }

    // Aktualizuj pole ACF użytkownika
    update_field('items', $items, 'user_' . $user_id);

    return [
        'success' => true,
        'message' => 'Przedmiot odebrany pomyślnie'
    ];
}

/**
 * Sprawdza czy użytkownik ma wystarczającą ilość przedmiotu (funkcja pomocnicza do walidacji)
 */
function user_has_enough_items($user_id, $item_id, $required_quantity)
{
    $check_result = check_user_has_item($user_id, $item_id);

    return $check_result['exists'] && $check_result['quantity'] >= $required_quantity;
}
