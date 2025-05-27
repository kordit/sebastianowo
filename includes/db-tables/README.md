# System Bazy Danych dla Gry

## Opis

Ten system zastępuje intensywne użycie ACF `get_field`/`update_field` customowymi tabelami MySQL dla lepszej wydajności w grze MMO.

## Struktura

### Klasy

- **`DatabaseManager.php`** - Zarządza strukturą tabel, tworzeniem i migracją
- **`GameUserModel.php`** - Model danych użytkownika, interfejs CRUD
- **`GameAdminPanel.php`** - Panel administracyjny do przeglądania danych
- **`init.php`** - Inicjalizacja systemu i automatyczna migracja

### Tabele

1. **`wp_game_users`** - Podstawowe dane gracza
   - Statystyki (siła, obrona, zręczność, etc.)
   - Witalność (życie, energia)
   - Progress (exp, punkty nauki, reputacja)

2. **`wp_game_user_skills`** - Umiejętności
   - Combat, steal, craft, trade, relations, street

3. **`wp_game_user_items`** - Ekwipunek
   - Przedmioty, ilości, założone, sloty

4. **`wp_game_user_missions`** - Misje
   - Status misji, dane zadań, progress

5. **`wp_game_user_relations`** - Relacje z NPC
   - Wartość relacji, czy poznany

6. **`wp_game_user_areas`** - Dostępne obszary
   - Lista odblokowanych lokacji

## Instalacja

System automatycznie:
1. Tworzy tabele przy pierwszym uruchomieniu
2. Inicjalizuje dane dla nowych użytkowników
3. Dodaje panel admin w WP

## Panel Administracyjny

W panelu WordPress w menu **"Dane Graczy"**:

- **Lista Graczy** - przegląd wszystkich użytkowników
- **Szczegóły Gracza** - pełne dane konkretnego gracza
- **Ustawienia Bazy** - zarządzanie tabelami

## Migracja z ACF

```php
// Migruj jednego użytkownika
GameDatabaseInit::migrate_acf_data($user_id);

// Migruj wszystkich użytkowników
GameDatabaseInit::migrate_acf_data();
```

## Użycie

```php
// Pobierz model użytkownika
$game_user = new GameUserModel($user_id);

// Sprawdź czy istnieje
if (!$game_user->exists()) {
    $game_user->initialize_new_user();
}

// Pobierz dane
$user_data = $game_user->get_user_data();
$basic_data = $game_user->get_basic_data();
$skills = $game_user->get_skills_data();

// Aktualizuj dane
$game_user->update_basic_data(['exp' => 1500]);
$game_user->update_skills_data(['combat' => 25]);
```

## Korzyści

### Wydajność
- **Było**: ~50-100 zapytań ACF na gracza
- **Jest**: 1-6 zapytań SQL na gracza

### Skalowalność  
- Atomiczne operacje UPDATE
- Lepsze indeksowanie
- Mniej locków bazy danych

### Zarządzanie
- Przejrzysty panel admin
- Łatwa migracja danych
- Kopie zapasowe tylko istotnych tabel

## Następne kroki

1. ✅ Struktura tabel i panel admin
2. 🔄 Migracja wszystkich pól ACF
3. 🔄 Aktualizacja `DialogFilter` do nowych tabel
4. 🔄 Zastąpienie wszystkich `get_field`/`update_field`
5. 🔄 Testy wydajnościowe
