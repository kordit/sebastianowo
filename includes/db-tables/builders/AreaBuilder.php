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
     * Tworzy powiązania między graczami a terenami i scenami
     * Inicjuje podstawowe wpisy w tabeli game_user_areas
     */
    public function buildAllAreaConnections()
    {
        $areas = $this->getAllAreas();
        $user_repo = new GameUserRepository();
        $users = $user_repo->getAll();

        $total_created = 0;
        $total_updated = 0;

        foreach ($users as $user) {
            foreach ($areas as $area) {
                // Dla każdej sceny w obszarze, dodaj wpis
                if (!empty($area['scenes'])) {
                    foreach ($area['scenes'] as $scene_id) {
                        // Sprawdź czy taka kombinacja użytkownik-obszar-scena już istnieje
                        $scene_exists = $this->wpdb->get_var(
                            $this->wpdb->prepare(
                                "SELECT id FROM {$this->wpdb->prefix}game_user_areas 
                                WHERE user_id = %d AND area_id = %d AND scene_id = %s",
                                $user['user_id'],
                                $area['id'],
                                $scene_id
                            )
                        );

                        if (!$scene_exists) {
                            // Domyślnie, tylko scena 'main' jest odblokowana dla terenów
                            $is_scene_unlocked = ($area['type'] === 'teren' && $scene_id === 'main') ? 1 : 0;

                            // Ustawienie is_current tylko dla głównej sceny w głównym terenie dla nowych graczy
                            $is_current = 0;
                            if ($area['type'] === 'teren' && $scene_id === 'main' && $area['id'] == 207) {  // Zakładam, że obszar 207 to główny obszar
                                $is_current = 1;

                                // Upewnij się, że tylko jedno miejsce jest oznaczone jako aktualne
                                $this->wpdb->update(
                                    $this->wpdb->prefix . 'game_user_areas',
                                    ['is_current' => 0],
                                    ['user_id' => $user['user_id']]
                                );
                            }

                            // Dodaj nowy wpis dla sceny
                            $result = $this->wpdb->insert(
                                $this->wpdb->prefix . 'game_user_areas',
                                [
                                    'user_id' => $user['user_id'],
                                    'area_id' => $area['id'],
                                    'scene_id' => $scene_id,
                                    'unlocked' => $is_scene_unlocked,
                                    'viewed' => 0,
                                    'is_current' => $is_current,
                                    'created_at' => date('Y-m-d H:i:s')
                                ]
                            );

                            if ($result) {
                                $total_created++;
                            }
                        } else {
                            // Możemy zaktualizować status lub inne pole w razie potrzeby
                            $total_updated++;
                        }
                    }
                }
            }
        }

        return [
            'success' => true,
            'message' => "Zbudowano powiązania obszarów dla graczy. Utworzono: $total_created, Zaktualizowano: $total_updated."
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

    /**
     * Pobiera statystyki struktury obszarów (dla buildera)
     */
    public function getAreasStructureStats()
    {
        $areas = $this->getAllAreas();
        $total_areas = count($areas);
        $total_tereny = 0;
        $total_events = 0;
        $total_scenes = 0;

        foreach ($areas as $area) {
            if ($area['type'] === 'teren') {
                $total_tereny++;
            } elseif ($area['type'] === 'event') {
                $total_events++;
            }
            $total_scenes += count($area['scenes']);
        }

        return [
            'total_areas' => $total_areas,
            'total_tereny' => $total_tereny,
            'total_events' => $total_events,
            'total_scenes' => $total_scenes
        ];
    }
}
