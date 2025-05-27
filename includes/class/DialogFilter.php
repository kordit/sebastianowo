<?php

/**
 * Klasa do filtrowania i zarządzania dialogami NPC
 * 
 * Odpowiada za:
 * - Pobieranie dialogów z ACF
 * - Filtrowanie dialogów na podstawie warunków widoczności
 * - Znajdowanie odpowiednich dialogów dla użytkownika
 */
class DialogFilter
{
    private $user_id;
    private $page_data;

    public function __construct($user_id = null, $page_data = [])
    {
        $this->user_id = $user_id ?: get_current_user_id();
        $this->page_data = $page_data;
    }

    private function log_debug($message, $data = null)
    {
        $log_file = get_template_directory() . '/npc_debug.log';
        $timestamp = date('[Y-m-d H:i:s]');
        $log_entry = $timestamp . ' [DEBUG] ' . $message . "\n";

        if ($data !== null) {
            $log_entry .= $timestamp . ' [DATA] ' . print_r($data, true) . "\n";
        }

        error_log($log_entry, 3, $log_file);
    }

    /**
     * Pobiera wszystkie dialogi dla NPC
     * 
     * @param int $npc_id ID NPC
     * @return array|null Lista dialogów lub null jeśli brak
     */
    public function get_npc_dialogs($npc_id)
    {
        $dialogs = get_field('dialogs', $npc_id);

        if (!$dialogs || !is_array($dialogs)) {
            return null;
        }

        return $dialogs;
    }

    /**
     * Znajduje pierwszy dostępny dialog dla użytkownika
     * 
     * @param int $npc_id ID NPC
     * @return array|null Pierwszy dostępny dialog
     */
    public function get_first_available_dialog($npc_id)
    {
        $dialogs = $this->get_npc_dialogs($npc_id);

        if (!$dialogs) {
            return null;
        }

        foreach ($dialogs as $dialog) {
            if ($this->is_dialog_visible($dialog)) {
                return $this->filter_dialog_answers($dialog);
            }
        }

        return null;
    }

    /**
     * Znajduje dialog po ID z filtrowaniem widoczności
     * 
     * @param int $npc_id ID NPC
     * @param string $dialog_id ID dialogu
     * @return array|null Znaleziony dialog lub null
     */
    public function get_dialog_by_id($npc_id, $dialog_id)
    {
        $dialogs = $this->get_npc_dialogs($npc_id);

        if (!$dialogs) {
            return null;
        }

        foreach ($dialogs as $dialog) {
            if (isset($dialog['id_pola']) && $dialog['id_pola'] === $dialog_id) {
                if ($this->is_dialog_visible($dialog)) {
                    return $this->filter_dialog_answers($dialog);
                }
                return null; // Dialog istnieje ale nie jest widoczny
            }
        }

        return null; // Dialog nie istnieje
    }

    /**
     * Sprawdza czy dialog jest widoczny dla użytkownika
     * 
     * @param array $dialog Dane dialogu
     * @return bool True jeśli dialog jest widoczny
     */
    public function is_dialog_visible($dialog)
    {
        $dialog_id = $dialog['id_pola'] ?? 'unknown';



        // Sprawdź ustawienia widoczności
        if (!isset($dialog['layout_settings']['visibility_settings']) || !is_array($dialog['layout_settings']['visibility_settings'])) {
            return true; // Domyślnie widoczny jeśli brak ustawień
        }



        $visibility_settings = $dialog['layout_settings']['visibility_settings'] ?? [];
        $logic_operator = $dialog['layout_settings']['logic_operator'] ?? 'and';

        if (empty($visibility_settings)) {
            return true; // Brak warunków = widoczny
        }

        $results = [];

        foreach ($visibility_settings as $condition) {
            $results[] = $this->evaluate_visibility_condition($condition);
        }

        // Zastosuj operator logiczny
        if ($logic_operator === 'or') {
            return in_array(true, $results);
        } else {
            return !in_array(false, $results);
        }
    }

    /**
     * Filtruje odpowiedzi w dialogu na podstawie warunków widoczności
     * 
     * @param array $dialog Dane dialogu
     * @return array Przefiltrowany dialog
     */
    public function filter_dialog_answers($dialog)
    {
        if (!isset($dialog['anwsers']) || !is_array($dialog['anwsers'])) {
            return $dialog;
        }

        $filtered_answers = [];
        foreach ($dialog['anwsers'] as $answer) {
            if ($this->is_answer_visible($answer)) {
                $filtered_answers[] = $answer;
            }
        }

        $dialog['anwsers'] = $filtered_answers;
        return $dialog;
    }

    /**
     * Sprawdza czy odpowiedź jest widoczna dla użytkownika
     * 
     * @param array $answer Dane odpowiedzi
     * @return bool True jeśli odpowiedź jest widoczna
     */
    public function is_answer_visible($answer)
    {
        if (!isset($answer['layout_settings']) || !is_array($answer['layout_settings'])) {
            return true; // Domyślnie widoczna jeśli brak ustawień
        }

        $visibility_settings = $answer['layout_settings']['visibility_settings'] ?? [];
        $logic_operator = $answer['layout_settings']['logic_operator'] ?? 'and';

        if (empty($visibility_settings)) {
            return true; // Brak warunków = widoczna
        }

        $results = [];
        foreach ($visibility_settings as $condition) {
            $results[] = $this->evaluate_visibility_condition($condition);
        }

        if ($logic_operator === 'or') {
            return in_array(true, $results);
        } else {
            return !in_array(false, $results);
        }
    }

    /**
     * Ocenia pojedynczy warunek widoczności
     * 
     * @param array $condition Warunek widoczności
     * @return bool Wynik oceny warunku
     */
    private function evaluate_visibility_condition($condition)
    {
        $type = $condition['acf_fc_layout'] ?? '';

        // $this->log_debug('visibilty settings', $visibility_settings);


        switch ($type) {
            case 'condition_stat':
                return $this->check_stat_condition($condition);

            case 'condition_skill':
                return $this->check_skill_condition($condition);

            case 'condition_item':
            case 'condition_inventory':
                return $this->check_item_condition($condition);

            case 'condition_relation':
            case 'condition_npc_relation':
                return $this->check_relation_condition($condition);

            case 'condition_mission':
                return $this->check_mission_condition($condition);

            case 'condition_location':
                return $this->check_location_condition($condition);

            case 'condition_task':
                return $this->check_task_condition($condition);

            case 'user_class':
                return $this->check_user_class_condition($condition);

            default:
                return true; // Nieznane warunki są domyślnie spełnione
        }
    }

    /**
     * Sprawdza warunek statystyki
     */
    private function check_stat_condition($condition)
    {
        $stat_name = $condition['stat'] ?? '';
        $operator = $condition['operator'] ?? '>=';
        $value = intval($condition['value'] ?? 0);

        // Użyj nowej funkcji pomocniczej zamiast get_field
        $current_value = get_game_stat($stat_name, $this->user_id);

        return $this->compare_values($current_value, $operator, $value);
    }

    /**
     * Sprawdza warunek umiejętności
     */
    private function check_skill_condition($condition)
    {
        $skill_name = $condition['skill'] ?? '';
        $operator = $condition['operator'] ?? '>=';
        $value = intval($condition['value'] ?? 0);

        // Użyj nowej funkcji pomocniczej zamiast get_field
        $current_value = get_game_skill($skill_name, $this->user_id);

        return $this->compare_values($current_value, $operator, $value);
    }

    /**
     * Sprawdza warunek przedmiotu
     */
    private function check_item_condition($condition)
    {
        $item_id = intval($condition['item'] ?? $condition['item_id'] ?? 0);
        $condition_type = $condition['condition'] ?? '';
        $required_quantity = intval($condition['quantity'] ?? 1);

        $operator = '>=';
        if ($condition_type === 'has_item') {
            $operator = '>=';
        } elseif ($condition_type === 'has_not_item') {
            $operator = '<';
        } elseif ($condition_type === 'quantity_above') {
            $operator = '>';
        } elseif ($condition_type === 'quantity_below') {
            $operator = '<';
        } elseif ($condition_type === 'quantity_equal') {
            $operator = '==';
        } else {
            $operator = $condition['operator'] ?? '>=';
        }

        // Użyj nowej funkcji pomocniczej zamiast get_field
        $current_quantity = 0;
        $game_user = get_game_user($this->user_id);
        if ($game_user) {
            $items = $game_user->get_items_data();
            foreach ($items as $item) {
                if (intval($item['item_id']) === $item_id) {
                    $current_quantity = intval($item['quantity'] ?? 0);
                    break;
                }
            }
        }

        return $this->compare_values($current_quantity, $operator, $required_quantity);
    }

    /**
     * Sprawdza warunek relacji z NPC
     */
    private function check_relation_condition($condition)
    {
        $npc_id = intval($condition['npc'] ?? 0);
        $condition_type = $condition['condition'] ?? 'relation_above';
        $value = intval($condition['value'] ?? 0);

        if ($condition_type === 'is_known') {
            // Użyj nowej funkcji pomocniczej zamiast get_field
            return game_user_knows_npc($npc_id, $this->user_id);
        }

        if ($condition_type === 'is_not_known') {
            // Użyj nowej funkcji pomocniczej zamiast get_field
            return !game_user_knows_npc($npc_id, $this->user_id);
        }

        $operator = '>=';
        if ($condition_type === 'relation_above') {
            $operator = '>';
        } elseif ($condition_type === 'relation_below') {
            $operator = '<';
        } elseif ($condition_type === 'relation_equal') {
            $operator = '==';
        } else {
            $operator = $condition['operator'] ?? '>=';
        }

        // Użyj nowej funkcji pomocniczej zamiast get_field
        $current_value = 0;
        $relation = get_game_npc_relation($npc_id, $this->user_id);
        if ($relation) {
            $current_value = intval($relation['relation_value'] ?? 0);
        }

        return $this->compare_values($current_value, $operator, $value);
    }

    /**
     * Sprawdza warunek misji
     */
    private function check_mission_condition($condition)
    {
        $mission_id = intval($condition['mission'] ?? $condition['mission_id'] ?? 0);
        $status = $condition['status'] ?? 'completed';
        $condition_type = $condition['condition'] ?? 'is';

        // Użyj nowej funkcji pomocniczej zamiast get_field
        $game_user = get_game_user($this->user_id);
        if (!$game_user) {
            return $condition_type === 'is_not';
        }

        $missions = $game_user->get_missions_data();
        foreach ($missions as $mission) {
            if (intval($mission['mission_id']) === $mission_id) {
                if ($condition_type === 'is') {
                    return $mission['status'] === $status;
                } elseif ($condition_type === 'is_not') {
                    return $mission['status'] !== $status;
                }
                return $mission['status'] === $status;
            }
        }

        if ($condition_type === 'is_not') {
            return true;
        }

        return false;
    }

    /**
     * Sprawdza warunek klasy użytkownika
     */
    private function check_user_class_condition($condition)
    {
        $required_class = $condition['class'] ?? '';

        // Użyj nowej funkcji pomocniczej zamiast get_field
        $game_user = get_game_user($this->user_id);
        if (!$game_user) {
            return false;
        }

        $user_data = $game_user->get_basic_data();
        $user_class = $user_data['user_class'] ?? '';

        return $user_class === $required_class;
    }

    /**
     * Sprawdza warunek lokalizacji
     */
    private function check_location_condition($condition)
    {
        $condition_type = $condition['condition'] ?? 'is';
        $location_type = $condition['location_type'] ?? 'text';
        $location_value = '';

        if ($location_type === 'text') {
            $location_value = $condition['location_text'] ?? '';
            $current_location = $this->page_data['value'] ?? '';
        } else {
            $area_id = intval($condition['area'] ?? 0);
            $location_value = $area_id;

            // Użyj nowej funkcji pomocniczej zamiast get_field
            $current_location = game_user_has_area_access($area_id, $this->user_id);

            if ($condition_type === 'is') {
                return $current_location === true;
            } else {
                return $current_location === false;
            }
        }

        if ($condition_type === 'is') {
            return $current_location === $location_value;
        } else {
            return $current_location !== $location_value;
        }
    }

    /**
     * Sprawdza warunek zadania
     */
    private function check_task_condition($condition)
    {
        $mission_id = intval($condition['mission_id'] ?? 0);
        $task_id = $condition['task_id'] ?? '';
        $status = $condition['status'] ?? 'completed';
        $condition_type = $condition['condition'] ?? 'is';

        if (empty($mission_id) || empty($task_id)) {
            return false;
        }

        // Użyj nowej funkcji pomocniczej zamiast get_field
        $game_user = get_game_user($this->user_id);
        if (!$game_user) {
            return $condition_type === 'is_not';
        }

        $missions = $game_user->get_missions_data();
        $user_mission_data = null;

        foreach ($missions as $mission) {
            if (intval($mission['mission_id']) === $mission_id) {
                $user_mission_data = $mission;
                break;
            }
        }

        if (!$user_mission_data) {
            return $condition_type === 'is_not';
        }

        // Sprawdź zadania w misji (jeśli są przechowywane w formacie JSON lub innym)
        $tasks = json_decode($user_mission_data['tasks_data'] ?? '{}', true);
        if (!isset($tasks[$task_id])) {
            return $condition_type === 'is_not';
        }

        $task_data = $tasks[$task_id];
        $task_status = is_array($task_data) && isset($task_data['status']) ? $task_data['status'] : $task_data;

        if ($condition_type === 'is') {
            return $task_status === $status;
        }

        return $task_status !== $status;
    }

    /**
     * Porównuje wartości według operatora
     */
    private function compare_values($current, $operator, $required)
    {
        switch ($operator) {
            case '>=':
                return $current >= $required;
            case '>':
                return $current > $required;
            case '<=':
                return $current <= $required;
            case '<':
                return $current < $required;
            case '==':
                return $current == $required;
            case '!=':
                return $current != $required;
            default:
                return false;
        }
    }

    /**
     * Znajduje konkretny dialog w listie dialogów (bez filtrowania widoczności)
     * Używane przez execute_answer_actions
     * 
     * @param int $npc_id ID NPC
     * @param string $dialog_id ID dialogu
     * @return array|null Znaleziony dialog
     */
    public function find_dialog_by_id_raw($npc_id, $dialog_id)
    {
        $dialogs = $this->get_npc_dialogs($npc_id);

        if (!$dialogs) {
            return null;
        }

        foreach ($dialogs as $dialog) {
            if (isset($dialog['id_pola']) && $dialog['id_pola'] === $dialog_id) {
                return $dialog;
            }
        }

        return null;
    }

    /**
     * Ustawia user_id (przydatne dla testów lub zmian kontekstu)
     */
    public function set_user_id($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * Ustawia page_data (przydatne dla warunków lokalizacji)
     */
    public function set_page_data($page_data)
    {
        $this->page_data = $page_data;
    }
}
