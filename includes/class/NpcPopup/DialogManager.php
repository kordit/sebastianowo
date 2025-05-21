<?php

/**
 * Klasa DialogManager
 * 
 * Zarządza dialogami NPC, ich warunkami i akcjami.
 * 
 * @package Game
 * @subpackage NpcPopup
 * @since 1.0.0
 */

require_once get_template_directory() . '/includes/class/NpcPopup/NpcLogger.php';
require_once get_template_directory() . '/includes/class/NpcPopup/ContextValidator.php';

class DialogManager
{
    /**
     * ID NPC
     *
     * @var int
     */
    private int $npc_id;

    /**
     * ID użytkownika
     *
     * @var int
     */
    private int $user_id;

    /**
     * Logger
     *
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Konfiguracja dialogów
     *
     * @var array
     */
    private array $dialogs_config;

    /**
     * LocationConditionChecker
     *
     * @var LocationConditionChecker|null
     */
    private ?LocationConditionChecker $locationConditionChecker = null;

    /**
     * Konstruktor klasy DialogManager
     *
     * @param NpcLogger $logger Logger do zapisywania informacji
     */
    public function __construct(NpcLogger $logger)
    {
        $this->logger = $logger;
        $this->npc_id = 0; // Wartość domyślna, zostanie zaktualizowana podczas przetwarzania dialogu
        $this->user_id = 0; // Wartość domyślna, zostanie zaktualizowana podczas przetwarzania dialogu
    }

    /**
     * Ustawia ID NPC dla kontekstu operacji
     * 
     * @param int $npc_id ID NPC
     * @return void
     */
    public function setNpcId(int $npc_id): void
    {
        $this->npc_id = $npc_id;
    }

    /**
     * Ustawia ID użytkownika dla kontekstu operacji
     * 
     * @param int $user_id ID użytkownika
     * @return void
     */
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function filter_questions_with_user_context(array $dialogs, $userContext, array $location_info): array
    {
        if (empty($dialogs)) {
            $this->logger->debug_log("DialogManager: Brak dialogów do filtrowania.");
            return [];
        }

        $filtered_dialogs = [];

        $validator = new ContextValidator($userContext, $this->logger);

        foreach ($dialogs as $dialog) {
            $conditions = $dialog['layout_settings']['visibility_settings'] ?? [];

            $all_conditions_pass = true;

            foreach ($conditions as $condition) {
                $context_for_condition = $validator->validateCondition($condition, $location_info);
                $result = $this->validate_dialog_condition($condition, $context_for_condition);

                if (!$result) {
                    $all_conditions_pass = false;
                    $this->logger->debug_log("DialogManager: Warunek dialogu niespełniony", [
                        'condition' => $condition,
                        'context' => $context_for_condition,
                    ]);
                    break;
                }
            }

            if ($all_conditions_pass) {
                $filtered_dialogs[] = $dialog;
            }
        }

        return $filtered_dialogs;
    }



    /**
     * Filtruje odpowiedzi z użyciem kontekstu użytkownika
     * 
     * @param array $answers Odpowiedzi do filtrowania
     * @param object $userContext Kontekst użytkownika
     * @param array $location_info Informacje o lokalizacji
     * @return array Przefiltrowane odpowiedzi
     */
    public function filter_answers_with_user_context(array $answers, $userContext, array $location_info): array
    {
        if (empty($answers)) {
            $this->logger->debug_log("DialogManager: Brak odpowiedzi do filtrowania.");
            return [];
        }

        $filtered_answers = [];
        foreach ($answers as $answer_key => $answer_data) {
            $answer_text_log = $answer_data['answer_text'] ?? (is_array($answer_key) ? json_encode($answer_key) : $answer_key);

            $conditions = $answer_data['visibility_conditions'] ?? [];
            $all_conditions_pass = true;
            $failed_reason = [];
            $validator = new ContextValidator($userContext);
            foreach ($conditions as $condition) {
                $context_for_condition = $validator->validateCondition($condition, $location_info);
                $result = $this->validate_dialog_condition($condition, $context_for_condition);
                if (!$result) {
                    $all_conditions_pass = false;
                    $failed_reason[] = [
                        'condition' => $condition,
                        'context' => $context_for_condition,
                        'reason' => 'Warunek nie został spełniony'
                    ];

                    break;
                }
            }
            if ($all_conditions_pass) {
                $filtered_answers[] = $answer_data; // Zmieniono z $filtered_answers[$answer_key] = $answer_data;
            }
        }
        return $filtered_answers;
    }

    /**
     * Waliduje warunek dialogu na podstawie kontekstu
     * 
     * @param array $condition Warunek do sprawdzenia
     * @param array $context Kontekst walidacji
     * @return bool Czy warunek jest spełniony
     */
    private function validate_dialog_condition(array $condition, array $context): bool
    {
        // Jeśli nie ma zdefiniowanego warunku, domyślnie zwróć true
        if (empty($condition)) {
            return true;
        }

        $type = $condition['acf_fc_layout'] ?? '';
        $type = str_replace('condition_', '', $type);


        // Obsługa poszczególnych typów warunków



        switch ($type) {
            case 'location':
                return $this->validate_location_condition($condition, $context);

            case 'mission':
                return $this->validate_mission_condition($condition, $context);

            case 'item':
                return $this->validate_item_condition($condition, $context);

            case 'relation':
                return $this->validate_relation_condition($condition, $context);

            case 'task':
                return $this->validate_task_condition($condition, $context);

            case 'resource':
                return $this->validate_resource_condition($condition, $context);

            case 'skill':
                return $this->validate_skill_condition($condition, $context);

            default:
                $this->logger->debug_log("Nieobsługiwany typ warunku: $type");
                return false; // Domyślnie przepuszczamy, jeśli nie znamy typu warunku
        }
    }

    /**
     * Waliduje warunek lokalizacji
     */
    private function validate_location_condition(array $condition, array $context): bool
    {
        $user_area_slug = $context['current_location_text'] ?? '';
        $area_id = $condition['area'] ?? null;

        $this->logger->debug_log('CONTEXT (location)', $context);
        $this->logger->debug_log('CONDITION (location)', $condition);

        if (!$area_id) {
            return true; // brak wymaganego area – przepuszczamy
        }

        // Pobierz slug lokalizacji z ID
        $required_slug = get_post_field('post_name', $area_id);

        if (!$required_slug) {
            $this->logger->debug_log("Nie znaleziono sluga dla area_id: $area_id");
            return false;
        }

        return $user_area_slug === $required_slug;
    }


    private function validate_task_condition(array $condition, array $context): bool
    {
        $tasks = $context['task'] ?? [];

        $mission_id = strval($condition['mission_id'] ?? '');
        $task_id = strval($condition['task_id'] ?? '');
        $required_status = $condition['status'] ?? null;
        $operator = $condition['condition'] ?? 'is'; // domyślnie 'is'

        if (!$mission_id || !$task_id || !$required_status) {
            return true;
        }

        if (!isset($tasks[$mission_id][$task_id])) {
            return false;
        }

        $task_status = $tasks[$mission_id][$task_id]['status'] ?? null;

        // Porównanie
        switch ($operator) {
            case 'is':
                return $task_status === $required_status;
            case 'is_not':
                return $task_status !== $required_status;
            case 'in':
                return is_array($required_status) && in_array($task_status, $required_status, true);
            case 'not_in':
                return is_array($required_status) && !in_array($task_status, $required_status, true);
            default:
                $this->logger->debug_log("TASK_CONDITION nieznany operator '$operator' – przepuszczam");
                return false;
        }
    }



    /**
     * Waliduje warunek misji
     */
    private function validate_mission_condition(array $condition, array $context): bool
    {
        $missions = $context['missions'] ?? [];
        $mission_id = $condition['mission_id'] ?? 0;
        $required_status = $condition['mission_status'] ?? '';

        if (empty($mission_id) || empty($required_status)) {
            return true; // Jeśli brakuje wymaganych danych, przepuszczamy
        }

        foreach ($missions as $mission) {
            if ($mission['id'] == $mission_id && $mission['status'] === $required_status) {
                return true;
            }
        }

        return false;
    }

    /**
     * Waliduje warunek przedmiotu
     */
    private function validate_item_condition(array $condition, array $context): bool
    {
        $items = $context['items'] ?? [];
        $item_id = $condition['item_id'] ?? 0;
        $required_count = $condition['required_count'] ?? 1;

        if (empty($item_id)) {
            return true; // Jeśli brakuje ID przedmiotu, przepuszczamy
        }

        foreach ($items as $item) {
            if ($item['id'] == $item_id && $item['count'] >= $required_count) {
                return true;
            }
        }

        return false;
    }

    /**
     * Waliduje warunek relacji
     */
    private function validate_relation_condition(array $condition, array $context): bool
    {
        $relations = $context['relations'] ?? [];
        $npc_id = $condition['npc_id'] ?? 0;
        $min_value = $condition['min_value'] ?? 0;

        if (empty($npc_id)) {
            return true; // Jeśli brakuje ID NPC, przepuszczamy
        }

        foreach ($relations as $relation) {
            if ($relation['npc_id'] == $npc_id && $relation['value'] >= $min_value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Waliduje warunek zasobu
     */
    private function validate_resource_condition(array $condition, array $context): bool
    {
        $resources = $context['resources'] ?? [];
        $resource_type = $condition['resource_type'] ?? '';
        $min_value = $condition['min_value'] ?? 0;

        if (empty($resource_type)) {
            return true; // Jeśli brakuje typu zasobu, przepuszczamy
        }

        foreach ($resources as $type => $value) {
            if ($type === $resource_type && $value >= $min_value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Waliduje warunek umiejętności
     */
    private function validate_skill_condition(array $condition, array $context): bool
    {
        $skills = $context['skills'] ?? [];
        $skill_type = $condition['skill_type'] ?? '';
        $min_value = $condition['min_value'] ?? 0;

        if (empty($skill_type)) {
            return true; // Jeśli brakuje typu umiejętności, przepuszczamy
        }

        foreach ($skills as $type => $value) {
            if ($type === $skill_type && $value >= $min_value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Upraszcza strukturę dialogu do formatu używanego przez frontend
     * 
     * @param array $dialog Dialog do uproszczenia
     * @return array Uproszczony dialog
     */
    public function simplify_dialog(array $dialog): array
    {
        // Mapowanie pól z formatu ACF na format używany przez frontend
        $simplified = [
            'id' => $dialog['id_pola'] ?? ($dialog['dialog_id'] ?? ''),
            'text' => $dialog['question'] ?? ($dialog['text'] ?? ''),
            'answers' => []
        ];

        // Obsługa odpowiedzi (sprawdź oba możliwe klucze: anwsers i answers)
        $answers = $dialog['anwsers'] ?? ($dialog['answers'] ?? []);

        if (is_array($answers)) {
            foreach ($answers as $answer) {
                $simplified['answers'][] = [
                    'id' => $answer['answer_id'] ?? '',
                    'text' => $answer['anwser_text'] ?? ($answer['text'] ?? ''),
                    'next_dialog' => $answer['go_to_id'] ?? ($answer['next_dialog'] ?? '')
                ];
            }
        }

        return $simplified;
    }

    /**
     * Pobiera konkretny dialog z listy dialogów
     *
     * @param string $dialog_id ID dialogu do pobrania
     * @param array $dialogs Lista wszystkich dialogów NPC
     * @return array|null Dialog lub null, jeśli nie znaleziono
     */
    public function get_dialog(string $dialog_id, array $dialogs): ?array
    {
        foreach ($dialogs as $dialog) {
            if (!isset($dialog['dialog_id']) || $dialog['dialog_id'] != $dialog_id) {
                continue;
            }

            // Zwracamy dialog bez sprawdzania warunków
            return $dialog;
        }

        $this->logger->log("Dialog {$dialog_id} nie istnieje", 'warning');
        return null;
    }

    /**
     * Znajduje pierwszy dialog, który pasuje do kontekstu użytkownika
     *
     * @param array $dialogs Lista dialogów do przeszukania
     * @param object $userContext Kontekst użytkownika
     * @param array $location_info Informacje o lokalizacji
     * @return array|null Pierwszy pasujący dialog lub null
     */
    public function get_first_matching_dialog(array $dialogs, $userContext, array $location_info): ?array
    {
        if (empty($dialogs)) {
            $this->logger->debug_log("Brak dialogów do filtrowania");
            return null;
        }


        $this->logger->debug_log('dialog', $dialog);
        foreach ($dialogs as $dialog) {
            // Jeżeli dialog ma odpowiedzi, filtrujemy je według kontekstu użytkownika
            if (isset($dialog['anwsers']) && is_array($dialog['anwsers'])) {
                $filtered_answers = $this->filter_answers_with_user_context($dialog['anwsers'], $userContext, $location_info);

                // Jeżeli po filtrowaniu nie ma odpowiedzi, przejdź do następnego dialogu
                if (empty($filtered_answers)) {
                    continue;
                }

                // Zaktualizuj dialog z przefiltrowanymi odpowiedziami
                $dialog['anwsers'] = $filtered_answers;
                return $dialog;
            }
        }

        $this->logger->debug_log("Nie znaleziono pasującego dialogu");
        return null;
    }

    /**
     * Przetwarza akcje powiązane z dialogiem
     *
     * @param array $dialog Dialog, którego akcje mają być przetworzone
     * @return array Tablica z powiadomieniami, które powinny być wyświetlone
     */
    public function process_dialog_actions(array $dialog): array
    {
        $notifications = [];

        if (empty($dialog['actions']) || !is_array($dialog['actions']) || $this->user_id === 0) {
            return $notifications;
        }

        foreach ($dialog['actions'] as $action) {
            $action_type = $action['type'] ?? '';
            $action_value = $action['value'] ?? '';
            $notification = null;

            switch ($action_type) {
                case 'give_item':
                    $quantity = $action['quantity'] ?? 1;
                    $result = $this->action_give_item($action_value, $quantity);
                    if ($result) {
                        // Pobierz nazwę przedmiotu
                        $item_post = get_post($action_value);
                        if ($item_post && $item_post->post_type === 'item') {
                            $notification = [
                                'message' => "Otrzymano $quantity x {$item_post->post_title}",
                                'status' => 'success'
                            ];
                        }
                    }
                    break;

                case 'remove_item':
                    $quantity = $action['quantity'] ?? 1;
                    $result = $this->action_remove_item($action_value, $quantity);
                    if ($result) {
                        // Pobierz nazwę przedmiotu
                        $item_post = get_post($action_value);
                        if ($item_post && $item_post->post_type === 'item') {
                            $notification = [
                                'message' => "Stracono $quantity x {$item_post->post_title}",
                                'status' => 'bad'
                            ];
                        }
                    }
                    break;

                case 'start_mission':
                    $this->action_start_mission($action_value);
                    break;

                case 'complete_mission':
                    $this->action_complete_mission($action_value);
                    break;

                case 'change_relation':
                    $change_value = $action['change_value'] ?? 0;
                    $target_npc_id = isset($action['npc_id']) ? (int)$action['npc_id'] : null;
                    $mark_as_known = !empty($action['mark_as_known']);
                    $this->action_change_relation($change_value, $target_npc_id, $mark_as_known);
                    break;

                case 'give_exp':
                    $exp_value = $action['exp_value'] ?? 0;
                    $this->action_give_exp($exp_value);
                    break;

                case 'unlock_area':
                    $this->action_unlock_area($action_value);
                    break;

                case 'transaction':
                    $currency = $action['backpack'] ?? '';
                    $value = (int)($action['value'] ?? 0);
                    $notification = $this->action_update_resource($currency, $value);
                    break;

                case 'skills':
                    $skill_type = $action['type_of_skills'] ?? '';
                    $value = (int)($action['value'] ?? 0);
                    $notification = $this->action_update_skill($skill_type, $value);
                    break;

                case 'item':
                    $item_action = $action['item_action'] ?? '';
                    $item_id = (int)($action['item'] ?? 0);
                    $quantity = (int)($action['quantity'] ?? 1);

                    if ($item_action === 'give') {
                        $result = $this->action_give_item($item_id, $quantity);
                        if ($result) {
                            // Pobierz nazwę przedmiotu
                            $item_post = get_post($item_id);
                            if ($item_post && $item_post->post_type === 'item') {
                                $notification = [
                                    'message' => "Otrzymano $quantity x {$item_post->post_title}",
                                    'status' => 'success'
                                ];
                            }
                        }
                    } elseif ($item_action === 'take') {
                        $result = $this->action_remove_item($item_id, $quantity);
                        if ($result) {
                            // Pobierz nazwę przedmiotu
                            $item_post = get_post($item_id);
                            if ($item_post && $item_post->post_type === 'item') {
                                $notification = [
                                    'message' => "Stracono $quantity x {$item_post->post_title}",
                                    'status' => 'bad'
                                ];
                            }
                        }
                    }
                    break;

                default:
                    $this->logger->log("Nieobsługiwany typ akcji: {$action_type}", 'warning');
                    break;
            }

            // Jeśli akcja wygenerowała powiadomienie, dodaj je do listy
            if ($notification) {
                $notifications[] = $notification;
            }
        }

        return $notifications;
    }

    /**
     * Akcja: Dodaje przedmiot do ekwipunku gracza
     *
     * @param int|string $item_id ID przedmiotu
     * @param int $quantity Ilość
     * @return bool Czy akcja zakończyła się powodzeniem
     */
    public function action_give_item($item_id, int $quantity): bool
    {
        $this->logger->log("Dodawanie przedmiotu {$item_id} w ilości {$quantity} dla użytkownika {$this->user_id}");

        // Pobierz aktualne dane ekwipunku
        $items_inventory = get_field('items', 'user_' . $this->user_id);

        if (!is_array($items_inventory)) {
            $items_inventory = [];
        }

        // Flaga określająca, czy przedmiot został znaleziony w ekwipunku
        $item_found = false;

        // Sprawdź, czy przedmiot już istnieje w ekwipunku
        foreach ($items_inventory as $key => $inventory_item) {
            if (isset($inventory_item['item']) && (int)$inventory_item['item'] === (int)$item_id) {
                $item_found = true;
                $current_quantity = (int)($inventory_item['quantity'] ?? 0);
                $items_inventory[$key]['quantity'] = $current_quantity + $quantity;
                $this->logger->log("Dodano {$quantity} x {$item_id} do ekwipunku. Nowy stan: {$items_inventory[$key]['quantity']}");
                break;
            }
        }

        // Jeśli przedmiot nie istnieje, dodaj go
        if (!$item_found) {
            $item_post = get_post($item_id);
            if (!$item_post || get_post_type($item_post) !== 'item') {
                $this->logger->log("Przedmiot {$item_id} nie istnieje", 'error');
                return false;
            }

            $items_inventory[] = [
                'item' => $item_id,
                'quantity' => $quantity
            ];

            $this->logger->log("Dodano nowy przedmiot {$item_id} (x{$quantity}) do ekwipunku");
        }

        // Aktualizuj dane ekwipunku
        $result = update_field('items', $items_inventory, 'user_' . $this->user_id);

        return $result;
    }

    /**
     * Akcja: Usuwa przedmiot z ekwipunku gracza
     *
     * @param int|string $item_id ID przedmiotu
     * @param int $quantity Ilość
     * @return bool Czy akcja zakończyła się powodzeniem
     */
    public function action_remove_item($item_id, int $quantity): bool
    {
        $this->logger->log("Usuwanie przedmiotu {$item_id} w ilości {$quantity} dla użytkownika {$this->user_id}");

        // Pobierz aktualne dane ekwipunku
        $items_inventory = get_field('items', 'user_' . $this->user_id);

        if (!is_array($items_inventory)) {
            return false;
        }

        $item_found = false;

        // Szukamy przedmiotu w ekwipunku
        foreach ($items_inventory as $key => $inventory_item) {
            if (isset($inventory_item['item']) && (int)$inventory_item['item'] === (int)$item_id) {
                $item_found = true;
                $current_quantity = (int)($inventory_item['quantity'] ?? 0);

                if ($current_quantity < $quantity) {
                    $this->logger->log("UWAGA: Próba zabrania większej ilości przedmiotów ({$quantity}) niż posiada gracz ({$current_quantity})");
                    return false;
                }

                // Nowa ilość po odjęciu
                $new_quantity = max(0, $current_quantity - $quantity);

                if ($new_quantity > 0) {
                    $items_inventory[$key]['quantity'] = $new_quantity;
                    $this->logger->log("Zabrano {$quantity} x {$item_id} z ekwipunku. Nowy stan: {$new_quantity}");
                } else {
                    // Jeśli ilość wynosi 0, usuwamy przedmiot z ekwipunku
                    unset($items_inventory[$key]);
                    $items_inventory = array_values($items_inventory); // Reindeksowanie tablicy
                    $this->logger->log("Usunięto przedmiot {$item_id} z ekwipunku (ilość = 0)");
                }

                // Aktualizuj dane ekwipunku
                $result = update_field('items', $items_inventory, 'user_' . $this->user_id);
                return $result;
            }
        }

        // Jeśli przedmiot nie został znaleziony
        if (!$item_found) {
            $this->logger->log("NIEPOWODZENIE AKCJI PRZEDMIOTU: Próba zabrania przedmiotu {$item_id}, ale użytkownik go nie posiada");
            return false;
        }

        return false;
    }

    /**
     * Akcja: Rozpoczyna misję dla gracza
     *
     * @param int|string $mission_id ID misji
     * @return bool Czy akcja zakończyła się powodzeniem
     */
    public function action_start_mission($mission_id): bool
    {
        // Sprawdź, czy misja istnieje
        $mission_post = get_post($mission_id);
        if (!$mission_post || get_post_type($mission_post) !== 'mission') {
            $this->logger->log("Misja {$mission_id} nie istnieje", 'error');
            return false;
        }

        $mission_name = $mission_post->post_title;
        $mission_field_key = 'mission_' . $mission_id;

        // Pobierz dane konkretnej misji bezpośrednio z pola użytkownika
        $mission_data = get_field($mission_field_key, 'user_' . $this->user_id);

        // Sprawdź czy misja już jest zdefiniowana dla użytkownika
        if (!is_array($mission_data)) {
            $mission_data = [
                'status' => 'in_progress',
                'assigned_date' => date('Y-m-d H:i:s'),
                'completion_date' => '',
                'tasks' => []
            ];
            $this->logger->log("Utworzono nową misję dla użytkownika: {$mission_name}");
        } else if ($mission_data['status'] === 'completed') {
            // Misja jest już ukończona
            $this->logger->log("Misja {$mission_name} (ID: {$mission_id}) już została ukończona");
            return false;
        } else if ($mission_data['status'] === 'in_progress') {
            // Misja jest już w trakcie
            $this->logger->log("Misja {$mission_name} (ID: {$mission_id}) jest już aktywna");
            return false;
        }

        // Ustaw status misji na "in_progress"
        $mission_data['status'] = 'in_progress';
        $mission_data['assigned_date'] = date('Y-m-d H:i:s');

        // Zapisz zaktualizowane dane misji
        $result = update_field($mission_field_key, $mission_data, 'user_' . $this->user_id);
        if ($result) {
            $this->logger->log("Rozpoczęto misję {$mission_name} (ID: {$mission_id}) dla użytkownika {$this->user_id}");
        } else {
            $this->logger->log("Błąd podczas rozpoczynania misji {$mission_name} (ID: {$mission_id})", 'error');
        }

        return $result;
    }

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
}
