<?php

/**
 * Repozytorium dla tabeli game_user_relations
 * Obsługuje wszystkie operacje CRUD na relacjach użytkowników z NPC
 */
class GameNPCRelationRepository
{
    private $wpdb;
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'game_user_relations';
    }

    /**
     * Pobiera wszystkie relacje użytkownika z NPC
     */
    public function getUserRelations($user_id)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d ORDER BY npc_id",
            $user_id
        ), ARRAY_A);
    }

    /**
     * Pobiera konkretną relację użytkownika z NPC
     */
    public function getRelation($user_id, $npc_id)
    {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND npc_id = %d",
            $user_id,
            $npc_id
        ), ARRAY_A);
    }

    /**
     * Tworzy nową relację
     */
    public function createRelation($user_id, $npc_id, $data = [])
    {
        $default_data = [
            'user_id' => $user_id,
            'npc_id' => $npc_id,
            'relation_value' => 0,
            'is_known' => 0,
            'fights_won' => 0,
            'fights_lost' => 0,
            'fights_draw' => 0,
            'last_interaction' => null
        ];

        $insert_data = array_merge($default_data, $data);

        $result = $this->wpdb->insert($this->table_name, $insert_data);

        return $result !== false ? $this->wpdb->insert_id : false;
    }

    /**
     * Aktualizuje relację
     */
    public function updateRelation($user_id, $npc_id, $data)
    {
        return $this->wpdb->update(
            $this->table_name,
            $data,
            ['user_id' => $user_id, 'npc_id' => $npc_id]
        );
    }

    /**
     * Inicjalizuje relacje dla użytkownika ze wszystkimi istniejącymi NPC
     */
    public function initializeUserRelations($user_id)
    {
        // Pobierz wszystkie NPC
        $npcs = get_posts([
            'post_type' => 'npc',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);

        $created_count = 0;

        foreach ($npcs as $npc_id) {
            // Sprawdź czy relacja już istnieje
            $exists = $this->getRelation($user_id, $npc_id);

            if (!$exists) {
                $result = $this->createRelation($user_id, $npc_id);
                if ($result) {
                    $created_count++;
                }
            }
        }

        return $created_count;
    }

    /**
     * Usuwa wszystkie relacje użytkownika
     */
    public function deleteUserRelations($user_id)
    {
        return $this->wpdb->delete($this->table_name, ['user_id' => $user_id]);
    }

    /**
     * Pobiera statystyki relacji dla użytkownika
     */
    public function getUserRelationStats($user_id)
    {
        $relations = $this->getUserRelations($user_id);

        $stats = [
            'total' => count($relations),
            'known' => 0,
            'positive' => 0,
            'negative' => 0,
            'neutral' => 0,
            'total_fights' => 0,
            'fights_won' => 0,
            'fights_lost' => 0,
            'fights_draw' => 0
        ];

        foreach ($relations as $relation) {
            if ($relation['is_known']) {
                $stats['known']++;
            }

            if ($relation['relation_value'] > 0) {
                $stats['positive']++;
            } elseif ($relation['relation_value'] < 0) {
                $stats['negative']++;
            } else {
                $stats['neutral']++;
            }

            $stats['fights_won'] += $relation['fights_won'];
            $stats['fights_lost'] += $relation['fights_lost'];
            $stats['fights_draw'] += $relation['fights_draw'];
            $stats['total_fights'] += $relation['fights_won'] + $relation['fights_lost'] + $relation['fights_draw'];
        }

        return $stats;
    }
}
