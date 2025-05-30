<?php

/**
 * NPC Admin Panel
 * Główny panel administracyjny dla systemu NPC
 */

if (!defined('ABSPATH')) {
    exit;
}

// Upewnij się, że mamy dostęp do funkcji WordPress
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once dirname(__FILE__) . '/../debug.php';

class NPC_AdminPanel
{
    private $npc_repository;
    private $dialog_repository;
    private $answer_repository;

    public function __construct()
    {
        $this->npc_repository = new NPC_NPCRepository();
        $this->dialog_repository = new NPC_DialogRepository();
        $this->answer_repository = new NPC_AnswerRepository();

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_admin_actions']);
        add_action('wp_ajax_npc_get_dialog', [$this, 'ajax_get_dialog']);
        add_action('wp_ajax_npc_get_answer', [$this, 'ajax_get_answer']);
        add_action('wp_ajax_npc_update_dialog_order', [$this, 'ajax_update_dialog_order']);
        add_action('wp_ajax_npc_update_answer_order', [$this, 'ajax_update_answer_order']);
        add_action('wp_ajax_npc_get_items', [$this, 'ajax_get_items']);
        add_action('wp_ajax_npc_get_locations', [$this, 'ajax_get_locations']);
        add_action('wp_ajax_npc_get_missions', [$this, 'ajax_get_missions']);
        add_action('wp_ajax_npc_get_quests_for_mission', [$this, 'ajax_get_quests_for_mission']);
        add_action('wp_ajax_npc_get_locations_with_scenes', [$this, 'ajax_get_locations_with_scenes']);
    }

    /**
     * Dodaje menu w panelu administracyjnym
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'NPC Manager',
            'NPC Manager',
            'manage_options',
            'npc-manager',
            [$this, 'render_npc_list'],
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'npc-manager',
            'Lista NPC',
            'Lista NPC',
            'manage_options',
            'npc-manager',
            [$this, 'render_npc_list']
        );

        add_submenu_page(
            'npc-manager',
            'Dodaj NPC',
            'Dodaj NPC',
            'manage_options',
            'npc-add',
            [$this, 'render_npc_form']
        );
    }

    /**
     * Obsługuje akcje administratora
     */
    public function handle_admin_actions()
    {
        NPC_Debug::log('Rozpoczęcie handle_admin_actions');
        NPC_Debug::log_post();

        if (!isset($_GET['page']) || strpos($_GET['page'], 'npc-') !== 0) {
            NPC_Debug::log('Brak strony NPC w GET');
            return;
        }

        // Sprawdź nonce w POST lub GET
        $nonce = $_POST['npc_nonce'] ?? $_GET['npc_nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'npc_admin_action')) {
            NPC_Debug::log('Nieprawidłowy nonce: ' . $nonce);
            return;
        }

        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        NPC_Debug::log('Akcja:', $action);

        switch ($action) {
            case 'create_npc':
                $this->handle_create_npc();
                break;
            case 'update_npc':
                $this->handle_update_npc();
                break;
            case 'delete_npc':
                $this->handle_delete_npc();
                break;
            case 'create_dialog':
                $this->handle_create_dialog();
                break;
            case 'update_dialog':
                $this->handle_update_dialog();
                break;
            case 'delete_dialog':
                $this->handle_delete_dialog();
                break;
            case 'create_answer':
                $this->handle_create_answer();
                break;
            case 'update_answer':
                NPC_Debug::log('Rozpoczęcie obsługi update_answer');
                $this->handle_update_answer();
                break;
            case 'delete_answer':
                $this->handle_delete_answer();
                break;
        }
    }

    /**
     * Renderuje listę NPC
     */
    public function render_npc_list()
    {
        $npcs = $this->npc_repository->get_all();
        $stats = $this->npc_repository->get_stats();

        include NPC_PLUGIN_PATH . 'admin/views/npc-list.php';
    }

    /**
     * Renderuje formularz NPC
     */
    public function render_npc_form()
    {
        $npc_id = $_GET['npc_id'] ?? 0;
        $npc = null;
        $dialogs = [];
        $locations = [];
        $dialogs_by_location = [];

        if ($npc_id) {
            $npc = $this->npc_repository->get_by_id($npc_id);
            if ($npc) {
                // Inicjalizuj lub napraw wartości dialog_order przed pobraniem dialogów
                $this->dialog_repository->initialize_dialog_order($npc_id);

                // Pobierz dialogi z zaktualizowaną kolejnością
                $dialogs = $this->dialog_repository->get_by_npc_id($npc_id);

                // Usuwamy duplikaty dialogów na podstawie ID
                $unique_dialogs = [];
                $used_ids = [];

                foreach ($dialogs as $dialog) {
                    if (!in_array($dialog->id, $used_ids)) {
                        $used_ids[] = $dialog->id;
                        $unique_dialogs[] = $dialog;
                    }
                }

                $dialogs = $unique_dialogs;

                // Dodaj odpowiedzi do każdego dialogu
                foreach ($dialogs as &$dialog) {
                    $dialog->answers = $this->answer_repository->get_by_dialog_id($dialog->id);
                }
                unset($dialog); // Usuń referencję!

                // Pobierz dostępne lokalizacje z WordPress (post_type='tereny')
                $available_locations = get_posts([
                    'post_type' => 'tereny',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC'
                ]);

                // Pobierz lokalizacje używane przez dialogi tego NPC
                $used_locations = $this->dialog_repository->get_locations_by_npc_id($npc_id);

                // Organizuj dialogi według lokalizacji
                $dialogs_by_location = ['__none__' => []]; // Dialogi bez lokalizacji

                foreach ($dialogs as $dialog) {
                    $location_key = empty($dialog->location) ? '__none__' : $dialog->location;
                    if (!isset($dialogs_by_location[$location_key])) {
                        $dialogs_by_location[$location_key] = [];
                    }
                    $dialogs_by_location[$location_key][] = $dialog;
                }

                // Przygotuj listę lokalizacji do tabu
                $locations = [];

                // Dodaj "Bez lokalizacji" jako pierwszy tab jeśli są takie dialogi
                if (!empty($dialogs_by_location['__none__'])) {
                    $locations[] = [
                        'slug' => '__none__',
                        'title' => 'Bez lokalizacji',
                        'count' => count($dialogs_by_location['__none__'])
                    ];
                }

                // Dodaj używane lokalizacje
                foreach ($used_locations as $location_slug) {
                    $location_post = null;
                    foreach ($available_locations as $loc) {
                        if ($loc->post_name === $location_slug) {
                            $location_post = $loc;
                            break;
                        }
                    }

                    $locations[] = [
                        'slug' => $location_slug,
                        'title' => $location_post ? $location_post->post_title : $location_slug,
                        'count' => isset($dialogs_by_location[$location_slug]) ? count($dialogs_by_location[$location_slug]) : 0
                    ];
                }
            }
        }

        // Pobierz wszystkie dostępne lokalizacje dla selecta
        $all_locations = get_posts([
            'post_type' => 'tereny',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        include NPC_PLUGIN_PATH . 'admin/views/npc-form.php';
    }

    /**
     * Obsługuje tworzenie NPC
     */
    private function handle_create_npc()
    {
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image_url' => esc_url_raw($_POST['image_url']),
            'avatar' => !empty($_POST['avatar']) ? intval($_POST['avatar']) : null,
            'avatar_full' => !empty($_POST['avatar_full']) ? intval($_POST['avatar_full']) : null,
            'avatar_full_back' => !empty($_POST['avatar_full_back']) ? intval($_POST['avatar_full_back']) : null,
            'strength' => intval($_POST['strength'] ?? 0),
            'defence' => intval($_POST['defence'] ?? 0),
            'dexterity' => intval($_POST['dexterity'] ?? 0),
            'perception' => intval($_POST['perception'] ?? 0),
            'technical' => intval($_POST['technical'] ?? 0),
            'charisma' => intval($_POST['charisma'] ?? 0),
            'combat' => intval($_POST['combat'] ?? 0),
            'steal' => intval($_POST['steal'] ?? 0),
            'craft' => intval($_POST['craft'] ?? 0),
            'trade' => intval($_POST['trade'] ?? 0),
            'relations' => intval($_POST['relations'] ?? 0),
            'street' => intval($_POST['street'] ?? 0),
            'life' => intval($_POST['life'] ?? 0),
            'max_life' => intval($_POST['max_life'] ?? 0),
            'metadata' => []
        ];

        $npc_id = $this->npc_repository->create($data);

        if ($npc_id) {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&message=created'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=npc-add&error=create_failed'));
            exit;
        }
    }

    /**
     * Obsługuje aktualizację NPC
     */
    private function handle_update_npc()
    {
        $npc_id = intval($_POST['npc_id']);

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image_url' => esc_url_raw($_POST['image_url']),
            'avatar' => !empty($_POST['avatar']) ? intval($_POST['avatar']) : null,
            'avatar_full' => !empty($_POST['avatar_full']) ? intval($_POST['avatar_full']) : null,
            'avatar_full_back' => !empty($_POST['avatar_full_back']) ? intval($_POST['avatar_full_back']) : null,
            'strength' => intval($_POST['strength'] ?? 0),
            'defence' => intval($_POST['defence'] ?? 0),
            'dexterity' => intval($_POST['dexterity'] ?? 0),
            'perception' => intval($_POST['perception'] ?? 0),
            'technical' => intval($_POST['technical'] ?? 0),
            'charisma' => intval($_POST['charisma'] ?? 0),
            'combat' => intval($_POST['combat'] ?? 0),
            'steal' => intval($_POST['steal'] ?? 0),
            'craft' => intval($_POST['craft'] ?? 0),
            'trade' => intval($_POST['trade'] ?? 0),
            'relations' => intval($_POST['relations'] ?? 0),
            'street' => intval($_POST['street'] ?? 0),
            'life' => intval($_POST['life'] ?? 0),
            'max_life' => intval($_POST['max_life'] ?? 0)
        ];

        $result = $this->npc_repository->update($npc_id, $data);

        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&message=updated'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&error=update_failed'));
            exit;
        }
    }

    /**
     * Obsługuje usuwanie NPC
     */
    private function handle_delete_npc()
    {
        $npc_id = intval($_GET['npc_id']);

        $result = $this->npc_repository->delete($npc_id);

        if ($result) {
            wp_redirect(admin_url('admin.php?page=npc-manager&message=deleted'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=npc-manager&error=delete_failed'));
            exit;
        }
    }

    /**
     * Obsługuje tworzenie dialogu
     */
    private function handle_create_dialog()
    {
        // Sprawdź czy jest to pierwszy dialog dla tego NPC
        $npc_id = intval($_POST['npc_id']);
        $existing_dialogs_count = $this->dialog_repository->count_by_npc($npc_id);

        $data = [
            'npc_id' => $npc_id,
            'title' => sanitize_text_field($_POST['dialog_title']),
            'content' => sanitize_textarea_field($_POST['dialog_content']),
            'dialog_order' => ($existing_dialogs_count === 0) ? 0 : intval($_POST['dialog_order']),
            'location' => !empty($_POST['dialog_location']) ? sanitize_text_field($_POST['dialog_location']) : null,
            'status' => 'active'
        ];

        $dialog_id = $this->dialog_repository->create($data);
        $npc_id = $data['npc_id'];

        if ($dialog_id) {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&message=dialog_created'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&error=dialog_create_failed'));
            exit;
        }
    }

    /**
     * Obsługuje aktualizację dialogu
     */
    private function handle_update_dialog()
    {
        $dialog_id = intval($_POST['dialog_id']);
        $npc_id = intval($_POST['npc_id']);

        // Pobierz aktualny dialog
        $current_dialog = $this->dialog_repository->get_by_id($dialog_id);

        $data = [
            'title' => sanitize_text_field($_POST['dialog_title']),
            'content' => sanitize_textarea_field($_POST['dialog_content']),
            'dialog_order' => intval($_POST['dialog_order']),
            'location' => !empty($_POST['dialog_location']) ? sanitize_text_field($_POST['dialog_location']) : null,
            // Nie używamy już flagi is_starting_dialog
        ];

        $result = $this->dialog_repository->update($dialog_id, $data);

        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&message=dialog_updated'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&error=dialog_update_failed'));
            exit;
        }
    }

    /**
     * Obsługuje usuwanie dialogu
     */
    private function handle_delete_dialog()
    {
        $dialog_id = intval($_GET['dialog_id']);
        $npc_id = intval($_GET['npc_id']);

        $result = $this->dialog_repository->delete($dialog_id);

        if ($result) {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&message=dialog_deleted'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&error=dialog_delete_failed'));
            exit;
        }
    }

    /**
     * AJAX endpoint dla pobierania danych dialogu
     */
    public function ajax_get_dialog()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        $dialog_id = intval($_POST['dialog_id']);
        $dialog = $this->dialog_repository->get_by_id($dialog_id);

        if (!$dialog) {
            wp_send_json_error('Dialog nie został znaleziony');
        }

        wp_send_json_success($dialog);
    }

    /**
     * AJAX endpoint dla pobierania danych odpowiedzi
     */
    public function ajax_get_answer()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        $answer_id = intval($_POST['answer_id']);
        $answer = $this->answer_repository->get_by_id($answer_id);

        if (!$answer) {
            wp_send_json_error('Odpowiedź nie została znaleziona');
        }

        wp_send_json_success($answer);
    }

    /**
     * Obsługuje tworzenie odpowiedzi
     */
    private function handle_create_answer()
    {
        $dialog_id = intval($_POST['dialog_id']);
        $npc_id = intval($_POST['npc_id']);

        // Obsługa akcji
        $actions = [];
        if (!empty($_POST['answer_actions'])) {
            $actions_json = stripslashes($_POST['answer_actions']);
            $actions = json_decode($actions_json, true);
            if (!is_array($actions)) {
                $actions = [];
            }
        }

        $data = [
            'dialog_id' => $dialog_id,
            'text' => sanitize_textarea_field($_POST['answer_text']),
            'next_dialog_id' => empty($_POST['answer_next_dialog_id']) ? null : intval($_POST['answer_next_dialog_id']),
            'answer_order' => intval($_POST['answer_order']),
            'actions' => $actions,
            'status' => 'active'
        ];

        $answer_id = $this->answer_repository->create($data);

        if ($answer_id) {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&message=answer_created'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&error=answer_create_failed'));
            exit;
        }
    }

    /**
     * Obsługuje aktualizację odpowiedzi
     */
    private function handle_update_answer()
    {
        NPC_Debug::log('Rozpoczęcie aktualizacji odpowiedzi');
        NPC_Debug::log_post();

        $answer_id = intval($_POST['answer_id']);
        $npc_id = intval($_POST['npc_id']);

        NPC_Debug::log('ID odpowiedzi:', $answer_id);
        NPC_Debug::log('ID NPC:', $npc_id);

        // Obsługa akcji
        $actions = [];
        if (!empty($_POST['answer_actions'])) {
            $actions = json_decode(wp_unslash($_POST['answer_actions']), true);
            if (!is_array($actions)) {
                $actions = [];
            }
        }

        NPC_Debug::log('Akcje:', $actions);

        $data = [
            'text' => sanitize_textarea_field($_POST['answer_text']),
            'next_dialog_id' => empty($_POST['answer_next_dialog_id']) ? null : intval($_POST['answer_next_dialog_id']),
            'answer_order' => intval($_POST['answer_order']),
            'actions' => $actions,
        ];

        NPC_Debug::log('Dane do aktualizacji:', $data);

        $result = $this->answer_repository->update($answer_id, $data);
        NPC_Debug::log('Wynik aktualizacji:', $result);

        if ($result !== false) {
            NPC_Debug::log('Aktualizacja udana, przekierowanie do:', admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&message=answer_updated'));
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&message=answer_updated'));
            exit;
        } else {
            NPC_Debug::log('Aktualizacja nieudana, przekierowanie do:', admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&error=answer_update_failed'));
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&error=answer_update_failed'));
            exit;
        }
    }

    /**
     * Obsługuje usuwanie odpowiedzi
     */
    private function handle_delete_answer()
    {
        $answer_id = intval($_GET['answer_id']);
        $npc_id = intval($_GET['npc_id']);

        $result = $this->answer_repository->delete($answer_id);

        if ($result) {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&message=answer_deleted'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&error=answer_delete_failed'));
            exit;
        }
    }

    /**
     * AJAX endpoint dla aktualizacji kolejności dialogów
     */
    public function ajax_update_dialog_order()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        $dialog_order = json_decode(stripslashes($_POST['dialog_order']), true);
        if (!is_array($dialog_order)) {
            wp_send_json_error('Nieprawidłowe dane');
        }

        $success = true;

        // Pobierz pierwszy dialog z nowej kolejności, aby ustawić go jako początkowy
        $first_dialog_id = 0;
        $npc_id = 0;

        if (!empty($dialog_order) && isset($dialog_order[0]['id'])) {
            $first_dialog_id = intval($dialog_order[0]['id']);

            // Pobierz NPC ID dla pierwszego dialogu
            $first_dialog = $this->dialog_repository->get_by_id($first_dialog_id);
            if ($first_dialog) {
                $npc_id = $first_dialog->npc_id;
            }
        }

        // Dialog początkowy jest ustalany tylko na podstawie kolejności (dialog_order)
        // Nie używamy już flagi is_starting_dialog

        foreach ($dialog_order as $item) {
            $dialog_id = intval($item['id']);
            $order = intval($item['order']);

            // Aktualizuj tylko kolejność, flag is_starting_dialog jest ustawiana oddzielnie
            $result = $this->dialog_repository->update($dialog_id, ['dialog_order' => $order]);
            if ($result === false) {
                $success = false;
            }
        }

        if ($success) {
            wp_send_json_success('Kolejność dialogów została zaktualizowana');
        } else {
            wp_send_json_error('Wystąpił błąd podczas aktualizacji kolejności dialogów');
        }
    }

    /**
     * AJAX endpoint dla aktualizacji kolejności odpowiedzi
     */
    public function ajax_update_answer_order()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        $dialog_id = intval($_POST['dialog_id']);
        $answer_order = json_decode(stripslashes($_POST['answer_order']), true);
        if (!is_array($answer_order)) {
            wp_send_json_error('Nieprawidłowe dane');
        }

        $success = true;
        foreach ($answer_order as $item) {
            $answer_id = intval($item['id']);
            $order = intval($item['order']);

            $result = $this->answer_repository->update($answer_id, ['answer_order' => $order]);
            if ($result === false) {
                $success = false;
            }
        }

        if ($success) {
            wp_send_json_success('Kolejność odpowiedzi została zaktualizowana');
        } else {
            wp_send_json_error('Wystąpił błąd podczas aktualizacji kolejności odpowiedzi');
        }
    }

    /**
     * AJAX endpoint dla pobierania przedmiotów z CPT items
     */
    public function ajax_get_items()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        global $wpdb;

        $items = $wpdb->get_results(
            "SELECT ID, post_title FROM {$wpdb->posts} 
             WHERE post_type = 'item' AND post_status = 'publish'
             ORDER BY post_title ASC"
        );

        $items_data = [];
        foreach ($items as $item) {
            $items_data[] = [
                'id' => $item->ID,
                'title' => $item->post_title
            ];
        }

        wp_send_json_success($items_data);
    }

    /**
     * AJAX endpoint dla pobierania misji z tabeli game_user_mission_tasks
     */
    public function ajax_get_missions()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        global $wpdb;

        $missions = $wpdb->get_results(
            "SELECT DISTINCT mission_id, mission_title 
             FROM {$wpdb->prefix}game_user_mission_tasks 
             WHERE mission_title != '' 
             ORDER BY mission_title ASC"
        );

        $missions_data = [];
        foreach ($missions as $mission) {
            $missions_data[] = [
                'id' => $mission->mission_id,
                'title' => $mission->mission_title
            ];
        }

        wp_send_json_success($missions_data);
    }

    /**
     * AJAX endpoint dla pobierania zadań z wybranej misji
     */
    public function ajax_get_quests_for_mission()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        $mission_id = intval($_POST['mission_id'] ?? 0);
        if (!$mission_id) {
            wp_send_json_error('Nieprawidłowe ID misji');
        }

        global $wpdb;

        $quests = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT task_id, task_title 
             FROM {$wpdb->prefix}game_user_mission_tasks 
             WHERE mission_id = %d AND task_title != '' 
             ORDER BY task_title ASC",
            $mission_id
        ));

        $quests_data = [];
        foreach ($quests as $quest) {
            $quests_data[] = [
                'id' => $quest->task_id,
                'title' => $quest->task_title
            ];
        }

        wp_send_json_success($quests_data);
    }

    /**
     * AJAX endpoint dla pobierania lokalizacji z post_type='tereny'
     */
    public function ajax_get_locations_with_scenes()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_admin_nonce')) {
            NPC_Debug::log('Nieprawidłowy nonce w ajax_get_locations_with_scenes');
            NPC_Debug::log('Otrzymany nonce:', $_POST['nonce']);
            wp_send_json_error('Nieprawidłowy nonce');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        $locations = get_posts([
            'post_type' => 'tereny',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $locations_data = [];

        foreach ($locations as $location) {
            $scenes = [];
            if (have_rows('scenes', $location->ID)) {
                while (have_rows('scenes', $location->ID)) {
                    the_row();
                    $scenes[] = [
                        'id' => get_sub_field('id_sceny'),
                        'title' => get_sub_field('nazwa') ?: ('Scena ' . get_sub_field('id_sceny'))
                    ];
                }
            }

            $locations_data[] = [
                'id' => $location->post_name,
                'title' => $location->post_title,
                'slug' => $location->post_name,
                'scenes' => $scenes
            ];
        }

        wp_send_json_success($locations_data);
    }
}
