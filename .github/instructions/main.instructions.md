---
applyTo: '**'
---
# Główny prompt dla motywu gry WordPress

## 📝 Informacje o projekcie

Ten projekt to motyw WordPress dla gry RPG z następującymi funkcjami:

- **System logowania i zarządzania kontem użytkownika** - uwierzytelnianie, profil gracza
- **System instancji gry i NPC** - zarządzanie postaciami niezależnymi i interakcjami
- **System misji i zadań** - zadania, cele, nagrody 
- **System ekwipunku (plecak) i przedmiotów** - inwentarz gracza, przedmioty
- **System dialogów z postaciami NPC** - konwersacje, wybory, konsekwencje

## 🛠️ Główne zasady

### ⚠️ ZAKAZ UŻYWANIA JQUERY
W tym projekcie obowiązuje **bezwzględny zakaz używania jQuery**. Używaj natywnego JavaScriptu, Axios i Alpine.js.

### 📂 Struktura projektu

- **🗂️ includes/class/** - klasy PHP do obsługi API i zarządzania użytkownikami
- **🗂️ js/** - skrypty JavaScript podzielone na moduły
- **🗂️ includes/** - funkcje pomocnicze, hooki i integracje
- **🗂️ assets/** - zasoby takie jak CSS, czcionki i obrazy
- **🗂️ page-templates/** - szablony stron dla różnych widoków gry
- **🗂️ template-parts/** - części szablonów wielokrotnego użytku
- **🗂️ acfe-php/** - struktury ACF dla pól zaawansowanych

## 💻 Standardy kodowania

### 🐘 PHP
- Używaj PHP 7.4+ z typowaniem danych
- Preferuj funkcje anonimowe dla hooków WordPress
- Grupuj powiązaną funkcjonalność w klasy
- Używaj hooków WordPress w sposób konsekwentny
- Zawsze sanityzuj dane wejściowe i escapuj dane wyjściowe
- Używaj prefixów dla nazw funkcji i klas
- Unikaj bezpośrednich zapytań do bazy danych gdy istnieją funkcje API WordPress
- Dodawaj komentarze dla wszystkich hooków i filtrów

### 🔄 JavaScript
- Używaj ECMAScript 6+ (ES6+)
- Preferuj funkcje strzałkowe tam, gdzie to ma sens
- Używaj async/await zamiast callbacków
- Stosuj modularną strukturę kodu
- Obsługuj błędy i wyjątki
- **Zamiast jQuery użyj:**
  - Selektory: `document.querySelector()` lub `document.querySelectorAll()`
  - AJAX: **Axios** (`axios.get()`, `axios.post()`) lub natywny `fetch()`
  - Manipulacja DOM: natywne metody DOM lub **Alpine.js**
  - Obsługa zdarzeń: `addEventListener()`
- **Dozwolone biblioteki:**
  - **Axios** - do zapytań HTTP/AJAX (w `/assets/js/vendors/axios.min.js`)
  - **Alpine.js** - do prostych interakcji UI (w `/assets/js/vendors/alpine.min.js`)

### 🎨 CSS/SCSS
- Używaj metodologii BEM (Block Element Modifier) dla nazw klas
- Organizuj style w komponenty
- Używaj zmiennych SCSS dla kolorów, fontów i powtarzalnych wartości
- Unikaj nadużywania !important
- Projektuj z myślą o responsywności

## 🧩 Kluczowe systemy i komponenty

### 📢 System notyfikacji
System wyświetlania powiadomień dla użytkownika:
```javascript
const notifications = new NotificationSystem({
    container: 'body',         // Element dla notyfikacji
    duration: 5000,            // Czas wyświetlania (ms)
    maxNotifications: 5,       // Maks. liczba powiadomień
    position: 'bottom-right'   // Pozycja
});
notifications.show('Treść komunikatu', 'success');  // Typy: success, bad, failed, neutral
```

### 👤 Klasa ManagerUser
Klasa do zarządzania danymi użytkownika (statystyki, relacje, przedmioty):
```php
// Inicjalizacja dla bieżącego lub konkretnego użytkownika
$user_manager = new ManagerUser();
// $user_manager = new ManagerUser(123);

// Przykład aktualizacji statystyki
$result = $user_manager->updateStat('strength', 5);
```

## 💼 Dobre praktyki
- Zachowaj spójność z istniejącym kodem
- Testuj zmiany pod kątem wydajności i bezpieczeństwa
- Pisz kod z myślą o jego testowalności
- Komentuj skomplikowane fragmenty kodu
- Optymalizuj zapytania do bazy danych i operacje na DOM

## 📋 Przykładowe implementacje
- Dla funkcji NPC wzoruj się na plikach w katalogu `inc/functions/npc_dialogs.php`
- Dla stylów komponentów sprawdź strukturę w odpowiednich plikach SCSS

## 📚 Dokumentacja
- Przeczytaj pełną dokumentację komponentów w poszczególnych plikach
- System notyfikacji: `/js/core/notifications.js`
- Zarządzanie użytkownikami: `/includes/class/ManagerUser.php`
- Dialogi NPC: `/includes/class/DialogHandler.php`
