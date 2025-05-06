<?php

/**
 * Klasa NpcPopup
 *
 * Klasa odpowiedzialna za obsługę endpointu API dla wyświetlania popupu NPC.
 *
 * @package Game
 * @since 1.0.0
 */

class NpcPopup
{
    /**
     * Prefiks dla nazw funkcji i endpointów
     *
     * @var string
     */
    private string $prefix = 'game';

    /**
     * Ścieżka do pliku logów debugowania
     * 
     * @var string
     */
    private string $debug_log_file;

    /**
     * Konstruktor klasy
     * 
     * Rejestruje endpoint REST API.
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_rest_endpoint']);
        $this->debug_log_file = get_template_directory() . '/npc_debug.log';
    }

    /**
     * Zapisuje informacje debugowania do pliku
     * 
     * @param string $message Wiadomość do zapisania
     * @param mixed $data Dodatkowe dane do logowania (opcjonalne)
     */
    private function debug_log(string $message, $data = null): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}";

        if ($data !== null) {
            $data_string = is_array($data) || is_object($data) ? print_r($data, true) : $data;
            $log_message .= "\n" . $data_string;
        }

        $log_message .= "\n--------------------------------------------------\n";
        file_put_contents($this->debug_log_file, $log_message, FILE_APPEND);
    }

    /**
     * Rejestruje endpoint REST API
     */
    public function register_rest_endpoint(): void
    {
        register_rest_route("{$this->prefix}/v1", '/npc/popup', [
            'methods' => 'POST',
            'callback' => [$this, 'get_npc_data'],
            'permission_callback' => function (\WP_REST_Request $request): bool {
                // Zapewnij, że WordPress wie, że jesteśmy zalogowani
                if (!defined('DOING_AJAX')) {
                    define('DOING_AJAX', true);
                }

                // W trybie deweloperskim zezwalaj na dostęp bez logowania
                $is_dev_mode = defined('WP_DEBUG') && WP_DEBUG;
                $is_logged_in = is_user_logged_in();

                if (!$is_logged_in) {
                    $this->debug_log('Próba dostępu do endpointu NPC przez niezalogowanego użytkownika - ' .
                        ($is_dev_mode ? 'ZEZWOLONO (tryb deweloperski)' : 'ODMÓWIONO'));
                }

                // W trybie deweloperskim zezwalaj na dostęp bez logowania
                return $is_logged_in || $is_dev_mode;
            },
            'args' => [
                'npc_id' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ],
                'page_data' => [
                    'required' => true,
                ],
                'current_url' => [
                    'required' => true,
                    'sanitize_callback' => 'esc_url_raw',
                ],
            ],
        ]);
    }

    /**
     * Zwraca dane NPC na podstawie ID, filtrując dialogi według aktualnej lokalizacji i innych kryteriów
     *
     * @param WP_REST_Request $request Obiekt żądania
     * @return WP_REST_Response
     */
    public function get_npc_data(\WP_REST_Request $request): \WP_REST_Response
    {
        $params = $request->get_params();
        $npc_id = isset($params['npc_id']) ? absint($params['npc_id']) : 0;
        $page_data = isset($params['page_data']) ? $params['page_data'] : [];
        $current_url = isset($params['current_url']) ? esc_url_raw($params['current_url']) : '';

        // Rozpocznij logowanie dla nowego żądania
        $this->debug_log("===== ROZPOCZĘCIE PRZETWARZANIA ŻĄDANIA NPC =====");
        $this->debug_log("Parametry żądania:", [
            'npc_id' => $npc_id,
            'page_data' => $page_data,
            'current_url' => $current_url
        ]);

        // Wyodrębnij informacje o lokalizacji
        $location = $this->extract_location_from_url($current_url);
        $type_page = isset($page_data['TypePage']) ? sanitize_text_field($page_data['TypePage']) : '';
        $location_value = isset($page_data['value']) ? sanitize_text_field($page_data['value']) : '';

        // Pobierz ID użytkownika - używamy cookie do identyfikacji
        $user_id = get_current_user_id();

        // Jeśli user_id to 0, ale mamy ciasteczko sesji, spróbujmy odtworzyć sesję
        if ($user_id === 0 && isset($_COOKIE[LOGGED_IN_COOKIE])) {
            $this->debug_log("Wykryto ciasteczko logowania, próba odtworzenia sesji");
            $user = wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in');
            if ($user) {
                $user_id = $user;
                $this->debug_log("Odtworzono sesję dla użytkownika: {$user_id}");
            }
        }

        $this->debug_log("Wyodrębnione dane lokalizacji:", [
            'location' => $location,
            'type_page' => $type_page,
            'location_value' => $location_value,
            'user_id' => $user_id
        ]);

        $criteria = [
            'type_page' => $type_page,
            'location' => $location_value,
            'user_id' => $user_id,
            'npc_id' => $npc_id
        ];

        if (!$npc_id) {
            $this->debug_log("BŁĄD: Nieprawidłowe ID NPC");
            return new \WP_REST_Response([
                'status' => 'error',
                'message' => 'Nieprawidłowe ID NPC'
            ], 400);
        }

        // Pobierz pola ACF dla NPC
        $fields = get_fields($npc_id);
        $dialogs = isset($fields['dialogs']) ? $fields['dialogs'] : [];

        $this->debug_log("Pobrane dialogi dla NPC {$npc_id}:", $dialogs);

        // Filtruj dialogi na podstawie lokalizacji, relacji NPC i innych kryteriów
        $filtered_dialog = $this->get_first_matching_dialog($dialogs, $criteria);
        $this->debug_log("Wybrany dialog po filtrowaniu:", $filtered_dialog);

        // Jeśli znaleziono dialog, filtruj także jego odpowiedzi
        if ($filtered_dialog) {
            $filtered_dialog = $this->filter_answers($filtered_dialog, $criteria);
            $this->debug_log("Dialog po filtrowaniu odpowiedzi:", $filtered_dialog);
        }

        // Przygotuj dane odpowiedzi z pojedynczym dialogiem
        $response_data = [
            'status' => 'success',
            'npc_data' => [
                'id' => $npc_id,
                'name' => get_the_title($npc_id),
                'user_id' => $user_id,
                'dialog' => $filtered_dialog ? $this->simplify_dialog($filtered_dialog) : null
            ]
        ];

        $this->debug_log("Dane odpowiedzi:", $response_data);
        $this->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA NPC =====");

        return new \WP_REST_Response($response_data, 200);
    }

    /**
     * Filtruje odpowiedzi w dialogu na podstawie podanych kryteriów
     *
     * @param array $dialog Dialog z odpowiedziami do filtrowania
     * @param array $criteria Kryteria filtrowania
     * @return array Dialog z przefiltrowanymi odpowiedziami
     */
    private function filter_answers(array $dialog, array $criteria): array
    {
        if (!isset($dialog['anwsers']) || empty($dialog['anwsers'])) {
            return $dialog;
        }

        $filtered_answers = [];
        $default_answer = null;

        foreach ($dialog['anwsers'] as $answer) {
            // Jeśli odpowiedź ma identyfikator 'domyślna', zapisz ją jako domyślną
            if (
                isset($answer['anwser_text']) &&
                (strtolower($answer['anwser_text']) === 'domyślna' ||
                    strtolower($answer['anwser_text']) === 'domyslna')
            ) {
                $default_answer = $answer;
            }

            // Sprawdź czy odpowiedź spełnia kryteria widoczności
            if ($this->answer_matches_criteria($answer, $criteria)) {
                $filtered_answers[] = $answer;
            }
        }

        // Jeśli nie ma odpowiedzi spełniających kryteria, dodaj domyślną (jeśli istnieje)
        if (empty($filtered_answers) && $default_answer !== null) {
            $filtered_answers[] = $default_answer;
        }

        // Jeśli nadal nie ma odpowiedzi, dodaj wszystkie oryginalne
        if (empty($filtered_answers)) {
            $filtered_answers = $dialog['anwsers'];
        }

        $dialog['anwsers'] = $filtered_answers;
        return $dialog;
    }

    /**
     * Sprawdza czy odpowiedź spełnia podane kryteria
     *
     * @param array $answer Odpowiedź do sprawdzenia
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy odpowiedź pasuje do kryteriów
     */
    private function answer_matches_criteria(array $answer, array $criteria): bool
    {
        $location = $criteria['location'] ?? '';

        // Jeśli odpowiedź nie ma ustawień widoczności, zawsze ją pokaż
        if (
            !isset($answer['layout_settings']) ||
            !isset($answer['layout_settings']['visibility_settings']) ||
            empty($answer['layout_settings']['visibility_settings'])
        ) {
            return true;
        }

        $visibility_settings = $answer['layout_settings']['visibility_settings'];
        $logic_operator = isset($answer['layout_settings']['logic_operator']) ?
            $answer['layout_settings']['logic_operator'] : 'and';

        $matches = [];

        // Sprawdź każdy warunek widoczności
        foreach ($visibility_settings as $condition) {
            $result = $this->check_single_condition($condition, $criteria);
            $matches[] = $result;
        }

        // Zastosuj operator logiczny do wszystkich warunków
        if ($logic_operator === 'and') {
            return !in_array(false, $matches, true);
        } else { // 'or'
            return in_array(true, $matches, true);
        }
    }

    /**
     * Upraszcza strukturę dialogu, zostawiając tylko potrzebne pola
     * 
     * @param array $dialog Pełna struktura dialogu
     * @return array Uproszczona struktura dialogu
     */
    private function simplify_dialog(array $dialog): array
    {
        $simplified = [
            'acf_fc_layout' => $dialog['acf_fc_layout'] ?? '',
            'question' => $dialog['question'] ?? '',
            'id_pola' => $dialog['id_pola'] ?? ''
        ];

        // Dodaj odpowiedzi, jeśli istnieją
        if (isset($dialog['anwsers']) && is_array($dialog['anwsers'])) {
            $simplified['anwsers'] = array_map(function ($answer) {
                return [
                    'acf_fc_layout' => $answer['acf_fc_layout'] ?? '',
                    'anwser_text' => $answer['anwser_text'] ?? '',
                    'type_anwser' => $answer['type_anwser'] ?? false,
                    'go_to_id' => $answer['go_to_id'] ?? '0'
                ];
            }, $dialog['anwsers']);
        } else {
            $simplified['anwsers'] = [];
        }

        return $simplified;
    }

    /**
     * Wyodrębnia informację o lokalizacji z URL
     *
     * @param string $url URL do analizy
     * @return string Wyodrębniona lokalizacja
     */
    private function extract_location_from_url(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));

        // Usuń pierwszą część ścieżki jeśli to nazwa motywu lub witryny
        if (count($parts) > 0) {
            if (in_array($parts[0], ['game', 'wp', 'wordpress'])) {
                array_shift($parts);
            }
        }

        return implode('/', $parts);
    }

    /**
     * Zwraca pierwszy dialog, który spełnia podane kryteria
     *
     * @param array $dialogs Wszystkie dialogi NPC
     * @param array $criteria Kryteria filtrowania (lokalizacja, typ strony)
     * @return array|null Pierwszy pasujący dialog lub null jeśli nie znaleziono
     */
    private function get_first_matching_dialog(array $dialogs, array $criteria): ?array
    {
        if (empty($dialogs)) {
            $this->debug_log("Brak dialogów do sprawdzenia");
            return null;
        }

        $default_dialog = null;
        $this->debug_log("Sprawdzanie pasujących dialogów, liczba dialogów: " . count($dialogs));

        foreach ($dialogs as $index => $dialog) {
            $dialog_id = isset($dialog['id_pola']) ? $dialog['id_pola'] : "Dialog #{$index}";
            $this->debug_log("Sprawdzanie dialogu: {$dialog_id}");

            // Zapisz dialog domyślny (zostanie użyty, jeśli żaden inny nie pasuje)
            if (isset($dialog['id_pola']) && $dialog['id_pola'] === 'domyslny') {
                $default_dialog = $dialog;
                $this->debug_log("Znaleziono dialog domyślny: {$dialog_id}");
            }

            // Sprawdź warunki widoczności
            $matches = $this->dialog_matches_criteria($dialog, $criteria);
            $this->debug_log("Dialog {$dialog_id} pasuje do kryteriów: " . ($matches ? 'TAK' : 'NIE'));

            if ($matches) {
                $this->debug_log("Zwracam pasujący dialog: {$dialog_id}");
                return $dialog;
            }
        }

        // Jeśli nie znaleziono pasującego dialogu, użyj domyślnego
        $this->debug_log("Brak pasujących dialogów, zwracam domyślny: " . ($default_dialog ? ($default_dialog['id_pola'] ?? 'Unknown') : 'Brak'));
        return $default_dialog;
    }

    /**
     * Sprawdza czy dialog spełnia podane kryteria
     *
     * @param array $dialog Dialog do sprawdzenia
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy dialog pasuje do kryteriów
     */
    private function dialog_matches_criteria(array $dialog, array $criteria): bool
    {
        $dialog_id = $dialog['id_pola'] ?? 'unknown';

        // Jeśli dialog nie ma ustawień widoczności, zawsze go pokaż
        if (
            !isset($dialog['layout_settings']) ||
            !isset($dialog['layout_settings']['visibility_settings']) ||
            empty($dialog['layout_settings']['visibility_settings'])
        ) {
            $this->debug_log("Dialog {$dialog_id} nie ma ustawień widoczności - pokazuję domyślnie");
            return true;
        }

        $visibility_settings = $dialog['layout_settings']['visibility_settings'];
        $logic_operator = isset($dialog['layout_settings']['logic_operator']) ?
            $dialog['layout_settings']['logic_operator'] : 'and';

        $this->debug_log("Dialog {$dialog_id}, operator logiczny: {$logic_operator}");
        $this->debug_log("Ustawienia widoczności dialogu {$dialog_id}:", $visibility_settings);

        $matches = [];

        // Sprawdź każdy warunek widoczności
        foreach ($visibility_settings as $index => $condition) {
            $condition_type = isset($condition['acf_fc_layout']) ? $condition['acf_fc_layout'] : "Warunek #{$index}";
            $this->debug_log("Sprawdzanie warunku {$condition_type} dla dialogu {$dialog_id}");

            $result = $this->check_single_condition($condition, $criteria);
            $matches[] = $result;

            $this->debug_log("Wynik warunku {$condition_type}: " . ($result ? 'PRAWDA' : 'FAŁSZ'));
        }

        // Zastosuj operator logiczny do wszystkich warunków
        if ($logic_operator === 'and') {
            $result = !in_array(false, $matches, true);
        } else { // 'or'
            $result = in_array(true, $matches, true);
        }

        $this->debug_log("Dialog {$dialog_id} końcowy wynik: " . ($result ? 'PASUJE' : 'NIE PASUJE'));
        return $result;
    }

    /**
     * Sprawdza pojedynczy warunek widoczności
     *
     * @param array $condition Warunek do sprawdzenia
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    private function check_single_condition(array $condition, array $criteria): bool
    {
        $result = false;
        $layout = isset($condition['acf_fc_layout']) ? $condition['acf_fc_layout'] : '';

        $this->debug_log("Sprawdzanie warunku typu: {$layout}");
        $this->debug_log("Dane warunku:", $condition);

        switch ($layout) {
            case 'condition_location':
                $result = $this->check_location_condition($condition, $criteria);
                break;
            case 'condition_npc_relation':
                $result = $this->check_npc_relation_condition($condition, $criteria);
                break;
            case 'condition_inventory':
                $result = $this->check_inventory_condition($condition, $criteria);
                break;
            case 'condition_mission':
                $result = $this->check_mission_condition($condition, $criteria);
                break;
            case 'condition_task':
                $result = $this->check_task_condition($condition, $criteria);
                break;
            default:
                // Dla nieznanych warunków, uznaj że są spełnione
                $this->debug_log("Nieznany warunek typu {$layout} - przyjmuję jako spełniony");
                $result = true;
                break;
        }

        $this->debug_log("Wynik warunku {$layout}: " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
        return $result;
    }

    /**
     * Sprawdza warunek stanu zadania w misji użytkownika
     *
     * @param array $condition Warunek zadania
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    private function check_task_condition(array $condition, array $criteria): bool
    {
        $user_id = $criteria['user_id'] ?? 0;
        $condition_op = $condition['condition'] ?? '';
        $mission_id = isset($condition['mission_id']) ? absint($condition['mission_id']) : 0;
        $task_id = $condition['task_id'] ?? '';
        $required_status = $condition['status'] ?? '';
        
        // Wyciągnij ID NPC z kontekstu dla sprawdzenia statusu NPC-specyficznego
        $npc_id = $criteria['npc_id'] ?? 0;

        $this->debug_log("Sprawdzanie warunku zadania:");
        $this->debug_log("- User ID: {$user_id}");
        $this->debug_log("- Operator warunku: {$condition_op}");
        $this->debug_log("- ID misji: {$mission_id}");
        $this->debug_log("- ID zadania: {$task_id}");
        $this->debug_log("- ID NPC: {$npc_id}");
        $this->debug_log("- Wymagany status: {$required_status}");

        // Jeśli użytkownik nie jest zalogowany lub brak ID misji/zadania, warunek nie jest spełniony
        if (!$user_id || !$mission_id || empty($task_id)) {
            $this->debug_log("- Brak User ID, Mission ID lub Task ID - warunek niespełniony");
            return false;
        }

        // Pobierz dane misji użytkownika
        $mission_field_name = "mission_{$mission_id}";
        $user_mission_data = get_field($mission_field_name, "user_{$user_id}");
        $this->debug_log("- Dane misji użytkownika:", $user_mission_data);

        // Sprawdź czy misja istnieje w danych użytkownika
        if (!$user_mission_data || !is_array($user_mission_data)) {
            $this->debug_log("- Misja nie istnieje w danych użytkownika - warunek niespełniony");
            return false;
        }
        
        // Sprawdzenie czy mamy do czynienia ze specjalną strukturą statusu NPC
        if (strpos($required_status, 'completed_npc') !== false && $npc_id > 0) {
            // Sprawdź czy zadanie istnieje w misji
            if (!isset($user_mission_data['tasks']) || !is_array($user_mission_data['tasks']) || !isset($user_mission_data['tasks'][$task_id])) {
                $this->debug_log("- Zadanie {$task_id} nie istnieje w danych misji - warunek niespełniony");
                return false;
            }
            
            // Sprawdź czy zadanie ma strukturę tablicową (zawierającą dane NPC)
            $task_data = $user_mission_data['tasks'][$task_id];
            $this->debug_log("- Dane zadania:", $task_data);
            
            if (is_array($task_data)) {
                // Sprawdź status NPC w zadaniu
                $npc_field = "npc_{$npc_id}";
                if (isset($task_data[$npc_field])) {
                    $npc_status = $task_data[$npc_field];
                    $this->debug_log("- Status NPC {$npc_id} w zadaniu: {$npc_status}");
                    
                    // Porównaj status NPC z wymaganym (zazwyczaj "completed")
                    $result = ($npc_status === 'completed');
                    $this->debug_log("- Warunek 'completed_npc' (status {$npc_field}: {$npc_status} === completed): " . 
                        ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                    return $result;
                } else {
                    // Sprawdź status dla npc_target_ID
                    $npc_target_field = "npc_target_{$npc_id}";
                    if (isset($task_data[$npc_target_field])) {
                        $npc_status = $task_data[$npc_target_field];
                        $this->debug_log("- Status NPC target {$npc_id} w zadaniu: {$npc_status}");
                        
                        // Porównaj status NPC z wymaganym (zazwyczaj "completed")
                        $result = ($npc_status === 'completed');
                        $this->debug_log("- Warunek 'completed_npc' (status {$npc_target_field}: {$npc_status} === completed): " . 
                            ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                        return $result;
                    }
                }
                
                // Sprawdź ogólny status zadania w strukturze tablicowej
                if (isset($task_data['status'])) {
                    $current_task_status = $task_data['status'];
                    $this->debug_log("- Aktualny ogólny status zadania: {$current_task_status}");
                } else {
                    $this->debug_log("- Brak ogólnego statusu zadania w strukturze tablicowej - warunek niespełniony");
                    return false;
                }
            } else {
                // Jeśli zadanie nie ma struktury tablicowej, uznaj jego wartość za status
                $current_task_status = $task_data;
                $this->debug_log("- Aktualny status zadania (wartość prosta): {$current_task_status}");
            }
        } else {
            // Pobierz aktualny status zadania - standardowa obsługa
            if (!isset($user_mission_data['tasks']) || !is_array($user_mission_data['tasks'])) {
                $this->debug_log("- Brak danych zadań w misji - warunek niespełniony");
                return false;
            }
            
            if (!isset($user_mission_data['tasks'][$task_id])) {
                $this->debug_log("- Zadanie {$task_id} nie istnieje w danych misji - warunek niespełniony");
                return false;
            }
            
            $task_data = $user_mission_data['tasks'][$task_id];
            
            // Sprawdź czy zadanie ma strukturę tablicową czy jest prostą wartością
            if (is_array($task_data)) {
                if (isset($task_data['status'])) {
                    $current_task_status = $task_data['status'];
                    $this->debug_log("- Aktualny status zadania (z tablicy): {$current_task_status}");
                } else {
                    $this->debug_log("- Brak pola status w danych zadania - warunek niespełniony");
                    return false;
                }
            } else {
                $current_task_status = $task_data;
                $this->debug_log("- Aktualny status zadania (wartość prosta): {$current_task_status}");
            }
        }

        // Standardowe sprawdzenie warunku dla statusu zadania
        switch ($condition_op) {
            case 'is':
                // Sprawdź czy zadanie ma dokładnie taki status jak wymagany
                $result = ($current_task_status === $required_status);
                $this->debug_log("- Warunek 'is' ({$current_task_status} === {$required_status}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'is_not':
                // Sprawdź czy zadanie ma inny status niż wymagany
                $result = ($current_task_status !== $required_status);
                $this->debug_log("- Warunek 'is_not' ({$current_task_status} !== {$required_status}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'is_at_least':
                // Sprawdź czy zadanie ma status co najmniej taki jak wymagany
                $status_hierarchy = [
                    'not_started' => 0,
                    'in_progress' => 1,
                    'completed' => 2,
                    'failed' => 3
                ];
                
                $current_level = isset($status_hierarchy[$current_task_status]) ? $status_hierarchy[$current_task_status] : -1;
                $required_level = isset($status_hierarchy[$required_status]) ? $status_hierarchy[$required_status] : -1;
                
                $result = ($current_level >= $required_level);
                $this->debug_log("- Warunek 'is_at_least' ({$current_task_status} [poziom: {$current_level}] >= {$required_status} [poziom: {$required_level}]): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'is_before':
                // Sprawdź czy zadanie ma status niższy niż wymagany
                $status_hierarchy = [
                    'not_started' => 0,
                    'in_progress' => 1,
                    'completed' => 2,
                    'failed' => 3
                ];
                
                $current_level = isset($status_hierarchy[$current_task_status]) ? $status_hierarchy[$current_task_status] : -1;
                $required_level = isset($status_hierarchy[$required_status]) ? $status_hierarchy[$required_status] : -1;
                
                $result = ($current_level < $required_level);
                $this->debug_log("- Warunek 'is_before' ({$current_task_status} [poziom: {$current_level}] < {$required_status} [poziom: {$required_level}]): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            default:
                $this->debug_log("- Nieznany operator warunku zadania: {$condition_op}");
                return false;
        }
    }

    /**
     * Sprawdza warunek stanu misji użytkownika
     *
     * @param array $condition Warunek misji
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    private function check_mission_condition(array $condition, array $criteria): bool
    {
        $user_id = $criteria['user_id'] ?? 0;
        $condition_op = $condition['condition'] ?? '';
        $mission_id = isset($condition['mission_id']) ? absint($condition['mission_id']) : 0;
        $required_status = $condition['status'] ?? '';

        $this->debug_log("Sprawdzanie warunku misji:");
        $this->debug_log("- User ID: {$user_id}");
        $this->debug_log("- Operator warunku: {$condition_op}");
        $this->debug_log("- ID misji: {$mission_id}");
        $this->debug_log("- Wymagany status: {$required_status}");

        // Jeśli użytkownik nie jest zalogowany lub brak ID misji, warunek nie jest spełniony
        if (!$user_id || !$mission_id) {
            $this->debug_log("- Brak User ID lub Mission ID - warunek niespełniony");
            return false;
        }

        // Pobierz dane misji użytkownika
        $mission_field_name = "mission_{$mission_id}";
        $user_mission_data = get_field($mission_field_name, "user_{$user_id}");
        $this->debug_log("- Dane misji użytkownika:", $user_mission_data);

        // Sprawdź czy misja istnieje w danych użytkownika
        if (!$user_mission_data || !is_array($user_mission_data)) {
            $this->debug_log("- Misja nie istnieje w danych użytkownika - warunek niespełniony");
            return false;
        }

        // Pobierz aktualny status misji
        $current_status = isset($user_mission_data['status']) ? $user_mission_data['status'] : '';
        $this->debug_log("- Aktualny status misji: {$current_status}");

        // Sprawdź warunek misji w zależności od operatora
        switch ($condition_op) {
            case 'is':
                // Sprawdź czy misja ma dokładnie taki status jak wymagany
                $result = ($current_status === $required_status);
                $this->debug_log("- Warunek 'is' ({$current_status} === {$required_status}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'is_not':
                // Sprawdź czy misja ma inny status niż wymagany
                $result = ($current_status !== $required_status);
                $this->debug_log("- Warunek 'is_not' ({$current_status} !== {$required_status}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'is_at_least':
                // Sprawdź czy misja ma status co najmniej taki jak wymagany (np. started >= not_started)
                $status_hierarchy = [
                    'not_assigned' => 0,
                    'not_started' => 1,
                    'started' => 2,
                    'completed' => 3,
                    'failed' => 4
                ];
                
                $current_level = isset($status_hierarchy[$current_status]) ? $status_hierarchy[$current_status] : -1;
                $required_level = isset($status_hierarchy[$required_status]) ? $status_hierarchy[$required_status] : -1;
                
                $result = ($current_level >= $required_level);
                $this->debug_log("- Warunek 'is_at_least' ({$current_status} [poziom: {$current_level}] >= {$required_status} [poziom: {$required_level}]): " . ($result ? 'SPEŁNIONY' : 'NIESPEłNIONY'));
                return $result;

            case 'has_task_completed':
                // Sprawdź czy zadanie w misji jest ukończone
                $task_id = $required_status; // W tym przypadku required_status to ID zadania
                
                if (isset($user_mission_data['tasks']) && is_array($user_mission_data['tasks']) && isset($user_mission_data['tasks'][$task_id])) {
                    $task_status = $user_mission_data['tasks'][$task_id];
                    $result = ($task_status === 'completed');
                    $this->debug_log("- Warunek 'has_task_completed' (zadanie {$task_id} ma status: {$task_status}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                    return $result;
                }
                
                $this->debug_log("- Zadanie {$task_id} nie istnieje w danych misji - warunek niespełniony");
                return false;

            default:
                $this->debug_log("- Nieznany operator warunku misji: {$condition_op}");
                return false;
        }
    }

    /**
     * Sprawdza warunek posiadania przedmiotu w ekwipunku
     *
     * @param array $condition Warunek ekwipunku
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    private function check_inventory_condition(array $condition, array $criteria): bool
    {
        $user_id = $criteria['user_id'] ?? 0;
        $condition_op = $condition['condition'] ?? '';
        $item_id = isset($condition['item_id']) ? absint($condition['item_id']) : 0;
        $quantity = isset($condition['quantity']) ? absint($condition['quantity']) : 1;

        $this->debug_log("Sprawdzanie warunku ekwipunku:");
        $this->debug_log("- User ID: {$user_id}");
        $this->debug_log("- Operator warunku: {$condition_op}");
        $this->debug_log("- ID przedmiotu: {$item_id}");
        $this->debug_log("- Wymagana ilość: {$quantity}");

        // Jeśli użytkownik nie jest zalogowany lub brak ID przedmiotu, warunek nie jest spełniony
        if (!$user_id || !$item_id) {
            $this->debug_log("- Brak User ID lub Item ID - warunek niespełniony");
            return false;
        }

        // Pobierz ekwipunek użytkownika
        $user_inventory = $this->get_user_inventory($user_id);
        $this->debug_log("- Ekwipunek użytkownika:", $user_inventory);

        // Oblicz aktualną ilość przedmiotu w ekwipunku użytkownika
        $current_quantity = 0;
        if (!empty($user_inventory)) {
            foreach ($user_inventory as $inventory_item) {
                if ($inventory_item['item_id'] == $item_id) {
                    $current_quantity += intval($inventory_item['quantity']);
                }
            }
        }
        $this->debug_log("- Aktualna ilość przedmiotu {$item_id}: {$current_quantity}");

        // Sprawdź warunek ekwipunku w zależności od operatora
        switch ($condition_op) {
            case 'has_item':
                // Sprawdź czy użytkownik posiada przedmiot w wymaganej ilości
                $result = ($current_quantity >= $quantity);
                $this->debug_log("- Warunek 'has_item' (ma {$current_quantity} >= {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'not_has_item':
            case 'has_not_item': // Obsługuj oba warianty nazwy
                // Sprawdź czy użytkownik nie posiada przedmiotu lub ma mniej niż wymagane
                $result = ($current_quantity < $quantity);
                $this->debug_log("- Warunek '{$condition_op}' (ma {$current_quantity} < {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'quantity_above':
                // Sprawdź czy użytkownik ma więcej niż określona ilość przedmiotu
                $result = ($current_quantity > $quantity);
                $this->debug_log("- Warunek 'quantity_above' (ma {$current_quantity} > {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'quantity_below':
                // Sprawdź czy użytkownik ma mniej niż określona ilość przedmiotu
                $result = ($current_quantity < $quantity);
                $this->debug_log("- Warunek 'quantity_below' (ma {$current_quantity} < {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            case 'quantity_equal':
                // Sprawdź czy użytkownik ma dokładnie określoną ilość przedmiotu
                $result = ($current_quantity == $quantity);
                $this->debug_log("- Warunek 'quantity_equal' (ma {$current_quantity} == {$quantity}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                return $result;

            default:
                $this->debug_log("- Nieznany operator warunku ekwipunku: {$condition_op}");
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
        $this->debug_log("Pobieranie ekwipunku dla użytkownika {$user_id}");
        
        // Jeśli pole items nie istnieje lub jest puste, zwróć pustą tablicę
        if (!$items_field || !is_array($items_field) || empty($items_field)) {
            $this->debug_log("Brak przedmiotów w ekwipunku lub pole nieznalezione");
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
        
        $this->debug_log("Znaleziono " . count($inventory) . " przedmiotów w ekwipunku");
        return $inventory;
    }

    /**
     * Sprawdza warunek lokalizacji
     *
     * @param array $condition Warunek lokalizacji
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    private function check_location_condition(array $condition, array $criteria): bool
    {
        $location = $criteria['location'] ?? '';
        $condition_op = $condition['condition'] ?? 'is';
        $location_type = $condition['location_type'] ?? 'text';

        $this->debug_log("Sprawdzanie warunku lokalizacji:");
        $this->debug_log("- Aktualna lokalizacja: {$location}");
        $this->debug_log("- Operator warunku: {$condition_op}");
        $this->debug_log("- Typ lokalizacji: {$location_type}");

        if ($location_type === 'text') {
            $location_text = $condition['location_text'] ?? '';
            $this->debug_log("- Wymagana lokalizacja: {$location_text}");

            if ($condition_op === 'is') {
                $result = ($location === $location_text);
                $this->debug_log("- Porównanie 'is': " . ($result ? 'PRAWDA' : 'FAŁSZ'));
                return $result;
            } else if ($condition_op === 'is_not') {
                $result = ($location !== $location_text);
                $this->debug_log("- Porównanie 'is_not': " . ($result ? 'PRAWDA' : 'FAŁSZ'));
                return $result;
            } else if ($condition_op === 'contains') {
                $result = (strpos($location, $location_text) !== false);
                $this->debug_log("- Porównanie 'contains': " . ($result ? 'PRAWDA' : 'FAŁSZ'));
                return $result;
            }
        }

        $this->debug_log("- Wynik warunku lokalizacji: FAŁSZ (niepasujący typ lub operator)");
        return false;
    }

    /**
     * Sprawdza warunek relacji z NPC
     *
     * @param array $condition Warunek relacji z NPC
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    private function check_npc_relation_condition(array $condition, array $criteria): bool
    {
        // Używaj ID użytkownika z przekazanych kryteriów, a nie pobieraj ponownie
        $user_id = $criteria['user_id'] ?? 0;
        $npc_id = isset($condition['npc_id']) ? absint($condition['npc_id']) : 0;
        $condition_op = $condition['condition'] ?? '';
        $relation_value = isset($condition['relation_value']) ? intval($condition['relation_value']) : 0;

        $this->debug_log("Sprawdzanie warunku relacji z NPC:");
        $this->debug_log("- User ID: {$user_id}");
        $this->debug_log("- NPC ID: {$npc_id}");
        $this->debug_log("- Operator warunku: {$condition_op}");
        $this->debug_log("- Wymagana wartość relacji: {$relation_value}");

        // Jeśli brak ID NPC, warunek nie jest spełniony
        if (!$npc_id) {
            $this->debug_log("- Brak NPC ID - warunek niespełniony");
            return false;
        }

        // Pobierz wartości relacji i spotkania z NPC dla użytkownika
        if ($user_id > 0) {
            $user_relation = intval(get_field('npc-relation-' . $npc_id, 'user_' . $user_id) ?? 0);
            $user_has_met = (bool)(get_field('npc-meet-' . $npc_id, 'user_' . $user_id) ?? false);
        } else {
            // Dla niezalogowanych użytkowników, przyjmij domyślne wartości
            $user_relation = -100; // Domyślna relacja dla niezalogowanych
            $user_has_met = true; // Zakładamy, że niezalogowani "znają" NPC
            $this->debug_log("- Niezalogowany użytkownik, przyjęto domyślną relację: {$user_relation} i znajomość: " . ($user_has_met ? 'TAK' : 'NIE'));
        }

        $this->debug_log("- Aktualna wartość relacji: {$user_relation}");
        $this->debug_log("- Czy użytkownik spotkał NPC: " . ($user_has_met ? 'TAK' : 'NIE'));

        $result = false;

        // Obsłuż różne warunki relacji
        switch ($condition_op) {
            case 'is_known':
                $result = $user_has_met;
                $this->debug_log("- Warunek 'is_known': " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                break;
            case 'is_not_known':
                $result = !$user_has_met;
                $this->debug_log("- Warunek 'is_not_known': " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                break;
            case 'relation_greater_than':
            case 'relation_above': // Obsługuj oba warianty tak samo
                $result = $user_relation > $relation_value;
                $this->debug_log("- Warunek '{$condition_op}' ({$user_relation} > {$relation_value}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                break;
            case 'relation_less_than':
            case 'relation_below': // Obsługuj oba warianty tak samo
                $result = $user_relation < $relation_value;
                $this->debug_log("- Warunek '{$condition_op}' ({$user_relation} < {$relation_value}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                break;
            case 'relation_equal':
            case 'relation_equals':
                $result = ($user_relation == $relation_value);
                $this->debug_log("- Warunek '{$condition_op}' ({$user_relation} == {$relation_value}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                break;
            default:
                $this->debug_log("- Nieznany operator warunku relacji: {$condition_op}");
                $result = false;
                break;
        }

        return $result;
    }
}

// Inicjalizacja klasy
$npc_popup = new NpcPopup();
