<?php

/**
 * Klasa InventoryConditionChecker
 *
 * Sprawdza warunki ekwipunku dla dialogów NPC.
 *
 * @package Game
 * @since 1.0.0
 */

class InventoryConditionChecker extends ConditionChecker
{
    /**
     * Sprawdza warunek posiadania przedmiotu w ekwipunku
     *
     * @param array $condition Warunek ekwipunku
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    public function check_condition(array $condition, array $criteria): bool
    {
        $user_id = $criteria['user_id'] ?? 0;
        $condition_op = $condition['condition'] ?? '';
        $item_id = isset($condition['item_id']) ? absint($condition['item_id']) : 0;
        $quantity = isset($condition['quantity']) ? absint($condition['quantity']) : 1;

        $this->logger->debug_log("Sprawdzanie warunku ekwipunku:");
        $this->logger->debug_log("- User ID: {$user_id}");
        $this->logger->debug_log("- Operator warunku: {$condition_op}");
        $this->logger->debug_log("- ID przedmiotu: {$item_id}");
        $this->logger->debug_log("- Wymagana ilość: {$quantity}");

        // Jeśli użytkownik nie jest zalogowany lub brak ID przedmiotu, warunek nie jest spełniony
        if (!$user_id || !$item_id) {
            $this->logger->debug_log("- Brak User ID lub Item ID - warunek niespełniony");
            return false;
        }

        // Pobierz ekwipunek użytkownika
        $user_inventory = $this->get_user_inventory($user_id);
        $this->logger->debug_log("- Ekwipunek użytkownika:", $user_inventory);

        // Oblicz aktualną ilość przedmiotu w ekwipunku użytkownika
        $current_quantity = 0;
        if (!empty($user_inventory)) {
            foreach ($user_inventory as $inventory_item) {
                if ($inventory_item['item_id'] == $item_id) {
                    $current_quantity += intval($inventory_item['quantity']);
                }
            }
        }
        $this->logger->debug_log("- Aktualna ilość przedmiotu {$item_id}: {$current_quantity}");

        // Sprawdź warunek ekwipunku w zależności od operatora
        switch ($condition_op) {
            case 'has_item':
                // Sprawdź czy użytkownik posiada przedmiot w wymaganej ilości
                $result = ($current_quantity >= $quantity);
                $this->logger->debug_log("- Warunek 'has_item' (ma {$current_quantity} >= {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'not_has_item':
            case 'has_not_item': // Obsługuj oba warianty nazwy
                // Sprawdź czy użytkownik nie posiada przedmiotu lub ma mniej niż wymagane
                $result = ($current_quantity < $quantity);
                $this->logger->debug_log("- Warunek '{$condition_op}' (ma {$current_quantity} < {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'quantity_above':
                // Sprawdź czy użytkownik ma więcej niż określona ilość przedmiotu
                $result = ($current_quantity > $quantity);
                $this->logger->debug_log("- Warunek 'quantity_above' (ma {$current_quantity} > {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'quantity_below':
                // Sprawdź czy użytkownik ma mniej niż określona ilość przedmiotu
                $result = ($current_quantity < $quantity);
                $this->logger->debug_log("- Warunek 'quantity_below' (ma {$current_quantity} < {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'quantity_equal':
                // Sprawdź czy użytkownik ma dokładnie określoną ilość przedmiotu
                $result = ($current_quantity == $quantity);
                $this->logger->debug_log("- Warunek 'quantity_equal' (ma {$current_quantity} == {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            default:
                $this->logger->debug_log("- Nieznany operator warunku ekwipunku: {$condition_op}");
                return false;
        }
    }

    /**
     * Pobiera ekwipunek użytkownika
     *
     * @param int $user_id ID użytkownika
     * @return array Tablica przedmiotów w ekwipunku
     */
    private function get_user_inventory(int $user_id): array
    {
        if (!$user_id) {
            return [];
        }

        // Pobierz przedmioty z pola ACF 'items'
        $items_field = get_field('items', 'user_' . $user_id);
        $this->logger->debug_log("Pobieranie ekwipunku dla użytkownika {$user_id}");

        // Jeśli pole items nie istnieje lub jest puste, zwróć pustą tablicę
        if (!$items_field || !is_array($items_field) || empty($items_field)) {
            $this->logger->debug_log("Brak przedmiotów w ekwipunku lub pole nieznalezione");
            return [];
        }

        // Przygotuj tablicę przedmiotów w formacie do sprawdzania warunków
        $inventory = [];
        foreach ($items_field as $item) {
            if (isset($item['item']) && isset($item['quantity'])) {
                $inventory[] = [
                    'item_id' => (int)$item['item'],
                    'quantity' => (int)$item['quantity']
                ];
            }
        }

        $this->logger->debug_log("Znaleziono " . count($inventory) . " przedmiotów w ekwipunku");
        return $inventory;
    }
}
