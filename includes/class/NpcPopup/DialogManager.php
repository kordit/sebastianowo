<?php

require_once get_template_directory() . '/includes/class/NpcPopup/NpcLogger.php'; // Poprawiona ścieżka
require_once get_template_directory() . '/includes/class/NpcPopup/ContextValidator.php';

/**
 * Klasa DialogManager
 * 
 * Zarządza dialogami NPC, ich warunkami i akcjami.
 * 
 * @package Game
 * @subpackage NpcPopup
 * @since 1.0.0
 */
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

    // public function filter_answers(array $answers, array $context): array
    // {
    //     $this->logger->debug_log("DialogManager: Rozpoczynam filtrowanie odpowiedzi.", ['count_answers_before' => count($answers), 'context' => $context]);
    //     if (empty($answers)) {
    //         $this->logger->debug_log("DialogManager: Brak odpowiedzi do filtrowania.");
    //         return [];
    //     }

    //     $filtered_answers = [];
    //     foreach ($answers as $answer_key => $answer_data) {
    //         $answer_text_log = $answer_data['answer_text'] ?? (is_array($answer_key) ? json_encode($answer_key) : $answer_key);
    //         $this->logger->debug_log("DialogManager: Sprawdzanie odpowiedzi: " . $answer_text_log, ['answer_data' => $answer_data]);

    //         $conditions = $answer_data['visibility_conditions'] ?? [];
    //         if (!empty($conditions)) {
    //             $this->logger->debug_log("DialogManager: Odpowiedź (" . $answer_text_log . ") ma zdefiniowane warunki widoczności. Rozpoczynam sprawdzanie.", ['conditions' => $conditions]);
    //             try {
    //                 if (!$this->getLocationConditionChecker()->check_conditions($conditions, $context)) {
    //                     $this->logger->debug_log("DialogManager: Warunki dla odpowiedzi (" . $answer_text_log . ") NIESPEŁNIONE. Usuwanie odpowiedzi.", ['conditions' => $conditions, 'context' => $context]);
    //                     continue;
    //                 }
    //                 $this->logger->debug_log("DialogManager: Warunki dla odpowiedzi (" . $answer_text_log . ") SPEŁNIONE.", ['conditions' => $conditions, 'context' => $context]);
    //             } catch (\Exception $e) {
    //                 $this->logger->log("DialogManager: Błąd podczas sprawdzania warunków dla odpowiedzi (" . $answer_text_log . ") - " . $e->getMessage(), 'error'); // Poprawione wywołanie loggera
    //                 continue;
    //             }
    //         } else {
    //             $this->logger->debug_log("DialogManager: Odpowiedź (" . $answer_text_log . ") nie ma zdefiniowanych warunków widoczności.");
    //         }
    //         $filtered_answers[$answer_key] = $answer_data;
    //     }

    //     $this->logger->debug_log("DialogManager: Zakończono filtrowanie odpowiedzi.", ['count_answers_after' => count($filtered_answers)]);
    //     return $filtered_answers;
    // }

    /**
     * Filtrowanie odpowiedzi z wykorzystaniem UserContext i validate_dialog_condition
     * @param array $answers
     * @param object $userContext
     * @param array $location_info
     * @return array
     */
    public function filter_answers_with_user_context(array $answers, $userContext, array $location_info): array
    {
        $this->logger->debug_log("DialogManager: Rozpoczynam filtrowanie odpowiedzi (UserContext).", [
            'count_answers_before' => count($answers),
            'location_info' => $location_info,
            'user_context' => [
                'missions' => $userContext->get_missions(),
                'relations' => $userContext->get_relations(),
                'tasks' => $userContext->get_tasks(),
                'items' => $userContext->get_item_counts(),
                'location' => $userContext->get_location(),
            ]
        ]);
        if (empty($answers)) {
            $this->logger->debug_log("DialogManager: Brak odpowiedzi do filtrowania.");
            return [];
        }

        $filtered_answers = [];
        foreach ($answers as $answer_key => $answer_data) {
            $answer_text_log = $answer_data['answer_text'] ?? (is_array($answer_key) ? json_encode($answer_key) : $answer_key);
            $this->logger->debug_log("DialogManager: Sprawdzanie odpowiedzi: " . $answer_text_log, [
                'answer_data_keys' => array_keys($answer_data),
                'answer_data_id' => $answer_data['answer_id'] ?? null,
                'answer_data_text' => $answer_data['answer_text'] ?? null
            ]);

            $conditions = $answer_data['visibility_conditions'] ?? [];
            $all_conditions_pass = true;
            $failed_reason = [];
            $validator = new ContextValidator($userContext);
            foreach ($conditions as $condition) {
                $context_for_condition = $validator->validateCondition($condition, $location_info);
                $this->logger->debug_log("DialogManager: Warunek do sprawdzenia", [
                    'answer_text' => $answer_text_log,
                    'condition' => $condition,
                    'context_for_condition' => $context_for_condition
                ]);
                $result = $this->validate_dialog_condition($condition, $context_for_condition);
                if (!$result) {
                    $all_conditions_pass = false;
                    $failed_reason[] = [
                        'condition' => $condition,
                        'context' => $context_for_condition,
                        'reason' => 'Warunek nie został spełniony'
                    ];
                    $this->logger->debug_log("DialogManager: Warunek NIE SPEŁNIONY", [
                        'answer_text' => $answer_text_log,
                        'condition' => $condition,
                        'context_for_condition' => $context_for_condition
                    ]);
                    break;
                } else {
                    $this->logger->debug_log("DialogManager: Warunek SPEŁNIONY", [
                        'answer_text' => $answer_text_log,
                        'condition' => $condition,
                        'context_for_condition' => $context_for_condition
                    ]);
                }
            }
            if ($all_conditions_pass) {
                $filtered_answers[$answer_key] = $answer_data;
                $this->logger->debug_log("DialogManager: Warunki dla odpowiedzi (" . $answer_text_log . ") SPEŁNIONE.", [
                    'answer_id' => $answer_data['answer_id'] ?? null,
                    'answer_text' => $answer_text_log
                ]);
            } else {
                $this->logger->debug_log("DialogManager: Warunki dla odpowiedzi (" . $answer_text_log . ") NIESPEŁNIONE. Usuwanie odpowiedzi.", [
                    'answer_id' => $answer_data['answer_id'] ?? null,
                    'answer_text' => $answer_text_log,
                    'reasons' => $failed_reason
                ]);
            }
        }
        $this->logger->debug_log("DialogManager: Zakończono filtrowanie odpowiedzi (UserContext).", ['count_answers_after' => count($filtered_answers)]);
        return $filtered_answers;
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
     * Przetwarza akcje powiązane z dialogiem
     *
     * @param array $dialog Dialog, którego akcje mają być przetworzone
     * @return void
     */
    public function process_dialog_actions(array $dialog): void
    {
        if (empty($dialog['actions']) || !is_array($dialog['actions']) || $this->user_id === 0) {
            return;
        }

        foreach ($dialog['actions'] as $action) {
            $action_type = $action['type'] ?? '';
            $action_value = $action['value'] ?? '';

            switch ($action_type) {
                case 'give_item':
                    $quantity = $action['quantity'] ?? 1;
                    $this->action_give_item($action_value, $quantity);
                    break;

                case 'remove_item':
                    $quantity = $action['quantity'] ?? 1;
                    $this->action_remove_item($action_value, $quantity);
                    break;

                case 'start_mission':
                    $this->action_start_mission($action_value);
                    break;

                case 'complete_mission':
                    $this->action_complete_mission($action_value);
                    break;

                case 'change_relation':
                    $change_value = $action['change_value'] ?? 0;
                    $this->action_change_relation($change_value);
                    break;

                case 'give_exp':
                    $exp_value = $action['exp_value'] ?? 0;
                    $this->action_give_exp($exp_value);
                    break;

                case 'unlock_area':
                    $this->action_unlock_area($action_value);
                    break;

                default:
                    $this->logger->log("Nieobsługiwany typ akcji: {$action_type}", 'warning');
                    break;
            }
        }
    }

    /**
     * Akcja: Dodaje przedmiot do ekwipunku gracza
     *
     * @param int|string $item_id ID przedmiotu
     * @param int $quantity Ilość
     * @return bool Czy akcja zakończyła się powodzeniem
     */
    private function action_give_item($item_id, int $quantity): bool
    {
        $this->logger->log("Dodawanie przedmiotu {$item_id} w ilości {$quantity} dla użytkownika {$this->user_id}");

        // Pobierz aktualne dane ekwipunku
        $inventory = get_user_meta($this->user_id, 'inventory', true);

        if (!is_array($inventory)) {
            $inventory = [];
        }

        // Sprawdź, czy przedmiot już istnieje w ekwipunku
        $item_exists = false;
        foreach ($inventory as &$item) {
            if ($item['id'] == $item_id) {
                $item['quantity'] += $quantity;
                $item_exists = true;
                break;
            }
        }

        // Jeśli przedmiot nie istnieje, dodaj go
        if (!$item_exists) {
            $item_post = get_post($item_id);
            if (!$item_post || get_post_type($item_post) !== 'items') {
                $this->logger->log("Przedmiot {$item_id} nie istnieje", 'error');
                return false;
            }

            $inventory[] = [
                'id' => $item_id,
                'name' => $item_post->post_title,
                'quantity' => $quantity,
                'date_added' => current_time('mysql')
            ];
        }

        // Aktualizuj dane ekwipunku
        update_user_meta($this->user_id, 'inventory', $inventory);

        return true;
    }

    /**
     * Akcja: Usuwa przedmiot z ekwipunku gracza
     *
     * @param int|string $item_id ID przedmiotu
     * @param int $quantity Ilość
     * @return bool Czy akcja zakończyła się powodzeniem
     */
    private function action_remove_item($item_id, int $quantity): bool
    {
        $this->logger->log("Usuwanie przedmiotu {$item_id} w ilości {$quantity} dla użytkownika {$this->user_id}");

        // Pobierz aktualne dane ekwipunku
        $inventory = get_user_meta($this->user_id, 'inventory', true);

        if (!is_array($inventory)) {
            return false;
        }

        foreach ($inventory as $key => &$item) {
            if ($item['id'] == $item_id) {
                if ($item['quantity'] <= $quantity) {
                    // Usuń cały przedmiot
                    unset($inventory[$key]);
                } else {
                    // Zmniejsz ilość
                    $item['quantity'] -= $quantity;
                }

                // Aktualizuj dane ekwipunku
                update_user_meta($this->user_id, 'inventory', array_values($inventory));
                return true;
            }
        }

        return false;
    }

    /**
     * Akcja: Rozpoczyna misję dla gracza
     *
     * @param int|string $mission_id ID misji
     * @return bool Czy akcja zakończyła się powodzeniem
     */
    private function action_start_mission($mission_id): bool
    {
        $this->logger->log("Rozpoczynanie misji {$mission_id} dla użytkownika {$this->user_id}");

        // Sprawdź, czy misja istnieje
        $mission = get_post($mission_id);
        if (!$mission || get_post_type($mission) !== 'mission') {
            $this->logger->log("Misja {$mission_id} nie istnieje", 'error');
            return false;
        }

        // Pobierz aktualne dane o misjach
        $active_missions = get_user_meta($this->user_id, 'active_missions', true);
        $completed_missions = get_user_meta($this->user_id, 'completed_missions', true);

        if (!is_array($active_missions)) {
            $active_missions = [];
        }

        if (!is_array($completed_missions)) {
            $completed_missions = [];
        }

        // Sprawdź, czy misja już jest aktywna lub ukończona
        if (in_array($mission_id, $active_missions) || in_array($mission_id, $completed_missions)) {
            return false;
        }

        // Dodaj misję do aktywnych
        $active_missions[] = $mission_id;

        // Aktualizuj dane o misjach
        update_user_meta($this->user_id, 'active_missions', $active_missions);

        return true;
    }

    /**
     * Akcja: Kończy misję dla gracza
     *
     * @param int|string $mission_id ID misji
     * @return bool Czy akcja zakończyła się powodzeniem
     */
    private function action_complete_mission($mission_id): bool
    {
        $this->logger->log("Kończenie misji {$mission_id} dla użytkownika {$this->user_id}");

        // Pobierz aktualne dane o misjach
        $active_missions = get_user_meta($this->user_id, 'active_missions', true);
        $completed_missions = get_user_meta($this->user_id, 'completed_missions', true);

        if (!is_array($active_missions)) {
            $active_missions = [];
        }

        if (!is_array($completed_missions)) {
            $completed_missions = [];
        }

        // Sprawdź, czy misja jest aktywna
        $key = array_search($mission_id, $active_missions);
        if ($key === false) {
            return false;
        }

        // Usuń misję z aktywnych
        unset($active_missions[$key]);

        // Dodaj misję do ukończonych (jeśli jeszcze nie została dodana)
        if (!in_array($mission_id, $completed_missions)) {
            $completed_missions[] = $mission_id;
        }

        // Aktualizuj dane o misjach
        update_user_meta($this->user_id, 'active_missions', array_values($active_missions));
        update_user_meta($this->user_id, 'completed_missions', $completed_missions);

        return true;
    }

    /**
     * Akcja: Zmienia poziom relacji z NPC
     *
     * @param int $change_value Wartość zmiany relacji
     * @return bool Czy akcja zakończyła się powodzeniem
     */
    private function action_change_relation(int $change_value): bool
    {
        $this->logger->log("Zmiana relacji z NPC {$this->npc_id} o {$change_value} dla użytkownika {$this->user_id}");

        // Pobierz aktualne dane o relacjach
        $relations = get_user_meta($this->user_id, 'npc_relations', true);

        if (!is_array($relations)) {
            $relations = [];
        }

        // Zaktualizuj lub dodaj relację
        if (isset($relations[$this->npc_id])) {
            $relations[$this->npc_id] += $change_value;
        } else {
            $relations[$this->npc_id] = $change_value;
        }

        // Aktualizuj dane o relacjach
        update_user_meta($this->user_id, 'npc_relations', $relations);

        return true;
    }

    /**
     * Akcja: Daje graczowi doświadczenie
     *
     * @param int $exp_value Wartość doświadczenia
     * @return bool Czy akcja zakończyła się powodzeniem
     */
    private function action_give_exp(int $exp_value): bool
    {
        $this->logger->log("Dodawanie {$exp_value} doświadczenia dla użytkownika {$this->user_id}");

        // Pobierz aktualne doświadczenie
        $current_exp = (int) get_user_meta($this->user_id, 'player_exp', true);
        $current_level = (int) get_user_meta($this->user_id, 'player_level', true);

        if (!$current_level) {
            $current_level = 1;
        }

        // Dodaj doświadczenie
        $new_exp = $current_exp + $exp_value;

        // Sprawdź, czy gracz powinien awansować
        $new_level = $this->calculate_level($new_exp);
        if ($new_level > $current_level) {
            update_user_meta($this->user_id, 'player_level', $new_level);
            $this->logger->log("Użytkownik {$this->user_id} awansował na poziom {$new_level}");
        }

        // Aktualizuj doświadczenie
        update_user_meta($this->user_id, 'player_exp', $new_exp);

        return true;
    }

    /**
     * Oblicza poziom gracza na podstawie doświadczenia
     *
     * @param int $exp Doświadczenie gracza
     * @return int Poziom gracza
     */
    private function calculate_level(int $exp): int
    {
        // Prosta formuła poziomu: poziom = pierwiastek kwadratowy z (exp / 100)
        return max(1, (int) sqrt($exp / 100));
    }

    /**
     * Akcja: Odblokowuje nowy obszar dla gracza
     *
     * @param int|string $area_id ID obszaru
     * @return bool Czy akcja zakończyła się powodzeniem
     */
    private function action_unlock_area($area_id): bool
    {
        $this->logger->log("Odblokowywanie obszaru {$area_id} dla użytkownika {$this->user_id}");

        // Sprawdź, czy obszar istnieje
        $area = get_post($area_id);
        if (!$area || get_post_type($area) !== 'tereny') {
            $this->logger->log("Obszar {$area_id} nie istnieje", 'error');
            return false;
        }

        // Pobierz aktualne dane o dostępnych obszarach
        $unlocked_areas = get_user_meta($this->user_id, 'unlocked_areas', true);

        if (!is_array($unlocked_areas)) {
            $unlocked_areas = [];
        }

        // Sprawdź, czy obszar już jest odblokowany
        if (in_array($area_id, $unlocked_areas)) {
            return true;
        }

        // Dodaj obszar do odblokowanych
        $unlocked_areas[] = $area_id;

        // Aktualizuj dane o dostępnych obszarach
        update_user_meta($this->user_id, 'unlocked_areas', $unlocked_areas);

        return true;
    }

    /**
     * Pobiera LocationConditionChecker
     *
     * @return LocationConditionChecker
     * @throws \Exception
     */
    private function getLocationConditionChecker(): LocationConditionChecker
    {
        if ($this->locationConditionChecker === null) {
            if (!isset($this->user_id) || $this->user_id === 0) {
                $error_message = "DialogManager: user_id nie jest ustawione przed próbą utworzenia LocationConditionChecker.";
                $this->logger->log_error($error_message);
                throw new \Exception($error_message);
            }
            $this->locationConditionChecker = new LocationConditionChecker($this->user_id, $this->logger);
        }
        return $this->locationConditionChecker;
    }

    /**
     * Waliduje pojedynczy warunek dialogu na podstawie kontekstu.
     *
     * @param array $condition Warunek do sprawdzenia (z ACF)
     * @param array $context Kontekst użytkownika (np. ['missions'=>[], 'relations'=>[], ...])
     * @return bool
     */
    public function validate_dialog_condition(array $condition, array $context): bool
    {
        $type = $condition['acf_fc_layout'] ?? '';
        switch ($type) {
            case 'condition_mission': {
                    $mission_id = (int)($condition['mission_id'] ?? 0);
                    $operator = $condition['condition'] ?? 'is';
                    $status = $condition['status'] ?? null;
                    $missions = $context['mission'] ?? [];
                    $found = false;
                    foreach ($missions as $mission) {
                        if ((int)$mission['id'] === $mission_id) {
                            $found = true;
                            if ($operator === 'is') {
                                return $mission['status'] === $status;
                            } elseif ($operator === 'is_not') {
                                return $mission['status'] !== $status;
                            }
                        }
                    }
                    // Jeśli warunek is_not i misja nie istnieje, to spełniony
                    if ($operator === 'is_not' && !$found) return true;
                    return false;
                }
            case 'condition_npc_relation': {
                    $npc_id = (int)($condition['npc_id'] ?? 0);
                    $operator = $condition['condition'] ?? 'is_known';
                    $value = isset($condition['relation_value']) ? (int)$condition['relation_value'] : null;
                    $relations = $context['relations'] ?? [];
                    foreach ($relations as $rel) {
                        if ((int)$rel['npc_id'] === $npc_id) {
                            switch ($operator) {
                                case 'is_known':
                                    return (bool)$rel['meet'];
                                case 'is_not_known':
                                    return !(bool)$rel['meet'];
                                case 'relation_above':
                                    return $rel['level'] > $value;
                                case 'relation_below':
                                    return $rel['level'] < $value;
                                case 'relation_equal':
                                    return $rel['level'] == $value;
                            }
                        }
                    }
                    // Jeśli warunek is_not_known i nie ma relacji, to spełniony
                    if ($operator === 'is_not_known') return true;
                    return false;
                }
            case 'condition_task': {
                    $mission_id = (int)($condition['mission_id'] ?? 0);
                    $task_id = $condition['task_id'] ?? '';
                    $operator = $condition['condition'] ?? 'is';
                    $status = $condition['status'] ?? null; // <-- poprawka: było 'task_status', powinno być 'status'
                    $tasks = $context['task'] ?? [];
                    if (isset($tasks[$mission_id][$task_id])) {
                        $task = $tasks[$mission_id][$task_id];
                        $task_status = is_array($task) ? ($task['status'] ?? null) : $task;
                        if ($operator === 'is') {
                            return $task_status === $status;
                        } elseif ($operator === 'is_not') {
                            return $task_status !== $status;
                        }
                    } else {
                        // Jeśli warunek is_not i zadanie nie istnieje, to spełniony
                        if ($operator === 'is_not') return true;
                    }
                    return false;
                }
            case 'condition_location': {
                    $operator = $condition['condition'] ?? 'is';
                    $location_text = $condition['location_text'] ?? '';
                    $current_location = $context['current_location_text'] ?? '';
                    if ($operator === 'is') {
                        return $current_location === $location_text;
                    } elseif ($operator === 'is_not') {
                        return $current_location !== $location_text;
                    }
                    return false;
                }
            case 'condition_inventory': {
                    $item_id = (int)($condition['item_id'] ?? 0);
                    $operator = $condition['condition'] ?? 'has_item';
                    $quantity = isset($condition['quantity']) ? (int)$condition['quantity'] : 1;
                    $items = $context['items'] ?? [];
                    $found = false;
                    $item_quantity = 0;
                    foreach ($items as $item) {
                        if ((int)$item['id'] === $item_id) {
                            $found = true;
                            $item_quantity = (int)$item['quantity'];
                            break;
                        }
                    }
                    switch ($operator) {
                        case 'has_item':
                            return $found && $item_quantity > 0;
                        case 'has_not_item':
                            return !$found || $item_quantity <= 0;
                        case 'quantity_above':
                            return $found && $item_quantity > $quantity;
                        case 'quantity_below':
                            return !$found || $item_quantity < $quantity;
                        case 'quantity_equal':
                            return $found && $item_quantity == $quantity;
                    }
                    return false;
                }
            default:
                $this->logger->debug_log('Nieznany typ warunku', $condition);
                return false;
        }
    }

    /**
     * Zwraca pierwszy dialog, który spełnia WSZYSTKIE warunki visibility_settings
     *
     * @param array $dialogs Tablica wszystkich dialogów
     * @param object $userContext Obiekt UserContext
     * @param array $location_info Informacje o lokalizacji
     * @return array|null Pełny dialog lub null
     */
    public function get_first_matching_dialog(array $dialogs, $userContext, array $location_info): ?array
    {
        foreach ($dialogs as $dialog) {
            $this->logger->debug_log('Sprawdzanie dialogu', [
                'dialog_id' => $dialog['dialog_id'] ?? 'brak id',
                'answers_count' => is_array($dialog['anwsers'] ?? []) ? count($dialog['anwsers']) : 0
            ]);

            $layout_settings = $dialog['layout_settings'] ?? [];
            $visibility_settings = $layout_settings['visibility_settings'] ?? [];
            $all_conditions_pass = true;
            $failed_reason = [];
            $validator = new ContextValidator($userContext);
            foreach ($visibility_settings as $condition) {
                $context_for_condition = $validator->validateCondition($condition, $location_info);
                $result = $this->validate_dialog_condition($condition, $context_for_condition);
                if (!$result) {
                    $all_conditions_pass = false;
                    $failed_reason[] = [
                        'dialog_id' => $dialog['dialog_id'] ?? null,
                        'condition' => $condition,
                        'context' => $context_for_condition,
                        'reason' => 'Warunek nie został spełniony'
                    ];
                    break;
                }
            }
            if ($all_conditions_pass) {
                // Filtrowanie odpowiedzi dialogu
                if (isset($dialog['anwsers']) && is_array($dialog['anwsers'])) {
                    $this->logger->debug_log('Filtrowanie anwsers dla dialogu', [
                        'dialog_id' => $dialog['dialog_id'] ?? 'brak id',
                        'anwsers_count_before' => count($dialog['anwsers'])
                    ]);

                    $filtered_anwsers = [];
                    foreach ($dialog['anwsers'] as $key => $answer) {
                        $answer_settings = $answer['layout_settings'] ?? [];
                        $answer_visibility = $answer_settings['visibility_settings'] ?? [];

                        // Jeśli nie ma warunków widoczności, zachowaj odpowiedź
                        if (empty($answer_visibility)) {
                            $filtered_anwsers[$key] = $answer;
                            continue;
                        }

                        $answer_passes = true;
                        $validator = new ContextValidator($userContext);
                        foreach ($answer_visibility as $answer_condition) {
                            $this->logger->debug_log('Warunek dla odpowiedzi', [
                                'answer_text' => $answer['anwser_text'] ?? 'brak tekstu',
                                'condition' => $answer_condition
                            ]);

                            $context_for_condition = $validator->validateCondition($answer_condition, $location_info);
                            $result = $this->validate_dialog_condition($answer_condition, $context_for_condition);

                            if (!$result) {
                                $answer_passes = false;
                                $this->logger->debug_log('Odpowiedź nie spełnia warunku', [
                                    'answer_text' => $answer['anwser_text'] ?? 'brak tekstu',
                                    'condition' => $answer_condition,
                                    'result' => $result
                                ]);
                                break;
                            }
                        }

                        if ($answer_passes) {
                            $filtered_anwsers[$key] = $answer;
                        }
                    }

                    $dialog['anwsers'] = $filtered_anwsers;

                    $this->logger->debug_log('Po filtrowaniu anwsers', [
                        'dialog_id' => $dialog['dialog_id'] ?? 'brak id',
                        'anwsers_count_after' => count($filtered_anwsers),
                        'anwsers_texts' => array_map(function ($a) {
                            return $a['anwser_text'] ?? 'brak tekstu';
                        }, $filtered_anwsers)
                    ]);
                }

                // Filtrowanie answers (jeśli istnieje)
                if (isset($dialog['answers']) && is_array($dialog['answers'])) {
                    $filtered_answers = [];
                    foreach ($dialog['answers'] as $key => $answer) {
                        $answer_settings = $answer['layout_settings'] ?? [];
                        $answer_visibility = $answer_settings['visibility_settings'] ?? [];

                        if (empty($answer_visibility)) {
                            $filtered_answers[$key] = $answer;
                            continue;
                        }

                        $answer_passes = true;
                        $validator = new ContextValidator($userContext);
                        foreach ($answer_visibility as $answer_condition) {
                            $context_for_condition = $validator->validateCondition($answer_condition, $location_info);
                            $result = $this->validate_dialog_condition($answer_condition, $context_for_condition);

                            if (!$result) {
                                $answer_passes = false;
                                break;
                            }
                        }

                        if ($answer_passes) {
                            $filtered_answers[$key] = $answer;
                        }
                    }

                    $dialog['answers'] = $filtered_answers;
                }

                return $dialog;
            }
        }
        return null;
    }
}
