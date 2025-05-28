# System zarządzania danymi gry RPG

## Opis systemu

Kompletny system zarządzania danymi graczy w WordPress RPG z niestandardowymi tabelami bazy danych, systemem delta dla bezpiecznych zmian wartości oraz panelem administracyjnym.

## Funkcjonalności

### 🎯 Główne komponenty

- **GameDatabaseManager** - Zarządzanie schematem bazy danych i tabelami
- **GameDeltaManager** - System delta dla bezpiecznych zmian wartości 
- **GameUserRepository** - CRUD dla danych graczy
- **GameMissionManager** - Zarządzanie misjami i zadaniami
- **GameAdminPanel** - Panel administracyjny WordPress
- **GameDataBuilder** - Budowanie struktur z danych ACF

### 📊 Tabele w bazie danych

1. `game_users` - Główne dane graczy
2. `game_user_stats` - Statystyki (siła, wytrzymałość, etc.)
3. `game_user_skills` - Umiejętności
4. `game_user_progress` - Postęp (poziom, doświadczenie)
5. `game_user_vitality` - Żywotność (HP, MP, energia)
6. `game_user_items` - Przedmioty graczy
7. `game_user_areas` - Odblokowane rejony
8. `game_user_fight_tokens` - Tokeny walk
9. `game_user_relations` - Relacje z NPC
10. `game_user_story` - Historia gracza
11. `game_user_missions` - Misje graczy
12. `game_user_mission_tasks` - Zadania misji

### 🔧 System Delta

System bezpiecznych zmian wartości z ograniczeniami:
- Atomowe transakcje
- Kontrola min/max
- Zabezpieczenie przed wyścigami

### 🎮 Panel administracyjny

- Zarządzanie bazą danych
- Edytor graczy
- Panel misji
- Budowanie danych z ACF

## Instalacja

### 1. Integracja z motywem

Dodaj do `functions.php` twojego motywu:

```php
// Inicjalizacja systemu gry
require_once get_template_directory() . '/includes/db-tables/game-system-init.php';

// Opcjonalnie: Automatyczne tworzenie gracza przy logowaniu
add_action('wp_login', function($user_login, $user) {
    $userRepo = new GameUserRepository();
    if (!$userRepo->playerExists($user->ID)) {
        $userRepo->createPlayer($user->ID);
    }
}, 10, 2);
```

### 2. Utworzenie tabel

Przejdź do **WordPress Admin → Gra RPG → Baza danych** i kliknij "Utwórz wszystkie tabele".

### 3. Zbudowanie danych

W **Gra RPG → Budowanie danych**:
- Zbuduj przedmioty z ACF
- Zbuduj rejony z CPT
- Zbuduj relacje NPC

## Użycie w kodzie

### Podstawowe operacje

```php
// Pobranie repozytorium
$userRepo = new GameUserRepository();

// Sprawdzenie istnienia gracza
if (!$userRepo->playerExists($userId)) {
    $userRepo->createPlayer($userId);
}

// Aktualizacja statystyk
$userRepo->updateStats($userId, [
    'strength' => 10,
    'endurance' => 8
]);

// System delta - bezpieczne dodawanie doświadczenia
$deltaManager = new GameDeltaManager();
$deltaManager->increase('game_user_progress', 
    ['user_id' => $userId], 
    'experience', 
    100
);
```

### Zarządzanie misjami

```php
$missionManager = new GameMissionManager();

// Dodanie misji
$missionManager->addMissionForPlayer($userId, 'mission_001', 'available');

// Rozpoczęcie misji
$missionManager->startMission($userId, 'mission_001');

// Zakończenie misji
$missionManager->completeMission($userId, 'mission_001');
```

### Zarządzanie przedmiotami

```php
// Dodanie przedmiotu
$userRepo->addItem($userId, 'sword_001', 1);

// Założenie przedmiotu
$userRepo->equipItem($userId, 'sword_001', 'weapon');

// Zdjęcie przedmiotu
$userRepo->unequipItem($userId, 'sword_001');
```

## API Reference

### GameUserRepository

| Metoda | Opis |
|--------|------|
| `createPlayer($userId, $data = [])` | Tworzy nowego gracza |
| `playerExists($userId)` | Sprawdza istnienie gracza |
| `updateStats($userId, $stats)` | Aktualizuje statystyki |
| `updateSkills($userId, $skills)` | Aktualizuje umiejętności |
| `addItem($userId, $itemId, $quantity)` | Dodaje przedmiot |
| `equipItem($userId, $itemId, $slot)` | Zakłada przedmiot |
| `unlockArea($userId, $areaId, $sceneId)` | Odblokuje rejon |

### GameDeltaManager

| Metoda | Opis |
|--------|------|
| `increase($table, $where, $column, $value, $limits = [])` | Zwiększa wartość |
| `decrease($table, $where, $column, $value, $limits = [])` | Zmniejsza wartość |
| `set($table, $where, $column, $value, $limits = [])` | Ustawia wartość |

### GameMissionManager

| Metoda | Opis |
|--------|------|
| `getPlayerMissions($userId)` | Pobiera misje gracza |
| `addMissionForPlayer($userId, $missionId, $status)` | Dodaje misję |
| `startMission($userId, $missionId)` | Rozpoczyna misję |
| `completeMission($userId, $missionId)` | Kończy misję |

## Struktura plików

```
includes/db-tables/
├── game-system-init.php      # Autoloader i inicjalizacja
├── GameDatabaseManager.php   # Zarządzanie bazą danych
├── GameDeltaManager.php      # System delta
├── GameUserRepository.php    # Repository graczy
├── GameMissionManager.php    # Zarządzanie misjami
├── GameAdminPanel.php        # Panel administracyjny
├── GameDataBuilder.php       # Budowanie z ACF
└── README.md                 # Ta dokumentacja
```

## Troubleshooting

### Błąd: "Table doesn't exist"
- Przejdź do panelu administracyjnego i utwórz tabele
- Sprawdź uprawnienia bazy danych

### Błąd: "Class not found"
- Upewnij się, że `game-system-init.php` jest załadowany
- Sprawdź ścieżki do plików

### Błąd delta: "Column not found"
- Sprawdź czy kolumna istnieje w tabeli
- Użyj poprawnych nazw kolumn zgodnie ze schematem

## Bezpieczeństwo

- Wszystkie dane wejściowe są sanityzowane
- Używane są prepared statements
- Nonce weryfikuje akcje formularzy
- System delta zapobiega wyścigom

## Performance

- Indeksy na wszystkich kluczach obcych
- Optymalizowane zapytania JOIN
- Minimal queries przez CRUD repository
- Delta system redukuje conflicty

## Support

System jest gotowy do produkcji i zawiera:
- ✅ Kompletne tabele bazy danych
- ✅ System delta z limitami
- ✅ Panel administracyjny  
- ✅ CRUD operations
- ✅ Zarządzanie misjami
- ✅ Integrację z ACF
- ✅ Dokumentację API
