<?php

/**
 * Klasa LootboxHandler
 * 
 * Obsługuje system lootboxów (śmietników, kufrów itp.)
 */
class LootboxHandler
{
    /**
     * Inicjalizuje klasę i rejestruje endpointy REST API
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);

        // Dodaj pole do śledzenia przeszukanych lootboxów
        add_action('init', [$this, 'register_user_fields']);
    }

    /**
     * Rejestruje pole ACF dla użytkowników do śledzenia przeszukanych lootboxów
     */
    public function register_user_fields()
    {
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group(array(
                'key' => 'group_searched_lootboxes',
                'title' => 'Przeszukane lootboxy',
                'fields' => array(
                    array(
                        'key' => 'field_searched_lootboxes',
                        'label' => 'ID przeszukanych lootboxów',
                        'name' => 'searched_lootboxes',
                        'type' => 'repeater',
                        'layout' => 'table',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_lootbox_id',
                                'label' => 'ID lootboxa',
                                'name' => 'lootbox_id',
                                'type' => 'number',
                            ),
                            array(
                                'key' => 'field_search_date',
                                'label' => 'Data przeszukania',
                                'name' => 'search_date',
                                'type' => 'date_time_picker',
                            ),
                        ),
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'user_form',
                            'operator' => '==',
                            'value' => 'all',
                        ),
                    ),
                ),
                'hide_on_screen' => array(),
            ));
        }
    }

    /**
     * Rejestruje endpointy REST API
     */
    public function register_endpoints()
    {
        register_rest_route('game/v1', '/lootbox/popup', array(
            'methods' => 'POST',
            'callback' => [$this, 'get_lootbox_popup'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ));

        register_rest_route('game/v1', '/lootbox/search', array(
            'methods' => 'POST',
            'callback' => [$this, 'search_lootbox'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ));

        register_rest_route('game/v1', '/lootbox/reset', array(
            'methods' => 'POST',
            'callback' => [$this, 'reset_lootboxes'],
            'permission_callback' => function () {
                return current_user_can('administrator');
            },
        ));
    }

    /**
     * Zwraca dane lootboxa dla popupu
     * 
     * @param WP_REST_Request $request Dane żądania
     * @return WP_REST_Response Odpowiedź API
     */
    public function get_lootbox_popup($request)
    {
        $lootbox_id = intval($request->get_param('lootbox_id'));

        if (!$lootbox_id) {
            return new WP_REST_Response(['error' => 'Brak ID lootboxa'], 400);
        }

        $lootbox = get_post($lootbox_id);

        if (!$lootbox || $lootbox->post_type !== 'lootbox') {
            return new WP_REST_Response(['error' => 'Nieprawidłowy lootbox'], 404);
        }

        $user_id = get_current_user_id();

        // Sprawdź, czy użytkownik już przeszukał ten lootbox
        $searched_lootboxes = $this->get_user_searched_lootboxes($user_id);
        $already_searched = in_array($lootbox_id, $searched_lootboxes);

        // Pobierz dane lootboxa
        $lootbox_data = [
            'id' => $lootbox_id,
            'title' => get_the_title($lootbox_id),
            'type' => get_field('lootbox_place_type', $lootbox_id),
            'price' => intval(get_field('lootbox_price', $lootbox_id)),
            'already_searched' => $already_searched,
            'user_energy' => intval(get_field('vitality_energy', 'user_' . $user_id)),
            'max_user_energy' => intval(get_field('vitality_max_energy', 'user_' . $user_id)),
        ];

        return new WP_REST_Response($lootbox_data);
    }

    /**
     * Przeszukuje lootbox i zwraca wyniki
     * 
     * @param WP_REST_Request $request Dane żądania
     * @return WP_REST_Response Odpowiedź API
     */
    public function search_lootbox($request)
    {
        $lootbox_id = intval($request->get_param('lootbox_id'));

        if (!$lootbox_id) {
            return new WP_REST_Response(['error' => 'Brak ID lootboxa'], 400);
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response(['error' => 'Użytkownik niezalogowany'], 401);
        }

        // Sprawdź, czy użytkownik już przeszukał ten lootbox
        $searched_lootboxes = $this->get_user_searched_lootboxes($user_id);
        if (in_array($lootbox_id, $searched_lootboxes)) {
            return new WP_REST_Response([
                'error' => 'Już przeszukałeś ten obiekt',
                'already_searched' => true
            ], 200);
        }

        // Pobierz koszt przeszukania i aktualną energię użytkownika
        $search_cost = intval(get_field('lootbox_price', $lootbox_id));
        $user_energy = intval(get_field('vitality_energy', 'user_' . $user_id));

        // Sprawdź, czy użytkownik ma wystarczająco energii
        if ($user_energy < $search_cost) {
            return new WP_REST_Response([
                'error' => 'Nie masz wystarczająco energii',
                'user_energy' => $user_energy,
                'required_energy' => $search_cost
            ], 200);
        }

        // Pobierz liczbę rund losowania
        $min_rounds = intval(get_field('lootbox_rounds_from', $lootbox_id));
        $max_rounds = intval(get_field('lootbox_rounds_to', $lootbox_id));
        $rounds = rand($min_rounds, $max_rounds);

        // Pobierz nagrody
        $rewards = get_field('lootbox_rewards', $lootbox_id);

        // Przygotuj pulę losowań
        $draw_pool = [];
        if ($rewards && is_array($rewards)) {
            foreach ($rewards as $reward) {
                $draws = intval($reward['draws']);
                for ($i = 0; $i < $draws; $i++) {
                    $draw_pool[] = $reward;
                }
            }
        }

        // Jeśli pula jest pusta, zwróć błąd
        if (empty($draw_pool)) {
            return new WP_REST_Response(['error' => 'Brak nagród do wylosowania'], 500);
        }

        // Wykonaj losowania
        $results = [];
        for ($i = 0; $i < $rounds; $i++) {
            // Losuj nagrodę z puli
            $random_index = array_rand($draw_pool);
            $reward = $draw_pool[$random_index];

            // Losuj ilość
            $min_amount = intval($reward['min']);
            $max_amount = intval($reward['max']);
            $amount = rand($min_amount, $max_amount);

            // Przygotuj komunikat
            $message = !empty($reward['message']) ? $reward['message'] : "Znalazłeś {{$i}} x [nagroda]";
            $message = str_replace('{{$i}}', $amount, $message);

            // Ustal typ i identyfikator nagrody
            $reward_type = $reward['reward_type'];
            $item_id = null;

            if ($reward_type === 'item') {
                $item_id = $reward['item'];
                $item_title = get_the_title($item_id);
                $message = str_replace('[nagroda]', $item_title, $message);
            } else {
                $message = str_replace('[nagroda]', $reward_type, $message);
            }

            // Dodaj do wyników
            $results[] = [
                'type' => $reward_type,
                'item_id' => $item_id,
                'amount' => $amount,
                'message' => $message
            ];

            // Przyznaj nagrody użytkownikowi
            $this->award_user($user_id, $reward_type, $item_id, $amount);
        }

        // Odejmij koszt energii
        $new_energy = $user_energy - $search_cost;
        update_field('vitality_energy', $new_energy, 'user_' . $user_id);

        // Oznacz lootbox jako przeszukany
        $this->mark_lootbox_as_searched($user_id, $lootbox_id);

        return new WP_REST_Response([
            'success' => true,
            'results' => $results,
            'rounds' => $rounds,
            'energy_cost' => $search_cost,
            'user_energy' => $new_energy
        ]);
    }

    /**
     * Resetuje listę przeszukanych lootboxów
     * 
     * @param WP_REST_Request $request Dane żądania
     * @return WP_REST_Response Odpowiedź API
     */
    public function reset_lootboxes($request)
    {
        $user_id = $request->get_param('user_id');
        $reset_all = $request->get_param('reset_all');

        if ($reset_all) {
            // Reset dla wszystkich użytkowników
            $users = get_users();
            foreach ($users as $user) {
                delete_field('searched_lootboxes', 'user_' . $user->ID);
            }
            return new WP_REST_Response(['success' => true, 'message' => 'Zresetowano wszystkie lootboxy dla wszystkich użytkowników']);
        } elseif ($user_id) {
            // Reset dla konkretnego użytkownika
            delete_field('searched_lootboxes', 'user_' . $user_id);
            return new WP_REST_Response(['success' => true, 'message' => 'Zresetowano lootboxy dla użytkownika ID: ' . $user_id]);
        } else {
            return new WP_REST_Response(['error' => 'Brak parametru user_id lub reset_all'], 400);
        }
    }

    /**
     * Pobiera listę ID przeszukanych lootboxów dla użytkownika
     * 
     * @param int $user_id ID użytkownika
     * @return array Tablica ID lootboxów
     */
    private function get_user_searched_lootboxes($user_id)
    {
        $searched = get_field('searched_lootboxes', 'user_' . $user_id);
        $ids = [];

        if ($searched && is_array($searched)) {
            foreach ($searched as $entry) {
                $ids[] = intval($entry['lootbox_id']);
            }
        }

        return $ids;
    }

    /**
     * Oznacza lootbox jako przeszukany przez użytkownika
     * 
     * @param int $user_id ID użytkownika
     * @param int $lootbox_id ID lootboxa
     */
    private function mark_lootbox_as_searched($user_id, $lootbox_id)
    {
        $searched = get_field('searched_lootboxes', 'user_' . $user_id);

        if (!$searched) {
            $searched = [];
        }

        $searched[] = [
            'lootbox_id' => $lootbox_id,
            'search_date' => date('Y-m-d H:i:s')
        ];

        update_field('searched_lootboxes', $searched, 'user_' . $user_id);
    }

    /**
     * Przyznaje nagrodę użytkownikowi
     * 
     * @param int $user_id ID użytkownika
     * @param string $type Typ nagrody (gold, szlugi, item)
     * @param int|null $item_id ID przedmiotu (tylko dla typu "item")
     * @param int $amount Ilość
     */
    private function award_user($user_id, $type, $item_id, $amount)
    {
        switch ($type) {
            case 'gold':
                $current = intval(get_field('backpack_gold', 'user_' . $user_id));
                update_field('backpack_gold', $current + $amount, 'user_' . $user_id);
                break;

            case 'szlugi':
                $current = intval(get_field('backpack_cigarettes', 'user_' . $user_id));
                update_field('backpack_cigarettes', $current + $amount, 'user_' . $user_id);
                break;

            case 'item':
                if ($item_id) {
                    // Sprawdź, czy użytkownik ma już ten przedmiot w ekwipunku
                    $backpack_items = get_field('backpack_items', 'user_' . $user_id);
                    if (!$backpack_items) {
                        $backpack_items = [];
                    }

                    // Sprawdź czy przedmiot już istnieje w ekwipunku
                    $item_exists = false;
                    foreach ($backpack_items as $key => $backpack_item) {
                        if ($backpack_item['item'] == $item_id) {
                            // Przedmiot istnieje, zwiększ ilość
                            $backpack_items[$key]['quantity'] += $amount;
                            $item_exists = true;
                            break;
                        }
                    }

                    // Jeśli przedmiotu nie ma w ekwipunku, dodaj go
                    if (!$item_exists) {
                        $backpack_items[] = [
                            'item' => $item_id,
                            'quantity' => $amount
                        ];
                    }

                    update_field('backpack_items', $backpack_items, 'user_' . $user_id);
                }
                break;
        }
    }
}

// Inicjalizacja klasy
new LootboxHandler();
