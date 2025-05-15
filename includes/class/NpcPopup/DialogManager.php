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

    /**
     * Pobiera pierwszy pasujący dialog z listy dialogów
     *
     * @param array $dialogs Lista wszystkich dialogów NPC
     * @param array $criteria Kryteria do sprawdzenia (type_page, location, user_id, npc_id)
     * @return array|null Dialog lub null, jeśli nie znaleziono
     */
    public function get_first_matching_dialog(array $dialogs, array $criteria): ?array
    {
        if (isset($criteria['user_id'])) {
            $this->setUserId((int)$criteria['user_id']);
        }

        if (isset($criteria['npc_id'])) {
            $this->setNpcId((int)$criteria['npc_id']);
        }

        $this->logger->log("Szukanie pasującego dialogu dla NPC ID: {$this->npc_id}, User ID: {$this->user_id}", 'info');

        if (empty($dialogs)) {
            $this->logger->log("Brak dialogów do przefiltrowania", 'warning');
            return null;
        }

        foreach ($dialogs as $dialog) {
            // Zwróć pierwszy dialog z listy - bez sprawdzania żadnych warunków
            return $dialog;
        }

        $this->logger->log("Nie znaleziono pasującego dialogu", 'warning');
        return null;
    }


    /**
     * Filtruje odpowiedzi w dialogu według warunków
     * 
     * @param array $dialog Dialog do przefiltrowania
     * @param array $criteria Kryteria filtrowania
     * @return array Przefiltrowany dialog
     */
    public function filter_answers(array $dialog, array $criteria): array
    {
        // if (!isset($dialog['answers']) || !is_array($dialog['answers'])) {
        //     return $dialog;
        // }

        // $filtered_answers = [];

        // foreach ($dialog['answers'] as $answer) {
        //     // Tworzenie sprawdzacza warunków dla danego kontekstu
        //     $condition_checker = $this->checkerFactory->create($this->user_id, $this->npc_id, $criteria);

        //     // Sprawdzenie warunków odpowiedzi
        //     $conditions = $answer['conditions'] ?? [];
        //     if (!$condition_checker->check_conditions($conditions)) {
        //         continue;
        //     }

        //     $filtered_answers[] = $answer;
        // }

        // $dialog['answers'] = $filtered_answers;
        return $dialog;
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
}
