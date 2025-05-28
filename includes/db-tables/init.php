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
        'GameUserItemRepository' => 'repositories/GameUserItemRepository.php',
        'GameAreaRepository' => 'repositories/GameAreaRepository.php',
        'GameFightTokenRepository' => 'repositories/GameFightTokenRepository.php',
        'GameNPCRelationRepository' => 'repositories/GameNPCRelationRepository.php',
        'GameUserSyncService' => 'services/GameUserSyncService.php',
        'DeltaUpdater' => 'services/DeltaUpdater.php',
        'MigrationService' => 'services/MigrationService.php',
        'AreaBuilder' => 'builders/AreaBuilder.php',
        'EventsBuilder' => 'builders/EventsBuilder.php',
        'NPCBuilder' => 'builders/NPCBuilder.php',
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
    // Inicjalizacja serwisu synchronizacji użytkowników
    $sync_service = new GameUserSyncService();

    // Rejestracja hook'a na tworzenie nowych użytkowników
    add_action('user_register', [$sync_service, 'autoCreateGameUser']);

    // Inicjalizacja panelu admina (tylko dla adminów)
    if (is_admin() && current_user_can('manage_options')) {
        new GameAdminPanel();
    }
});
