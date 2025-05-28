<?php

/**
 * Repozytorium dostępu do danych obszarów i scen
 */
class GameAreaRepository
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Pobiera wszystkie obszary dla użytkownika (grupowane po area_id)
     */
    public function getUserAreas($user_id)
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT area_id, COUNT(*) as scene_count, 
                        SUM(unlocked) as unlocked_scenes,
                        SUM(viewed) as viewed_scenes,
                        MAX(is_current) as is_current_area
                 FROM {$this->wpdb->prefix}game_user_areas 
                 WHERE user_id = %d 
                 GROUP BY area_id 
                 ORDER BY area_id ASC",
                $user_id
            ),
            ARRAY_A
        );
    }

    /**
     * Pobiera tylko odblokowane obszary dla użytkownika
     */
    public function getUserUnlockedAreas($user_id)
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT area_id, COUNT(*) as scene_count, 
                        SUM(unlocked) as unlocked_scenes,
                        SUM(viewed) as viewed_scenes
                 FROM {$this->wpdb->prefix}game_user_areas 
                 WHERE user_id = %d AND unlocked = 1 
                 GROUP BY area_id 
                 ORDER BY area_id ASC",
                $user_id
            ),
            ARRAY_A
        );
    }

    /**
     * Pobiera konkretny obszar użytkownika z wszystkimi scenami
     */
    public function getUserArea($user_id, $area_id)
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}game_user_areas 
                 WHERE user_id = %d AND area_id = %d 
                 ORDER BY scene_id ASC",
                $user_id,
                $area_id
            ),
            ARRAY_A
        );
    }

    /**
     * Pobiera konkretną scenę użytkownika
     */
    public function getUserScene($user_id, $area_id, $scene_id)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}game_user_areas 
                 WHERE user_id = %d AND area_id = %d AND scene_id = %s",
                $user_id,
                $area_id,
                $scene_id
            ),
            ARRAY_A
        );
    }

    /**
     * Ustawia status odblokowania wszystkich scen w obszarze dla użytkownika
     */
    public function setAreaUnlocked($user_id, $area_id, $unlocked = true)
    {
        return $this->wpdb->update(
            $this->wpdb->prefix . 'game_user_areas',
            [
                'unlocked' => $unlocked ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'user_id' => $user_id,
                'area_id' => $area_id
            ]
        ) !== false;
    }

    /**
     * Odblokuje konkretną scenę dla użytkownika
     */
    public function unlockSceneForUser($user_id, $area_id, $scene_id)
    {
        // Sprawdź czy scena już istnieje
        $scene = $this->getUserScene($user_id, $area_id, $scene_id);

        if ($scene) {
            // Aktualizuj istniejącą scenę
            return $this->wpdb->update(
                $this->wpdb->prefix . 'game_user_areas',
                [
                    'unlocked' => 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'user_id' => $user_id,
                    'area_id' => $area_id,
                    'scene_id' => $scene_id
                ]
            ) !== false;
        } else {
            // Utwórz nowy wpis dla sceny
            return $this->wpdb->insert(
                $this->wpdb->prefix . 'game_user_areas',
                [
                    'user_id' => $user_id,
                    'area_id' => $area_id,
                    'scene_id' => $scene_id,
                    'unlocked' => 1,
                    'viewed' => 0,
                    'is_current' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ) !== false;
        }
    }
    /**
     * Oznacza scenę jako oglądaną przez użytkownika
     */
    public function markSceneAsViewed($user_id, $area_id, $scene_id)
    {
        // Sprawdź czy scena już istnieje
        $scene = $this->getUserScene($user_id, $area_id, $scene_id);

        if ($scene) {
            // Aktualizuj istniejącą scenę
            return $this->wpdb->update(
                $this->wpdb->prefix . 'game_user_areas',
                [
                    'viewed' => 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'user_id' => $user_id,
                    'area_id' => $area_id,
                    'scene_id' => $scene_id
                ]
            ) !== false;
        } else {
            // Utwórz nowy wpis dla sceny z oznaczeniem jako oglądana
            return $this->wpdb->insert(
                $this->wpdb->prefix . 'game_user_areas',
                [
                    'user_id' => $user_id,
                    'area_id' => $area_id,
                    'scene_id' => $scene_id,
                    'unlocked' => 1, // Jeśli oglądana, to też odblokowana
                    'viewed' => 1,
                    'is_current' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ) !== false;
        }
    }

    /**
     * Oznacza wszystkie sceny w obszarze jako oglądane przez użytkownika
     */
    public function markAreaAsViewed($user_id, $area_id)
    {
        return $this->wpdb->update(
            $this->wpdb->prefix . 'game_user_areas',
            [
                'viewed' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'user_id' => $user_id,
                'area_id' => $area_id
            ]
        ) !== false;
    }

    /**
     * Sprawdza czy istnieje połączenie między graczem a obszarem
     * Jeśli podana jest scena, sprawdza konkretną scenę
     */
    public function getAreaConnection($user_id, $area_id, $scene_id = null)
    {
        if ($scene_id) {
            // Sprawdź konkretną scenę
            $result = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}game_user_areas 
                    WHERE user_id = %d AND area_id = %d AND scene_id = %s",
                    $user_id,
                    $area_id,
                    $scene_id
                )
            );
        } else {
            // Sprawdź jakąkolwiek scenę w tym obszarze (pierwszą znalezioną)
            $result = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}game_user_areas 
                    WHERE user_id = %d AND area_id = %d LIMIT 1",
                    $user_id,
                    $area_id
                )
            );
        }

        return $result ? $result : false;
    }

    /**
     * Aktualizuje status odblokowania obszaru dla użytkownika
     * (aktualizuje wszystkie sceny tego obszaru na raz)
     */
    public function unlockArea($user_id, $area_id, $unlock = true)
    {
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'game_user_areas',
            [
                'unlocked' => $unlock ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'user_id' => $user_id,
                'area_id' => $area_id
            ]
        );

        return $result !== false;
    }

    /**
     * Sprawdza czy obszar jest odblokowany dla użytkownika
     */
    public function isAreaUnlocked($user_id, $area_id)
    {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}game_user_areas 
                WHERE user_id = %d AND area_id = %d AND unlocked = 1",
                $user_id,
                $area_id
            )
        );

        return $count > 0;
    }

    /**
     * Sprawdza czy scena jest odblokowana dla użytkownika
     */
    public function isSceneUnlocked($user_id, $area_id, $scene_id)
    {
        $scene = $this->getUserScene($user_id, $area_id, $scene_id);
        return $scene && $scene['unlocked'] == 1;
    }

    /**
     * Sprawdza czy scena została już oglądana przez użytkownika
     */
    public function isSceneViewed($user_id, $area_id, $scene_id)
    {
        $scene = $this->getUserScene($user_id, $area_id, $scene_id);
        return $scene && $scene['viewed'] == 1;
    }

    /**
     * Odblokowanie obszaru docelowego (unlocked_area_id) dla użytkownika
     */
    public function unlockDestinationArea($user_id, $source_area_id, $destination_area_id)
    {
        // Aktualizujemy wpisy dla obszaru źródłowego - ustawiamy unlocked_area_id
        $result1 = $this->wpdb->update(
            $this->wpdb->prefix . 'game_user_areas',
            [
                'unlocked_area_id' => $destination_area_id,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'user_id' => $user_id,
                'area_id' => $source_area_id
            ]
        );

        // Odblokowujemy obszar docelowy
        $result2 = $this->unlockArea($user_id, $destination_area_id, true);

        return ($result1 !== false && $result2 !== false);
    }

    /**
     * Pobieranie statystyk obszarów użytkownika
     */
    public function getUserAreasStats($user_id)
    {
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(DISTINCT area_id) as total_areas,
                    COUNT(DISTINCT CASE WHEN unlocked = 1 THEN area_id END) as unlocked_areas,
                    COUNT(DISTINCT CASE WHEN viewed = 1 THEN area_id END) as viewed_areas,
                    COUNT(*) as total_scenes,
                    SUM(unlocked) as unlocked_scenes,
                    SUM(viewed) as viewed_scenes
                 FROM {$this->wpdb->prefix}game_user_areas 
                 WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );

        return [
            'total_areas' => (int) $stats['total_areas'],
            'unlocked_areas' => (int) $stats['unlocked_areas'],
            'viewed_areas' => (int) $stats['viewed_areas'],
            'locked_areas' => (int) $stats['total_areas'] - (int) $stats['unlocked_areas'],
            'total_scenes' => (int) $stats['total_scenes'],
            'unlocked_scenes' => (int) $stats['unlocked_scenes'],
            'viewed_scenes' => (int) $stats['viewed_scenes']
        ];
    }

    /**
     * Pobiera aktualną lokalizację gracza
     */
    public function getCurrentLocation($user_id)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}game_user_areas 
                WHERE user_id = %d AND is_current = 1 
                LIMIT 1",
                $user_id
            )
        );
    }

    /**
     * Aktualizuje aktualną lokalizację gracza
     */
    public function setCurrentLocation($user_id, $area_id, $scene_id)
    {
        // Najpierw oznacz wszystkie lokalizacje tego użytkownika jako nieaktualne
        $this->wpdb->update(
            $this->wpdb->prefix . 'game_user_areas',
            ['is_current' => 0],
            ['user_id' => $user_id]
        );

        // Sprawdź czy scena już istnieje
        $scene = $this->getUserScene($user_id, $area_id, $scene_id);

        if ($scene) {
            // Aktualizuj istniejącą scenę jako aktualną
            return $this->wpdb->update(
                $this->wpdb->prefix . 'game_user_areas',
                [
                    'is_current' => 1,
                    'viewed' => 1, // Oznacz jako oglądaną
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'user_id' => $user_id,
                    'area_id' => $area_id,
                    'scene_id' => $scene_id
                ]
            ) !== false;
        } else {
            // Utwórz nowy wpis i ustaw jako aktualny
            return $this->wpdb->insert(
                $this->wpdb->prefix . 'game_user_areas',
                [
                    'user_id' => $user_id,
                    'area_id' => $area_id,
                    'scene_id' => $scene_id,
                    'unlocked' => 1,
                    'viewed' => 1,
                    'is_current' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ) !== false;
        }
    }

    /**
     * Pobiera statystyki obszarów i scen z bazy danych
     */
    public function getAreasStats()
    {
        $areas_count = $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT area_id) FROM {$this->wpdb->prefix}game_user_areas"
        );

        $users_count = $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$this->wpdb->prefix}game_user_areas"
        );

        $total_connections = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}game_user_areas"
        );

        $unlocked_connections = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}game_user_areas WHERE unlocked = 1"
        );

        $viewed_connections = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}game_user_areas WHERE viewed = 1"
        );

        return [
            'areas_in_db' => (int) $areas_count,
            'users_with_areas' => (int) $users_count,
            'total_connections' => (int) $total_connections,
            'unlocked_connections' => (int) $unlocked_connections,
            'viewed_connections' => (int) $viewed_connections
        ];
    }

    /**
     * Pobiera obszar z jego scenami dla użytkownika (format do wyświetlania)
     */
    public function getUserAreaWithScenes($user_id, $area_id)
    {
        $scenes = $this->getUserArea($user_id, $area_id);

        if (!$scenes) {
            return null;
        }

        // Grupuj sceny według statusu
        $area_data = [
            'area_id' => $area_id,
            'total_scenes' => count($scenes),
            'unlocked_scenes' => 0,
            'viewed_scenes' => 0,
            'current_scene' => null,
            'scenes' => []
        ];

        foreach ($scenes as $scene) {
            if ($scene['unlocked']) {
                $area_data['unlocked_scenes']++;
            }
            if ($scene['viewed']) {
                $area_data['viewed_scenes']++;
            }
            if ($scene['is_current']) {
                $area_data['current_scene'] = $scene['scene_id'];
            }

            $area_data['scenes'][$scene['scene_id']] = $scene;
        }

        return $area_data;
    }

    /**
     * Sprawdza czy użytkownik może odwiedzić scenę
     */
    public function canUserAccessScene($user_id, $area_id, $scene_id)
    {
        $scene = $this->getUserScene($user_id, $area_id, $scene_id);
        return $scene && $scene['unlocked'] == 1;
    }

    /**
     * Pobiera listę dostępnych obszarów dla użytkownika (tylko odblokowane)
     */
    public function getAvailableAreasForUser($user_id)
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT DISTINCT area_id, 
                        COUNT(*) as total_scenes,
                        SUM(unlocked) as unlocked_scenes,
                        SUM(viewed) as viewed_scenes,
                        MAX(is_current) as is_current_area
                 FROM {$this->wpdb->prefix}game_user_areas 
                 WHERE user_id = %d AND unlocked = 1
                 GROUP BY area_id 
                 HAVING unlocked_scenes > 0
                 ORDER BY area_id ASC",
                $user_id
            ),
            ARRAY_A
        );
    }

    /**
     * Przełącza użytkownika do innej sceny (zmienia current location)
     */
    public function moveUserToScene($user_id, $area_id, $scene_id)
    {
        // Sprawdź czy scena jest dostępna
        if (!$this->canUserAccessScene($user_id, $area_id, $scene_id)) {
            return false;
        }

        // Przenieś użytkownika
        return $this->setCurrentLocation($user_id, $area_id, $scene_id);
    }

    /**
     * Odblokuje następną scenę w obszarze (automatyczne przejście)
     */
    public function unlockNextSceneInArea($user_id, $area_id, $current_scene_id)
    {
        // Pobierz wszystkie sceny obszaru dla użytkownika
        $scenes = $this->getUserArea($user_id, $area_id);

        if (!$scenes) {
            return false;
        }

        // Znajdź aktualną scenę i następną
        $scene_ids = array_column($scenes, 'scene_id');
        $current_index = array_search($current_scene_id, $scene_ids);

        if ($current_index === false || $current_index >= count($scene_ids) - 1) {
            return false; // Nie ma następnej sceny
        }

        $next_scene_id = $scene_ids[$current_index + 1];
        return $this->unlockSceneForUser($user_id, $area_id, $next_scene_id);
    }
}
