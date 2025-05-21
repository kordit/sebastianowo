<?php

/**
 * Rozszerzenie klasy DialogManager o dodatkowe funkcje obsługi zasobów
 * 
 * @package Game
 * @subpackage NpcPopup
 * @since 1.0.0
 */

/**
 * Akcja: Aktualizuje zasób w plecaku gracza
 *
 * @param string $resource_key Klucz zasobu
 * @param int $value Wartość do dodania (dodatnia) lub odjęcia (ujemna)
 * @return array|false Powiadomienie lub false w przypadku niepowodzenia
 */
public function action_update_resource(string $resource_key, int $value): array|false
{
    $this->logger->log("Aktualizacja zasobu {$resource_key} o wartość {$value} dla użytkownika {$this->user_id}");

    // Mapowanie nazw z polskiego na klucze systemowe
    $currency_mapping = [
        'papierosy' => 'cigarettes',
        'szlugi' => 'cigarettes',
        'złoto' => 'gold',
        'zloto' => 'gold',
        'hajs' => 'gold',
        'grzyby' => 'mushrooms',
        'grzybki' => 'mushrooms',
        'piwo' => 'beer',
        'browary' => 'beer',
        'alko' => 'vodka',
        'wóda' => 'vodka',
        'wódka' => 'vodka',
        'klej' => 'glue',
        'kleje' => 'glue',
        'mj' => 'weed',
        'zioło' => 'weed',
        'ziolo' => 'weed',
        'zielone' => 'weed',
        'marihuana' => 'weed'
    ];

    // Słownik wyświetlanych nazw dla zasobów (do komunikatów)
    $currency_display_names = [
        'cigarettes' => 'papierosów',
        'gold' => 'złota',
        'mushrooms' => 'grzybów',
        'beer' => 'piwa',
        'vodka' => 'wódki',
        'glue' => 'kleju',
        'weed' => 'zioła'
    ];

    // Sprawdź czy trzeba zamapować klucz
    if (isset($currency_mapping[$resource_key])) {
        $mapped_resource = $currency_mapping[$resource_key];
        $this->logger->log("MAPOWANIE ZASOBU: Zamieniono klucz '$resource_key' na '$mapped_resource'");
        $resource_key = $mapped_resource;
    }

    // Pobierz dane plecaka za pomocą ACF
    $backpack = get_field(BACKPACK['name'], 'user_' . $this->user_id);
    if (!is_array($backpack)) {
        $backpack = [];
        // Zainicjuj domyślne wartości wszystkich pól plecaka
        foreach (BACKPACK['fields'] as $field_key => $field_data) {
            $backpack[$field_key] = $field_data['default'];
        }
    }

    // Zapisz obecną wartość waluty
    $current_value = isset($backpack[$resource_key]) ? (int)$backpack[$resource_key] : 0;
    $this->logger->log("Obecna wartość zasobu $resource_key w plecaku: $current_value");

    // Sprawdź czy mamy wystarczającą ilość zasobu, gdy wartość jest ujemna
    if ($value < 0 && abs($value) > $current_value) {
        $this->logger->log("NIEPOWODZENIE AKTUALIZACJI ZASOBU: Niewystarczająca ilość zasobu $resource_key ($current_value)");
        return false;
    }

    // Oblicz nową wartość
    $new_value = $current_value + $value;
    if ($new_value < 0) {
        $new_value = 0; // Zabezpieczenie przed ujemnymi wartościami
    }

    // Aktualizuj wartość w plecaku
    $backpack[$resource_key] = $new_value;
    $result = update_field(BACKPACK['name'], $backpack, 'user_' . $this->user_id);

    if (!$result) {
        $this->logger->log("BŁĄD aktualizacji zasobu $resource_key");
        return false;
    }

    // Przygotuj powiadomienie na podstawie typu transakcji
    $display_name = $currency_display_names[$resource_key] ?? $resource_key;
    
    if ($value > 0) {
        $notification = [
            'message' => "Otrzymano " . abs($value) . " " . $display_name,
            'status' => 'success'
        ];
    } else {
        $notification = [
            'message' => "Stracono " . abs($value) . " " . $display_name,
            'status' => 'bad'
        ];
    }

    $this->logger->log("Zakończono aktualizację zasobu. Nowa wartość $resource_key: $new_value");
    return $notification;
}

/**
 * Weryfikuje czy użytkownik ma wystarczającą ilość zasobów
 *
 * @param string $resource_key Klucz zasobu
 * @param int $required_value Wymagana wartość (dla wartości ujemnych sprawdzana jest dostępna ilość)
 * @return bool Czy zasób jest dostępny
 */
public function verify_resource_availability(string $resource_key, int $required_value): bool
{
    // Jeśli dodajemy zasób, zawsze możemy to zrobić
    if ($required_value >= 0) {
        return true;
    }

    // Mapowanie nazw z polskiego na klucze systemowe
    $currency_mapping = [
        'papierosy' => 'cigarettes',
        'szlugi' => 'cigarettes',
        'złoto' => 'gold',
        'zloto' => 'gold',
        'hajs' => 'gold',
        'grzyby' => 'mushrooms',
        'grzybki' => 'mushrooms',
        'piwo' => 'beer',
        'browary' => 'beer',
        'alko' => 'vodka',
        'wóda' => 'vodka',
        'wódka' => 'vodka',
        'klej' => 'glue',
        'kleje' => 'glue',
        'mj' => 'weed',
        'zioło' => 'weed',
        'ziolo' => 'weed',
        'zielone' => 'weed',
        'marihuana' => 'weed'
    ];

    // Sprawdź czy trzeba zamapować klucz
    if (isset($currency_mapping[$resource_key])) {
        $resource_key = $currency_mapping[$resource_key];
    }

    // Pobierz dane plecaka za pomocą ACF
    $backpack = get_field(BACKPACK['name'], 'user_' . $this->user_id);
    if (!is_array($backpack)) {
        $backpack = [];
        foreach (BACKPACK['fields'] as $field_key => $field_data) {
            $backpack[$field_key] = $field_data['default'];
        }
    }

    // Pobierz aktualną wartość zasobu
    $current_value = isset($backpack[$resource_key]) ? (int)$backpack[$resource_key] : 0;

    // Sprawdź czy mamy wystarczającą ilość zasobu
    return abs($required_value) <= $current_value;
}

/**
 * Akcja: Aktualizuje umiejętność gracza
 *
 * @param string $skill_type Typ umiejętności
 * @param int $value Wartość do dodania (dodatnia) lub odjęcia (ujemna)
 * @return array|false Powiadomienie lub false w przypadku niepowodzenia
 */
public function action_update_skill(string $skill_type, int $value): array|false
{
    $this->logger->log("Aktualizacja umiejętności {$skill_type} o wartość {$value} dla użytkownika {$this->user_id}");

    // Sprawdź, czy podany typ umiejętności istnieje w strukturze
    if (!isset(SKILLS['fields'][$skill_type])) {
        $this->logger->log("BŁĄD: Nieprawidłowy typ umiejętności: $skill_type");
        return false;
    }

    // Pobierz umiejętności użytkownika
    $skills = get_field(SKILLS['name'], 'user_' . $this->user_id);
    if (!is_array($skills)) {
        $skills = [];
        // Zainicjuj domyślne wartości wszystkich umiejętności
        foreach (SKILLS['fields'] as $field_key => $field_data) {
            $skills[$field_key] = $field_data['default'];
        }
    }

    // Pobierz aktualną wartość umiejętności
    $current_value = isset($skills[$skill_type]) ? (int)$skills[$skill_type] : 0;
    $this->logger->log("Obecna wartość umiejętności $skill_type: $current_value");

    // Sprawdź czy mamy wystarczającą wartość umiejętności, gdy wartość jest ujemna
    if ($value < 0 && abs($value) > $current_value) {
        $this->logger->log("NIEPOWODZENIE AKTUALIZACJI UMIEJĘTNOŚCI: Niewystarczająca wartość $skill_type ($current_value)");
        return false;
    }

    // Oblicz nową wartość
    $new_value = $current_value + $value;
    if ($new_value < 0) {
        $new_value = 0; // Zabezpieczenie przed ujemnymi wartościami
    }

    // Aktualizuj wartość umiejętności
    $skills[$skill_type] = $new_value;
    $result = update_field(SKILLS['name'], $skills, 'user_' . $this->user_id);

    if (!$result) {
        $this->logger->log("BŁĄD aktualizacji umiejętności $skill_type");
        return false;
    }

    // Przygotuj komunikat w zależności od wartości
    $skill_label = SKILLS['fields'][$skill_type]['label'];
    
    if ($value > 0) {
        $notification = [
            'message' => "Zwiększono umiejętność $skill_label o $value",
            'status' => 'success'
        ];
    } elseif ($value < 0) {
        $notification = [
            'message' => "Zmniejszono umiejętność $skill_label o " . abs($value),
            'status' => 'bad'
        ];
    } else {
        // Jeśli wartość jest 0, nie pokazujemy powiadomienia
        return false;
    }

    $this->logger->log("Zakończono aktualizację umiejętności. Nowa wartość $skill_type: $new_value");
    return $notification;
}

/**
 * Weryfikuje czy użytkownik ma wystarczającą wartość umiejętności
 *
 * @param string $skill_type Typ umiejętności
 * @param int $required_value Wymagana wartość (dla wartości ujemnych sprawdzana jest dostępna wartość)
 * @return bool Czy umiejętność jest dostępna na wymaganym poziomie
 */
public function verify_skill_availability(string $skill_type, int $required_value): bool
{
    // Jeśli dodajemy wartość umiejętności, zawsze możemy to zrobić
    if ($required_value >= 0) {
        return true;
    }

    // Sprawdź, czy podany typ umiejętności istnieje w strukturze
    if (!isset(SKILLS['fields'][$skill_type])) {
        return false;
    }

    // Pobierz umiejętności użytkownika
    $skills = get_field(SKILLS['name'], 'user_' . $this->user_id);
    if (!is_array($skills)) {
        $skills = [];
        foreach (SKILLS['fields'] as $field_key => $field_data) {
            $skills[$field_key] = $field_data['default'];
        }
    }

    // Pobierz aktualną wartość umiejętności
    $current_value = isset($skills[$skill_type]) ? (int)$skills[$skill_type] : 0;

    // Sprawdź czy mamy wystarczającą wartość umiejętności
    return abs($required_value) <= $current_value;
}
