<?php

/**
 * Builder dla misji
 * Odpowiedzialny za dodawanie rekordów do tabeli misji
 */
class MissionBuilder
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Przygotowuje zadania do zapisania
     */
    private function prepareTasks($tasks)
    {
        $prepared_tasks = [];

        foreach ($tasks as $task) {
            $prepared_task = [
                'task_id' => sanitize_text_field($task['task_id']),
                'task_title' => sanitize_text_field($task['task_title']),
                'task_description' => sanitize_textarea_field($task['task_description']),
                'task_optional' => !empty($task['task_optional']),
                'task_attempt_limit' => intval($task['task_attempt_limit'] ?? 0),
                'task_type' => sanitize_text_field($task['task_type']),
                'task_location' => intval($task['task_location'] ?? 0),
                'task_location_scene' => sanitize_text_field($task['task_location_scene'] ?? ''),
                'task_checkpoint_npc' => null,
                'task_defeat_enemies' => null
            ];

            // Przygotuj dane specyficzne dla typu
            switch ($task['task_type']) {
                case 'checkpoint_npc':
                    if (!empty($task['task_checkpoint_npc'])) {
                        $prepared_task['task_checkpoint_npc'] = $task['task_checkpoint_npc'];
                    }
                    break;

                case 'defeat_enemies':
                    if (!empty($task['task_defeat_enemies'])) {
                        $prepared_task['task_defeat_enemies'] = array_map('intval', $task['task_defeat_enemies']);
                    }
                    break;
            }

            $prepared_tasks[] = $prepared_task;
        }

        return $prepared_tasks;
    }

    /**
     * Pobiera misję z WordPress (post_type='mission')
     */
    public function getMissionFromWordPress($mission_id)
    {
        $post = get_post($mission_id);

        if (!$post || $post->post_type !== 'mission') {
            return [
                'success' => false,
                'message' => 'Misja nie istnieje lub ma nieprawidłowy typ'
            ];
        }

        // Pobierz custom fields
        $mission_description = get_post_meta($mission_id, 'mission_description', true);

        // Jeśli mission_description nie istnieje, użyj zawartości postu jako opisu misji
        if (empty($mission_description)) {
            $mission_description = $post->post_content;
        }

        $mission_time_limit = get_post_meta($mission_id, 'mission_time_limit', true);
        $mission_type = get_post_meta($mission_id, 'mission_type', true);
        $tasks_count = intval(get_post_meta($mission_id, 'mission_tasks', true));

        if ($tasks_count === 0) {
            return [
                'success' => false,
                'message' => 'Misja nie ma zdefiniowanych zadań (mission_tasks)'
            ];
        }

        // Pobierz zadania z ACF (są zapisane jako oddzielne meta fields)
        $mission_tasks = [];
        for ($i = 0; $i < $tasks_count; $i++) {
            $task = [
                'task_id' => get_post_meta($mission_id, "mission_tasks_{$i}_task_id", true),
                'task_title' => get_post_meta($mission_id, "mission_tasks_{$i}_task_title", true),
                'task_description' => get_post_meta($mission_id, "mission_tasks_{$i}_task_description", true),
                'task_optional' => get_post_meta($mission_id, "mission_tasks_{$i}_task_optional", true),
                'task_attempt_limit' => intval(get_post_meta($mission_id, "mission_tasks_{$i}_task_attempt_limit", true)),
                'task_type' => get_post_meta($mission_id, "mission_tasks_{$i}_task_type", true),
                'task_location' => intval(get_post_meta($mission_id, "mission_tasks_{$i}_task_location", true)),
                'task_location_scene' => get_post_meta($mission_id, "mission_tasks_{$i}_task_location_scene", true),
                'task_checkpoint_npc' => [],
                'task_defeat_enemies' => []
            ];

            // Pobierz dane specyficzne dla typu zadania
            if ($task['task_type'] === 'checkpoint_npc') {
                $npc_count = intval(get_post_meta($mission_id, "mission_tasks_{$i}_task_checkpoint_npc", true));
                for ($j = 0; $j < $npc_count; $j++) {
                    $npc_data = [
                        'npc' => intval(get_post_meta($mission_id, "mission_tasks_{$i}_task_checkpoint_npc_{$j}_npc", true)),
                        'status' => get_post_meta($mission_id, "mission_tasks_{$i}_task_checkpoint_npc_{$j}_status", true)
                    ];
                    if ($npc_data['npc']) {
                        $task['task_checkpoint_npc'][] = $npc_data;
                    }
                }
            }

            if ($task['task_type'] === 'defeat_enemies') {
                $enemies_data = get_post_meta($mission_id, "mission_tasks_{$i}_task_defeat_enemies", true);
                if ($enemies_data) {
                    // Może być serializowane
                    $enemies = maybe_unserialize($enemies_data);
                    if (is_array($enemies)) {
                        $task['task_defeat_enemies'] = array_map('intval', $enemies);
                    }
                }
            }

            $mission_tasks[] = $task;
        }

        $mission_data = [
            'mission_id' => $mission_id,
            'mission_title' => $post->post_title,
            'mission_description' => $mission_description,
            'mission_time_limit' => intval($mission_time_limit),
            'mission_type' => $mission_type ?: 'one-time',
            'mission_tasks' => $mission_tasks
        ];

        return [
            'success' => true,
            'mission' => $mission_data
        ];
    }

    /**
     * Przypisuje misję z WordPress do użytkownika
     */
    public function assignMissionToUser($user_id, $mission_id)
    {
        // Sprawdź, czy user_id jest poprawny
        if (!$user_id || !is_numeric($user_id) || $user_id <= 0) {
            return [
                'success' => false,
                'message' => 'Nieprawidłowy ID użytkownika: ' . var_export($user_id, true)
            ];
        }

        // Pobierz misję z WordPress
        $mission_result = $this->getMissionFromWordPress($mission_id);
        if (!$mission_result['success']) {
            return $mission_result;
        }

        $mission = $mission_result['mission'];
        $mission_data = [
            'mission_description' => $mission['mission_description'],
            'mission_time_limit' => $mission['mission_time_limit'],
            'mission_type' => $mission['mission_type'],
            'mission_tasks' => $this->prepareTasks($mission['mission_tasks']),
        ];

        // Sprawdź czy użytkownik już ma tę misję
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM $mission_tasks_table WHERE user_id = %d AND mission_id = %d LIMIT 1",
            $user_id,
            $mission_id
        ));

        if ($existing) {
            return [
                'success' => false,
                'message' => 'Użytkownik już ma przypisaną tę misję'
            ];
        }

        // Oblicz expires_at jeśli jest limit czasowy
        $expires_at = null;
        if ($mission_data['mission_time_limit'] > 0) {
            $expires_at = date('Y-m-d H:i:s', strtotime('+' . $mission_data['mission_time_limit'] . ' hours'));
        }

        // Wstaw wszystkie zadania misji do jednej tabeli
        $tasks_added = 0;
        foreach ($mission_data['mission_tasks'] as $task) {
            $task_result = $this->wpdb->insert(
                $mission_tasks_table,
                [
                    'user_id' => $user_id,
                    'mission_id' => $mission_id,
                    'mission_title' => $mission['mission_title'],
                    'mission_description' => $mission_data['mission_description'],
                    'mission_time_limit' => $mission_data['mission_time_limit'],
                    'mission_type' => $mission_data['mission_type'],
                    'mission_status' => 'not_started',
                    'task_id' => $task['task_id'],
                    'task_title' => $task['task_title'],
                    'task_description' => $task['task_description'],
                    'task_optional' => $task['task_optional'] ? 1 : 0,
                    'task_attempt_limit' => $task['task_attempt_limit'],
                    'task_type' => $task['task_type'],
                    'task_location' => $task['task_location'],
                    'task_location_scene' => $task['task_location_scene'],
                    'task_checkpoint_npc' => $task['task_checkpoint_npc'] ? json_encode($task['task_checkpoint_npc']) : null,
                    'task_defeat_enemies' => $task['task_defeat_enemies'] ? json_encode($task['task_defeat_enemies']) : null,
                    'task_status' => 'not_started',
                    'mission_started_at' => date('Y-m-d H:i:s'),
                    'mission_expires_at' => $expires_at
                ]
            );

            if ($task_result !== false) {
                $tasks_added++;
            }
        }

        if ($tasks_added === 0) {
            return [
                'success' => false,
                'message' => 'Nie udało się dodać żadnego zadania'
            ];
        }

        return [
            'success' => true,
            'message' => "Przypisano misję '{$mission['mission_title']}' do użytkownika",
            'tasks_added' => $tasks_added,
            'mission_data' => [
                'title' => $mission['mission_title'],
                'description' => $mission_data['mission_description'],
                'time_limit' => $mission_data['mission_time_limit'],
                'type' => $mission_data['mission_type'],
                'expires_at' => $expires_at
            ]
        ];
    }

    /**
     * Buduje wszystkie misje - przypisuje misje z WordPress do wszystkich użytkowników
     */
    public function buildAllMissions()
    {
        // Pobierz wszystkie misje z WordPress
        $wp_missions = get_posts([
            'post_type' => 'mission',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);

        if (empty($wp_missions)) {
            return [
                'success' => false,
                'message' => 'Nie znaleziono misji w WordPress (post_type="mission")'
            ];
        }

        // Pobierz wszystkich użytkowników gry (bez limitu)
        $user_repo = new GameUserRepository();
        $users = $user_repo->getAll(999999, 0);

        if (empty($users)) {
            return [
                'success' => false,
                'message' => 'Nie znaleziono użytkowników w grze'
            ];
        }

        $assigned_missions = 0;
        $total_tasks = 0;
        $errors = [];

        foreach ($wp_missions as $wp_mission) {
            // Pobierz misję z WordPress
            $mission_result = $this->getMissionFromWordPress($wp_mission->ID);
            if (!$mission_result['success']) {
                $errors[] = "Misja '{$wp_mission->post_title}' (ID: {$wp_mission->ID}): " . $mission_result['message'];
                continue;
            }

            $mission_data = $mission_result['mission'];

            // Przypisz misję do wszystkich użytkowników
            foreach ($users as $user) {
                // Sprawdź strukturę danych użytkownika i pobierz ID
                $user_id = isset($user['id']) ? $user['id'] : (isset($user['ID']) ? $user['ID'] : (isset($user['user_id']) ? $user['user_id'] : null));

                if ($user_id === null) {
                    $errors[] = "Błąd struktury danych - brak ID dla użytkownika " . (isset($user['nick']) ? $user['nick'] : (isset($user['user_login']) ? $user['user_login'] : 'nieznany'));
                    continue;
                }

                $result = $this->assignMissionToUser($user_id, $wp_mission->ID);
                if ($result['success']) {
                    $assigned_missions++;
                    // Każda misja może mieć wiele zadań
                    if (isset($mission_data['mission_tasks']) && is_array($mission_data['mission_tasks'])) {
                        $total_tasks += count($mission_data['mission_tasks']);
                    }
                } else {
                    $errors[] = "Nie udało się przypisać misji '{$wp_mission->post_title}' do użytkownika " .
                        (isset($user['nick']) ? $user['nick'] : (isset($user['user_login']) ? $user['user_login'] : 'nieznany')) .
                        ": " . $result['message'];
                }
            }
        }

        // Pobierz rzeczywistą liczbę zadań z bazy danych
        $tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';
        $actual_tasks = 0;
        if ($this->wpdb->get_var("SHOW TABLES LIKE '$tasks_table'") === $tasks_table) {
            $actual_tasks = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM $tasks_table");
        }

        $message = "Zbudowano misje! Przypisano {$assigned_missions} misji z {$actual_tasks} zadaniami.";

        if (!empty($errors)) {
            $message .= " Błędy: " . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= " i " . (count($errors) - 3) . " więcej.";
            }
        }

        return [
            'success' => true,
            'message' => $message,
            'assigned_missions' => $assigned_missions,
            'total_tasks' => $total_tasks,
            'errors' => $errors
        ];
    }

    /**
     * Czyści wszystkie misje użytkowników
     */
    public function clearAllMissions()
    {
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        // Usuń wszystkie rekordy z tabeli misji i zadań
        $deleted_records = $this->wpdb->query("DELETE FROM $mission_tasks_table");

        if ($deleted_records === false) {
            return [
                'success' => false,
                'message' => 'Wystąpił błąd podczas czyszczenia misji: ' . $this->wpdb->last_error
            ];
        }

        return [
            'success' => true,
            'message' => "Wyczyszczono wszystkie misje! Usunięto {$deleted_records} rekordów misji i zadań.",
            'deleted_records' => $deleted_records
        ];
    }
}
