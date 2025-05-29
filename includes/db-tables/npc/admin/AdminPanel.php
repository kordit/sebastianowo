<?php

/**
 * NPC Admin Panel
 * Główny panel administracyjny dla systemu NPC
 */

if (!defined('ABSPATH')) {
    exit;
}

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
        if (!isset($_GET['page']) || strpos($_GET['page'], 'npc-') !== 0) {
            return;
        }

        if (!wp_verify_nonce($_POST['npc_nonce'] ?? '', 'npc_admin_action')) {
            return;
        }

        $action = $_POST['action'] ?? $_GET['action'] ?? '';

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

        if ($npc_id) {
            $npc = $this->npc_repository->get_by_id($npc_id);
            if ($npc) {
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
            }
        }

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
            'image_url' => esc_url_raw($_POST['image_url'])
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
        require_once dirname(__FILE__) . '/components/ConditionManager.php';

        $data = [
            'npc_id' => intval($_POST['npc_id']),
            'title' => sanitize_text_field($_POST['dialog_title']),
            'content' => sanitize_textarea_field($_POST['dialog_content']),
            'dialog_order' => intval($_POST['dialog_order']),
            'is_starting_dialog' => isset($_POST['is_starting_dialog']) ? 1 : 0,
            'conditions' => NPC_ConditionManager::sanitize_conditions($_POST['dialog_conditions'] ?? ''),
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
        require_once dirname(__FILE__) . '/components/ConditionManager.php';

        $dialog_id = intval($_POST['dialog_id']);
        $npc_id = intval($_POST['npc_id']);

        $data = [
            'title' => sanitize_text_field($_POST['dialog_title']),
            'content' => sanitize_textarea_field($_POST['dialog_content']),
            'dialog_order' => intval($_POST['dialog_order']),
            'is_starting_dialog' => isset($_POST['is_starting_dialog']) ? 1 : 0,
            'conditions' => NPC_ConditionManager::sanitize_conditions($_POST['dialog_conditions'] ?? '')
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
        require_once dirname(__FILE__) . '/components/ConditionManager.php';

        $dialog_id = intval($_POST['dialog_id']);
        $npc_id = intval($_POST['npc_id']);

        $data = [
            'dialog_id' => $dialog_id,
            'text' => sanitize_textarea_field($_POST['answer_text']),
            'next_dialog_id' => empty($_POST['answer_next_dialog_id']) ? null : intval($_POST['answer_next_dialog_id']),
            'answer_order' => intval($_POST['answer_order']),
            'conditions' => NPC_ConditionManager::sanitize_conditions($_POST['answer_conditions'] ?? ''),
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
        require_once dirname(__FILE__) . '/components/ConditionManager.php';

        $answer_id = intval($_POST['answer_id']);
        $npc_id = intval($_POST['npc_id']);

        $data = [
            'text' => sanitize_textarea_field($_POST['answer_text']),
            'next_dialog_id' => empty($_POST['answer_next_dialog_id']) ? null : intval($_POST['answer_next_dialog_id']),
            'answer_order' => intval($_POST['answer_order']),
            'conditions' => NPC_ConditionManager::sanitize_conditions($_POST['answer_conditions'] ?? '')
        ];

        $result = $this->answer_repository->update($answer_id, $data);

        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=npc-add&npc_id=' . $npc_id . '&message=answer_updated'));
            exit;
        } else {
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
        foreach ($dialog_order as $item) {
            $dialog_id = intval($item['id']);
            $order = intval($item['order']);

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
}
