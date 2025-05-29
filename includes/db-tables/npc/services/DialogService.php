<?php

/**
 * NPC Dialog Service
 * Serwis obsługujący logikę dialogów i warunków
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

            // Sprawdź warunki dialogu
            if (!$this->check_dialog_conditions($dialog, $user_id)) {
                return false;
            }

            // Pobierz dostępne odpowiedzi
            $answers = $this->get_available_answers($dialog->id, $user_id);

            // Wykonaj akcje dialogu
            $this->execute_dialog_actions($dialog, $user_id);

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

            // Sprawdź warunki odpowiedzi
            if (!$this->check_answer_conditions($answer, $user_id)) {
                return false;
            }

            // Wykonaj akcje odpowiedzi
            $this->execute_answer_actions($answer, $user_id);

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

            // Sprawdź warunki następnego dialogu
            if (!$this->check_dialog_conditions($next_dialog, $user_id)) {
                return [
                    'dialog_ended' => true,
                    'answer' => $answer
                ];
            }

            // Pobierz dostępne odpowiedzi dla następnego dialogu
            $next_answers = $this->get_available_answers($next_dialog->id, $user_id);

            // Wykonaj akcje następnego dialogu
            $this->execute_dialog_actions($next_dialog, $user_id);

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
     * Sprawdza warunki dialogu
     */
    private function check_dialog_conditions($dialog, $user_id = null)
    {
        if (!$dialog->conditions) {
            return true;
        }

        $conditions = json_decode($dialog->conditions, true);
        if (!$conditions) {
            return true;
        }

        return $this->evaluate_conditions($conditions, $user_id);
    }

    /**
     * Sprawdza warunki odpowiedzi
     */
    private function check_answer_conditions($answer, $user_id = null)
    {
        if (!$answer->conditions) {
            return true;
        }

        $conditions = json_decode($answer->conditions, true);
        if (!$conditions) {
            return true;
        }

        return $this->evaluate_conditions($conditions, $user_id);
    }

    /**
     * Pobiera dostępne odpowiedzi dla dialogu
     */
    private function get_available_answers($dialog_id, $user_id = null)
    {
        $all_answers = $this->answer_repository->get_by_dialog_id($dialog_id);
        $available_answers = [];

        foreach ($all_answers as $answer) {
            if ($this->check_answer_conditions($answer, $user_id)) {
                $available_answers[] = $answer;
            }
        }

        return $available_answers;
    }

    /**
     * Ocenia warunki (conditions)
     */
    private function evaluate_conditions($conditions, $user_id = null)
    {
        if (!is_array($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (!$this->evaluate_single_condition($condition, $user_id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ocenia pojedynczy warunek
     */
    private function evaluate_single_condition($condition, $user_id = null)
    {
        $type = $condition['type'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? '';
        $field = $condition['field'] ?? '';

        switch ($type) {
            case 'user_level':
                $user_level = $this->get_user_level($user_id);
                return $this->compare_values($user_level, $operator, intval($value));

            case 'user_gold':
                $user_gold = $this->get_user_gold($user_id);
                return $this->compare_values($user_gold, $operator, intval($value));

            case 'user_item':
                $has_item = $this->user_has_item($user_id, $value);
                return $operator === 'has' ? $has_item : !$has_item;

            case 'quest_completed':
                $quest_completed = $this->is_quest_completed($user_id, $value);
                return $operator === 'completed' ? $quest_completed : !$quest_completed;

            case 'user_stat':
                $stat_value = $this->get_user_stat($user_id, $field);
                return $this->compare_values($stat_value, $operator, intval($value));

            case 'time_of_day':
                $current_hour = intval(date('H'));
                return $this->compare_values($current_hour, $operator, intval($value));

            case 'custom':
                return apply_filters('npc_dialog_custom_condition', true, $condition, $user_id);

            default:
                return true;
        }
    }

    /**
     * Porównuje wartości według operatora
     */
    private function compare_values($actual, $operator, $expected)
    {
        switch ($operator) {
            case '==':
                return $actual == $expected;
            case '!=':
                return $actual != $expected;
            case '>':
                return $actual > $expected;
            case '>=':
                return $actual >= $expected;
            case '<':
                return $actual < $expected;
            case '<=':
                return $actual <= $expected;
            default:
                return true;
        }
    }

    /**
     * Wykonuje akcje dialogu
     */
    private function execute_dialog_actions($dialog, $user_id = null)
    {
        if (!$dialog->actions) {
            return;
        }

        $actions = json_decode($dialog->actions, true);
        if (!$actions) {
            return;
        }

        foreach ($actions as $action) {
            $this->execute_single_action($action, $user_id);
        }
    }

    /**
     * Wykonuje akcje odpowiedzi
     */
    private function execute_answer_actions($answer, $user_id = null)
    {
        if (!$answer->actions) {
            return;
        }

        $actions = json_decode($answer->actions, true);
        if (!$actions) {
            return;
        }

        foreach ($actions as $action) {
            $this->execute_single_action($action, $user_id);
        }
    }

    /**
     * Wykonuje pojedynczą akcję
     */
    private function execute_single_action($action, $user_id = null)
    {
        $type = $action['type'] ?? '';
        $value = $action['value'] ?? '';
        $amount = intval($action['amount'] ?? 0);

        switch ($type) {
            case 'give_gold':
                $this->give_user_gold($user_id, $amount);
                break;

            case 'take_gold':
                $this->take_user_gold($user_id, $amount);
                break;

            case 'give_item':
                $this->give_user_item($user_id, $value, $amount);
                break;

            case 'take_item':
                $this->take_user_item($user_id, $value, $amount);
                break;

            case 'give_exp':
                $this->give_user_exp($user_id, $amount);
                break;

            case 'complete_quest':
                $this->complete_quest($user_id, $value);
                break;

            case 'start_quest':
                $this->start_quest($user_id, $value);
                break;

            case 'teleport':
                $this->teleport_user($user_id, $value);
                break;

            case 'custom':
                do_action('npc_dialog_custom_action', $action, $user_id);
                break;
        }
    }

    // Metody pomocnicze dla interakcji z systemem gry
    // Te metody będą integrować się z istniejącym systemem gry

    private function get_user_level($user_id)
    {
        // Integracja z systemem poziomów
        return get_user_meta($user_id, 'user_level', true) ?: 1;
    }

    private function get_user_gold($user_id)
    {
        // Integracja z systemem waluty
        return get_user_meta($user_id, 'user_gold', true) ?: 0;
    }

    private function user_has_item($user_id, $item_id)
    {
        // Integracja z systemem przedmiotów
        $backpack = get_user_meta($user_id, 'user_backpack', true) ?: [];
        return isset($backpack[$item_id]) && $backpack[$item_id] > 0;
    }

    private function is_quest_completed($user_id, $quest_id)
    {
        // Integracja z systemem zadań
        $completed_quests = get_user_meta($user_id, 'completed_quests', true) ?: [];
        return in_array($quest_id, $completed_quests);
    }

    private function get_user_stat($user_id, $stat_name)
    {
        // Integracja z systemem statystyk
        return get_user_meta($user_id, 'user_stat_' . $stat_name, true) ?: 0;
    }

    private function give_user_gold($user_id, $amount)
    {
        $current_gold = $this->get_user_gold($user_id);
        update_user_meta($user_id, 'user_gold', $current_gold + $amount);
    }

    private function take_user_gold($user_id, $amount)
    {
        $current_gold = $this->get_user_gold($user_id);
        update_user_meta($user_id, 'user_gold', max(0, $current_gold - $amount));
    }

    private function give_user_item($user_id, $item_id, $amount)
    {
        $backpack = get_user_meta($user_id, 'user_backpack', true) ?: [];
        $backpack[$item_id] = ($backpack[$item_id] ?? 0) + $amount;
        update_user_meta($user_id, 'user_backpack', $backpack);
    }

    private function take_user_item($user_id, $item_id, $amount)
    {
        $backpack = get_user_meta($user_id, 'user_backpack', true) ?: [];
        if (isset($backpack[$item_id])) {
            $backpack[$item_id] = max(0, $backpack[$item_id] - $amount);
            if ($backpack[$item_id] === 0) {
                unset($backpack[$item_id]);
            }
        }
        update_user_meta($user_id, 'user_backpack', $backpack);
    }

    private function give_user_exp($user_id, $amount)
    {
        $current_exp = get_user_meta($user_id, 'user_exp', true) ?: 0;
        update_user_meta($user_id, 'user_exp', $current_exp + $amount);
    }

    private function complete_quest($user_id, $quest_id)
    {
        $completed_quests = get_user_meta($user_id, 'completed_quests', true) ?: [];
        if (!in_array($quest_id, $completed_quests)) {
            $completed_quests[] = $quest_id;
            update_user_meta($user_id, 'completed_quests', $completed_quests);
        }
    }

    private function start_quest($user_id, $quest_id)
    {
        $active_quests = get_user_meta($user_id, 'active_quests', true) ?: [];
        if (!in_array($quest_id, $active_quests)) {
            $active_quests[] = $quest_id;
            update_user_meta($user_id, 'active_quests', $active_quests);
        }
    }

    private function teleport_user($user_id, $location)
    {
        update_user_meta($user_id, 'user_location', $location);
    }
}
