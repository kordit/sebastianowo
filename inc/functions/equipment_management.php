<?php
if (!defined('ABSPATH')) {
    exit; // Zabezpieczenie przed bezpośrednim dostępem
}

/**
 * Inicjalizacja punktów zaczepienia (hooks) dla funkcji zarządzania ekwipunkiem
 */
function init_equipment_management()
{
    add_action('wp_ajax_handle_equipment_action', 'ajax_handle_equipment_action');
}
add_action('init', 'init_equipment_management');

/**
 * Obsługuje akcję zakładania lub zdejmowania przedmiotu
 */
function ajax_handle_equipment_action()
{
    // Sprawdzenie czy użytkownik jest zalogowany
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Użytkownik nie jest zalogowany']);
        return;
    }

    // Sprawdzenie wymaganych parametrów
    if (!isset($_POST['item_id']) || !isset($_POST['slot']) || !isset($_POST['action'])) {
        wp_send_json_error(['message' => 'Brakujące parametry: item_id, slot lub action']);
        return;
    }

    $item_id = intval($_POST['item_id']);
    $slot = sanitize_text_field($_POST['slot']); // chest_item, bottom_item lub legs_item
    $action = sanitize_text_field($_POST['equipment_action']); // equip lub unequip
    $user_id = get_current_user_id();

    // Sprawdzenie czy przedmiot istnieje
    $item = get_post($item_id);
    if (!$item || $item->post_type !== 'item') {
        wp_send_json_error(['message' => 'Nieprawidłowy przedmiot']);
        return;
    }

    // Pobierz grupę założonych przedmiotów
    $equipped_items = get_field('equipped_items', 'user_' . $user_id) ?: [];

    // Sprawdzamy czy chodzi o zdejmowanie czy zakładanie przedmiotu
    if ($action === 'equip') {
        // Przy zakładaniu sprawdzamy czy przedmiot jest w ekwipunku
        $check_result = check_user_has_item($user_id, $item_id);
        if (!$check_result['exists']) {
            wp_send_json_error(['message' => 'Nie posiadasz tego przedmiotu']);
            return;
        }
    } elseif ($action === 'unequip') {
        // Przy zdejmowaniu sprawdzamy czy jakikolwiek przedmiot jest założony w danym slocie
        if (empty($equipped_items[$slot])) {
            wp_send_json_error(['message' => 'Nie ma założonego przedmiotu w tym miejscu']);
            return;
        }

        // Używamy ID faktycznie założonego przedmiotu zamiast ID z requesta
        $item_id = $equipped_items[$slot];
    }    // Obsługa operacji
    if ($action === 'equip') {
        // Sprawdź czy przedmiot pasuje do wybranego slotu (tylko przy zakładaniu)
        if (!can_equip_item_to_slot($item_id, $slot)) {
            wp_send_json_error(['message' => 'Ten przedmiot nie może być założony w tym miejscu']);
            return;
        }

        // Sprawdź, czy masz już coś założonego w tym slocie - jeśli tak, zdejmij to najpierw
        $old_item_id = null;
        $old_item_name = null;

        if (!empty($equipped_items[$slot])) {
            $old_item_id = $equipped_items[$slot];
            $old_item = get_post($old_item_id);
            $old_item_name = $old_item ? $old_item->post_title : 'przedmiot';

            // Dodaj stary przedmiot z powrotem do ekwipunku użytkownika
            $items = get_field('items', 'user_' . $user_id) ?: [];

            // Dodajemy stary przedmiot bezpośrednio do repeatera
            $items[] = [
                'item' => $old_item_id,  // ACF automatycznie konwertuje to na obiekt posta
                'quantity' => 1,
                'equipped' => false
            ];

            // Aktualizuj pole ACF użytkownika - dodajemy stary przedmiot do ekwipunku
            $update_success = update_field('items', $items, 'user_' . $user_id);

            if (!$update_success) {
                wp_send_json_error(['message' => 'Nie udało się dodać zdjętego przedmiotu do ekwipunku']);
                return;
            }
        }

        // Odejmij przedmiot z ekwipunku użytkownika
        $result = take_item_from_user($user_id, $item_id, 1);

        if (!$result['success']) {
            wp_send_json_error(['message' => $result['message']]);
            return;
        }

        // Zakładanie przedmiotu
        $equipped_items[$slot] = $item_id;
        update_field('equipped_items', $equipped_items, 'user_' . $user_id);

        $message = sprintf('Przedmiot %s został założony', $item->post_title);
        if ($old_item_id) {
            $message = sprintf('Zamieniono przedmiot %s na %s', $old_item_name, $item->post_title);
        }

        wp_send_json_success([
            'message' => $message,
            'item' => $item->post_title,
            'old_item_id' => $old_item_id,
            'old_item_name' => $old_item_name,
            'slot' => $slot,
            'equipped' => true,
            'replaced' => (bool)$old_item_id
        ]);
    } elseif ($action === 'unequip') {
        // Zdejmowanie przedmiotu
        $old_item_id = $equipped_items[$slot];

        // Usuń przedmiot ze slotu
        $equipped_items[$slot] = '';
        update_field('equipped_items', $equipped_items, 'user_' . $user_id);

        // Dodaj przedmiot z powrotem do ekwipunku użytkownika
        // Ignorujemy wynik sprawdzenia czy przedmiot istnieje, zawsze tworzymy nowy wpis jeśli trzeba
        $items = get_field('items', 'user_' . $user_id) ?: [];

        // Dodajemy przedmiot bezpośrednio do repeatera
        $items[] = [
            'item' => $item_id,  // ACF automatycznie konwertuje to na obiekt posta
            'quantity' => 1,
            'equipped' => false
        ];

        // Aktualizuj pole ACF użytkownika
        $update_success = update_field('items', $items, 'user_' . $user_id);

        // Jeśli aktualizacja się nie powiodła, przywróć poprzedni stan
        if (!$update_success) {
            $equipped_items[$slot] = $old_item_id;
            update_field('equipped_items', $equipped_items, 'user_' . $user_id);

            wp_send_json_error(['message' => 'Nie udało się dodać przedmiotu do ekwipunku']);
            return;
        }

        wp_send_json_success([
            'message' => sprintf('Przedmiot %s został zdjęty', $item->post_title),
            'item' => $item->post_title,
            'slot' => $slot,
            'equipped' => false
        ]);
    } else {
        wp_send_json_error(['message' => 'Nieprawidłowa operacja. Dozwolone: equip, unequip']);
    }
}

/**
 * Sprawdza czy przedmiot może być założony w danym slocie
 * 
 * @param int $item_id ID przedmiotu
 * @param string $slot Nazwa slotu (chest_item, bottom_item, legs_item)
 * @return bool Czy przedmiot może być założony w danym slocie
 */
function can_equip_item_to_slot($item_id, $slot)
{
    // Pobierz kategorie przedmiotu
    $item_terms = wp_get_post_terms($item_id, 'item_type', ['fields' => 'ids']);

    // Jeśli nie ma kategorii, przedmiot nie może być założony
    if (empty($item_terms)) {
        return false;
    }

    // Sprawdź czy przedmiot pasuje do slotu na podstawie kategorii
    switch ($slot) {
        case 'chest_item':
            // Przedmioty na klatę (ID kategorii: 3)
            return in_array(3, $item_terms);

        case 'bottom_item':
            // Przedmioty na poślady (ID kategorii: 4)
            return in_array(4, $item_terms);

        case 'legs_item':
            // Przedmioty na giczuły (ID kategorii: 7)
            return in_array(7, $item_terms);

        default:
            return false;
    }
}

/**
 * Pobiera listę założonych przedmiotów dla użytkownika
 * 
 * @param int $user_id ID użytkownika
 * @return array Lista założonych przedmiotów według slotów
 */
function get_user_equipped_items($user_id)
{
    $equipped_items = get_field('equipped_items', 'user_' . $user_id) ?: [];

    // Upewnij się, że mamy wszystkie potrzebne sloty
    $result = [
        'chest_item' => null,
        'bottom_item' => null,
        'legs_item' => null
    ];

    // Pobierz obiekty przedmiotów dla wypełnionych slotów
    foreach ($result as $slot => $value) {
        if (isset($equipped_items[$slot]) && !empty($equipped_items[$slot])) {
            $result[$slot] = get_post($equipped_items[$slot]);
        }
    }

    return $result;
}

/**
 * Generuje HTML dla listy założonych przedmiotów
 * 
 * @param int $user_id ID użytkownika
 * @return string HTML dla listy założonych przedmiotów
 */
function generate_equipped_items_html($user_id)
{
    $equipped_items = get_user_equipped_items($user_id);
    $output = '<div class="equipped-items-selected">';

    // Definicja slotów z opisami
    $slots = [
        'chest_item' => 'Na klatę',
        'bottom_item' => 'Na poślady',
        'legs_item' => 'Na giczuły'
    ];

    foreach ($slots as $slot_key => $slot_name) {
        $output .= '<div class="equipment-slot" data-slot="' . $slot_key . '">';
        $output .= '<h4 class="slot-name">' . $slot_name . '</h4>';

        if ($equipped_items[$slot_key]) {
            // Jeśli jest założony przedmiot w tym slocie, wygeneruj jego kartę
            $output .= generate_item_card($equipped_items[$slot_key], 1, true, $slot_key);
        } else {
            // Jeśli slot jest pusty, dodaj placeholder
            $output .= '<div class="empty-slot">';
            $output .= '<div class="empty-slot-icon"></div>';
            $output .= '<p>Przejdź do zakładki przedmioty, by założyć przedmiot</p>';
            $output .= '</div>';
        }

        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
}
