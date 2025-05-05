# Standardy kodowania dla projektu gry WordPress

## Podstawowe informacje o projekcie
Ten projekt to motyw WordPress dla gry RPG z następującymi funkcjami:
- System logowania i zarządzania kontem użytkownika
- System instancji gry i NPC
- System misji i zadań
- System ekwipunku (plecak) i przedmiotów
- System dialogów z postaciami NPC

## PHP
- Używaj PHP 7.4+ i jego nowszych funkcji
- Używaj typowania danych gdzie to możliwe
- Grupuj powiązaną funkcjonalność w klasy
- Używaj hooków WordPress w sposób konsekwentny
- Zawsze sanityzuj dane wejściowe i escapuj dane wyjściowe
- Zwracaj uwagę na potencjalne problemy z bezpieczeństwem

## JavaScript
- Używaj ECMAScript 6+ (ES6+)
- Preferuj funkcje strzałkowe tam, gdzie to ma sens
- Używaj async/await zamiast callbacków
- Modularyzuj kod JavaScript
- Obsługuj błędy i wyjątki

## CSS/SCSS
- Używaj BEM (Block Element Modifier) dla nazywania klas CSS
- Organizuj style w komponenty
- Używaj zmiennych SCSS dla kolorów, fontów i innych powtarzalnych wartości

## WordPress
- Używaj standardów kodowania WordPress
- Używaj prefixów dla nazw funkcji i klas
- Dodawaj komentarze dla wszystkich hooków i filtrów
- Unikaj bezpośrednich zapytań do bazy danych tam, gdzie istnieją funkcje API WordPress

## Struktura projektu
- Umieszczaj pliki PHP w odpowiednich katalogach według ich funkcji
- JavaScript powinien być zorganizowany według modułów
- Style powinny być zorganizowane według komponentów