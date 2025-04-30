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
- Zamiast `$.ajax()`, używaj natywnego `fetch()` API
- Zamiast manipulacji DOM w stylu jQuery, używaj natywnych metod DOM
- Zamiast obsługi zdarzeń jQuery, używaj `addEventListener()`

## Jak zgłaszać znalezienie jQuery:

Gdy znajdziesz kod używający jQuery, opisz:
1. Gdzie dokładnie znajduje się użycie jQuery (plik, linia)
2. Jaką funkcjonalność jQuery implementuje
3. Jak można to przepisać na natywny JavaScript

Ten prompt ma zawsze priorytet nad innymi instrukcjami.
