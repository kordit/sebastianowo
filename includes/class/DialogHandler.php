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

            // Logger
            $logger = new NpcLogger();
            $logger->debug_log("===== ROZPOCZĘCIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");
            $logger->debug_log("Żądanie dialogu: NPC ID: $npc_id, Dialog ID: $dialog_id, User ID: $user_id");

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

            // Można dodać obsługę akcji dialogów (np. zmiana relacji z NPC, dodanie przedmiotu itp.)
            // $dialog_manager->process_dialog_actions($filtered_dialog);

            // Uproszczenie struktury dialogu
            $simplified_dialog = $dialog_manager->simplify_dialog($filtered_dialog);
            $logger->debug_log("Uproszczona struktura dialogu:", $simplified_dialog);

            // Pobierz URL obrazka miniatury dla NPC
            $thumbnail_url = get_the_post_thumbnail_url($npc_id, 'full') ?: '';

            // Zwróć dane dialogu w uproszczonej strukturze
            $response_data = [
                'success' => true,
                'dialog' => $simplified_dialog,
                'npc' => [
                    'id' => $npc->ID,
                    'name' => $npc->post_title,
                    'image' => $thumbnail_url,
                ],
            ];

            $logger->debug_log("Dane odpowiedzi:", $response_data);
            $logger->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA DIALOGU =====");

            return new \WP_REST_Response($response_data, 200);
        } catch (\Exception $e) {
            // Loguj błąd
            $logger = new NpcLogger();
            $logger->debug_log("Błąd podczas obsługi dialogu: " . $e->getMessage());
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
