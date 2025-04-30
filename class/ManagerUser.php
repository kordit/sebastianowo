<?php

/**
 * Klasa ManagerUser - zarządza funkcjonalnościami związanymi z użytkownikami poprzez AJAX
 * 
 * @package game
 */

class ManagerUser
{
    /**
     * Konstruktor klasy, inicjuje hooki AJAX
     */
    public function __construct()
    {
        // Inicjacja hooków AJAX dla zalogowanych użytkowników
        add_action('wp_ajax_get_user_data', array($this, 'get_user_data'));
        add_action('wp_ajax_update_user_data', array($this, 'update_user_data'));
        add_action('wp_ajax_validate_user_requirement', array($this, 'validate_user_requirement'));
        add_action('wp_ajax_update_user_level', array($this, 'update_user_level'));
        add_action('wp_ajax_get_user_missions', array($this, 'get_user_missions'));
        add_action('wp_ajax_update_user_mission', array($this, 'update_user_mission'));
        add_action('wp_ajax_get_user_inventory', array($this, 'get_user_inventory'));
        add_action('wp_ajax_update_user_inventory', array($this, 'update_user_inventory'));

        // Inicjacja hooków AJAX dla niezalogowanych użytkowników (np. rejestracja)
        add_action('wp_ajax_nopriv_register_user', array($this, 'register_user'));
    }

    /**
     * Sprawdza nonce dla bezpieczeństwa AJAX
     * 
     * @return bool True jeśli nonce jest poprawny
     */
    private function verify_nonce()
    {
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'user_manager_nonce')) {
            wp_send_json_error(array('message' => 'Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.'));
            return false;
        }
        return true;
    }

    /**
     * Pobiera dane o użytkowniku
     */
    public function get_user_data()
    {
        if (!$this->verify_nonce()) return;

        // Domyślnie pobieramy dane aktualnego użytkownika
        $user_id = get_current_user_id();

        // Jeśli podano ID użytkownika i aktualny użytkownik ma uprawnienia admina
        if (isset($_REQUEST['user_id']) && current_user_can('administrator')) {
            $user_id = intval($_REQUEST['user_id']);
        }

        // Jeśli brak ID użytkownika, zwróć błąd
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Użytkownik nie jest zalogowany.'));
            return;
        }

        // Pobieramy podstawowe dane użytkownika
        $user_data = get_userdata($user_id);
        if (!$user_data) {
            wp_send_json_error(array('message' => 'Nie znaleziono użytkownika.'));
            return;
        }

        // Pobieramy dane ACF użytkownika
        $user_class = get_field('user_class', 'user_' . $user_id);
        $user_level = get_field('user_level', 'user_' . $user_id);
        $user_experience = get_field('user_experience', 'user_' . $user_id);
        $user_stats = get_field('user_stats', 'user_' . $user_id);

        // Przygotowanie odpowiedzi
        $response = array(
            'id' => $user_id,
            'username' => $user_data->user_login,
            'nicename' => $user_data->user_nicename,
            'email' => $user_data->user_email,
            'display_name' => $user_data->display_name,
            'class' => $user_class,
            'level' => $user_level,
            'experience' => $user_experience,
            'stats' => $user_stats
        );

        wp_send_json_success($response);
    }

    /**
     * Aktualizuje dane użytkownika
     */
    public function update_user_data()
    {
        if (!$this->verify_nonce()) return;

        // Sprawdzamy, czy są dane do aktualizacji
        if (!isset($_REQUEST['user_data']) || empty($_REQUEST['user_data'])) {
            wp_send_json_error(array('message' => 'Brak danych do aktualizacji.'));
            return;
        }

        // Pobieramy ID użytkownika
        $user_id = get_current_user_id();
        if (isset($_REQUEST['user_id']) && current_user_can('administrator')) {
            $user_id = intval($_REQUEST['user_id']);
        }

        // Jeśli brak ID użytkownika, zwróć błąd
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Użytkownik nie jest zalogowany.'));
            return;
        }

        // Parsujemy dane JSON
        $user_data = json_decode(stripslashes($_REQUEST['user_data']), true);
        if (!$user_data) {
            wp_send_json_error(array('message' => 'Niepoprawny format danych.'));
            return;
        }

        // Aktualizujemy pola ACF
        if (isset($user_data['class'])) {
            update_field('user_class', $user_data['class'], 'user_' . $user_id);
        }

        if (isset($user_data['stats']) && is_array($user_data['stats'])) {
            update_field('user_stats', $user_data['stats'], 'user_' . $user_id);
        }

        // Aktualizacja podstawowych danych użytkownika
        $wp_user_data = array('ID' => $user_id);

        if (isset($user_data['display_name'])) {
            $wp_user_data['display_name'] = sanitize_text_field($user_data['display_name']);
        }

        if (isset($user_data['email'])) {
            $wp_user_data['user_email'] = sanitize_email($user_data['email']);
        }

        if (count($wp_user_data) > 1) { // Sprawdzamy, czy jest więcej niż samo ID
            wp_update_user($wp_user_data);
        }

        wp_send_json_success(array('message' => 'Dane użytkownika zaktualizowane pomyślnie.'));
    }

    /**
     * Aktualizuje poziom użytkownika
     */
    public function update_user_level()
    {
        if (!$this->verify_nonce()) return;

        // Pobieramy ID użytkownika
        $user_id = get_current_user_id();
        if (isset($_REQUEST['user_id']) && current_user_can('administrator')) {
            $user_id = intval($_REQUEST['user_id']);
        }

        // Sprawdzamy poziom i doświadczenie
        $level = isset($_REQUEST['level']) ? intval($_REQUEST['level']) : null;
        $experience = isset($_REQUEST['experience']) ? intval($_REQUEST['experience']) : null;

        if ($level === null && $experience === null) {
            wp_send_json_error(array('message' => 'Nie podano danych do aktualizacji.'));
            return;
        }

        // Aktualizujemy poziom
        if ($level !== null) {
            update_field('user_level', $level, 'user_' . $user_id);
        }

        // Aktualizujemy doświadczenie
        if ($experience !== null) {
            update_field('user_experience', $experience, 'user_' . $user_id);
        }

        // Jeśli podano tylko doświadczenie, sprawdź czy użytkownik awansował
        if ($level === null && $experience !== null) {
            $current_level = get_field('user_level', 'user_' . $user_id);
            $new_level = $this->calculate_level_from_experience($experience);

            if ($new_level > $current_level) {
                update_field('user_level', $new_level, 'user_' . $user_id);
                $response = array(
                    'message' => 'Poziom i doświadczenie zaktualizowane pomyślnie.',
                    'level_up' => true,
                    'new_level' => $new_level
                );
                wp_send_json_success($response);
                return;
            }
        }

        wp_send_json_success(array('message' => 'Poziom i doświadczenie zaktualizowane pomyślnie.'));
    }

    /**
     * Oblicza poziom na podstawie doświadczenia
     * 
     * @param int $experience Ilość doświadczenia
     * @return int Obliczony poziom
     */
    private function calculate_level_from_experience($experience)
    {
        // Implementacja algorytmu obliczania poziomu na podstawie doświadczenia
        // To jest przykładowa implementacja, można ją dostosować do potrzeb gry
        $level = 1;
        $threshold = 1000; // Próg doświadczenia dla poziomu 2

        while ($experience >= $threshold) {
            $level++;
            $threshold = $threshold * 1.5; // Każdy następny poziom wymaga o 50% więcej doświadczenia
        }

        return $level;
    }

    /**
     * Pobiera misje użytkownika
     */
    public function get_user_missions()
    {
        if (!$this->verify_nonce()) return;

        $user_id = get_current_user_id();
        if (isset($_REQUEST['user_id']) && current_user_can('administrator')) {
            $user_id = intval($_REQUEST['user_id']);
        }

        // Pobieramy misje użytkownika
        $user_missions = get_field('user_missions', 'user_' . $user_id);

        if (!$user_missions) {
            $user_missions = array();
        }

        wp_send_json_success($user_missions);
    }

    /**
     * Aktualizuje misje użytkownika
     */
    public function update_user_mission()
    {
        if (!$this->verify_nonce()) return;

        $user_id = get_current_user_id();
        if (isset($_REQUEST['user_id']) && current_user_can('administrator')) {
            $user_id = intval($_REQUEST['user_id']);
        }

        // Sprawdzamy dane misji
        if (!isset($_REQUEST['mission_data']) || empty($_REQUEST['mission_data'])) {
            wp_send_json_error(array('message' => 'Brak danych misji do aktualizacji.'));
            return;
        }

        // Parsujemy dane JSON
        $mission_data = json_decode(stripslashes($_REQUEST['mission_data']), true);
        if (!$mission_data || !isset($mission_data['mission_id'])) {
            wp_send_json_error(array('message' => 'Niepoprawny format danych misji.'));
            return;
        }

        // Pobieramy aktualne misje użytkownika
        $user_missions = get_field('user_missions', 'user_' . $user_id);
        if (!is_array($user_missions)) {
            $user_missions = array();
        }

        // Sprawdzamy, czy misja istnieje w tablicy
        $mission_exists = false;
        foreach ($user_missions as $key => $mission) {
            if ($mission['mission_id'] == $mission_data['mission_id']) {
                $user_missions[$key] = array_merge($mission, $mission_data);
                $mission_exists = true;
                break;
            }
        }

        // Jeśli misja nie istnieje, dodajemy ją
        if (!$mission_exists) {
            $user_missions[] = $mission_data;
        }

        // Aktualizujemy pole z misjami
        update_field('user_missions', $user_missions, 'user_' . $user_id);

        wp_send_json_success(array('message' => 'Misja zaktualizowana pomyślnie.'));
    }

    /**
     * Pobiera ekwipunek użytkownika
     */
    public function get_user_inventory()
    {
        if (!$this->verify_nonce()) return;

        $user_id = get_current_user_id();
        if (isset($_REQUEST['user_id']) && current_user_can('administrator')) {
            $user_id = intval($_REQUEST['user_id']);
        }

        // Pobieramy ekwipunek użytkownika
        $user_inventory = get_field('user_inventory', 'user_' . $user_id);

        if (!$user_inventory) {
            $user_inventory = array();
        }

        wp_send_json_success($user_inventory);
    }

    /**
     * Aktualizuje ekwipunek użytkownika
     */
    public function update_user_inventory()
    {
        if (!$this->verify_nonce()) return;

        $user_id = get_current_user_id();
        if (isset($_REQUEST['user_id']) && current_user_can('administrator')) {
            $user_id = intval($_REQUEST['user_id']);
        }

        // Sprawdzamy dane ekwipunku
        if (!isset($_REQUEST['inventory_data'])) {
            wp_send_json_error(array('message' => 'Brak danych ekwipunku do aktualizacji.'));
            return;
        }

        // Parsujemy dane JSON
        $inventory_data = json_decode(stripslashes($_REQUEST['inventory_data']), true);
        if (!$inventory_data) {
            wp_send_json_error(array('message' => 'Niepoprawny format danych ekwipunku.'));
            return;
        }

        // Aktualizujemy ekwipunek
        update_field('user_inventory', $inventory_data, 'user_' . $user_id);

        wp_send_json_success(array('message' => 'Ekwipunek zaktualizowany pomyślnie.'));
    }

    /**
     * Rejestruje nowego użytkownika
     */
    public function register_user()
    {
        if (!$this->verify_nonce()) return;

        // Sprawdzamy dane rejestracji
        $username = isset($_REQUEST['username']) ? sanitize_user($_REQUEST['username']) : '';
        $email = isset($_REQUEST['email']) ? sanitize_email($_REQUEST['email']) : '';
        $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
        $display_name = isset($_REQUEST['display_name']) ? sanitize_text_field($_REQUEST['display_name']) : '';
        $user_class = isset($_REQUEST['user_class']) ? sanitize_text_field($_REQUEST['user_class']) : '';

        // Sprawdzamy, czy podano wymagane dane
        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Wszystkie pola są wymagane.'));
            return;
        }

        // Sprawdzamy, czy użytkownik o podanej nazwie lub emailu już istnieje
        if (username_exists($username)) {
            wp_send_json_error(array('message' => 'Użytkownik o podanej nazwie już istnieje.'));
            return;
        }

        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Podany adres email jest już używany.'));
            return;
        }

        // Tworzymy nowego użytkownika
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
            return;
        }

        // Ustawiamy rolę użytkownika
        $user = new WP_User($user_id);
        $user->set_role('gracz');

        // Aktualizujemy dodatkowe dane
        if (!empty($display_name)) {
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $display_name
            ));
        }

        // Aktualizujemy pola ACF
        if (!empty($user_class)) {
            update_field('user_class', $user_class, 'user_' . $user_id);
        }

        // Inicjujemy podstawowe wartości
        update_field('user_level', 1, 'user_' . $user_id);
        update_field('user_experience', 0, 'user_' . $user_id);
        update_field('user_inventory', array(), 'user_' . $user_id);
        update_field('user_missions', array(), 'user_' . $user_id);

        // Inicjujemy podstawowe statystyki w zależności od klasy postaci
        $default_stats = $this->get_default_stats_for_class($user_class);
        update_field('user_stats', $default_stats, 'user_' . $user_id);

        wp_send_json_success(array(
            'message' => 'Użytkownik zarejestrowany pomyślnie.',
            'user_id' => $user_id
        ));
    }

    /**
     * Zwraca domyślne statystyki dla określonej klasy postaci
     * 
     * @param string $user_class Klasa postaci
     * @return array Domyślne statystyki
     */
    private function get_default_stats_for_class($user_class)
    {
        // Przykładowe statystyki dla różnych klas postaci
        $default_stats = array(
            'strength' => 5,
            'agility' => 5,
            'intelligence' => 5,
            'charisma' => 5,
            'health' => 100,
            'energy' => 100
        );

        // Modyfikujemy statystyki w zależności od klasy
        switch ($user_class) {
            case 'warrior':
                $default_stats['strength'] = 8;
                $default_stats['health'] = 120;
                break;

            case 'thief':
                $default_stats['agility'] = 8;
                $default_stats['charisma'] = 7;
                break;

            case 'mage':
                $default_stats['intelligence'] = 8;
                $default_stats['energy'] = 120;
                break;

                // Możesz dodać więcej klas według potrzeb
        }

        return $default_stats;
    }
    /**
     * Waliduje czy użytkownik spełnia określone wymagania (posiada przedmiot, pieniądze, umiejętność)
     * Wymagania definiowane są na podstawie pól z register_fields.php
     * Walidacja jest opcjonalna i wykonywana tylko gdy zdefiniowana w zapytaniu
     */
    public function validate_user_requirement()
    {
        if (!$this->verify_nonce()) return;

        // Pobieramy ID użytkownika
        $user_id = get_current_user_id();
        if (isset($_REQUEST['user_id']) && current_user_can('administrator')) {
            $user_id = intval($_REQUEST['user_id']);
        }

        if (!$user_id) {
            wp_send_json_error(array('message' => 'Użytkownik nie jest zalogowany.'));
            return;
        }

        // Sprawdź, czy podano typ wymagania do sprawdzenia
        if (!isset($_REQUEST['req_type'])) {
            wp_send_json_error(array('message' => 'Nie podano typu wymagania do sprawdzenia.'));
            return;
        }

        // Sprawdzamy, czy walidacja ma być wykonana
        $validate = isset($_REQUEST['validate']) ? filter_var($_REQUEST['validate'], FILTER_VALIDATE_BOOLEAN) : true;
        $req_type = sanitize_text_field($_REQUEST['req_type']);
        $req_value = isset($_REQUEST['req_value']) ? sanitize_text_field($_REQUEST['req_value']) : '';
        $req_amount = isset($_REQUEST['req_amount']) ? intval($_REQUEST['req_amount']) : 0;

        // Zapisujemy info do debug loga
        error_log("Walidacja użytkownika ID: {$user_id}, typ: {$req_type}, wartość: {$req_value}, ilość: {$req_amount}, czy walidować: " . ($validate ? 'tak' : 'nie'));

        $result = false;
        $message = '';

        // Jeśli walidacja jest wyłączona, zakładamy że wymaganie jest spełnione
        if (!$validate) {
            $result = true;
            $message = 'Walidacja pominięta.';
            error_log("Walidacja pominięta dla użytkownika ID: {$user_id}");
        } else {

            switch ($req_type) {
                // Sprawdzenie pieniędzy
                case 'gold':
                    $backpack = get_field('backpack', 'user_' . $user_id);
                    $user_gold = isset($backpack['gold']) ? intval($backpack['gold']) : 0;
                    $result = ($user_gold >= $req_amount);
                    $message = $result ? 'Masz wystarczająco dużo złotych.' : 'Nie masz wystarczającej ilości złotych.';
                    error_log("Złote użytkownika: {$user_gold}, wymagane: {$req_amount}, wynik: " . ($result ? 'true' : 'false'));
                    break;

                case 'cigarettes':
                    $backpack = get_field('backpack', 'user_' . $user_id);
                    $user_cigarettes = isset($backpack['cigarettes']) ? intval($backpack['cigarettes']) : 0;
                    $result = ($user_cigarettes >= $req_amount);
                    $message = $result ? 'Masz wystarczająco dużo papierosów.' : 'Nie masz wystarczającej ilości papierosów.';
                    error_log("Papierosy użytkownika: {$user_cigarettes}, wymagane: {$req_amount}, wynik: " . ($result ? 'true' : 'false'));
                    break;

                // Sprawdzenie statystyk
                case 'stats':
                    if (empty($req_value)) {
                        wp_send_json_error(array('message' => 'Nie podano nazwy statystyki do sprawdzenia.'));
                        return;
                    }

                    $stats = get_field('stats', 'user_' . $user_id);
                    $user_stat = isset($stats[$req_value]) ? intval($stats[$req_value]) : 0;
                    $result = ($user_stat >= $req_amount);
                    $message = $result ? "Masz wystarczający poziom statystyki {$req_value}." : "Nie masz wystarczającego poziomu statystyki {$req_value}.";
                    error_log("Statystyka {$req_value}: {$user_stat}, wymagany poziom: {$req_amount}, wynik: " . ($result ? 'true' : 'false'));
                    break;

                // Sprawdzenie umiejętności
                case 'skills':
                    if (empty($req_value)) {
                        wp_send_json_error(array('message' => 'Nie podano nazwy umiejętności do sprawdzenia.'));
                        return;
                    }

                    $skills = get_field('skills', 'user_' . $user_id);
                    $user_skill = isset($skills[$req_value]) ? intval($skills[$req_value]) : 0;
                    $result = ($user_skill >= $req_amount);
                    $message = $result ? "Masz wystarczający poziom umiejętności {$req_value}." : "Nie masz wystarczającego poziomu umiejętności {$req_value}.";
                    error_log("Umiejętność {$req_value}: {$user_skill}, wymagany poziom: {$req_amount}, wynik: " . ($result ? 'true' : 'false'));
                    break;

                // Sprawdzenie życia/energii
                case 'vitality':
                    if (empty($req_value)) {
                        wp_send_json_error(array('message' => 'Nie podano typu witalności do sprawdzenia.'));
                        return;
                    }

                    $vitality = get_field('vitality', 'user_' . $user_id);
                    $user_vitality = isset($vitality[$req_value]) ? intval($vitality[$req_value]) : 0;
                    $result = ($user_vitality >= $req_amount);
                    $message = $result ? "Masz wystarczający poziom {$req_value}." : "Nie masz wystarczającego poziomu {$req_value}.";
                    error_log("Witalność {$req_value}: {$user_vitality}, wymagany poziom: {$req_amount}, wynik: " . ($result ? 'true' : 'false'));
                    break;

                // Sprawdzenie przedmiotu
                case 'item':
                    if (empty($req_value)) {
                        wp_send_json_error(array('message' => 'Nie podano ID przedmiotu do sprawdzenia.'));
                        return;
                    }

                    $items = get_field('items', 'user_' . $user_id);
                    $has_item = false;
                    $item_quantity = 0;

                    if (is_array($items)) {
                        foreach ($items as $item) {
                            if (isset($item['item']) && $item['item']->ID == $req_value) {
                                $item_quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
                                $has_item = ($item_quantity >= $req_amount);
                                break;
                            }
                        }
                    }

                    $result = $has_item;
                    $message = $result ? "Masz wymaganą ilość przedmiotu." : "Nie masz wymaganej ilości przedmiotu.";
                    error_log("Przedmiot ID: {$req_value}, ilość: {$item_quantity}, wymagana ilość: {$req_amount}, wynik: " . ($result ? 'true' : 'false'));
                    break;

                case 'equipped_item':
                    if (empty($req_value)) {
                        wp_send_json_error(array('message' => 'Nie podano typu przedmiotu do sprawdzenia.'));
                        return;
                    }

                    $equipped_items = get_field('equipped_items', 'user_' . $user_id);
                    $item_field = $req_value . '_item'; // np. chest_item, legs_item, bottom_item
                    $has_equipped_item = isset($equipped_items[$item_field]) && !empty($equipped_items[$item_field]);

                    $result = $has_equipped_item;
                    if ($req_amount > 0) { // Jeśli podano konkretne ID przedmiotu
                        $result = $has_equipped_item && $equipped_items[$item_field]->ID == $req_amount;
                    }
                    $message = $result ? "Masz założony wymagany przedmiot." : "Nie masz założonego wymaganego przedmiotu.";
                    error_log("Założony przedmiot typu: {$req_value}, wynik: " . ($result ? 'true' : 'false'));
                    break;

                default:
                    wp_send_json_error(array('message' => 'Nieznany typ wymagania.'));
                    return;
            }

            // Zwracamy wynik walidacji
            $response = array(
                'success' => $result,
                'message' => $message,
                'debug' => array(
                    'req_type' => $req_type,
                    'req_value' => $req_value,
                    'req_amount' => $req_amount,
                    'user_id' => $user_id
                )
            );

            wp_send_json_success($response);
        }
    }
}

// Inicjalizacja klasy
$user_manager = new ManagerUser();
