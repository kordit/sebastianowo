<?php

/**
 * ManagerUser - klasa do zarządzania statystykami i polami użytkownika
 * 
 * Pozwala na modyfikację (dodawanie/odejmowanie) wartości liczbowych w polach ACF
 * z odpowiednią walidacją i komunikatami zwrotnymi. Dostęp tylko z poziomu PHP.
 */
class ManagerUser
{
    /**
     * ID użytkownika
     */
    private $user_id;

    /**
     * Konstruktor
     * 
     * @param int $user_id ID użytkownika (opcjonalne, domyślnie aktualny użytkownik)
     */
    public function __construct($user_id = null)
    {
        // Jeśli nie podano ID użytkownika, użyj aktualnie zalogowanego
        $this->user_id = $user_id ?? get_current_user_id();
    }

    /**
     * Aktualizacja wartości liczbowej w polu ACF
     * 
     * @param string $field_name Nazwa pola
     * @param int|float $value Wartość do dodania/odjęcia (dodatnia dodaje, ujemna odejmuje)
     * @param string $label Etykieta pola (dla komunikatów)
     * @return array Tablica z wynikiem operacji (success, message, new_value)
     */
    public function updateNumericField($field_name, $value, $label = null)
    {
        // Sprawdź, czy użytkownik istnieje
        if (!get_user_by('ID', $this->user_id)) {
            return [
                'success' => false,
                'message' => 'Użytkownik nie istnieje'
            ];
        }

        // Pobierz aktualną wartość pola
        $current_value = $this->getFieldValue($field_name);

        // Sprawdź czy pole istnieje (jest różne od null)
        if ($current_value === null) {
            return [
                'success' => false,
                'message' => 'Pole nie istnieje'
            ];
        }

        // Sprawdź czy to pole liczbowe
        if (!is_numeric($current_value)) {
            return [
                'success' => false,
                'message' => 'Pole nie jest wartością liczbową'
            ];
        }

        // Oblicz nową wartość
        $new_value = $current_value + $value;
        $display_label = $label ?? $this->getFieldLabel($field_name);

        // Walidacja - sprawdź czy nie będzie ujemna (chyba że to pole reputacji, która może być ujemna)
        if ($new_value < 0 && $field_name !== 'progress_reputation') {
            return [
                'success' => false,
                'message' => "Nie masz wystarczająco {$display_label} do wykonania tej operacji"
            ];
        }

        // Aktualizacja wartości
        $updated = $this->updateField($field_name, $new_value);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Nie udało się zaktualizować pola'
            ];
        }

        // Przygotuj komunikat zwrotny
        if ($value > 0) {
            $message = "Dodano {$value} {$display_label}";
        } else {
            $abs_value = abs($value);
            $message = "Odjęto {$abs_value} {$display_label}";
        }

        return [
            'success' => true,
            'message' => $message,
            'new_value' => $new_value,
            'old_value' => $current_value,
            'field_name' => $field_name
        ];
    }

    /**
     * Sprawdza czy użytkownik ma wystarczającą ilość zasobu
     * 
     * @param string $field_name Nazwa pola
     * @param int|float $required_value Wymagana wartość
     * @return bool|array True jeśli ma wystarczająco, lub array z informacją o błędzie
     */
    public function checkResourceAvailability($field_name, $required_value)
    {
        // Sprawdź, czy użytkownik istnieje
        if (!get_user_by('ID', $this->user_id)) {
            return [
                'success' => false,
                'message' => 'Użytkownik nie istnieje'
            ];
        }

        // Pobierz aktualną wartość pola
        $current_value = $this->getFieldValue($field_name);

        // Sprawdź czy pole istnieje (jest różne od null)
        if ($current_value === null) {
            return [
                'success' => false,
                'message' => 'Pole nie istnieje'
            ];
        }

        // Sprawdź czy to pole liczbowe
        if (!is_numeric($current_value)) {
            return [
                'success' => false,
                'message' => 'Pole nie jest wartością liczbową'
            ];
        }

        // Sprawdź czy użytkownik ma wystarczającą ilość zasobu
        if ($current_value < $required_value) {
            $display_label = $this->getFieldLabel($field_name);
            return [
                'success' => false,
                'message' => "Nie masz wystarczająco {$display_label} (wymagane: {$required_value}, posiadane: {$current_value})",
                'required' => $required_value,
                'available' => $current_value
            ];
        }

        return true;
    }

    /**
     * Sprawdza czy użytkownik posiada przedmiot w wymaganej ilości
     * 
     * @param int $item_id ID przedmiotu
     * @param int $required_quantity Wymagana ilość
     * @return bool|array True jeśli ma wystarczająco, lub array z informacją o błędzie
     */
    public function checkItemAvailability($item_id, $required_quantity)
    {
        // Sprawdź czy użytkownik istnieje
        if (!get_user_by('ID', $this->user_id)) {
            return [
                'success' => false,
                'message' => 'Użytkownik nie istnieje'
            ];
        }

        // Sprawdź czy przedmiot istnieje
        $item = get_post($item_id);
        if (!$item || $item->post_type !== 'item') {
            return [
                'success' => false,
                'message' => 'Przedmiot nie istnieje'
            ];
        }

        // Pobierz aktualną listę przedmiotów użytkownika
        $items = get_field('items', 'user_' . $this->user_id);

        // Jeśli nie ma żadnych przedmiotów
        if (!$items) {
            return [
                'success' => false,
                'message' => 'Nie masz tego przedmiotu w plecaku',
                'required' => $required_quantity,
                'available' => 0
            ];
        }

        // Szukaj przedmiotu na liście
        $found = false;
        $available_quantity = 0;

        foreach ($items as $user_item) {
            if ($user_item['item']->ID == $item_id) {
                $found = true;
                $available_quantity = $user_item['quantity'];
                break;
            }
        }

        // Jeśli przedmiot nie został znaleziony
        if (!$found) {
            return [
                'success' => false,
                'message' => 'Nie masz tego przedmiotu w plecaku',
                'required' => $required_quantity,
                'available' => 0
            ];
        }

        // Sprawdź czy ma wystarczającą ilość
        if ($available_quantity < $required_quantity) {
            return [
                'success' => false,
                'message' => "Nie masz wystarczającej ilości {$item->post_title} w plecaku (wymagane: {$required_quantity}, posiadane: {$available_quantity})",
                'required' => $required_quantity,
                'available' => $available_quantity
            ];
        }

        return true;
    }

    /**
     * Pobieranie wartości pola ACF
     * 
     * @param string $field_name Nazwa pola
     * @return mixed Wartość pola lub null jeśli pole nie istnieje
     */
    public function getFieldValue($field_name)
    {
        // Sprawdź formaty zagnieżdżenia pól
        if (strpos($field_name, '_') === false) {
            // Bezpośrednie pole
            return get_field($field_name, 'user_' . $this->user_id);
        }

        // Obsługa różnych formatów pól zagnieżdżonych
        // Format: grupa_pole (np. stats_strength)
        $parts = explode('_', $field_name, 2);
        if (count($parts) === 2) {
            $group = $parts[0];
            $field = $parts[1];

            $group_value = get_field($group, 'user_' . $this->user_id);

            if (is_array($group_value) && isset($group_value[$field])) {
                return $group_value[$field];
            }
        }

        return null;
    }

    /**
     * Aktualizacja pola ACF
     * 
     * @param string $field_name Nazwa pola
     * @param mixed $value Nowa wartość
     * @return bool Czy aktualizacja się powiodła
     */
    private function updateField($field_name, $value)
    {
        // Sprawdź formaty zagnieżdżenia pól
        if (strpos($field_name, '_') === false) {
            // Bezpośrednie pole
            return update_field($field_name, $value, 'user_' . $this->user_id);
        }

        // Obsługa różnych formatów pól zagnieżdżonych
        // Format: grupa_pole (np. stats_strength)
        $parts = explode('_', $field_name, 2);
        if (count($parts) === 2) {
            $group = $parts[0];
            $field = $parts[1];

            $group_value = get_field($group, 'user_' . $this->user_id);

            if (is_array($group_value)) {
                $group_value[$field] = $value;
                $result = update_field($group, $group_value, 'user_' . $this->user_id);

                // Weryfikacja czy dane faktycznie zostały zaktualizowane
                // W niektórych przypadkach ACF może zwrócić false, mimo że dane zostały zaktualizowane
                if (!$result) {
                    $updated_value = get_field($group, 'user_' . $this->user_id);
                    if (isset($updated_value[$field]) && $updated_value[$field] == $value) {
                        return true; // Dane zostały zaktualizowane mimo zwróconego false
                    }
                }

                return $result;
            }
        }

        return false;
    }

    /**
     * Pobieranie etykiety pola ACF
     * 
     * @param string $field_name Nazwa pola
     * @return string Etykieta pola lub nazwa pola jeśli etykieta nie istnieje
     */
    public function getFieldLabel($field_name)
    {
        $field_labels = [
            // Statystyki
            'stats_strength' => 'Siła',
            'stats_defense' => 'Wytrzymałość',
            'stats_dexterity' => 'Zręczność',
            'stats_perception' => 'Percepcja',
            'stats_technical' => 'Zdolności manualne',
            'stats_charisma' => 'Cwaniactwo',

            // Umiejętności
            'skills_combat' => 'Walka',
            'skills_steal' => 'Kradzież',
            'skills_craft' => 'Produkcja',
            'skills_trade' => 'Handel',
            'skills_relations' => 'Relacje',
            'skills_street' => 'Uliczna wiedza',

            // Plecak
            'backpack_gold' => 'Złote',
            'backpack_cigarettes' => 'Papierosy',

            // Witalność
            'vitality_life' => 'Życie',
            'vitality_max_life' => 'Maksymalne życie',
            'vitality_energy' => 'Energia',
            'vitality_max_energy' => 'Maksymalna energia',

            // Postęp
            'progress_exp' => 'Doświadczenie',
            'progress_learning_points' => 'Punkty nauki',
            'progress_reputation' => 'Reputacja'
        ];

        return isset($field_labels[$field_name]) ? $field_labels[$field_name] : $field_name;
    }

    /**
     * Aktualizacja statystyki
     * 
     * @param string $stat_name Nazwa statystyki
     * @param int|float $value Wartość do dodania/odjęcia
     * @return array Wynik operacji
     */
    public function updateStat($stat_name, $value)
    {
        return $this->updateNumericField('stats_' . $stat_name, $value);
    }

    /**
     * Aktualizacja umiejętności
     * 
     * @param string $skill_name Nazwa umiejętności
     * @param int|float $value Wartość do dodania/odjęcia
     * @return array Wynik operacji
     */
    public function updateSkill($skill_name, $value)
    {
        return $this->updateNumericField('skills_' . $skill_name, $value);
    }

    /**
     * Aktualizacja zawartości plecaka
     * 
     * @param string $item_name Nazwa przedmiotu (gold, cigarettes)
     * @param int|float $value Wartość do dodania/odjęcia
     * @return array Wynik operacji
     */
    public function updateBackpack($item_name, $value)
    {
        return $this->updateNumericField('backpack_' . $item_name, $value);
    }

    /**
     * Aktualizacja witalności
     * 
     * @param string $vitality_name Nazwa witalności (life, max_life, energy, max_energy)
     * @param int|float $value Wartość do dodania/odjęcia
     * @return array Wynik operacji
     */
    public function updateVitality($vitality_name, $value)
    {
        return $this->updateNumericField('vitality_' . $vitality_name, $value);
    }

    /**
     * Aktualizacja postępu
     * 
     * @param string $progress_name Nazwa postępu (exp, learning_points, reputation)
     * @param int|float $value Wartość do dodania/odjęcia
     * @return array Wynik operacji
     */
    public function updateProgress($progress_name, $value)
    {
        return $this->updateNumericField('progress_' . $progress_name, $value);
    }

    /**
     * Dodaje przedmiot do plecaka użytkownika
     * 
     * @param int $item_id ID przedmiotu do dodania
     * @param int $quantity Ilość przedmiotu do dodania
     * @return array Wynik operacji
     */
    public function addItem($item_id, $quantity)
    {
        // Sprawdź czy użytkownik istnieje
        if (!get_user_by('ID', $this->user_id)) {
            return [
                'success' => false,
                'message' => 'Użytkownik nie istnieje'
            ];
        }

        // Sprawdź czy przedmiot istnieje
        $item = get_post($item_id);
        if (!$item || $item->post_type !== 'item') {
            return [
                'success' => false,
                'message' => 'Przedmiot nie istnieje'
            ];
        }

        // Pobierz aktualną listę przedmiotów użytkownika
        $items = get_field('items', 'user_' . $this->user_id);

        // Jeśli nie ma jeszcze żadnych przedmiotów, utwórz pustą tablicę
        if (!$items) {
            $items = [];
        }

        // Sprawdź, czy użytkownik już posiada ten przedmiot
        $found = false;
        foreach ($items as $key => $user_item) {
            if ($user_item['item']->ID == $item_id) {
                // Przedmiot znaleziony, zaktualizuj ilość
                $items[$key]['quantity'] += $quantity;
                $found = true;
                $new_quantity = $items[$key]['quantity'];
                break;
            }
        }

        // Jeśli przedmiot nie został znaleziony, dodaj go do listy
        if (!$found) {
            $items[] = [
                'item' => $item,
                'quantity' => $quantity
            ];
            $new_quantity = $quantity;
        }        // Zaktualizuj przedmioty w bazie danych
        $updated = update_field('items', $items, 'user_' . $this->user_id);

        if (!$updated) {
            // Dodatkowa weryfikacja - sprawdź czy przedmiot faktycznie został dodany
            // Funkcja ACF update_field() czasami zwraca false mimo że zmiany zostały zapisane
            $current_items = get_field('items', 'user_' . $this->user_id);
            if ($current_items) {
                $item_found = false;
                foreach ($current_items as $current_item) {
                    if ($current_item['item']->ID == $item_id && $current_item['quantity'] == $new_quantity) {
                        $item_found = true;
                        break;
                    }
                }

                if ($item_found) {
                    // Przedmiot został faktycznie dodany, mimo że update_field zwrócił false
                    return [
                        'success' => true,
                        'message' => "Dodano {$quantity}x {$item->post_title} do plecaka",
                        'item_id' => $item_id,
                        'quantity' => $new_quantity
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Nie udało się dodać przedmiotu'
            ];
        }

        return [
            'success' => true,
            'message' => "Dodano {$quantity}x {$item->post_title} do plecaka",
            'item_id' => $item_id,
            'quantity' => $new_quantity
        ];
    }

    /**
     * Usuwa przedmiot z plecaka użytkownika
     * 
     * @param int $item_id ID przedmiotu do usunięcia
     * @param int $quantity Ilość przedmiotu do usunięcia
     * @return array Wynik operacji
     */
    public function removeItem($item_id, $quantity)
    {
        // Sprawdź dostępność przedmiotu
        $availability = $this->checkItemAvailability($item_id, $quantity);
        if ($availability !== true) {
            return $availability; // Zwróć błąd dostępności
        }

        // Sprawdź czy przedmiot istnieje
        $item = get_post($item_id);
        if (!$item || $item->post_type !== 'item') {
            return [
                'success' => false,
                'message' => 'Przedmiot nie istnieje'
            ];
        }

        // Pobierz aktualną listę przedmiotów użytkownika
        $items = get_field('items', 'user_' . $this->user_id);

        // Znajdź przedmiot i zaktualizuj ilość
        $new_quantity = 0;
        foreach ($items as $key => $user_item) {
            if ($user_item['item']->ID == $item_id) {
                // Aktualizuj ilość lub usuń przedmiot
                if ($user_item['quantity'] > $quantity) {
                    $items[$key]['quantity'] -= $quantity;
                    $new_quantity = $items[$key]['quantity'];
                } else {
                    // Usuń przedmiot z listy
                    unset($items[$key]);
                }
                break;
            }
        }

        // Reindeksuj tablicę (po usunięciu elementu)
        $items = array_values($items);

        // Zaktualizuj przedmioty w bazie danych
        $updated = update_field('items', $items, 'user_' . $this->user_id);

        if (!$updated) {
            // Dodatkowa weryfikacja - sprawdź czy przedmiot faktycznie został usunięty
            // Funkcja ACF update_field() czasami zwraca false mimo że zmiany zostały zapisane
            $current_items = get_field('items', 'user_' . $this->user_id);

            if ($current_items) {
                $item_still_exists = false;
                $correct_quantity = false;

                // Sprawdź czy przedmiot nadal istnieje i ma odpowiednią ilość
                foreach ($current_items as $current_item) {
                    if ($current_item['item']->ID == $item_id) {
                        $item_still_exists = true;
                        // Sprawdź czy ilość się zgadza
                        if ($current_item['quantity'] == $new_quantity) {
                            $correct_quantity = true;
                        }
                        break;
                    }
                }

                // Jeśli przedmiot został usunięty (gdy new_quantity = 0) lub jego ilość została poprawnie zmniejszona
                if (($new_quantity == 0 && !$item_still_exists) || ($new_quantity > 0 && $item_still_exists && $correct_quantity)) {
                    return [
                        'success' => true,
                        'message' => "Usunięto {$quantity}x {$item->post_title} z plecaka",
                        'item_id' => $item_id,
                        'quantity' => $new_quantity
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Nie udało się usunąć przedmiotu'
            ];
        }

        return [
            'success' => true,
            'message' => "Usunięto {$quantity}x {$item->post_title} z plecaka",
            'item_id' => $item_id,
            'quantity' => $new_quantity
        ];
    }

    /**
     * Dodaje rejon do dostępnych rejonów użytkownika
     * 
     * @param int $area_id ID rejonu do dodania
     * @return array Wynik operacji
     */
    public function addAvailableArea($area_id)
    {
        // Sprawdź czy użytkownik istnieje
        if (!get_user_by('ID', $this->user_id)) {
            return [
                'success' => false,
                'message' => 'Użytkownik nie istnieje'
            ];
        }

        // Sprawdź czy rejon istnieje
        $area = get_post($area_id);
        if (!$area || $area->post_type !== 'tereny') {
            return [
                'success' => false,
                'message' => 'Rejon nie istnieje'
            ];
        }

        // Pobierz aktualne dostępne rejony
        $available_areas = get_field('available_areas', 'user_' . $this->user_id);
        if (!$available_areas) {
            $available_areas = [];
        }

        // Sprawdź czy rejon jest już dostępny
        if (in_array($area_id, $available_areas)) {
            return [
                'success' => true,
                'message' => "Rejon {$area->post_title} jest już dostępny",
                'area_id' => $area_id
            ];
        }

        // Dodaj rejon do dostępnych
        $available_areas[] = $area_id;

        // Zapisz zmiany
        $updated = update_field('available_areas', $available_areas, 'user_' . $this->user_id);

        if (!$updated) {
            // Dodatkowa weryfikacja czy rejon został dodany
            $current_areas = get_field('available_areas', 'user_' . $this->user_id);
            if (is_array($current_areas) && in_array($area_id, $current_areas)) {
                return [
                    'success' => true,
                    'message' => "Dodano rejon {$area->post_title} do dostępnych",
                    'area_id' => $area_id
                ];
            }

            return [
                'success' => false,
                'message' => 'Nie udało się dodać rejonu'
            ];
        }

        return [
            'success' => true,
            'message' => "Dodano rejon {$area->post_title} do dostępnych",
            'area_id' => $area_id
        ];
    }

    /**
     * Usuwa rejon z dostępnych rejonów użytkownika
     * 
     * @param int $area_id ID rejonu do usunięcia
     * @return array Wynik operacji
     */
    public function removeAvailableArea($area_id)
    {
        // Sprawdź czy użytkownik istnieje
        if (!get_user_by('ID', $this->user_id)) {
            return [
                'success' => false,
                'message' => 'Użytkownik nie istnieje'
            ];
        }

        // Sprawdź czy rejon istnieje
        $area = get_post($area_id);
        if (!$area || $area->post_type !== 'tereny') {
            return [
                'success' => false,
                'message' => 'Rejon nie istnieje'
            ];
        }

        // Pobierz aktualne dostępne rejony
        $available_areas = get_field('available_areas', 'user_' . $this->user_id);

        // Jeśli nie ma dostępnych rejonów, zwróć błąd
        if (!$available_areas || !is_array($available_areas)) {
            return [
                'success' => false,
                'message' => 'Nie ma dostępnych rejonów'
            ];
        }

        // Sprawdź czy rejon jest dostępny
        if (!in_array($area_id, $available_areas)) {
            return [
                'success' => true,
                'message' => "Rejon {$area->post_title} nie jest dostępny",
                'area_id' => $area_id
            ];
        }

        // Usuń rejon z dostępnych
        $available_areas = array_diff($available_areas, [$area_id]);

        // Zapisz zmiany
        $updated = update_field('available_areas', $available_areas, 'user_' . $this->user_id);

        if (!$updated) {
            // Dodatkowa weryfikacja czy rejon został usunięty
            $current_areas = get_field('available_areas', 'user_' . $this->user_id);
            if (is_array($current_areas) && !in_array($area_id, $current_areas)) {
                return [
                    'success' => true,
                    'message' => "Usunięto rejon {$area->post_title} z dostępnych",
                    'area_id' => $area_id
                ];
            }

            return [
                'success' => false,
                'message' => 'Nie udało się usunąć rejonu'
            ];
        }

        return [
            'success' => true,
            'message' => "Usunięto rejon {$area->post_title} z dostępnych",
            'area_id' => $area_id
        ];
    }

    /**
     * Enumeracja stanów dla maszyny stanów przy aktualizacji relacji NPC
     */
    private const RELATION_STATE = [
        'INIT' => 'init',             // Stan początkowy
        'VALIDATING' => 'validating', // Walidacja danych
        'CALCULATING' => 'calculating', // Obliczanie nowych wartości
        'UPDATING' => 'updating',     // Aktualizacja w bazie danych
        'VERIFYING' => 'verifying',   // Weryfikacja zmian
        'COMPLETED' => 'completed',   // Operacja zakończona sukcesem
        'FAILED' => 'failed'          // Operacja zakończona niepowodzeniem
    ];

    /**
     * Aktualizuje relację z NPC z wykorzystaniem maszyny stanów (FSM)
     * 
     * @param int $npc_id ID NPC
     * @param int $value Wartość do dodania/odjęcia od relacji
     * @return array Wynik operacji
     */
    public function updateNpcRelation($npc_id, $value)
    {
        // Inicjalizacja danych i stanu początkowego FSM
        $state = self::RELATION_STATE['INIT'];
        $result = [
            'success' => false,
            'message' => 'Nie rozpoczęto przetwarzania',
            'state_history' => [$state],
            'npc_id' => $npc_id,
            'value' => $value
        ];

        $npc = null;
        $current_relation = null;
        $new_relation = null;
        $relation_field = null;
        $meet_field = null;

        // Logika FSM - przetwarzanie stanu po stanie
        while ($state !== self::RELATION_STATE['COMPLETED'] && $state !== self::RELATION_STATE['FAILED']) {

            switch ($state) {
                // Stan początkowy - inicjalizacja
                case self::RELATION_STATE['INIT']:
                    // Przejdź do walidacji
                    $state = self::RELATION_STATE['VALIDATING'];
                    $result['state_history'][] = $state;
                    break;

                // Walidacja danych wejściowych
                case self::RELATION_STATE['VALIDATING']:
                    try {
                        // Sprawdź czy użytkownik istnieje
                        if (!get_user_by('ID', $this->user_id)) {
                            $result['message'] = 'Użytkownik nie istnieje';
                            $state = self::RELATION_STATE['FAILED'];
                            break;
                        }

                        // Sprawdź czy NPC istnieje
                        $npc = get_post($npc_id);
                        if (!$npc || $npc->post_type !== 'npc') {
                            $result['message'] = 'NPC nie istnieje';
                            $state = self::RELATION_STATE['FAILED'];
                            break;
                        }

                        // Nazwa pola relacji dla tego NPC
                        $relation_field = 'npc-relation-' . $npc_id;
                        $meet_field = 'npc-meet-' . $npc_id;

                        // Sprawdź czy wartość jest liczbą
                        if (!is_numeric($value)) {
                            $result['message'] = 'Wartość musi być liczbą';
                            $state = self::RELATION_STATE['FAILED'];
                            break;
                        }

                        // Przejdź do obliczania nowej wartości
                        $state = self::RELATION_STATE['CALCULATING'];
                        $result['state_history'][] = $state;
                    } catch (Exception $e) {
                        $result['message'] = 'Błąd walidacji: ' . $e->getMessage();
                        $state = self::RELATION_STATE['FAILED'];
                    }
                    break;

                // Obliczanie nowej wartości relacji
                case self::RELATION_STATE['CALCULATING']:
                    try {
                        // Pobierz aktualną wartość relacji
                        $current_relation = get_field($relation_field, 'user_' . $this->user_id);
                        if ($current_relation === null || $current_relation === '') {
                            $current_relation = 0;
                        } else {
                            $current_relation = intval($current_relation);
                        }

                        $result['old_value'] = $current_relation;

                        // Oblicz nową wartość relacji
                        $new_relation = $current_relation + $value;

                        // Ograniczenie wartości relacji do zakresu -100 do 100
                        $new_relation = max(-100, min(100, $new_relation));

                        $result['new_value'] = $new_relation;
                        $result['field_name'] = $relation_field;

                        // Przejdź do aktualizacji danych
                        $state = self::RELATION_STATE['UPDATING'];
                        $result['state_history'][] = $state;
                    } catch (Exception $e) {
                        $result['message'] = 'Błąd obliczania: ' . $e->getMessage();
                        $state = self::RELATION_STATE['FAILED'];
                    }
                    break;

                // Aktualizacja danych w bazie
                case self::RELATION_STATE['UPDATING']:
                    try {
                        // Zapisz zmiany w relacji
                        $updated = update_field($relation_field, $new_relation, 'user_' . $this->user_id);

                        // Ustaw flagę poznania NPC na true, jeśli jeszcze nie ustawiona
                        $has_met = get_field($meet_field, 'user_' . $this->user_id);
                        if (!$has_met) {
                            update_field($meet_field, true, 'user_' . $this->user_id);
                            $result['first_meeting'] = true;
                        }

                        // Jeśli aktualizacja się nie powiodła, przejdź do weryfikacji
                        if (!$updated) {
                            $state = self::RELATION_STATE['VERIFYING'];
                            $result['state_history'][] = $state;
                        } else {
                            // Aktualizacja się powiodła
                            $state = self::RELATION_STATE['COMPLETED'];
                            $result['state_history'][] = $state;
                        }
                    } catch (Exception $e) {
                        $result['message'] = 'Błąd aktualizacji: ' . $e->getMessage();
                        $state = self::RELATION_STATE['FAILED'];
                    }
                    break;

                // Weryfikacja zmian (gdy update_field zwróciło false)
                case self::RELATION_STATE['VERIFYING']:
                    try {
                        // Dodatkowa weryfikacja czy relacja została zaktualizowana
                        $verified_relation = get_field($relation_field, 'user_' . $this->user_id);

                        if ($verified_relation == $new_relation) {
                            // Relacja została faktycznie zaktualizowana mimo zwróconego false
                            $state = self::RELATION_STATE['COMPLETED'];
                            $result['state_history'][] = $state;
                        } else {
                            $result['message'] = 'Nie udało się zaktualizować relacji';
                            $state = self::RELATION_STATE['FAILED'];
                        }
                    } catch (Exception $e) {
                        $result['message'] = 'Błąd weryfikacji: ' . $e->getMessage();
                        $state = self::RELATION_STATE['FAILED'];
                    }
                    break;

                // Stan nieprawidłowy - zabezpieczenie
                default:
                    $result['message'] = 'Nieprawidłowy stan FSM';
                    $state = self::RELATION_STATE['FAILED'];
                    break;
            }
        }

        // Ustaw końcowy wynik
        if ($state === self::RELATION_STATE['COMPLETED']) {
            $result['success'] = true;
            $result['message'] = $value > 0 ?
                "Zwiększono relację z {$npc->post_title} o {$value}" :
                "Zmniejszono relację z {$npc->post_title} o " . abs($value);

            // Dodaj dodatkowe dane wynikowe
            $result['npc_id'] = $npc_id;
            $result['npc_name'] = $npc->post_title;

            // Informacje o zmianie relacji
            if ($current_relation <= -75 && $new_relation > -75) {
                $result['relation_threshold'] = 'improved_from_hostile';
            } elseif ($current_relation >= 75 && $new_relation < 75) {
                $result['relation_threshold'] = 'reduced_from_friendly';
            } elseif ($new_relation >= 75 && $current_relation < 75) {
                $result['relation_threshold'] = 'became_friendly';
            } elseif ($new_relation <= -75 && $current_relation > -75) {
                $result['relation_threshold'] = 'became_hostile';
            }
        }

        // W trybie debugowania możemy zachować historię stanów
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return $result;
        } else {
            // W produkcji usuń dane debugowania
            unset($result['state_history']);
            return $result;
        }
    }

    /**
     * Ustawia aktualny rejon gracza
     * 
     * @param int $area_id ID rejonu do ustawienia jako aktualny
     * @return array Wynik operacji
     */
    public function setCurrentArea($area_id)
    {
        // Sprawdź czy użytkownik istnieje
        if (!get_user_by('ID', $this->user_id)) {
            return [
                'success' => false,
                'message' => 'Użytkownik nie istnieje'
            ];
        }

        // Sprawdź czy rejon istnieje
        $area = get_post($area_id);
        if (!$area || $area->post_type !== 'tereny') {
            return [
                'success' => false,
                'message' => 'Rejon nie istnieje'
            ];
        }

        // Pobierz aktualne dostępne rejony
        $available_areas = get_field('available_areas', 'user_' . $this->user_id);

        // Sprawdź czy rejon jest dostępny dla użytkownika
        if (is_array($available_areas) && !in_array($area_id, $available_areas)) {
            return [
                'success' => false,
                'message' => "Rejon {$area->post_title} nie jest dostępny dla tego gracza"
            ];
        }

        // Ustawienie aktualnego rejonu
        $updated = update_field('current_area', $area_id, 'user_' . $this->user_id);

        if (!$updated) {
            // Dodatkowa weryfikacja czy rejon został ustawiony
            $current_area = get_field('current_area', 'user_' . $this->user_id);
            if ($current_area && $current_area->ID == $area_id) {
                return [
                    'success' => true,
                    'message' => "Zmieniono aktualny rejon na {$area->post_title}",
                    'area_id' => $area_id,
                    'area_name' => $area->post_title
                ];
            }

            return [
                'success' => false,
                'message' => 'Nie udało się zmienić aktualnego rejonu'
            ];
        }

        return [
            'success' => true,
            'message' => "Zmieniono aktualny rejon na {$area->post_title}",
            'area_id' => $area_id,
            'area_name' => $area->post_title
        ];
    }

    /**
     * Pobierz dane użytkownika
     * 
     * @return array Dane użytkownika
     */
    public function getUserData()
    {
        $user_data = [];

        // Pobierz wszystkie potrzebne dane użytkownika
        $stats = get_field('stats', 'user_' . $this->user_id) ?: [];
        $skills = get_field('skills', 'user_' . $this->user_id) ?: [];
        $backpack = get_field('backpack', 'user_' . $this->user_id) ?: [];
        $vitality = get_field('vitality', 'user_' . $this->user_id) ?: [];
        $progress = get_field('progress', 'user_' . $this->user_id) ?: [];

        // Podstawowe informacje
        $user_data['nick'] = get_field('nick', 'user_' . $this->user_id);
        $user_data['avatar'] = get_field('avatar', 'user_' . $this->user_id);
        $user_data['story'] = get_field('story', 'user_' . $this->user_id);
        $user_data['user_class'] = get_field('user_class', 'user_' . $this->user_id);

        // Statystyki
        $user_data['stats'] = $stats;

        // Umiejętności
        $user_data['skills'] = $skills;

        // Plecak
        $user_data['backpack'] = $backpack;

        // Witalność
        $user_data['vitality'] = $vitality;

        // Postęp
        $user_data['progress'] = $progress;

        return $user_data;
    }
}

/**
 * Inicjalizacja ManagerUser
 */
function init_manager_user()
{
    // Utwórz instancję ManagerUser, aby zarejestrować endpointy REST API
    new ManagerUser();
}
add_action('init', 'init_manager_user');

/**
 * Dodaj REST API URL do skryptów JavaScript
 */
add_action('wp_enqueue_scripts', 'add_user_manager_rest_api_data');
function add_user_manager_rest_api_data()
{
    wp_localize_script('axios', 'userManagerData', [
        'rest_url' => rest_url('game/v1'),
        'nonce' => wp_create_nonce('game_user_manager_nonce')
    ]);
}
