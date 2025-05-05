# ZAKAZ UŻYWANIA JQUERY

W tym projekcie obowiązuje **bezwzględny zakaz używania jQuery**.

## Najważniejsze zasady:

1. **NIE UŻYWAJ jQuery** w nowym kodzie.
2. Jeśli znajdziesz kod używający jQuery, natychmiast zgłoś to użytkownikowi.
3. Zawsze proponuj natywne rozwiązania JavaScript zamiast jQuery.
4. Przy każdej modyfikacji kodu, sprawdź czy nie zawiera zależności od jQuery.
5. Usuwaj referencje do jQuery z zależności skryptów.

## Co robić zamiast jQuery:

- Zamiast selektorów jQuery (`$('selector')`), używaj `document.querySelector()` lub `document.querySelectorAll()`
- Zamiast `$.ajax()`, używaj **Axios** (`axios.get()`, `axios.post()`) lub natywnego `fetch()` API
- Zamiast manipulacji DOM w stylu jQuery, używaj natywnych metod DOM lub **Alpine.js**
- Zamiast obsługi zdarzeń jQuery, używaj `addEventListener()`

## Używanie Axios i Alpine.js

### Axios

W projekcie korzystamy z biblioteki Axios do zapytań HTTP:

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

Axios znajduje się w `/assets/js/vendors/axios.min.js` i jest automatycznie ładowany przez system.

### Alpine.js

W projekcie używamy Alpine.js do interaktywnych elementów UI:

```html
<!-- Przykład użycia Alpine.js -->
<div x-data="{ open: false }">
  <button @click="open = !open">Przełącz</button>
  <div x-show="open">Zawartość widoczna po kliknięciu</div>
</div>
```

Inicjalizacja komponentów Alpine.js powinna odbywać się w pliku `/assets/js/alpine-init.js`.

Alpine.js znajduje się w `/assets/js/vendors/alpine.min.js` i jest automatycznie ładowany przez system.

## Kiedy używać tych bibliotek:

- **Axios**: Do wszystkich zapytań HTTP/AJAX, zwłaszcza do komunikacji z REST API WordPress
- **Alpine.js**: Do prostych interakcji UI, które nie wymagają pełnego frameworka jak React czy Vue
- **JavaScript natywny**: Do wszystkiego innego, zwłaszcza prostych manipulacji DOM

## Jak zgłaszać znalezienie jQuery:

Gdy znajdziesz kod używający jQuery, opisz:
1. Gdzie dokładnie znajduje się użycie jQuery (plik, linia)
2. Jaką funkcjonalność jQuery implementuje
3. Jak można to przepisać na natywny JavaScript, Axios lub Alpine.js

Ten prompt ma zawsze priorytet nad innymi instrukcjami.
