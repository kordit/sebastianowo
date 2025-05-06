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
require_once get_template_directory() . '/includes/class/NpcPopup/ConditionChecker.php';
require_once get_template_directory() . '/includes/class/NpcPopup/ConditionCheckerFactory.php';
require_once get_template_directory() . '/includes/class/NpcPopup/DialogManager.php';
require_once get_template_directory() . '/includes/class/NpcPopup/LocationExtractor.php';

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

        /* // Sprawdź, czy użytkownik jest zalogowany
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

        return true; */
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
            $logger->debug_log("===== ROZPOCZĘCIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");
            $logger->debug_log("Żądanie dialogu: NPC ID: $npc_id, Dialog ID: $dialog_id, User ID: $user_id");

            // Zapisz całe dane żądania do logu
            $logger->debug_log("WSZYSTKIE DANE ŻĄDANIA:", $request->get_params());

            if ($answer_id && $current_dialog_id) {
                $logger->debug_log("Dane odpowiedzi: ID: $answer_id, Index: $answer_index, Poprzedni dialog: $current_dialog_id");
            }

            // Sprawdź aktualny stan zasobów użytkownika na początku
            $current_backpack = get_field(BACKPACK['name'], 'user_' . $user_id);
            if (is_array($current_backpack)) {
                $logger->debug_log("STAN PLECAKA PRZED PRZETWARZANIEM DIALOGU:", $current_backpack);
            } else {
                $logger->debug_log("UWAGA: Brak inicjalizacji plecaka dla użytkownika $user_id przed przetwarzaniem dialogu");
            }

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
                $logger->debug_log("Błąd: NPC $npc_id nie ma zdefiniowanych dialogów");
                return new \WP_Error(
                    'no_dialogs',
                    __('Ten NPC nie ma zdefiniowanych dialogów.', 'game'),
                    ['status' => 404]
                );
            }

            $logger->debug_log("Pobrane dialogi dla NPC $npc_id", $dialogs);

            // Przygotuj zarządzanie dialogiem
            $checker_factory = new ConditionCheckerFactory($logger);
            $dialog_manager = new DialogManager($logger, $checker_factory);
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

                    // Filtruj odpowiedzi w dialogu
                    $filtered_dialog = $dialog_manager->filter_answers($dialog, $criteria);
                    $logger->debug_log("Dialog po filtrowaniu:", $filtered_dialog);

                    // Uproszczenie struktury dialogu
                    $simplified_dialog = $dialog_manager->simplify_dialog($filtered_dialog);
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

                            // Przetwórz każdą akcję w odpowiedzi
                            foreach ($answer['type_anwser'] as $action) {
                                $logger->debug_log("Przetwarzanie akcji:", $action);
                                $action_type = $action['acf_fc_layout'] ?? '';
                                $logger->debug_log("Typ akcji: $action_type");

                                switch ($action_type) {
                                    case 'transaction':
                                        $currency = $action['backpack'] ?? '';
                                        $value = (int)($action['value'] ?? 0);

                                        $logger->debug_log("Wykonuję transakcję: waluta=$currency, wartość=$value");

                                        // Pobierz dane plecaka za pomocą ACF
                                        $backpack = get_field(BACKPACK['name'], 'user_' . $user_id);
                                        if (!is_array($backpack)) {
                                            $backpack = [];
                                            // Zainicjuj domyślne wartości wszystkich pól plecaka
                                            foreach (BACKPACK['fields'] as $field_key => $field_data) {
                                                $backpack[$field_key] = $field_data['default'];
                                            }
                                        }

                                        // Zapisz obecną wartość waluty do logów
                                        $current_value = isset($backpack[$currency]) ? (int)$backpack[$currency] : 0;
                                        $logger->debug_log("Obecna wartość waluty $currency w plecaku dla użytkownika $user_id: $current_value");

                                        // Jeśli próbujemy zabrać walutę (wartość ujemna), sprawdź czy użytkownik ma jej wystarczającą ilość
                                        if ($value < 0 && abs($value) > $current_value) {
                                            $logger->debug_log("NIEPOWODZENIE TRANSAKCJI: Próba zabrania {$value} $currency, ale użytkownik ma tylko $current_value");
                                            
                                            // Przygotuj powiadomienie o niewystarczających środkach
                                            $notification = [
                                                'message' => "Nie masz wystarczającej ilości $currency! Potrzeba " . abs($value) . ", a masz $current_value.",
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

            // Filtruj odpowiedzi w dialogu
            $filtered_dialog = $dialog_manager->filter_answers($dialog, $criteria);
            $logger->debug_log("Dialog po filtrowaniu:", $filtered_dialog);

            // Uproszczenie struktury dialogu
            $simplified_dialog = $dialog_manager->simplify_dialog($filtered_dialog);
            $logger->debug_log("Uproszczona struktura dialogu:", $simplified_dialog);

            // Pobierz URL obrazka miniatury dla NPC
            $thumbnail_url = get_the_post_thumbnail_url($npc_id, 'full') ?: '';

            // Sprawdź finalny stan zasobów użytkownika
            $final_gold = (int)get_user_meta($user_id, 'gold', true);
            $final_papierosy = (int)get_user_meta($user_id, 'papierosy', true);
            $logger->debug_log("STAN ZASOBÓW PO TRANSAKCJI - Gold: $final_gold, Papierosy: $final_papierosy");
            $logger->debug_log("ZMIANA ZASOBÓW - Gold: " . ($final_gold - $current_gold) . ", Papierosy: " . ($final_papierosy - $current_papierosy));

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
}
DialogHandler::init();
