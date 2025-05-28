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
     * Pobiera wszystkie obszary dla użytkownika
     */
    public function getUserAreas($user_id)
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}game_user_areas WHERE user_id = %d ORDER BY area_id ASC",
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
                "SELECT * FROM {$this->wpdb->prefix}game_user_areas WHERE user_id = %d AND unlocked = 1 ORDER BY area_id ASC",
                $user_id
            ),
            ARRAY_A
        );
    }

    /**
     * Pobiera konkretny obszar użytkownika
     */
    public function getUserArea($user_id, $area_id)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}game_user_areas WHERE user_id = %d AND area_id = %d",
                $user_id,
                $area_id
            ),
            ARRAY_A
        );
    }

    /**
     * Ustawia status odblokowania obszaru dla użytkownika
     */
    public function setAreaUnlocked($user_id, $area_id, $unlocked = true)
    {
        return $this->wpdb->update(
            $this->wpdb->prefix . 'game_user_areas',
            [
                'unlocked' => $unlocked ? 1 : 0,
                'updated_at' => current_time('mysql')
            ],
            [
                'user_id' => $user_id,
                'area_id' => $area_id
            ]
        ) !== false;
    }

    /**
     * Dodaje scenę do listy odblokowanych scen użytkownika dla danego obszaru
     */
    public function unlockSceneForUser($user_id, $area_id, $scene_id)
    {
        $area = $this->getUserArea($user_id, $area_id);

        if ($area) {
            $unlocked_scenes = json_decode($area['unlocked_scenes'] ?? '[]', true) ?: [];

            if (!in_array($scene_id, $unlocked_scenes)) {
                $unlocked_scenes[] = $scene_id;

                return $this->wpdb->update(
                    $this->wpdb->prefix . 'game_user_areas',
                    [
                        'unlocked_scenes' => json_encode($unlocked_scenes),
                        'updated_at' => current_time('mysql')
                    ],
                    [
                        'user_id' => $user_id,
                        'area_id' => $area_id
                    ]
                ) !== false;
            }

            return true; // Scena jest już odblokowana
        }

        return false;
    }

    /**
     * Dodaje scenę do listy oglądanych scen użytkownika dla danego obszaru
     */
    public function markSceneAsViewed($user_id, $area_id, $scene_id)
    {
        $area = $this->getUserArea($user_id, $area_id);

        if ($area) {
            $viewed_scenes = json_decode($area['viewed_scenes'] ?? '[]', true) ?: [];

            if (!in_array($scene_id, $viewed_scenes)) {
                $viewed_scenes[] = $scene_id;

                return $this->wpdb->update(
                    $this->wpdb->prefix . 'game_user_areas',
                    [
                        'viewed_scenes' => json_encode($viewed_scenes),
                        'updated_at' => current_time('mysql')
                    ],
                    [
                        'user_id' => $user_id,
                        'area_id' => $area_id
                    ]
                ) !== false;
            }

            return true; // Scena jest już oznaczona jako oglądana
        }

        return false;
    }

    /**
     * Oznacza obszar jako oglądany przez użytkownika
     */
    public function markAreaAsViewed($user_id, $area_id)
    {
        return $this->wpdb->update(
            $this->wpdb->prefix . 'game_user_areas',
            [
                'viewed_area' => 1,
                'updated_at' => current_time('mysql')
            ],
            [
                'user_id' => $user_id,
                'area_id' => $area_id
            ]
        ) !== false;
    }

    /**
     * Sprawdza czy scena jest odblokowana dla użytkownika
     */
    public function isSceneUnlocked($user_id, $area_id, $scene_id)
    {
        $area = $this->getUserArea($user_id, $area_id);

        if ($area && $area['unlocked'] == 1) {
            $unlocked_scenes = json_decode($area['unlocked_scenes'] ?? '[]', true) ?: [];
            return in_array($scene_id, $unlocked_scenes);
        }

        return false;
    }

    /**
     * Sprawdza czy scena została już oglądana przez użytkownika
     */
    public function isSceneViewed($user_id, $area_id, $scene_id)
    {
        $area = $this->getUserArea($user_id, $area_id);

        if ($area) {
            $viewed_scenes = json_decode($area['viewed_scenes'] ?? '[]', true) ?: [];
            return in_array($scene_id, $viewed_scenes);
        }

        return false;
    }

    /**
     * Odblokowanie obszaru docelowego (unlocked_area_id) dla użytkownika
     */
    public function unlockDestinationArea($user_id, $source_area_id, $destination_area_id)
    {
        // Aktualizujemy wpis dla obszaru źródłowego
        $result1 = $this->wpdb->update(
            $this->wpdb->prefix . 'game_user_areas',
            [
                'unlocked_area_id' => $destination_area_id,
                'updated_at' => current_time('mysql')
            ],
            [
                'user_id' => $user_id,
                'area_id' => $source_area_id
            ]
        );

        // Odblokowujemy obszar docelowy
        $result2 = $this->setAreaUnlocked($user_id, $destination_area_id, true);

        return ($result1 !== false && $result2 !== false);
    }

    /**
     * Pobieranie statystyk obszarów użytkownika
     */
    public function getUserAreasStats($user_id)
    {
        $total_areas = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}game_user_areas WHERE user_id = %d",
                $user_id
            )
        );

        $unlocked_areas = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}game_user_areas WHERE user_id = %d AND unlocked = 1",
                $user_id
            )
        );

        $viewed_areas = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}game_user_areas WHERE user_id = %d AND viewed_area = 1",
                $user_id
            )
        );

        return [
            'total' => (int) $total_areas,
            'unlocked' => (int) $unlocked_areas,
            'viewed' => (int) $viewed_areas,
            'locked' => (int) $total_areas - (int) $unlocked_areas
        ];
    }
}
