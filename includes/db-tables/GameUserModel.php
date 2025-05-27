<?php

/**
 * Model danych użytkownika dla customowych tabel
 * 
 * Zapewnia interfejs do operacji CRUD na danych gracza
 */
class GameUserModel
{
    private $user_id;
    private $tables;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->tables = GameDatabaseManager::get_instance()->get_table_names();
    }

    /**
     * Pobiera wszystkie dane użytkownika z customowych tabel
     */
    public function get_user_data()
    {
        return [
            'basic' => $this->get_basic_data(),
            'skills' => $this->get_skills_data(),
            'items' => $this->get_items_data(),
            'missions' => $this->get_missions_data(),
            'relations' => $this->get_relations_data(),
            'areas' => $this->get_areas_data()
        ];
    }

    /**
     * Pobiera podstawowe dane użytkownika
     */
    public function get_basic_data()
    {
        global $wpdb;

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['users']} WHERE id = %d",
            $this->user_id
        ), ARRAY_A);

        return $result ?: null;
    }

    /**
     * Pobiera umiejętności użytkownika
     */
    public function get_skills_data()
    {
        global $wpdb;

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['user_skills']} WHERE user_id = %d",
            $this->user_id
        ), ARRAY_A);

        return $result ?: null;
    }

    /**
     * Pobiera przedmioty użytkownika
     */
    public function get_items_data()
    {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT ui.*, p.post_title as item_name 
             FROM {$this->tables['user_items']} ui 
             LEFT JOIN {$wpdb->posts} p ON ui.item_id = p.ID 
             WHERE ui.user_id = %d 
             ORDER BY ui.equipped DESC, p.post_title ASC",
            $this->user_id
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Pobiera misje użytkownika
     */
    public function get_missions_data()
    {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT um.*, p.post_title as mission_name 
             FROM {$this->tables['user_missions']} um 
             LEFT JOIN {$wpdb->posts} p ON um.mission_id = p.ID 
             WHERE um.user_id = %d 
             ORDER BY um.created_at DESC",
            $this->user_id
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Pobiera relacje z NPC
     */
    public function get_relations_data()
    {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT ur.*, p.post_title as npc_name 
             FROM {$this->tables['user_relations']} ur 
             LEFT JOIN {$wpdb->posts} p ON ur.npc_id = p.ID 
             WHERE ur.user_id = %d 
             ORDER BY ur.relation_value DESC",
            $this->user_id
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Pobiera dostępne obszary
     */
    public function get_areas_data()
    {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT ua.*, p.post_title as area_name 
             FROM {$this->tables['user_areas']} ua 
             LEFT JOIN {$wpdb->posts} p ON ua.area_id = p.ID 
             WHERE ua.user_id = %d 
             ORDER BY p.post_title ASC",
            $this->user_id
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Tworzy rekord użytkownika w tabeli głównej
     */
    public function create_user_record($data = [])
    {
        global $wpdb;

        $defaults = [
            'id' => $this->user_id,
            'nick' => '',
            'avatar_id' => 0,
            'user_class' => '',
            'current_area_id' => 0,
            'strength' => 1,
            'defense' => 1,
            'dexterity' => 1,
            'perception' => 1,
            'technical' => 1,
            'charisma' => 1,
            'max_life' => 100,
            'current_life' => 100,
            'max_energy' => 100,
            'current_energy' => 100,
            'exp' => 0,
            'learning_points' => 3,
            'reputation' => 1
        ];

        $data = array_merge($defaults, $data);

        $result = $wpdb->insert($this->tables['users'], $data);

        if ($result !== false) {
            // Utwórz też rekord umiejętności
            $this->create_skills_record();
        }

        return $result;
    }

    /**
     * Tworzy rekord umiejętności
     */
    public function create_skills_record($data = [])
    {
        global $wpdb;

        $defaults = [
            'user_id' => $this->user_id,
            'combat' => 0,
            'steal' => 0,
            'craft' => 0,
            'trade' => 0,
            'relations' => 0,
            'street' => 0
        ];

        $data = array_merge($defaults, $data);

        return $wpdb->insert($this->tables['user_skills'], $data);
    }

    /**
     * Sprawdza czy użytkownik ma rekord w customowych tabelach
     */
    public function exists()
    {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables['users']} WHERE id = %d",
            $this->user_id
        ));

        return $count > 0;
    }

    /**
     * Inicjalizuje rekordy dla nowego użytkownika
     */
    public function initialize_new_user()
    {
        if (!$this->exists()) {
            $this->create_user_record();
            return true;
        }
        return false;
    }

    /**
     * Aktualizuje podstawowe dane użytkownika
     */
    public function update_basic_data($data)
    {
        global $wpdb;

        return $wpdb->update(
            $this->tables['users'],
            $data,
            ['id' => $this->user_id]
        );
    }

    /**
     * Aktualizuje umiejętności użytkownika
     */
    public function update_skills_data($data)
    {
        global $wpdb;

        return $wpdb->update(
            $this->tables['user_skills'],
            $data,
            ['user_id' => $this->user_id]
        );
    }

    /**
     * Aktualizuje timestamp ostatniej aktywności użytkownika
     */
    public function touch_activity()
    {
        global $wpdb;

        if ($this->exists()) {
            return $wpdb->update(
                $this->tables['users'],
                ['updated_at' => date('Y-m-d H:i:s')],
                ['id' => $this->user_id]
            );
        }

        return false;
    }

    /**
     * Pobiera pojedynczą statystykę użytkownika
     */
    public function get_stat($stat_name)
    {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT {$stat_name} FROM {$this->tables['users']} WHERE id = %d",
            $this->user_id
        ));

        return $result !== null ? intval($result) : 0;
    }

    /**
     * Pobiera pojedynczą umiejętność użytkownika
     */
    public function get_skill($skill_name)
    {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT {$skill_name} FROM {$this->tables['user_skills']} WHERE user_id = %d",
            $this->user_id
        ));

        return $result !== null ? intval($result) : 0;
    }
}
