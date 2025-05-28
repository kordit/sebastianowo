<?php

/**
 * Inicjalizacja systemu customowych tabel gry
 */

// Zapobiegamy bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

// Autoloader dla klas systemu
spl_autoload_register(function ($class_name) {
    $base_dir = __DIR__ . '/';
    $class_map = [
        'GameDatabaseManager' => 'core/GameDatabaseManager.php',
        'GameUserRepository' => 'repositories/GameUserRepository.php',
        'GameItemRepository' => 'repositories/GameItemRepository.php',
        'GameAreaRepository' => 'repositories/GameAreaRepository.php',
        'GameFightTokenRepository' => 'repositories/GameFightTokenRepository.php',
        'DeltaUpdater' => 'services/DeltaUpdater.php',
        'MigrationService' => 'services/MigrationService.php',
        'AreasBuilder' => 'builders/AreasBuilder.php',
        'EventsBuilder' => 'builders/EventsBuilder.php',
        'GameAdminPanel' => 'admin/GameAdminPanel.php',
    ];

    if (isset($class_map[$class_name])) {
        $file = $base_dir . $class_map[$class_name];
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Inicjalizacja systemu po załadowaniu WordPressa
add_action('init', function () {
    // Rejestracja hook'a na tworzenie nowych użytkowników
    add_action('user_register', 'game_create_user_record');

    // Inicjalizacja panelu admina (tylko dla adminów)
    if (is_admin() && current_user_can('manage_options')) {
        new GameAdminPanel();
    }
});

/**
 * Automatyczne tworzenie rekordu game_user dla nowego użytkownika WP
 */
function game_create_user_record($user_id)
{
    try {
        $user_repo = new GameUserRepository();

        // Sprawdź czy gracz już istnieje (zabezpieczenie przed duplikatami)
        if (!$user_repo->exists($user_id)) {
            $user_repo->create($user_id);
        }
    } catch (Exception $e) {
        error_log('Błąd tworzenia game_user: ' . $e->getMessage());
    }
}
