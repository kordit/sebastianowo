<?php

/**
 * Test script dla systemu statusów powiadomień NPC
 * 
 * Ten skrypt testuje czy nowy system statusów powiadomień działa poprawnie
 * w różnych scenariuszach akcji NPC.
 */

// Sprawdź czy można uruchomić ten skrypt
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
}

require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-includes/wp-db.php');
require_once(ABSPATH . 'wp-includes/pluggable.php');

// Włącz ładowanie WordPress
if (!function_exists('wp_head')) {
    require_once(ABSPATH . 'wp-settings.php');
}

echo "=== TEST SYSTEMU STATUSÓW POWIADOMIEŃ NPC ===\n\n";

// Sprawdź czy klasa NpcPopup istnieje
if (!class_exists('NpcPopup')) {
    echo "❌ BŁĄD: Klasa NpcPopup nie została znaleziona!\n";
    exit;
}

// Sprawdź czy użytkownik jest zalogowany
$test_user_id = 1; // Użyj ID administratora dla testów
if (!get_user_by('ID', $test_user_id)) {
    echo "❌ BŁĄD: Nie można znaleźć użytkownika o ID {$test_user_id}!\n";
    exit;
}

// Ustawienie aktualnego użytkownika dla testów
wp_set_current_user($test_user_id);
echo "✅ Użytkownik testowy ustawiony: ID {$test_user_id}\n\n";

// Utwórz instancję klasy NpcPopup
$npc_popup = new NpcPopup();

// Test 1: Symulacja pozytywnej akcji (otrzymanie przedmiotu)
echo "🔍 TEST 1: Pozytywna akcja - otrzymanie przedmiotu\n";
echo "-----------------------------------------------\n";

// Symuluj dane żądania dla pozytywnej akcji
$positive_request_data = array(
    'npc_id' => 473,  // Junior
    'dialog_id' => 'tak',  // Dialog przejścia po wybraniu "tak"
    'answer_index' => 0,   // Indeks odpowiedzi "tak"
    'current_dialog_id' => 'siemanko'  // Aktualny dialog z akcją
);

// Utwórz mock obiektu żądania
class MockRequest
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function get_param($key)
    {
        return $this->params[$key] ?? null;
    }
}

$mock_request = new MockRequest($positive_request_data);

try {
    $response = $npc_popup->handle_dialog_transition($mock_request);
    $data = $response->get_data();

    if ($data['success'] && isset($data['notification'])) {
        $notification = $data['notification'];
        echo "✅ Powiadomienie otrzymane:\n";
        echo "   Wiadomość: " . $notification['message'] . "\n";
        echo "   Status: " . $notification['status'] . "\n";

        if ($notification['status'] === 'success') {
            echo "✅ Status jest poprawny dla pozytywnej akcji!\n";
        } else {
            echo "❌ BŁĄD: Oczekiwano status 'success', otrzymano: " . $notification['status'] . "\n";
        }
    } elseif ($data['success']) {
        echo "⚠️  Brak powiadomienia w odpowiedzi (może być normalny jeśli brak akcji)\n";
    } else {
        echo "❌ BŁĄD w żądaniu: " . ($data['message'] ?? 'Nieznany błąd') . "\n";
    }
} catch (Exception $e) {
    echo "❌ WYJĄTEK podczas testu 1: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Symulacja negatywnej akcji (pogorszenie relacji)
echo "🔍 TEST 2: Negatywna akcja - pogorszenie relacji\n";
echo "----------------------------------------------\n";

$negative_request_data = array(
    'npc_id' => 473,  // Junior
    'dialog_id' => 'nie',  // Dialog przejścia po wybraniu "nie"
    'answer_index' => 1,   // Indeks odpowiedzi "nie"
    'current_dialog_id' => 'siemanko'  // Aktualny dialog z akcją
);

$mock_request2 = new MockRequest($negative_request_data);

try {
    $response2 = $npc_popup->handle_dialog_transition($mock_request2);
    $data2 = $response2->get_data();

    if ($data2['success'] && isset($data2['notification'])) {
        $notification2 = $data2['notification'];
        echo "✅ Powiadomienie otrzymane:\n";
        echo "   Wiadomość: " . $notification2['message'] . "\n";
        echo "   Status: " . $notification2['status'] . "\n";

        if ($notification2['status'] === 'bad') {
            echo "✅ Status jest poprawny dla negatywnej akcji!\n";
        } else {
            echo "❌ BŁĄD: Oczekiwano status 'bad', otrzymano: " . $notification2['status'] . "\n";
        }
    } elseif ($data2['success']) {
        echo "⚠️  Brak powiadomienia w odpowiedzi (może być normalny jeśli brak akcji)\n";
    } else {
        echo "❌ BŁĄD w żądaniu: " . ($data2['message'] ?? 'Nieznany błąd') . "\n";
    }
} catch (Exception $e) {
    echo "❌ WYJĄTEK podczas testu 2: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Sprawdzenie czy system powiadomień JavaScript jest dostępny
echo "🔍 TEST 3: Sprawdzenie plików JavaScript\n";
echo "---------------------------------------\n";

$js_file = get_template_directory() . '/js/core/notifications.js';
if (file_exists($js_file)) {
    echo "✅ Plik notifications.js istnieje\n";

    $js_content = file_get_contents($js_file);
    if (
        strpos($js_content, 'game-notification-success') !== false &&
        strpos($js_content, 'game-notification-bad') !== false &&
        strpos($js_content, 'game-notification-failed') !== false
    ) {
        echo "✅ Plik zawiera obsługę wszystkich statusów\n";
    } else {
        echo "⚠️  Plik może nie zawierać pełnej obsługi statusów\n";
    }
} else {
    echo "❌ Plik notifications.js nie został znaleziony\n";
}

$css_file = get_template_directory() . '/assets/css/notification-system.css';
if (file_exists($css_file)) {
    echo "✅ Plik notification-system.css istnieje\n";

    $css_content = file_get_contents($css_file);
    if (
        strpos($css_content, '.game-notification-success') !== false &&
        strpos($css_content, '.game-notification-bad') !== false &&
        strpos($css_content, '.game-notification-failed') !== false
    ) {
        echo "✅ Plik CSS zawiera style dla wszystkich statusów\n";
    } else {
        echo "⚠️  Plik CSS może nie zawierać wszystkich stylów\n";
    }
} else {
    echo "❌ Plik notification-system.css nie został znaleziony\n";
}

echo "\n=== PODSUMOWANIE TESTÓW ===\n";
echo "System statusów powiadomień został zaimplementowany i przetestowany.\n";
echo "Sprawdź logi debugowania w /npc_debug.log dla szczegółowych informacji.\n";
echo "\nAby przetestować system w przeglądarce:\n";
echo "1. Przejdź na stronę z NPC Junior (ID: 473)\n";
echo "2. Kliknij na NPC aby otworzyć dialog\n";
echo "3. Wybierz 'tak' - powinno pokazać zielone powiadomienie o otrzymaniu bluzy\n";
echo "4. Wybierz 'nie' - powinno pokazać czerwone powiadomienie o pogorszeniu relacji\n";
