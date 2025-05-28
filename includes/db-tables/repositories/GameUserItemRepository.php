<?php

/**
 * Repozytorium dla tabeli game_user_items
 * Obsługuje wszystkie operacje CRUD na przedmiotach użytkowników
 */
class GameUserItemRepository
{
    private $wpdb;
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'game_user_items';
    }

    /**
     * Pobiera wszystkie przedmioty użytkownika
     */
    public function getUserItems($user_id)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT gui.*, p.post_title as item_name 
             FROM {$this->table_name} gui 
             LEFT JOIN {$this->wpdb->posts} p ON gui.item_id = p.ID 
             WHERE gui.user_id = %d 
             ORDER BY p.post_title",
            $user_id
        ), ARRAY_A);
    }

    /**
     * Pobiera konkretny przedmiot użytkownika
     */
    public function getUserItem($user_id, $item_id)
    {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND item_id = %d",
            $user_id,
            $item_id
        ), ARRAY_A);
    }

    /**
     * Dodaje przedmiot do ekwipunku lub zwiększa ilość
     */
    public function addItem($user_id, $item_id, $amount = 1, $is_equipped = 0, $slot = '')
    {
        // Sprawdź czy przedmiot już istnieje
        $existing = $this->getUserItem($user_id, $item_id);

        if ($existing) {
            // Zwiększ ilość
            return $this->updateItemAmount($user_id, $item_id, $existing['amount'] + $amount);
        } else {
            // Dodaj nowy przedmiot
            $result = $this->wpdb->insert($this->table_name, [
                'user_id' => $user_id,
                'item_id' => $item_id,
                'amount' => $amount,
                'is_equipped' => $is_equipped,
                'slot' => $slot
            ]);

            return $result !== false ? $this->wpdb->insert_id : false;
        }
    }

    /**
     * Usuwa przedmiot lub zmniejsza ilość
     */
    public function removeItem($user_id, $item_id, $amount = 1)
    {
        $existing = $this->getUserItem($user_id, $item_id);

        if (!$existing) {
            return false;
        }

        $new_amount = $existing['amount'] - $amount;

        if ($new_amount <= 0) {
            // Usuń całkowicie
            return $this->wpdb->delete($this->table_name, [
                'user_id' => $user_id,
                'item_id' => $item_id
            ]);
        } else {
            // Zmniejsz ilość
            return $this->updateItemAmount($user_id, $item_id, $new_amount);
        }
    }

    /**
     * Ustawia konkretną ilość przedmiotu
     */
    public function setItemAmount($user_id, $item_id, $amount)
    {
        if ($amount <= 0) {
            // Usuń przedmiot
            return $this->wpdb->delete($this->table_name, [
                'user_id' => $user_id,
                'item_id' => $item_id
            ]);
        }

        $existing = $this->getUserItem($user_id, $item_id);

        if ($existing) {
            // Aktualizuj ilość
            return $this->updateItemAmount($user_id, $item_id, $amount);
        } else {
            // Dodaj nowy przedmiot
            return $this->addItem($user_id, $item_id, $amount);
        }
    }

    /**
     * Aktualizuje ilość przedmiotu
     */
    private function updateItemAmount($user_id, $item_id, $amount)
    {
        return $this->wpdb->update(
            $this->table_name,
            ['amount' => $amount],
            ['user_id' => $user_id, 'item_id' => $item_id]
        );
    }

    /**
     * Zmienia status wyposażenia przedmiotu
     */
    public function setEquipped($user_id, $item_id, $is_equipped, $slot = '')
    {
        return $this->wpdb->update(
            $this->table_name,
            [
                'is_equipped' => $is_equipped,
                'slot' => $slot
            ],
            ['user_id' => $user_id, 'item_id' => $item_id]
        );
    }

    /**
     * Pobiera wszystkie dostępne przedmioty z post_type='item'
     */
    public function getAllAvailableItems()
    {
        return get_posts([
            'post_type' => 'item',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
    }

    /**
     * Usuwa wszystkie przedmioty użytkownika
     */
    public function deleteUserItems($user_id)
    {
        return $this->wpdb->delete($this->table_name, ['user_id' => $user_id]);
    }

    /**
     * Pobiera statystyki przedmiotów dla użytkownika
     */
    public function getUserItemStats($user_id)
    {
        $total_items = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));

        $total_amount = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(amount) FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));

        $equipped_items = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND is_equipped = 1",
            $user_id
        ));

        return [
            'unique_items' => (int) $total_items,
            'total_amount' => (int) $total_amount,
            'equipped_items' => (int) $equipped_items
        ];
    }
}
