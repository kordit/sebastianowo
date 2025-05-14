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

#### Integracja systemu notyfikacji z API

System notyfikacji jest dostępny globalnie jako obiekt `window.gameNotifications`, co pozwala na łatwą integrację z kodem obsługującym API:

```javascript
// Przykład integracji z kodem API
async function handleUserAction() {
    try {
        // Próba wykonania operacji przez API
        const response = await axios.post('/wp-json/game/v1/user-action', {
            // dane
        });
        
        // Obsługa sukcesu
        if (response.data.success) {
            window.gameNotifications.show(response.data.message, 'success');
            // Aktualizuj UI lub wykonaj inne operacje
            return true;
        } else {
            // Obsługa błędu biznesowego
            window.gameNotifications.show(response.data.message, 'bad');
            return false;
        }
    } catch (error) {
        // Obsługa błędu połączenia lub wyjątku
        let errorMessage = 'Wystąpił nieznany błąd';
        
        if (error.response) {
            // Błąd z odpowiedzią serwera (status kod inny niż 2xx)
            if (error.response.status === 401) {
                errorMessage = 'Sesja wygasła. Zaloguj się ponownie.';
                // Automatyczne przekierowanie do strony logowania po 2 sekundach
                setTimeout(() => {
                    window.location.href = '/login';
                }, 2000);
            } else if (error.response.status === 403) {
                errorMessage = 'Brak uprawnień do wykonania tej operacji.';
            } else if (error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
            }
        } else if (error.request) {
            // Brak odpowiedzi z serwera
            errorMessage = 'Brak odpowiedzi z serwera. Sprawdź połączenie internetowe.';
        } else {
            // Błąd w konfiguracji żądania
            errorMessage = `Błąd aplikacji: ${error.message}`;
        }
        
        // Wyświetl komunikat błędu
        window.gameNotifications.show(errorMessage, 'failed');
        
        // Opcjonalnie zapisz błąd do konsoli dla deweloperów
        console.error('API Error:', error);
        
        return false;
    }
}
```

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

## 🛡️ Walidacja danych użytkownika

Walidacja danych użytkownika jest kluczowym elementem bezpieczeństwa aplikacji. Poniżej znajdują się najważniejsze zasady walidacji danych w projektach Game:

### 🔒 Backend (PHP)

```php
// Przykład walidacji danych wejściowych w klasie ManagerUser
public function updateStat(string $stat_name, $value): array
{
    // 1. Walidacja nazwy statystyki - czy jest dozwolona
    if (!in_array($stat_name, $this->allowed_stats)) {
        return [
            'success' => false,
            'message' => 'Nieprawidłowa nazwa statystyki'
        ];
    }

    // 2. Walidacja typu danych
    if (!is_numeric($value)) {
        return [
            'success' => false, 
            'message' => 'Wartość statystyki musi być liczbą'
        ];
    }

    // 3. Walidacja zakresu wartości
    $value = intval($value);
    if ($value < 0 || $value > 100) {
        return [
            'success' => false,
            'message' => 'Wartość statystyki musi być w zakresie 0-100'
        ];
    }

    // 4. Walidacja uprawnień użytkownika
    if (!$this->canUpdateStat($stat_name)) {
        return [
            'success' => false,
            'message' => 'Brak uprawnień do aktualizacji tej statystyki'
        ];
    }

    // Dane są poprawne - kontynuuj aktualizację
    // ...
}
```

### 🔍 Frontend (JavaScript)

Walidacja na frontendzie służy głównie do poprawy UX, ale nie zastępuje walidacji na backendzie:

```javascript
// Przykład walidacji formularza przed wysłaniem do API
function validateStatUpgrade(statName, value) {
    const errors = [];
    
    // 1. Walidacja wymaganych pól
    if (!statName || !value) {
        errors.push('Wszystkie pola są wymagane');
    }
    
    // 2. Walidacja typu danych
    if (isNaN(value)) {
        errors.push('Wartość musi być liczbą');
    }
    
    // 3. Walidacja zakresu
    const numValue = parseInt(value);
    if (numValue < 0 || numValue > 100) {
        errors.push('Wartość musi być w zakresie 0-100');
    }
    
    // 4. Sprawdź czy gracz ma wystarczającą ilość punktów
    const availablePoints = getAvailablePoints();
    if (numValue > availablePoints) {
        errors.push('Niewystarczająca liczba dostępnych punktów');
    }
    
    return {
        isValid: errors.length === 0,
        errors: errors
    };
}

// Użycie walidacji przed wysłaniem do API
function handleStatUpgrade(event) {
    const statName = event.target.dataset.stat;
    const value = document.getElementById('stat-value').value;
    
    const validation = validateStatUpgrade(statName, value);
    if (!validation.isValid) {
        // Wyświetl błędy
        validation.errors.forEach(error => {
            gameNotifications.show(error, 'failed');
        });
        return;
    }
    
    // Wyślij dane do API
    userManager.updateStat(statName, value)
        .then(response => {
            // Obsługa odpowiedzi
        });
}
```

### ✅ Najważniejsze zasady walidacji

1. **Zawsze waliduj dane dwukrotnie**: na frontendzie dla lepszego UX oraz na backendzie dla bezpieczeństwa
2. **Nigdy nie ufaj danym wejściowym** od użytkownika, nawet jeśli pochodzą z formularzy
3. **Stosuj białe listy** dla dozwolonych wartości zamiast czarnych list
4. **Sanityzuj dane wejściowe** przed zapisem do bazy danych:
   - `sanitize_text_field()` dla tekstu jednoliniowego
   - `sanitize_textarea_field()` dla pól tekstowych wieloliniowych
   - `absint()` dla liczb całkowitych nieujemnych
   - `intval()` dla dowolnych liczb całkowitych
5. **Używaj przygotowanych zapytań** (prepared statements) dla wszelkich operacji na bazie danych
6. **Escapuj dane wyjściowe** przed wyświetleniem:
   - `esc_html()` dla tekstu w HTML
   - `esc_url()` dla adresów URL
   - `esc_attr()` dla atrybutów HTML
7. **Loguj nieudane walidacje** do wykrywania potencjalnych ataków
8. **Stosuj regularne wyrażenia** dla danych o strukturalnych wymaganiach (np. kody pocztowe, numery telefonów)
9. **Zwracaj czytelne komunikaty o błędach** dla użytkownika, ale bez ujawniania technicznych szczegółów
10. **Sprawdzaj uprawnienia** użytkownika przed wykonaniem operacji na danych

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

### 🔐 Rejestracja i walidacja endpointów REST API

Poprawna rejestracja endpointu REST API powinna zawierać:

```php
// Rejestracja endpointu REST API
register_rest_route('game/v1', '/update-stat', [
    'methods'             => 'POST',
    'callback'            => [$this, 'handleStatUpdate'],
    'permission_callback' => [$this, 'checkPermission'],
    'args'                => [
        'stat_name' => [
            'required'          => true,
            'validate_callback' => function($param) {
                return is_string($param) && in_array($param, ['strength', 'dexterity', 'intelligence', 'charisma']);
            },
            'sanitize_callback' => 'sanitize_text_field'
        ],
        'value' => [
            'required'          => true,
            'validate_callback' => function($param) {
                return is_numeric($param) && $param >= 0 && $param <= 100;
            },
            'sanitize_callback' => 'absint'
        ]
    ]
]);

// Sprawdzenie uprawnień
public function checkPermission(\WP_REST_Request $request) {
    // Sprawdź czy użytkownik jest zalogowany
    if (!is_user_logged_in()) {
        return new \WP_Error(
            'rest_forbidden',
            __('Musisz być zalogowany, aby wykonać tę operację.', 'game'),
            ['status' => 401]
        );
    }

    // Sprawdź nonce
    $nonce = $request->get_header('X-WP-Nonce');
    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        return new \WP_Error(
            'rest_cookie_invalid_nonce',
            __('Nieprawidłowy token bezpieczeństwa.', 'game'),
            ['status' => 403]
        );
    }

    return true;
}
```

### 📝 Poprawna obsługa błędów REST API

```php
// Obsługa żądania z prawidłową walidacją i komunikatami błędów
public function handleStatUpdate(\WP_REST_Request $request) {
    try {
        // Pobierz dane z żądania (już zwalidowane przez args)
        $stat_name = $request->get_param('stat_name');
        $value = $request->get_param('value');
        $user_id = get_current_user_id();

        // Logger dla debugowania
        $logger = null;
        if (class_exists('GameLogger')) {
            $logger = new GameLogger();
            $logger->log("Próba aktualizacji statystyki $stat_name na $value dla użytkownika $user_id");
        }

        // Pobierz menedżera użytkownika
        $user_manager = new ManagerUser($user_id);
        
        // Wykonaj aktualizację statystyki
        $result = $user_manager->updateStat($stat_name, $value);

        // Sprawdź wynik
        if ($result['success']) {
            if ($logger) {
                $logger->log("Aktualizacja statystyki zakończona sukcesem");
            }
            
            return new \WP_REST_Response([
                'success' => true,
                'message' => $result['message'] ?? 'Statystyka została zaktualizowana',
                'new_stat_value' => $result['new_value'] ?? $value
            ], 200);
        } else {
            if ($logger) {
                $logger->log("Błąd aktualizacji statystyki: " . ($result['message'] ?? 'Nieznany błąd'));
            }
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result['message'] ?? 'Nie udało się zaktualizować statystyki'
            ], 400);
        }
    } catch (\Exception $e) {
        if (isset($logger)) {
            $logger->log("Wyjątek podczas aktualizacji statystyki: " . $e->getMessage());
        }
        
        return new \WP_REST_Response([
            'success' => false,
            'message' => 'Wystąpił błąd podczas przetwarzania żądania'
        ], 500);
    }
}

---

Pamiętaj: Ten projekt jest motywem gry RPG w WordPress, który wymaga specjalistycznej wiedzy z zakresu WordPress, PHP, JavaScript i systemów gier. Kod powinien być wydajny, bezpieczny i modułowy.