<?php

/**
 * Repozytorium dla tabeli game_users
 * Obsługuje wszystkie operacje CRUD na danych graczy
 */
class GameUserRepository
{
    private $wpdb;
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'game_users';
    }

    /**
     * Tworzy nowego gracza z domyślnymi wartościami
     */
    public function create($user_id, $data = [])
    {
        $default_data = [
            'user_id' => $user_id,
            'nick' => '',
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
        ];

        $final_data = array_merge($default_data, $data);

        $result = $this->wpdb->insert($this->table_name, $final_data);

        if ($result === false) {
            throw new Exception('Nie udało się utworzyć gracza: ' . $this->wpdb->last_error);
        }

        return $user_id;
    }

    /**
     * Pobiera gracza po ID
     */
    public function getByUserId($user_id)
    {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE user_id = %d", $user_id),
            ARRAY_A
        );

        return $result;
    }

    /**
     * Sprawdza czy gracz istnieje
     */
    public function exists($user_id)
    {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d", $user_id)
        );

        return (int)$count > 0;
    }

    /**
     * Aktualizuje dane gracza
     */
    public function update($user_id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        $result = $this->wpdb->update(
            $this->table_name,
            $data,
            ['user_id' => $user_id]
        );

        if ($result === false) {
            throw new Exception('Nie udało się zaktualizować gracza: ' . $this->wpdb->last_error);
        }

        return $result;
    }

    /**
     * Pobiera wszystkich graczy (dla panelu admina)
     */
    public function getAll($limit = 50, $offset = 0)
    {
        $sql = "SELECT gu.*, u.user_login, u.user_email 
                FROM {$this->table_name} gu 
                LEFT JOIN {$this->wpdb->users} u ON gu.user_id = u.ID 
                ORDER BY gu.created_at DESC 
                LIMIT %d OFFSET %d";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $limit, $offset),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Usuwa gracza
     */
    public function delete($user_id)
    {
        $result = $this->wpdb->delete(
            $this->table_name,
            ['user_id' => $user_id]
        );

        if ($result === false) {
            throw new Exception('Nie udało się usunąć gracza: ' . $this->wpdb->last_error);
        }

        return $result;
    }

    /**
     * Zlicza wszystkich graczy
     */
    public function count()
    {
        return (int)$this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }
}
