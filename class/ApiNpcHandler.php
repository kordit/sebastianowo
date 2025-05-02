<?php

/**
 * Klasa obsługująca zapytania API związane z NPC
 * 
 * Udostępnia endpointy REST API dla obsługi interakcji z NPC
 */
class ApiNpcHandler
{
    /**
     * Inicjuje klasę i rejestruje punkty końcowe API
     */
    public static function init()
    {
        add_action('rest_api_init', [self::class, 'register_endpoints']);
    }

    /**
     * Rejestruje punkty końcowe REST API dla NPC
     */
    public static function register_endpoints()
    {
        register_rest_route('game/v1', '/npc/popup', [
            'methods' => 'POST',
            'callback' => [self::class, 'get_npc_popup'],
            'permission_callback' => '__return_true', // Można zmienić na własną funkcję sprawdzającą uprawnienia
        ]);
    }

    /**
     * Obsługuje zapytanie GET o dane popupu NPC
     * 
     * @param WP_REST_Request $request Obiekt żądania REST
     * @return WP_REST_Response
     */
    public static function get_npc_popup($request)
    {
        $params = $request->get_params();

        $npc_id = isset($params['npc_id']) ? intval($params['npc_id']) : 0;
        $current_url = isset($params['current_url']) ? esc_url_raw($params['current_url']) : '';
        $conditions = isset($params['page_id']) ? json_decode($params['page_id'], true) : [];

        $id_conversation = isset($params['id_conversation']) ? sanitize_text_field($params['id_conversation']) : null;

        if (!$npc_id) {
            return new WP_REST_Response(['error' => 'Brak ID NPC'], 400);
        }

        $npc_data = [
            'npc_id'              => $npc_id,
            'popup_id'            => 'npc-popup',
            'active'              => false,
            'current_url'         => $current_url,
            'current_state_array' => $conditions
        ];

        $dialogue = get_dialogue($npc_data, $id_conversation, $conditions);
        if (empty($dialogue)) {
            return new WP_REST_Response(['error' => 'Nie znaleziono dialogu dla NPC'], 404);
        }

        $dialogue['npc_post_title'] = get_the_title($npc_id);

        return new WP_REST_Response(['npc_data' => $dialogue], 200);
    }
}

// Inicjalizacja klasy
ApiNpcHandler::init();
