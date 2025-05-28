# Refaktoryzacja NpcPopup i DialogFilter

## Zmiany wprowadzone

### 1. Utworzenie klasy DialogFilter
- **Plik**: `DialogFilter.php`
- **Odpowiedzialność**: Filtrowanie i zarządzanie dialogami NPC
- **Funkcje**:
  - Pobieranie dialogów z ACF (`get_npc_dialogs()`)
  - Znajdowanie pierwszego dostępnego dialogu (`get_first_available_dialog()`)
  - Znajdowanie dialogu po ID z filtrowaniem (`get_dialog_by_id()`)
  - Sprawdzanie widoczności dialogów (`is_dialog_visible()`)
  - Filtrowanie odpowiedzi w dialogach (`filter_dialog_answers()`)
  - Wszystkie metody sprawdzania warunków (statystyki, umiejętności, przedmioty, itp.)

### 2. Refaktoryzacja klasy NpcPopup
- **Usunięto**: Wszystkie metody związane z filtrowaniem dialogów
- **Dodano**: Właściwość `$dialog_filter` typu `DialogFilter`
- **Zmieniono**: Metody używają teraz `DialogFilter` zamiast bezpośredniego wywołania `get_field('dialogs')`

## Korzyści z refaktoryzacji

### 1. Separacja odpowiedzialności
- `NpcPopup`: Obsługa endpointów REST API i logika biznesowa
- `DialogFilter`: Filtrowanie dialogów i warunków widoczności

### 2. Reużywalność
- Klasa `DialogFilter` może być używana w innych częściach systemu
- Łatwiejsze testowanie poszczególnych komponentów

### 3. Maintainability
- Kod jest bardziej zorganizowany i łatwiejszy w utrzymaniu
- Zmiany w logice filtrowania nie wpływają na logikę endpointów

## Sposób użycia

### Podstawowe użycie DialogFilter
```php
// Stworzenie instancji dla konkretnego użytkownika
$dialog_filter = new DialogFilter($user_id, $page_data);

// Pobranie pierwszego dostępnego dialogu
$first_dialog = $dialog_filter->get_first_available_dialog($npc_id);

// Pobranie konkretnego dialogu po ID
$dialog = $dialog_filter->get_dialog_by_id($npc_id, $dialog_id);

// Sprawdzenie czy dialog jest widoczny
$is_visible = $dialog_filter->is_dialog_visible($dialog);
```

### Zmiana kontekstu
```php
$dialog_filter = new DialogFilter();

// Zmiana użytkownika
$dialog_filter->set_user_id($new_user_id);

// Zmiana danych strony (dla warunków lokalizacji)
$dialog_filter->set_page_data($new_page_data);
```

### Pobieranie surowych dialogów (dla akcji)
```php
// Do znajdowania dialogów bez filtrowania widoczności (np. dla execute_answer_actions)
$raw_dialog = $dialog_filter->find_dialog_by_id_raw($npc_id, $dialog_id);
```

## Zmiany w istniejącym kodzie

### Przed refaktoryzacją
```php
// Bezpośrednie pobieranie dialogów
$dialogs = get_field('dialogs', $npc_id);
$first_dialog = $this->find_first_available_dialog($dialogs, $user_id, $page_data);
```

### Po refaktoryzacji
```php
// Używanie DialogFilter
$this->dialog_filter = new DialogFilter($user_id, $page_data);
$first_dialog = $this->dialog_filter->get_first_available_dialog($npc_id);
```

## Testowanie

### Testowanie DialogFilter
```php
// Test podstawowej funkcjonalności
$filter = new DialogFilter($test_user_id);
$dialogs = $filter->get_npc_dialogs($npc_id);
$this->assertIsArray($dialogs);

// Test filtrowania
$first_dialog = $filter->get_first_available_dialog($npc_id);
$this->assertArrayHasKey('id_pola', $first_dialog);
```

## Backward compatibility
- Wszystkie istniejące endpointy REST API działają bez zmian
- Struktura odpowiedzi API pozostaje taka sama
- Logika filtrowania dialogów jest identyczna
