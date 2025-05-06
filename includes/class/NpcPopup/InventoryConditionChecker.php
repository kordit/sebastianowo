<?php

/**
 * Klasa InventoryConditionChecker
 *
 * Sprawdza warunki ekwipunku dla dialogów NPC.
 *
 * @package Game
 * @since 1.0.0
 */

class InventoryConditionChecker implements ConditionChecker
{
    /**
     * Logger do zapisywania informacji o działaniu
     *
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Konstruktor klasy InventoryConditionChecker
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
        foreach ($conditions as $condition) {
            if (isset($condition['type']) && $condition['type'] === 'inventory') {
                $criteria = [
                    'user_id' => $condition['user_id'] ?? get_current_user_id()
                ];
                if (!$this->check_condition($condition, $criteria)) {
                    return false;
                }
            }
        }
        
        return true;
    }

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

        $this->logger->log("Sprawdzanie warunku ekwipunku:", 'debug');
        $this->logger->log("- User ID: {$user_id}", 'debug');
        $this->logger->log("- Operator warunku: {$condition_op}", 'debug');
        $this->logger->log("- ID przedmiotu: {$item_id}", 'debug');
        $this->logger->log("- Wymagana ilość: {$quantity}", 'debug');

        // Jeśli użytkownik nie jest zalogowany lub brak ID przedmiotu, warunek nie jest spełniony
        if (!$user_id || !$item_id) {
            $this->logger->log("- Brak User ID lub Item ID - warunek niespełniony", 'debug');
            return false;
        }

        // Pobierz ekwipunek użytkownika
        $user_inventory = $this->get_user_inventory($user_id);
        $this->logger->log("- Pobrano ekwipunek użytkownika", 'debug');

        // Oblicz aktualną ilość przedmiotu w ekwipunku użytkownika
        $current_quantity = 0;
        if (!empty($user_inventory)) {
            foreach ($user_inventory as $inventory_item) {
                if ($inventory_item['item_id'] == $item_id) {
                    $current_quantity += intval($inventory_item['quantity']);
                }
            }
        }
        $this->logger->log("- Aktualna ilość przedmiotu {$item_id}: {$current_quantity}", 'debug');

        // Sprawdź warunek ekwipunku w zależności od operatora
        switch ($condition_op) {
            case 'has_item':
                // Sprawdź czy użytkownik posiada przedmiot w wymaganej ilości
                $result = ($current_quantity >= $quantity);
                $this->logger->log("- Warunek 'has_item' (ma {$current_quantity} >= {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                return $result;

            case 'not_has_item':
            case 'has_not_item': // Obsługuj oba warianty nazwy
                // Sprawdź czy użytkownik nie posiada przedmiotu lub ma mniej niż wymagane
                $result = ($current_quantity < $quantity);
                $this->logger->log("- Warunek '{$condition_op}' (ma {$current_quantity} < {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                return $result;

            case 'quantity_above':
                // Sprawdź czy użytkownik ma więcej niż określona ilość przedmiotu
                $result = ($current_quantity > $quantity);
                $this->logger->log("- Warunek 'quantity_above' (ma {$current_quantity} > {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                return $result;

            case 'quantity_below':
                // Sprawdź czy użytkownik ma mniej niż określona ilość przedmiotu
                $result = ($current_quantity < $quantity);
                $this->logger->log("- Warunek 'quantity_below' (ma {$current_quantity} < {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                return $result;

            case 'quantity_equal':
                // Sprawdź czy użytkownik ma dokładnie określoną ilość przedmiotu
                $result = ($current_quantity == $quantity);
                $this->logger->log("- Warunek 'quantity_equal' (ma {$current_quantity} == {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'), 'debug');
                return $result;

            default:
                $this->logger->log("- Nieznany operator warunku ekwipunku: {$condition_op}", 'debug');
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
        $this->logger->log("Pobieranie ekwipunku dla użytkownika {$user_id}", 'debug');

        // Jeśli pole items nie istnieje lub jest puste, zwróć pustą tablicę
        if (!$items_field || !is_array($items_field) || empty($items_field)) {
            $this->logger->log("Brak przedmiotów w ekwipunku lub pole nieznalezione", 'debug');
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

        $this->logger->log("Znaleziono " . count($inventory) . " przedmiotów w ekwipunku", 'debug');
        return $inventory;
    }
}
