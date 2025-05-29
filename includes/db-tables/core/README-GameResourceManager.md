# GameResourceManager - Dokumentacja

## Wprowadzenie

`GameResourceManager` to centralna, bezpieczna klasa do zarządzania wszystkimi zasobami gry. Została zaprojektowana z myślą o bezpieczeństwie, walidacji danych i kontroli dostępu.

## Główne funkcje

### 🔒 Bezpieczeństwo
- **Autoryzacja wielopoziomowa**: user, moderator, admin
- **Walidacja danych** przed każdą operacją
- **Kontrola dostępu** do zasobów innych użytkowników
- **Logowanie operacji** dla auditowania

### 🛡️ Walidacja zasobów
- **Limity minimalne i maksymalne** dla każdego zasobu
- **Sprawdzanie dostępności** przed odejmowaniem
- **Walidacja integralności** danych

### 📊 Zarządzane zasoby
- Złoto (gold)
- Papierosy (cigarettes)
- Doświadczenie (exp)
- Punkty nauki (learning_points)
- Życie (life/max_life)
- Energia (energy/max_energy)

## Poziomy autoryzacji

### 1. User (użytkownik)
- Może modyfikować tylko własne zasoby
- Dostęp do podstawowych operacji

### 2. Moderator
- Może modyfikować zasoby innych użytkowników
- Rozszerzone uprawnienia

### 3. Admin (administrator)
- Pełny dostęp do wszystkich funkcji
- Może pobierać bezpośredni dostęp do repositories
- Dostęp do logów operacji

## Podstawowe użycie

### Inicjalizacja
```php
$resourceManager = new GameResourceManager();
```

### Sprawdzenie uprawnień
```php
if ($resourceManager->canPerformOperation('modify_resources', $user_id)) {
    // Wykonaj operację
}
```

### Modyfikacja zasobów
```php
try {
    $resourceManager->modifyUserResources(
        $user_id, 
        ['gold' => 100, 'exp' => 50], 
        'Nagroda za quest'
    );
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage();
}
```

### Zarządzanie przedmiotami
```php
// Dodaj przedmiot
$resourceManager->addItemToUser($user_id, $item_id, $quantity);

// Usuń przedmiot
$resourceManager->removeItemFromUser($user_id, $item_id, $quantity);
```

## Walidacja zasobów

### Automatyczne sprawdzenia
- **Złoto**: min 0, max 999,999,999
- **Papierosy**: min 0, max 999,999
- **Doświadczenie**: min 0, max 999,999,999
- **Punkty nauki**: min 0, max 1,000
- **Życie**: min 0, max względem max_life
- **Energia**: min 0, max względem max_energy

### Przykłady walidacji
```php
// To spowoduje błąd - próba odłożenia 100 złota gdy gracz ma tylko 50
$resourceManager->modifyUserResources($user_id, ['gold' => -100]);
// Exception: "Niewystarczająca ilość zasobu gold. Aktualna: 50, próba zmiany: -100"

// To spowoduje błąd - próba ustawienia życia powyżej maksimum
$resourceManager->modifyUserResources($user_id, ['life' => 150]); // gdy max_life = 100
// Exception: "Życie nie może przekroczyć maksymalnego poziomu: 100"
```

## Bezpieczne operacje

### Sprawdzanie przed operacją
```php
// Sprawdź czy użytkownik ma wystarczająco złota
$user_data = $resourceManager->getUserData($user_id);
if ($user_data['gold'] >= $cost) {
    $resourceManager->modifyUserResources($user_id, ['gold' => -$cost]);
}
```

### Transakcje
Klasa automatycznie używa transakcji MySQL dla operacji modyfikacji zasobów:
```php
// Jeśli którakolwiek operacja się nie powiedzie, wszystko zostanie cofnięte
$resourceManager->modifyUserResources($user_id, [
    'gold' => -100,
    'exp' => 50,
    'life' => 20
]);
```

## Logowanie i audyt

### Automatyczne logowanie
Wszystkie operacje są automatycznie logowane z informacjami:
- Timestamp operacji
- ID użytkownika wykonującego
- Typ operacji
- ID użytkownika docelowego
- Poziom autoryzacji

### Dostęp do logów (tylko admin)
```php
$logs = $resourceManager->getOperationLogs();
```

## Przykłady rzeczywistego użycia

### 1. System sklepu
```php
public function buyItem($user_id, $item_id, $cost) {
    try {
        $resourceManager = new GameResourceManager();
        
        // Sprawdź zasoby
        $user_data = $resourceManager->getUserData($user_id);
        if ($user_data['gold'] < $cost) {
            throw new Exception('Niewystarczająco złota');
        }
        
        // Wykonaj transakcję
        $resourceManager->modifyUserResources($user_id, ['gold' => -$cost]);
        $resourceManager->addItemToUser($user_id, $item_id, 1);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
```

### 2. System walki
```php
public function processCombatResult($user_id, $won, $exp_gained) {
    $resourceManager = new GameResourceManager();
    
    $changes = ['exp' => $exp_gained];
    
    if ($won) {
        $changes['gold'] = 50; // Nagroda za wygraną
        $reason = 'Wygrana walka';
    } else {
        $changes['life'] = -20; // Obrażenia za przegraną
        $reason = 'Przegrana walka';
    }
    
    $resourceManager->modifyUserResources($user_id, $changes, $reason);
}
```

### 3. System leczenia
```php
public function useHealthPotion($user_id) {
    $resourceManager = new GameResourceManager();
    
    // Sprawdź czy gracz ma miksturę
    $items = $resourceManager->getUserItems($user_id);
    $has_potion = false;
    foreach ($items as $item) {
        if ($item['item_id'] == HEALTH_POTION_ID && $item['amount'] > 0) {
            $has_potion = true;
            break;
        }
    }
    
    if (!$has_potion) {
        throw new Exception('Nie masz mikstury zdrowia');
    }
    
    // Usuń miksturę i dodaj życie
    $resourceManager->removeItemFromUser($user_id, HEALTH_POTION_ID, 1);
    $resourceManager->modifyUserResources($user_id, ['life' => 50], 'Użycie mikstury');
}
```

## Najlepsze praktyki

### 1. Zawsze używaj try-catch
```php
try {
    $resourceManager->modifyUserResources($user_id, $changes);
} catch (Exception $e) {
    // Obsłuż błąd gracefully
    error_log("Resource modification failed: " . $e->getMessage());
    return false;
}
```

### 2. Sprawdzaj uprawnienia przed operacją
```php
if (!$resourceManager->canPerformOperation('modify_resources', $target_user_id)) {
    return ['error' => 'Brak uprawnień'];
}
```

### 3. Używaj opisowych powodów zmian
```php
$resourceManager->modifyUserResources(
    $user_id, 
    ['exp' => 100], 
    'Wykonanie misji: Dostawa paczki do Starego Joe'
);
```

### 4. Waliduj dane wejściowe
```php
$amount = intval($_POST['amount']);
if ($amount <= 0) {
    throw new Exception('Nieprawidłowa ilość');
}
```

## Integracja z WordPress

### Ajax handlery
```php
function handle_game_action() {
    if (!wp_verify_nonce($_POST['nonce'], 'game_action')) {
        wp_die('Invalid nonce');
    }
    
    $resourceManager = new GameResourceManager();
    // ... operacje
    
    wp_send_json($result);
}
add_action('wp_ajax_game_action', 'handle_game_action');
```

### Shortcodes
```php
function game_status_shortcode() {
    if (!is_user_logged_in()) {
        return 'Musisz być zalogowany';
    }
    
    $resourceManager = new GameResourceManager();
    $data = $resourceManager->getUserData(get_current_user_id());
    
    return '<div>Złoto: ' . $data['gold'] . '</div>';
}
add_shortcode('game_status', 'game_status_shortcode');
```

## Troubleshooting

### Częste błędy

#### "Niewystarczająca ilość zasobu"
- Sprawdź aktualne zasoby przed operacją
- Użyj `getUserData()` do weryfikacji

#### "Niewystarczające uprawnienia"
- Sprawdź czy użytkownik jest zalogowany
- Sprawdź poziom uprawnień metodą `getAuthorizationLevel()`

#### "Użytkownik nie istnieje"
- Sprawdź czy gracz został utworzony w systemie gry
- Użyj `createUser()` jeśli potrzeba

### Debug
```php
// Sprawdź aktualny poziom autoryzacji
echo $resourceManager->getAuthorizationLevel();

// Sprawdź aktualne ID użytkownika
echo $resourceManager->getCurrentUserId();

// Sprawdź czy można wykonać operację
var_dump($resourceManager->canPerformOperation('modify_resources', $user_id));
```

## Rozszerzanie funkcjonalności

### Dodawanie nowych zasobów
Edytuj tablicę `$resource_limits` w konstruktorze:
```php
private $resource_limits = [
    // ... istniejące zasoby
    'karma' => [
        'min' => -1000,
        'max' => 1000,
        'field' => 'karma'
    ]
];
```

### Dodawanie nowych poziomów autoryzacji
Edytuj metodę `authorize()` aby obsłużyć nowe poziomy.
