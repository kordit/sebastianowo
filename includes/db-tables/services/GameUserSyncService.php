<?php

/**
 * Serwis synchronizacji użytkowników WordPress z tabelą game_users
 * Odpowiedzialny za automatyczne tworzenie i import graczy
 */
class GameUserSyncService
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Pobiera statystyki użytkowników
     */
    public function getUsersStats()
    {
        // Liczba użytkowników WordPress
        $wp_users_count = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->users}");

        // Liczba graczy w game_users (jeśli tabela istnieje)
        $game_users_count = 0;
        $game_users_table = $this->wpdb->prefix . 'game_users';
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '$game_users_table'") === $game_users_table;

        if ($table_exists) {
            $game_users_count = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM $game_users_table");
        }

        return [
            'wp_users' => $wp_users_count,
            'game_users' => $game_users_count,
            'missing' => $wp_users_count - $game_users_count,
            'table_exists' => $table_exists
        ];
    }

    /**
     * Importuje wszystkich użytkowników WP do game_users
     */
    public function importAllUsers()
    {
        $game_users_table = $this->wpdb->prefix . 'game_users';

        // Sprawdź czy tabela istnieje
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '$game_users_table'") === $game_users_table;
        if (!$table_exists) {
            return [
                'success' => false,
                'message' => 'Tabela game_users nie istnieje. Najpierw utwórz tabele bazy danych.',
                'imported' => 0,
                'skipped' => 0
            ];
        }

        // Pobierz wszystkich użytkowników WP którzy nie mają rekordu w game_users
        $missing_users = $this->wpdb->get_results("
            SELECT u.ID, u.user_login, u.display_name 
            FROM {$this->wpdb->users} u
            LEFT JOIN {$this->wpdb->prefix}game_users gu ON u.ID = gu.user_id
            WHERE gu.user_id IS NULL
        ");

        $total_missing = count($missing_users);
        $imported = 0;
        $failed = 0;

        foreach ($missing_users as $user) {
            if ($this->createGameUser($user->ID, $user->display_name)) {
                $imported++;
            } else {
                $failed++;
            }
        }

        if ($total_missing === 0) {
            return [
                'success' => true,
                'message' => 'Wszyscy użytkownicy WordPress już mają swoje rekordy w game_users.',
                'imported' => 0,
                'skipped' => 0
            ];
        }

        return [
            'success' => true,
            'message' => "Import zakończony pomyślnie.",
            'imported' => $imported,
            'skipped' => $failed
        ];
    }

    /**
     * Tworzy rekord game_user dla danego użytkownika WP
     */
    public function createGameUser($user_id, $display_name = '')
    {
        $table_name = $this->wpdb->prefix . 'game_users';

        // Sprawdź czy tabela istnieje
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return false;
        }

        // Sprawdź czy rekord już istnieje
        $exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE user_id = %d",
            $user_id
        ));

        if ($exists) {
            return false; // Rekord już istnieje
        }

        // Pobierz dane użytkownika WP jeśli nie podano display_name
        if (empty($display_name)) {
            $user = get_user_by('ID', $user_id);
            $display_name = $user ? $user->display_name : '';
        }

        // Wstaw nowy rekord z domyślnymi wartościami
        $result = $this->wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'nick' => $display_name,
                'user_class' => '',
                'avatar' => 0,
                'avatar_full' => 0,
                'story_text' => '',
                'strength' => 1,
                'defense' => 1,
                'dexterity' => 1,
                'perception' => 1,
                'technical' => 1,
                'charisma' => 1,
                'combat' => 0,
                'steal' => 0,
                'craft' => 0,
                'trade' => 0,
                'relations' => 0,
                'street' => 0,
                'exp' => 0,
                'learning_points' => 3,
                'reputation' => 1,
                'life' => 100,
                'max_life' => 100,
                'energy' => 100,
                'max_energy' => 100,
                'gold' => 0,
                'cigarettes' => 0
            ],
            [
                '%d',
                '%s',
                '%s',
                '%d',
                '%d',
                '%s',
                '%d',
                '%s',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d'
            ]
        );

        return $result !== false;
    }

    /**
     * Hook do automatycznego tworzenia gracza przy rejestracji użytkownika WP
     */
    public function autoCreateGameUser($user_id)
    {
        $user = get_user_by('ID', $user_id);
        if ($user) {
            $this->createGameUser($user_id, $user->display_name);
        }
    }
}
