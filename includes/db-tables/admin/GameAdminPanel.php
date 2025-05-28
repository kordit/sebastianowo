<?php

/**
 * Panel administracyjny gry
 */
class GameAdminPanel
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'handleFormSubmissions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Dodaje menu w panelu admina
     */
    public function addAdminMenu()
    {
        add_menu_page(
            'Lista graczy',
            'Gracze',
            'manage_options',
            'game-users',
            [$this, 'displayUsersPage'],
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'game-users',
            'Konfiguracja bazy danych',
            'Konfiguracja bazy danych',
            'manage_options',
            'game-database',
            [$this, 'displayDatabasePage']
        );

        add_submenu_page(
            'game-users',
            'Buildery',
            'Buildery',
            'manage_options',
            'game-builders',
            [$this, 'displayBuildersPage']
        );
    }

    /**
     * Ładuje CSS i JS dla panelu
     */
    public function enqueueAssets($hook)
    {
        // Ładuj tylko na stronach naszego panelu
        if (strpos($hook, 'game-') === false) {
            return;
        }

        $assets_url = get_template_directory_uri() . '/includes/db-tables/admin/assets/';

        wp_enqueue_style(
            'game-admin-css',
            $assets_url . 'css/admin.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'game-admin-js',
            $assets_url . 'js/admin.js',
            [],
            '1.0.0',
            true
        );
    }

    /**
     * Obsługuje formularze POST
     */
    public function handleFormSubmissions()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $db_manager = new GameDatabaseManager();
        $user_sync = new GameUserSyncService();
        $npc_builder = new NPCBuilder();
        $area_builder = new AreaBuilder();
        $area_repository = new GameAreaRepository();

        // Tworzenie tabel
        if (isset($_POST['create_tables']) && wp_verify_nonce($_POST['_wpnonce'], 'create_tables')) {
            $db_manager->createTables();
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> Tabele zostały utworzone/zaktualizowane!</p></div>';
            });
        }

        // Usuwanie tabel
        if (isset($_POST['drop_tables']) && wp_verify_nonce($_POST['_wpnonce'], 'drop_tables')) {
            $db_manager->dropTables();
            add_action('admin_notices', function () {
                echo '<div class="notice notice-warning is-dismissible"><p><strong>Uwaga!</strong> Wszystkie tabele zostały usunięte!</p></div>';
            });
        }

        // Import użytkowników
        if (isset($_POST['import_users']) && wp_verify_nonce($_POST['_wpnonce'], 'import_users')) {
            $result = $user_sync->importAllUsers();

            if ($result['success']) {
                add_action('admin_notices', function () use ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> ' . esc_html($result['message']) . ' Zaimportowano: ' . $result['imported'] . '</p></div>';
                });
            } else {
                add_action('admin_notices', function () use ($result) {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> ' . esc_html($result['message']) . '</p></div>';
                });
            }
        }

        // Edycja gracza
        if (isset($_POST['update_game_user']) && wp_verify_nonce($_POST['_wpnonce'], 'update_game_user')) {
            $user_id = intval($_POST['user_id']);
            $user_repo = new GameUserRepository();

            $update_data = [
                'nick' => sanitize_text_field($_POST['nick']),
                'user_class' => sanitize_text_field($_POST['user_class']),
                'exp' => intval($_POST['exp']),
                'learning_points' => intval($_POST['learning_points']),
                'reputation' => intval($_POST['reputation']),
                'strength' => intval($_POST['strength']),
                'defense' => intval($_POST['defense']),
                'dexterity' => intval($_POST['dexterity']),
                'perception' => intval($_POST['perception']),
                'technical' => intval($_POST['technical']),
                'charisma' => intval($_POST['charisma']),
                'combat' => intval($_POST['combat']),
                'steal' => intval($_POST['steal']),
                'craft' => intval($_POST['craft']),
                'trade' => intval($_POST['trade']),
                'relations' => intval($_POST['relations']),
                'street' => intval($_POST['street']),
                'life' => intval($_POST['life']),
                'max_life' => intval($_POST['max_life']),
                'energy' => intval($_POST['energy']),
                'max_energy' => intval($_POST['max_energy']),
                'gold' => intval($_POST['gold']),
                'cigarettes' => intval($_POST['cigarettes']),
                'story_text' => sanitize_textarea_field($_POST['story_text'])
            ];

            try {
                $user_repo->update($user_id, $update_data);

                // Aktualizuj relacje z NPC jeśli zostały przesłane
                if (isset($_POST['npc_relations']) && is_array($_POST['npc_relations'])) {
                    $npc_repo = new GameNPCRelationRepository();
                    $updated_relations = 0;

                    foreach ($_POST['npc_relations'] as $npc_id => $relation_data) {
                        $npc_id = intval($npc_id);

                        // Sprawdź czy relacja istnieje
                        $existing_relation = $npc_repo->getRelation($user_id, $npc_id);

                        if ($existing_relation) {
                            // Przygotuj dane do aktualizacji
                            $npc_update_data = [
                                'is_known' => isset($relation_data['is_known']) ? 1 : 0,
                                'relation_value' => intval($relation_data['relation_value']),
                                'fights_won' => intval($relation_data['fights_won']),
                                'fights_lost' => intval($relation_data['fights_lost']),
                                'fights_draw' => intval($relation_data['fights_draw']),
                                'last_interaction' => date('Y-m-d H:i:s')
                            ];

                            // Waliduj relation_value
                            if ($npc_update_data['relation_value'] < -100) $npc_update_data['relation_value'] = -100;
                            if ($npc_update_data['relation_value'] > 100) $npc_update_data['relation_value'] = 100;

                            $npc_repo->updateRelation($user_id, $npc_id, $npc_update_data);
                            $updated_relations++;
                        }
                    }
                }

                add_action('admin_notices', function () use ($updated_relations) {
                    $message = 'Dane gracza zostały zaktualizowane!';
                    if (isset($updated_relations) && $updated_relations > 0) {
                        $message .= " Zaktualizowano również {$updated_relations} relacji z NPC.";
                    }
                    echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> ' . esc_html($message) . '</p></div>';
                });
            } catch (Exception $e) {
                add_action('admin_notices', function () use ($e) {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> ' . esc_html($e->getMessage()) . '</p></div>';
                });
            }
        }

        // Obsługa operacji na przedmiotach (niezależna od głównego formularza)
        if (isset($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);

            // Dodawanie przedmiotu
            if (
                isset($_POST['add_item']) && isset($_POST['item_id']) && isset($_POST['item_amount']) &&
                isset($_POST['_wpnonce_add_item']) && wp_verify_nonce($_POST['_wpnonce_add_item'], 'add_item')
            ) {

                $item_id = intval($_POST['item_id']);
                $amount = intval($_POST['item_amount']);

                if ($item_id > 0 && $amount > 0) {
                    $item_repo = new GameUserItemRepository();
                    $result = $item_repo->addItem($user_id, $item_id, $amount);

                    if ($result !== false) {
                        add_action('admin_notices', function () use ($amount) {
                            echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> Dodano ' . $amount . ' szt. przedmiotu do ekwipunku gracza.</p></div>';
                        });
                    } else {
                        add_action('admin_notices', function () {
                            echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> Nie udało się dodać przedmiotu.</p></div>';
                        });
                    }
                }
            }

            // Usuwanie przedmiotu
            if (
                isset($_POST['remove_item']) && isset($_POST['item_id_remove']) && isset($_POST['item_amount_remove']) &&
                isset($_POST['_wpnonce_remove_item']) && wp_verify_nonce($_POST['_wpnonce_remove_item'], 'remove_item')
            ) {

                $item_id = intval($_POST['item_id_remove']);
                $amount = intval($_POST['item_amount_remove']);

                if ($item_id > 0 && $amount > 0) {
                    $item_repo = new GameUserItemRepository();
                    $result = $item_repo->removeItem($user_id, $item_id, $amount);

                    if ($result !== false) {
                        add_action('admin_notices', function () use ($amount) {
                            echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> Usunięto ' . $amount . ' szt. przedmiotu z ekwipunku gracza.</p></div>';
                        });
                    } else {
                        add_action('admin_notices', function () {
                            echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> Nie udało się usunąć przedmiotu.</p></div>';
                        });
                    }
                }
            }

            // Aktualizacja ilości przedmiotu
            if (
                isset($_POST['update_item_amount']) && isset($_POST['item_id']) && isset($_POST['item_new_amount']) &&
                isset($_POST['_wpnonce_update_item']) && wp_verify_nonce($_POST['_wpnonce_update_item'], 'update_item_amount')
            ) {

                $item_id = intval($_POST['item_id']);
                $amount = intval($_POST['item_new_amount']);

                if ($item_id > 0) {
                    $item_repo = new GameUserItemRepository();

                    if ($amount <= 0) {
                        // Usuń przedmiot całkowicie
                        $result = $item_repo->removeItem($user_id, $item_id, 999999); // Duża liczba, aby usunąć wszystko

                        if ($result !== false) {
                            add_action('admin_notices', function () {
                                echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> Przedmiot został całkowicie usunięty z ekwipunku gracza.</p></div>';
                            });
                        } else {
                            add_action('admin_notices', function () {
                                echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> Nie udało się usunąć przedmiotu.</p></div>';
                            });
                        }
                    } else {
                        // Ustaw nową ilość
                        $result = $item_repo->setItemAmount($user_id, $item_id, $amount);

                        if ($result !== false) {
                            add_action('admin_notices', function () use ($amount) {
                                echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> Zmieniono ilość przedmiotu na ' . $amount . ' szt.</p></div>';
                            });
                        } else {
                            add_action('admin_notices', function () {
                                echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> Nie udało się zaktualizować ilości przedmiotu.</p></div>';
                            });
                        }
                    }
                }
            }

            // Zmiana statusu wyposażenia przedmiotu
            if (
                isset($_POST['update_item_equipped']) && isset($_POST['item_id']) && isset($_POST['item_equipped_status']) &&
                isset($_POST['_wpnonce_update_equipped']) && wp_verify_nonce($_POST['_wpnonce_update_equipped'], 'update_item_equipped')
            ) {

                $item_id = intval($_POST['item_id']);
                $is_equipped = intval($_POST['item_equipped_status']);

                if ($item_id > 0) {
                    $item_repo = new GameUserItemRepository();
                    $result = $item_repo->setEquipped($user_id, $item_id, $is_equipped);

                    if ($result !== false) {
                        $status_text = $is_equipped ? 'wyposażony' : 'nie wyposażony';
                        add_action('admin_notices', function () use ($status_text) {
                            echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> Przedmiot został oznaczony jako ' . $status_text . '.</p></div>';
                        });
                    } else {
                        // Sprawdź czy próbujemy wyposażyć przedmiot, który nie może wyposażyć
                        if ($is_equipped) {
                            $item_types = wp_get_post_terms($item_id, 'item_type');
                            $can_be_equipped = false;

                            if (!empty($item_types)) {
                                foreach ($item_types as $term) {
                                    if ($term->term_id == 2) {
                                        $can_be_equipped = true;
                                        break;
                                    }
                                }
                            }

                            if (!$can_be_equipped) {
                                add_action('admin_notices', function () {
                                    echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> Ten przedmiot nie może być wyposażony. Tylko przedmioty z kategorii wyposażenia (ID=2) mogą być wyposażone.</p></div>';
                                });
                                return;
                            }
                        }

                        add_action('admin_notices', function () {
                            echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> Nie udało się zaktualizować statusu wyposażenia.</p></div>';
                        });
                    }
                }
            }
        }

        // Budowanie relacji NPC
        if (isset($_POST['build_npc_relations']) && wp_verify_nonce($_POST['_wpnonce'], 'build_npc_relations')) {
            $result = $npc_builder->buildAllRelations();

            if ($result['success']) {
                add_action('admin_notices', function () use ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> ' . esc_html($result['message']) . '</p></div>';
                });
            } else {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> Nie udało się zbudować relacji.</p></div>';
                });
            }
        }

        // Czyszczenie relacji NPC
        if (isset($_POST['clear_npc_relations']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_npc_relations')) {
            $result = $npc_builder->clearAllRelations();

            if ($result['success']) {
                add_action('admin_notices', function () use ($result) {
                    echo '<div class="notice notice-warning is-dismissible"><p><strong>Uwaga!</strong> ' . esc_html($result['message']) . '</p></div>';
                });
            } else {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> Nie udało się wyczyścić relacji.</p></div>';
                });
            }
        }

        // Budowanie połączeń obszarów
        if (isset($_POST['build_area_connections']) && wp_verify_nonce($_POST['_wpnonce'], 'build_area_connections')) {
            $result = $area_builder->buildAllAreaConnections();

            if ($result['success']) {
                add_action('admin_notices', function () use ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> ' . esc_html($result['message']) . '</p></div>';
                });
            } else {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> Nie udało się zbudować połączeń obszarów.</p></div>';
                });
            }
        }

        // Czyszczenie połączeń obszarów
        if (isset($_POST['clear_area_connections']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_area_connections')) {
            $result = $area_builder->clearAllAreaConnections();

            if ($result['success']) {
                add_action('admin_notices', function () use ($result) {
                    echo '<div class="notice notice-warning is-dismissible"><p><strong>Uwaga!</strong> ' . esc_html($result['message']) . '</p></div>';
                });
            } else {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> Nie udało się wyczyścić połączeń obszarów.</p></div>';
                });
            }
        }

        // Aktualizacja statusów obszarów gracza
        if (isset($_POST['update_user_areas']) && wp_verify_nonce($_POST['_wpnonce'], 'update_user_areas')) {
            $user_id = intval($_POST['user_id']);
            $area_repo = new GameAreaRepository();
            $updated_scenes = 0;
            $errors = 0;

            if (isset($_POST['area_scenes']) && is_array($_POST['area_scenes'])) {
                foreach ($_POST['area_scenes'] as $scene_key => $scene_data) {
                    // Parsuj klucz sceny (format: area_id-scene_id)
                    $parts = explode('-', $scene_key);
                    if (count($parts) !== 2) continue;

                    $area_id = intval($parts[0]);
                    $scene_id = sanitize_text_field($parts[1]);

                    // Pobierz aktualną scenę
                    $current_scene = $area_repo->getUserScene($user_id, $area_id, $scene_id);

                    if ($current_scene) {
                        // Przygotuj dane do aktualizacji
                        $update_data = [
                            'unlocked' => isset($scene_data['unlocked']) ? 1 : 0,
                            'viewed' => isset($scene_data['viewed']) ? 1 : 0,
                            'is_current' => isset($scene_data['is_current']) ? 1 : 0,
                            'updated_at' => date('Y-m-d H:i:s')
                        ];

                        // Jeśli ustawiamy is_current=1, to najpierw wyczyść wszystkie inne is_current dla tego użytkownika
                        if ($update_data['is_current'] == 1) {
                            global $wpdb;
                            $wpdb->update(
                                $wpdb->prefix . 'game_user_areas',
                                ['is_current' => 0],
                                ['user_id' => $user_id]
                            );
                        }

                        // Aktualizuj scenę
                        global $wpdb;
                        $result = $wpdb->update(
                            $wpdb->prefix . 'game_user_areas',
                            $update_data,
                            [
                                'user_id' => $user_id,
                                'area_id' => $area_id,
                                'scene_id' => $scene_id
                            ]
                        );

                        if ($result !== false) {
                            $updated_scenes++;
                        } else {
                            $errors++;
                        }
                    }
                }
            }

            if ($updated_scenes > 0) {
                add_action('admin_notices', function () use ($updated_scenes) {
                    echo '<div class="notice notice-success is-dismissible"><p><strong>Sukces!</strong> Zaktualizowano ' . $updated_scenes . ' scen obszarów.</p></div>';
                });
            }

            if ($errors > 0) {
                add_action('admin_notices', function () use ($errors) {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Błąd!</strong> ' . $errors . ' scen nie zostało zaktualizowanych.</p></div>';
                });
            }

            if ($updated_scenes == 0 && $errors == 0) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-info is-dismissible"><p><strong>Info!</strong> Nie wprowadzono żadnych zmian w obszarach.</p></div>';
                });
            }
        }
    }

    /**
     * Strona Users
     */
    public function displayUsersPage()
    {
        // Sprawdź czy pokazać szczegóły gracza
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'view' && $user_id > 0) {
            $this->displayUserDetails($user_id);
        } else {
            $this->displayUsersList();
        }
    }

    /**
     * Lista wszystkich graczy
     */
    private function displayUsersList()
    {
        $user_repo = new GameUserRepository();
        $users = $user_repo->getAll();

        // Pobierz statystyki
        $user_sync = new GameUserSyncService();
        $stats = $user_sync->getUsersStats();

        include __DIR__ . '/views/users-list.php';
    }

    /**
     * Szczegóły pojedynczego gracza
     */
    private function displayUserDetails($user_id)
    {
        $user_repo = new GameUserRepository();
        $game_user = $user_repo->getByUserId($user_id);

        if (!$game_user) {
            wp_die('Gracz nie został znaleziony.');
        }

        // Pobierz dane użytkownika WordPress
        $wp_user = get_user_by('ID', $user_id);

        // Pobierz relacje z NPC
        $npc_repo = new GameNPCRelationRepository();
        $user_npc_relations = $npc_repo->getUserRelations($user_id);

        // Pobierz wszystkie NPC dla nazw
        $npc_builder = new NPCBuilder();
        $all_npcs = $npc_builder->getAllNPCs();
        $npcs_by_id = [];
        foreach ($all_npcs as $npc) {
            $npcs_by_id[$npc['id']] = $npc['name'];
        }

        // Pobierz przedmioty gracza
        $item_repo = new GameUserItemRepository();
        $user_items = $item_repo->getUserItems($user_id);
        $all_items = $item_repo->getAllAvailableItems();
        $items_stats = $item_repo->getUserItemStats($user_id);

        // Pobierz aktualną lokalizację gracza
        $area_repo = new GameAreaRepository();
        $current_location = $area_repo->getCurrentLocation($user_id);
        $user_areas_stats = $area_repo->getUserAreasStats($user_id);

        // Pobierz wszystkie obszary gracza z detalami
        $user_areas = $area_repo->getUserAreas($user_id);
        $area_builder = new AreaBuilder();
        $all_areas = $area_builder->getAllAreas();

        // Połącz dane WordPress z danymi gracza
        $areas_with_details = [];
        foreach ($all_areas as $wp_area) {
            $user_area_scenes = $area_repo->getUserArea($user_id, $wp_area['id']);
            $areas_with_details[] = [
                'wp_data' => $wp_area,
                'user_scenes' => $user_area_scenes ?: []
            ];
        }

        include __DIR__ . '/views/user-details.php';
    }

    /**
     * Strona Database Setup
     */
    public function displayDatabasePage()
    {
        $db_manager = new GameDatabaseManager();
        $user_sync = new GameUserSyncService();

        $tables_exist = $db_manager->allTablesExist();
        $tables_status = $db_manager->getTablesStatus();
        $users_stats = $user_sync->getUsersStats();

        include __DIR__ . '/views/database-page.php';
    }

    /**
     * Wyświetla stronę builderów
     */
    public function displayBuildersPage()
    {
        $npc_builder = new NPCBuilder();
        $area_builder = new AreaBuilder();
        $area_repository = new GameAreaRepository();

        $relations_stats = $npc_builder->getRelationsStats();
        $npcs_list = $npc_builder->getAllNPCs();

        // Pobierz statystyki struktury z buildera i bazodanowe z repozytorium
        $areas_structure_stats = $area_builder->getAreasStructureStats();
        $areas_database_stats = $area_repository->getAreasStats();
        $areas_list = $area_builder->getAllAreas();

        include __DIR__ . '/views/builders-page.php';
    }
}
