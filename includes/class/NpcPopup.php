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
require_once get_template_directory() . '/includes/class/NpcPopup/ConditionChecker.php';
require_once get_template_directory() . '/includes/class/NpcPopup/LocationConditionChecker.php';
require_once get_template_directory() . '/includes/class/NpcPopup/RelationConditionChecker.php';
require_once get_template_directory() . '/includes/class/NpcPopup/InventoryConditionChecker.php';
require_once get_template_directory() . '/includes/class/NpcPopup/MissionConditionChecker.php';
require_once get_template_directory() . '/includes/class/NpcPopup/TaskConditionChecker.php';
require_once get_template_directory() . '/includes/class/NpcPopup/ConditionCheckerFactory.php';
require_once get_template_directory() . '/includes/class/NpcPopup/DialogManager.php';
require_once get_template_directory() . '/includes/class/NpcPopup/LocationExtractor.php';

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

        // Inicjalizuj fabrykę sprawdzaczy warunków
        $checkerFactory = new ConditionCheckerFactory($this->logger);

        // Inicjalizuj menedżera dialogów
        $this->dialogManager = new DialogManager($this->logger, $checkerFactory);

        // Inicjalizuj ekstraktor lokalizacji
        $this->locationExtractor = new LocationExtractor();

        // Zarejestruj endpoint REST API
        add_action('rest_api_init', [$this, 'register_rest_endpoint']);
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
                // Zapewnij, że WordPress wie, że jesteśmy zalogowani
                if (!defined('DOING_AJAX')) {
                    define('DOING_AJAX', true);
                }

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
        $page_data = isset($params['page_data']) ? $params['page_data'] : [];
        $current_url = isset($params['current_url']) ? esc_url_raw($params['current_url']) : '';

        // Rozpocznij logowanie dla nowego żądania
        $this->logger->debug_log("===== ROZPOCZĘCIE PRZETWARZANIA ŻĄDANIA NPC =====");
        $this->logger->debug_log("Parametry żądania:", [
            'npc_id' => $npc_id,
            'page_data' => $page_data,
            'current_url' => $current_url
        ]);

        // Wyodrębnij informacje o lokalizacji
        $location = $this->locationExtractor->extract_location_from_url($current_url);
        $type_page = isset($page_data['TypePage']) ? sanitize_text_field($page_data['TypePage']) : '';
        $location_value = isset($page_data['value']) ? sanitize_text_field($page_data['value']) : '';

        // Pobierz ID użytkownika - używamy cookie do identyfikacji
        $user_id = get_current_user_id();

        // Jeśli user_id to 0, ale mamy ciasteczko sesji, spróbujmy odtworzyć sesję
        if ($user_id === 0 && isset($_COOKIE[LOGGED_IN_COOKIE])) {
            $this->logger->debug_log("Wykryto ciasteczko logowania, próba odtworzenia sesji");
            $user = wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in');
            if ($user) {
                $user_id = $user;
                $this->logger->debug_log("Odtworzono sesję dla użytkownika: {$user_id}");
            }
        }

        $this->logger->debug_log("Wyodrębnione dane lokalizacji:", [
            'location' => $location,
            'type_page' => $type_page,
            'location_value' => $location_value,
            'user_id' => $user_id
        ]);

        $criteria = [
            'type_page' => $type_page,
            'location' => $location_value,
            'user_id' => $user_id,
            'npc_id' => $npc_id
        ];

        if (!$npc_id) {
            $this->logger->debug_log("BŁĄD: Nieprawidłowe ID NPC");
            return new \WP_REST_Response([
                'status' => 'error',
                'message' => 'Nieprawidłowe ID NPC'
            ], 400);
        }

        // Pobierz pola ACF dla NPC
        $fields = get_fields($npc_id);
        $dialogs = isset($fields['dialogs']) ? $fields['dialogs'] : [];

        $this->logger->debug_log("Pobrane dialogi dla NPC {$npc_id}:", $dialogs);

        // Filtruj dialogi na podstawie lokalizacji, relacji NPC i innych kryteriów
        $filtered_dialog = $this->dialogManager->get_first_matching_dialog($dialogs, $criteria);
        $this->logger->debug_log("Wybrany dialog po filtrowaniu:", $filtered_dialog);

        // Jeśli znaleziono dialog, filtruj także jego odpowiedzi
        if ($filtered_dialog) {
            $filtered_dialog = $this->dialogManager->filter_answers($filtered_dialog, $criteria);
            $this->logger->debug_log("Dialog po filtrowaniu odpowiedzi:", $filtered_dialog);
        }

        // Pobierz URL obrazka miniatury dla NPC
        $thumbnail_url = '';
        if (has_post_thumbnail($npc_id)) {
            // Pobierz URL obrazka w pełnym rozmiarze
            $thumbnail_url = get_the_post_thumbnail_url($npc_id, 'full');
        } else {
            // Jeśli nie ma miniatury, można ustawić domyślny obrazek
            $thumbnail_url = get_template_directory_uri() . '/assets/images/png/postac.png';
        }

        $this->logger->debug_log("URL miniatury NPC: {$thumbnail_url}");

        // Pobierz dodatkowe dane o wpisie
        $post_data = get_post($npc_id, ARRAY_A);

        // Przygotuj dane odpowiedzi z pojedynczym dialogiem
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

        $this->logger->debug_log("Dane odpowiedzi:", $response_data);
        $this->logger->debug_log("===== ZAKOŃCZENIE PRZETWARZANIA ŻĄDANIA NPC =====");

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
