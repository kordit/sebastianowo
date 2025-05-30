<?php

/**
 * Test integracji systemu NPC
 * Sprawdza czy wszystkie komponenty działają razem
 */

require_once 'wp-config.php';
require_once 'wp-content/themes/game/includes/db-tables/npc/init.php';

echo "=== Test integracji systemu NPC ===\n\n";

// 1. Test autoloadera
echo "1. Sprawdzenie autoloadera:\n";
$classes_to_test = [
    'NPCDialogSystem',
    'NPC_DatabaseManager',
    'NPC_NPCRepository',
    'NPC_DialogRepository',
    'NPC_AnswerRepository',
    'NPC_AdminPanel',
    'NPC_DialogService',
    'NPC_APIManager'
];

foreach ($classes_to_test as $class) {
    if (class_exists($class)) {
        echo "   ✓ $class - załadowana\n";
    } else {
        echo "   ✗ $class - BRAK\n";
    }
}

// 2. Test bazy danych
echo "\n2. Sprawdzenie tabel bazy danych:\n";
global $wpdb;

$tables_to_test = [
    $wpdb->prefix . 'npc_entities',
    $wpdb->prefix . 'npc_dialogs',
    $wpdb->prefix . 'npc_answers'
];

foreach ($tables_to_test as $table) {
    $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($result == $table) {
        echo "   ✓ $table - istnieje\n";
    } else {
        echo "   ✗ $table - BRAK\n";
    }
}

// 3. Test repozytoriów
echo "\n3. Test repozytoriów:\n";
try {
    $npc_repo = new NPC_NPCRepository();
    $stats = $npc_repo->get_stats();
    echo "   ✓ NPCRepository - działa (NPCs: {$stats['total']})\n";
} catch (Exception $e) {
    echo "   ✗ NPCRepository - BŁĄD: " . $e->getMessage() . "\n";
}

try {
    $dialog_repo = new NPC_DialogRepository();
    echo "   ✓ DialogRepository - działa\n";
} catch (Exception $e) {
    echo "   ✗ DialogRepository - BŁĄD: " . $e->getMessage() . "\n";
}

try {
    $answer_repo = new NPC_AnswerRepository();
    echo "   ✓ AnswerRepository - działa\n";
} catch (Exception $e) {
    echo "   ✗ AnswerRepository - BŁĄD: " . $e->getMessage() . "\n";
}

// 4. Test DialogService
echo "\n4. Test DialogService:\n";
try {
    $dialog_service = new NPC_DialogService();
    echo "   ✓ DialogService - zainicjalizowany\n";
} catch (Exception $e) {
    echo "   ✗ DialogService - BŁĄD: " . $e->getMessage() . "\n";
}

// 5. Test GameResourceManager integration
echo "\n5. Test integracji GameResourceManager:\n";
$grm_path = 'wp-content/themes/game/includes/db-tables/user/core/GameResourceManager.php';
if (file_exists($grm_path)) {
    require_once $grm_path;
    if (class_exists('GameResourceManager')) {
        echo "   ✓ GameResourceManager - dostępny\n";
        try {
            $grm = new GameResourceManager();
            echo "   ✓ GameResourceManager - zainicjalizowany\n";
        } catch (Exception $e) {
            echo "   ✗ GameResourceManager init - BŁĄD: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✗ GameResourceManager - klasa nie znaleziona\n";
    }
} else {
    echo "   ✗ GameResourceManager - plik nie istnieje\n";
}

// 6. Test typów akcji
echo "\n6. Test typów akcji JavaScript:\n";
$js_path = 'wp-content/themes/game/includes/db-tables/npc/assets/js/npc-actions.js';
if (file_exists($js_path)) {
    echo "   ✓ npc-actions.js - istnieje\n";
    $js_content = file_get_contents($js_path);

    $action_types = [
        'give_gold',
        'take_gold',
        'give_item',
        'take_item',
        'modify_resources',
        'add_item',
        'change_mission_status',
        'unlock_area',
        'modify_npc_relation'
    ];

    foreach ($action_types as $action) {
        if (strpos($js_content, "'$action'") !== false) {
            echo "   ✓ Akcja '$action' - zdefiniowana\n";
        } else {
            echo "   ✗ Akcja '$action' - BRAK\n";
        }
    }
} else {
    echo "   ✗ npc-actions.js - nie istnieje\n";
}

echo "\n=== Koniec testu ===\n";
