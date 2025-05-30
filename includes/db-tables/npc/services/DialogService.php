<?php

/**
 * NPC Dialog Service
 * Serwis obsługujący logikę dialogów
 */

if (!defined('ABSPATH')) {
    exit;
}

class NPC_DialogService
{
    private $npc_repository;
    private $dialog_repository;
    private $answer_repository;

    public function __construct()
    {
        $this->npc_repository = new NPC_NPCRepository();
        $this->dialog_repository = new NPC_DialogRepository();
        $this->answer_repository = new NPC_AnswerRepository();

        add_action('wp_ajax_npc_start_dialog', [$this, 'ajax_start_dialog']);
        add_action('wp_ajax_npc_continue_dialog', [$this, 'ajax_continue_dialog']);
        add_action('wp_ajax_nopriv_npc_start_dialog', [$this, 'ajax_start_dialog']);
        add_action('wp_ajax_nopriv_npc_continue_dialog', [$this, 'ajax_continue_dialog']);
    }

    /**
     * Rozpoczyna dialog z NPC (AJAX)
     */
    public function ajax_start_dialog()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_dialog_nonce')) {
            wp_send_json_error('Nieprawidłowy nonce');
        }

        $npc_id = intval($_POST['npc_id']);
        $user_id = get_current_user_id();

        $dialog_data = $this->start_dialog($npc_id, $user_id);

        if ($dialog_data) {
            wp_send_json_success($dialog_data);
        } else {
            wp_send_json_error('Nie można rozpocząć dialogu');
        }
    }

    /**
     * Kontynuuje dialog (AJAX)
     */
    public function ajax_continue_dialog()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'npc_dialog_nonce')) {
            wp_send_json_error('Nieprawidłowy nonce');
        }

        $answer_id = intval($_POST['answer_id']);
        $user_id = get_current_user_id();

        $dialog_data = $this->continue_dialog($answer_id, $user_id);

        if ($dialog_data) {
            wp_send_json_success($dialog_data);
        } else {
            wp_send_json_error('Nie można kontynuować dialogu');
        }
    }

    /**
     * Rozpoczyna dialog z NPC
     */
    public function start_dialog($npc_id, $user_id = null)
    {
        try {
            // Sprawdź czy NPC istnieje
            $npc = $this->npc_repository->get_by_id($npc_id);
            if (!$npc || $npc->status !== 'active') {
                return false;
            }

            // Pobierz dialog początkowy
            $dialog = $this->dialog_repository->get_starting_dialog($npc_id);
            if (!$dialog) {
                return false;
            }

            // Pobierz dostępne odpowiedzi
            $answers = $this->get_answers($dialog->id);

            return [
                'npc' => $npc,
                'dialog' => $dialog,
                'answers' => $answers
            ];
        } catch (Exception $e) {
            error_log('NPC Dialog Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kontynuuje dialog na podstawie wybranej odpowiedzi
     */
    public function continue_dialog($answer_id, $user_id = null)
    {
        try {
            // Pobierz odpowiedź
            $answer = $this->answer_repository->get_by_id($answer_id);
            if (!$answer || $answer->status !== 'active') {
                return false;
            }

            // Sprawdź czy jest następny dialog
            if (!$answer->next_dialog_id) {
                return [
                    'dialog_ended' => true,
                    'answer' => $answer
                ];
            }

            // Pobierz następny dialog
            $next_dialog = $this->dialog_repository->get_by_id($answer->next_dialog_id);
            if (!$next_dialog || $next_dialog->status !== 'active') {
                return [
                    'dialog_ended' => true,
                    'answer' => $answer
                ];
            }

            // Pobierz dostępne odpowiedzi dla następnego dialogu
            $next_answers = $this->get_answers($next_dialog->id);

            return [
                'dialog' => $next_dialog,
                'answers' => $next_answers,
                'previous_answer' => $answer
            ];
        } catch (Exception $e) {
            error_log('NPC Dialog Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobiera wszystkie odpowiedzi dla dialogu
     */
    private function get_answers($dialog_id)
    {
        return $this->answer_repository->get_by_dialog_id($dialog_id);
    }
}
