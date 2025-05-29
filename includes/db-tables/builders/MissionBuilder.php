<?php

/**
 * Builder dla misji
 * Odpowiedzialny za tworzenie i zarządzanie misjami jako "przepisami"
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
     * Tworzy lub aktualizuje misję jako przepis
     */
    public function createMissionRecipe($mission_data)
    {
        $required_fields = ['mission_description', 'mission_tasks'];

        foreach ($required_fields as $field) {
            if (empty($mission_data[$field])) {
                return [
                    'success' => false,
                    'message' => "Brakuje pola: $field"
                ];
            }
        }

        // Walidacja zadań
        $tasks_validation = $this->validateTasks($mission_data['mission_tasks']);
        if (!$tasks_validation['valid']) {
            return [
                'success' => false,
                'message' => $tasks_validation['message']
            ];
        }

        // Przygotuj dane misji
        $mission_recipe = [
            'mission_description' => sanitize_textarea_field($mission_data['mission_description']),
            'mission_time_limit' => intval($mission_data['mission_time_limit'] ?? 0),
            'mission_type' => sanitize_text_field($mission_data['mission_type'] ?? 'one-time'),
            'mission_tasks' => $this->prepareTasks($mission_data['mission_tasks'])
        ];

        return [
            'success' => true,
            'recipe' => $mission_recipe,
            'tasks_count' => count($mission_recipe['mission_tasks'])
        ];
    }

    /**
     * Waliduje zadania misji
     */
    private function validateTasks($tasks)
    {
        if (empty($tasks) || !is_array($tasks)) {
            return [
                'valid' => false,
                'message' => 'Misja musi mieć przynajmniej jedno zadanie'
            ];
        }

        $valid_task_types = ['checkpoint', 'checkpoint_npc', 'defeat_enemies'];

        foreach ($tasks as $index => $task) {
            // Sprawdź wymagane pola
            $required = ['task_id', 'task_title', 'task_description', 'task_type'];
            foreach ($required as $field) {
                if (empty($task[$field])) {
                    return [
                        'valid' => false,
                        'message' => "Zadanie #" . ($index + 1) . " - brakuje pola: $field"
                    ];
                }
            }

            // Sprawdź typ zadania
            if (!in_array($task['task_type'], $valid_task_types)) {
                return [
                    'valid' => false,
                    'message' => "Zadanie #{$task['task_id']} - nieprawidłowy typ: {$task['task_type']}"
                ];
            }

            // Walidacja specyficzna dla typu
            $type_validation = $this->validateTaskByType($task);
            if (!$type_validation['valid']) {
                return $type_validation;
            }
        }

        return ['valid' => true];
    }

    /**
     * Waliduje zadanie na podstawie typu
     */
    private function validateTaskByType($task)
    {
        switch ($task['task_type']) {
            case 'checkpoint':
                if (empty($task['task_location']) || empty($task['task_location_scene'])) {
                    return [
                        'valid' => false,
                        'message' => "Zadanie typu 'checkpoint' wymaga task_location i task_location_scene"
                    ];
                }
                break;

            case 'checkpoint_npc':
                if (empty($task['task_checkpoint_npc']) || !is_array($task['task_checkpoint_npc'])) {
                    return [
                        'valid' => false,
                        'message' => "Zadanie typu 'checkpoint_npc' wymaga tablicy task_checkpoint_npc"
                    ];
                }

                foreach ($task['task_checkpoint_npc'] as $npc_data) {
                    if (empty($npc_data['npc']) || empty($npc_data['status'])) {
                        return [
                            'valid' => false,
                            'message' => "Każdy NPC w checkpoint_npc musi mieć 'npc' i 'status'"
                        ];
                    }
                }
                break;

            case 'defeat_enemies':
                if (empty($task['task_defeat_enemies']) || !is_array($task['task_defeat_enemies'])) {
                    return [
                        'valid' => false,
                        'message' => "Zadanie typu 'defeat_enemies' wymaga tablicy task_defeat_enemies"
                    ];
                }
                break;
        }

        return ['valid' => true];
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
     * Zwraca dostępne typy zadań z opisami
     */
    public function getTaskTypes()
    {
        return [
            'checkpoint' => [
                'label' => 'Punkt kontrolny',
                'description' => 'Sprawdza czy gracz dotarł do określonej lokacji/sceny',
                'fields' => ['task_location', 'task_location_scene']
            ],
            'checkpoint_npc' => [
                'label' => 'Interakcja z NPC',
                'description' => 'Sprawdza interakcję z określonymi NPC',
                'fields' => ['task_checkpoint_npc']
            ],
            'defeat_enemies' => [
                'label' => 'Pokonaj wrogów',
                'description' => 'Wymaga pokonania określonych przeciwników',
                'fields' => ['task_defeat_enemies']
            ]
        ];
    }

    /**
     * Zwraca dostępne typy misji
     */
    public function getMissionTypes()
    {
        return [
            'one-time' => 'Jednorazowa',
            'repeatable' => 'Powtarzalna',
            'daily' => 'Codzienna',
            'weekly' => 'Tygodniowa'
        ];
    }

    /**
     * Zwraca dostępne statusy zadań
     */
    public function getTaskStatuses()
    {
        return [
            'not_started' => 'Nierozpoczęte',
            'in_progress' => 'W trakcie',
            'completed' => 'Ukończone',
            'failed' => 'Nieudane'
        ];
    }

    /**
     * Zwraca statystyki buildera misji
     */
    public function getBuilderStats()
    {
        // Pobierz liczbę misji z WordPress (post_type='mission')
        $missions_count = wp_count_posts('mission');
        $total_missions = 0;
        if ($missions_count) {
            $total_missions = $missions_count->publish + $missions_count->draft;
        }

        // Pobierz liczbę zapisanych przepisów misji z bazy
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';
        $active_missions = 0;
        $total_tasks = 0;
        $users_with_missions = 0;
        $completed_tasks = 0;
        $not_started_tasks = 0;

        if ($this->wpdb->get_var("SHOW TABLES LIKE '$mission_tasks_table'") === $mission_tasks_table) {
            // Policz unikalne misje
            $active_missions = (int) $this->wpdb->get_var("SELECT COUNT(DISTINCT CONCAT(user_id, '-', mission_id)) FROM $mission_tasks_table");
            // Policz użytkowników z misjami
            $users_with_missions = (int) $this->wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $mission_tasks_table");
            // Policz wszystkie zadania
            $total_tasks = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM $mission_tasks_table");
            // Policz ukończone zadania
            $completed_tasks = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM $mission_tasks_table WHERE task_status = 'completed'");
            // Policz zadania nierozpoczęte
            $not_started_tasks = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM $mission_tasks_table WHERE task_status = 'not_started'");
        }

        return [
            'wordpress_missions' => $total_missions,
            'assigned_missions' => $active_missions,
            'total_tasks' => $total_tasks,
            'users_with_missions' => $users_with_missions,
            'completed_tasks' => $completed_tasks,
            'not_started_tasks' => $not_started_tasks,
            'available_types' => count($this->getTaskTypes()),
            'mission_types' => count($this->getMissionTypes())
        ];
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
     * Waliduje misję pobraną z WordPress
     */
    public function validateWordPressMission($mission_data)
    {
        $result = $this->createMissionRecipe($mission_data);

        // Przekształć wynik na format z kluczem 'valid'
        return [
            'valid' => $result['success'],
            'message' => isset($result['message']) ? $result['message'] : 'Nieznany błąd podczas walidacji misji',
            'recipe' => isset($result['recipe']) ? $result['recipe'] : null
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

        // Waliduj misję
        $validation = $this->validateWordPressMission($mission_result['mission']);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Misja nie przeszła walidacji: ' . $validation['message']
            ];
        }

        // Sprawdźmy, czy recipe jest dostępne w walidacji
        if (!isset($validation['recipe']) || $validation['recipe'] === null) {
            return [
                'success' => false,
                'message' => 'Błąd walidacji misji: brak przepisu misji'
            ];
        }

        $mission_data = $validation['recipe'];
        $mission = $mission_result['mission'];

        // Sprawdź czy użytkownik już ma tę misję (w nowej tabeli)
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
                ],
                [
                    '%d', // user_id
                    '%d', // mission_id
                    '%s', // mission_title
                    '%s', // mission_description
                    '%d', // mission_time_limit
                    '%s', // mission_type
                    '%s', // mission_status
                    '%s', // task_id
                    '%s', // task_title
                    '%s', // task_description
                    '%d', // task_optional
                    '%d', // task_attempt_limit
                    '%s', // task_type
                    '%d', // task_location
                    '%s', // task_location_scene
                    '%s', // task_checkpoint_npc
                    '%s', // task_defeat_enemies
                    '%s', // task_status
                    '%s', // mission_started_at
                    '%s'  // mission_expires_at
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
     * Pobiera misje użytkownika (z nowej struktury tabeli)
     */
    public function getUserMissions($user_id, $status = null)
    {
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        $where = $this->wpdb->prepare("WHERE user_id = %d", $user_id);
        if ($status) {
            $where .= $this->wpdb->prepare(" AND mission_status = %s", $status);
        }

        $sql = "
            SELECT 
                mission_id,
                mission_title,
                mission_description,
                mission_time_limit,
                mission_type,
                mission_status,
                mission_started_at,
                mission_expires_at,
                COUNT(*) as total_tasks,
                SUM(CASE WHEN task_status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN task_status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                SUM(CASE WHEN task_status = 'not_started' THEN 1 ELSE 0 END) as not_started_tasks,
                SUM(CASE WHEN task_status = 'failed' THEN 1 ELSE 0 END) as failed_tasks
            FROM $mission_tasks_table
            $where
            GROUP BY mission_id, mission_title, mission_description, mission_time_limit, mission_type, mission_status, mission_started_at, mission_expires_at
            ORDER BY mission_started_at DESC
        ";

        return $this->wpdb->get_results($sql);
    }

    /**
     * Pobiera zadania misji użytkownika (z nowej struktury tabeli)
     */
    public function getUserMissionTasks($user_id, $mission_id)
    {
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $mission_tasks_table 
             WHERE user_id = %d AND mission_id = %d 
             ORDER BY task_id ASC",
            $user_id,
            $mission_id
        ));
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

        // Sprawdź strukturę pierwszego użytkownika dla debugowania
        $sample_user = reset($users);
        $debug_info = '';
        if ($sample_user) {
            $debug_info = ' [Struktura danych użytkownika: ' . implode(', ', array_keys($sample_user)) . ']';
        }

        $assigned_missions = 0;
        $total_tasks = 0;
        $errors = [];

        foreach ($wp_missions as $wp_mission) {
            // Waliduj misję z WordPress
            $mission_result = $this->getMissionFromWordPress($wp_mission->ID);
            if (!$mission_result['success']) {
                $errors[] = "Misja '{$wp_mission->post_title}' (ID: {$wp_mission->ID}): " . $mission_result['message'];
                continue;
            }

            $mission_data = $mission_result['mission'];
            $validation = $this->validateWordPressMission($mission_data);

            if (!$validation['valid']) {
                $errors[] = "Misja '{$wp_mission->post_title}' (ID: {$wp_mission->ID}): " . $validation['message'];
                continue;
            }

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

        $message = "Zbudowano misje! Przypisano {$assigned_missions} misji z {$actual_tasks} zadaniami." . (isset($debug_info) ? $debug_info : '');

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

    /**
     * Aktualizuje status zadania
     */
    public function updateTaskStatus($user_id, $mission_id, $task_id, $status, $wins = null, $losses = null, $draws = null)
    {
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        $update_data = ['task_status' => $status];
        $format = ['%s'];

        // Dodaj statystyki walk jeśli podane
        if ($wins !== null) {
            $update_data['task_wins'] = intval($wins);
            $format[] = '%d';
        }
        if ($losses !== null) {
            $update_data['task_losses'] = intval($losses);
            $format[] = '%d';
        }
        if ($draws !== null) {
            $update_data['task_draws'] = intval($draws);
            $format[] = '%d';
        }

        // Dodaj timestamp zakończenia zadania
        if ($status === 'completed') {
            $update_data['task_completed_at'] = date('Y-m-d H:i:s');
            $format[] = '%s';
        }

        $result = $this->wpdb->update(
            $mission_tasks_table,
            $update_data,
            [
                'user_id' => $user_id,
                'mission_id' => $mission_id,
                'task_id' => $task_id
            ],
            $format,
            ['%d', '%d', '%s']
        );

        // Sprawdź czy wszystkie zadania misji są ukończone
        if ($status === 'completed') {
            $this->checkMissionCompletion($user_id, $mission_id);
        }

        return $result !== false;
    }

    /**
     * Sprawdza czy misja jest ukończona i aktualizuje jej status
     */
    private function checkMissionCompletion($user_id, $mission_id)
    {
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        // Sprawdź czy wszystkie nieobowiązkowe zadania są ukończone
        $incomplete_tasks = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $mission_tasks_table 
             WHERE user_id = %d AND mission_id = %d 
             AND task_optional = 0 AND task_status != 'completed'",
            $user_id,
            $mission_id
        ));

        if ($incomplete_tasks == 0) {
            // Wszystkie wymagane zadania ukończone - zakończ misję
            $this->wpdb->update(
                $mission_tasks_table,
                [
                    'mission_status' => 'completed',
                    'mission_completed_at' => date('Y-m-d H:i:s')
                ],
                [
                    'user_id' => $user_id,
                    'mission_id' => $mission_id
                ],
                ['%s', '%s'],
                ['%d', '%d']
            );
        }
    }

    /**
     * Pobiera szczegóły konkretnej misji użytkownika
     */
    public function getUserMissionDetails($user_id, $mission_id)
    {
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        // Pobierz dane misji i wszystkie jej zadania
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $mission_tasks_table 
             WHERE user_id = %d AND mission_id = %d 
             ORDER BY task_id ASC",
            $user_id,
            $mission_id
        ));

        if (empty($results)) {
            return null;
        }

        // Pierwszy rekord zawiera dane misji
        $first_task = $results[0];

        $mission_details = [
            'mission_id' => $first_task->mission_id,
            'mission_title' => $first_task->mission_title,
            'mission_description' => $first_task->mission_description,
            'mission_time_limit' => $first_task->mission_time_limit,
            'mission_type' => $first_task->mission_type,
            'mission_status' => $first_task->mission_status,
            'mission_started_at' => $first_task->mission_started_at,
            'mission_expires_at' => $first_task->mission_expires_at,
            'mission_completed_at' => $first_task->mission_completed_at ?? null,
            'tasks' => []
        ];

        // Dodaj wszystkie zadania
        foreach ($results as $task) {
            $mission_details['tasks'][] = [
                'task_id' => $task->task_id,
                'task_title' => $task->task_title,
                'task_description' => $task->task_description,
                'task_optional' => $task->task_optional,
                'task_attempt_limit' => $task->task_attempt_limit,
                'task_type' => $task->task_type,
                'task_location' => $task->task_location,
                'task_location_scene' => $task->task_location_scene,
                'task_checkpoint_npc' => $task->task_checkpoint_npc ? json_decode($task->task_checkpoint_npc, true) : null,
                'task_defeat_enemies' => $task->task_defeat_enemies ? json_decode($task->task_defeat_enemies, true) : null,
                'task_status' => $task->task_status,
                'task_wins' => $task->task_wins,
                'task_losses' => $task->task_losses,
                'task_draws' => $task->task_draws,
                'task_completed_at' => $task->task_completed_at
            ];
        }

        return $mission_details;
    }

    /**
     * Usuwa konkretną misję użytkownika
     */
    public function removeUserMission($user_id, $mission_id)
    {
        $mission_tasks_table = $this->wpdb->prefix . 'game_user_mission_tasks';

        $deleted = $this->wpdb->delete(
            $mission_tasks_table,
            [
                'user_id' => $user_id,
                'mission_id' => $mission_id
            ],
            ['%d', '%d']
        );

        return [
            'success' => $deleted !== false,
            'deleted_tasks' => $deleted ?: 0
        ];
    }
}
