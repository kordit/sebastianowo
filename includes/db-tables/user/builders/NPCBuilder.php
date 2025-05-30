<?php

/**
 * NPC Builder - zarządza relacjami wszystkich userów z wszystkimi NPC
 */
class NPCBuilder
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Buduje relacje wszystkich userów z wszystkimi NPC
     * Główna metoda wywoływana z panelu admin
     */
    public function buildAllRelations()
    {
        // Pobierz wszystkich userów z game_users
        $game_users = $this->wpdb->get_results(
            "SELECT user_id FROM {$this->wpdb->prefix}game_users"
        );

        // Pobierz wszystkie NPC z post_type = 'npc'
        $npcs = get_posts([
            'post_type' => 'npc',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);

        $relations_table = $this->wpdb->prefix . 'game_user_relations';
        $created_count = 0;
        $existing_count = 0;

        // Dla każdego usera x każdy NPC
        foreach ($game_users as $user) {
            foreach ($npcs as $npc_id) {
                // Sprawdź czy relacja już istnieje
                $exists = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT id FROM $relations_table WHERE user_id = %d AND npc_id = %d",
                    $user->user_id,
                    $npc_id
                ));

                if (!$exists) {
                    // Stwórz nową relację
                    $result = $this->wpdb->insert(
                        $relations_table,
                        [
                            'user_id' => $user->user_id,
                            'npc_id' => $npc_id,
                            'relation_value' => 0,
                            'is_known' => 0,
                            'fights_won' => 0,
                            'fights_lost' => 0,
                            'fights_draw' => 0,
                            'last_interaction' => null
                        ]
                    );

                    if ($result !== false) {
                        $created_count++;
                    }
                } else {
                    $existing_count++;
                }
            }
        }

        return [
            'success' => true,
            'created' => $created_count,
            'existing' => $existing_count,
            'total_users' => count($game_users),
            'total_npcs' => count($npcs),
            'message' => "Stworzono {$created_count} nowych relacji. {$existing_count} już istniało."
        ];
    }

    /**
     * Usuwa wszystkie relacje (do resetowania)
     */
    public function clearAllRelations()
    {
        $relations_table = $this->wpdb->prefix . 'game_user_relations';
        $deleted = $this->wpdb->query("DELETE FROM $relations_table");

        return [
            'success' => true,
            'deleted' => $deleted,
            'message' => "Usunięto {$deleted} relacji."
        ];
    }

    /**
     * Pobiera statystyki relacji
     */
    public function getRelationsStats()
    {
        $relations_table = $this->wpdb->prefix . 'game_user_relations';

        $total_relations = $this->wpdb->get_var("SELECT COUNT(*) FROM $relations_table");
        $known_relations = $this->wpdb->get_var("SELECT COUNT(*) FROM $relations_table WHERE is_known = 1");
        $positive_relations = $this->wpdb->get_var("SELECT COUNT(*) FROM $relations_table WHERE relation_value > 0");
        $negative_relations = $this->wpdb->get_var("SELECT COUNT(*) FROM $relations_table WHERE relation_value < 0");
        $neutral_relations = $this->wpdb->get_var("SELECT COUNT(*) FROM $relations_table WHERE relation_value = 0");

        $total_users = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}game_users");
        $total_npcs = count(get_posts([
            'post_type' => 'npc',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ]));

        $expected_relations = $total_users * $total_npcs;

        return [
            'total_relations' => (int) $total_relations,
            'known_relations' => (int) $known_relations,
            'positive_relations' => (int) $positive_relations,
            'negative_relations' => (int) $negative_relations,
            'neutral_relations' => (int) $neutral_relations,
            'total_users' => (int) $total_users,
            'total_npcs' => (int) $total_npcs,
            'expected_relations' => (int) $expected_relations,
            'missing_relations' => (int) ($expected_relations - $total_relations)
        ];
    }

    /**
     * Pobiera wszystkie NPC dla listy
     */
    public function getAllNPCs()
    {
        $npcs = get_posts([
            'post_type' => 'npc',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);

        $npc_data = [];
        foreach ($npcs as $npc) {
            $npc_data[] = [
                'id' => $npc->ID,
                'name' => $npc->post_title
            ];
        }

        return $npc_data;
    }
}
