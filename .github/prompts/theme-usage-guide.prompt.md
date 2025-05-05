# Instrukcja używania motywu Game

## Spis treści
1. Struktura motywu
2. System notyfikacji
3. Klasa ManagerUser
4. API użytkownika (user-manager-api.js)
5. Organizacja plików JS
6. Rozbudowa katalogu klasy

## 1. Struktura motywu

Motyw "Game" to zaawansowany motyw WordPress zaprojektowany specjalnie do tworzenia interaktywnych gier z elementami RPG. Posiada rozbudowany system zarządzania użytkownikami, notyfikacje, system dialogów NPC oraz inwentarz.

Motyw składa się z następujących głównych katalogów:
- `/class` - zawiera klasy PHP do obsługi API i zarządzania użytkownikami
- `/js` - zawiera skrypty JavaScript podzielone na kategorie
- `/inc` - zawiera funkcje pomocnicze, hooki i integracje
- `/assets` - zawiera zasoby takie jak CSS, czcionki i obrazy
- `/page-templates` - szablony stron dla różnych widoków gry
- `/template-parts` - części szablonów wielokrotnego użytku

## 2. System notyfikacji

System notyfikacji jest kluczowym elementem motywu pozwalającym na wyświetlanie komunikatów dla użytkownika.

### Jak korzystać z systemu notyfikacji:

```javascript
// Inicjalizacja systemu notyfikacji
const notifications = new NotificationSystem({
    container: 'body',         // Element, do którego będą dodawane notyfikacje
    duration: 5000,            // Czas wyświetlania w ms (domyślnie 5 sekund)
    maxNotifications: 5,       // Maksymalna liczba jednocześnie wyświetlanych powiadomień
    position: 'bottom-right'   // Pozycja powiadomień
});

// Wyświetlanie notyfikacji
notifications.show('Treść komunikatu', 'success');  // Typy: success, bad, failed, neutral
```

System powiadomień znajduje się w `/js/core/notifications.js` i obsługuje różne typy powiadomień:
- `success` - powiadomienie o sukcesie (zielone)
- `bad` - powiadomienie ostrzegawcze (żółte)
- `failed` - powiadomienie o błędzie (czerwone)
- `neutral` - neutralne powiadomienie (szare)

Style notyfikacji znajdują się w pliku `/assets/css/notification-system.css`.

## 3. Klasa ManagerUser (PHP)

Klasa `ManagerUser` znajduje się w `/class/ManagerUser.php` i służy do zarządzania statystykami i polami użytkownika w grze. Umożliwia modyfikację wartości pól ACF z odpowiednią walidacją i komunikatami zwrotnymi.

### Główne funkcjonalności:

1. **Zarządzanie statystykami użytkownika**:
   - Aktualizacja parametrów statystyk (np. siła, zręczność)
   - Aktualizacja umiejętności
   - Zarządzanie zdrowiem i wytrzymałością (vitality)
   - Śledzenie postępów

2. **Zarządzanie relacjami z NPC**:
   - Zmiana stosunków z postaciami niezależnymi
   - Odblokowanie nowych dialogów

3. **Zarządzanie rejonami mapy**:
   - Dodawanie dostępnych rejonów
   - Usuwanie dostępnych rejonów
   - Ustawianie aktualnego rejonu

4. **Zarządzanie przedmiotami**:
   - Dodawanie przedmiotów do inwentarza
   - Usuwanie przedmiotów z inwentarza
   - Sprawdzanie ilości przedmiotów

5. **Obsługa REST API**:
   - Endpointy do aktualizacji pól użytkownika
   - Endpointy do pobierania danych użytkownika

### Przykład wykorzystania w PHP:

```php
// Inicjalizacja managera dla aktualnego użytkownika
$user_manager = new ManagerUser();

// Lub dla konkretnego użytkownika
$user_manager = new ManagerUser(123);

// Aktualizacja statystyki
$result = $user_manager->updateStat('strength', 5);

// Sprawdzenie czy operacja się powiodła
if ($result['success']) {
    // Operacja zakończona powodzeniem
} else {
    // Wystąpił błąd, $result['message'] zawiera informację o błędzie
}
```

## 4. API użytkownika (user-manager-api.js)

Plik `js/utils/user-manager-api.js` zawiera zestaw funkcji do komunikacji z endpointami REST API klasy ManagerUser.

### Główne funkcjonalności:

```javascript
// Inicjalizacja
const userManager = new UserManagerApi();

// Aktualizacja statystyki
userManager.updateStat('strength', 5)
    .then(response => {
        // Obsługa sukcesu
        // response.data zawiera odpowiedź z serwera
    })
    .catch(error => {
        // Obsługa błędu
    });

// Dodawanie przedmiotu do inwentarza
userManager.addItem(itemId, quantity)
    .then(response => {
        // Obsługa sukcesu
    });

// Aktualizacja relacji z NPC
userManager.updateNpcRelation(npcId, valueChange)
    .then(response => {
        // Obsługa sukcesu
    });

// Pobieranie danych użytkownika
userManager.getUserData()
    .then(userData => {
        // Użyj danych użytkownika
    });
```

Wszystkie funkcje API zwracają obiekty Promise, co pozwala na łatwe używanie ich w kodzie asynchronicznym.

## 5. Organizacja plików JS

**WAŻNE**: Wszystkie nowe pliki JavaScript powinny być dodawane w katalogu `/js` z zachowaniem następującej struktury:

- `/js/core` - podstawowe funkcjonalności (powiadomienia, ajax, helpers)
- `/js/modules` - moduły funkcjonalne podzielone na kategorie
- `/js/pages` - skrypty specyficzne dla poszczególnych stron
- `/js/utils` - funkcje pomocnicze i narzędziowe

Główny plik `/js/app.js` powinien importować wszystkie potrzebne moduły.

### Przykład dodawania nowego modułu:

1. Utwórz nowy plik w odpowiedniej podkategorii, np. `/js/modules/missions/mission-tracker.js`
2. Zaimportuj go w głównym pliku aplikacji `/js/app.js`

## 6. Rozbudowa katalogu klasy

**WAŻNE**: Wszystkie nowe klasy PHP powinny być umieszczane w katalogu głównym `/class`. Dodatkowo należy rozbudować katalog `.github/prompts`, który zawiera szablony i instrukcje dla rozwoju motywu.

### Proces dodawania nowej klasy PHP:

1. Utwórz nowy plik w katalogu `/class`, np. `InventoryManager.php`
2. Zachowaj spójność nazewnictwa (PascalCase dla nazw klas)
3. Dodaj odpowiednią dokumentację PHP Docblocks
4. Zarejestruj ewentualne endpointy REST API w konstruktorze klasy

### Wzór dla nowej klasy PHP:

```php
<?php

/**
 * NazwaKlasy - krótki opis klasy
 * 
 * Szczegółowy opis funkcjonalności klasy
 */
class NazwaKlasy
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        // Kod inicjalizacyjny

        // Rejestracja endpointów REST API dla Axios (jeśli potrzebne)
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Rejestracja endpointów REST API
     */
    public function register_rest_routes()
    {
        // Kod rejestrujący endpointy
    }

    // Metody klasy
}
```

## Podsumowanie

Ten motyw WordPress jest rozbudowanym systemem do tworzenia gier opartych na WP. Klasa ManagerUser stanowi rdzeń zarządzania danymi użytkownika, a system notyfikacji zapewnia interaktywne powiadomienia. Wszystkie pliki JS należy dodawać w strukturze katalogu `/js`, a klasy PHP w `/class`. Pamiętaj o rozbudowie `.github/prompts` o odpowiednie szablony i instrukcje dla rozwoju motywu.
