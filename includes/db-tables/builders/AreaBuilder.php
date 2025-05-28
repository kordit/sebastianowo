<?php

/**
 * Builder terenów i scen gry
 * Odpowiada za pobieranie danych z postów typu 'tereny' i 'events'
 * oraz budowanie relacji między obszarami i dostępnymi scenami
 */
class AreaBuilder
{

    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Zwraca wszystkie obszary (tereny i eventy) z WP
     */
    public function getAllAreas()
    {
        $areas = [];

        // Pobierz tereny
        $tereny_args = [
            'post_type' => 'tereny',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ];

        $tereny_posts = get_posts($tereny_args);

        foreach ($tereny_posts as $post) {
            $areas[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => 'teren',
                'content' => $post->post_content,
                'scenes' => $this->getAreaScenes($post->ID)
            ];
        }

        // Pobierz eventy
        $events_args = [
            'post_type' => 'events',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ];

        $events_posts = get_posts($events_args);

        foreach ($events_posts as $post) {
            $areas[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => 'event',
                'content' => $post->post_content,
                'scenes' => $this->getAreaScenes($post->ID)
            ];
        }

        return $areas;
    }

    /**
     * Pobiera wszystkie sceny dla danego obszaru
     */
    private function getAreaScenes($post_id)
    {
        $scenes = [];

        // Sprawdź czy istnieje pole 'scenes' (ACF Repeater)
        if (function_exists('have_rows') && have_rows('scenes', $post_id)) {
            while (have_rows('scenes', $post_id)) {
                the_row();

                // Pobierz id sceny z pola id_sceny
                $scene_id = get_sub_field('id_sceny');

                if ($scene_id) {
                    $scenes[] = $scene_id;
                }
            }
        }

        return $scenes;
    }

    /**
     * Tworzy powiązania między graczami a terenami
     * Inicjuje podstawowe wpisy w tabeli game_user_areas
     */
    public function buildAllAreaConnections()
    {
        $areas = $this->getAllAreas();
        $user_repo = new GameUserRepository();
        $users = $user_repo->getAll();

        $created = 0;
        $updated = 0;

        foreach ($users as $user) {
            foreach ($areas as $area) {
                // Sprawdź czy relacja już istnieje
                $exists = $this->getAreaConnection($user['user_id'], $area['id']);

                if (!$exists) {
                    // Domyślnie tylko główne tereny są odblokowane
                    $is_unlocked = ($area['type'] === 'teren') ? 1 : 0;

                    // Przygotuj dane scen jako JSON
                    $scenes_json = !empty($area['scenes']) ? json_encode($area['scenes']) : null;

                    // Domyślnie brak odblokowanych scen
                    $unlocked_scenes_json = null;

                    // Utwórz nowy wpis w tabeli
                    $result = $this->wpdb->insert(
                        $this->wpdb->prefix . 'game_user_areas',
                        [
                            'user_id' => $user['user_id'],
                            'area_id' => $area['id'],
                            'unlocked' => $is_unlocked,
                            'scenes' => $scenes_json,
                            'unlocked_scenes' => $unlocked_scenes_json,
                            'viewed_scenes' => null,
                            'viewed_area' => 0,
                            'created_at' => current_time('mysql')
                        ]
                    );

                    if ($result) {
                        $created++;
                    }
                } else {
                    // Aktualizujemy tylko listę scen, nie zmieniając stanu odblokowania
                    $scenes_json = !empty($area['scenes']) ? json_encode($area['scenes']) : null;

                    $result = $this->wpdb->update(
                        $this->wpdb->prefix . 'game_user_areas',
                        [
                            'scenes' => $scenes_json,
                            'updated_at' => current_time('mysql')
                        ],
                        [
                            'user_id' => $user['user_id'],
                            'area_id' => $area['id']
                        ]
                    );

                    if ($result) {
                        $updated++;
                    }
                }
            }
        }

        return [
            'success' => true,
            'message' => "Zbudowano powiązania obszarów dla graczy. Utworzono: $created, Zaktualizowano: $updated."
        ];
    }

    /**
     * Sprawdza czy istnieje połączenie między graczem a obszarem
     */
    public function getAreaConnection($user_id, $area_id)
    {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}game_user_areas WHERE user_id = %d AND area_id = %d",
                $user_id,
                $area_id
            )
        );

        return $result ? $result : false;
    }

    /**
     * Aktualizuje status odblokowania obszaru dla użytkownika
     */
    public function unlockArea($user_id, $area_id, $unlock = true)
    {
        $connection = $this->getAreaConnection($user_id, $area_id);

        if ($connection) {
            $result = $this->wpdb->update(
                $this->wpdb->prefix . 'game_user_areas',
                [
                    'unlocked' => $unlock ? 1 : 0,
                    'updated_at' => current_time('mysql')
                ],
                [
                    'user_id' => $user_id,
                    'area_id' => $area_id
                ]
            );

            return $result !== false;
        }

        return false;
    }

    /**
     * Aktualizuje status odblokowania sceny dla użytkownika
     */
    public function unlockSceneForUser($user_id, $area_id, $scene_id)
    {
        $connection = $this->getAreaConnection($user_id, $area_id);

        if ($connection) {
            $unlocked_scenes = json_decode($connection->unlocked_scenes ?? '[]', true) ?: [];

            // Dodaj scenę do odblokowanych jeśli jeszcze jej nie ma
            if (!in_array($scene_id, $unlocked_scenes)) {
                $unlocked_scenes[] = $scene_id;

                $result = $this->wpdb->update(
                    $this->wpdb->prefix . 'game_user_areas',
                    [
                        'unlocked_scenes' => json_encode($unlocked_scenes),
                        'updated_at' => current_time('mysql')
                    ],
                    [
                        'user_id' => $user_id,
                        'area_id' => $area_id
                    ]
                );

                return $result !== false;
            }

            return true; // Scena już była odblokowana
        }

        return false; // Brak połączenia użytkownika z obszarem
    }

    /**
     * Oznacza scenę jako oglądaną przez użytkownika
     */
    public function markSceneAsViewed($user_id, $area_id, $scene_id)
    {
        $connection = $this->getAreaConnection($user_id, $area_id);

        if ($connection) {
            $viewed_scenes = json_decode($connection->viewed_scenes ?? '[]', true) ?: [];

            // Dodaj scenę do oglądanych jeśli jeszcze jej nie ma
            if (!in_array($scene_id, $viewed_scenes)) {
                $viewed_scenes[] = $scene_id;

                $result = $this->wpdb->update(
                    $this->wpdb->prefix . 'game_user_areas',
                    [
                        'viewed_scenes' => json_encode($viewed_scenes),
                        'updated_at' => current_time('mysql')
                    ],
                    [
                        'user_id' => $user_id,
                        'area_id' => $area_id
                    ]
                );

                return $result !== false;
            }

            return true; // Scena już była oznaczona jako oglądana
        }

        return false; // Brak połączenia użytkownika z obszarem
    }

    /**
     * Oznacza obszar jako oglądany przez użytkownika
     */
    public function markAreaAsViewed($user_id, $area_id)
    {
        $connection = $this->getAreaConnection($user_id, $area_id);

        if ($connection) {
            $result = $this->wpdb->update(
                $this->wpdb->prefix . 'game_user_areas',
                [
                    'viewed_area' => 1,
                    'updated_at' => current_time('mysql')
                ],
                [
                    'user_id' => $user_id,
                    'area_id' => $area_id
                ]
            );

            return $result !== false;
        }

        return false;
    }

    /**
     * Pobiera statystyki obszarów i scen
     */
    public function getAreasStats()
    {
        $total_areas = count($this->getAllAreas());

        $areas_count = $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT area_id) FROM {$this->wpdb->prefix}game_user_areas"
        );

        $users_count = $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$this->wpdb->prefix}game_user_areas"
        );

        $total_connections = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}game_user_areas"
        );

        return [
            'total_areas' => $total_areas,
            'areas_in_db' => (int) $areas_count,
            'users_with_areas' => (int) $users_count,
            'total_connections' => (int) $total_connections
        ];
    }

    /**
     * Usuwa wszystkie powiązania z obszarami
     */
    public function clearAllAreaConnections()
    {
        $rows = $this->wpdb->query(
            "TRUNCATE TABLE {$this->wpdb->prefix}game_user_areas"
        );

        return [
            'success' => true,
            'message' => "Wszystkie powiązania z obszarami zostały usunięte."
        ];
    }
}
