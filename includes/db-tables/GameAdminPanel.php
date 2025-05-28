<?php

/**
 * Panel administracyjny do zarządzania grą
 */
class GameAdminPanel
{

    private $dbManager;
    private $userRepo;
    private $missionManager;
    private $deltaManager;
    private $dataBuilder;

    public function __construct()
    {
        $this->dbManager = GameDatabaseManager::getInstance();
        $this->userRepo = new GameUserRepository();
        $this->deltaManager = new GameDeltaManager();
        $this->dataBuilder = new GameDataBuilder();

        add_action('admin_menu', [$this, 'addAdminMenus']);
        add_action('admin_post_game_create_tables', [$this, 'handleCreateTables']);
        add_action('admin_post_game_recreate_tables', [$this, 'handleRecreateTables']);
        add_action('admin_post_game_build_missions', [$this, 'handleBuildMissions']);
        add_action('admin_post_game_build_npc_relations', [$this, 'handleBuildNpcRelations']);
        add_action('admin_post_game_build_areas', [$this, 'handleBuildAreas']);
        add_action('admin_post_game_update_player', [$this, 'handleUpdatePlayer']);
        add_action('admin_post_game_update_player_extended', [$this, 'handleUpdatePlayerExtended']);
    }

    /**
     * Dodaje menu w panelu admina
     */
    public function addAdminMenus()
    {
        add_menu_page(
            'Zarządzanie Grą',
            'Gra RPG',
            'manage_options',
            'game-management',
            [$this, 'renderMainPage'],
            'dashicons-games',
            30
        );

        add_submenu_page(
            'game-management',
            'Baza danych',
            'Baza danych',
            'manage_options',
            'game-database',
            [$this, 'renderDatabasePage']
        );

        add_submenu_page(
            'game-management',
            'Gracze',
            'Gracze',
            'manage_options',
            'game-players',
            [$this, 'renderPlayersPage']
        );

        add_submenu_page(
            'game-management',
            'Budowanie danych',
            'Budowanie danych',
            'manage_options',
            'game-builder',
            [$this, 'renderBuilderPage']
        );
    }

    /**
     * Główna strona panelu
     */
    public function renderMainPage()
    {
        // Przekaż dane do szablonu
        $systemStatus = $this->getSystemStatusData(); // Załóżmy, że istnieje taka metoda
        $quickActions = [
            [
                'url' => admin_url('admin.php?page=game-database'),
                'text' => 'Zarządzaj bazą danych',
                'class' => 'button-primary'
            ],
            [
                'url' => admin_url('admin.php?page=game-players'),
                'text' => 'Przeglądaj graczy',
                'class' => 'button-secondary'
            ],
            [
                'url' => admin_url('admin.php?page=game-builder'),
                'text' => 'Zbuduj dane z ACF',
                'class' => 'button-secondary'
            ]
        ];

        include __DIR__ . '/templates/main-page.php';
    }

    /**
     * Pobiera dane statusu systemu
     */
    private function getSystemStatusData()
    {
        $allTablesExist = true;
        $existingTables = 0;
        $totalTables = count(GameDatabaseManager::TABLES);

        foreach (GameDatabaseManager::TABLES as $tableName) {
            if ($this->dbManager->tableExists($tableName)) {
                $existingTables++;
            } else {
                $allTablesExist = false;
            }
        }

        return [
            'allTablesExist' => $allTablesExist,
            'existingTables' => $existingTables,
            'totalTables' => $totalTables,
            'statusColor' => $allTablesExist ? 'green' : 'orange'
        ];
    }

    /**
     * Strona zarządzania bazą danych
     */
    public function renderDatabasePage()
    {
        $tableData = [];
        $tableDescriptions = [
            'game_users' => 'Główne dane graczy',
            'game_user_data' => 'Wszystkie dane gracza (statystyki, umiejętności, postęp, witalność, historia)',
            'game_user_items' => 'Ekwipunek graczy',
            'game_user_areas' => 'Dostępne rejony',
            'game_user_fight_tokens' => 'Tokeny walk',
            'game_user_relations' => 'Relacje z NPC',
            'game_user_missions' => 'Misje graczy',
            'game_user_mission_tasks' => 'Zadania misji'
        ];

        foreach (GameDatabaseManager::TABLES as $tableName) {
            $exists = $this->dbManager->tableExists($tableName);
            $status = $exists ? '<span style="color: green;">✓ Istnieje</span>' : '<span style="color: red;">✗ Brak</span>';
            $description = $tableDescriptions[$tableName] ?? '';
            $tableData[] = [
                'name' => $tableName,
                'status' => $status,
                'description' => $description
            ];
        }

        include __DIR__ . '/templates/database-page.php';
    }

    /**
     * Strona graczy
     */
    public function renderPlayersPage()
    {
        $selectedUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

        // Przekaż dane do szablonu
        $templateData = [
            'selectedUserId' => $selectedUserId,
            'userRepo' => $this->userRepo, // Przekazujemy repozytorium użytkownika
            'gameAdminPanel' => $this,     // Przekazujemy instancję GameAdminPanel
            'dataBuilder' => $this->dataBuilder, // Przekazujemy instancję GameDataBuilder
            // Dodajemy puste wartości, aby uniknąć błędów w szablonach, jeśli nie są jeszcze ustawione
            'users' => [],
            'totalUsers' => 0,
            'totalPages' => 1,
            'currentPage' => 1,
            'search' => '',
            'pageSlug' => 'game-players',
            'playerExists' => false,
            'userData' => null,
            'user' => null,
            'userId' => 0,
        ];

        // Jeśli edytujemy konkretnego gracza, pobierz jego dane
        if ($selectedUserId > 0) {
            $this->renderPlayerEditor($selectedUserId, $templateData);
        } else {
            // Jeśli wyświetlamy listę graczy, pobierz dane dla tabeli
            $this->renderPlayersTable($templateData);
        }

        extract($templateData);

        // Załaduj główny szablon strony graczy, który zdecyduje, co wyświetlić
        include __DIR__ . '/templates/players-page.php';
    }

    /**
     * Renderuje edytor gracza
     */
    public function renderPlayerEditor($userId, &$templateData = [])
    {
        $playerExists = $this->userRepo->playerExists($userId);
        $userData = $playerExists ? $this->userRepo->getPlayerData($userId) : null;
        $user = get_userdata($userId);

        // Przygotuj dane dla szablonu
        $templateData['playerExists'] = $playerExists;
        $templateData['userData'] = $userData;
        $templateData['user'] = $user;
        $templateData['userId'] = $userId;
        $templateData['gameAdminPanel'] = $this; // Upewnij się, że $gameAdminPanel jest dostępne w player-editor.php
    }

    /**
     * Renderuje tabelę graczy z paginacją
     */
    public function renderPlayersTable(&$templateData = [])
    {
        $currentPage = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $perPage = 20; // Liczba użytkowników na stronę

        $args = [
            'number' => $perPage,
            'paged' => $currentPage,
            'orderby' => 'display_name',
            'order' => 'ASC',
        ];

        if (!empty($search)) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        $users = get_users($args);

        $total_args = ['orderby' => 'display_name', 'count_total' => true];
        if (!empty($search)) {
            $total_args['search'] = '*' . $search . '*';
            $total_args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }
        // Użyj 'count_total' => true i pobierz wynik z zapytania, zamiast pobierać wszystkich użytkowników
        $user_query = new WP_User_Query($total_args);
        $total_users = $user_query->get_total();

        $totalPages = ceil($total_users / $perPage);

        // Przygotuj dane dla szablonu
        $templateData['users'] = $users;
        $templateData['totalUsers'] = $total_users;
        $templateData['totalPages'] = $totalPages > 0 ? $totalPages : 1; // Unikaj dzielenia przez zero i upewnij się, że jest co najmniej 1 strona
        $templateData['currentPage'] = $currentPage;
        $templateData['search'] = $search;
        $templateData['pageSlug'] = 'game-players'; // Slug strony dla paginacji
        $templateData['userRepo'] = $this->userRepo; // Dodajemy userRepo do danych tabeli
        $templateData['gameAdminPanel'] = $this; // Upewnij się, że $gameAdminPanel jest dostępne w players-table.php
    }


    /**
     * Strona budowania danych
     */
    public function renderBuilderPage()
    {
        // Dane do przekazania do szablonu
        $data = [
            'admin_post_url' => admin_url('admin-post.php'),
            'nonce_build_missions' => wp_create_nonce('game_build_missions'),
            'nonce_build_npc_relations' => wp_create_nonce('game_build_npc_relations'),
            'nonce_build_areas' => wp_create_nonce('game_build_areas'),
        ];
        include __DIR__ . '/templates/builder-page.php';
    }

    /**
     * Renderuje status systemu
     */
    private function renderSystemStatus()
    {
        $allTablesExist = true;
        $existingTables = 0;
        $totalTables = count(GameDatabaseManager::TABLES);

        foreach (GameDatabaseManager::TABLES as $tableName) {
            if ($this->dbManager->tableExists($tableName)) {
                $existingTables++;
            } else {
                $allTablesExist = false;
            }
        }

        $statusColor = $allTablesExist ? 'green' : 'orange';

        echo "<p><strong>Tabele bazy danych:</strong> <span style='color: $statusColor;'>$existingTables/$totalTables</span></p>";

        if (!$allTablesExist) {
            echo "<p><em>Niektóre tabele nie istnieją. Przejdź do zarządzania bazą danych aby je utworzyć.</em></p>";
        }
    }

    // Handlery formularzy

    public function handleCreateTables()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'game_create_tables')) {
            wp_die('Błąd bezpieczeństwa');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Brak uprawnień');
        }

        $results = $this->dbManager->createAllTables();

        wp_redirect(admin_url('admin.php?page=game-database&created=1'));
        exit;
    }

    public function handleRecreateTables()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'game_recreate_tables')) {
            wp_die('Błąd bezpieczeństwa');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Brak uprawnień');
        }

        $results = $this->dbManager->recreateAllTables();

        wp_redirect(admin_url('admin.php?page=game-database&recreated=1'));
        exit;
    }

    public function handleBuildMissions()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'game_build_missions')) {
            wp_die('Błąd bezpieczeństwa');
        }

        $missions = $this->missionManager->buildMissionsFromACF();

        wp_redirect(admin_url('admin.php?page=game-builder&built_missions=' . count($missions)));
        exit;
    }

    public function handleBuildNpcRelations()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'game_build_npc_relations')) {
            wp_die('Błąd bezpieczeństwa');
        }

        // Budujemy relacje NPC dla wszystkich graczy
        $result = $this->dataBuilder->buildAllNPCRelations();

        $message = $result['success'] ?
            "Zbudowano {$result['created']} relacji NPC, pominięto {$result['skipped']}" :
            "Błąd podczas budowania relacji NPC";

        wp_redirect(admin_url('admin.php?page=game-builder&built_npc=1&message=' . urlencode($message)));
        exit;
    }

    public function handleBuildAreas()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'game_build_areas')) {
            wp_die('Błąd bezpieczeństwa');
        }

        // Budujemy rejony z CPT
        $areas = $this->dataBuilder->buildAreasFromCPT();

        $message = "Zbudowano " . count($areas) . " rejonów z CPT";

        wp_redirect(admin_url('admin.php?page=game-builder&built_areas=1&message=' . urlencode($message)));
        exit;
    }

    public function handleUpdatePlayer()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'game_update_player')) {
            wp_die('Błąd bezpieczeństwa');
        }

        $userId = intval($_POST['user_id']);

        // Sprawdzamy czy gracz istnieje, jeśli nie - tworzymy
        if (!$this->userRepo->playerExists($userId)) {
            $this->userRepo->createPlayer($userId);
        }

        // Aktualizujemy dane
        if (isset($_POST['stats'])) {
            $this->userRepo->updateStats($userId, $_POST['stats']);
        }

        if (isset($_POST['skills'])) {
            $this->userRepo->updateSkills($userId, $_POST['skills']);
        }

        // Więcej aktualizacji...

        wp_redirect(admin_url('admin.php?page=game-players&user_id=' . $userId . '&updated=1'));
        exit;
    }

    /**
     * Obsługuje rozszerzone aktualizacje gracza (przedmioty, rejony, relacje)
     */
    public function handleUpdatePlayerExtended()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'game_update_player_extended')) {
            wp_die('Błąd bezpieczeństwa');
        }

        $userId = intval($_POST['user_id']);

        // Sprawdzamy czy gracz istnieje
        if (!$this->userRepo->playerExists($userId)) {
            wp_die('Gracz nie istnieje');
        }

        // Aktualizacja przedmiotów
        if (isset($_POST['items'])) {
            foreach ($_POST['items'] as $itemId => $itemData) {
                if (isset($itemData['quantity']) && $itemData['quantity'] > 0) {
                    $this->userRepo->addItem($userId, $itemId, intval($itemData['quantity']));

                    // Obsługa założenia przedmiotu
                    if (isset($itemData['equipped']) && $itemData['equipped'] == '1') {
                        $slot = sanitize_text_field($itemData['slot'] ?? '');
                        $this->userRepo->equipItem($userId, $itemId, $slot);
                    }
                } else {
                    // Usunięcie przedmiotu jeśli ilość = 0
                    $this->userRepo->removeItem($userId, $itemId);
                }
            }
        }

        // Aktualizacja dostępnych rejonów
        if (isset($_POST['areas'])) {
            foreach ($_POST['areas'] as $areaId => $areaData) {
                if (isset($areaData['unlocked']) && $areaData['unlocked'] == '1') {
                    $sceneId = sanitize_text_field($areaData['scene_id'] ?? '');
                    $this->userRepo->unlockArea($userId, $areaId, $sceneId);
                } else {
                    $this->userRepo->lockArea($userId, $areaId);
                }
            }
        }

        // Aktualizacja relacji z NPC
        if (isset($_POST['relations'])) {
            foreach ($_POST['relations'] as $npcId => $relationData) {
                $relationValue = intval($relationData['value'] ?? 0);
                $isKnown = isset($relationData['known']) && $relationData['known'] == '1';

                $this->userRepo->updateNpcRelation($userId, $npcId, $relationValue, $isKnown);
            }
        }

        // Aktualizacja wyników walk
        if (isset($_POST['fight_results'])) {
            foreach ($_POST['fight_results'] as $npcId => $fightData) {
                if (isset($fightData['won'])) {
                    $this->userRepo->addFightResult($userId, $npcId, 'won');
                }
                if (isset($fightData['lost'])) {
                    $this->userRepo->addFightResult($userId, $npcId, 'lost');
                }
                if (isset($fightData['draw'])) {
                    $this->userRepo->addFightResult($userId, $npcId, 'draw');
                }
            }
        }

        wp_redirect(admin_url('admin.php?page=game-players&user_id=' . $userId . '&updated=extended'));
        exit;
    }

    private function renderPlayerItems($userId)
    {
        $items = $this->userRepo->getPlayerItems($userId);
        // Przekaż dane do szablonu player-items.php
        include __DIR__ . '/templates/player-items.php';
    }

    private function renderPlayerAreas($userId)
    {
        $areas = $this->userRepo->getPlayerAreas($userId);
        // Przekaż dane do szablonu player-areas.php
        include __DIR__ . '/templates/player-areas.php';
    }

    private function renderPlayerRelations($userId)
    {
        $relations = $this->userRepo->getPlayerRelations($userId);
        // Przekaż dane do szablonu player-relations.php
        include __DIR__ . '/templates/player-relations.php';
    }


    public function renderPlayerItemsSection($userId)
    {
        // Użyj GameDataBuilder do wygenerowania HTML dla zarządzania przedmiotami
        // GameDataBuilder powinien mieć metodę, która zwraca HTML lub renderuje szablon
        // Na przykład:
        // echo $this->dataBuilder->renderItemManagement($userId, $this->userRepo->getPlayerItems($userId));
        // Lub, jeśli GameDataBuilder używa własnych szablonów:
        // $this->dataBuilder->displayItemManagement($userId, $this->userRepo->getPlayerItems($userId));

        // Na razie, jako placeholder, ponieważ GameDataBuilder nie jest jeszcze dostosowany do szablonów:
        echo '<div class="postbox"><div class="inside"><p>Zarządzanie przedmiotami (placeholder - do implementacji w GameDataBuilder lub nowym szablonie)</p></div></div>';
        // Docelowo, ta metoda powinna przygotować dane i dołączyć szablon, np.:
        // $items = $this->userRepo->getPlayerItems($userId);
        // $allItems = $this->dataBuilder->getAllPossibleItems(); // Metoda do pobrania wszystkich możliwych przedmiotów
        // include __DIR__ . '/templates/player-editor-items.php';
    }

    public function renderPlayerAreasSection($userId)
    {
        // Podobnie jak dla przedmiotów
        echo '<div class="postbox"><div class="inside"><p>Zarządzanie rejonami (placeholder)</p></div></div>';
        // $areas = $this->userRepo->getPlayerAreas($userId);
        // $allAreas = $this->dataBuilder->getAllPossibleAreas();
        // include __DIR__ . '/templates/player-editor-areas.php';
    }

    public function renderPlayerRelationsSection($userId)
    {
        // Podobnie jak dla przedmiotów
        echo '<div class="postbox"><div class="inside"><p>Zarządzanie relacjami NPC (placeholder)</p></div></div>';
        // $relations = $this->userRepo->getPlayerRelations($userId);
        // $allNpcs = $this->dataBuilder->getAllNpcs();
        // include __DIR__ . '/templates/player-editor-relations.php';
    }

    public function renderPlayerFightResultsSection($userId)
    {
        // Podobnie jak dla przedmiotów
        echo '<div class="postbox"><div class="inside"><p>Zarządzanie wynikami walk (placeholder)</p></div></div>';
        // $fightData = $this->userRepo->getPlayerFightData($userId); // Załóżmy, że jest taka metoda
        // include __DIR__ . '/templates/player-editor-fights.php';
    }
}
