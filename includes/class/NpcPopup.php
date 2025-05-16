<?php

/**
 * Klasa NpcPopup
 *
 * Klasa odpowiedzialna za obsługę endpointu API dla wyświetlania popupu NPC.
 *
 * @package Game
 * @since 1.0.0
 */

// Załaduj wymagane klasy
require_once get_template_directory() . '/includes/class/NpcPopup/NpcLogger.php';
require_once get_template_directory() . '/includes/class/NpcPopup/DialogManager.php';
require_once get_template_directory() . '/includes/class/NpcPopup/LocationExtractor.php';
require_once get_template_directory() . '/includes/class/NpcPopup/UserContext.php';

class NpcPopup
{
    /**
     * Prefiks dla nazw funkcji i endpointów
     *
     * @var string
     */
    private string $prefix = 'game';

    /**
     * Logger do zapisywania informacji debugowania
     * 
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Menedżer dialogów
     * 
     * @var DialogManager
     */
    private DialogManager $dialogManager;

    /**
     * Ekstraktor lokalizacji z URL
     * 
     * @var LocationExtractor
     */
    private LocationExtractor $locationExtractor;

    /**
     * Konstruktor klasy
     * 
     * Inicjalizuje komponenty i rejestruje endpoint REST API.
     */
    public function __construct()
    {
        // Inicjalizuj logger
        $this->logger = new NpcLogger(get_template_directory() . '/npc_debug.log');

        // Inicjalizuj menedżer dialogów
        $this->dialogManager = new DialogManager($this->logger);

        // Inicjalizuj ekstraktor lokalizacji
        $this->locationExtractor = new LocationExtractor();

        // Zarejestruj endpoint REST API
        add_action('rest_api_init', [$this, 'register_rest_endpoint']);
    }

    private function ensure_user_loaded(): void
    {
        if (!is_user_logged_in() && isset($_COOKIE[LOGGED_IN_COOKIE])) {
            $user = wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in');
            if ($user) {
                wp_set_current_user($user);
            }
        }
    }

    /**
     * Rejestruje endpoint REST API
     */
    public function register_rest_endpoint(): void
    {
        // Endpoint dla głównego dialogu NPC
        register_rest_route("{$this->prefix}/v1", '/npc/popup', [
            'methods' => 'POST',  // Określenie metody HTTP jako POST
            'callback' => [$this, 'get_npc_data'],
            'permission_callback' => function (\WP_REST_Request $request): bool {
                $this->ensure_user_loaded();


                // W trybie deweloperskim zezwalaj na dostęp bez logowania
                $is_dev_mode = defined('WP_DEBUG') && WP_DEBUG;
                $is_logged_in = is_user_logged_in();

                if (!$is_logged_in) {
                    $this->logger->debug_log('Próba dostępu do endpointu NPC przez niezalogowanego użytkownika - ' .
                        ($is_dev_mode ? 'ZEZWOLONO (tryb deweloperski)' : 'ODMÓWIONO'));
                }

                // W trybie deweloperskim zezwalaj na dostęp bez logowania
                return $is_logged_in || $is_dev_mode;
            },
            'args' => [
                'npc_id' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
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
        $current_url = isset($params['current_url']) ? esc_url_raw($params['current_url']) : '';
        $user_id = get_current_user_id();

        if (!$npc_id) {
            $this->logger->debug_log("BŁĄD: Nieprawidłowe ID NPC");
            return new \WP_REST_Response([
                'status' => 'error',
                'message' => 'Nieprawidłowe ID NPC'
            ], 400);
        }

        $location_info = $this->locationExtractor->extract_from_url($current_url);
        $userContext = new UserContext(new ManagerUser($user_id));

        $fields = get_fields($npc_id);
        $dialogs = isset($fields['dialogs']) ? $fields['dialogs'] : [];

        $this->dialogManager->setNpcId($npc_id);
        $this->dialogManager->setUserId($user_id);

        foreach ($dialogs as $dialog) {
            $layout_settings = $dialog['layout_settings'];
            $visibility_settings = $layout_settings['visibility_settings'];
            if (isset($visibility_settings)) {
                foreach ($visibility_settings as $condition) {
                    $acf_layout = $condition['acf_fc_layout'] ?? '';
                    switch ($acf_layout) {
                        case 'condition_mission':
                            $context_for_condition = ['mission' => $userContext->get_missions()];
                            break;
                        case 'condition_npc_relation':
                            $context_for_condition = ['relations' => $userContext->get_relations()];
                            break;
                        case 'condition_task':
                            $context_for_condition = ['task' => $userContext->get_tasks()];
                            break;
                        case 'condition_location':
                            $context_for_condition = ['current_location_text' => $location_info['area_slug'] ?? null];
                            break;
                        case 'condition_inventory':
                            $context_for_condition = ['items' => $userContext->get_item_counts()];
                            break;
                        default:
                            $context_for_condition = [];
                    }
                    $filtered_dialog = $this->dialogManager->get_first_matching_dialog($condition, $context_for_condition);
                }
            }
        }

        $thumbnail_url = '';
        if (has_post_thumbnail($npc_id)) {
            $thumbnail_url = get_the_post_thumbnail_url($npc_id, 'full');
        } else {
            $thumbnail_url = get_template_directory_uri() . '/assets/images/png/postac.png';
        }

        $post_data = get_post($npc_id, ARRAY_A);

        $response_data = [
            'status' => 'success',
            'npc_data' => [
                'id' => $npc_id,
                'name' => get_the_title($npc_id),
                'user_id' => $user_id,
                'thumbnail_url' => $thumbnail_url,
                'title' => $post_data['post_title'],
                'slug' => $post_data['post_name'],
                'dialog' => $filtered_dialog ? $this->dialogManager->simplify_dialog($filtered_dialog) : null
            ]
        ];

        return new \WP_REST_Response($response_data, 200);
    }
}

function game_init_main_classes(): void
{
    // Inicjalizacja klasy NpcPopup do obsługi endpointa popupu NPC
    new NpcPopup();

    // Tutaj można dodać inicjalizację innych klas w przyszłości
}
add_action('init', 'game_init_main_classes', 5);
