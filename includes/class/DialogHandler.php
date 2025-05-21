<?php

/**
 * Nowa, uproszczona implementacja DialogHandler wykorzystująca rozszerzoną klasę DialogManager
 * 
 * Ta klasa będzie zastępować oryginalną klasę DialogHandler
 */

require_once get_template_directory() . '/includes/class/NpcPopup/NpcLogger.php';
require_once get_template_directory() . '/includes/class/NpcPopup/DialogManager.php';
require_once get_template_directory() . '/includes/class/NpcPopup/LocationExtractor.php';
require_once get_template_directory() . '/includes/class/NpcPopup/UserContext.php';
require_once get_template_directory() . '/includes/class/NpcPopup/ContextValidator.php';
require_once get_template_directory() . '/includes/class/ManagerUser.php';

class DialogHandler
{
    /**
     * Przestrzeń nazw dla endpointu REST API
     *
     * @var string
     */
    private const API_NAMESPACE = 'game/v1';

    /**
     * Ścieżka endpointu dla obsługi dialogów
     *
     * @var string
     */
    private const DIALOG_ROUTE = '/dialog';

    /**
     * Minimalny czas między transakcjami (w sekundach)
     *
     * @var int
     */
    private const TRANSACTION_COOLDOWN = 2;

    /**
     * Inicjalizacja klasy DialogHandler
     *
     * Rejestruje endpointy REST API i inne niezbędne akcje.
     *
     * @return void
     */
    public static function init(): void
    {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    /**
     * Rejestracja tras REST API
     *
     * @return void
     */
    public static function register_routes(): void
    {
        register_rest_route(
            self::API_NAMESPACE,
            self::DIALOG_ROUTE,
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'handle_dialog_request'],
                'permission_callback' => [self::class, 'check_permission'],
                'args'                => self::get_dialog_args(),
            ]
        );
    }

    /**
     * Sprawdzenie uprawnień do korzystania z endpointu
     *
     * @param WP_REST_Request $request Obiekt żądania.
     * @return bool|WP_Error True jeśli użytkownik ma uprawnienia, w przeciwnym wypadku obiekt WP_Error.
     */
    public static function check_permission(\WP_REST_Request $request)
    {
        // Tymczasowo wyłączone sprawdzanie autoryzacji
        return true;
    }

    /**
     * Pobiera definicję argumentów dla endpointu dialogu
     *
     * @return array Definicja argumentów.
     */
    private static function get_dialog_args(): array
    {
        return [
            'npc_id' => [
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'dialog_id' => [
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'current_url' => [
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'user_id' => [
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'page_data' => [
                'required'          => false,
                'type'              => 'object',
                'default'           => [],
                'sanitize_callback' => [self::class, 'sanitize_page_data'],
            ],
            'answer_id' => [
                'required'          => false,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'answer_index' => [
                'required'          => false,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'current_dialog_id' => [
                'required'          => false,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Sanityzacja danych strony
     *
     * @param mixed $data Dane do sanityzacji.
     * @return array Zdezynfekowane dane.
     */
    public static function sanitize_page_data($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $sanitized_data = [];
        foreach ($data as $key => $value) {
            $sanitized_key = sanitize_text_field($key);
            if (is_array($value)) {
                $sanitized_data[$sanitized_key] = self::sanitize_page_data($value);
            } else {
                $sanitized_data[$sanitized_key] = sanitize_text_field($value);
            }
        }

        return $sanitized_data;
    }

    /**
     * Sprawdza, czy można wykonać transakcję (zabezpieczenie przed szybkim klikaniem)
     * 
     * @param int $user_id ID użytkownika
     * @param string $transaction_key Unikalny klucz transakcji (np. npc_id + dialog_id + answer_id)
     * @param NpcLogger $logger Logger do zapisywania informacji
     * @return bool True jeśli można wykonać transakcję, false w przeciwnym wypadku
     * @throws \Exception Gdy transakcja jest wykonywana zbyt szybko
     */
    private static function can_process_transaction(int $user_id, string $transaction_key, NpcLogger $logger): bool
    {
        // Klucz meta dla znaczników czasu transakcji
        $meta_key = 'last_transaction_timestamps';

        // Pobierz zapisane znaczniki czasu transakcji
        $transaction_timestamps = get_user_meta($user_id, $meta_key, true);
        if (!is_array($transaction_timestamps)) {
            $transaction_timestamps = [];
        }

        $current_time = time();

        // Sprawdź, czy transakcja o danym kluczu była wykonana w ostatnim czasie
        if (isset($transaction_timestamps[$transaction_key])) {
            $last_time = $transaction_timestamps[$transaction_key];
            $time_diff = $current_time - $last_time;

            if ($time_diff < self::TRANSACTION_COOLDOWN) {
                $logger->debug_log("TRANSAKCJA ZABLOKOWANA: Próba wykonania transakcji '$transaction_key' za szybko (ostatnia: " . $time_diff . "s temu)");
                throw new \Exception("Zbyt szybkie klikanie w transakcji '$transaction_key'. Poprzednie kliknięcie " . $time_diff . "s temu, wymagana przerwa: " . self::TRANSACTION_COOLDOWN . "s");
            }
        }

        // Zapisz znacznik czasu dla tej transakcji
        $transaction_timestamps[$transaction_key] = $current_time;

        // Usuń stare znaczniki czasu (starsze niż 1 minuta)
        foreach ($transaction_timestamps as $key => $timestamp) {
            if ($current_time - $timestamp > 60) {
                unset($transaction_timestamps[$key]);
            }
        }

        // Zapisz zaktualizowane znaczniki czasu
        update_user_meta($user_id, $meta_key, $transaction_timestamps);

        return true;
    }

    /**
     * Obsługa żądania dialogu - uproszczona wersja korzystająca z nowej DialogManager
     *
     * @param WP_REST_Request $request Obiekt żądania.
     * @return WP_REST_Response|WP_Error Odpowiedź z danymi dialogu lub błąd.
     */
    public static function handle_dialog_request(\WP_REST_Request $request)
    {
        try {
            // Pobierz parametry
            $npc_id = (int)$request->get_param('npc_id');
            $dialog_id = $request->get_param('dialog_id');
            $current_url = $request->get_param('current_url');
            $user_id = (int)$request->get_param('user_id');
            $page_data = $request->get_param('page_data');

            // Parametry odpowiedzi
            $answer_id = $request->get_param('answer_id');
            $answer_index = $request->get_param('answer_index');
            $current_dialog_id = $request->get_param('current_dialog_id');

            // Logger
            $logger = new NpcLogger();
            $logger->debug_log("===== ROZPOCZĘCIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");
            $logger->debug_log("Parametry żądania:", [
                'npc_id' => $npc_id,
                'dialog_id' => $dialog_id,
                'current_url' => $current_url,
                'user_id' => $user_id,
                'answer_id' => $answer_id,
                'answer_index' => $answer_index,
                'current_dialog_id' => $current_dialog_id
            ]);

            // Sprawdź, czy NPC istnieje
            $npc = get_post($npc_id);
            if (!$npc || 'npc' !== get_post_type($npc)) {
                $logger->debug_log("Błąd: Nie znaleziono NPC o ID: $npc_id");
                return new \WP_Error(
                    'invalid_npc',
                    __('Nie znaleziono NPC o podanym ID.', 'game'),
                    ['status' => 404]
                );
            }

            // Pobierz dane dialogu z ACF
            $dialogs = get_field('dialogs', $npc_id);
            if (!$dialogs || !is_array($dialogs)) {
                $logger->debug_log("Błąd: NPC nie ma zdefiniowanych dialogów");
                return new \WP_Error(
                    'no_dialogs',
                    __('Ten NPC nie ma zdefiniowanych dialogów.', 'game'),
                    ['status' => 404]
                );
            }

            // Przygotuj zarządzanie dialogiem
            $dialog_manager = new DialogManager($logger);
            $dialog_manager->setNpcId($npc_id);
            $dialog_manager->setUserId($user_id);

            // Przygotuj UserContext i LocationInfo przed filtrowaniem dialogów
            $userContext = new UserContext(new ManagerUser($user_id));

            // Ekstrakcja danych lokalizacji
            $location_extractor = new LocationExtractor();
            $location = $location_extractor->extract_location_from_url($current_url);
            $type_page = isset($page_data['TypePage']) ? sanitize_text_field($page_data['TypePage']) : '';
            $location_value = isset($page_data['value']) ? sanitize_text_field($page_data['value']) : '';

            // Przygotuj dane lokalizacji dla kontekstu
            $location_info = [
                'area_slug' => $location,
                'type_page' => $type_page,
                'location_value' => $location_value
            ];

            // Filtruj wszystkie dialogi według kontekstu użytkownika
            $logger->debug_log("Filtrowanie wszystkich dialogów NPC według kontekstu użytkownika...");
            $filtered_dialogs = [];
            foreach ($dialogs as $d) {
                // Najpierw sprawdź warunki widoczności dla całego dialogu
                $dialog_passes = true;

                // Sprawdź, czy dialog ma zdefiniowane warunki widoczności
                if (isset($d['layout_settings']) && isset($d['layout_settings']['visibility_settings']) && is_array($d['layout_settings']['visibility_settings'])) {
                    $visibility_conditions = $d['layout_settings']['visibility_settings'];
                    $logic_operator = isset($d['layout_settings']['logic_operator']) ? strtolower($d['layout_settings']['logic_operator']) : 'and';

                    $logger->debug_log("Sprawdzanie warunków widoczności dla dialogu", [
                        'dialog_id' => $d['id_pola'] ?? 'unknown',
                        'conditions_count' => count($visibility_conditions),
                        'logic_operator' => $logic_operator
                    ]);

                    // Domyślne wartości dla operatorów logicznych
                    if ($logic_operator === 'and') {
                        $dialog_passes = true; // Dla AND, zaczynamy od true i musimy znaleźć jeden false
                    } else {
                        $dialog_passes = false; // Dla OR, zaczynamy od false i musimy znaleźć jeden true
                    }

                    $validator = new ContextValidator($userContext);

                    foreach ($visibility_conditions as $condition) {
                        try {
                            $context_for_condition = $validator->validateCondition($condition, $location_info);
                            $condition_result = $dialog_manager->validate_dialog_condition($condition, $context_for_condition);

                            $logger->debug_log("Wynik warunku", [
                                'condition' => $condition,
                                'result' => $condition_result
                            ]);

                            if ($logic_operator === 'and' && !$condition_result) {
                                // Dla AND, jeśli jakikolwiek warunek jest false, cały dialog nie przechodzi
                                $dialog_passes = false;
                                break;
                            } else if ($logic_operator === 'or' && $condition_result) {
                                // Dla OR, jeśli jakikolwiek warunek jest true, cały dialog przechodzi
                                $dialog_passes = true;
                                break;
                            }
                        } catch (\Exception $e) {
                            $logger->debug_log("Błąd podczas walidacji warunku: " . $e->getMessage(), [
                                'condition' => $condition,
                                'exception' => $e->getTraceAsString()
                            ]);

                            // W przypadku błędu, kontynuuj z następnym warunkiem 
                            // dla operatora AND traktujemy warunek jako niespełniony
                            if ($logic_operator === 'and') {
                                $dialog_passes = false;
                                break;
                            }
                            // dla OR kontynuujemy sprawdzanie innych warunków
                        }
                    }
                }

                // Jeśli dialog nie przeszedł warunków widoczności, pomijamy go
                if (!$dialog_passes) {
                    $logger->debug_log("Dialog pominięty z powodu niespełnionych warunków widoczności", [
                        'dialog_id' => $d['id_pola'] ?? 'unknown'
                    ]);
                    continue;
                }

                // Teraz filtrujemy odpowiedzi dialogu, jeśli przeszedł warunki widoczności
                if (isset($d['anwsers']) && is_array($d['anwsers'])) {
                    $filtered_answers = $dialog_manager->filter_answers_with_user_context($d['anwsers'], $userContext, $location_info);

                    // Jeśli po filtrowaniu są jakieś odpowiedzi, dodaj dialog do przefiltrowanych
                    if (!empty($filtered_answers)) {
                        $dialog_copy = $d;
                        $dialog_copy['anwsers'] = $filtered_answers;
                        $filtered_dialogs[] = $dialog_copy;
                        $logger->debug_log("Dialog dodany do przefiltrowanych", [
                            'dialog_id' => $d['id_pola'] ?? 'unknown',
                            'answers_count' => count($filtered_answers)
                        ]);
                    } else {
                        $logger->debug_log("Dialog pominięty z powodu braku pasujących odpowiedzi", [
                            'dialog_id' => $d['id_pola'] ?? 'unknown'
                        ]);
                    }
                }
            }

            $logger->debug_log("Filtrowanie zakończone. Liczba dialogów przed: " . count($dialogs) . ", po: " . count($filtered_dialogs));

            // Zastąp oryginalne dialogi przefiltrowanymi
            $dialogs = $filtered_dialogs;

            // Powiadomienia z akcji dialogu
            $notifications = [];

            // Sprawdź, czy mamy do przetworzenia transakcję z poprzedniego dialogu
            if ($current_dialog_id && $answer_index !== null) {
                $logger->debug_log("Przetwarzanie odpowiedzi z dialogu: $current_dialog_id, indeks odpowiedzi: $answer_index");

                // Unikalny klucz transakcji
                $transaction_key = "npc_{$npc_id}_dialog_{$current_dialog_id}_answer_{$answer_index}";

                try {
                    // Sprawdź, czy można przetworzyć transakcję (zabezpieczenie przed szybkim klikaniem)
                    self::can_process_transaction($user_id, $transaction_key, $logger);

                    // Znajdź poprzedni dialog
                    $prev_dialog = null;
                    foreach ($dialogs as $d) {
                        if (isset($d['id_pola']) && $d['id_pola'] === $current_dialog_id) {
                            $prev_dialog = $d;
                            break;
                        }
                    }

                    if (!$prev_dialog) {
                        $logger->debug_log("Błąd: Nie znaleziono poprzedniego dialogu o ID: $current_dialog_id");
                        throw new \Exception("Nie znaleziono poprzedniego dialogu o ID: $current_dialog_id");
                    }

                    // Pobierz odpowiedzi z dialogu (już są przefiltrowane)
                    $answers = isset($prev_dialog['anwsers']) ? $prev_dialog['anwsers'] : [];

                    // Sprawdź, czy indeks odpowiedzi jest prawidłowy
                    if (!isset($answers[$answer_index])) {
                        $logger->debug_log("Błąd: Nieprawidłowy indeks odpowiedzi: $answer_index");
                        throw new \Exception("Nieprawidłowy indeks odpowiedzi: $answer_index");
                    }

                    $answer = $answers[$answer_index];
                    $logger->debug_log("Przetwarzanie odpowiedzi:", $answer);

                    // Pobierz nowe ID dialogu z odpowiedzi
                    $next_dialog_id = $answer['go_to_id'] ?? '';
                    if (empty($next_dialog_id)) {
                        $logger->debug_log("Błąd: Brak ID następnego dialogu w odpowiedzi");
                        throw new \Exception("Brak ID następnego dialogu w odpowiedzi");
                    }

                    // Sprawdź, czy odpowiedź ma akcje do wykonania
                    if (isset($answer['type_anwser']) && is_array($answer['type_anwser'])) {
                        $logger->debug_log("Wykonywanie akcji z odpowiedzi...");

                        // Przetwórz akcje dialogu za pomocą DialogManager
                        $action_notifications = $dialog_manager->process_dialog_actions(['actions' => $answer['type_anwser']]);
                        if (!empty($action_notifications)) {
                            $notifications = array_merge($notifications, $action_notifications);
                        }
                    }

                    // Zaktualizuj ID dialogu do wyświetlenia na następny
                    $dialog_id = $next_dialog_id;
                    $logger->debug_log("Przejście do następnego dialogu: $dialog_id");
                } catch (\Exception $e) {
                    // Obsłuż wyjątek związany z szybkim klikaniem
                    $logger->debug_log("Wyjątek podczas przetwarzania transakcji: " . $e->getMessage());

                    // Znajdź dialog o określonym ID (oryginalny dialog)
                    $dialog = null;
                    foreach ($dialogs as $d) {
                        if (isset($d['id_pola']) && $d['id_pola'] === $dialog_id) {
                            $dialog = $d;
                            break;
                        }
                    }

                    if (!$dialog) {
                        $logger->debug_log("Błąd: Nie znaleziono dialogu o ID: $dialog_id");
                        return new \WP_Error(
                            'dialog_not_found',
                            __('Nie znaleziono dialogu o podanym ID.', 'game'),
                            ['status' => 404]
                        );
                    }

                    // Uproszczenie struktury dialogu (dialog jest już przefiltrowany)
                    $simplified_dialog = $dialog_manager->simplify_dialog($dialog);

                    // Pobierz URL obrazka miniatury dla NPC
                    $thumbnail_url = get_the_post_thumbnail_url($npc_id, 'full') ?: '';

                    // Przygotuj odpowiedź z ostrzeżeniem o zbyt szybkim klikaniu
                    $response_data = [
                        'success' => true,
                        'dialog' => $simplified_dialog,
                        'npc' => [
                            'id' => $npc->ID,
                            'name' => $npc->post_title,
                            'image' => $thumbnail_url,
                        ],
                        'notification' => [
                            'message' => 'Za szybko klikasz! Poczekaj chwilę...',
                            'status' => 'warning'
                        ]
                    ];

                    $logger->debug_log("Zwracanie odpowiedzi z ostrzeżeniem o zbyt szybkim klikaniu:", $response_data);
                    $logger->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");
                    return new \WP_REST_Response($response_data, 200);
                }
            }

            // Znajdź dialog o określonym ID
            $dialog = null;
            foreach ($dialogs as $d) {
                if (isset($d['id_pola']) && $d['id_pola'] === $dialog_id) {
                    $dialog = $d;
                    break;
                }
            }

            if (!$dialog) {
                $logger->debug_log("Błąd: Nie znaleziono dialogu o ID: $dialog_id");
                return new \WP_Error(
                    'dialog_not_found',
                    __('Nie znaleziono dialogu o podanym ID.', 'game'),
                    ['status' => 404]
                );
            }

            $logger->debug_log("Dialog po filtrowaniu:", $dialog);

            // Uproszczenie struktury dialogu
            $simplified_dialog = $dialog_manager->simplify_dialog($dialog);
            $logger->debug_log("Uproszczona struktura dialogu:", $simplified_dialog);

            // Pobierz URL obrazka miniatury dla NPC
            $thumbnail_url = get_the_post_thumbnail_url($npc_id, 'full') ?: '';

            // Przygotuj dane odpowiedzi
            $response_data = [
                'success' => true,
                'dialog' => $simplified_dialog,
                'npc' => [
                    'id' => $npc->ID,
                    'name' => $npc->post_title,
                    'image' => $thumbnail_url,
                ],
            ];

            // Dodaj powiadomienie, jeśli istnieje
            if (!empty($notifications)) {
                // Jeśli jest więcej powiadomień, użyj pierwszego
                $response_data['notification'] = $notifications[0];
                $logger->debug_log("Dodano powiadomienie z akcji do odpowiedzi:", $notifications[0]);
            }

            $logger->debug_log("Zwracanie odpowiedzi:", $response_data);
            $logger->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");

            return new \WP_REST_Response($response_data, 200);
        } catch (\Exception $e) {
            // Loguj błąd
            $logger = new NpcLogger();
            $logger->debug_log("POWAŻNY BŁĄD podczas obsługi dialogu: " . $e->getMessage());
            $logger->debug_log("Stack trace: " . $e->getTraceAsString());

            // Zamiast zwracać błąd 500, zwróć komunikat o błędzie jako normalną odpowiedź
            $dialog_id = $request->get_param('dialog_id') ?? '';
            $npc_id = (int)$request->get_param('npc_id') ?? 0;

            // Pobierz dane NPC, jeśli możliwe
            $npc = get_post($npc_id);
            $npc_name = $npc ? $npc->post_title : 'NPC';
            $thumbnail_url = $npc ? (get_the_post_thumbnail_url($npc_id, 'full') ?: '') : '';

            // Przygotuj komunikat o błędzie
            $error_response = [
                'success' => false,
                'dialog' => [
                    'id' => $dialog_id,
                    'text' => '<p>Wystąpił błąd - odśwież stronę i spróbuj ponownie.</p>',
                    'answers' => [],
                ],
                'npc' => [
                    'id' => $npc_id,
                    'name' => $npc_name,
                    'image' => $thumbnail_url,
                ],
                'notification' => [
                    'message' => 'Za szybko klikasz! Odczekaj chwilę i spróbuj ponownie.',
                    'status' => 'warning'
                ]
            ];

            $logger->debug_log("Zwracanie odpowiedzi z błędem:", $error_response);
            $logger->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA DIALOGU Z BŁĘDEM =====");

            return new \WP_REST_Response($error_response, 200);
        }
    }

    /**
     * Pobiera kontekst użytkownika
     *
     * @param int $user_id ID użytkownika
     * @return UserContext Obiekt kontekstu użytkownika
     */
    private static function get_user_context(int $user_id): UserContext
    {
        return new UserContext(new ManagerUser($user_id));
    }
}

// Inicjalizacja klasy
DialogHandler::init();
