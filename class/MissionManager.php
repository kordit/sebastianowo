<?php

/**
 * Klasa zarządzająca misjami w grze
 * 
 * Nowoczesne podejście obiektowe do zarządzania misjami z obsługą Axios
 * Zapewnia kompatybilność z istniejącym systemem opartym o funkcje
 * 
 * @package Game
 * @version 1.0.0
 */

class MissionManager
{
    /**
     * ID użytkownika
     * @var int
     */
    private $user_id;

    /**
     * Namespace dla REST API
     * @var string
     */
    private $namespace = 'game/v1';

    /**
     * Stałe dla statusów misji
     */
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Stałe dla statusów zadań
     */
    const TASK_NOT_STARTED = 'not_started';
    const TASK_IN_PROGRESS = 'in_progress';
    const TASK_COMPLETED = 'completed';
    const TASK_FAILED = 'failed';
    const TASK_COMPLETED_NPC = 'completed_npc';
    const TASK_FAILED_NPC = 'failed_npc';

    /**
     * Słownik przyjaznych nazw statusów
     * @var array
     */
    private $status_friendly_names = [
        'not_started' => 'Niezaczęta',
        'in_progress' => 'W trakcie',
        'completed' => 'Ukończona',
        'failed' => 'Nieudana',
        'progress_npc' => 'W trakcie'
    ];

    /**
     * Słownik przyjaznych nazw statusów zadań
     * @var array
     */
    private $task_status_friendly_names = [
        'not_started' => 'Niezaczęte',
        'in_progress' => 'W trakcie',
        'completed' => 'Ukończone',
        'failed' => 'Nieudane',
        'progress_npc' => 'W trakcie'
    ];

    /**
     * Konstruktor
     * 
     * @param int|null $user_id ID użytkownika (opcjonalne, domyślnie aktualny użytkownik)
     */
    public function __construct($user_id = null)
    {
        // Jeśli nie podano ID użytkownika, użyj aktualnie zalogowanego
        $this->user_id = $user_id ?: get_current_user_id();

        // Rejestracja endpointów REST API dla Axios
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Rejestracja endpointów REST API dla misji
     */
    public function register_rest_routes()
    {
        // Endpoint do przypisywania misji użytkownikowi
        register_rest_route($this->namespace, '/mission/assign', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_assign_mission'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Endpoint do pobierania informacji o misji
        register_rest_route($this->namespace, '/mission/info', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_mission_info'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Endpoint do aktualizacji statusu zadania w misji
        register_rest_route($this->namespace, '/mission/task/update', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_update_mission_task_status'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Endpoint do pobierania aktywnych misji użytkownika
        register_rest_route($this->namespace, '/mission/active', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_active_missions'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Endpoint do obsługi wielu misji naraz
        register_rest_route($this->namespace, '/mission/batch', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_handle_multiple_missions'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }

    /**
     * Sprawdza uprawnienia użytkownika do endpointów API
     * 
     * @return bool Czy użytkownik ma uprawnienia
     */
    public function check_permissions()
    {
        return is_user_logged_in();
    }

    /**
     * Obsługa endpoint REST API do przypisywania misji
     * 
     * @param WP_REST_Request $request Obiekt żądania
     * @return WP_REST_Response Odpowiedź REST API
     */
    public function rest_assign_mission($request)
    {
        $this->debug_log('====== ROZPOCZĘCIE REST_ASSIGN_MISSION ======');
        $this->debug_log('Wszystkie dane żądania:', $request->get_params());

        // Pobierz parametry z żądania
        $mission_id = $request->get_param('mission_id') ? intval($request->get_param('mission_id')) : 0;
        $npc_id = $request->get_param('npc_id') ? intval($request->get_param('npc_id')) : 0;
        $mission_status = sanitize_text_field($request->get_param('mission_status') ?: self::STATUS_IN_PROGRESS);
        $mission_task_id = sanitize_text_field($request->get_param('mission_task_id') ?: null);
        $mission_task_status = sanitize_text_field($request->get_param('mission_task_status') ?: self::TASK_IN_PROGRESS);

        $this->debug_log('Parametry: mission_id=' . $mission_id . ', npc_id=' . $npc_id .
            ', mission_status=' . $mission_status . ', mission_task_id=' . $mission_task_id .
            ', mission_task_status=' . $mission_task_status);

        if (!$mission_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Nieprawidłowe ID misji'
            ], 400);
        }

        // Sprawdź czy misja istnieje
        $mission = get_post($mission_id);
        if (!$mission || $mission->post_type !== 'mission') {
            $this->debug_log('BŁĄD: Misja o ID ' . $mission_id . ' nie istnieje');
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Misja nie istnieje'
            ], 404);
        }

        // Przypisz misję używając metody klasy
        $result = $this->assign_mission(
            $mission_id,
            $mission_status,
            $mission_task_id,
            $mission_task_status,
            $npc_id
        );

        if ($result['success']) {
            // Pobierz numer z task_id, jeśli istnieje (np. "dojedz-do-sebastianowa_2" -> "2")
            $task_num = '';
            if ($mission_task_id && preg_match('/_(\d+)$/', $mission_task_id, $matches)) {
                $task_num = $matches[1];
            }

            // Określ sekwencje zadań w misji
            $task_sequence = $this->get_mission_task_sequence($mission_id);

            // Rozszerz odpowiedź o dodatkowe informacje
            $response = [
                'success' => true,
                'message' => $result['message'],
                'mission_id' => $mission_id,
                'mission_title' => $mission->post_title,
                'task_id' => $result['first_task_id'],
                'task_num' => $task_num,
                'task_name' => $result['task_name'],
                'task_sequence' => $task_sequence,
                'npc_id' => $npc_id,
                'messages' => $result['messages']
            ];

            $this->debug_log('Odpowiedź dla sukcesu misji:', $response);
            return new WP_REST_Response($response, 200);
        } else {
            $this->debug_log('Błąd podczas przypisywania misji:', $result['message']);
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
    }

    /**
     * Obsługa endpoint REST API do pobierania informacji o misji
     * 
     * @param WP_REST_Request $request Obiekt żądania
     * @return WP_REST_Response Odpowiedź REST API
     */
    public function rest_get_mission_info($request)
    {
        $this->debug_log('====== ROZPOCZĘCIE REST_GET_MISSION_INFO ======');
        $this->debug_log('Wszystkie dane żądania:', $request->get_params());

        $mission_id = $request->get_param('mission_id') ? intval($request->get_param('mission_id')) : 0;

        if (!$mission_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Nieprawidłowe ID misji'
            ], 400);
        }

        $mission = get_post($mission_id);
        if (!$mission || $mission->post_type !== 'mission') {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Misja nie istnieje'
            ], 404);
        }

        // Sprawdź czy mamy wskazane konkretne zadanie
        $specified_task_id = $request->get_param('mission_task_id') ? sanitize_text_field($request->get_param('mission_task_id')) : null;
        $mission_info = $this->get_mission_info($mission_id, $specified_task_id);

        if (!$mission_info) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Nie udało się pobrać informacji o misji'
            ], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $mission_info
        ], 200);
    }

    /**
     * Obsługa endpoint REST API do aktualizacji statusu zadania
     * 
     * @param WP_REST_Request $request Obiekt żądania
     * @return WP_REST_Response Odpowiedź REST API
     */
    public function rest_update_mission_task_status($request)
    {
        $this->debug_log('====== ROZPOCZĘCIE REST_UPDATE_MISSION_TASK_STATUS ======');
        $this->debug_log('Wszystkie dane żądania:', $request->get_params());

        $mission_id = $request->get_param('mission_id') ? intval($request->get_param('mission_id')) : 0;
        $mission_task_id = $request->get_param('mission_task_id') ? sanitize_text_field($request->get_param('mission_task_id')) : null;
        $mission_task_status = $request->get_param('mission_task_status') ? sanitize_text_field($request->get_param('mission_task_status')) : null;
        $npc_id = $request->get_param('npc_id') ? intval($request->get_param('npc_id')) : 0;

        if (!$mission_id || !$mission_task_id || !$mission_task_status) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Brakuje wymaganych parametrów'
            ], 400);
        }

        $result = $this->update_task_status($mission_id, $mission_task_id, $mission_task_status, $npc_id);

        if ($result['success']) {
            return new WP_REST_Response([
                'success' => true,
                'message' => $result['message'],
                'mission_id' => $mission_id,
                'task_id' => $mission_task_id,
                'new_status' => $mission_task_status,
                'messages' => $result['messages'] ?? []
            ], 200);
        } else {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
    }

    /**
     * Obsługa endpoint REST API do pobierania aktywnych misji użytkownika
     * 
     * @param WP_REST_Request $request Obiekt żądania
     * @return WP_REST_Response Odpowiedź REST API
     */
    public function rest_get_active_missions($request)
    {
        $this->debug_log('====== ROZPOCZĘCIE REST_GET_ACTIVE_MISSIONS ======');

        $active_missions = $this->get_user_active_missions();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'missions' => $active_missions,
                'count' => count($active_missions)
            ]
        ], 200);
    }

    /**
     * Obsługa endpoint REST API do przetwarzania wielu misji naraz
     * 
     * @param WP_REST_Request $request Obiekt żądania
     * @return WP_REST_Response Odpowiedź REST API
     */
    public function rest_handle_multiple_missions($request)
    {
        $this->debug_log('====== ROZPOCZĘCIE REST_HANDLE_MULTIPLE_MISSIONS ======');
        $missions = $request->get_param('missions');

        if (!is_array($missions) || empty($missions)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Brak misji do przetworzenia'
            ], 400);
        }

        $results = $this->handle_multiple_missions($missions);

        return new WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200);
    }

    /**
     * Przypisuje lub aktualizuje misję dla użytkownika
     * 
     * @param int    $mission_id        ID misji
     * @param string $mission_status    Status misji
     * @param string $mission_task_id   ID zadania w misji (opcjonalne)
     * @param string $mission_task_status Status zadania
     * @param int    $npc_id            ID NPC (opcjonalne)
     * @return array Rezultat operacji
     */
    public function assign_mission($mission_id, $mission_status = 'in_progress', $mission_task_id = null, $mission_task_status = 'in_progress', $npc_id = 0)
    {
        $this->debug_log('====== ROZPOCZĘCIE ASSIGN_MISSION ======');
        $this->debug_log('Parametry: mission_id=' . $mission_id . ', mission_status=' . $mission_status .
            ', mission_task_id=' . $mission_task_id . ', mission_task_status=' . $mission_task_status .
            ', npc_id=' . $npc_id);

        // 1. Klucz misji i pobieranie istniejącej misji
        $mission_meta_key = 'mission_' . $mission_id;
        $this->debug_log('Klucz meta dla misji: ' . $mission_meta_key);

        $existing_mission = get_field($mission_meta_key, 'user_' . $this->user_id);
        $this->debug_log('Istniejąca misja: ', $existing_mission);

        // 2. Przygotowanie podstawowej struktury misji
        $mission_data = is_array($existing_mission) ? $existing_mission : [
            'status' => self::STATUS_NOT_STARTED,
            'assigned_date' => '',
            'completion_date' => '',
            'tasks' => []
        ];

        $this->debug_log('Przygotowana struktura misji: ', $mission_data);

        // Zapewnienie, że tablica tasks istnieje
        if (!isset($mission_data['tasks']) || !is_array($mission_data['tasks'])) {
            $mission_data['tasks'] = [];
            $this->debug_log('Zainicjowano pustą tablicę tasks dla misji ' . $mission_id);
        }

        // 3. Ustawienie statusu misji
        $mission_data['status'] = $mission_status;

        // 4. Ustawienie daty przypisania (tylko jeśli nie była ustawiona)
        if (empty($mission_data['assigned_date'])) {
            $mission_data['assigned_date'] = current_time('mysql');
        }

        // 5. Ustawienie daty zakończenia (jeśli status to completed)
        if ($mission_status === self::STATUS_COMPLETED) {
            $mission_data['completion_date'] = current_time('mysql');
        }

        // 6. Obsługa zadania
        $first_task_id = null;
        $task_name = 'Nieznane zadanie';

        // Obsługa specjalnych statusów NPC
        $updated_npc = false;
        $npc_status = null;

        // Sprawdzanie statusów NPC
        if ($mission_task_status === self::TASK_COMPLETED_NPC || $mission_task_status === self::TASK_FAILED_NPC) {
            $mission_task_status = ($mission_task_status === self::TASK_COMPLETED_NPC) ? self::TASK_COMPLETED : self::TASK_FAILED;
            if ($npc_id > 0) {
                $npc_status = $mission_task_status;
                $updated_npc = true;
                $this->debug_log('Aktualizacja statusu NPC ' . $npc_id . ' na ' . $npc_status . ' dla zadania ' . $mission_task_id);
            }
        }

        if ($mission_task_id) {
            // Jeśli podano ID zadania, aktualizujemy je
            if (!isset($mission_data['tasks']) || !is_array($mission_data['tasks'])) {
                $mission_data['tasks'] = [];
            }

            // Debugowanie struktury zadania
            if (isset($mission_data['tasks'][$mission_task_id])) {
                $this->debug_log('Struktura istniejącego zadania ' . $mission_task_id . ':', $mission_data['tasks'][$mission_task_id]);
            }

            // Sprawdzanie czy zadanie jest stringiem zamiast tablicy i naprawianie
            if (isset($mission_data['tasks'][$mission_task_id]) && !is_array($mission_data['tasks'][$mission_task_id])) {
                $old_status = $mission_data['tasks'][$mission_task_id];
                $this->debug_log('Naprawianie nieprawidłowej struktury zadania ' . $mission_task_id . ' (było: ' . $old_status . ')');
                $mission_data['tasks'][$mission_task_id] = [
                    'status' => $old_status,
                    'start_date' => current_time('mysql')
                ];
            }

            // Aktualizacja lub dodanie zadania
            if (!isset($mission_data['tasks'][$mission_task_id])) {
                $mission_data['tasks'][$mission_task_id] = [
                    'status' => $mission_task_status,
                    'start_date' => current_time('mysql')
                ];

                // Jeśli tworzymy nowe zadanie i podany jest NPC, ustawiamy status NPC
                if ($npc_id > 0) {
                    if ($updated_npc) {
                        // Użyj określonego statusu NPC
                        $npc_key = 'npc_' . $npc_id;
                        $mission_data['tasks'][$mission_task_id][$npc_key] = $npc_status;
                        $this->debug_log('Utworzono nowe zadanie, ustawiono status NPC ' . $npc_id . ' na ' . $npc_status);
                    } else {
                        // Domyślnie ustaw not_started dla nowego NPC
                        $npc_key = 'npc_' . $npc_id;
                        $mission_data['tasks'][$mission_task_id][$npc_key] = self::STATUS_NOT_STARTED;
                        $this->debug_log('Utworzono nowe zadanie, ustawiono status NPC ' . $npc_id . ' na not_started');
                    }
                }

                // Pobierz informacje o misji, aby sprawdzić czy zadanie ma innych NPC
                $mission_tasks = get_field('mission_tasks', $mission_id);
                if (is_array($mission_tasks)) {
                    foreach ($mission_tasks as $task) {
                        $task_id = isset($task['task_id']) ? $task['task_id'] : '';
                        if ($task_id === $mission_task_id && isset($task['task_type']) && $task['task_type'] === 'checkpoint_npc') {
                            if (!empty($task['task_checkpoint_npc']) && is_array($task['task_checkpoint_npc'])) {
                                // Ustaw status wszystkich NPC w zadaniu jako not_started
                                foreach ($task['task_checkpoint_npc'] as $npc_info) {
                                    if (!empty($npc_info['npc'])) {
                                        $task_npc_id = $npc_info['npc'];
                                        $task_npc_key = 'npc_' . $task_npc_id;
                                        // Nie nadpisujemy już ustawionego NPC
                                        if (!isset($mission_data['tasks'][$mission_task_id][$task_npc_key])) {
                                            $mission_data['tasks'][$mission_task_id][$task_npc_key] = self::STATUS_NOT_STARTED;
                                            $this->debug_log('Ustawiono status NPC ' . $task_npc_id . ' na not_started w nowym zadaniu');
                                        }
                                    }
                                }
                            }
                            break;
                        }
                    }
                }
            } else {
                // Jeśli zadanie już istnieje, aktualizujemy jego status
                $mission_data['tasks'][$mission_task_id]['status'] = $mission_task_status;

                // Jeśli podano NPC, zaktualizuj jego status
                if ($npc_id > 0 && $updated_npc) {
                    $npc_key = 'npc_' . $npc_id;
                    $mission_data['tasks'][$mission_task_id][$npc_key] = $npc_status;
                    $this->debug_log('Zaktualizowano status NPC ' . $npc_id . ' na ' . $npc_status . ' dla zadania ' . $mission_task_id);
                }
            }

            $first_task_id = $mission_task_id;

            // Pobierz nazwę zadania
            $mission_tasks = get_field('mission_tasks', $mission_id);
            if (is_array($mission_tasks)) {
                foreach ($mission_tasks as $task) {
                    $task_id = isset($task['task_id']) ? $task['task_id'] : '';
                    $clean_task_id = preg_replace('/_\d+$/', '', $task_id);
                    $clean_mission_task_id = preg_replace('/_\d+$/', '', $mission_task_id);

                    if (
                        $task_id === $mission_task_id ||
                        $clean_task_id === $clean_mission_task_id ||
                        strpos($task_id, $clean_mission_task_id) !== false ||
                        strpos($mission_task_id, $clean_task_id) !== false
                    ) {
                        $task_name = isset($task['task_title']) && !empty($task['task_title']) ?
                            $task['task_title'] : $task_name;
                        $this->debug_log('Znaleziono zadanie: ' . $task_name);
                        break;
                    }
                }
            }
        } else {
            // Jeśli nie podano ID zadania, używamy pierwszego zadania z misji
            $mission_tasks = get_field('mission_tasks', $mission_id);

            if (is_array($mission_tasks) && !empty($mission_tasks)) {
                $first_task = $mission_tasks[0];
                $first_task_id = isset($first_task['task_id']) ? $first_task['task_id'] : 'task_0';
                $task_name = isset($first_task['task_title']) ? $first_task['task_title'] : 'Zadanie 1';

                if (!isset($mission_data['tasks'][$first_task_id])) {
                    $mission_data['tasks'][$first_task_id] = [
                        'status' => $mission_task_status,
                        'start_date' => current_time('mysql')
                    ];
                } else {
                    $mission_data['tasks'][$first_task_id]['status'] = $mission_task_status;
                }
            }
        }

        // 7. Generowanie komunikatów o zmianach
        $messages = [];
        $mission_post = get_post($mission_id);
        $mission_title = $mission_post ? $mission_post->post_title : 'Nieznana misja';

        // Sprawdź czy zmienił się status misji
        if (isset($existing_mission['status']) && $existing_mission['status'] !== $mission_data['status']) {
            // Użyj przyjaznej nazwy statusu
            $friendly_status = isset($this->status_friendly_names[$mission_data['status']]) ?
                $this->status_friendly_names[$mission_data['status']] : $mission_data['status'];

            $messages[] = "Misja \"{$mission_title}\" jest teraz {$friendly_status}";
        }

        // Sprawdź czy zmienił się status zadania
        if ($mission_task_id) {
            // Pobierz czyste ID zadania bez numerów i podkreślnika na końcu
            $clean_display_id = preg_replace('/_\d+$/', '', $mission_task_id);

            // Pobierz przyjazną nazwę statusu zadania
            $friendly_task_status = isset($this->task_status_friendly_names[$mission_task_status]) ?
                $this->task_status_friendly_names[$mission_task_status] : $mission_task_status;

            // Sprawdź czy zadanie istniało wcześniej i czy zmienił się jego status
            if (!isset($existing_mission['tasks'][$mission_task_id])) {
                // Nowe zadanie
                $messages[] = "Zadanie \"{$task_name}\" zostało rozpoczęte";
            } elseif (
                is_array($existing_mission['tasks'][$mission_task_id]) &&
                isset($existing_mission['tasks'][$mission_task_id]['status']) &&
                $existing_mission['tasks'][$mission_task_id]['status'] !== $mission_task_status
            ) {
                // Zmienił się status zadania (tablica)
                $messages[] = "Zadanie \"{$task_name}\" jest teraz {$friendly_task_status}";
            } elseif (
                !is_array($existing_mission['tasks'][$mission_task_id]) &&
                $existing_mission['tasks'][$mission_task_id] !== $mission_task_status
            ) {
                // Zmienił się status zadania (string)
                $messages[] = "Zadanie \"{$task_name}\" jest teraz {$friendly_task_status}";
            }
        }

        // Jeśli nie wykryto zmian, dodaj ogólny komunikat
        if (empty($messages)) {
            $messages[] = "Aktualizacja misji \"{$mission_title}\" zakończona pomyślnie";
        }

        // Poprawienie formatu komunikatu - usuń nazwę misji w komunikacie
        if (count($messages) > 1 && isset($existing_mission['status']) && $existing_mission['status'] !== $mission_data['status']) {
            // Jeśli mamy komunikat o misji i zadaniu, uprość komunikat o misji
            foreach ($messages as $key => $message) {
                if (strpos($message, 'Misja "') === 0) {
                    $friendly_status = isset($this->status_friendly_names[$mission_data['status']]) ?
                        $this->status_friendly_names[$mission_data['status']] : $mission_data['status'];
                    $messages[$key] = "{$friendly_status}";
                    break;
                }
            }
        }

        $final_message = implode('. ', $messages);

        // 8. Zapisanie danych
        // Używamy bezpośrednio update_user_meta, które jest bardziej niezawodne niż update_field
        $success = update_user_meta($this->user_id, $mission_meta_key, $mission_data);

        // 9. Zapewnienie kompatybilności z ACF i czyszczenie cache
        update_field($mission_meta_key, $mission_data, 'user_' . $this->user_id);

        // 10. Usuwamy cache, by zmiany były od razu widoczne
        clean_user_cache($this->user_id);
        wp_cache_delete($this->user_id, 'user_meta');

        // 11. Zwracamy wynik z dodatkowymi informacjami
        return [
            'success' => $success,
            'message' => $success ? $final_message : 'Błąd podczas aktualizacji misji',
            'first_task_id' => $first_task_id,
            'task_name' => $task_name,
            'messages' => $messages
        ];
    }

    /**
     * Aktualizuje status zadania w misji
     * 
     * @param int    $mission_id ID misji
     * @param string $task_id ID zadania
     * @param string $task_status Nowy status zadania
     * @param int    $npc_id ID NPC (opcjonalne)
     * @return array Rezultat operacji
     */
    public function update_task_status($mission_id, $task_id, $task_status, $npc_id = 0)
    {
        // Tutaj używamy funkcji assign_mission, ale tylko z aktualizacją zadania
        return $this->assign_mission(
            $mission_id,
            null, // Nie zmieniamy statusu misji
            $task_id,
            $task_status,
            $npc_id
        );
    }

    /**
     * Pobiera informacje o misji
     * 
     * @param int    $mission_id ID misji
     * @param string $specified_task_id Opcjonalne ID zadania
     * @return array Informacje o misji
     */
    public function get_mission_info($mission_id, $specified_task_id = null)
    {
        $mission = get_post($mission_id);
        if (!$mission || $mission->post_type !== 'mission') {
            return null;
        }

        $mission_tasks = get_field('mission_tasks', $mission_id);
        $first_task_id = null;
        $tasks = [];
        $found_specified = false;

        if (is_array($mission_tasks) && !empty($mission_tasks)) {
            // Jeśli mamy wskazane konkretne zadanie, znajdź je
            if ($specified_task_id) {
                $this->debug_log('Szukam konkretnego zadania: ' . $specified_task_id);
                $clean_specified_id = preg_replace('/_\d+$/', '', $specified_task_id);

                foreach ($mission_tasks as $index => $task) {
                    $task_id = isset($task['task_id']) ? $task['task_id'] : 'task_' . $index;
                    $clean_task_id = preg_replace('/_\d+$/', '', $task_id);

                    // Dokładne dopasowanie lub dopasowanie po usunięciu sufiksów
                    if (
                        $task_id === $specified_task_id ||
                        $clean_task_id === $clean_specified_id ||
                        strpos($task_id, $clean_specified_id) !== false ||
                        strpos($specified_task_id, $clean_task_id) !== false
                    ) {
                        $first_task_id = $task_id;
                        $tasks[$task_id] = $task['task_title'] ?? ('Zadanie ' . ($index + 1));
                        $found_specified = true;
                        $this->debug_log('Znaleziono konkretne zadanie: ' . $task_id);
                        break;
                    }
                }
            }

            // Jeśli nie znaleziono konkretnego zadania lub nie wskazano żadnego, dodaj wszystkie
            if (!$found_specified) {
                foreach ($mission_tasks as $index => $task) {
                    $task_id = isset($task['task_id']) ? $task['task_id'] : 'task_' . $index;
                    if ($index === 0) {
                        $first_task_id = $task_id;
                    }
                    $tasks[$task_id] = $task['task_title'] ?? ('Zadanie ' . ($index + 1));
                }
            }
        }

        // Używamy specified_task_id jeśli istnieje, w przeciwnym razie first_task_id
        $task_id_to_return = $specified_task_id ?: $first_task_id;

        // Pobierz nazwę zadania dla pierwszego/wybranego zadania
        $task_name = '';
        if ($first_task_id && isset($tasks[$first_task_id])) {
            $task_name = $tasks[$first_task_id];
        }

        // Przygotowanie odpowiedzi
        return [
            'mission_id' => $mission_id,
            'mission_title' => $mission->post_title,
            'mission_description' => $mission->post_content,
            'task_id' => $task_id_to_return,
            'task_name' => $task_name,
            'conversation' => $this->get_mission_conversation($mission_id, $task_id_to_return)
        ];
    }

    /**
     * Pobiera dialogu dla misji/zadania
     * 
     * @param int    $mission_id ID misji
     * @param string $task_id ID zadania
     * @return array Dane dialogu
     */
    private function get_mission_conversation($mission_id, $task_id)
    {
        // To jest uproszczona implementacja - w rzeczywistym przypadku można pobrać faktyczny dialog
        return [
            'question' => get_field('mission_description', $mission_id) ?: 'Co chcesz zrobić?',
            'answers' => [
                [
                    'anwser_text' => 'Wykonam to zadanie!',
                    'go_to_id' => '0' // 0 oznacza koniec dialogu
                ]
            ]
        ];
    }

    /**
     * Pobiera sekwencję zadań w misji
     * 
     * @param int $mission_id ID misji
     * @return array Sekwencja zadań
     */
    public function get_mission_task_sequence($mission_id)
    {
        $mission_tasks = get_field('mission_tasks', $mission_id);
        $task_sequence = [];

        if (is_array($mission_tasks)) {
            foreach ($mission_tasks as $index => $task) {
                $task_id = isset($task['task_id']) ? $task['task_id'] : '';
                $task_title = isset($task['task_title']) ? $task['task_title'] : 'Zadanie ' . ($index + 1);

                if (!empty($task_id)) {
                    $task_sequence[] = [
                        'id' => $task_id,
                        'title' => $task_title,
                        'position' => $index
                    ];
                }
            }
        }

        return $task_sequence;
    }

    /**
     * Pobiera aktywne misje użytkownika
     * 
     * @return array Aktywne misje
     */
    public function get_user_active_missions()
    {
        $user_meta = get_user_meta($this->user_id);
        $active_missions = [];

        if (!empty($user_meta)) {
            foreach ($user_meta as $key => $value) {
                if (strpos($key, 'mission_') === 0) {
                    $mission_data = maybe_unserialize($value[0]);

                    // Sprawdź czy misja jest aktywna
                    if (isset($mission_data['status']) && $mission_data['status'] === self::STATUS_IN_PROGRESS) {
                        $mission_id = intval(str_replace('mission_', '', $key));
                        $mission = get_post($mission_id);

                        if ($mission) {
                            $active_missions[] = [
                                'id' => $mission_id,
                                'title' => $mission->post_title,
                                'status' => $mission_data['status'],
                                'assigned_date' => $mission_data['assigned_date'] ?? '',
                                'tasks_count' => isset($mission_data['tasks']) ? count($mission_data['tasks']) : 0,
                                'tasks' => $mission_data['tasks'] ?? []
                            ];
                        }
                    }
                }
            }
        }

        return $active_missions;
    }

    /**
     * Obsługuje wiele misji naraz
     * 
     * @param array $missions Tablica z danymi misji
     * @return array Wyniki operacji dla każdej misji
     */
    public function handle_multiple_missions($missions)
    {
        $results = [];

        foreach ($missions as $mission_data) {
            $mission_id = isset($mission_data['mission_id']) ? intval($mission_data['mission_id']) : 0;
            $mission_status = isset($mission_data['mission_status']) ? $mission_data['mission_status'] : self::STATUS_IN_PROGRESS;
            $mission_task_id = isset($mission_data['mission_task_id']) ? $mission_data['mission_task_id'] : null;
            $mission_task_status = isset($mission_data['mission_task_status']) ? $mission_data['mission_task_status'] : self::TASK_IN_PROGRESS;
            $npc_id = isset($mission_data['npc_id']) ? intval($mission_data['npc_id']) : 0;

            if (!$mission_id) {
                $results[] = [
                    'success' => false,
                    'message' => 'Nieprawidłowe ID misji',
                    'data' => $mission_data
                ];
                continue;
            }

            $result = $this->assign_mission(
                $mission_id,
                $mission_status,
                $mission_task_id,
                $mission_task_status,
                $npc_id
            );

            $results[] = [
                'success' => $result['success'],
                'message' => $result['message'],
                'mission_id' => $mission_id,
                'mission_task_id' => $mission_task_id ?: $result['first_task_id'],
                'messages' => $result['messages']
            ];
        }

        return $results;
    }

    /**
     * Funkcja do logowania debugowania
     * 
     * @param string $message Wiadomość do zalogowania
     * @param mixed $data Opcjonalne dane do zalogowania
     */
    private function debug_log($message, $data = null)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = '[MissionManager] ' . $message;

            if ($data !== null) {
                if (is_array($data) || is_object($data)) {
                    $log_message .= ' ' . print_r($data, true);
                } else {
                    $log_message .= ' ' . $data;
                }
            }

            error_log($log_message);
        }
    }
}

/**
 * Inicjalizacja menedżera misji globalne
 */
function init_mission_manager()
{
    // Tworzymy instancję menedżera misji
    global $missionManager;
    $missionManager = new MissionManager();

    // Dla kompatybilności z istniejącym kodem - mapowanie globalnych funkcji na metody klasy
    function ajax_assign_mission_to_user()
    {
        global $missionManager;
        $missionManager->rest_assign_mission(new WP_REST_Request('POST', '/game/v1/mission/assign'));
        exit;
    }

    function ajax_get_mission_info()
    {
        global $missionManager;
        $missionManager->rest_get_mission_info(new WP_REST_Request('GET', '/game/v1/mission/info'));
        exit;
    }

    function ajax_update_mission_task_status()
    {
        global $missionManager;
        $missionManager->rest_update_mission_task_status(new WP_REST_Request('POST', '/game/v1/mission/task/update'));
        exit;
    }

    // Rejestracja starych punktów końcowych AJAX
    add_action('wp_ajax_assign_mission_to_user', 'ajax_assign_mission_to_user');
    add_action('wp_ajax_get_mission_info', 'ajax_get_mission_info');
    add_action('wp_ajax_update_mission_task_status', 'ajax_update_mission_task_status');
}
add_action('init', 'init_mission_manager');

// Udostępnij globalną funkcję handleMultipleMissions dla JS
function handle_multiple_missions_ajax()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Nie jesteś zalogowany']);
    }

    $missions = isset($_POST['missions']) ? $_POST['missions'] : [];

    if (empty($missions)) {
        wp_send_json_error(['message' => 'Brak misji do przetworzenia']);
    }

    global $missionManager;
    $results = $missionManager->handle_multiple_missions($missions);

    wp_send_json_success(['messages' => $results]);
}
add_action('wp_ajax_handle_multiple_missions', 'handle_multiple_missions_ajax');

// Dodanie funkcji dla JS
add_action('wp_footer', function () {
    if (is_user_logged_in()) {
?>
        <script>
            // Globalne funkcje dla zarządzania misjami
            window.missionManager = {
                // Przypisuje misję do użytkownika
                assignMission: function(missionId, options = {}) {
                    return axios({
                        method: 'POST',
                        url: '/wp-json/game/v1/mission/assign',
                        data: {
                            mission_id: missionId,
                            mission_status: options.missionStatus || 'in_progress',
                            mission_task_id: options.taskId || null,
                            mission_task_status: options.taskStatus || 'in_progress',
                            npc_id: options.npcId || 0
                        }
                    });
                },

                // Pobiera informacje o misji
                getMissionInfo: function(missionId, taskId = null) {
                    let params = {
                        mission_id: missionId
                    };
                    if (taskId) params.mission_task_id = taskId;

                    return axios({
                        method: 'GET',
                        url: '/wp-json/game/v1/mission/info',
                        params: params
                    });
                },

                // Aktualizuje status zadania w misji
                updateTaskStatus: function(missionId, taskId, status, npcId = 0) {
                    return axios({
                        method: 'POST',
                        url: '/wp-json/game/v1/mission/task/update',
                        data: {
                            mission_id: missionId,
                            mission_task_id: taskId,
                            mission_task_status: status,
                            npc_id: npcId
                        }
                    });
                },

                // Pobiera aktywne misje użytkownika
                getActiveMissions: function() {
                    return axios({
                        method: 'GET',
                        url: '/wp-json/game/v1/mission/active'
                    });
                },

                // Obsługuje wiele misji naraz
                handleMultipleMissions: function(missions) {
                    return axios({
                        method: 'POST',
                        url: '/wp-json/game/v1/mission/batch',
                        data: {
                            missions: missions
                        }
                    });
                }
            };
        </script>
<?php
    }
});
