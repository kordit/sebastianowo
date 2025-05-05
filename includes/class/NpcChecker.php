<?php

/**
 * Klasa NpcChecker
 *
 * Narzędzie deweloperskie do testowania i walidacji danych NPC.
 * Zwraca pełne, niefiltrowane dane NPC dla celów debugowania i rozwoju.
 *
 * @package Game
 * @since 1.0.0
 */

class NpcChecker
{
    /**
     * Prefiks dla nazw funkcji i endpointów
     *
     * @var string
     */
    private string $prefix = 'game';

    /**
     * Konstruktor klasy
     * 
     * Rejestruje endpoint REST API.
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_rest_endpoint']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_debug_script']);
    }

    /**
     * Rejestruje endpoint REST API
     */
    public function register_rest_endpoint(): void
    {
        register_rest_route("{$this->prefix}/v1", '/npc/debug', [
            'methods' => 'POST',
            'callback' => [$this, 'get_npc_full_data'],
            'permission_callback' => function () {
                // Sprawdzamy, czy bieżący użytkownik jest administratorem
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * Dołącza skrypt deweloperski do interfejsu użytkownika
     */
    public function enqueue_debug_script(): void
    {
        // Dodajemy skrypt tylko dla administratorów
        if (current_user_can('manage_options')) {
            wp_enqueue_script(
                'npc-debug-script',
                get_template_directory_uri() . '/js/modules/npc/npc-debug.js',
                ['jquery', 'axios'],
                filemtime(get_template_directory() . '/js/modules/npc/npc-debug.js'),
                true
            );

            // Dodajemy lokalizację dla skryptu
            wp_localize_script('npc-debug-script', 'npcDebugData', [
                'ajaxUrl' => rest_url("{$this->prefix}/v1/npc/debug"),
                'nonce' => wp_create_nonce('wp_rest')
            ]);
        }
    }

    /**
     * Zwraca pełne dane NPC bez filtrowania
     *
     * @param WP_REST_Request $request Obiekt żądania
     * @return WP_REST_Response
     */
    public function get_npc_full_data(\WP_REST_Request $request): \WP_REST_Response
    {
        $params = $request->get_params();
        $npc_id = isset($params['npc_id']) ? absint($params['npc_id']) : 0;
        $page_data = isset($params['page_id']) ? json_decode(stripslashes($params['page_id']), true) : [];
        $current_url = isset($params['current_url']) ? esc_url_raw($params['current_url']) : '';

        // Sprawdź uprawnienia raz jeszcze (bezpieczeństwo)
        if (!current_user_can('manage_options')) {
            return new \WP_REST_Response([
                'status' => 'error',
                'message' => 'Brak wymaganych uprawnień do wykonania tej operacji'
            ], 403);
        }

        if (!$npc_id) {
            return new \WP_REST_Response([
                'status' => 'error',
                'message' => 'Nieprawidłowe ID NPC'
            ], 400);
        }

        // Pobierz wszystkie pola ACF dla NPC
        $fields = get_fields($npc_id);

        // Pobierz dodatkowe informacje o NPC
        $npc_post = get_post($npc_id);
        $npc_meta = get_post_meta($npc_id);
        $acf_fields = get_fields($npc_id, false); // Pobierz surowe dane ACF

        // Przygotuj pełną odpowiedź z wszystkimi możliwymi danymi
        $response_data = [
            'status' => 'success',
            'request_params' => [
                'npc_id' => $npc_id,
                'page_data' => $page_data,
                'current_url' => $current_url
            ],
            'npc_data' => [
                'id' => $npc_id,
                'name' => get_the_title($npc_id),
                'post_data' => $npc_post ? [
                    'post_title' => $npc_post->post_title,
                    'post_content' => $npc_post->post_content,
                    'post_status' => $npc_post->post_status,
                    'post_type' => $npc_post->post_type,
                    'post_date' => $npc_post->post_date,
                ] : null,
                'acf_data' => $fields,
                'acf_raw' => $acf_fields,
                'meta_data' => $npc_meta,
                'dialogs' => isset($fields['dialogs']) ? $fields['dialogs'] : [],
            ]
        ];

        return new \WP_REST_Response($response_data, 200);
    }
}

// Inicjalizacja klasy
$npc_checker = new NpcChecker();
