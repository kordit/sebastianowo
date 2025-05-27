<?php

/**
 * API do obsługi misji użytkownika
 */
class GameMissionAPI
{

    public function __construct()
    {
        // Hooki do automatycznego tworzenia misji
        add_action('save_post', [$this, 'auto_create_mission_for_users'], 10, 3);
        add_action('transition_post_status', [$this, 'handle_mission_publish'], 10, 3);
    }

    /**
     * Automatyczne tworzenie misji dla użytkowników po zapisaniu posta misji
     */
    public function auto_create_mission_for_users($post_id, $post, $update)
    {
        // Sprawdź czy to jest misja
        if ($post->post_type !== 'mission') {
            return;
        }

        // Sprawdź czy misja jest opublikowana
        if ($post->post_status !== 'publish') {
            return;
        }

        // Jeśli to nowa misja, utwórz ją dla wszystkich użytkowników
        if (!$update) {
            $this->create_mission_for_all_users($post_id);
        }
    }

    /**
     * Obsługuje publikację misji
     */
    public function handle_mission_publish($new_status, $old_status, $post)
    {
        // Sprawdź czy to jest misja
        if ($post->post_type !== 'mission') {
            return;
        }

        // Sprawdź czy misja została właśnie opublikowana
        if ($new_status === 'publish' && $old_status !== 'publish') {
            $this->create_mission_for_all_users($post->ID);
        }
    }

    /**
     * Tworzy misję dla wszystkich użytkowników
     */
    public function create_mission_for_all_users($mission_id)
    {
        // Pobierz wszystkich użytkowników
        $users = get_users(['fields' => 'ID']);

        foreach ($users as $user_id) {
            // Sprawdź czy użytkownik ma rekord w tabeli game_users
            $game_user = new GameUserModel($user_id);
            if (!$game_user->exists()) {
                continue; // Pomiń użytkowników bez danych w grze
            }

            // Utwórz misję dla użytkownika
            $mission_model = new GameMissionUserModel($user_id);
            $existing = $mission_model->get_mission($mission_id);

            if (!$existing) {
                $mission_model->create_mission($mission_id);
            }
        }
    }

    /**
     * Tworzy misję dla konkretnego użytkownika (ręcznie)
     */
    public static function create_mission_for_user($user_id, $mission_id)
    {
        require_once(get_template_directory() . '/includes/db-tables/MissionUserModel.php');

        $mission_model = new GameMissionUserModel($user_id);
        $result = $mission_model->create_mission($mission_id);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Misja została utworzona dla użytkownika',
                'mission_db_id' => $result
            ];
        }

        return [
            'success' => false,
            'message' => 'Nie udało się utworzyć misji'
        ];
    }

    /**
     * Inicjalizuje nową misję dla użytkownika
     * 
     * @param int $user_id ID użytkownika
     * @param int $mission_id ID misji
     * @return array Status operacji
     */
    public static function start_mission($user_id, $mission_id)
    {
        require_once(get_template_directory() . '/includes/db-tables/MissionUserModel.php');

        $mission_model = new GameMissionUserModel($user_id);
        $result = $mission_model->create_mission($mission_id);

        if ($result) {
            $mission_model->update_mission_status($mission_id, 'in_progress');
            return [
                'success' => true,
                'message' => 'Misja została rozpoczęta',
                'mission_id' => $result
            ];
        }

        return [
            'success' => false,
            'message' => 'Nie udało się rozpocząć misji'
        ];
    }

    /**
     * Aktualizuje status zadania w misji
     * 
     * @param int $user_id ID użytkownika
     * @param int $mission_id ID misji
     * @param string $task_id ID zadania
     * @param array $update_data Dane do aktualizacji
     * @return array Status operacji
     */
    public static function update_task($user_id, $mission_id, $task_id, $update_data)
    {
        require_once(get_template_directory() . '/includes/db-tables/MissionUserModel.php');

        $mission_model = new GameMissionUserModel($user_id);
        $result = $mission_model->update_task_data($mission_id, $task_id, $update_data);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Zadanie zostało zaktualizowane'
            ];
        }

        return [
            'success' => false,
            'message' => 'Nie udało się zaktualizować zadania'
        ];
    }

    /**
     * Aktualizuje status odwiedzenia lokacji w zadaniu
     * 
     * @param int $user_id ID użytkownika
     * @param int $mission_id ID misji
     * @param string $task_id ID zadania
     * @param int $location_id ID lokacji
     * @param string $scene_id ID sceny (opcjonalnie)
     * @return array Status operacji
     */
    public static function visit_location($user_id, $mission_id, $task_id, $location_id, $scene_id = '')
    {
        require_once(get_template_directory() . '/includes/db-tables/MissionUserModel.php');

        $mission_model = new GameMissionUserModel($user_id);
        $mission = $mission_model->get_mission($mission_id);

        if (!$mission) {
            return [
                'success' => false,
                'message' => 'Misja nie istnieje'
            ];
        }

        $tasks_data = json_decode($mission['tasks_data'], true);
        if (!is_array($tasks_data)) {
            return [
                'success' => false,
                'message' => 'Nieprawidłowe dane zadań'
            ];
        }

        // Znajdź zadanie
        $task_index = -1;
        foreach ($tasks_data as $index => $task) {
            if ($task['task_id'] === $task_id) {
                $task_index = $index;
                break;
            }
        }

        if ($task_index === -1) {
            return [
                'success' => false,
                'message' => 'Zadanie nie istnieje'
            ];
        }

        // Sprawdź czy to zadanie typu checkpoint
        if ($tasks_data[$task_index]['task_type'] !== 'checkpoint') {
            return [
                'success' => false,
                'message' => 'To zadanie nie jest typu checkpoint'
            ];
        }

        // Sprawdź czy lokacja się zgadza
        if ($tasks_data[$task_index]['location_id'] != $location_id) {
            return [
                'success' => false,
                'message' => 'Nieprawidłowa lokacja dla tego zadania'
            ];
        }

        // Sprawdź scenę jeśli wymagana
        if (!empty($tasks_data[$task_index]['scene']) && $tasks_data[$task_index]['scene'] !== $scene_id) {
            return [
                'success' => false,
                'message' => 'Nieprawidłowa scena dla tego zadania'
            ];
        }

        // Aktualizuj dane zadania
        $update_data = [
            'location_visited' => true,
            'current_scene' => $scene_id,
            'status' => 'completed'
        ];

        $result = $mission_model->update_task_data($mission_id, $task_id, $update_data);

        if ($result) {
            // Sprawdź czy wszystkie zadania są ukończone
            $all_completed = self::check_if_mission_completed($user_id, $mission_id);

            return [
                'success' => true,
                'message' => 'Lokacja została odwiedzona',
                'mission_completed' => $all_completed
            ];
        }

        return [
            'success' => false,
            'message' => 'Nie udało się zaktualizować lokacji'
        ];
    }

    /**
     * Aktualizuje status NPC w zadaniu
     * 
     * @param int $user_id ID użytkownika
     * @param int $mission_id ID misji
     * @param string $task_id ID zadania
     * @param int $npc_id ID NPC
     * @param string $status Nowy status
     * @return array Status operacji
     */
    public static function update_npc_status($user_id, $mission_id, $task_id, $npc_id, $status)
    {
        require_once(get_template_directory() . '/includes/db-tables/MissionUserModel.php');

        $mission_model = new GameMissionUserModel($user_id);
        $mission = $mission_model->get_mission($mission_id);

        if (!$mission) {
            return [
                'success' => false,
                'message' => 'Misja nie istnieje'
            ];
        }

        $tasks_data = json_decode($mission['tasks_data'], true);
        if (!is_array($tasks_data)) {
            return [
                'success' => false,
                'message' => 'Nieprawidłowe dane zadań'
            ];
        }

        // Znajdź zadanie
        $task_index = -1;
        foreach ($tasks_data as $index => $task) {
            if ($task['task_id'] === $task_id) {
                $task_index = $index;
                break;
            }
        }

        if ($task_index === -1) {
            return [
                'success' => false,
                'message' => 'Zadanie nie istnieje'
            ];
        }

        // Sprawdź czy to zadanie typu checkpoint_npc
        if ($tasks_data[$task_index]['task_type'] !== 'checkpoint_npc') {
            return [
                'success' => false,
                'message' => 'To zadanie nie jest typu checkpoint_npc'
            ];
        }

        // Znajdź NPC
        $npc_index = -1;
        foreach ($tasks_data[$task_index]['npcs'] as $index => $npc) {
            if ($npc['npc_id'] == $npc_id) {
                $npc_index = $index;
                break;
            }
        }

        if ($npc_index === -1) {
            return [
                'success' => false,
                'message' => 'NPC nie istnieje w tym zadaniu'
            ];
        }

        // Aktualizuj status NPC
        $tasks_data[$task_index]['npcs'][$npc_index]['current_status'] = $status;

        // Sprawdź czy wszystkie wymagane statusy są spełnione
        $all_completed = true;
        foreach ($tasks_data[$task_index]['npcs'] as $npc) {
            if ($npc['current_status'] !== $npc['required_status']) {
                $all_completed = false;
                break;
            }
        }

        // Jeśli wszystkie statusy się zgadzają, oznacz zadanie jako ukończone
        if ($all_completed) {
            $tasks_data[$task_index]['status'] = 'completed';
        } else {
            $tasks_data[$task_index]['status'] = 'in_progress';
        }

        // Zapisz zaktualizowane dane
        $result = $mission_model->update_task_data($mission_id, $task_id, $tasks_data[$task_index]);

        if ($result) {
            // Sprawdź czy wszystkie zadania są ukończone
            $mission_completed = self::check_if_mission_completed($user_id, $mission_id);

            return [
                'success' => true,
                'message' => 'Status NPC został zaktualizowany',
                'task_completed' => $all_completed,
                'mission_completed' => $mission_completed
            ];
        }

        return [
            'success' => false,
            'message' => 'Nie udało się zaktualizować statusu NPC'
        ];
    }

    /**
     * Aktualizuje status pokonania przeciwnika w zadaniu
     * 
     * @param int $user_id ID użytkownika
     * @param int $mission_id ID misji
     * @param string $task_id ID zadania
     * @param int $enemy_id ID przeciwnika
     * @param bool $defeated Czy pokonany
     * @return array Status operacji
     */
    public static function update_enemy_defeat($user_id, $mission_id, $task_id, $enemy_id, $defeated = true)
    {
        require_once(get_template_directory() . '/includes/db-tables/MissionUserModel.php');

        $mission_model = new GameMissionUserModel($user_id);
        $mission = $mission_model->get_mission($mission_id);

        if (!$mission) {
            return [
                'success' => false,
                'message' => 'Misja nie istnieje'
            ];
        }

        $tasks_data = json_decode($mission['tasks_data'], true);
        if (!is_array($tasks_data)) {
            return [
                'success' => false,
                'message' => 'Nieprawidłowe dane zadań'
            ];
        }

        // Znajdź zadanie
        $task_index = -1;
        foreach ($tasks_data as $index => $task) {
            if ($task['task_id'] === $task_id) {
                $task_index = $index;
                break;
            }
        }

        if ($task_index === -1) {
            return [
                'success' => false,
                'message' => 'Zadanie nie istnieje'
            ];
        }

        // Sprawdź czy to zadanie typu defeat_enemies
        if ($tasks_data[$task_index]['task_type'] !== 'defeat_enemies') {
            return [
                'success' => false,
                'message' => 'To zadanie nie jest typu defeat_enemies'
            ];
        }

        // Znajdź przeciwnika
        $enemy_index = -1;
        foreach ($tasks_data[$task_index]['enemies'] as $index => $enemy) {
            if ($enemy['enemy_id'] == $enemy_id) {
                $enemy_index = $index;
                break;
            }
        }

        if ($enemy_index === -1) {
            return [
                'success' => false,
                'message' => 'Przeciwnik nie istnieje w tym zadaniu'
            ];
        }

        // Aktualizuj status pokonania
        $tasks_data[$task_index]['enemies'][$enemy_index]['defeated'] = (bool) $defeated;

        // Sprawdź czy wszyscy przeciwnicy są pokonani
        $all_defeated = true;
        foreach ($tasks_data[$task_index]['enemies'] as $enemy) {
            if (!$enemy['defeated']) {
                $all_defeated = false;
                break;
            }
        }

        // Jeśli wszyscy pokonani, oznacz zadanie jako ukończone
        if ($all_defeated) {
            $tasks_data[$task_index]['status'] = 'completed';
        } else {
            $tasks_data[$task_index]['status'] = 'in_progress';
        }

        // Zapisz zaktualizowane dane
        $result = $mission_model->update_task_data($mission_id, $task_id, $tasks_data[$task_index]);

        if ($result) {
            // Zaktualizuj bilans walki
            if ($defeated) {
                $mission_model->update_battle_result($mission_id, 'win');
            } else {
                $mission_model->update_battle_result($mission_id, 'loss');
            }

            // Sprawdź czy wszystkie zadania są ukończone
            $mission_completed = self::check_if_mission_completed($user_id, $mission_id);

            return [
                'success' => true,
                'message' => 'Status przeciwnika został zaktualizowany',
                'task_completed' => $all_defeated,
                'mission_completed' => $mission_completed
            ];
        }

        return [
            'success' => false,
            'message' => 'Nie udało się zaktualizować statusu przeciwnika'
        ];
    }

    /**
     * Sprawdza czy wszystkie zadania w misji są zakończone
     * 
     * @param int $user_id ID użytkownika
     * @param int $mission_id ID misji
     * @return bool Czy wszystkie zakończone
     */
    private static function check_if_mission_completed($user_id, $mission_id)
    {
        $mission_model = new GameMissionUserModel($user_id);
        $mission = $mission_model->get_mission($mission_id);

        if (!$mission) {
            return false;
        }

        $tasks_data = json_decode($mission['tasks_data'], true);
        if (!is_array($tasks_data)) {
            return false;
        }

        $all_completed = true;
        foreach ($tasks_data as $task) {
            // Jeśli zadanie nie jest opcjonalne i nie jest zakończone
            if (empty($task['task_optional']) && $task['status'] !== 'completed') {
                $all_completed = false;
                break;
            }
        }

        // Jeśli wszystkie zadania są zakończone, zaktualizuj status misji
        if ($all_completed) {
            $mission_model->update_mission_status($mission_id, 'completed');
            return true;
        }

        return false;
    }

    /**
     * Pobiera dane wszystkich misji użytkownika
     * 
     * @param int $user_id ID użytkownika
     * @return array Dane misji
     */
    public static function get_user_missions($user_id)
    {
        require_once(get_template_directory() . '/includes/db-tables/MissionUserModel.php');

        $mission_model = new GameMissionUserModel($user_id);
        return $mission_model->get_all_missions();
    }

    /**
     * Pobiera dane konkretnej misji użytkownika
     * 
     * @param int $user_id ID użytkownika
     * @param int $mission_id ID misji
     * @return array Dane misji
     */
    public static function get_user_mission($user_id, $mission_id)
    {
        require_once(get_template_directory() . '/includes/db-tables/MissionUserModel.php');

        $mission_model = new GameMissionUserModel($user_id);
        return $mission_model->get_mission($mission_id);
    }
}
