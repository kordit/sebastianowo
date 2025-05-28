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
        $this->missionManager = new GameMissionManager();
        $this->deltaManager = new GameDeltaManager();
        $this->dataBuilder = new GameDataBuilder();

        add_action('admin_menu', [$this, 'addAdminMenus']);
        add_action('admin_post_game_create_tables', [$this, 'handleCreateTables']);
        add_action('admin_post_game_build_missions', [$this, 'handleBuildMissions']);
        add_action('admin_post_game_build_npc_relations', [$this, 'handleBuildNpcRelations']);
        add_action('admin_post_game_build_areas', [$this, 'handleBuildAreas']);
        add_action('admin_post_game_update_player', [$this, 'handleUpdatePlayer']);
        add_action('admin_post_game_update_player_extended', [$this, 'handleUpdatePlayerExtended']);
        add_action('admin_post_game_add_mission', [$this, 'handleAddMission']);
        add_action('admin_post_game_start_mission', [$this, 'handleStartMission']);
        add_action('admin_post_game_complete_mission', [$this, 'handleCompleteMission']);
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
            'Misje',
            'Misje',
            'manage_options',
            'game-missions',
            [$this, 'renderMissionsPage']
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
?>
        <div class="wrap">
            <h1>Zarządzanie Grą RPG</h1>

            <div class="card">
                <h2>Status systemu</h2>
                <?php $this->renderSystemStatus(); ?>
            </div>

            <div class="card">
                <h2>Szybkie akcje</h2>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=game-database'); ?>" class="button button-primary">
                        Zarządzaj bazą danych
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=game-players'); ?>" class="button button-secondary">
                        Przeglądaj graczy
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=game-builder'); ?>" class="button button-secondary">
                        Zbuduj dane z ACF
                    </a>
                </p>
            </div>
        </div>
    <?php
    }

    /**
     * Strona zarządzania bazą danych
     */
    public function renderDatabasePage()
    {
        if (isset($_GET['created']) && $_GET['created'] === '1') {
            echo '<div class="notice notice-success"><p>Tabele zostały utworzone pomyślnie!</p></div>';
        }

    ?>
        <div class="wrap">
            <h1>Zarządzanie bazą danych</h1>

            <div class="card">
                <h2>Status tabel</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Tabela</th>
                            <th>Status</th>
                            <th>Opis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tableDescriptions = [
                            'game_users' => 'Główne dane graczy',
                            'game_user_stats' => 'Statystyki graczy',
                            'game_user_skills' => 'Umiejętności graczy',
                            'game_user_progress' => 'Postęp graczy',
                            'game_user_vitality' => 'Witalność graczy',
                            'game_user_items' => 'Ekwipunek graczy',
                            'game_user_areas' => 'Dostępne rejony',
                            'game_user_fight_tokens' => 'Tokeny walk',
                            'game_user_relations' => 'Relacje z NPC',
                            'game_user_story' => 'Historia postaci',
                            'game_user_missions' => 'Misje graczy',
                            'game_user_mission_tasks' => 'Zadania misji'
                        ];

                        foreach (GameDatabaseManager::TABLES as $tableName) {
                            $exists = $this->dbManager->tableExists($tableName);
                            $status = $exists ? '<span style="color: green;">✓ Istnieje</span>' : '<span style="color: red;">✗ Brak</span>';
                            $description = $tableDescriptions[$tableName] ?? '';

                            echo "<tr>";
                            echo "<td><code>$tableName</code></td>";
                            echo "<td>$status</td>";
                            echo "<td>$description</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>Akcje</h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="game_create_tables">
                    <?php wp_nonce_field('game_create_tables'); ?>
                    <p>
                        <input type="submit" class="button button-primary" value="Utwórz wszystkie tabele"
                            onclick="return confirm('Czy na pewno chcesz utworzyć tabele?');">
                    </p>
                    <p class="description">
                        Utworzy wszystkie tabele gry jeśli nie istnieją. Bezpieczne do ponownego uruchomienia.
                    </p>
                </form>
            </div>
        </div>
    <?php
    }

    /**
     * Strona graczy
     */
    public function renderPlayersPage()
    {
        $selectedUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    ?>
        <div class="wrap">
            <h1>Zarządzanie graczami</h1>

            <div class="card">
                <h2>Wybierz gracza</h2>
                <form method="get">
                    <input type="hidden" name="page" value="game-players">
                    <select name="user_id" onchange="this.form.submit()">
                        <option value="0">-- Wybierz gracza --</option>
                        <?php
                        $users = get_users(['orderby' => 'display_name']);
                        foreach ($users as $user) {
                            $selected = $selectedUserId === $user->ID ? 'selected' : '';
                            $hasGameData = $this->userRepo->playerExists($user->ID) ? ' (ma dane gry)' : '';
                            echo "<option value='{$user->ID}' $selected>{$user->display_name}{$hasGameData}</option>";
                        }
                        ?>
                    </select>
                </form>
            </div>

            <?php if ($selectedUserId > 0) {
                $this->renderPlayerEditor($selectedUserId);
            } ?>
        </div>
    <?php
    }

    /**
     * Renderuje edytor gracza
     */
    private function renderPlayerEditor($userId)
    {
        $playerExists = $this->userRepo->playerExists($userId);
        $userData = $playerExists ? $this->userRepo->getPlayerData($userId) : null;
        $user = get_userdata($userId);

    ?>
        <div class="card">
            <h2>Edycja gracza: <?php echo $user->display_name; ?></h2>

            <?php if (!$playerExists): ?>
                <div class="notice notice-warning">
                    <p>Ten użytkownik nie ma jeszcze danych gry. Utworzenie danych nastąpi automatycznie po pierwszym zapisie.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="game_update_player_extended">
                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                <?php wp_nonce_field('game_update_player_extended'); ?>

                <table class="form-table">
                    <!-- Statystyki -->
                    <tr>
                        <th scope="row">Statystyki</th>
                        <td>
                            <fieldset>
                                <label>Siła: <input type="number" name="stats[strength]" value="<?php echo $userData['stats']['strength'] ?? 10; ?>" min="1" max="100"></label><br>
                                <label>Obrona: <input type="number" name="stats[defense]" value="<?php echo $userData['stats']['defense'] ?? 10; ?>" min="1" max="100"></label><br>
                                <label>Zwinność: <input type="number" name="stats[agility]" value="<?php echo $userData['stats']['agility'] ?? 10; ?>" min="1" max="100"></label><br>
                                <label>Inteligencja: <input type="number" name="stats[intelligence]" value="<?php echo $userData['stats']['intelligence'] ?? 10; ?>" min="1" max="100"></label><br>
                                <label>Charyzma: <input type="number" name="stats[charisma]" value="<?php echo $userData['stats']['charisma'] ?? 10; ?>" min="1" max="100"></label>
                            </fieldset>
                        </td>
                    </tr>

                    <!-- Umiejętności -->
                    <tr>
                        <th scope="row">Umiejętności</th>
                        <td>
                            <fieldset>
                                <label>Walka: <input type="number" name="skills[combat]" value="<?php echo $userData['skills']['combat'] ?? 1; ?>" min="1" max="100"></label><br>
                                <label>Kradzież: <input type="number" name="skills[steal]" value="<?php echo $userData['skills']['steal'] ?? 1; ?>" min="1" max="100"></label><br>
                                <label>Dyplomacja: <input type="number" name="skills[diplomacy]" value="<?php echo $userData['skills']['diplomacy'] ?? 1; ?>" min="1" max="100"></label><br>
                                <label>Śledztwo: <input type="number" name="skills[investigation]" value="<?php echo $userData['skills']['investigation'] ?? 1; ?>" min="1" max="100"></label><br>
                                <label>Przetrwanie: <input type="number" name="skills[survival]" value="<?php echo $userData['skills']['survival'] ?? 1; ?>" min="1" max="100"></label>
                            </fieldset>
                        </td>
                    </tr>

                    <!-- Postęp -->
                    <tr>
                        <th scope="row">Postęp</th>
                        <td>
                            <fieldset>
                                <label>Doświadczenie: <input type="number" name="progress[experience]" value="<?php echo $userData['progress']['experience'] ?? 0; ?>" min="0"></label><br>
                                <label>Punkty nauki: <input type="number" name="progress[learning_points]" value="<?php echo $userData['progress']['learning_points'] ?? 5; ?>" min="0"></label><br>
                                <label>Reputacja: <input type="number" name="progress[reputation]" value="<?php echo $userData['progress']['reputation'] ?? 0; ?>"></label><br>
                                <label>Poziom: <input type="number" name="progress[level]" value="<?php echo $userData['progress']['level'] ?? 1; ?>" min="1"></label>
                            </fieldset>
                        </td>
                    </tr>

                    <!-- Witalność -->
                    <tr>
                        <th scope="row">Witalność</th>
                        <td>
                            <fieldset>
                                <label>Max życie: <input type="number" name="vitality[max_life]" value="<?php echo $userData['vitality']['max_life'] ?? 100; ?>" min="1"></label><br>
                                <label>Obecne życie: <input type="number" name="vitality[current_life]" value="<?php echo $userData['vitality']['current_life'] ?? 100; ?>" min="0"></label><br>
                                <label>Max energia: <input type="number" name="vitality[max_energy]" value="<?php echo $userData['vitality']['max_energy'] ?? 100; ?>" min="1"></label><br>
                                <label>Obecna energia: <input type="number" name="vitality[current_energy]" value="<?php echo $userData['vitality']['current_energy'] ?? 100; ?>" min="0"></label>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <h3>Dostępne rejony</h3>
                <?php $this->dataBuilder->renderAreaCheckboxes($userId); ?>

                <h3>Ekwipunek</h3>
                <?php $this->dataBuilder->renderItemSelector($userId); ?>

                <h3>Relacje z NPC</h3>
                <?php $this->dataBuilder->renderNPCRelations($userId); ?>

                <?php submit_button('Zapisz wszystkie zmiany'); ?>
            </form>
        </div>

        <?php
        // Renderuj sekcje przedmiotów, rejonów i relacji
        $this->renderPlayerItems($userId);
        $this->renderPlayerAreas($userId);
        $this->renderPlayerRelations($userId);
        ?>
    <?php
    }

    /**
     * Strona budowania danych
     */
    public function renderBuilderPage()
    {
    ?>
        <div class="wrap">
            <h1>Budowanie danych z ACF</h1>

            <div class="card">
                <h2>Zbuduj misje</h2>
                <p>Tworzy strukturę misji na podstawie Custom Post Types i pól ACF.</p>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="game_build_missions">
                    <?php wp_nonce_field('game_build_missions'); ?>
                    <input type="submit" class="button button-primary" value="Zbuduj misje">
                </form>
            </div>

            <div class="card">
                <h2>Zbuduj relacje z NPC</h2>
                <p>Tworzy bazowe relacje z wszystkimi NPC dla wszystkich graczy.</p>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="game_build_npc_relations">
                    <?php wp_nonce_field('game_build_npc_relations'); ?>
                    <input type="submit" class="button button-primary" value="Zbuduj relacje NPC">
                </form>
            </div>

            <div class="card">
                <h2>Zbuduj dostępne rejony</h2>
                <p>Tworzy strukturę rejonów i scen na podstawie CPT terenów.</p>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="game_build_areas">
                    <?php wp_nonce_field('game_build_areas'); ?>
                    <input type="submit" class="button button-primary" value="Zbuduj rejony">
                </form>
            </div>
        </div>
    <?php
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

        echo '<div class="card">';
        echo '<h3>Przedmioty gracza</h3>';

        if (empty($items)) {
            echo '<p>Gracz nie ma żadnych przedmiotów.</p>';
        } else {
            echo '<table class="wp-list-table widefat">';
            echo '<thead><tr><th>ID przedmiotu</th><th>Ilość</th><th>Założony</th><th>Slot</th></tr></thead>';
            echo '<tbody>';
            foreach ($items as $item) {
                $equipped = $item['is_equipped'] ? 'Tak' : 'Nie';
                echo "<tr>";
                echo "<td>{$item['item_id']}</td>";
                echo "<td>{$item['quantity']}</td>";
                echo "<td>$equipped</td>";
                echo "<td>{$item['equipment_slot']}</td>";
                echo "</tr>";
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    private function renderPlayerAreas($userId)
    {
        $areas = $this->userRepo->getPlayerAreas($userId);

        echo '<div class="card">';
        echo '<h3>Dostępne rejony</h3>';

        if (empty($areas)) {
            echo '<p>Gracz nie ma odblokowanych rejonów.</p>';
        } else {
            echo '<table class="wp-list-table widefat">';
            echo '<thead><tr><th>ID rejonu</th><th>Scena</th><th>Odblokowany</th><th>Data odblokowania</th></tr></thead>';
            echo '<tbody>';
            foreach ($areas as $area) {
                $unlocked = $area['is_unlocked'] ? 'Tak' : 'Nie';
                echo "<tr>";
                echo "<td>{$area['area_id']}</td>";
                echo "<td>{$area['scene_id']}</td>";
                echo "<td>$unlocked</td>";
                echo "<td>{$area['unlocked_at']}</td>";
                echo "</tr>";
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    private function renderPlayerRelations($userId)
    {
        $relations = $this->userRepo->getPlayerRelations($userId);

        echo '<div class="card">';
        echo '<h3>Relacje z NPC</h3>';

        if (empty($relations)) {
            echo '<p>Gracz nie ma relacji z NPC.</p>';
        } else {
            echo '<table class="wp-list-table widefat">';
            echo '<thead><tr><th>ID NPC</th><th>Relacja</th><th>Znany</th><th>W/P/R</th></tr></thead>';
            echo '<tbody>';
            foreach ($relations as $relation) {
                $known = $relation['is_known'] ? 'Tak' : 'Nie';
                $fights = "{$relation['fights_won']}/{$relation['fights_lost']}/{$relation['fights_draw']}";
                echo "<tr>";
                echo "<td>{$relation['npc_id']}</td>";
                echo "<td>{$relation['relation_value']}</td>";
                echo "<td>$known</td>";
                echo "<td>$fights</td>";
                echo "</tr>";
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Strona zarządzania misjami
     */
    public function renderMissionsPage()
    {
        $selectedUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    ?>
        <div class="wrap">
            <h1>Zarządzanie misjami</h1>

            <div class="card">
                <h2>Wybierz gracza</h2>
                <?php $this->renderUserSelector($selectedUserId, 'game-missions'); ?>
            </div>

            <?php if ($selectedUserId): ?>
                <?php if (!$this->userRepo->playerExists($selectedUserId)): ?>
                    <div class="notice notice-warning">
                        <p>Gracz nie ma danych w systemie gry. <a href="<?php echo admin_url('admin.php?page=game-players&user_id=' . $selectedUserId); ?>">Przejdź do panelu gracza</a>, aby utworzyć dane.</p>
                    </div>
                <?php else: ?>
                    <?php $this->renderPlayerMissions($selectedUserId); ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php
    }

    /**
     * Renderuje misje gracza
     */
    private function renderPlayerMissions($userId)
    {
        $missions = $this->missionManager->getPlayerMissions($userId);

        echo '<div class="card">';
        echo '<h3>Misje gracza</h3>';

        if (empty($missions)) {
            echo '<p>Gracz nie ma żadnych misji.</p>';
        } else {
            echo '<table class="wp-list-table widefat">';
            echo '<thead><tr><th>ID Misji</th><th>Status</th><th>Postęp</th><th>Data rozpoczęcia</th><th>Data zakończenia</th><th>Akcje</th></tr></thead>';
            echo '<tbody>';

            foreach ($missions as $mission) {
                $status = $this->missionManager->translateStatus($mission['status']);
                $progress = $mission['completed_tasks'] . '/' . $mission['total_tasks'];
                $startDate = $mission['started_at'] ? date('d.m.Y H:i', strtotime($mission['started_at'])) : '-';
                $endDate = $mission['completed_at'] ? date('d.m.Y H:i', strtotime($mission['completed_at'])) : '-';

                echo "<tr>";
                echo "<td>{$mission['mission_id']}</td>";
                echo "<td>$status</td>";
                echo "<td>$progress</td>";
                echo "<td>$startDate</td>";
                echo "<td>$endDate</td>";
                echo "<td>";

                if ($mission['status'] === 'active') {
                    echo '<a href="#" class="button button-small" onclick="completeMission(' . $mission['mission_id'] . ')">Zakończ</a> ';
                }
                if ($mission['status'] === 'available') {
                    echo '<a href="#" class="button button-small" onclick="startMission(' . $mission['mission_id'] . ')">Rozpocznij</a> ';
                }

                echo "</td>";
                echo "</tr>";
            }

            echo '</tbody></table>';
        }

        echo '</div>';

        // Formularz do ręcznego dodawania misji
        echo '<div class="card">';
        echo '<h3>Dodaj misję</h3>';
        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
        echo '<input type="hidden" name="action" value="game_add_mission">';
        echo '<input type="hidden" name="user_id" value="' . $userId . '">';
        wp_nonce_field('game_add_mission');

        echo '<table class="form-table">';
        echo '<tr><th><label for="mission_id">ID Misji</label></th>';
        echo '<td><input type="text" id="mission_id" name="mission_id" required></td></tr>';
        echo '<tr><th><label for="mission_status">Status</label></th>';
        echo '<td><select id="mission_status" name="status">';
        echo '<option value="available">Dostępna</option>';
        echo '<option value="active">Aktywna</option>';
        echo '<option value="completed">Zakończona</option>';
        echo '<option value="failed">Nieudana</option>';
        echo '</select></td></tr>';
        echo '</table>';

        echo '<p><input type="submit" class="button button-primary" value="Dodaj misję"></p>';
        echo '</form>';
        echo '</div>';

    ?>
        <script>
            function startMission(missionId) {
                if (confirm('Czy na pewno chcesz rozpocząć tę misję?')) {
                    // Implementacja AJAX lub przekierowanie
                    window.location.href = '<?php echo admin_url('admin-post.php'); ?>?action=game_start_mission&mission_id=' + missionId + '&user_id=<?php echo $userId; ?>&_wpnonce=<?php echo wp_create_nonce('game_start_mission'); ?>';
                }
            }

            function completeMission(missionId) {
                if (confirm('Czy na pewno chcesz zakończyć tę misję?')) {
                    window.location.href = '<?php echo admin_url('admin-post.php'); ?>?action=game_complete_mission&mission_id=' + missionId + '&user_id=<?php echo $userId; ?>&_wpnonce=<?php echo wp_create_nonce('game_complete_mission'); ?>';
                }
            }
        </script>
<?php
    }

    /**
     * Obsługuje dodawanie misji
     */
    public function handleAddMission()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'game_add_mission')) {
            wp_die('Błąd bezpieczeństwa');
        }

        $userId = intval($_POST['user_id']);
        $missionId = sanitize_text_field($_POST['mission_id']);
        $status = sanitize_text_field($_POST['status']);

        $result = $this->missionManager->addMissionForPlayer($userId, $missionId, $status);

        $message = $result['success'] ? 'added' : 'error';
        wp_redirect(admin_url('admin.php?page=game-missions&user_id=' . $userId . '&message=' . $message));
        exit;
    }

    /**
     * Obsługuje rozpoczynanie misji
     */
    public function handleStartMission()
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'game_start_mission')) {
            wp_die('Błąd bezpieczeństwa');
        }

        $userId = intval($_GET['user_id']);
        $missionId = sanitize_text_field($_GET['mission_id']);

        $result = $this->missionManager->activateMission($userId, $missionId);

        $message = $result['success'] ? 'started' : 'error';
        wp_redirect(admin_url('admin.php?page=game-missions&user_id=' . $userId . '&message=' . $message));
        exit;
    }

    /**
     * Obsługuje kończenie misji
     */
    public function handleCompleteMission()
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'game_complete_mission')) {
            wp_die('Błąd bezpieczeństwa');
        }

        $userId = intval($_GET['user_id']);
        $missionId = sanitize_text_field($_GET['mission_id']);

        $result = $this->missionManager->completeMission($userId, $missionId);

        $message = $result['success'] ? 'completed' : 'error';
        wp_redirect(admin_url('admin.php?page=game-missions&user_id=' . $userId . '&message=' . $message));
        exit;
    }
}
