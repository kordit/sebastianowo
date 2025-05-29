/**
* Skrypt testowy do weryfikacji nowej funkcjonalności drag and drop
* Umieść ten plik w folderze /Users/kordiansasiela/localhost/seb.soeasy.it/public_html/wp-content/themes/game/includes/db-tables/npc/test-drag-drop.php
* i uruchom go przez przeglądarkę lub z linii komend
*/

require_once('../../../../../wp-load.php');

// Sprawdź czy użytkownik ma uprawnienia administratora
if (!current_user_can('administrator')) {
die('Dostęp zabroniony - wymagane uprawnienia administratora');
}

echo "<h1>Test funkcjonalności sortowania dialogów NPC</h1>";

// Testuj pobieranie pierwszego dialogu
function test_get_starting_dialog() {
global $wpdb;

$npc_repository = new NPC_NPCRepository();
$dialog_repository = new NPC_DialogRepository();

// Pobierz wszystkie aktywne NPC
$npcs = $npc_repository->get_all('active');

if (empty($npcs)) {
echo "<p>Brak aktywnych NPC w bazie danych.</p>";
return;
}

echo "<h2>Test wyboru dialogu początkowego</h2>";

foreach ($npcs as $npc) {
echo "<h3>NPC: {$npc->name} (ID: {$npc->id})</h3>";

// Pobierz wszystkie dialogi dla tego NPC
$all_dialogs = $dialog_repository->get_by_npc_id($npc->id);

if (empty($all_dialogs)) {
echo "<p>Brak dialogów dla tego NPC.</p>";
continue;
}

echo "<p>Dialogi w kolejności:</p>
<ol>";
    foreach ($all_dialogs as $dialog) {
    echo "<li>Dialog: {$dialog->title} (ID: {$dialog->id}, Kolejność: {$dialog->dialog_order}, czy początkowy: " . ($dialog->is_starting_dialog ? 'Tak' : 'Nie') . ")</li>";
    }
    echo "</ol>";

// Pobierz dialog początkowy według nowej logiki
$starting_dialog = $dialog_repository->get_starting_dialog($npc->id);

if ($starting_dialog) {
echo "<p><strong>Wybrany dialog początkowy:</strong> {$starting_dialog->title} (ID: {$starting_dialog->id}, Kolejność: {$starting_dialog->dialog_order})</p>";

// Sprawdź czy faktycznie jest to pierwszy dialog w kolejności
if ($starting_dialog->id === $all_dialogs[0]->id) {
echo '<p style="color: green;">TEST PASSED: Dialog początkowy jest pierwszym dialogiem w kolejności.</p>';
} else {
echo '<p style="color: red;">TEST FAILED: Dialog początkowy NIE jest pierwszym dialogiem w kolejności!</p>';
}
} else {
echo "<p>Nie znaleziono dialogu początkowego dla tego NPC.</p>";
}

echo "
<hr>";
}
}

// Uruchom testy
test_get_starting_dialog();

echo "<p>Test zakończony.</p>";