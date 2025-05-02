<?php

/**
 * Klasa obsługująca operacje na polach ACF przez REST API
 * 
 * Udostępnia endpointy REST API do pobierania i aktualizacji pól ACF
 */
class ApiAcfHandler
{
    /**
     * Inicjuje klasę i rejestruje punkty końcowe API
     */
    public static function init()
    {
        add_action('rest_api_init', [self::class, 'register_endpoints']);
    }

    /**
     * Rejestruje punkty końcowe REST API dla operacji na polach ACF
     */
    public static function register_endpoints()
    {
        // Endpoint do pobierania pól ACF użytkownika
        register_rest_route('game/v1', '/acf/fields', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_acf_fields'],
            'permission_callback' => '__return_true', // Pozwala na dostęp wszystkim, ale wewnątrz metody są odpowiednie sprawdzenia
        ]);

        // Endpoint do aktualizacji pól ACF użytkownika
        register_rest_route('game/v1', '/acf/update', [
            'methods' => 'POST',
            'callback' => [self::class, 'update_acf_fields'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ]);

        // Endpoint do aktualizacji pól ACF dla konkretnego postu
        register_rest_route('game/v1', '/acf/update-post', [
            'methods' => 'POST',
            'callback' => [self::class, 'update_acf_post_fields'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ]);

        // Endpoint do tworzenia nowego wpisu
        register_rest_route('game/v1', '/post/create', [
            'methods' => 'POST',
            'callback' => [self::class, 'create_custom_post'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ]);
    }

    /**
     * Pobiera aktualne pola ACF dla użytkownika
     * 
     * @param WP_REST_Request $request Obiekt żądania REST
     * @return WP_REST_Response
     */
    public static function get_acf_fields($request)
    {
        // Pobierz ID zalogowanego użytkownika
        $user_id = get_current_user_id();

        // Jeśli użytkownik nie jest zalogowany, zwróć puste struktury danych
        // zamiast błędu 401, co pozwala aplikacji działać w trybie "gościa"
        if (!$user_id) {
            $default_data = [
                'user' => [
                    'id' => 0,
                    'name' => 'Gość',
                ],
                'stats' => [],
                'backpack' => [],
                'equipment' => [],
                'skills' => [],
                'active_missions' => [],
                'relations' => [],
            ];

            return new WP_REST_Response(['success' => true, 'data' => ['fields' => $default_data]], 200);
        }

        // Pobierz dane użytkownika z ACF
        $user_data = [
            'user' => [
                'id' => $user_id,
                'name' => get_user_meta($user_id, 'nickname', true),
            ],
            'stats' => get_field('user_stats', 'user_' . $user_id) ?: [],
            'backpack' => get_field('backpack', 'user_' . $user_id) ?: [],
            'equipment' => get_field('equipment', 'user_' . $user_id) ?: [],
            'skills' => get_field('skills', 'user_' . $user_id) ?: [],
            'active_missions' => get_field('active_missions', 'user_' . $user_id) ?: [],
            'relations' => get_field('relations', 'user_' . $user_id) ?: [],
        ];

        // Zwróć dane jako odpowiedź REST API
        return new WP_REST_Response(['success' => true, 'data' => ['fields' => $user_data]], 200);
    }

    /**
     * Aktualizuje pola ACF dla zalogowanego użytkownika
     * 
     * @param WP_REST_Request $request Obiekt żądania REST
     * @return WP_REST_Response
     */
    public static function update_acf_fields($request)
    {
        // Pobierz parametry z żądania
        $params = $request->get_params();

        // Sprawdź czy pola są dostarczone
        if (!isset($params['fields']) || empty($params['fields'])) {
            return new WP_REST_Response(['message' => 'Nie przekazano danych do aktualizacji'], 400);
        }

        // Pobierz ID zalogowanego użytkownika
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response(['message' => 'Użytkownik nie jest zalogowany'], 401);
        }

        // Pobierz pola do aktualizacji
        $fields = is_string($params['fields']) ? json_decode($params['fields'], true) : $params['fields'];

        if (!$fields || !is_array($fields)) {
            return new WP_REST_Response(['message' => 'Nieprawidłowy format danych'], 400);
        }

        // Aktualizuj każde pole ACF
        $updated_fields = [];
        foreach ($fields as $field_name => $field_value) {
            // Sprawdź, czy mamy do czynienia z polem zagnieżdżonym (notacja z kropką)
            if (strpos($field_name, '.') !== false) {
                // Podziel nazwę pola na części (np. "backpack.gold" na "backpack" i "gold")
                $parts = explode('.', $field_name);
                $parent_field = $parts[0];
                $child_field = $parts[1];

                // Pobierz obecną wartość pola nadrzędnego
                $parent_value = get_field($parent_field, 'user_' . $user_id);

                // Jeśli nie jest tablicą, utwórz nową tablicę
                if (!is_array($parent_value)) {
                    $parent_value = [];
                }

                // Zaktualizuj pole potomne
                $parent_value[$child_field] = is_numeric($field_value) && !is_string($field_value) ?
                    (isset($parent_value[$child_field]) ? $parent_value[$child_field] + $field_value : $field_value) :
                    $field_value;

                // Zapisz całe zaktualizowane pole nadrzędne
                $result = update_field($parent_field, $parent_value, 'user_' . $user_id);
                $updated_fields[$field_name] = $result;

                // Dodaj log dla debugowania
                error_log('Aktualizacja zagnieżdżonego pola: ' . $field_name . ' na wartość: ' . print_r($parent_value, true));
            } else {
                // Standardowa aktualizacja dla niezagnieżdżonych pól
                $result = update_field($field_name, $field_value, 'user_' . $user_id);
                $updated_fields[$field_name] = $result;
            }
        }

        // Zwróć odpowiedź z informacją o aktualizacji
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'message' => 'Pola zostały zaktualizowane',
                'updated' => $updated_fields
            ]
        ], 200);
    }

    /**
     * Aktualizuje pola ACF dla konkretnego wpisu
     * 
     * @param WP_REST_Request $request Obiekt żądania REST
     * @return WP_REST_Response
     */
    public static function update_acf_post_fields($request)
    {
        // Pobierz parametry z żądania
        $params = $request->get_params();

        // Sprawdź wymagane parametry
        if (!isset($params['post_id']) || !isset($params['fields'])) {
            return new WP_REST_Response(['message' => 'Nieprawidłowe parametry'], 400);
        }

        $post_id = intval($params['post_id']);
        $fields = is_string($params['fields']) ? json_decode($params['fields'], true) : $params['fields'];

        if (!$fields || !is_array($fields)) {
            return new WP_REST_Response(['message' => 'Nieprawidłowy format danych'], 400);
        }

        // Aktualizuj każde pole ACF dla danego wpisu
        $updated_fields = [];
        foreach ($fields as $field_name => $field_value) {
            $result = update_field($field_name, $field_value, $post_id);
            $updated_fields[$field_name] = $result;
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'message' => 'Pola wpisu zostały zaktualizowane',
                'updated' => $updated_fields,
                'post_id' => $post_id
            ]
        ], 200);
    }

    /**
     * Tworzy nowy wpis niestandardowy wraz z polami ACF
     * 
     * @param WP_REST_Request $request Obiekt żądania REST
     * @return WP_REST_Response
     */
    public static function create_custom_post($request)
    {
        // Pobierz parametry z żądania
        $params = $request->get_params();

        // Sprawdź wymagane parametry
        if (!isset($params['title']) || !isset($params['post_type'])) {
            return new WP_REST_Response(['message' => 'Brakujące parametry: tytuł lub typ wpisu'], 400);
        }

        $title = sanitize_text_field($params['title']);
        $post_type = sanitize_text_field($params['post_type']);

        // Utwórz nowy wpis
        $post_data = [
            'post_title' => $title,
            'post_status' => 'publish',
            'post_type' => $post_type,
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return new WP_REST_Response(['message' => $post_id->get_error_message()], 500);
        }

        // Jeśli dostarczone są pola ACF, zaktualizuj je
        if (isset($params['acf_fields']) && !empty($params['acf_fields'])) {
            $acf_fields = is_string($params['acf_fields']) ? json_decode($params['acf_fields'], true) : $params['acf_fields'];

            if (is_array($acf_fields)) {
                foreach ($acf_fields as $field_name => $field_value) {
                    update_field($field_name, $field_value, $post_id);
                }
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'message' => 'Wpis został utworzony pomyślnie',
                'post_id' => $post_id,
                'post_title' => $title,
                'post_type' => $post_type
            ]
        ], 201);
    }
}

// Inicjalizacja klasy
ApiAcfHandler::init();
