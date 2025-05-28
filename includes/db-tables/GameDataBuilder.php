<?php

/**
 * Builder do budowania struktur danych z ACF
 */
class GameDataBuilder
{

    private $wpdb;
    private $dbManager;
    private $userRepo;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->dbManager = GameDatabaseManager::getInstance();
        $this->userRepo = new GameUserRepository();
    }

    /**
     * Buduje rejony i sceny z CPT terenów
     */
    public function buildAreasFromCPT()
    {
        // Pobieramy posty typu 'tereny' i 'events'
        $locations = get_posts([
            'post_type' => ['tereny', 'events'],
            'post_status' => 'publish',
            'numberposts' => -1
        ]);

        $builtAreas = [];

        foreach ($locations as $location) {
            $scenes = get_field('scenes', $location->ID);

            if (!empty($scenes)) {
                foreach ($scenes as $scene) {
                    $sceneId = $scene['id_sceny'] ?? '';

                    if (!empty($sceneId)) {
                        $builtAreas[] = [
                            'area_id' => $location->ID,
                            'area_title' => $location->post_title,
                            'scene_id' => $sceneId,
                            'area_type' => $location->post_type
                        ];
                    }
                }
            }
        }

        return $builtAreas;
    }

    /**
     * Tworzy checkboxy dla rejonów w formularzu gracza
     */
    public function renderAreaCheckboxes($userId)
    {
        $allAreas = $this->buildAreasFromCPT();
        $playerAreas = $this->userRepo->getPlayerAreas($userId);

        // Tworzymy mapę odblokowanych rejonów gracza
        $unlockedMap = [];
        foreach ($playerAreas as $area) {
            $key = $area['area_id'] . '_' . $area['scene_id'];
            $unlockedMap[$key] = $area['is_unlocked'];
        }

        if (empty($allAreas)) {
            echo '<p>Brak dostępnych rejonów do odblokowania.</p>';
            return;
        }

        echo '<div class="area-checkboxes">';
        echo '<h4>Dostępne rejony:</h4>';

        $currentAreaId = null;

        foreach ($allAreas as $area) {
            // Grupa nowego rejonu
            if ($currentAreaId !== $area['area_id']) {
                if ($currentAreaId !== null) {
                    echo '</fieldset>';
                }
                echo '<fieldset style="margin: 10px 0; padding: 10px; border: 1px solid #ddd;">';
                echo '<legend><strong>' . esc_html($area['area_title']) . '</strong> (ID: ' . $area['area_id'] . ')</legend>';
                $currentAreaId = $area['area_id'];
            }

            $key = $area['area_id'] . '_' . $area['scene_id'];
            $checked = isset($unlockedMap[$key]) && $unlockedMap[$key] ? 'checked' : '';
            $inputName = "areas[{$area['area_id']}][{$area['scene_id']}]";

            echo '<label style="display: block; margin: 5px 0;">';
            echo '<input type="checkbox" name="' . esc_attr($inputName) . '" value="1" ' . $checked . '> ';
            echo esc_html($area['scene_id']);
            echo '</label>';
        }

        if ($currentAreaId !== null) {
            echo '</fieldset>';
        }

        echo '</div>';
    }

    /**
     * Buduje listę przedmiotów z CPT items
     */
    public function buildItemsFromCPT()
    {
        $items = get_posts([
            'post_type' => 'items', // dostosuj do nazwy swojego CPT
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $builtItems = [];

        foreach ($items as $item) {
            // Pobieramy dodatkowe dane z ACF jeśli potrzebne
            $itemType = get_field('item_type', $item->ID);
            $itemRarity = get_field('item_rarity', $item->ID);
            $itemValue = get_field('item_value', $item->ID);

            $builtItems[] = [
                'id' => $item->ID,
                'title' => $item->post_title,
                'type' => $itemType,
                'rarity' => $itemRarity,
                'value' => $itemValue
            ];
        }

        return $builtItems;
    }

    /**
     * Renderuje interface do dodawania przedmiotów
     */
    public function renderItemSelector($userId)
    {
        $allItems = $this->buildItemsFromCPT();
        $playerItems = $this->userRepo->getPlayerItems($userId);

        // Mapa przedmiotów gracza
        $playerItemsMap = [];
        foreach ($playerItems as $item) {
            $playerItemsMap[$item['item_id']] = $item['quantity'];
        }

        if (empty($allItems)) {
            echo '<p>Brak dostępnych przedmiotów.</p>';
            return;
        }

        echo '<div class="item-selector">';
        echo '<h4>Ekwipunek gracza:</h4>';
        echo '<table class="wp-list-table widefat">';
        echo '<thead><tr><th>Przedmiot</th><th>Typ</th><th>Obecna ilość</th><th>Nowa ilość</th></tr></thead>';
        echo '<tbody>';

        foreach ($allItems as $item) {
            $currentQuantity = $playerItemsMap[$item['id']] ?? 0;
            $inputName = "items[{$item['id']}]";

            echo '<tr>';
            echo '<td><strong>' . esc_html($item['title']) . '</strong></td>';
            echo '<td>' . esc_html($item['type'] ?? 'Brak') . '</td>';
            echo '<td>' . $currentQuantity . '</td>';
            echo '<td>';
            echo '<input type="number" name="' . esc_attr($inputName) . '" value="' . $currentQuantity . '" min="0" max="999" style="width: 80px;">';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Buduje NPC z CPT
     */
    public function buildNPCFromCPT()
    {
        $npcs = get_posts([
            'post_type' => 'npc', // dostosuj do nazwy swojego CPT
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $builtNPCs = [];

        foreach ($npcs as $npc) {
            // Pobieramy dodatkowe dane z ACF
            $npcType = get_field('npc_type', $npc->ID);
            $npcFaction = get_field('npc_faction', $npc->ID);
            $npcLocation = get_field('npc_location', $npc->ID);

            $builtNPCs[] = [
                'id' => $npc->ID,
                'title' => $npc->post_title,
                'type' => $npcType,
                'faction' => $npcFaction,
                'location' => $npcLocation
            ];
        }

        return $builtNPCs;
    }

    /**
     * Renderuje interface do zarządzania relacjami z NPC
     */
    public function renderNPCRelations($userId)
    {
        $allNPCs = $this->buildNPCFromCPT();
        $playerRelations = $this->userRepo->getPlayerRelations($userId);

        // Mapa relacji gracza
        $relationsMap = [];
        foreach ($playerRelations as $relation) {
            $relationsMap[$relation['npc_id']] = $relation;
        }

        if (empty($allNPCs)) {
            echo '<p>Brak dostępnych NPC.</p>';
            return;
        }

        echo '<div class="npc-relations">';
        echo '<h4>Relacje z NPC:</h4>';
        echo '<table class="wp-list-table widefat">';
        echo '<thead><tr><th>NPC</th><th>Typ</th><th>Relacja (-50 do +50)</th><th>Poznany</th><th>W/P/R</th></tr></thead>';
        echo '<tbody>';

        foreach ($allNPCs as $npc) {
            $relation = $relationsMap[$npc['id']] ?? null;
            $relationValue = $relation ? $relation['relation_value'] : 0;
            $isKnown = $relation ? $relation['is_known'] : false;
            $fights = $relation ? "{$relation['fights_won']}/{$relation['fights_lost']}/{$relation['fights_draw']}" : "0/0/0";

            echo '<tr>';
            echo '<td><strong>' . esc_html($npc['title']) . '</strong></td>';
            echo '<td>' . esc_html($npc['type'] ?? 'Brak') . '</td>';
            echo '<td>';
            echo '<input type="number" name="npc_relations[' . $npc['id'] . '][relation_value]" value="' . $relationValue . '" min="-50" max="50" style="width: 80px;">';
            echo '</td>';
            echo '<td>';
            echo '<input type="checkbox" name="npc_relations[' . $npc['id'] . '][is_known]" value="1" ' . ($isKnown ? 'checked' : '') . '>';
            echo '</td>';
            echo '<td>' . $fights . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Aktualizuje rejony gracza na podstawie formularza
     */
    public function updatePlayerAreas($userId, $areasData)
    {
        if (empty($areasData)) {
            return ['success' => true, 'message' => 'Brak zmian w rejonach'];
        }

        $results = [];

        foreach ($areasData as $areaId => $scenes) {
            foreach ($scenes as $sceneId => $unlocked) {
                if ($unlocked) {
                    $result = $this->userRepo->unlockArea($userId, intval($areaId), $sceneId);
                    $results[] = $result;
                } else {
                    // Opcjonalnie: blokowanie rejonu (usuwanie wpisu lub ustawianie is_unlocked = 0)
                    $this->lockArea($userId, intval($areaId), $sceneId);
                }
            }
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Blokuje rejon (pomocnicza metoda)
     */
    private function lockArea($userId, $areaId, $sceneId)
    {
        $tableName = $this->dbManager->getTableName('game_user_areas');

        return $this->wpdb->update(
            $tableName,
            ['is_unlocked' => 0],
            [
                'user_id' => $userId,
                'area_id' => $areaId,
                'scene_id' => $sceneId
            ],
            ['%d'],
            ['%d', '%d', '%s']
        );
    }

    /**
     * Aktualizuje przedmioty gracza
     */
    public function updatePlayerItems($userId, $itemsData)
    {
        if (empty($itemsData)) {
            return ['success' => true, 'message' => 'Brak zmian w przedmiotach'];
        }

        $tableName = $this->dbManager->getTableName('game_user_items');
        $results = [];

        foreach ($itemsData as $itemId => $newQuantity) {
            $itemId = intval($itemId);
            $newQuantity = intval($newQuantity);

            // Sprawdzamy obecny stan
            $existing = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM `$tableName` WHERE user_id = %d AND item_id = %d",
                $userId,
                $itemId
            ));

            if ($existing) {
                if ($newQuantity > 0) {
                    // Aktualizujemy ilość
                    $result = $this->wpdb->update(
                        $tableName,
                        ['quantity' => $newQuantity],
                        ['user_id' => $userId, 'item_id' => $itemId],
                        ['%d'],
                        ['%d', '%d']
                    );
                } else {
                    // Usuwamy przedmiot (ilość 0)
                    $result = $this->wpdb->delete(
                        $tableName,
                        ['user_id' => $userId, 'item_id' => $itemId],
                        ['%d', '%d']
                    );
                }
            } else {
                if ($newQuantity > 0) {
                    // Dodajemy nowy przedmiot
                    $result = $this->wpdb->insert(
                        $tableName,
                        [
                            'user_id' => $userId,
                            'item_id' => $itemId,
                            'quantity' => $newQuantity
                        ],
                        ['%d', '%d', '%d']
                    );
                }
                // Jeśli newQuantity = 0 i nie ma przedmiotu, nic nie robimy
            }

            $results[$itemId] = $result !== false;
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Aktualizuje relacje z NPC
     */
    public function updatePlayerNPCRelations($userId, $relationsData)
    {
        if (empty($relationsData)) {
            return ['success' => true, 'message' => 'Brak zmian w relacjach'];
        }

        $tableName = $this->dbManager->getTableName('game_user_relations');
        $results = [];

        foreach ($relationsData as $npcId => $relationData) {
            $npcId = intval($npcId);
            $relationValue = intval($relationData['relation_value'] ?? 0);
            $isKnown = isset($relationData['is_known']) ? 1 : 0;

            // Sprawdzamy czy relacja istnieje
            $existing = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM `$tableName` WHERE user_id = %d AND npc_id = %d",
                $userId,
                $npcId
            ));

            if ($existing) {
                // Aktualizujemy
                $result = $this->wpdb->update(
                    $tableName,
                    [
                        'relation_value' => $relationValue,
                        'is_known' => $isKnown
                    ],
                    ['user_id' => $userId, 'npc_id' => $npcId],
                    ['%d', '%d'],
                    ['%d', '%d']
                );
            } else {
                // Tworzymy nową relację
                $result = $this->wpdb->insert(
                    $tableName,
                    [
                        'user_id' => $userId,
                        'npc_id' => $npcId,
                        'relation_value' => $relationValue,
                        'is_known' => $isKnown,
                        'fights_won' => 0,
                        'fights_lost' => 0,
                        'fights_draw' => 0
                    ],
                    ['%d', '%d', '%d', '%d', '%d', '%d', '%d']
                );
            }

            $results[$npcId] = $result !== false;
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Tworzy wszystkie relacje NPC dla wszystkich graczy
     */
    public function buildAllNPCRelations()
    {
        $allNPCs = $this->buildNPCFromCPT();
        $users = get_users();

        $created = 0;
        $skipped = 0;

        foreach ($users as $user) {
            // Sprawdzamy czy gracz ma dane gry
            if (!$this->userRepo->playerExists($user->ID)) {
                continue;
            }

            foreach ($allNPCs as $npc) {
                $result = $this->userRepo->addNpcRelation($user->ID, $npc['id'], 0, false);
                if ($result['success']) {
                    $created++;
                } else {
                    $skipped++;
                }
            }
        }

        return [
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'total_npcs' => count($allNPCs),
            'total_users' => count($users)
        ];
    }
}
