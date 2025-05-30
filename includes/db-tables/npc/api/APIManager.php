<?php

/**
 * NPC API Manager
 * Zarządza REST API endpoints dla systemu NPC
 */

if (!defined('ABSPATH')) {
    exit;
}

class NPC_APIManager
{
    private $namespace = 'npc/v1';
    private $npc_repository;
    private $dialog_repository;
    private $answer_repository;

    public function __construct()
    {
        $this->npc_repository = new NPC_NPCRepository();
        $this->dialog_repository = new NPC_DialogRepository();
        $this->answer_repository = new NPC_AnswerRepository();

        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('wp_ajax_npc_get_dialog', [$this, 'ajax_get_dialog']);
        add_action('wp_ajax_npc_auto_save', [$this, 'ajax_auto_save']);
    }

    /**
     * Rejestruje REST API routes
     */
    public function register_routes()
    {
        // NPC endpoints
        register_rest_route($this->namespace, '/npcs', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_npcs'],
                'permission_callback' => '__return_true'
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_npc'],
                'permission_callback' => [$this, 'check_admin_permissions']
            ]
        ]);

        register_rest_route($this->namespace, '/npcs/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_npc'],
                'permission_callback' => '__return_true'
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_npc'],
                'permission_callback' => [$this, 'check_admin_permissions']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_npc'],
                'permission_callback' => [$this, 'check_admin_permissions']
            ]
        ]);

        // Dialog endpoints
        register_rest_route($this->namespace, '/npcs/(?P<npc_id>\d+)/dialogs', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_dialogs'],
                'permission_callback' => '__return_true'
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_dialog'],
                'permission_callback' => [$this, 'check_admin_permissions']
            ]
        ]);

        register_rest_route($this->namespace, '/dialogs/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_dialog'],
                'permission_callback' => '__return_true'
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_dialog'],
                'permission_callback' => [$this, 'check_admin_permissions']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_dialog'],
                'permission_callback' => [$this, 'check_admin_permissions']
            ]
        ]);

        // Answer endpoints
        register_rest_route($this->namespace, '/dialogs/(?P<dialog_id>\d+)/answers', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_answers'],
                'permission_callback' => '__return_true'
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_answer'],
                'permission_callback' => [$this, 'check_admin_permissions']
            ]
        ]);

        register_rest_route($this->namespace, '/answers/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_answer'],
                'permission_callback' => '__return_true'
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_answer'],
                'permission_callback' => [$this, 'check_admin_permissions']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_answer'],
                'permission_callback' => [$this, 'check_admin_permissions']
            ]
        ]);

        // Special endpoints
        register_rest_route($this->namespace, '/npcs/(?P<npc_id>\d+)/dialog/starting', [
            'methods' => 'GET',
            'callback' => [$this, 'get_starting_dialog'],
            'permission_callback' => '__return_true'
        ]);

        // Kompatybilność z istniejącym frontendem
        register_rest_route('game/v1', '/dialog', [
            'methods' => 'POST',
            'callback' => [$this, 'get_frontend_dialog'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Sprawdza uprawnienia administratora
     */
    public function check_admin_permissions()
    {
        return current_user_can('manage_options');
    }

    /**
     * Pobiera listę NPC
     */
    public function get_npcs($request)
    {
        try {
            $status = $request->get_param('status') ?: 'active';
            $location = $request->get_param('location');

            if ($location) {
                $npcs = $this->npc_repository->get_by_location($location);
            } else {
                $npcs = $this->npc_repository->get_all($status);
            }

            // Dodaj liczbę dialogów do każdego NPC
            foreach ($npcs as &$npc) {
                $npc->dialog_count = $this->dialog_repository->count_by_npc($npc->id);
                if ($npc->metadata) {
                    $npc->metadata = json_decode($npc->metadata, true);
                }
            }
            unset($npc); // Usuń referencję

            return new WP_REST_Response($npcs, 200);
        } catch (Exception $e) {
            return new WP_Error('npc_error', 'Błąd podczas pobierania NPC: ' . $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Pobiera pojedynczy NPC
     */
    public function get_npc($request)
    {
        try {
            $npc_id = intval($request->get_param('id'));
            $npc = $this->npc_repository->get_by_id($npc_id);

            if (!$npc) {
                return new WP_Error('npc_not_found', 'NPC nie został znaleziony', ['status' => 404]);
            }

            // Dodaj dialogi
            $npc->dialogs = $this->dialog_repository->get_by_npc_id($npc_id);

            // Dodaj odpowiedzi do każdego dialogu
            foreach ($npc->dialogs as &$dialog) {
                $dialog->answers = $this->answer_repository->get_by_dialog_id($dialog->id);

                // Decode JSON fields
                if ($dialog->conditions) {
                    $dialog->conditions = json_decode($dialog->conditions, true);
                }
                if ($dialog->actions) {
                    $dialog->actions = json_decode($dialog->actions, true);
                }

                foreach ($dialog->answers as &$answer) {
                    if ($answer->conditions) {
                        $answer->conditions = json_decode($answer->conditions, true);
                    }
                    if ($answer->actions) {
                        $answer->actions = json_decode($answer->actions, true);
                    }
                }
                unset($answer); // Usuń referencję na odpowiedzi
            }
            unset($dialog); // Usuń referencję na dialog

            if ($npc->metadata) {
                $npc->metadata = json_decode($npc->metadata, true);
            }

            return new WP_REST_Response($npc, 200);
        } catch (Exception $e) {
            return new WP_Error('npc_error', 'Błąd podczas pobierania NPC: ' . $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Pobiera dialog początkowy dla NPC
     */
    public function get_starting_dialog($request)
    {
        try {
            $npc_id = intval($request->get_param('npc_id'));
            $dialog = $this->dialog_repository->get_starting_dialog($npc_id);

            if (!$dialog) {
                return new WP_Error('dialog_not_found', 'Dialog początkowy nie został znaleziony', ['status' => 404]);
            }

            // Dodaj odpowiedzi
            $dialog->answers = $this->answer_repository->get_by_dialog_id($dialog->id);

            // Decode JSON fields
            if ($dialog->conditions) {
                $dialog->conditions = json_decode($dialog->conditions, true);
            }
            if ($dialog->actions) {
                $dialog->actions = json_decode($dialog->actions, true);
            }

            foreach ($dialog->answers as &$answer) {
                if ($answer->conditions) {
                    $answer->conditions = json_decode($answer->conditions, true);
                }
                if ($answer->actions) {
                    $answer->actions = json_decode($answer->actions, true);
                }
            }
            unset($answer); // Usuń referencję

            return new WP_REST_Response($dialog, 200);
        } catch (Exception $e) {
            return new WP_Error('dialog_error', 'Błąd podczas pobierania dialogu: ' . $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Pobiera dialogi dla NPC
     */
    public function get_dialogs($request)
    {
        try {
            $npc_id = intval($request->get_param('npc_id'));
            $dialogs = $this->dialog_repository->get_by_npc_id($npc_id);

            foreach ($dialogs as &$dialog) {
                $dialog->answers = $this->answer_repository->get_by_dialog_id($dialog->id);

                if ($dialog->conditions) {
                    $dialog->conditions = json_decode($dialog->conditions, true);
                }
                if ($dialog->actions) {
                    $dialog->actions = json_decode($dialog->actions, true);
                }

                foreach ($dialog->answers as &$answer) {
                    if ($answer->conditions) {
                        $answer->conditions = json_decode($answer->conditions, true);
                    }
                    if ($answer->actions) {
                        $answer->actions = json_decode($answer->actions, true);
                    }
                }
                unset($answer); // Usuń referencję na odpowiedzi
            }
            unset($dialog); // Usuń referencję na dialog

            return new WP_REST_Response($dialogs, 200);
        } catch (Exception $e) {
            return new WP_Error('dialog_error', 'Błąd podczas pobierania dialogów: ' . $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Pobiera pojedynczy dialog (AJAX)
     */
    public function ajax_get_dialog()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_admin_nonce')) {
            wp_die('Nieprawidłowy nonce');
        }

        $dialog_id = intval($_POST['dialog_id']);
        $dialog = $this->dialog_repository->get_by_id($dialog_id);

        if ($dialog) {
            if ($dialog->conditions) {
                $dialog->conditions = json_decode($dialog->conditions, true);
            }
            if ($dialog->actions) {
                $dialog->actions = json_decode($dialog->actions, true);
            }

            wp_send_json_success($dialog);
        } else {
            wp_send_json_error('Dialog nie został znaleziony');
        }
    }

    /**
     * Auto-save (AJAX)
     */
    public function ajax_auto_save()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_admin_nonce')) {
            wp_die('Nieprawidłowy nonce');
        }

        $npc_id = intval($_POST['npc_id']);

        if (!$npc_id) {
            wp_send_json_error('Nieprawidłowe ID NPC');
        }

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image_url' => esc_url_raw($_POST['image_url']),
            'location' => sanitize_text_field($_POST['location']),
            'status' => sanitize_text_field($_POST['status'])
        ];

        $result = $this->npc_repository->update($npc_id, $data);

        if ($result !== false) {
            wp_send_json_success('Auto-save completed');
        } else {
            wp_send_json_error('Auto-save failed');
        }
    }

    // Dodatkowe metody dla pozostałych endpoints...
    // (create_npc, update_npc, delete_npc, create_dialog, etc.)
    // Te metody będą implementowane w podobny sposób
}
