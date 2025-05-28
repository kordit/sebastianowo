# Instrukcja instalacji systemu zarządzania grą RPG

## Wymagania wstępne

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+ lub MariaDB 10.2+
- Aktywny motyw WordPress
- Opcjonalnie: ACF (Advanced Custom Fields) dla builderów danych

## Krok 1: Instalacja plików

1. Skopiuj całą zawartość folderu `db-tables` do:
   ```
   /wp-content/themes/[nazwa-motywu]/includes/db-tables/
   ```

2. Struktura powinna wyglądać tak:
   ```
   wp-content/themes/game/includes/db-tables/
   ├── game-system-init.php
   ├── GameDatabaseManager.php
   ├── GameDeltaManager.php
   ├── GameUserRepository.php
   ├── GameMissionManager.php
   ├── GameAdminPanel.php
   ├── GameDataBuilder.php
   ├── README.md
   ├── INSTALL.md
   └── game-examples.php
   ```

## Krok 2: Integracja z motywem

Dodaj ten kod do `functions.php` swojego motywu:

```php
<?php
// Inicjalizacja systemu gry RPG
require_once get_template_directory() . '/includes/db-tables/game-system-init.php';

// Automatyczne tworzenie gracza przy pierwszym logowaniu
add_action('wp_login', function($user_login, $user) {
    $userRepo = game_get_user_repository();
    if (!$userRepo->playerExists($user->ID)) {
        $userRepo->createPlayer($user->ID, [
            'nickname' => $user->display_name,
            'character_class' => 'beginner'
        ]);
    }
}, 10, 2);

// Hook do sprawdzania wygasłych misji (opcjonalnie)
add_action('wp_loaded', function() {
    if (class_exists('GameMissionManager')) {
        $missionManager = game_get_mission_manager();
        $missionManager->checkExpiredMissions();
    }
});
```

## Krok 3: Utworzenie bazy danych

1. Zaloguj się do panelu administracyjnego WordPress
2. Przejdź do **Gra RPG → Baza danych**
3. Kliknij **"Utwórz wszystkie tabele"**
4. Sprawdź czy wszystkie tabele zostały utworzone (zielone checkmarki)

## Krok 4: Weryfikacja instalacji

### Sprawdzenie przez panel administracyjny

1. Przejdź do **Gra RPG → Gracze**
2. Wybierz użytkownika testowego
3. Sprawdź czy dane się ładują
4. Spróbuj zaktualizować statystyki

### Sprawdzenie programistyczne

Dodaj ten kod do testowego pliku PHP:

```php
<?php
// Test basic functionality
$status = game_system_check();

if (empty($status['errors'])) {
    echo "✓ System działa poprawnie!";
} else {
    echo "✗ Problemy z systemem:";
    foreach ($status['errors'] as $error) {
        echo "\n- " . $error;
    }
}
```

## Krok 5: Budowanie danych (opcjonalnie)

Jeśli używasz ACF i masz już istniejące dane:

1. Przejdź do **Gra RPG → Budowanie danych**
2. Kliknij **"Zbuduj przedmioty z ACF"**
3. Kliknij **"Zbuduj rejony z CPT"**
4. Kliknij **"Zbuduj relacje NPC"**

## Konfiguracja dla różnych środowisk

### Środowisko deweloperskie

Dodaj do `wp-config.php`:

```php
// Włącz debug dla systemu gry
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Środowisko produkcyjne

Upewnij się, że w `wp-config.php`:

```php
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
```

## Rozwiązywanie problemów

### Problem: "Table doesn't exist"

**Rozwiązanie:**
1. Sprawdź uprawnienia bazy danych
2. Przejdź do panelu **Gra RPG → Baza danych**
3. Kliknij ponownie **"Utwórz wszystkie tabele"**

### Problem: "Class not found"

**Rozwiązanie:**
1. Sprawdź czy `game-system-init.php` jest poprawnie załadowany w `functions.php`
2. Sprawdź ścieżki do plików
3. Sprawdź czy wszystkie pliki PHP zostały przesłane

### Problem: Błędy delta systemu

**Rozwiązanie:**
1. Sprawdź czy kolumny istnieją w tabelach
2. Sprawdź format danych (numeryczne wartości)
3. Sprawdź limity min/max

### Problem: Panel administracyjny nie widoczny

**Rozwiązanie:**
1. Sprawdź czy jesteś zalogowany jako administrator
2. Sprawdź czy `GameAdminPanel.php` został załadowany
3. Sprawdź error_log WordPress

## Testowanie funkcjonalności

### Test 1: Podstawowe operacje

```php
$userId = 1; // Zamień na istniejący ID użytkownika
$userRepo = game_get_user_repository();

// Test tworzenia gracza
if (!$userRepo->playerExists($userId)) {
    $result = $userRepo->createPlayer($userId);
    var_dump($result);
}

// Test aktualizacji statystyk
$result = $userRepo->updateStats($userId, ['strength' => 10]);
var_dump($result);
```

### Test 2: System delta

```php
$deltaManager = game_get_delta_manager();

// Test dodawania doświadczenia
$result = $deltaManager->increase(
    'game_user_progress',
    ['user_id' => $userId],
    'experience',
    100
);
var_dump($result);
```

### Test 3: System misji

```php
$missionManager = game_get_mission_manager();

// Test dodawania misji
$result = $missionManager->addMissionForPlayer($userId, 'test_mission', 'available');
var_dump($result);

// Test listy misji
$missions = $missionManager->getPlayerMissions($userId);
var_dump($missions);
```

## Konserwacja

### Regularne sprawdzanie

1. Raz w tygodniu sprawdź logi błędów WordPress
2. Sprawdź wydajność zapytań SQL w bazie danych
3. Sprawdź czy nie ma wygasłych misji do wyczyszczenia

### Aktualizacje

System jest zaprojektowany jako samowystarczalny. Przyszłe aktualizacje:

1. Pobierz nowe pliki
2. Zamień stare pliki (zachowaj backup)
3. Przejdź do panelu bazy danych i sprawdź tabele
4. Przetestuj funkcjonalność

## Support i dokumentacja

- **README.md** - Pełna dokumentacja API
- **game-examples.php** - Przykłady użycia
- **Panel administracyjny** - Interface do zarządzania

## Bezpieczeństwo

System zawiera:
- ✅ Sanityzację danych wejściowych
- ✅ Prepared statements SQL
- ✅ Nonce verification dla formularzy
- ✅ Walidację uprawnień
- ✅ Error handling i logging

Pamiętaj o regularnych backupach bazy danych!

---

**UWAGA:** Nie usuwaj pliku `game-examples.php` w środowisku deweloperskim - zawiera przydatne przykłady. W produkcji możesz go usunąć.
