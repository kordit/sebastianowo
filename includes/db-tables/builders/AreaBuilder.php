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

        // Debug: Sprawdź dostępne typy postów
        $post_types = get_post_types(['public' => true], 'names');
        error_log("DEBUG: Dostępne typy postów: " . implode(", ", $post_types));

        // Pobierz tereny
        $tereny_args = [
            'post_type' => 'tereny',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ];

        $tereny_posts = get_posts($tereny_args);
        error_log("DEBUG: Znaleziono " . count($tereny_posts) . " postów typu 'tereny'");

        foreach ($tereny_posts as $post) {
            $scenes = $this->getAreaScenes($post->ID);
            $areas[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => 'teren',
                'content' => $post->post_content,
                'scenes' => $scenes
            ];
            error_log("DEBUG: Dodano obszar '{$post->post_title}' (ID: {$post->ID}) z " . count($scenes) . " scenami");
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
        error_log("DEBUG: Znaleziono " . count($events_posts) . " postów typu 'events'");

        foreach ($events_posts as $post) {
            $scenes = $this->getAreaScenes($post->ID);
            $areas[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => 'event',
                'content' => $post->post_content,
                'scenes' => $scenes
            ];
            error_log("DEBUG: Dodano obszar '{$post->post_title}' (ID: {$post->ID}) z " . count($scenes) . " scenami");
        }

        // Jeśli nie znaleziono żadnych obszarów ze scenami, spróbuj utworzyć testowe wpisy
        if (count($areas) == 0 || !array_reduce($areas, function ($carry, $item) {
            return $carry || !empty($item['scenes']);
        }, false)) {
            error_log("DEBUG: Nie znaleziono żadnych obszarów ze scenami, rozważenie dodania testowych danych");
        }

        return $areas;
    }

    /**
     * Pobiera wszystkie sceny dla danego obszaru
     */
    private function getAreaScenes($post_id)
    {
        $scenes = [];

        // Debug: sprawdź czy post istnieje
        $post = get_post($post_id);
        if (!$post) {
            error_log("DEBUG: Post o ID {$post_id} nie istnieje");
            return $scenes;
        }

        // Debug: sprawdź dostępne pola ACF
        if (function_exists('get_field_objects')) {
            $field_objects = get_field_objects($post_id);
            if ($field_objects) {
                error_log("DEBUG: Dostępne pola ACF dla posta {$post_id} (" . $post->post_title . "): " . implode(", ", array_keys($field_objects)));
            } else {
                error_log("DEBUG: Brak pól ACF dla posta {$post_id} (" . $post->post_title . ")");
            }
        }

        // Sprawdź czy istnieje pole 'scenes' (ACF Repeater)
        if (function_exists('have_rows') && have_rows('scenes', $post_id)) {
            error_log("DEBUG: Znaleziono pole 'scenes' dla posta {$post_id} (" . $post->post_title . ")");

            while (have_rows('scenes', $post_id)) {
                the_row();

                // Pobierz id sceny z pola id_sceny
                $scene_id = get_sub_field('id_sceny');

                if ($scene_id) {
                    error_log("DEBUG: Dodaję scenę ID: {$scene_id}");
                    $scenes[] = $scene_id;
                } else {
                    error_log("DEBUG: Znaleziono pustą wartość id_sceny");
                }
            }
        } else {
            error_log("DEBUG: Brak pola 'scenes' dla posta {$post_id} (" . $post->post_title . ")");

            // Sprawdź alternatywne pola scen
            if (function_exists('have_rows') && have_rows('events', $post_id)) {
                error_log("DEBUG: Znaleziono pole 'events' zamiast 'scenes' dla posta {$post_id}");
                while (have_rows('events', $post_id)) {
                    the_row();
                    $scene_id = get_sub_field('id_sceny');
                    if ($scene_id) {
                        error_log("DEBUG: Dodaję scenę z pola 'events', ID: {$scene_id}");
                        $scenes[] = $scene_id;
                    }
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
        $users = $user_repo->getAll(999999, 0); // Pobierz wszystkich użytkowników

        $total_created = 0;
        $total_updated = 0;
        $total_areas = 0;
        $total_areas_with_scenes = 0;
        $errors = [];

        // Sprawdź czy mamy strukturę użytkowników
        if (empty($users)) {
            return [
                'success' => false,
                'message' => "Brak użytkowników w bazie danych."
            ];
        }

        // Sprawdź pierwszy rekord użytkownika dla debugowania
        $sample_user = reset($users);

        // Sprawdź czy klucz 'user_id' istnieje, jeśli nie, sprawdź alternatywne klucze
        $user_id_key = 'user_id';
        if (!isset($sample_user['user_id'])) {
            if (isset($sample_user['id'])) {
                $user_id_key = 'id';
            } elseif (isset($sample_user['ID'])) {
                $user_id_key = 'ID';
            } else {
                error_log("ERROR: Nie znaleziono klucza ID użytkownika w strukturze danych.");
                return [
                    'success' => false,
                    'message' => "Nieprawidłowa struktura danych użytkowników."
                ];
            }
            error_log("DEBUG: Używam klucza '{$user_id_key}' jako identyfikator użytkownika.");
        }

        // Jeśli nie ma obszarów, dodaj testowy obszar z domyślną sceną
        if (empty($areas)) {
            error_log("ERROR: Brak zdefiniowanych obszarów.");
            return [
                'success' => false,
                'message' => "Brak zdefiniowanych obszarów w systemie. Sprawdź posty typu 'tereny' i 'events'."
            ];
        }

        // Sprawdzenie czy mamy jakiekolwiek obszary ze scenami
        $areas_with_scenes = array_filter($areas, function ($area) {
            return !empty($area['scenes']);
        });

        if (empty($areas_with_scenes)) {
            error_log("DEBUG: Żaden z obszarów nie ma zdefiniowanych scen. Dodanie domyślnych scen.");
            // Dodaj domyślną scenę dla każdego obszaru
            foreach ($areas as &$area) {
                $area['scenes'][] = 'default_scene_1';
                error_log("DEBUG: Dodano domyślną scenę 'default_scene_1' dla obszaru '{$area['title']}'");
            }
        }

        foreach ($areas as $area) {
            $total_areas++;

            if (!empty($area['scenes'])) {
                $total_areas_with_scenes++;
                error_log("DEBUG Area: " . $area['title'] . " (ID: " . $area['id'] . ") has " . count($area['scenes']) . " scenes: " . implode(", ", $area['scenes']));
            } else {
                error_log("DEBUG Area: " . $area['title'] . " (ID: " . $area['id'] . ") has NO scenes");
            }
        }

        foreach ($users as $user) {
            if (!isset($user[$user_id_key])) {
                $errors[] = "Użytkownik bez ID: " . print_r($user, true);
                continue;
            }

            $user_id = $user[$user_id_key];

            foreach ($areas as $area) {
                // Dla każdej sceny w obszarze, dodaj wpis
                if (!empty($area['scenes'])) {
                    foreach ($area['scenes'] as $scene_id) {
                        // Sprawdź czy taka kombinacja użytkownik-obszar-scena już istnieje
                        $scene_exists = $this->wpdb->get_var(
                            $this->wpdb->prepare(
                                "SELECT id FROM {$this->wpdb->prefix}game_user_areas 
                                WHERE user_id = %d AND area_id = %d AND scene_id = %s",
                                $user_id,
                                $area['id'],
                                $scene_id
                            )
                        );

                        if (!$scene_exists) {
                            // Wszystkie wartości domyślnie ustawione na 0
                            $is_scene_unlocked = 0;
                            $is_current = 0;

                            // Dodaj nowy wpis dla sceny
                            $result = $this->wpdb->insert(
                                $this->wpdb->prefix . 'game_user_areas',
                                [
                                    'user_id' => $user_id,
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
                            } else {
                                $errors[] = "Błąd dodawania sceny {$scene_id} do obszaru {$area['id']} dla użytkownika {$user_id}: " . $this->wpdb->last_error;
                            }
                        } else {
                            // Możemy zaktualizować status lub inne pole w razie potrzeby
                            $total_updated++;
                        }
                    }
                } else {
                    // Brak scen dla tego obszaru
                    error_log("DEBUG: Obszar '{$area['title']}' (ID: {$area['id']}) nie ma zdefiniowanych scen");
                }
            }
        }

        $message = "Zbudowano powiązania obszarów dla graczy. Utworzono: $total_created, Zaktualizowano: $total_updated.";
        $message .= " Obszary: $total_areas (z czego ze scenami: $total_areas_with_scenes).";

        if (!empty($errors)) {
            $message .= " Wystąpiły błędy: " . count($errors) . " (szczegóły w logu).";
            foreach ($errors as $error) {
                error_log("ERROR: " . $error);
            }
        }

        return [
            'success' => true,
            'message' => $message
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
