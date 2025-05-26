<?php
class NpcPopup
{

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

            $this->log_debug("===== ROZPOCZĘCIE ŻĄDANIA NPC POPUP =====");
            $this->log_debug("Parametry żądania", [
                'npc_id' => $npc_id,
                'user_id' => $user_id,
                'page_data' => $page_data,
                'current_url' => $current_url
            ]);

            // Sprawdź czy NPC istnieje
            $npc_post = get_post($npc_id);
            if (!$npc_post || $npc_post->post_type !== 'npc') {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'NPC nie istnieje'
                ], 404);
            }            // Pobierz dane NPC
            $npc_data = $this->get_npc_data($npc_id);

            // Pobierz dialogi NPC
            $dialogs = get_field('dialogs', $npc_id);
            $this->log_debug("Pobrano dialogi z ACF", [
                'field_name' => 'dialogs',
                'npc_id' => $npc_id,
                'dialogs_count' => is_array($dialogs) ? count($dialogs) : 'null/false',
                'dialogs_type' => gettype($dialogs)
            ]);

            if (!$dialogs || !is_array($dialogs)) {
                $this->log_debug("BŁĄD: Brak dialogów dla NPC");
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Brak dialogów dla tego NPC'
                ], 404);
            }

            // Znajdź pierwszy dostępny dialog
            $first_dialog = $this->find_first_available_dialog($dialogs, $user_id, $page_data);
            $this->log_debug("Rezultat wyszukiwania pierwszego dialogu", [
                'found' => $first_dialog !== null,
                'dialog_id' => $first_dialog ? $first_dialog['id_pola'] : 'null'
            ]);

            if (!$first_dialog) {
                $this->log_debug("BŁĄD: Brak dostępnych dialogów dla użytkownika");
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Brak dostępnych dialogów'
                ], 404);
            }

            // Przefiltruj odpowiedzi w dialogu
            $filtered_dialog = $this->filter_dialog_answers($first_dialog, $user_id, $page_data);
            $this->log_debug("Dialog po filtrowaniu odpowiedzi", $filtered_dialog);

            // Przygotuj dane odpowiedzi
            $response_data = [
                'success' => true,
                'npc_data' => [
                    'id' => $npc_id,
                    'name' => $npc_data['name'],
                    'title' => $npc_data['title'],
                    'thumbnail_url' => $npc_data['thumbnail_url'],
                    'dialog' => $filtered_dialog,
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

            // Pobierz dialogi NPC
            $dialogs = get_field('dialogs', $npc_id);
            if (!$dialogs || !is_array($dialogs)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Brak dialogów dla tego NPC'
                ], 404);
            }

            // Wykonaj akcje z poprzedniej odpowiedzi (jeśli wybrano odpowiedź)
            $notification = null;
            if ($current_dialog_id && is_numeric($answer_index)) {
                $notification = $this->execute_answer_actions($dialogs, $current_dialog_id, $answer_index, $user_id);
                if ($notification) {
                    $this->log_debug("Otrzymano powiadomienie z akcji", [
                        'message' => $notification['message'],
                        'status' => $notification['status']
                    ]);
                }
            }

            // Znajdź docelowy dialog
            $target_dialog = $this->find_dialog_by_id($dialogs, $dialog_id);

            if (!$target_dialog) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Dialog nie został znaleziony'
                ], 404);
            }

            // Sprawdź widoczność docelowego dialogu
            if (!$this->is_dialog_visible($target_dialog, $user_id)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Dialog nie jest dostępny'
                ], 403);
            }

            // Przefiltruj odpowiedzi w dialogu
            $filtered_dialog = $this->filter_dialog_answers($target_dialog, $user_id);

            // Przygotuj dane odpowiedzi
            $response_data = [
                'success' => true,
                'dialog' => $filtered_dialog,
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
     * Znajduje pierwszy dostępny dialog dla użytkownika
     * 
     * @param array $dialogs Lista dialogów
     * @param int $user_id ID użytkownika
     * @param array $page_data Dane strony
     * @return array|null Pierwszy dostępny dialog
     */
    private function find_first_available_dialog($dialogs, $user_id, $page_data = [])
    {
        foreach ($dialogs as $dialog) {
            if ($this->is_dialog_visible($dialog, $user_id, $page_data)) {
                return $dialog;
            }
        }
        return null;
    }

    /**
     * Znajduje dialog po ID
     * 
     * @param array $dialogs Lista dialogów
     * @param string $dialog_id ID dialogu
     * @return array|null Znaleziony dialog
     */
    private function find_dialog_by_id($dialogs, $dialog_id)
    {
        foreach ($dialogs as $dialog) {
            if (isset($dialog['id_pola']) && $dialog['id_pola'] === $dialog_id) {
                return $dialog;
            }
        }
        return null;
    }

    /**
     * Sprawdza czy dialog jest widoczny dla użytkownika
     * 
     * @param array $dialog Dane dialogu
     * @param int $user_id ID użytkownika
     * @param array $page_data Dane strony
     * @return bool True jeśli dialog jest widoczny
     */
    private function is_dialog_visible($dialog, $user_id, $page_data = [])
    {
        $dialog_id = $dialog['id_pola'] ?? 'unknown';
        $this->log_debug("Sprawdzanie widoczności dialogu: {$dialog_id}");

        // Sprawdź ustawienia widoczności
        if (!isset($dialog['dialog_settings']) || !is_array($dialog['dialog_settings'])) {
            $this->log_debug("Dialog {$dialog_id}: Brak ustawień widoczności - domyślnie widoczny");
            return true; // Domyślnie widoczny jeśli brak ustawień
        }

        $visibility_settings = $dialog['dialog_settings']['visibility_settings'] ?? [];
        $logic_operator = $dialog['dialog_settings']['logic_operator'] ?? 'and';

        $this->log_debug("Dialog {$dialog_id}: Ustawienia widoczności", [
            'logic_operator' => $logic_operator,
            'conditions_count' => count($visibility_settings)
        ]);

        if (empty($visibility_settings)) {
            $this->log_debug("Dialog {$dialog_id}: Brak warunków - domyślnie widoczny");
            return true; // Brak warunków = widoczny
        }

        $results = [];
        foreach ($visibility_settings as $index => $condition) {
            $result = $this->evaluate_visibility_condition($condition, $user_id, $page_data);
            $results[] = $result;
            $this->log_debug("Dialog {$dialog_id}: Warunek #{$index} - wynik: " . ($result ? 'SPEŁNIONY' : 'NIE SPEŁNIONY'), $condition);
        }

        // Zastosuj operator logiczny
        $final_result = false;
        if ($logic_operator === 'or') {
            $final_result = in_array(true, $results);
        } else {
            $final_result = !in_array(false, $results);
        }

        $this->log_debug("Dialog {$dialog_id}: Końcowy wynik widoczności: " . ($final_result ? 'WIDOCZNY' : 'UKRYTY'));
        return $final_result;
    }
    /**
     * Ocenia pojedynczy warunek widoczności
     * 
     * @param array $condition Warunek widoczności
     * @param int $user_id ID użytkownika
     * @param array $page_data Dane strony
     * @return bool Wynik oceny warunku
     */
    private function evaluate_visibility_condition($condition, $user_id, $page_data = [])
    {
        $type = $condition['acf_fc_layout'] ?? '';
        $this->log_debug("Ocena warunku typu: {$type}", $condition);

        $result = false;
        switch ($type) {
            case 'stat':
                $result = $this->check_stat_condition($condition, $user_id);
                break;

            case 'skill':
                $result = $this->check_skill_condition($condition, $user_id);
                break;

            case 'item':
                $result = $this->check_item_condition($condition, $user_id);
                break;

            case 'relation':
                $result = $this->check_relation_condition($condition, $user_id);
                break;

            case 'mission':
                $result = $this->check_mission_condition($condition, $user_id);
                break;

            case 'condition_inventory':
                $result = $this->check_item_condition($condition, $user_id);
                break;

            case 'condition_mission':
                $result = $this->check_mission_condition($condition, $user_id);
                break;

            case 'condition_location':
                $result = $this->check_location_condition($condition, $user_id, $page_data);
                break;

            case 'condition_task':
                $result = $this->check_task_condition($condition, $user_id);
                break;

            case 'condition_npc_relation':
                $result = $this->check_relation_condition($condition, $user_id);
                break;

            case 'user_class':
                $result = $this->check_user_class_condition($condition, $user_id);
                break;

            default:
                $this->log_debug("NIEROZPOZNANY typ warunku: {$type}");
                $result = true; // Nieznane warunki są domyślnie spełnione
        }

        $this->log_debug("Wynik warunku {$type}: " . ($result ? 'SPEŁNIONY' : 'NIE SPEŁNIONY'));
        return $result;
    }
    /**
     * Sprawdza warunek statystyki
     */
    private function check_stat_condition($condition, $user_id)
    {
        $stat_name = $condition['stat'] ?? '';
        $operator = $condition['operator'] ?? '>=';
        $value = intval($condition['value'] ?? 0);

        $user_stats = get_field('stats', 'user_' . $user_id) ?: [];
        $current_value = intval($user_stats[$stat_name] ?? 0);

        $result = $this->compare_values($current_value, $operator, $value);

        $this->log_debug("Warunek statystyki", [
            'stat_name' => $stat_name,
            'operator' => $operator,
            'required_value' => $value,
            'current_value' => $current_value,
            'result' => $result
        ]);

        return $result;
    }
    /**
     * Sprawdza warunek umiejętności
     */
    private function check_skill_condition($condition, $user_id)
    {
        $skill_name = $condition['skill'] ?? '';
        $operator = $condition['operator'] ?? '>=';
        $value = intval($condition['value'] ?? 0);

        $user_skills = get_field('skills', 'user_' . $user_id) ?: [];
        $current_value = intval($user_skills[$skill_name] ?? 0);

        $result = $this->compare_values($current_value, $operator, $value);

        $this->log_debug("Warunek umiejętności", [
            'skill_name' => $skill_name,
            'operator' => $operator,
            'required_value' => $value,
            'current_value' => $current_value,
            'result' => $result
        ]);

        return $result;
    }

    /**
     * Sprawdza warunek przedmiotu
     */
    private function check_item_condition($condition, $user_id)
    {
        // Obsługa parametrów zarówno z item jak i condition_inventory
        $item_id = intval($condition['item'] ?? $condition['item_id'] ?? 0);
        $condition_type = $condition['condition'] ?? '';
        $required_quantity = intval($condition['quantity'] ?? 1);

        // Mapowanie warunku na operator
        $operator = '>=';
        if ($condition_type === 'has_item') {
            $operator = '>=';
        } elseif ($condition_type === 'has_not_item') {
            $operator = '<';
        } elseif ($condition_type === 'quantity_above') {
            $operator = '>';
        } elseif ($condition_type === 'quantity_below') {
            $operator = '<';
        } elseif ($condition_type === 'quantity_equal') {
            $operator = '==';
        } else {
            $operator = $condition['operator'] ?? '>=';
        }

        $user_items = get_field('items', 'user_' . $user_id) ?: [];
        $current_quantity = 0;

        foreach ($user_items as $user_item) {
            if (intval($user_item['item']) === $item_id) {
                $current_quantity = intval($user_item['quantity'] ?? 0);
                break;
            }
        }

        return $this->compare_values($current_quantity, $operator, $required_quantity);
    }

    /**
     * Sprawdza warunek relacji z NPC
     */
    private function check_relation_condition($condition, $user_id)
    {
        $npc_id = intval($condition['npc'] ?? 0);
        $condition_type = $condition['condition'] ?? 'relation_above';
        $value = intval($condition['value'] ?? 0);

        // Sprawdzenie czy NPC jest poznany
        if ($condition_type === 'is_known') {
            $meet_field = 'npc-meet-' . $npc_id;
            $is_known = get_field($meet_field, 'user_' . $user_id);
            return (bool)$is_known;
        }

        // Sprawdzenie czy NPC nie jest poznany
        if ($condition_type === 'is_not_known') {
            $meet_field = 'npc-meet-' . $npc_id;
            $is_known = get_field($meet_field, 'user_' . $user_id);
            return !(bool)$is_known;
        }

        // Obsługa operatorów porównania wartości relacji
        $operator = '>=';
        if ($condition_type === 'relation_above') {
            $operator = '>';
        } elseif ($condition_type === 'relation_below') {
            $operator = '<';
        } elseif ($condition_type === 'relation_equal') {
            $operator = '==';
        } else {
            $operator = $condition['operator'] ?? '>=';
        }

        $user_relations = get_field('relation_npc', 'user_' . $user_id) ?: [];
        $current_value = 0;

        foreach ($user_relations as $relation) {
            if (intval($relation['npc']) === $npc_id) {
                $current_value = intval($relation['relation'] ?? 0);
                break;
            }
        }

        return $this->compare_values($current_value, $operator, $value);
    }

    /**
     * Sprawdza warunek misji
     */
    private function check_mission_condition($condition, $user_id)
    {
        // Obsługa parametrów zarówno z mission jak i condition_mission
        $mission_id = intval($condition['mission'] ?? $condition['mission_id'] ?? 0);
        $status = $condition['status'] ?? 'completed';
        $condition_type = $condition['condition'] ?? 'is';

        $user_missions = get_field('user_missions', 'user_' . $user_id) ?: [];

        foreach ($user_missions as $mission) {
            if (intval($mission['mission']) === $mission_id) {
                // Obsługa różnych typów warunków misji
                if ($condition_type === 'is') {
                    return $mission['status'] === $status;
                } elseif ($condition_type === 'is_not') {
                    return $mission['status'] !== $status;
                }

                // Domyślne sprawdzenie
                return $mission['status'] === $status;
            }
        }

        // Dla warunku "is_not" zwracamy true jeśli misja nie istnieje
        if ($condition_type === 'is_not') {
            return true;
        }

        return false;
    }

    /**
     * Sprawdza warunek klasy użytkownika
     */
    private function check_user_class_condition($condition, $user_id)
    {
        $required_class = $condition['class'] ?? '';
        $user_class_data = get_field('user_class', 'user_' . $user_id);
        $user_class = is_array($user_class_data) ? ($user_class_data['value'] ?? '') : $user_class_data;

        return $user_class === $required_class;
    }

    /**
     * Sprawdza warunek lokalizacji
     */
    private function check_location_condition($condition, $user_id, $page_data = [])
    {
        $condition_type = $condition['condition'] ?? 'is';
        $location_type = $condition['location_type'] ?? 'text';
        $location_value = '';

        if ($location_type === 'text') {
            $location_value = $condition['location_text'] ?? '';
            $current_location = $page_data['value'] ?? '';
        } else {
            $area_id = intval($condition['area'] ?? 0);
            $location_value = $area_id;

            // Sprawdź czy użytkownik ma dostęp do obszaru
            $user_areas = get_field('available_areas', 'user_' . $user_id) ?: [];
            $current_location = in_array($area_id, $user_areas);

            // Dla obszarów używamy boolean, więc dostosujemy warunek
            if ($condition_type === 'is') {
                return $current_location === true;
            } else {
                return $current_location === false;
            }
        }

        // Dla tekstowej lokalizacji porównujemy stringi
        if ($condition_type === 'is') {
            return $current_location === $location_value;
        } else {
            return $current_location !== $location_value;
        }
    }

    /**
     * Sprawdza warunek zadania
     */
    private function check_task_condition($condition, $user_id)
    {
        $mission_id = intval($condition['mission_id'] ?? 0);
        $task_id = $condition['task_id'] ?? '';
        $status = $condition['status'] ?? 'completed';
        $condition_type = $condition['condition'] ?? 'is';

        if (empty($mission_id) || empty($task_id)) {
            return false;
        }

        // Pobierz dane misji użytkownika
        $mission_field = 'mission_' . $mission_id;
        $user_mission_data = get_field($mission_field, 'user_' . $user_id);

        // Sprawdź czy istnieje pole zadania
        if (!$user_mission_data || !isset($user_mission_data['tasks']) || !isset($user_mission_data['tasks'][$task_id])) {
            // Dla warunku "is_not" zwracamy true, jeśli zadanie nie istnieje
            return $condition_type === 'is_not';
        }

        $task_status = '';

        // Zadanie może być prostym stringiem lub złożonym obiektem z polami
        $task_data = $user_mission_data['tasks'][$task_id];
        if (is_array($task_data) && isset($task_data['status'])) {
            $task_status = $task_data['status'];
        } else {
            $task_status = $task_data;
        }

        // Sprawdź warunek
        if ($condition_type === 'is') {
            return $task_status === $status;
        } else {
            return $task_status !== $status;
        }
    }

    /**
     * Porównuje wartości według operatora
     */
    private function compare_values($current, $operator, $required)
    {
        switch ($operator) {
            case '>=':
                return $current >= $required;
            case '>':
                return $current > $required;
            case '<=':
                return $current <= $required;
            case '<':
                return $current < $required;
            case '==':
                return $current == $required;
            case '!=':
                return $current != $required;
            default:
                return $current >= $required;
        }
    }

    /**
     * Filtruje odpowiedzi w dialogu na podstawie warunków widoczności
     * 
     * @param array $dialog Dane dialogu
     * @param int $user_id ID użytkownika
     * @param array $page_data Dane strony
     * @return array Przefiltrowany dialog
     */
    private function filter_dialog_answers($dialog, $user_id, $page_data = [])
    {
        if (!isset($dialog['anwsers']) || !is_array($dialog['anwsers'])) {
            return $dialog;
        }

        $filtered_answers = [];
        foreach ($dialog['anwsers'] as $answer) {
            if ($this->is_answer_visible($answer, $user_id, $page_data)) {
                $filtered_answers[] = $answer;
            }
        }

        $dialog['anwsers'] = $filtered_answers;
        return $dialog;
    }

    /**
     * Sprawdza czy odpowiedź jest widoczna dla użytkownika
     */
    private function is_answer_visible($answer, $user_id, $page_data = [])
    {
        if (!isset($answer['layout_settings']) || !is_array($answer['layout_settings'])) {
            return true;
        }

        $visibility_settings = $answer['layout_settings']['visibility_settings'] ?? [];
        $logic_operator = $answer['layout_settings']['logic_operator'] ?? 'and';

        if (empty($visibility_settings)) {
            return true;
        }

        $results = [];
        foreach ($visibility_settings as $condition) {
            $results[] = $this->evaluate_visibility_condition($condition, $user_id, $page_data);
        }

        if ($logic_operator === 'or') {
            return in_array(true, $results);
        } else {
            return !in_array(false, $results);
        }
    }

    /**
     * Wykonuje akcje związane z wybraną odpowiedzią
     * 
     * @param array $dialogs Lista wszystkich dialogów
     * @param string $dialog_id ID aktualnego dialogu
     * @param int $answer_index Indeks wybranej odpowiedzi
     * @param int $user_id ID użytkownika
     * @return array|null Powiadomienie o wykonanych akcjach z statusem
     */
    private function execute_answer_actions($dialogs, $dialog_id, $answer_index, $user_id)
    {
        $dialog = $this->find_dialog_by_id($dialogs, $dialog_id);
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
