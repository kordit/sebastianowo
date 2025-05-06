<?php

/**
 * Klasa UserDataEndpoint
 *
 * Klasa odpowiedzialna za obsługę endpointu API dla pobierania danych użytkownika.
 *
 * @package Game
 * @since 1.0.0
 */

class UserDataEndpoint
{
    /**
     * Przestrzeń nazw dla endpointu REST API
     *
     * @var string
     */
    private const API_NAMESPACE = 'game/v1';

    /**
     * Ścieżka endpointu dla pobierania danych użytkownika
     *
     * @var string
     */
    private const USER_DATA_ROUTE = '/get-user-data';

    /**
     * Inicjalizacja klasy UserDataEndpoint
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
            self::USER_DATA_ROUTE,
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'handle_user_data_request'],
                'permission_callback' => [self::class, 'check_permission'],
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
        // W trybie deweloperskim pozwalamy na dostęp bez logowania
        $is_dev_mode = defined('WP_DEBUG') && WP_DEBUG;

        // Sprawdzamy, czy user jest zalogowany lub czy jesteśmy w trybie dev
        if (!is_user_logged_in() && !$is_dev_mode) {
            return new \WP_Error(
                'rest_forbidden',
                __('Musisz być zalogowany, aby uzyskać dostęp do tych danych.', 'game'),
                ['status' => 401]
            );
        }

        return true;
    }

    /**
     * Obsługa żądania danych użytkownika
     *
     * @param WP_REST_Request $request Obiekt żądania.
     * @return WP_REST_Response Odpowiedź z danymi użytkownika lub błąd.
     */
    public static function handle_user_data_request(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            // Pobierz ID zalogowanego użytkownika
            $user_id = get_current_user_id();

            // Jeśli użytkownik nie jest zalogowany, ale jesteśmy w trybie deweloperskim, użyj ID 1
            if ($user_id === 0 && defined('WP_DEBUG') && WP_DEBUG) {
                $user_id = 1;
            }

            // Logger debugowania, jeśli istnieje
            $logger = null;
            if (class_exists('NpcLogger')) {
                $logger = new NpcLogger();
                $logger->debug_log("Obsługa żądania danych użytkownika ID: $user_id");
            }

            // Pobierz dane użytkownika
            $user_data = [];

            // Pobierz dane plecaka używając get_field (ACF)
            $backpack = get_field(BACKPACK['name'], 'user_' . $user_id);
            if (!is_array($backpack)) {
                if ($logger) {
                    $logger->debug_log("Nie znaleziono danych plecaka ACF dla użytkownika $user_id, sprawdzam meta");
                }
                
                // Spróbuj pobrać jako meta dane (zapewnia kompatybilność wsteczną)
                $backpack = get_user_meta($user_id, BACKPACK['name'], true);
                
                if (!is_array($backpack)) {
                    if ($logger) {
                        $logger->debug_log("Nie znaleziono danych plecaka w meta dla użytkownika $user_id, inicjalizuję domyślne wartości");
                    }
                    
                    // Inicjalizacja domyślnymi wartościami
                    $backpack = [];
                    foreach (BACKPACK['fields'] as $field_key => $field_data) {
                        $backpack[$field_key] = $field_data['default'];
                    }
                    
                    // Zapisz zainicjalizowany plecak do pola ACF
                    update_field(BACKPACK['name'], $backpack, 'user_' . $user_id);
                    
                    if ($logger) {
                        $logger->debug_log("Zainicjalizowano nowy plecak dla użytkownika z domyślnymi wartościami", $backpack);
                    }
                }
            }
            
            $user_data['backpack'] = $backpack;
            
            if ($logger) {
                $logger->debug_log("Pobrano dane plecaka użytkownika:", $backpack);
            }

            // Pobierz dane witalności (życie, energia)
            $vitality = get_field(VITALITY['name'], 'user_' . $user_id);
            if (!is_array($vitality)) {
                // Inicjalizacja domyślnymi wartościami
                $vitality = [];
                foreach (VITALITY['fields'] as $field_key => $field_data) {
                    $vitality[$field_key] = $field_data['default'];
                }
                
                // Zapisz zainicjalizowaną witalność do ACF
                update_field(VITALITY['name'], $vitality, 'user_' . $user_id);
            }
            
            $user_data['vitality'] = $vitality;
            
            if ($logger) {
                $logger->debug_log("Pobrano dane witalności użytkownika:", $vitality);
            }

            // Przygotuj odpowiedź
            $response_data = [
                'success' => true,
                'message' => 'Dane użytkownika pobrane pomyślnie',
                'data' => $user_data
            ];

            if ($logger) {
                $logger->debug_log("Zwracam dane użytkownika:", $response_data);
            }

            return new \WP_REST_Response($response_data, 200);
        } catch (\Exception $e) {
            if (isset($logger)) {
                $logger->debug_log("BŁĄD podczas pobierania danych użytkownika: " . $e->getMessage());
                $logger->debug_log("Stack trace: " . $e->getTraceAsString());
            }

            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Wystąpił błąd podczas pobierania danych użytkownika: ' . $e->getMessage()
            ], 500);
        }
    }
}

// Inicjalizacja endpointu
UserDataEndpoint::init();
