<?php
class NpcPopup
{
    private $dialog_filter;

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    private function log_debug($message, $data = null)
    {
        $log_file = get_template_directory() . '/npc_debug.log';
        $timestamp = date('[Y-m-d H:i:s]');
        $log_entry = $timestamp . ' [DEBUG] ' . $message . "\n";

        if ($data !== null) {
            $log_entry .= $timestamp . ' [DATA] ' . print_r($data, true) . "\n";
        }

        error_log($log_entry, 3, $log_file);
    }

    public function register_endpoints()
    {
        // Endpoint do pokazywania początkowego popup-a NPC
        register_rest_route('game/v1', '/npc/popup', array(
            'methods' => 'POST',
            'callback' => [$this, 'get_npc_popup'],
            'permission_callback' => [$this, 'check_user_permission'],
            'args' => array(
                'npc_id' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ),
                'page_data' => array(
                    'required' => false,
                    'default' => array()
                ),
                'current_url' => array(
                    'required' => false,
                    'default' => ''
                )
            )
        ));

        // Endpoint do obsługi przejść między dialogami
        register_rest_route('game/v1', '/dialog', array(
            'methods' => 'POST',
            'callback' => [$this, 'handle_dialog_transition'],
            'permission_callback' => [$this, 'check_user_permission'],
            'args' => array(
                'npc_id' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ),
                'dialog_id' => array(
                    'required' => true
                ),
                'answer_index' => array(
                    'required' => false,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
    }

    /**
     * Sprawdza uprawnienia użytkownika do korzystania z endpointów
     */
    public function check_user_permission($request)
    {
        $auth_cookie = '';

        if (isset($_COOKIE[LOGGED_IN_COOKIE])) {
            $auth_cookie = $_COOKIE[LOGGED_IN_COOKIE];
        }

        $user_id = wp_validate_auth_cookie($auth_cookie, 'logged_in');

        if ($user_id) {
            wp_set_current_user($user_id);
            return true;
        }

        $this->log_debug("Sprawdzenie uprawnień: ODRZUCONO (brak poprawnego cookie)");
        return false;
    }



    /**
     * Bezpiecznie pobiera ID aktualnego użytkownika
     */
    private function get_secure_user_id()
    {
        $user_id = get_current_user_id();

        return $user_id;
    }

    /**
     * Sprawdza throttling zapytań - zapobiega zbyt częstym wywołaniom
     * Używa WordPress transients do przechowywania czasów ostatnich zapytań
     * 
     * @param int $user_id ID użytkownika
     * @param float $throttle_seconds Minimalna liczba sekund między zapytaniami (domyślnie 0.5 sekundy)
     * @return bool true jeśli zapytanie może być przetworzone, false jeśli jest zbyt szybkie
     */
    private function check_request_throttling($user_id, $throttle_seconds = 0.5)
    {
        // Unikalna nazwa transient dla każdego użytkownika
        $transient_key = 'npc_dialog_throttle_' . $user_id;

        // Pobierz czas ostatniego zapytania (używamy microtime dla większej precyzji)
        $last_request_time = get_transient($transient_key);
        $current_time = microtime(true);

        // Jeśli to pierwsze zapytanie lub minął wystarczający czas
        if ($last_request_time === false || ($current_time - $last_request_time) >= $throttle_seconds) {
            // Zapisz aktualny czas i pozwól na wykonanie zapytania
            set_transient($transient_key, $current_time, 60); // Transient ważny przez 60 sekund

            return true;
        }

        return false;
    }

    /**
     * Zwraca dane NPC i początkowy dialog dla popup-a
     * 
     * @param WP_REST_Request $request Dane żądania
     * @return WP_REST_Response Odpowiedź API
     */
    public function get_npc_popup($request)
    {
        $npc_id = $request->get_param('npc_id');
        $page_data = $request->get_param('page_data');
        $current_url = $request->get_param('current_url');

        try {
            // Bezpieczne pobranie user_id
            $user_id = $this->get_secure_user_id();

            // Sprawdź throttling - blokuj zbyt częste zapytania
            if (!$this->check_request_throttling($user_id)) {
                return new WP_REST_Response([
                    'success' => true,
                    'status' => 'neutral',
                    'message' => 'Zbyt szybkie kolejne działania. Poczekaj chwilę przed następnym kliknięciem.'
                ], 200);
            }

            // Sprawdź czy NPC istnieje
            $npc_post = get_post($npc_id);
            if (!$npc_post || $npc_post->post_type !== 'npc') {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'NPC nie istnieje'
                ], 404);
            }            // Pobierz dane NPC
            $npc_data = $this->get_npc_data($npc_id);

            // Inicjalizuj dialog filter z kontekstem użytkownika
            $this->dialog_filter = new DialogFilter($user_id, $page_data);

            // Znajdź pierwszy dostępny dialog
            $first_dialog = $this->dialog_filter->get_first_available_dialog($npc_id);


            if (!$first_dialog) {
                $this->log_debug("BŁĄD: Brak dostępnych dialogów dla użytkownika");
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Brak dostępnych dialogów'
                ], 404);
            }

            // Przygotuj dane odpowiedzi
            $response_data = [
                'success' => true,
                'npc_data' => [
                    'id' => $npc_id,
                    'name' => $npc_data['name'],
                    'title' => $npc_data['title'],
                    'thumbnail_url' => $npc_data['thumbnail_url'],
                    'dialog' => $first_dialog,
                    'user_id' => $user_id
                ]
            ];

            return new WP_REST_Response($response_data, 200);
        } catch (Exception $e) {
            error_log('NpcPopup error: ' . $e->getMessage());
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Wystąpił błąd podczas ładowania NPC'
            ], 500);
        }
    }


    public function handle_dialog_transition($request)
    {
        $npc_id = $request->get_param('npc_id');
        $dialog_id = $request->get_param('dialog_id');
        $answer_index = $request->get_param('answer_index');
        $current_dialog_id = $request->get_param('current_dialog_id');

        try {
            // Bezpieczne pobranie user_id
            $user_id = $this->get_secure_user_id();

            // Sprawdź throttling - blokuj zbyt częste zapytania
            if (!$this->check_request_throttling($user_id)) {
                return new WP_REST_Response([
                    'success' => true,
                    'status' => 'neutral',
                    'message' => 'Zbyt szybkie kolejne działania. Poczekaj chwilę przed następnym kliknięciem.'
                ], 200);
            }

            // Sprawdź czy NPC istnieje
            $npc_post = get_post($npc_id);
            if (!$npc_post || $npc_post->post_type !== 'npc') {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'NPC nie istnieje'
                ], 404);
            }

            // Pobierz dane NPC
            $npc_data = $this->get_npc_data($npc_id);

            // Inicjalizuj dialog filter z kontekstem użytkownika
            $this->dialog_filter = new DialogFilter($user_id);

            // Wykonaj akcje z poprzedniej odpowiedzi (jeśli wybrano odpowiedź)
            $notification = null;
            if ($current_dialog_id && is_numeric($answer_index)) {
                $notification = $this->execute_answer_actions($npc_id, $current_dialog_id, $answer_index, $user_id);
                if ($notification) {
                    $this->log_debug("Otrzymano powiadomienie z akcji", [
                        'message' => $notification['message'],
                        'status' => $notification['status']
                    ]);
                }
            }

            // Znajdź docelowy dialog
            $target_dialog = $this->dialog_filter->get_dialog_by_id($npc_id, $dialog_id);

            if (!$target_dialog) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Dialog nie został znaleziony lub nie jest dostępny'
                ], 404);
            }

            // Przygotuj dane odpowiedzi
            $response_data = [
                'success' => true,
                'dialog' => $target_dialog,
                'npc' => [
                    'name' => $npc_data['name'],
                    'image' => $npc_data['thumbnail_url']
                ]
            ];

            // Dodaj powiadomienie jeśli istnieje
            if ($notification) {
                $response_data['notification'] = [
                    'message' => $notification['message'],
                    'status' => $notification['status']
                ];
            }

            return new WP_REST_Response($response_data, 200);
        } catch (Exception $e) {
            error_log('NpcPopup dialog transition error: ' . $e->getMessage());
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Wystąpił błąd podczas przejścia dialogu'
            ], 500);
        }
    }

    /**
     * Pobiera podstawowe dane NPC
     * 
     * @param int $npc_id ID NPC
     * @return array Dane NPC
     */
    private function get_npc_data($npc_id)
    {
        $post = get_post($npc_id);
        $thumbnail_id = get_post_thumbnail_id($npc_id);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';

        return [
            'name' => $post->post_title,
            'title' => $post->post_title,
            'thumbnail_url' => $thumbnail_url
        ];
    }

    /**
     * Wykonuje akcje związane z wybraną odpowiedzią
     * 
     * @param int $npc_id ID NPC
     * @param string $dialog_id ID aktualnego dialogu
     * @param int $answer_index Indeks wybranej odpowiedzi
     * @param int $user_id ID użytkownika
     * @return array|null Powiadomienie o wykonanych akcjach z statusem
     */
    private function execute_answer_actions($npc_id, $dialog_id, $answer_index, $user_id)
    {
        $dialog = $this->dialog_filter->find_dialog_by_id_raw($npc_id, $dialog_id);
        $this->log_debug("Dialog kurwa", [
            'dialog' => $dialog
        ]);
        if (!$dialog || !isset($dialog['anwsers'][$answer_index])) {
            return null;
        }

        $answer = $dialog['anwsers'][$answer_index];
        $actions = $answer['type_anwser'] ?? [];

        $notifications = [];
        $overall_status = 'success'; // Domyślny status
        $manager_user = new ManagerUser($user_id);

        foreach ($actions as $action) {
            $action_type = $action['acf_fc_layout'] ?? '';

            switch ($action_type) {
                case 'transaction':
                    $result = $this->execute_transaction_action($action, $manager_user);
                    if ($result) {
                        $notifications[] = $result['message'];
                        if ($result['status'] === 'bad' || $result['status'] === 'error') $overall_status = 'bad';
                    }
                    break;

                case 'item':
                    $result = $this->execute_item_action($action, $user_id);
                    if ($result) {
                        $notifications[] = $result['message'];
                        if ($result['status'] === 'bad' || $result['status'] === 'error') $overall_status = 'bad';
                    }
                    break;

                case 'exp_rep':
                    $result = $this->execute_exp_rep_action($action, $manager_user);
                    if ($result) {
                        $notifications[] = $result['message'];
                        if ($result['status'] === 'bad' || $result['status'] === 'error') $overall_status = 'bad';
                    }
                    break;

                case 'relation':
                    $result = $this->execute_relation_action($action, $user_id);
                    if ($result) {
                        $notifications[] = $result['message'];
                        if ($result['status'] === 'bad' || $result['status'] === 'error') $overall_status = 'bad';
                    }
                    break;

                case 'function':
                    $result = $this->execute_function_action($action, $user_id);
                    if ($result) {
                        $notifications[] = $result['message'];
                        if ($result['status'] === 'bad' || $result['status'] === 'error') $overall_status = 'bad';
                    }
                    break;

                case 'skills':
                    $result = $this->execute_skills_action($action, $manager_user);
                    if ($result) {
                        $notifications[] = $result['message'];
                        if ($result['status'] === 'bad' || $result['status'] === 'error') $overall_status = 'bad';
                    }
                    break;

                case 'mission':
                    $result = $this->execute_mission_action($action, $user_id);
                    if ($result) {
                        $notifications[] = $result['message'];
                        if ($result['status'] === 'bad' || $result['status'] === 'error') $overall_status = 'bad';
                    }
                    break;
            }
        }

        if (!empty($notifications)) {
            return [
                'message' => implode(', ', $notifications),
                'status' => $overall_status
            ];
        }

        return null;
    }

    /**
     * Wykonuje akcję transakcji
     */
    private function execute_transaction_action($action, $manager_user)
    {
        $currency = $action['backpack'] ?? 'gold';
        $value = intval($action['value'] ?? 0);

        $field_name = 'backpack_' . $currency;
        $result = $manager_user->updateNumericField($field_name, $value);

        if ($result['success']) {
            // Jeśli wartość jest ujemna, to tracisz walutę (bad)
            $status = $value < 0 ? 'bad' : 'success';
            return [
                'message' => $result['message'],
                'status' => $status
            ];
        } else {
            // Jeśli operacja nie powiodła się (np. brak wystarczającej ilości waluty)
            return [
                'message' => $result['message'],
                'status' => 'error'
            ];
        }
    }

    /**
     * Wykonuje akcję przedmiotu
     */
    private function execute_item_action($action, $user_id)
    {
        $item_id = intval($action['item'] ?? 0);
        $quantity = intval($action['quantity'] ?? 1);
        $item_action = $action['item_action'] ?? 'give';

        if ($item_id <= 0) return null;

        // Dla akcji 'take' (zabierz), ilość powinna być ujemna
        if ($item_action === 'take') {
            $quantity = -abs($quantity);
        } else {
            $quantity = abs($quantity);
        }

        $user_items = get_field('items', 'user_' . $user_id) ?: [];
        $item_found = false;
        $current_quantity = 0;

        // Sprawdź czy użytkownik już ma ten przedmiot i jego ilość
        foreach ($user_items as $item) {
            if (intval($item['item']) === $item_id) {
                $current_quantity = intval($item['quantity']);
                $item_found = true;
                break;
            }
        }

        // Sprawdź czy użytkownik ma wystarczającą ilość przedmiotów do zabrania
        if ($item_action === 'take' && ($current_quantity < abs($quantity) || !$item_found)) {
            $item_name = get_the_title($item_id);
            return [
                'message' => "Nie masz wystarczającej ilości przedmiotu: {$item_name}",
                'status' => 'error'
            ];
        }

        // Aktualizuj ilości przedmiotów
        $item_updated = false;
        foreach ($user_items as &$user_item) {
            if (intval($user_item['item']) === $item_id) {
                $user_item['quantity'] = intval($user_item['quantity']) + $quantity;
                // Usuń przedmiot jeśli ilość spadła do 0 lub poniżej
                if ($user_item['quantity'] <= 0) {
                    $user_item['quantity'] = 0;
                }
                $item_updated = true;
                break;
            }
        }

        // Jeśli nie ma przedmiotu i próbujemy dodać (nie zabierać)
        if (!$item_updated && $quantity > 0) {
            $user_items[] = [
                'item' => $item_id,
                'quantity' => $quantity
            ];
        }

        // Usuń przedmioty z ilością 0
        $user_items = array_filter($user_items, function ($item) {
            return intval($item['quantity']) > 0;
        });

        // Resetuj indeksy tablicy
        $user_items = array_values($user_items);

        update_field('items', $user_items, 'user_' . $user_id);

        $item_name = get_the_title($item_id);

        if ($item_action === 'take') {
            return [
                'message' => "Zabrano " . abs($quantity) . "x {$item_name}",
                'status' => 'bad'  // Zabieranie przedmiotu to negatywny wpływ
            ];
        } else {
            return [
                'message' => "Otrzymano {$quantity}x {$item_name}",
                'status' => 'success'  // Otrzymywanie przedmiotu to pozytywne wpływ
            ];
        }
    }

    /**
     * Wykonuje akcję doświadczenia/reputacji
     */
    private function execute_exp_rep_action($action, $manager_user)
    {
        $type = $action['type'] ?? 'exp';
        $value = intval($action['value'] ?? 0);

        $field_name = 'progress_' . $type;
        $result = $manager_user->updateNumericField($field_name, $value);

        if ($result['success']) {
            // Jeśli wartość jest ujemna, to tracisz exp/reputację (bad)
            $status = $value < 0 ? 'bad' : 'success';
            return [
                'message' => $result['message'],
                'status' => $status
            ];
        }

        return null;
    }

    /**
     * Wykonuje akcję relacji z NPC
     */
    private function execute_relation_action($action, $user_id)
    {
        $npc_id = intval($action['npc'] ?? 0);
        $change = intval($action['change_relation'] ?? 0);
        $poznaj = intval($action['poznaj'] ?? 0);

        if ($npc_id <= 0) return null;

        $user_relations = get_field('relation_npc', 'user_' . $user_id) ?: [];
        $relation_found = false;

        foreach ($user_relations as &$relation) {
            if (intval($relation['npc']) === $npc_id) {
                $relation['relation'] = intval($relation['relation']) + $change;
                if ($poznaj) $relation['poznaj'] = 1;
                $relation_found = true;
                break;
            }
        }

        if (!$relation_found) {
            $user_relations[] = [
                'npc' => $npc_id,
                'relation' => $change,
                'poznaj' => $poznaj
            ];
        }

        update_field('relation_npc', $user_relations, 'user_' . $user_id);

        $npc_name = get_the_title($npc_id);
        if ($poznaj) {
            return [
                'message' => "Poznano postać: {$npc_name}",
                'status' => 'success'  // Poznanie nowej postaci to pozytyw
            ];
        }

        $message = ($change > 0 ? 'Poprawiono' : 'Pogorszono') . " relację z {$npc_name}";
        $status = $change > 0 ? 'success' : 'bad';  // Pogorszenie relacji to negatywny wpływ

        return [
            'message' => $message,
            'status' => $status
        ];
    }

    /**
     * Wykonuje akcję funkcji (np. SetClass)
     */
    private function execute_function_action($action, $user_id)
    {
        $function = $action['do_function'] ?? '';

        switch ($function) {
            case 'SetClass':
                $class = $action['user_class'] ?? '';
                if ($class) {
                    update_field('user_class', ['value' => $class], 'user_' . $user_id);
                    return [
                        'message' => "Ustawiono klasę: {$class}",
                        'status' => 'success'  // Ustawienie klasy to pozytywna akcja
                    ];
                }
                break;

                // Dodaj inne funkcje w przyszłości
        }

        return null;
    }

    /**
     * Wykonuje akcję umiejętności
     */
    private function execute_skills_action($action, $manager_user)
    {
        $skill = $action['skill'] ?? '';
        $value = intval($action['value'] ?? 0);

        if (!$skill) return null;

        $field_name = 'skills_' . $skill;
        $result = $manager_user->updateNumericField($field_name, $value);

        if ($result['success']) {
            // Jeśli wartość jest ujemna, to tracisz umiejętność (bad)
            $status = $value < 0 ? 'bad' : 'success';
            return [
                'message' => $result['message'],
                'status' => $status
            ];
        }

        return null;
    }

    /**
     * Wykonuje akcję misji
     */
    private function execute_mission_action($action, $user_id)
    {
        $mission_id = intval($action['mission'] ?? 0);
        $status = $action['status'] ?? 'active';

        if ($mission_id <= 0) return null;

        $user_missions = get_field('user_missions', 'user_' . $user_id) ?: [];
        $mission_found = false;

        foreach ($user_missions as &$mission) {
            if (intval($mission['mission']) === $mission_id) {
                $mission['status'] = $status;
                $mission_found = true;
                break;
            }
        }

        if (!$mission_found) {
            $user_missions[] = [
                'mission' => $mission_id,
                'status' => $status
            ];
        }

        update_field('user_missions', $user_missions, 'user_' . $user_id);

        $mission_name = get_the_title($mission_id);

        // Określ status powiadomienia na podstawie statusu misji
        $negative_statuses = ['failed', 'failed_npc', 'oblej_npc'];
        $status_type = in_array($status, $negative_statuses) ? 'bad' : 'success';

        return [
            'message' => "Misja '{$mission_name}' - status: {$status}",
            'status' => $status_type
        ];
    }
}

// Inicjalizuj klasę
new NpcPopup();
