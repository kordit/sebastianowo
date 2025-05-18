<?php

/**
 * Klasa DialogHandler
 *
 * Klasa odpowiedzialna za obsługę endpointu API dla dialogów NPC.
 *
 * @package Game
 * @since 1.0.0
 */

// Załaduj wymagane klasy
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

        // Sprawdź, czy użytkownik jest zalogowany
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_forbidden',
                __('Musisz być zalogowany, aby uzyskać dostęp do dialogów.', 'game'),
                ['status' => 401]
            );
        }

        // Sprawdź, czy przekazany user_id zgadza się z ID zalogowanego użytkownika
        $user_id = (int)$request->get_param('user_id');
        $current_user_id = get_current_user_id();

        if ($user_id !== $current_user_id) {
            return new \WP_Error(
                'rest_forbidden',
                __('Brak dostępu do dialogów innego użytkownika.', 'game'),
                ['status' => 403]
            );
        }

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
                return false;
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
     * Obsługa żądania dialogu
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

            // Zapisz całe dane żądania do logu
            $logger->debug_log("WSZYSTKIE DANE ŻĄDANIA:", $request->get_params());

            // Sprawdź aktualny stan zasobów użytkownika na początku
            $current_backpack = get_field(BACKPACK['name'], 'user_' . $user_id);

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

            // Ekstrakcja danych lokalizacji
            $location_extractor = new LocationExtractor();
            $location = $location_extractor->extract_location_from_url($current_url);
            $type_page = isset($page_data['TypePage']) ? sanitize_text_field($page_data['TypePage']) : '';
            $location_value = isset($page_data['value']) ? sanitize_text_field($page_data['value']) : '';

            $logger->debug_log("Wyodrębnione dane lokalizacji:", [
                'location' => $location,
                'type_page' => $type_page,
                'location_value' => $location_value
            ]);

            $criteria = [
                'type_page' => $type_page,
                'location' => $location_value,
                'user_id' => $user_id,
                'npc_id' => $npc_id
            ];

            // Komunikat po wykonaniu transakcji (jeśli będzie)
            $notification = null;

            // Sprawdź, czy mamy do przetworzenia transakcję z poprzedniego dialogu
            if ($current_dialog_id && $answer_index !== null) {
                $logger->debug_log("ROZPOCZYNAM PRZETWARZANIE PRZYCISKÓW ODPOWIEDZI");

                // Unikalny klucz transakcji
                $transaction_key = "npc_{$npc_id}_dialog_{$current_dialog_id}_answer_{$answer_index}";

                // Sprawdź, czy można przetworzyć transakcję (zabezpieczenie przed szybkim klikaniem)
                if (!self::can_process_transaction($user_id, $transaction_key, $logger)) {
                    $logger->debug_log("Pomijam przetwarzanie transakcji z powodu ochrony przed szybkim klikaniem: $transaction_key");

                    // Zwróć aktualny dialog bez przetwarzania transakcji
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
                    // Filtruj odpowiedzi w dialogu z wykorzystaniem UserContext
                    $userContext = self::get_user_context($user_id);
                    $location_info = [
                        'type_page' => $criteria['type_page'] ?? null,
                        'location_value' => $criteria['location_value'] ?? null
                    ];
                    $filtered_dialog = $dialog_manager->get_first_matching_dialog([$dialog], $userContext, $location_info);
                    if (!$filtered_dialog) {
                        $logger->debug_log("UWAGA: Dialog nie przeszedł filtrowania z UserContext");
                        $filtered_dialog = $dialog; // Używamy oryginalnego dialogu jeśli filtrowanie nie zwróciło wyników
                    } else {
                        $filtered_dialog = $filtered_dialog[0]; // get_first_matching_dialog zwraca tablicę, bierzemy pierwszy element
                    }
                    $logger->debug_log("Dialog po filtrowaniu:", $filtered_dialog);

                    // Uproszczenie struktury dialogu
                    $simplified_dialog = $dialog_manager->simplify_dialog($filtered_dialog);

                    // $logger->debug_log("Uproszczona struktura dialogu:", $simplified_dialog);

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

                    $response_data['notification'] = [
                        'message' => 'Za szybko klikasz! Poczekaj chwilę...',
                        'status' => 'warning'
                    ];

                    $logger->debug_log("Dane odpowiedzi (pomijam transakcję):", $response_data);
                    $logger->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");

                    return new \WP_REST_Response($response_data, 200);
                }

                // Znajdź poprzedni dialog
                $prev_dialog = null;
                foreach ($dialogs as $d) {
                    if (isset($d['id_pola']) && $d['id_pola'] === $current_dialog_id) {
                        $prev_dialog = $d;
                        $logger->debug_log("Znaleziono dialog o ID: {$d['id_pola']}");
                        break;
                    }
                }

                if ($prev_dialog) {
                    $logger->debug_log("Znaleziono poprzedni dialog ID: $current_dialog_id");
                    $logger->debug_log("Pełne dane dialogu:", $prev_dialog);

                    // Znajdź odpowiedź na podstawie indeksu
                    $answers = isset($prev_dialog['anwsers']) ? $prev_dialog['anwsers'] : [];
                    $logger->debug_log("Odpowiedzi w dialogu:", $answers);

                    if (is_array($answers) && isset($answers[$answer_index])) {
                        $answer = $answers[$answer_index];
                        $logger->debug_log("Znaleziono odpowiedź o indeksie: $answer_index");
                        $logger->debug_log("Dane odpowiedzi:", $answer);

                        // Sprawdź, czy odpowiedź ma transakcję lub inną akcję
                        if (isset($answer['type_anwser']) && !empty($answer['type_anwser'])) {
                            $logger->debug_log("Odpowiedź zawiera akcje do wykonania:", $answer['type_anwser']);

                            // -- NAJPIERW WERYFIKUJEMY WSZYSTKIE AKCJE --
                            // Zmienna określająca, czy wszystkie akcje są wykonalne
                            $all_actions_possible = true;
                            // Lista brakujących zasobów do wyświetlenia użytkownikowi
                            $missing_resources = [];

                            // Pobierz dane plecaka i zasobów użytkownika za pomocą ACF
                            $backpack = get_field(BACKPACK['name'], 'user_' . $user_id);
                            if (!is_array($backpack)) {
                                $backpack = [];
                                // Zainicjuj domyślne wartości wszystkich pól plecaka
                                foreach (BACKPACK['fields'] as $field_key => $field_data) {
                                    $backpack[$field_key] = $field_data['default'];
                                }
                            }

                            // Pobierz ekwipunek użytkownika (przedmioty)
                            $items_inventory = get_field('items', 'user_' . $user_id);
                            if (!is_array($items_inventory)) {
                                $items_inventory = [];
                            }

                            // Pobierz umiejętności użytkownika
                            $skills = get_field(SKILLS['name'], 'user_' . $user_id);
                            if (!is_array($skills)) {
                                $skills = [];
                                // Zainicjuj domyślne wartości wszystkich umiejętności
                                foreach (SKILLS['fields'] as $field_key => $field_data) {
                                    $skills[$field_key] = $field_data['default'];
                                }
                            }

                            // Sprawdź każdą akcję, czy jest wykonalna, ale jej nie wykonuj
                            foreach ($answer['type_anwser'] as $action) {
                                $action_type = $action['acf_fc_layout'] ?? '';
                                $logger->debug_log("Weryfikacja akcji:", $action);

                                switch ($action_type) {
                                    case 'transaction':
                                        // Sprawdź, czy transakcja jest wykonalna
                                        $currency = $action['backpack'] ?? '';
                                        $value = (int)($action['value'] ?? 0);

                                        // Mapowanie nazw z polskiego na klucze systemowe
                                        $currency_mapping = [
                                            'papierosy' => 'cigarettes',
                                            'szlugi' => 'cigarettes',
                                            'złoto' => 'gold',
                                            'zloto' => 'gold',
                                            'hajs' => 'gold',
                                            'grzyby' => 'mushrooms',
                                            'grzybki' => 'mushrooms',
                                            'piwo' => 'beer',
                                            'browary' => 'beer',
                                            'alko' => 'vodka',
                                            'wóda' => 'vodka',
                                            'wódka' => 'vodka',
                                            'klej' => 'glue',
                                            'kleje' => 'glue',
                                            'mj' => 'weed',
                                            'zioło' => 'weed',
                                            'ziolo' => 'weed',
                                            'zielone' => 'weed',
                                            'marihuana' => 'weed'
                                        ];

                                        // Słownik wyświetlanych nazw dla zasobów (do komunikatów)
                                        $currency_display_names = [
                                            'cigarettes' => 'papierosów',
                                            'gold' => 'złota',
                                            'mushrooms' => 'grzybów',
                                            'beer' => 'piwa',
                                            'vodka' => 'wódki',
                                            'glue' => 'kleju',
                                            'weed' => 'zioła'
                                        ];

                                        // Sprawdź czy trzeba zamapować klucz
                                        if (isset($currency_mapping[$currency])) {
                                            $mapped_currency = $currency_mapping[$currency];
                                            $logger->debug_log("MAPOWANIE WALUTY: Zamieniono klucz '$currency' na '$mapped_currency'");
                                            $currency = $mapped_currency;
                                        }

                                        // Jeśli zabieramy zasoby (wartość ujemna), sprawdź czy użytkownik ma ich wystarczającą ilość
                                        if ($value < 0) {
                                            $current_value = isset($backpack[$currency]) ? (int)$backpack[$currency] : 0;
                                            $logger->debug_log("Weryfikacja transakcji: waluta=$currency, wartość=$value, dostępne=$current_value");

                                            if (abs($value) > $current_value) {
                                                $all_actions_possible = false;
                                                $display_name = $currency_display_names[$currency] ?? $currency;
                                                $missing_resources[] = [
                                                    'resource' => $display_name,
                                                    'required' => abs($value),
                                                    'available' => $current_value,
                                                    'type' => 'currency'
                                                ];
                                                $logger->debug_log("NIEPOWODZENIE WERYFIKACJI TRANSAKCJI: Próba zabrania " . abs($value) . " $currency, ale użytkownik ma tylko $current_value");
                                            }
                                        }
                                        break;

                                    case 'item':
                                        $item_action = $action['item_action'] ?? '';
                                        $item_id = (int)($action['item'] ?? 0);
                                        $quantity = (int)($action['quantity'] ?? 1);

                                        // Weryfikuj tylko akcje "take" (zabieranie przedmiotu)
                                        if ($item_action === 'take') {
                                            $logger->debug_log("Weryfikacja akcji przedmiotu: $item_action, ID: $item_id, ilość: $quantity");

                                            if (!$item_id) {
                                                $logger->debug_log("BŁĄD: Nieprawidłowe ID przedmiotu");
                                                $all_actions_possible = false;
                                                $missing_resources[] = [
                                                    'resource' => 'przedmiot (błędna konfiguracja)',
                                                    'required' => $quantity,
                                                    'available' => 0,
                                                    'type' => 'item'
                                                ];
                                                break;
                                            }

                                            // Pobierz informacje o przedmiocie
                                            $item_post = get_post($item_id);
                                            if (!$item_post || $item_post->post_type !== 'item') {
                                                $logger->debug_log("BŁĄD: Przedmiot o ID $item_id nie istnieje");
                                                $all_actions_possible = false;
                                                $missing_resources[] = [
                                                    'resource' => 'przedmiot (nieistniejący)',
                                                    'required' => $quantity,
                                                    'available' => 0,
                                                    'type' => 'item'
                                                ];
                                                break;
                                            }

                                            $item_name = $item_post->post_title;

                                            // Flaga określająca, czy przedmiot został znaleziony w ekwipunku
                                            $item_found = false;
                                            $current_quantity = 0;

                                            // Szukamy przedmiotu w ekwipunku
                                            foreach ($items_inventory as $inventory_item) {
                                                if (isset($inventory_item['item']) && (int)$inventory_item['item'] === $item_id) {
                                                    $item_found = true;
                                                    $current_quantity = (int)($inventory_item['quantity'] ?? 0);
                                                    break;
                                                }
                                            }

                                            if (!$item_found || $current_quantity < $quantity) {
                                                $all_actions_possible = false;
                                                $missing_resources[] = [
                                                    'resource' => $item_name,
                                                    'required' => $quantity,
                                                    'available' => $current_quantity,
                                                    'type' => 'item'
                                                ];
                                                $logger->debug_log("NIEPOWODZENIE WERYFIKACJI PRZEDMIOTU: Próba zabrania $quantity x $item_name, ale użytkownik ma tylko $current_quantity");
                                            }
                                        }
                                        break;

                                    case 'skills':
                                        $skill_type = $action['type_of_skills'] ?? '';
                                        $skill_value = (int)($action['value'] ?? 0);

                                        // Weryfikuj tylko akcje zmniejszające wartość umiejętności
                                        if ($skill_value < 0) {
                                            $logger->debug_log("Weryfikacja akcji umiejętności: typ=$skill_type, wartość=$skill_value");

                                            if (empty($skill_type)) {
                                                $logger->debug_log("BŁĄD: Nie podano typu umiejętności");
                                                $all_actions_possible = false;
                                                $missing_resources[] = [
                                                    'resource' => 'umiejętność (nieprawidłowa)',
                                                    'required' => abs($skill_value),
                                                    'available' => 0,
                                                    'type' => 'skill'
                                                ];
                                                break;
                                            }

                                            // Sprawdź, czy podany typ umiejętności istnieje w strukturze
                                            if (!isset(SKILLS['fields'][$skill_type])) {
                                                $logger->debug_log("BŁĄD: Nieprawidłowy typ umiejętności: $skill_type");
                                                $all_actions_possible = false;
                                                $missing_resources[] = [
                                                    'resource' => 'umiejętność (nieistniejąca)',
                                                    'required' => abs($skill_value),
                                                    'available' => 0,
                                                    'type' => 'skill'
                                                ];
                                                break;
                                            }

                                            // Pobierz aktualną wartość umiejętności
                                            $current_value = isset($skills[$skill_type]) ? (int)$skills[$skill_type] : 0;

                                            if (abs($skill_value) > $current_value) {
                                                $skill_label = SKILLS['fields'][$skill_type]['label'];
                                                $all_actions_possible = false;
                                                $missing_resources[] = [
                                                    'resource' => $skill_label,
                                                    'required' => abs($skill_value),
                                                    'available' => $current_value,
                                                    'type' => 'skill'
                                                ];
                                                $logger->debug_log("NIEPOWODZENIE WERYFIKACJI UMIEJĘTNOŚCI: Próba zmniejszenia {$skill_type} o " . abs($skill_value) . ", ale użytkownik ma tylko $current_value");
                                            }
                                        }
                                        break;

                                    // Inne typy akcji nie wymagają weryfikacji, więc je pomijamy
                                    default:
                                        break;
                                }
                            }

                            // Jeśli co najmniej jedna akcja nie jest wykonalna, przerwij wszystkie
                            if (!$all_actions_possible) {
                                $logger->debug_log("Nie można wykonać wszystkich akcji. Akcje zostały anulowane.");

                                // Przygotuj komunikat o brakujących zasobach
                                if (!empty($missing_resources)) {
                                    // Przygotuj szczegółową wiadomość o wszystkich brakujących zasobach
                                    $error_message = "Nie możesz wykonać tej akcji. Brakuje:<br>";

                                    foreach ($missing_resources as $resource) {
                                        $resource_name = $resource['resource'];
                                        $required = $resource['required'];
                                        $available = $resource['available'];
                                        $error_message .= "• {$resource_name}: potrzeba {$required}, masz {$available}<br>";
                                    }

                                    $error_notification = [
                                        'message' => $error_message,
                                        'status' => 'bad'
                                    ];
                                } else {
                                    // Jeśli nie ma szczegółowych informacji o brakujących zasobach, użyj domyślnej wiadomości
                                    $error_notification = [
                                        'message' => "Nie można wykonać wszystkich wymaganych akcji.",
                                        'status' => 'bad'
                                    ];
                                }

                                $logger->debug_log("Akcje odrzucone - niemożliwe do wykonania", $error_notification);

                                // Zamiast kontynuować do następnego dialogu, wracamy ten sam dialog
                                // aby użytkownik mógł wybrać inną opcję
                                $response_data = [
                                    'success' => true,
                                    'dialog' => $dialog_manager->simplify_dialog($prev_dialog),
                                    'npc' => [
                                        'id' => $npc->ID,
                                        'name' => $npc->post_title,
                                        'image' => get_the_post_thumbnail_url($npc_id, 'full') ?: '',
                                    ],
                                    'notification' => $error_notification
                                ];

                                $logger->debug_log("Zwracam ten sam dialog (warunki akcji nie spełnione):", $response_data);
                                $logger->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");

                                return new \WP_REST_Response($response_data, 200);
                            }

                            // -- JEŚLI DOTARLIŚMY TUTAJ, WSZYSTKIE AKCJE SĄ WYKONALNE --
                            $logger->debug_log("WSZYSTKIE AKCJE SĄ WYKONALNE, PRZYSTĘPUJĘ DO ICH REALIZACJI");

                            // Przetwórz każdą akcję w odpowiedzi
                            foreach ($answer['type_anwser'] as $action) {
                                $logger->debug_log("Przetwarzanie akcji:", $action);
                                $action_type = $action['acf_fc_layout'] ?? '';
                                $logger->debug_log("Typ akcji: $action_type");

                                switch ($action_type) {
                                    case 'transaction':
                                        // Sprawdź wszystkie transakcje przed ich wykonaniem
                                        // Znajdź wszystkie akcje transaction w tej odpowiedzi
                                        $all_transactions = [];
                                        foreach ($answer['type_anwser'] as $trans_check) {
                                            if (isset($trans_check['acf_fc_layout']) && $trans_check['acf_fc_layout'] === 'transaction') {
                                                $all_transactions[] = $trans_check;
                                            }
                                        }

                                        $logger->debug_log("Znaleziono " . count($all_transactions) . " transakcji do wykonania");

                                        // Pobierz dane plecaka za pomocą ACF
                                        $backpack = get_field(BACKPACK['name'], 'user_' . $user_id);
                                        if (!is_array($backpack)) {
                                            $backpack = [];
                                            // Zainicjuj domyślne wartości wszystkich pól plecaka
                                            foreach (BACKPACK['fields'] as $field_key => $field_data) {
                                                $backpack[$field_key] = $field_data['default'];
                                            }
                                        }

                                        // Sprawdź czy wszystkie transakcje są wykonalne
                                        $all_transactions_possible = true;
                                        $failed_currency = '';
                                        $failed_value = 0;
                                        $failed_current = 0;

                                        foreach ($all_transactions as $trans_item) {
                                            $check_currency = $trans_item['backpack'] ?? '';
                                            $check_value = (int)($trans_item['value'] ?? 0);

                                            // Mapowanie nazw z polskiego na klucze systemowe
                                            $currency_mapping = [
                                                'papierosy' => 'cigarettes',
                                                'szlugi' => 'cigarettes',
                                                'złoto' => 'gold',
                                                'zloto' => 'gold',
                                                'hajs' => 'gold',
                                                'grzyby' => 'mushrooms',
                                                'grzybki' => 'mushrooms',
                                                'piwo' => 'beer',
                                                'browary' => 'beer',
                                                'alko' => 'vodka',
                                                'wóda' => 'vodka',
                                                'wódka' => 'vodka',
                                                'klej' => 'glue',
                                                'kleje' => 'glue',
                                                'mj' => 'weed',
                                                'zioło' => 'weed',
                                                'ziolo' => 'weed',
                                                'zielone' => 'weed',
                                                'marihuana' => 'weed'
                                            ];

                                            // Sprawdź czy trzeba zamapować klucz
                                            if (isset($currency_mapping[$check_currency])) {
                                                $mapped_currency = $currency_mapping[$check_currency];
                                                $logger->debug_log("MAPOWANIE WALUTY: Zamieniono klucz '$check_currency' na '$mapped_currency'");
                                                $check_currency = $mapped_currency;
                                            }

                                            if ($check_value < 0) { // Sprawdzamy tylko gdy zabieramy zasoby
                                                $check_current = isset($backpack[$check_currency]) ? (int)$backpack[$check_currency] : 0;
                                                $logger->debug_log("Sprawdzanie transakcji: waluta=$check_currency, wartość=$check_value, dostępne=$check_current");

                                                if (abs($check_value) > $check_current) {
                                                    $all_transactions_possible = false;
                                                    $failed_currency = $check_currency;
                                                    $failed_value = $check_value;
                                                    $failed_current = $check_current;
                                                    $logger->debug_log("NIEPOWODZENIE WERYFIKACJI TRANSAKCJI: Próba zabrania " . abs($check_value) . " $check_currency, ale użytkownik ma tylko $check_current");
                                                    break; // Kończymy sprawdzanie gdy znajdziemy pierwszą niemożliwą transakcję
                                                }
                                            }
                                        }

                                        // Jeśli którakolwiek transakcja nie jest wykonalna, przerwij wszystkie
                                        if (!$all_transactions_possible) {
                                            $logger->debug_log("Nie można wykonać wszystkich transakcji. Transakcje zostały anulowane.");

                                            // Przygotuj powiadomienie o niewystarczających środkach
                                            $notification = [
                                                'message' => "Nie masz wystarczającej ilości $failed_currency! Potrzeba " . abs($failed_value) . ", a masz $failed_current.",
                                                'status' => 'bad'
                                            ];

                                            $logger->debug_log("Transakcja odrzucona - niewystarczające środki", $notification);

                                            // Zamiast kontynuować do następnego dialogu, wracamy ten sam dialog
                                            // aby użytkownik mógł wybrać inną opcję
                                            $response_data = [
                                                'success' => true,
                                                'dialog' => $dialog_manager->simplify_dialog($prev_dialog),
                                                'npc' => [
                                                    'id' => $npc->ID,
                                                    'name' => $npc->post_title,
                                                    'image' => get_the_post_thumbnail_url($npc_id, 'full') ?: '',
                                                ],
                                                'notification' => $notification
                                            ];

                                            $logger->debug_log("Zwracam ten sam dialog (warunek transakcji nie spełniony):", $response_data);
                                            $logger->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");

                                            return new \WP_REST_Response($response_data, 200);
                                        }

                                        // Jeśli dotarliśmy tutaj, wszystkie transakcje są wykonalne, więc wykonaj tę transakcję
                                        $currency = $action['backpack'] ?? '';
                                        $value = (int)($action['value'] ?? 0);

                                        $logger->debug_log("Wykonuję transakcję: waluta=$currency, wartość=$value");

                                        // Zapisz obecną wartość waluty do logów
                                        $current_value = isset($backpack[$currency]) ? (int)$backpack[$currency] : 0;
                                        $logger->debug_log("Obecna wartość waluty $currency w plecaku dla użytkownika $user_id: $current_value");

                                        // Oblicz nową wartość
                                        $new_value = $current_value + $value;
                                        if ($new_value < 0) {
                                            $new_value = 0; // Dodatkowe zabezpieczenie przed ujemnymi wartościami (nie powinno już być potrzebne)
                                        }

                                        // Aktualna wartość przed aktualizacją
                                        $logger->debug_log("Aktualne dane użytkownika przed aktualizacją:", [
                                            'user_id' => $user_id,
                                            'backpack_key' => $currency,
                                            'current_value' => $current_value,
                                            'value_to_add' => $value,
                                            'new_value' => $new_value
                                        ]);

                                        // Zaktualizuj wartość w plecaku
                                        $backpack[$currency] = $new_value;

                                        // Zapisz cały plecak z powrotem do meta danych użytkownika za pomocą ACF
                                        $result = update_field(BACKPACK['name'], $backpack, 'user_' . $user_id);
                                        $logger->debug_log("Rezultat update_field dla plecaka: " . ($result ? 'SUKCES' : 'BŁĄD'));

                                        // Sprawdź czy meta została zaktualizowana
                                        $updated_backpack = get_field(BACKPACK['name'], 'user_' . $user_id);
                                        $updated_value = isset($updated_backpack[$currency]) ? (int)$updated_backpack[$currency] : 0;
                                        $logger->debug_log("Wartość $currency po aktualizacji plecaka: $updated_value");

                                        // Przygotuj powiadomienie na podstawie typu transakcji
                                        $notification = [
                                            'message' => ($value > 0)
                                                ? "Otrzymano $value $currency"
                                                : "Stracono " . abs($value) . " $currency",
                                            'status' => ($value > 0) ? 'success' : 'bad'
                                        ];

                                        $logger->debug_log("Utworzono powiadomienie:", $notification);
                                        $logger->debug_log("Wykonano transakcję. Nowa wartość $currency w plecaku: $new_value");
                                        break;

                                    case 'item':
                                        $item_action = $action['item_action'] ?? '';
                                        $item_id = (int)($action['item'] ?? 0);
                                        $quantity = (int)($action['quantity'] ?? 1);

                                        $logger->debug_log("Wykonuję akcję przedmiotu: $item_action, ID: $item_id, ilość: $quantity");

                                        if (!$item_id) {
                                            $logger->debug_log("BŁĄD: Nieprawidłowe ID przedmiotu");
                                            break;
                                        }

                                        // Pobierz informacje o przedmiocie
                                        $item_post = get_post($item_id);
                                        if (!$item_post || $item_post->post_type !== 'item') {
                                            $logger->debug_log("BŁĄD: Przedmiot o ID $item_id nie istnieje");
                                            break;
                                        }

                                        $item_name = $item_post->post_title;

                                        // Pobierz aktualny ekwipunek użytkownika
                                        $items_inventory = get_field('items', 'user_' . $user_id);

                                        if (!is_array($items_inventory)) {
                                            $items_inventory = [];
                                        }

                                        $logger->debug_log("Aktualny stan ekwipunku użytkownika:", $items_inventory);

                                        // Flaga określająca, czy przedmiot został znaleziony w ekwipunku
                                        $item_found = false;

                                        // Szukamy przedmiotu w ekwipunku
                                        foreach ($items_inventory as $key => $inventory_item) {
                                            if (isset($inventory_item['item']) && (int)$inventory_item['item'] === $item_id) {
                                                $item_found = true;
                                                $current_quantity = (int)($inventory_item['quantity'] ?? 0);

                                                if ($item_action === 'give') {
                                                    // Dodaj przedmiot do ekwipunku
                                                    $items_inventory[$key]['quantity'] = $current_quantity + $quantity;
                                                    $notification = [
                                                        'message' => "Otrzymano $quantity x $item_name",
                                                        'status' => 'success'
                                                    ];
                                                    $logger->debug_log("Dodano $quantity x $item_name do ekwipunku. Nowy stan: {$items_inventory[$key]['quantity']}");
                                                } elseif ($item_action === 'take') {
                                                    // Zabierz przedmiot z ekwipunku
                                                    $new_quantity = max(0, $current_quantity - $quantity);

                                                    if ($new_quantity > 0) {
                                                        $items_inventory[$key]['quantity'] = $new_quantity;
                                                        $logger->debug_log("Zabrano $quantity x $item_name z ekwipunku. Nowy stan: {$items_inventory[$key]['quantity']}");
                                                    } else {
                                                        // Jeśli ilość wynosi 0, usuwamy przedmiot z ekwipunku
                                                        unset($items_inventory[$key]);
                                                        $items_inventory = array_values($items_inventory); // Reindeksowanie tablicy
                                                        $logger->debug_log("Usunięto przedmiot $item_name z ekwipunku (ilość = 0)");
                                                    }

                                                    $notification = [
                                                        'message' => "Stracono $quantity x $item_name",
                                                        'status' => 'bad'
                                                    ];

                                                    // Sprawdź, czy gracz ma wystarczającą ilość przedmiotów
                                                    if ($current_quantity < $quantity) {
                                                        $logger->debug_log("UWAGA: Próba zabrania większej ilości przedmiotów ($quantity) niż posiada gracz ($current_quantity)");
                                                    }
                                                }

                                                break;
                                            }
                                        }

                                        // Jeśli przedmiot nie został znaleziony w ekwipunku, a akcja to dodawanie
                                        if (!$item_found && $item_action === 'give') {
                                            $items_inventory[] = [
                                                'item' => $item_id,
                                                'quantity' => $quantity
                                            ];

                                            $notification = [
                                                'message' => "Otrzymano $quantity x $item_name",
                                                'status' => 'success'
                                            ];

                                            $logger->debug_log("Dodano nowy przedmiot $item_name (x$quantity) do ekwipunku");
                                        } elseif (!$item_found && $item_action === 'take') {
                                            $logger->debug_log("NIEPOWODZENIE AKCJI PRZEDMIOTU: Próba zabrania przedmiotu $item_name, ale użytkownik go nie posiada");

                                            $notification = [
                                                'message' => "Nie posiadasz przedmiotu $item_name!",
                                                'status' => 'bad'
                                            ];

                                            $logger->debug_log("Akcja przedmiotu odrzucona - brak przedmiotu w ekwipunku", $notification);

                                            // Zamiast kontynuować do następnego dialogu, wracamy ten sam dialog
                                            // aby użytkownik mógł wybrać inną opcję
                                            $response_data = [
                                                'success' => true,
                                                'dialog' => $dialog_manager->simplify_dialog($prev_dialog),
                                                'npc' => [
                                                    'id' => $npc->ID,
                                                    'name' => $npc->post_title,
                                                    'image' => get_the_post_thumbnail_url($npc_id, 'full') ?: '',
                                                ],
                                                'notification' => $notification
                                            ];

                                            $logger->debug_log("Zwracam ten sam dialog (brak przedmiotu w ekwipunku):", $response_data);
                                            $logger->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");

                                            return new \WP_REST_Response($response_data, 200);
                                        }

                                        break;

                                    case 'skills':
                                        $skill_type = $action['type_of_skills'] ?? '';
                                        $skill_value = (int)($action['value'] ?? 0);

                                        $logger->debug_log("Wykonuję akcję umiejętności: typ=$skill_type, wartość=$skill_value");

                                        if (empty($skill_type)) {
                                            $logger->debug_log("BŁĄD: Nie podano typu umiejętności");
                                            break;
                                        }

                                        // Pobierz aktualne umiejętności użytkownika
                                        $skills = get_field(SKILLS['name'], 'user_' . $user_id);
                                        if (!is_array($skills)) {
                                            $skills = [];
                                            // Zainicjuj domyślne wartości wszystkich umiejętności
                                            foreach (SKILLS['fields'] as $field_key => $field_data) {
                                                $skills[$field_key] = $field_data['default'];
                                            }
                                        }

                                        // Sprawdź, czy podany typ umiejętności istnieje w strukturze
                                        if (!isset(SKILLS['fields'][$skill_type])) {
                                            $logger->debug_log("BŁĄD: Nieprawidłowy typ umiejętności: $skill_type");
                                            break;
                                        }

                                        // Pobierz aktualną wartość umiejętności
                                        $current_value = isset($skills[$skill_type]) ? (int)$skills[$skill_type] : 0;
                                        $logger->debug_log("Obecna wartość umiejętności $skill_type dla użytkownika $user_id: $current_value");

                                        // Jeśli próbujemy zmniejszyć umiejętność (wartość ujemna), sprawdź czy użytkownik ma jej wystarczający poziom
                                        if ($skill_value < 0 && abs($skill_value) > $current_value) {
                                            $logger->debug_log("NIEPOWODZENIE AKCJI UMIEJĘTNOŚCI: Próba zmniejszenia {$skill_type} o " . abs($skill_value) . ", ale użytkownik ma tylko $current_value");

                                            // Przygotuj powiadomienie o niewystarczającym poziomie umiejętności
                                            $skill_label = SKILLS['fields'][$skill_type]['label'];
                                            $notification = [
                                                'message' => "Nie masz wystarczającego poziomu umiejętności {$skill_label}! Wymagane " . abs($skill_value) . ", a masz $current_value.",
                                                'status' => 'bad'
                                            ];

                                            $logger->debug_log("Akcja umiejętności odrzucona - niewystarczający poziom", $notification);

                                            // Zamiast kontynuować do następnego dialogu, wracamy ten sam dialog
                                            // aby użytkownik mógł wybrać inną opcję
                                            $response_data = [
                                                'success' => true,
                                                'dialog' => $dialog_manager->simplify_dialog($prev_dialog),
                                                'npc' => [
                                                    'id' => $npc->ID,
                                                    'name' => $npc->post_title,
                                                    'image' => get_the_post_thumbnail_url($npc_id, 'full') ?: '',
                                                ],
                                                'notification' => $notification
                                            ];

                                            $logger->debug_log("Zwracam ten sam dialog (niewystarczający poziom umiejętności):", $response_data);
                                            $logger->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");

                                            return new \WP_REST_Response($response_data, 200);
                                        }

                                        // Oblicz nową wartość
                                        $new_value = $current_value + $skill_value;
                                        if ($new_value < 0) {
                                            $new_value = 0; // Zabezpieczenie przed ujemnymi wartościami (nie powinno już być potrzebne)
                                        }

                                        // Aktualna wartość przed aktualizacją
                                        $logger->debug_log("Aktualne dane umiejętności użytkownika przed aktualizacją:", [
                                            'user_id' => $user_id,
                                            'skill_type' => $skill_type,
                                            'current_value' => $current_value,
                                            'value_to_add' => $skill_value,
                                            'new_value' => $new_value
                                        ]);

                                        // Zaktualizuj wartość umiejętności
                                        $skills[$skill_type] = $new_value;

                                        // Zapisz zaktualizowane umiejętności do ACF
                                        $result = update_field(SKILLS['name'], $skills, 'user_' . $user_id);
                                        $logger->debug_log("Rezultat update_field dla umiejętności: " . ($result ? 'SUKCES' : 'BŁĄD'));

                                        // Sprawdź, czy aktualizacja się powiodła
                                        $updated_skills = get_field(SKILLS['name'], 'user_' . $user_id);
                                        $updated_value = isset($updated_skills[$skill_type]) ? (int)$updated_skills[$skill_type] : 0;
                                        $logger->debug_log("Wartość umiejętności $skill_type po aktualizacji: $updated_value");

                                        // Przygotuj komunikat w zależności od wartości
                                        $skill_label = SKILLS['fields'][$skill_type]['label'];
                                        if ($skill_value > 0) {
                                            $notification = [
                                                'message' => "Zwiększono umiejętność $skill_label o $skill_value",
                                                'status' => 'success'
                                            ];
                                        } elseif ($skill_value < 0) {
                                            $notification = [
                                                'message' => "Zmniejszono umiejętność $skill_label o " . abs($skill_value),
                                                'status' => 'bad'
                                            ];
                                        } else {
                                            // Jeśli wartość jest 0, nie pokazujemy powiadomienia
                                            $notification = null;
                                        }

                                        if ($notification) {
                                            $logger->debug_log("Utworzono powiadomienie dla umiejętności:", $notification);
                                        }

                                        $logger->debug_log("Wykonano aktualizację umiejętności. Nowa wartość $skill_type: $new_value");
                                        break;

                                    case 'exp_rep':
                                        $type = $action['type'] ?? '';
                                        $value = (int)($action['value'] ?? 0);

                                        $logger->debug_log("Wykonuję akcję exp/rep: typ=$type, wartość=$value");

                                        if (empty($type) || !in_array($type, ['exp', 'reputation'])) {
                                            $logger->debug_log("BŁĄD: Nieprawidłowy typ exp_rep: $type");
                                            break;
                                        }

                                        // Pobierz aktualne wartości postępu użytkownika
                                        $progress = get_field(PROGRESS['name'], 'user_' . $user_id);
                                        if (!is_array($progress)) {
                                            $progress = [];
                                            // Zainicjuj domyślne wartości wszystkich pól postępu
                                            foreach (PROGRESS['fields'] as $field_key => $field_data) {
                                                $progress[$field_key] = $field_data['default'];
                                            }
                                        }

                                        // Pobierz aktualną wartość
                                        $current_value = isset($progress[$type]) ? (int)$progress[$type] : 0;
                                        $logger->debug_log("Obecna wartość $type dla użytkownika $user_id: $current_value");

                                        // Oblicz nową wartość
                                        $new_value = $current_value + $value;
                                        if ($new_value < 0 && $type === 'reputation') {
                                            $new_value = 0; // Reputacja nie może być ujemna
                                        }

                                        $logger->debug_log("Aktualne dane postępu użytkownika przed aktualizacją:", [
                                            'user_id' => $user_id,
                                            'progress_type' => $type,
                                            'current_value' => $current_value,
                                            'value_to_add' => $value,
                                            'new_value' => $new_value
                                        ]);

                                        // Zaktualizuj wartość w postępie
                                        $progress[$type] = $new_value;

                                        // Jeśli dodano exp, sprawdź czy trzeba dodać punkty nauki (co 100 exp = 1 punkt nauki)
                                        if ($type === 'exp' && $value > 0) {
                                            $old_level = floor($current_value / 100);
                                            $new_level = floor($new_value / 100);
                                            $learning_points_to_add = $new_level - $old_level;

                                            if ($learning_points_to_add > 0) {
                                                $current_learning_points = isset($progress['learning_points']) ? (int)$progress['learning_points'] : 0;
                                                $progress['learning_points'] = $current_learning_points + $learning_points_to_add;
                                                $logger->debug_log("Dodano $learning_points_to_add punktów nauki (nowy poziom: $new_level)");
                                            }
                                        }

                                        // Zapisz zaktualizowany postęp do ACF
                                        $result = update_field(PROGRESS['name'], $progress, 'user_' . $user_id);
                                        $logger->debug_log("Rezultat update_field dla postępu: " . ($result ? 'SUKCES' : 'BŁĄD'));

                                        // Sprawdź, czy aktualizacja się powiodła
                                        $updated_progress = get_field(PROGRESS['name'], 'user_' . $user_id);
                                        $updated_value = isset($updated_progress[$type]) ? (int)$updated_progress[$type] : 0;
                                        $logger->debug_log("Wartość $type po aktualizacji: $updated_value");

                                        // Przygotuj komunikat
                                        $type_label = PROGRESS['fields'][$type]['label'] ?? $type;
                                        if ($value > 0) {
                                            $notification = [
                                                'message' => "Otrzymano $value punktów: $type_label",
                                                'status' => 'success'
                                            ];
                                        } elseif ($value < 0) {
                                            $notification = [
                                                'message' => "Utracono " . abs($value) . " punktów: $type_label",
                                                'status' => 'bad'
                                            ];
                                        } else {
                                            $notification = null;
                                        }

                                        if ($notification) {
                                            $logger->debug_log("Utworzono powiadomienie dla postępu:", $notification);
                                        }

                                        $logger->debug_log("Wykonano aktualizację $type. Nowa wartość: $new_value");
                                        break;

                                    case 'unlock_area':
                                        $area_id = (int)($action['area'] ?? 0);

                                        $logger->debug_log("Wykonuję akcję odblokowania rejonu: area_id=$area_id");

                                        if (empty($area_id)) {
                                            $logger->debug_log("BŁĄD: Nie podano ID rejonu do odblokowania");
                                            break;
                                        }

                                        // Sprawdź, czy rejon istnieje
                                        $area_post = get_post($area_id);
                                        if (!$area_post || $area_post->post_type !== 'tereny') {
                                            $logger->debug_log("BŁĄD: Rejon o ID $area_id nie istnieje lub nie jest rejonem");
                                            break;
                                        }

                                        $area_name = $area_post->post_title;

                                        // Pobierz dostępne rejony użytkownika
                                        $available_areas = get_field('available_areas', 'user_' . $user_id);
                                        if (!is_array($available_areas)) {
                                            $available_areas = [];
                                        }

                                        $logger->debug_log("Aktualne dostępne rejony użytkownika:", $available_areas);

                                        // Sprawdź, czy rejon nie jest już odblokowany
                                        $already_unlocked = in_array($area_id, $available_areas);

                                        if ($already_unlocked) {
                                            $logger->debug_log("Rejon $area_name jest już dostępny dla użytkownika $user_id");
                                            $notification = [
                                                'message' => "Masz już dostęp do rejonu: $area_name",
                                                'status' => 'info'
                                            ];
                                        } else {
                                            // Dodaj rejon do dostępnych
                                            $available_areas[] = $area_id;

                                            // Zapisz zaktualizowane dostępne rejony
                                            $result = update_field('available_areas', $available_areas, 'user_' . $user_id);
                                            $logger->debug_log("Rezultat update_field dla dostępnych rejonów: " . ($result ? 'SUKCES' : 'BŁĄD'));

                                            // Sprawdź, czy aktualizacja się powiodła
                                            $updated_areas = get_field('available_areas', 'user_' . $user_id);
                                            $found = in_array($area_id, $updated_areas);

                                            $logger->debug_log("Weryfikacja odblokowania rejonu: " . ($found ? 'SUKCES' : 'BŁĄD'));

                                            $notification = [
                                                'message' => "Odblokowano dostęp do nowego rejonu: $area_name",
                                                'status' => 'success'
                                            ];
                                        }

                                        $logger->debug_log("Utworzono powiadomienie dla odblokowania rejonu:", $notification);
                                        break;

                                    case 'mission':
                                        $mission_id = (int)($action['mission_id'] ?? 0);
                                        $mission_status = $action['mission_status'] ?? '';
                                        $task_id = $action['mission_task_id'] ?? '';
                                        $task_status = $action['mission_task_status'] ?? '';
                                        $npc_id = $action['npc_id'] ?? $npc_id; // Używaj ID NPC z akcji lub bieżącego NPC

                                        $logger->debug_log("Wykonuję akcję misji: mission_id=$mission_id, status=$mission_status, task_id=$task_id, task_status=$task_status, npc_id=$npc_id");

                                        if (empty($mission_id)) {
                                            $logger->debug_log("BŁĄD: Nie podano ID misji");

                                            $notification = [
                                                'message' => "Błąd konfiguracji akcji: Brak ID misji",
                                                'status' => 'bad'
                                            ];
                                            break;
                                        }

                                        // Sprawdź, czy misja istnieje w systemie
                                        $mission_post = get_post($mission_id);
                                        if (!$mission_post || $mission_post->post_type !== 'mission') {
                                            $logger->debug_log("BŁĄD: Misja o ID $mission_id nie istnieje lub nie jest misją");

                                            $notification = [
                                                'message' => "Błąd: Misja nie istnieje (ID: $mission_id)",
                                                'status' => 'bad'
                                            ];
                                            break;
                                        }

                                        $mission_name = $mission_post->post_title;
                                        $mission_field_key = 'mission_' . $mission_id;

                                        // Pobierz dane konkretnej misji bezpośrednio z pola użytkownika
                                        $mission_data = get_field($mission_field_key, 'user_' . $user_id);

                                        // Walidacja - czy misja jest zdefiniowana dla użytkownika
                                        if ($mission_data === false) {
                                            $logger->debug_log("Misja $mission_field_key nie znaleziona w danych użytkownika $user_id. Próba inicjalizacji.");

                                            // Sprawdź czy pole ACF istnieje dla tej misji
                                            $acf_field = acf_get_field("field_mission_{$mission_id}");
                                            if (empty($acf_field)) {
                                                $logger->debug_log("BŁĄD: Nie znaleziono definicji pola ACF dla misji $mission_id. Sprawdź konfigurację misji.");

                                                $notification = [
                                                    'message' => "Błąd: Misja niezdefiniowana w systemie (ID: $mission_id)",
                                                    'status' => 'bad'
                                                ];
                                                break;
                                            }
                                        }

                                        if (!is_array($mission_data)) {
                                            $mission_data = [
                                                'status' => 'not_started',
                                                'assigned_date' => date('Y-m-d H:i:s'),
                                                'completion_date' => '',
                                                'tasks' => []
                                            ];
                                            $logger->debug_log("Utworzono nową misję dla użytkownika: $mission_name");
                                        }

                                        $logger->debug_log("Aktualny stan misji użytkownika:", $mission_data);

                                        // Zapisujemy kopię oryginalnych danych do porównania po aktualizacji
                                        $original_mission_data = $mission_data;

                                        // Zaktualizuj status misji, jeśli podano
                                        if (!empty($mission_status)) {
                                            $old_status = $mission_data['status'];
                                            $mission_data['status'] = $mission_status;

                                            // Jeśli misja została ukończona, dodaj datę ukończenia
                                            if ($mission_status === 'completed' && empty($mission_data['completion_date'])) {
                                                $mission_data['completion_date'] = date('Y-m-d H:i:s');
                                            }

                                            $logger->debug_log("Zaktualizowano status misji '$mission_name' z '$old_status' na '$mission_status'");
                                        }

                                        // Zaktualizuj status zadania, jeśli podano
                                        if (!empty($task_id) && !empty($task_status)) {
                                            // Inicjalizuj tablicę zadań, jeśli nie istnieje
                                            if (!isset($mission_data['tasks']) || !is_array($mission_data['tasks'])) {
                                                $mission_data['tasks'] = [];
                                            }

                                            // Sprawdź, czy zadanie już istnieje i jakiego jest typu
                                            $task_exists = isset($mission_data['tasks'][$task_id]);
                                            $is_npc_task = false;
                                            $current_task_value = null;

                                            if ($task_exists) {
                                                $current_task_value = $mission_data['tasks'][$task_id];
                                                $is_npc_task = is_array($current_task_value);
                                            }

                                            // Sprawdź, czy status zadania ma prefiks _npc, co oznacza zadanie z NPC
                                            $is_npc_status = preg_match('/_npc$/', $task_status);

                                            // Jeśli to zadanie z NPC lub już istnieje jako zadanie z NPC
                                            if ($is_npc_status || $is_npc_task) {
                                                $logger->debug_log("Rozpoznano zadanie z NPC: task_id=$task_id, npc_id=$npc_id");

                                                // Jeśli zadanie jeszcze nie istnieje lub nie jest tablicą, zainicjuj je
                                                if (!$task_exists || !$is_npc_task) {
                                                    $old_value = $task_exists ? $mission_data['tasks'][$task_id] : 'not_started';
                                                    $mission_data['tasks'][$task_id] = [
                                                        'status' => $is_npc_status ? 'not_started' : $task_status
                                                    ];
                                                    $logger->debug_log("Przekształcono proste zadanie na zadanie z NPC. Poprzedni status: $old_value");
                                                }

                                                // Jeśli mamy status NPC, zaktualizuj odpowiednie pole dla tego NPC
                                                if ($is_npc_status && $npc_id) {
                                                    // Usuń suffix _npc ze statusu dla NPC
                                                    $clean_status = str_replace('_npc', '', $task_status);

                                                    // Aktualizuj pola NPC
                                                    $npc_field = 'npc_' . $npc_id;
                                                    $npc_target_field = 'npc_target_' . $npc_id;

                                                    $old_npc_status = isset($mission_data['tasks'][$task_id][$npc_field])
                                                        ? $mission_data['tasks'][$task_id][$npc_field]
                                                        : 'not_started';

                                                    $mission_data['tasks'][$task_id][$npc_field] = $clean_status;
                                                    $mission_data['tasks'][$task_id][$npc_target_field] = $clean_status;

                                                    $logger->debug_log("Zaktualizowano status NPC $npc_id w zadaniu '$task_id' z '$old_npc_status' na '$clean_status'");

                                                    // Jeśli NPC został zaliczony jako 'completed', sprawdź czy wszystkie NPC są zaliczone
                                                    // aby zaktualizować ogólny status zadania
                                                    if ($clean_status === 'completed') {
                                                        $all_npcs_completed = true;
                                                        foreach ($mission_data['tasks'][$task_id] as $key => $value) {
                                                            // Sprawdź tylko pola npc_ (nie npc_target_)
                                                            if (strpos($key, 'npc_') === 0 && strpos($key, 'npc_target_') !== 0) {
                                                                if ($value !== 'completed') {
                                                                    $all_npcs_completed = false;
                                                                    break;
                                                                }
                                                            }
                                                        }

                                                        if ($all_npcs_completed) {
                                                            $old_task_status = $mission_data['tasks'][$task_id]['status'];
                                                            $mission_data['tasks'][$task_id]['status'] = 'completed';
                                                            $logger->debug_log("Wszystkie NPC ukończone, aktualizuję ogólny status zadania '$task_id' z '$old_task_status' na 'completed'");
                                                        }
                                                    }
                                                } else {
                                                    // Aktualizuj ogólny status zadania NPC
                                                    $old_task_status = $mission_data['tasks'][$task_id]['status'];
                                                    $mission_data['tasks'][$task_id]['status'] = $task_status;
                                                    $logger->debug_log("Zaktualizowano ogólny status zadania NPC '$task_id' z '$old_task_status' na '$task_status'");
                                                }
                                            } else {
                                                // Standardowe zadanie (nie NPC) - po prostu aktualizuj status
                                                $old_task_status = $mission_data['tasks'][$task_id] ?? 'not_started';
                                                $mission_data['tasks'][$task_id] = $task_status;
                                                $logger->debug_log("Zaktualizowano status standardowego zadania '$task_id' z '$old_task_status' na '$task_status'");
                                            }

                                            // Sprawdź, czy wszystkie zadania są ukończone, aby automatycznie ukończyć misję
                                            if ($task_status === 'completed' || ($is_npc_task && $mission_data['tasks'][$task_id]['status'] === 'completed')) {
                                                $all_completed = true;

                                                foreach ($mission_data['tasks'] as $task_key => $task_value) {
                                                    // Obsługa zarówno prostych stringów jak i tablic dla zadań z NPC
                                                    $task_status_value = is_array($task_value) ? $task_value['status'] : $task_value;

                                                    if ($task_status_value !== 'completed') {
                                                        $all_completed = false;
                                                        break;
                                                    }
                                                }

                                                // Jeśli wszystkie zadania są ukończone, a misja nie jest jeszcze oznaczona jako ukończona
                                                if ($all_completed && $mission_data['status'] !== 'completed') {
                                                    $mission_data['status'] = 'completed';
                                                    $mission_data['completion_date'] = date('Y-m-d H:i:s');
                                                    $logger->debug_log("Automatycznie ukończono misję '$mission_name' (wszystkie zadania ukończone)");
                                                }
                                            }
                                        }

                                        // Zapisz zaktualizowane dane misji, tylko jeśli dane zostały zmienione
                                        if ($mission_data !== $original_mission_data) {
                                            $result = update_field($mission_field_key, $mission_data, 'user_' . $user_id);
                                            $logger->debug_log("Rezultat update_field dla misji $mission_field_key: " . ($result ? 'SUKCES' : 'BŁĄD'));

                                            // Sprawdź, czy aktualizacja się powiodła
                                            $updated_mission_data = get_field($mission_field_key, 'user_' . $user_id);

                                            // Weryfikacja, czy dane zostały poprawnie zapisane
                                            $update_successful = false;
                                            if (is_array($updated_mission_data)) {
                                                // Sprawdź, czy status misji się zmienił
                                                if (!empty($mission_status) && isset($updated_mission_data['status']) && $updated_mission_data['status'] === $mission_status) {
                                                    $update_successful = true;
                                                }

                                                // Sprawdź, czy status zadania się zmienił
                                                if (!empty($task_id) && !empty($task_status)) {
                                                    if (isset($updated_mission_data['tasks'][$task_id])) {
                                                        $updated_task_value = $updated_mission_data['tasks'][$task_id];

                                                        if (is_array($updated_task_value)) {
                                                            // Dla zadania z NPC
                                                            if ($is_npc_status && $npc_id) {
                                                                $npc_field = 'npc_' . $npc_id;
                                                                $clean_status = str_replace('_npc', '', $task_status);
                                                                if (isset($updated_task_value[$npc_field]) && $updated_task_value[$npc_field] === $clean_status) {
                                                                    $update_successful = true;
                                                                }
                                                            } else {
                                                                if (isset($updated_task_value['status']) && $updated_task_value['status'] === $task_status) {
                                                                    $update_successful = true;
                                                                }
                                                            }
                                                        } else {
                                                            // Dla prostego zadania
                                                            if ($updated_task_value === $task_status) {
                                                                $update_successful = true;
                                                            }
                                                        }
                                                    }
                                                }
                                            }

                                            $logger->debug_log("Stan misji po aktualizacji:", $updated_mission_data ?: 'Błąd aktualizacji');

                                            if (!$update_successful) {
                                                $logger->debug_log("UWAGA: Nie udało się zweryfikować aktualizacji misji!");

                                                $notification = [
                                                    'message' => "Wystąpił błąd podczas aktualizacji misji",
                                                    'status' => 'bad'
                                                ];
                                                break;
                                            }
                                        } else {
                                            $logger->debug_log("Misja nie wymaga aktualizacji - dane nie uległy zmianie");
                                        }

                                        // Przygotuj komunikat dla użytkownika
                                        if (!empty($task_id) && !empty($task_status)) {
                                            // Pobierz szczegóły zadania, jeśli możliwe
                                            $task_name = $task_id; // Domyślnie używamy ID zadania
                                            // Usuń sufiks _N (np. _0, _1) z ID zadania przed wyświetleniem
                                            $task_name = preg_replace('/_\d+$/', '', $task_name);

                                            $mission_tasks = get_field('mission_tasks', $mission_id);

                                            if (is_array($mission_tasks)) {
                                                foreach ($mission_tasks as $task) {
                                                    // Sprawdź zarówno dokładne dopasowanie jak i dopasowanie bez sufiksu _N
                                                    $task_id_no_suffix = preg_replace('/_\d+$/', '', $task_id);
                                                    if (
                                                        isset($task['task_id']) &&
                                                        ($task['task_id'] === $task_id ||
                                                            $task['task_id'] === $task_id_no_suffix)
                                                    ) {
                                                        $task_name = $task['task_title'] ?? $task_name;
                                                        $task_name = get_task_title_by_slug($task_name);
                                                        break;
                                                    }
                                                }
                                            } else {
                                                // Jeśli nie znaleziono zadania, spróbujmy rozbić ID zadania na czytelną nazwę
                                                if (strpos($task_name, '-') !== false) {
                                                    $task_parts = explode('-', $task_name);
                                                    if (!empty($task_parts)) {
                                                        // Zamień myślniki na spacje i sformatuj tekst
                                                        $task_name = ucfirst(implode(' ', $task_parts));
                                                        $task_name = get_task_title_by_slug($task_name);
                                                    }
                                                }
                                            }

                                            $status_display = str_replace('_npc', '', $task_status);

                                            $task_name = get_task_title_by_slug($task_name);

                                            if ($status_display === 'completed') {
                                                $notification = [
                                                    'message' => "Ukończono zadanie: $task_name",
                                                    'status' => 'success'
                                                ];
                                            } elseif ($status_display === 'in_progress' || $status_display === 'progress') {
                                                $notification = [
                                                    'message' => "Rozpoczęto zadanie: $task_name",
                                                    'status' => 'info'
                                                ];
                                            } elseif ($status_display === 'failed') {
                                                $notification = [
                                                    'message' => "Nie udało się wykonać zadania: $task_name",
                                                    'status' => 'bad'
                                                ];
                                            } else {
                                                $notification = [
                                                    'message' => "Zaktualizowano status zadania: $task_name",
                                                    'status' => 'info'
                                                ];
                                            }
                                        } elseif (!empty($mission_status)) {
                                            if ($mission_status === 'completed') {
                                                $notification = [
                                                    'message' => "Ukończono misję: $mission_name",
                                                    'status' => 'success'
                                                ];
                                            } elseif ($mission_status === 'in_progress') {
                                                $notification = [
                                                    'message' => "Rozpoczęto misję: $mission_name",
                                                    'status' => 'info'
                                                ];
                                            } elseif ($mission_status === 'failed') {
                                                $notification = [
                                                    'message' => "Nie udało się ukończyć misji: $mission_name",
                                                    'status' => 'bad'
                                                ];
                                            } else {
                                                $notification = [
                                                    'message' => "Zaktualizowano status misji: $mission_name",
                                                    'status' => 'info'
                                                ];
                                            }
                                        }

                                        if ($notification) {
                                            $logger->debug_log("Utworzono powiadomienie dla misji:", $notification);
                                        }
                                        break;

                                    case 'change_area':
                                        $area_id = (int)($action['area'] ?? 0);

                                        $logger->debug_log("Wykonuję akcję zmiany rejonu: area_id=$area_id");

                                        if (empty($area_id)) {
                                            $logger->debug_log("BŁĄD: Nie podano ID rejonu do przeniesienia");
                                            break;
                                        }

                                        // Sprawdź, czy rejon istnieje
                                        $area_post = get_post($area_id);
                                        if (!$area_post || $area_post->post_type !== 'tereny') {
                                            $logger->debug_log("BŁĄD: Rejon o ID $area_id nie istnieje lub nie jest rejonem");
                                            break;
                                        }

                                        $area_name = $area_post->post_title;
                                        $area_slug = $area_post->post_name;

                                        // Pobierz odblokowane rejony użytkownika
                                        $unlocked_areas = get_field('unlocked_areas', 'user_' . $user_id);
                                        if (!is_array($unlocked_areas)) {
                                            $unlocked_areas = [];
                                        }

                                        // Sprawdź, czy rejon jest odblokowany dla użytkownika
                                        $is_unlocked = false;
                                        foreach ($unlocked_areas as $unlocked_area) {
                                            if (isset($unlocked_area['area']) && (int)$unlocked_area['area'] === $area_id) {
                                                $is_unlocked = true;
                                                break;
                                            }
                                        }

                                        // Jeśli rejon nie jest odblokowany, automatycznie go odblokuj
                                        if (!$is_unlocked) {
                                            $logger->debug_log("Rejon $area_name nie jest jeszcze odblokowany, odblokowuję go automatycznie");
                                            $unlocked_areas[] = [
                                                'area' => $area_id
                                            ];
                                            update_field('unlocked_areas', $unlocked_areas, 'user_' . $user_id);
                                        }

                                        // Zapisz lokalizację w danych użytkownika
                                        $result = update_field('current_area', $area_id, 'user_' . $user_id);
                                        $logger->debug_log("Rezultat update_field dla aktualnej lokalizacji: " . ($result ? 'SUKCES' : 'BŁĄD'));

                                        // Dodaj adres URL rejonu do odpowiedzi
                                        $area_url = site_url('/tereny/' . $area_slug . '/');

                                        $notification = [
                                            'message' => "Przemieszczono do rejonu: $area_name",
                                            'status' => 'success',
                                            'redirect' => $area_url
                                        ];

                                        $logger->debug_log("Utworzono powiadomienie dla zmiany rejonu:", $notification);
                                        break;

                                    case 'relation':
                                        // Pobierz wartość zmiany relacji z pola 'change_relation'
                                        $relation_value = (int)($action['change_relation'] ?? 0);
                                        // ID NPC, dla którego zmieniamy relację
                                        $target_npc_id = (int)($action['npc'] ?? $npc_id);
                                        // Flaga 'poznaj' determinuje czy NPC ma zostać oznaczony jako poznany
                                        $mark_as_known = (bool)($action['poznaj'] ?? false);

                                        $logger->debug_log("Wykonuję akcję relacji: npc_id=$target_npc_id, wartość=$relation_value, poznaj=" . ($mark_as_known ? 'tak' : 'nie'));

                                        // Sprawdź czy NPC istnieje
                                        $target_npc = get_post($target_npc_id);
                                        if (!$target_npc || $target_npc->post_type !== 'npc') {
                                            $logger->debug_log("BŁĄD: NPC o ID $target_npc_id nie istnieje");
                                            break;
                                        }

                                        $npc_name = $target_npc->post_title;

                                        // Nazwy pól ACF dla relacji i poznania NPC
                                        $relation_field_key = 'npc-relation-' . $target_npc_id;
                                        $meet_field_key = 'npc-meet-' . $target_npc_id;

                                        // Pobierz aktualny poziom relacji dla tego NPC
                                        $current_relation = (int)get_field($relation_field_key, 'user_' . $user_id);
                                        $logger->debug_log("Obecna relacja z NPC $npc_name dla użytkownika $user_id: $current_relation");

                                        // Oblicz nowy poziom relacji (ograniczenia -100 do 100)
                                        $new_relation = max(-100, min(100, $current_relation + $relation_value));
                                        $logger->debug_log("Aktualne dane relacji przed aktualizacją:", [
                                            'user_id' => $user_id,
                                            'npc_id' => $target_npc_id,
                                            'npc_name' => $npc_name,
                                            'current_relation' => $current_relation,
                                            'value_to_add' => $relation_value,
                                            'new_relation' => $new_relation
                                        ]);

                                        $logger->debug_log("Nazwa pola do update: $relation_field_key");

                                        // Zapisz nową wartość relacji
                                        $result = update_field($relation_field_key, $new_relation, 'user_' . $user_id);

                                        if ($result === false) {
                                            // Spróbuj alternatywną metodę aktualizacji, jeśli update_field nie działa
                                            update_user_meta($user_id, $relation_field_key, $new_relation);
                                            $logger->debug_log("Używam update_user_meta jako alternatywę dla update_field");
                                        }

                                        $logger->debug_log("Rezultat update_field dla relacji z NPC: " . ($result ? 'SUKCES' : 'PRÓBA ALTERNATYWNA'));

                                        // Sprawdź, czy aktualizacja się powiodła
                                        $updated_relation = (int)get_field($relation_field_key, 'user_' . $user_id);
                                        $logger->debug_log("Wartość relacji z NPC $npc_name po aktualizacji: $updated_relation");

                                        // Oznacz NPC jako poznanego, jeśli flaga poznaj jest ustawiona
                                        if ($mark_as_known) {
                                            $already_met = get_field($meet_field_key, 'user_' . $user_id);

                                            if (!$already_met) {
                                                $meet_result = update_field($meet_field_key, true, 'user_' . $user_id);

                                                if ($meet_result === false) {
                                                    // Alternatywna metoda aktualizacji
                                                    update_user_meta($user_id, $meet_field_key, true);
                                                    $logger->debug_log("Używam update_user_meta jako alternatywę dla update_field (poznanie NPC)");
                                                }

                                                $logger->debug_log("Oznaczono NPC $npc_name jako poznanego przez użytkownika $user_id");
                                            }
                                        }

                                        // Generowanie komunikatu dla gracza o zmianie relacji
                                        if ($relation_value > 0) {
                                            $notification = [
                                                'message' => "Twoja relacja z {$npc_name} uległa polepszeniu",
                                                'status' => 'success'
                                            ];
                                        } elseif ($relation_value < 0) {
                                            $notification = [
                                                'message' => "Twoja relacja z {$npc_name} uległa pogorszeniu",
                                                'status' => 'bad'
                                            ];
                                        } else {
                                            // Jeśli zmiana wynosi 0, ale poznaj=1, to informujemy o poznaniu NPC
                                            if ($mark_as_known) {
                                                $notification = [
                                                    'message' => "Poznałeś {$npc_name}",
                                                    'status' => 'info'
                                                ];
                                            } else {
                                                // Jeśli zmiana wynosi 0 i nie poznajemy NPC, nie pokazujemy powiadomienia
                                                $notification = null;
                                            }
                                        }

                                        if ($notification) {
                                            $logger->debug_log("Utworzono powiadomienie dla zmiany relacji:", $notification);
                                        }

                                        $logger->debug_log("Wykonano aktualizację relacji z NPC $npc_name. Nowa wartość: $new_relation");
                                        break;

                                    // Można dodać obsługę innych typów akcji w przyszłości
                                    default:
                                        $logger->debug_log("Nieobsługiwany typ akcji: $action_type");
                                        break;
                                }
                            }
                        } else {
                            $logger->debug_log("Odpowiedź NIE zawiera akcji type_anwser lub jest pusta");
                        }
                    } else {
                        $logger->debug_log("BŁĄD: Nie znaleziono odpowiedzi o indeksie: $answer_index w tablicy odpowiedzi");
                    }
                } else {
                    $logger->debug_log("BŁĄD: Nie znaleziono poprzedniego dialogu o ID: $current_dialog_id");
                }
            } else {
                $logger->debug_log("Brak danych o poprzednim dialogu lub indeksie odpowiedzi - pomijam przetwarzanie akcji");
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

            // Tworzymy obiekt UserContext dla odpowiedniego filtrowania odpowiedzi
            $userContext = new UserContext(new ManagerUser($user_id));
            
            // Przygotuj dane lokalizacji dla kontekstu
            $location_info = [
                'area_slug' => $location,
                'type_page' => $type_page,
                'location_value' => $location_value
            ];
            
            // Filtruj odpowiedzi w dialogu z wykorzystaniem UserContext
            $filtered_dialog = $dialog_manager->get_first_matching_dialog([$dialog], $userContext, $location_info);
            if (!$filtered_dialog) {
                $logger->debug_log("UWAGA: Dialog nie przeszedł filtrowania z UserContext");
                $filtered_dialog = $dialog; // Używamy oryginalnego dialogu jeśli filtrowanie nie zwróciło wyników
            } else {
                $filtered_dialog = $filtered_dialog[0]; // get_first_matching_dialog zwraca tablicę, bierzemy pierwszy element
            }
            
            $logger->debug_log("Dialog po filtrowaniu:", $filtered_dialog);

            // Uproszczenie struktury dialogu
            $simplified_dialog = $dialog_manager->simplify_dialog($filtered_dialog);
            // $logger->debug_log("Uproszczona struktura dialogu:", $simplified_dialog);

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

            // Dodaj powiadomienie jeśli istnieje
            if ($notification) {
                $response_data['notification'] = $notification;
                $logger->debug_log("Dodano powiadomienie do odpowiedzi:", $notification);
            }

            $logger->debug_log("Dane odpowiedzi:", $response_data);
            $logger->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");

            return new \WP_REST_Response($response_data, 200);
        } catch (\Exception $e) {
            // Loguj błąd
            $logger = new NpcLogger();
            $logger->debug_log("POWAŻNY BŁĄD podczas obsługi dialogu: " . $e->getMessage());
            $logger->debug_log("Stack trace: " . $e->getTraceAsString());

            return new \WP_Error(
                'dialog_error',
                __('Wystąpił błąd podczas przetwarzania dialogu: ', 'game') . $e->getMessage(),
                ['status' => 500]
            );
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
DialogHandler::init();
