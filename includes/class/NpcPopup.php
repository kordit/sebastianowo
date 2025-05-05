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
                    'validate_callback' => function($param) {
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
        $user_id = get_current_user_id();
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
        // Dla niezalogowanych użytkowników (user_id = 0), przyjmujemy domyślne wartości
        if ($user_id > 0) {
            $user_relation = intval(get_field('npc-relation-' . $npc_id, 'user_' . $user_id) ?? 0);
            $user_has_met = (bool)(get_field('npc-meet-' . $npc_id, 'user_' . $user_id) ?? false);
        } else {
            // Dla niezalogowanych użytkowników, przyjmij domyślne wartości
            // Możesz dostosować te wartości według potrzeb gry
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
                $result = $user_relation > $relation_value;
                $this->debug_log("- Warunek 'relation_greater_than' ({$user_relation} > {$relation_value}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                break;
            case 'relation_above':
                $result = $user_relation > $relation_value;
                $this->debug_log("- Warunek 'relation_above' ({$user_relation} > {$relation_value}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                break;
            case 'relation_less_than':
                $result = $user_relation < $relation_value;
                $this->debug_log("- Warunek 'relation_less_than' ({$user_relation} < {$relation_value}): " . ($result ? 'SPEŁNIONY' : 'NIESPEłNIONY'));
                break;
            case 'relation_below':
                $result = $user_relation < $relation_value;
                $this->debug_log("- Warunek 'relation_below' ({$user_relation} < {$relation_value}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
                break;
            case 'relation_equals':
            case 'relation_equal':
                $result = ($user_relation == $relation_value);
                $this->debug_log("- Warunek 'relation_equals' ({$user_relation} == {$relation_value}): " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
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
