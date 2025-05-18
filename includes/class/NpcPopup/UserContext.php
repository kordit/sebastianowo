<?php

/**
 * UserContext - Klasa zapewniająca kontekst użytkownika dla dialogów NPC
 * 
 * Klasa ta dostarcza informacje o użytkowniku, które są używane do określenia
 * warunków wyświetlania dialogów NPC, takie jak posiadane przedmioty, misje, relacje,
 * zadania i lokalizację.
 */
class UserContext
{
    /**
     * Instancja ManagerUser
     * @var ManagerUser
     */
    private $managerUser;
    private $missions;
    private $relations;
    private $tasks;
    private $location;

    /**
     * Konstruktor klasy UserContext
     *
     * @param ManagerUser $managerUser
     * @param array $missions
     * @param array $relations
     * @param array $tasks
     * @param array $location
     */
    public function __construct(ManagerUser $managerUser, array $missions = [], array $relations = [], array $tasks = [], array $location = [])
    {
        $this->managerUser = $managerUser;
        $this->missions = $missions;
        $this->relations = $relations;
        $this->tasks = $tasks;
        $this->location = $location;
    }

    /**
     * Pobiera listę wszystkich przedmiotów użytkownika wraz z ich ilościami
     *
     * @return array Tablica asocjacyjna, gdzie kluczami są ID przedmiotów, a wartościami ich ilości
     */
    public function get_item_counts(): array
    {
        $user_id = $this->managerUser->getUserId();
        if (!$user_id) {
            return [];
        }
        $items = get_field('items', 'user_' . $user_id);
        if (!is_array($items)) {
            return [];
        }
        $item_counts = [];
        foreach ($items as $item) {
            if (isset($item['item']) && isset($item['quantity'])) {
                $item_id = is_object($item['item']) ? $item['item']->ID : $item['item'];
                $item_name = is_object($item['item']) ? $item['item']->post_title : get_the_title($item_id);
                $item_counts[] = [
                    'id' => $item_id,
                    'name' => $item_name,
                    'quantity' => (int)$item['quantity']
                ];
            }
        }
        return $item_counts;
    }

    /**
     * Pobiera informacje o misjach użytkownika
     *
     * @return array Tablica z informacjami o misjach (nazwa, slug, status, daty)
     */
    public function get_missions(): array
    {
        $user_id = $this->managerUser->getUserId();
        if (!$user_id) {
            return [];
        }
        $fields = get_fields('user_' . $user_id);
        if (!is_array($fields)) {
            return [];
        }
        $missions = [];
        foreach ($fields as $key => $mission) {
            if (strpos($key, 'mission_') === 0 && is_array($mission)) {
                $mission_id = str_replace('mission_', '', $key);
                $post = get_post($mission_id);
                if ($post && $post->post_type === 'mission') {
                    $missions[] = [
                        'id' => (int)$mission_id,
                        'name' => $post->post_title,
                        'id_on_user' => 'mission_' . $mission_id,
                        'slug' => $post->post_name,
                        'status' => $mission['status'] ?? null,
                        'assigned_date' => $mission['assigned_date'] ?? null,
                        'completion_date' => $mission['completion_date'] ?? null,
                    ];
                }
            }
        }
        return $missions;
    }

    /**
     * Pobiera informacje o relacjach użytkownika z NPC
     *
     * @return array Tablica z relacjami użytkownika (id NPC, nazwa NPC, poziom relacji, czy poznany)
     */
    public function get_relations(): array
    {
        $user_id = $this->managerUser->getUserId();
        if (!$user_id) {
            return [];
        }
        // Pobierz wszystkich NPC
        $npcs = get_posts([
            'post_type' => 'npc',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);
        $relations = [];
        foreach ($npcs as $npc) {
            $relation_level = get_field('npc-relation-' . $npc->ID, 'user_' . $user_id);
            $met = get_field('npc-meet-' . $npc->ID, 'user_' . $user_id);
            $relations[] = [
                'npc_id' => $npc->ID,
                'npc_name' => $npc->post_title,
                'level' => (int)($relation_level ?? 0),
                'meet' => (bool)$met,
            ];
        }
        return $relations;
    }

    /**
     * Pobiera informacje o zadaniach (tasks) użytkownika w ramach misji
     *
     * @return array Tablica z zadaniami pogrupowanymi po misjach
     */
    public function get_tasks(): array
    {
        $user_id = $this->managerUser->getUserId();
        if (!$user_id) {
            return [];
        }
        $fields = get_fields('user_' . $user_id);
        if (!is_array($fields)) {
            return [];
        }
        $tasks = [];
        foreach ($fields as $key => $mission) {
            if (strpos($key, 'mission_') === 0 && is_array($mission) && isset($mission['tasks']) && is_array($mission['tasks'])) {
                $mission_id = str_replace('mission_', '', $key);
                foreach ($mission['tasks'] as $task_key => $task_value) {
                    // Jeśli task to tablica (zagnieżdżone podzadania)
                    if (is_array($task_value)) {
                        $tasks[$mission_id][$task_key] = $task_value;
                    } else {
                        // Jeśli task to status (string)
                        $tasks[$mission_id][$task_key] = ['status' => $task_value];
                    }
                }
            }
        }
        return $tasks;
    }

    public function get_location(): array
    {
        return $this->location;
    }

    /**
     * Zwraca kontekst wymagany do walidacji warunku dialogu.
     * 
     * Uwaga: Ta metoda jest pozostawiona dla zachowania kompatybilności.
     * W nowym kodzie należy używać klasy ContextValidator.
     *
     * @param string $acf_layout Typ warunku (np. 'condition_mission', 'condition_npc_relation', ...)
     * @param array $location_info Informacje o lokalizacji (opcjonalnie)
     * @return array Kontekst do walidacji warunku
     * @deprecated Używaj ContextValidator::getContextForCondition() zamiast tej metody
     */
    public function get_context_for_condition(string $acf_layout, array $location_info = []): array
    {
        $validator = new ContextValidator($this);
        return $validator->getContextForCondition($acf_layout, $location_info);
    }
}
