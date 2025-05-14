---
applyTo: '**'
---
# 🎮 Prompt ekspertowy dla motywu gry WordPress

## 🎯 O projekcie
Pracujesz nad zaawansowanym motywem WordPress dla gry RPG "Game", który oferuje:

- System kont użytkowników i logowania
- Interaktywne postacie NPC z dialogami
- System misji i zadań dla graczy
- Ekwipunek (plecak) i zarządzanie przedmiotami
- System wyboru i zarządzania terenami gry

## 🔍 Struktura projektu

Motyw "Game" to zaawansowany motyw WordPress zaprojektowany specjalnie do tworzenia interaktywnych gier z elementami RPG. Posiada rozbudowany system zarządzania użytkownikami, notyfikacje, system dialogów NPC oraz inwentarz.

Projekt organizuje kod w następujące katalogi:

- **`/includes/class/`**: Klasy PHP do zarządzania logiką biznesową (ManagerUser, DialogHandler, NpcChecker)
- **`/includes/functions/`**: Funkcje pomocnicze i hooki WordPress
- **`/includes/core/`**: Podstawowe funkcje globalne i stałe
- **`/includes/register-cpt/`**: Rejestracja niestandardowych typów postów (NPC, misje, przedmioty)
- **`/js/`**: Skrypty JavaScript podzielone według funkcji
- **`/js/core/`**: Podstawowe funkcjonalności (notyfikacje, UI helpers)
- **`/js/modules/`**: Skrypty dla poszczególnych modułów funkcjonalnych
- **`/assets/`**: Obrazy, style CSS/SCSS i zasoby zewnętrzne
- **`/assets/css/`**: Style CSS i SCSS
- **`/assets/js/vendors/`**: Zewnętrzne biblioteki (Axios, Alpine.js)
- **`/page-templates/`**: Szablony stron dla różnych sekcji gry
- **`/acfe-php/`**: Definicje pól ACF (Advanced Custom Fields)

## 🚨 NAJWAŻNIEJSZE ZASADY

### ⛔️ BEZWZGLĘDNY ZAKAZ UŻYCIA JQUERY
W tym projekcie obowiązuje **bezwzględny zakaz używania jQuery**. Zasady:

1. **NIE UŻYWAJ jQuery** w nowym kodzie.
2. Jeśli znajdziesz kod używający jQuery, natychmiast zgłoś to użytkownikowi.
3. Zawsze proponuj natywne rozwiązania JavaScript zamiast jQuery.
4. Przy każdej modyfikacji kodu, sprawdź czy nie zawiera zależności od jQuery.
5. Usuwaj referencje do jQuery z zależności skryptów.

Zamiast jQuery używaj:
- Selektory: `document.querySelector()` lub `document.querySelectorAll()` zamiast `$('selector')`
- AJAX: **Axios** (dostępny w `/assets/js/vendors/axios.min.js`) lub natywny `fetch()` API zamiast `$.ajax()`
- Manipulacja DOM: natywne metody DOM lub **Alpine.js** zamiast metod jQuery
- Obsługa zdarzeń: `addEventListener()` zamiast `$.on()`

#### Axios (dla zapytań HTTP)
```javascript
// Przykład użycia Axios dla zapytań GET
axios.get('/api/endpoint')
  .then(response => {
    // Obsługa odpowiedzi
    console.log(response.data);
  })
  .catch(error => {
    // Obsługa błędów
    console.error(error);
  });

// Przykład użycia Axios dla zapytań POST
axios.post('/api/endpoint', {
  key: 'value',
  anotherKey: 'anotherValue'
})
  .then(response => {
    // Obsługa odpowiedzi
  });
```

#### Alpine.js (dla interaktywnego UI)
```html
<!-- Przykład użycia Alpine.js -->
<div x-data="{ open: false }">
  <button @click="open = !open">Przełącz</button>
  <div x-show="open">Zawartość widoczna po kliknięciu</div>
</div>
```

Inicjalizacja komponentów Alpine.js powinna odbywać się w pliku `/assets/js/alpine-init.js`.

### 🔄 Standardy kodowania

#### PHP (7.4+)
- Używaj typowania danych tam, gdzie to możliwe
- Grupuj powiązane funkcje w klasy
- Stosuj konsekwentnie hooki WordPress
- Sanityzuj dane wejściowe, escapuj dane wyjściowe
- Używaj prefixów dla nazw funkcji i klas (najczęściej `game_` lub `Game_`)
- Preferuj API WordPress zamiast bezpośrednich zapytań SQL

#### JavaScript (ES6+)
- Używaj `const` i `let` zamiast `var`
- Korzystaj z funkcji strzałkowych
- Stosuj `async/await` zamiast zagnieżdżonych callbacków
- Organizuj kod w moduły według funkcjonalności
- Obsługuj odpowiednio błędy i wyjątki

#### CSS/SCSS
- Stosuj metodologię BEM dla nazywania klas CSS
- Organizuj style w komponenty
- Używaj zmiennych SCSS dla kolorów, fontów i innych powtarzalnych wartości
- Ograniczaj zagnieżdżanie selektorów do 3-4 poziomów

## 🔧 Kluczowe komponenty systemu

### 📱 System notyfikacji
System powiadomień dla użytkownika (w `/js/core/notifications.js`):

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

System powiadomień obsługuje różne typy powiadomień:
- `success` - powiadomienie o sukcesie (zielone)
- `bad` - powiadomienie ostrzegawcze (żółte)
- `failed` - powiadomienie o błędzie (czerwone)
- `neutral` - neutralne powiadomienie (szare)

Style notyfikacji znajdują się w pliku `/assets/css/notification-system.css`.

### 👤 Klasa ManagerUser (PHP)
Klasa `ManagerUser` znajduje się w `/includes/class/ManagerUser.php` i służy do zarządzania statystykami i polami użytkownika w grze. Umożliwia modyfikację wartości pól ACF z odpowiednią walidacją i komunikatami zwrotnymi.

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

#### Główne funkcjonalności:

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

### 🗣️ System dialogów (DialogHandler)
Obsługa rozmów z postaciami NPC (w `/includes/class/DialogHandler.php`):

- Wyświetlanie dialogów z opcjami wyboru
- Obsługa warunków widoczności dialogów
- Powiązanie dialogów z akcjami (nagrody, zmiany relacji)

### 🗺️ Tereny i lokacje
System zarządzania lokacjami w grze (w `/includes/register-cpt/tereny.php`):

- Definiowanie nowych obszarów
- Określanie wymagań dostępu
- Łączenie terenów z NPC i misjami

### 📦 API użytkownika (user-manager-api.js)

Plik `js/utils/user-manager-api.js` zawiera zestaw funkcji do komunikacji z endpointami REST API klasy ManagerUser.

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

## 📋 Dobre praktyki

- **Bezpieczeństwo**: Zawsze waliduj dane wejściowe od użytkownika
- **Wydajność**: Optymalizuj kod i zapytania do bazy danych
- **Czystość kodu**: Stosuj się do standardów kodowania WordPress
- **Modułowość**: Utrzymuj modułową strukturę kodu
- **Komentarze**: Dokumentuj skomplikowane fragmenty kodu

## 🧩 Referencje do istniejącego kodu

Przy tworzeniu nowej funkcjonalności wzoruj się na:
- Funkcje dla NPC: `inc/functions/npc_dialogs.php`
- Funkcje dla ekwipunku: `page-templates/plecak/functions.php`
- Obsługa terenów: `page-templates/tereny/template.php`
- Styl komponentów: pliki SCSS w katalogach komponentów

## 🔗 Używanie REST API

System wykorzystuje REST API WordPress do komunikacji między frontendem a backendem:

```javascript
// Przykład użycia Axios z REST API WordPress
axios.post('/wp-json/game/v1/user/update-stat', {
    stat: 'strength',
    value: 5,
    _wpnonce: gameData.nonce // Nonce dla bezpieczeństwa
})
.then(response => {
    if (response.data.success) {
        notifications.show(response.data.message, 'success');
    }
})
.catch(error => {
    notifications.show('Wystąpił błąd', 'failed');
    console.error(error);
});
```

---

Pamiętaj: Ten projekt jest motywem gry RPG w WordPress, który wymaga specjalistycznej wiedzy z zakresu WordPress, PHP, JavaScript i systemów gier. Kod powinien być wydajny, bezpieczny i modułowy.