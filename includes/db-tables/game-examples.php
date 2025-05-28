<?php

/**
 * Przykłady użycia systemu zarządzania grą RPG
 * 
 * Ten plik zawiera przykłady podstawowych operacji.
 * Nie włączaj tego pliku do produkcji - służy tylko jako dokumentacja.
 */

// UWAGA: To jest tylko plik demonstracyjny!
// return; // Odkomentuj tę linię w środowisku produkcyjnym

// Upewnij się, że system jest załadowany
if (!class_exists('GameUserRepository')) {
    require_once 'game-system-init.php';
}

/**
 * PRZYKŁAD 1: Podstawowe operacje na graczu
 */
function example_basic_player_operations()
{
    $userId = 1; // ID użytkownika WordPress

    // Pobierz repozytorium
    $userRepo = game_get_user_repository();

    // Sprawdź czy gracz istnieje
    if (!$userRepo->playerExists($userId)) {
        echo "Tworzę nowego gracza...\n";

        // Utwórz gracza z domyślnymi danymi
        $result = $userRepo->createPlayer($userId, [
            'nickname' => 'TestHero',
            'character_class' => 'warrior'
        ]);

        if ($result['success']) {
            echo "Gracz utworzony pomyślnie!\n";
        } else {
            echo "Błąd tworzenia gracza: " . $result['error'] . "\n";
            return;
        }
    }

    // Aktualizuj statystyki
    $stats = [
        'strength' => 15,
        'endurance' => 12,
        'dexterity' => 10,
        'intelligence' => 8
    ];

    $result = $userRepo->updateStats($userId, $stats);
    if ($result['success']) {
        echo "Statystyki zaktualizowane!\n";
    }

    // Aktualizuj umiejętności
    $skills = [
        'sword_fighting' => 25,
        'magic' => 5,
        'archery' => 15
    ];

    $result = $userRepo->updateSkills($userId, $skills);
    if ($result['success']) {
        echo "Umiejętności zaktualizowane!\n";
    }
}

/**
 * PRZYKŁAD 2: System Delta - bezpieczne zmiany wartości
 */
function example_delta_system()
{
    $userId = 1;

    // Pobierz menedżera delta
    $deltaManager = game_get_delta_manager();

    // Dodaj doświadczenie (z limitem maksymalnym)
    $result = $deltaManager->increase(
        'game_user_progress',
        ['user_id' => $userId],
        'experience',
        150,
        ['max' => 10000] // Maksimum 10000 exp
    );

    if ($result['success']) {
        echo "Dodano 150 doświadczenia!\n";
        echo "Nowa wartość: " . $result['new_value'] . "\n";
    }

    // Odejmij HP (z limitem minimalnym)
    $result = $deltaManager->decrease(
        'game_user_vitality',
        ['user_id' => $userId],
        'current_hp',
        30,
        ['min' => 0] // Minimum 0 HP
    );

    if ($result['success']) {
        echo "Odjęto 30 HP!\n";
        echo "Obecne HP: " . $result['new_value'] . "\n";
    }

    // Ustaw poziom na konkretną wartość
    $result = $deltaManager->set(
        'game_user_progress',
        ['user_id' => $userId],
        'level',
        5,
        ['min' => 1, 'max' => 100]
    );

    if ($result['success']) {
        echo "Poziom ustawiony na 5!\n";
    }
}

/**
 * PRZYKŁAD 3: Zarządzanie przedmiotami
 */
function example_item_management()
{
    $userId = 1;
    $userRepo = game_get_user_repository();

    // Dodaj miecz
    $result = $userRepo->addItem($userId, 'iron_sword', 1);
    if ($result['success']) {
        echo "Dodano miecz żelazny!\n";
    }

    // Załóż miecz w slot broni
    $result = $userRepo->equipItem($userId, 'iron_sword', 'weapon');
    if ($result['success']) {
        echo "Założono miecz!\n";
    }

    // Dodaj miksturę leczniczą
    $result = $userRepo->addItem($userId, 'health_potion', 5);
    if ($result['success']) {
        echo "Dodano 5 mikstur leczniczych!\n";
    }

    // Użyj mikstury (usuń 1 sztukę)
    $result = $userRepo->removeItem($userId, 'health_potion', 1);
    if ($result['success']) {
        echo "Użyto miksturę leczniczą!\n";
    }
}

/**
 * PRZYKŁAD 4: Zarządzanie rejonami
 */
function example_area_management()
{
    $userId = 1;
    $userRepo = game_get_user_repository();

    // Odblokuj nowy rejon
    $result = $userRepo->unlockArea($userId, 'forest_001', 'entrance');
    if ($result['success']) {
        echo "Odblokowano las!\n";
    }

    // Odblokuj kolejny rejon z konkretną sceną
    $result = $userRepo->unlockArea($userId, 'cave_001', 'cave_entrance');
    if ($result['success']) {
        echo "Odblokowano jaskinię!\n";
    }

    // Zablokuj rejon (jeśli potrzebne)
    $result = $userRepo->lockArea($userId, 'forest_001');
    if ($result['success']) {
        echo "Zablokowano las!\n";
    }
}

/**
 * PRZYKŁAD 5: System misji
 */
function example_mission_system()
{
    $userId = 1;
    $missionManager = game_get_mission_manager();

    // Dodaj dostępną misję
    $result = $missionManager->addMissionForPlayer($userId, 'kill_10_goblins', 'available');
    if ($result['success']) {
        echo "Dodano misję zabicia goblinów!\n";
    }

    // Rozpocznij misję
    $result = $missionManager->activateMission($userId, 'kill_10_goblins');
    if ($result['success']) {
        echo "Rozpoczęto misję!\n";
    }

    // Zakończ misję
    $result = $missionManager->completeMission($userId, 'kill_10_goblins');
    if ($result['success']) {
        echo "Misja zakończona!\n";
    }

    // Pobierz wszystkie misje gracza
    $missions = $missionManager->getPlayerMissions($userId);
    echo "Gracz ma " . count($missions) . " misji\n";
}

/**
 * PRZYKŁAD 6: Relacje z NPC
 */
function example_npc_relations()
{
    $userId = 1;
    $userRepo = game_get_user_repository();

    // Dodaj relację z NPC
    $result = $userRepo->addNpcRelation($userId, 'blacksmith_tom', 0, true);
    if ($result['success']) {
        echo "Poznano kowala Toma!\n";
    }

    // Aktualizuj relację (poprawa stosunków)
    $result = $userRepo->updateNpcRelation($userId, 'blacksmith_tom', 25, true);
    if ($result['success']) {
        echo "Relacja z kowalem poprawiona!\n";
    }

    // Dodaj wynik walki
    $result = $userRepo->addFightResult($userId, 'bandit_leader', 'won');
    if ($result['success']) {
        echo "Pokonano lidera bandytów!\n";
    }
}

/**
 * PRZYKŁAD 7: Sprawdzanie stanu systemu
 */
function example_system_check()
{
    $status = game_system_check();

    echo "=== STAN SYSTEMU ===\n";

    // Sprawdź klasy
    echo "Klasy:\n";
    foreach ($status['classes'] as $class => $exists) {
        $status_text = $exists ? "✓" : "✗";
        echo "  $status_text $class\n";
    }

    // Sprawdź tabele
    echo "\nTabele:\n";
    foreach ($status['database'] as $table => $exists) {
        $status_text = $exists ? "✓" : "✗";
        echo "  $status_text $table\n";
    }

    // Pokaż błędy
    if (!empty($status['errors'])) {
        echo "\nBłędy:\n";
        foreach ($status['errors'] as $error) {
            echo "  • $error\n";
        }
    } else {
        echo "\n✓ System działa poprawnie!\n";
    }
}

/**
 * PRZYKŁAD 8: Funkcje pomocnicze
 */
function example_helper_functions()
{
    // Walidacja ID użytkownika
    $isValid = game_validate_user_id(1);
    echo "Użytkownik 1 jest " . ($isValid ? "poprawny" : "niepoprawny") . "\n";

    // Walidacja statystyk
    $stats = ['strength' => 15, 'invalid_stat' => 200];
    $isValid = game_validate_stats($stats);
    echo "Statystyki są " . ($isValid ? "poprawne" : "niepoprawne") . "\n";

    // Bezpieczna konwersja liczb
    $safe_value = game_safe_numeric(150, 0, 100); // Ograniczone do 100
    echo "Bezpieczna wartość: $safe_value\n";
}

// Uruchom przykłady (tylko w trybie deweloperskim)
if (WP_DEBUG) {
    echo "=== PRZYKŁADY UŻYCIA SYSTEMU GRY ===\n\n";

    try {
        example_system_check();
        echo "\n" . str_repeat("=", 40) . "\n\n";

        // Uruchom inne przykłady tylko jeśli system działa
        $status = game_system_check();
        if (empty($status['errors'])) {
            example_basic_player_operations();
            echo "\n";
            example_delta_system();
            echo "\n";
            example_item_management();
            echo "\n";
            example_area_management();
            echo "\n";
            example_mission_system();
            echo "\n";
            example_npc_relations();
            echo "\n";
            example_helper_functions();
        }
    } catch (Exception $e) {
        echo "BŁĄD: " . $e->getMessage() . "\n";
        game_log_error("Błąd w przykładach: " . $e->getMessage());
    }
}
