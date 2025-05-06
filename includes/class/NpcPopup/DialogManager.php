<?php

/**
 * Klasa DialogManager
 *
 * Klasa odpowiedzialna za zarządzanie dialogami NPC i ich filtrowaniem.
 *
 * @package Game
 * @since 1.0.0
 */

class DialogManager
{
    /**
     * Logger do zapisywania informacji debugowania
     * 
     * @var NpcLogger
     */
    private NpcLogger $logger;

    /**
     * Fabryka sprawdzaczy warunków
     * 
     * @var ConditionCheckerFactory
     */
    private ConditionCheckerFactory $checkerFactory;

    /**
     * Konstruktor klasy
     * 
     * @param NpcLogger $logger Logger do zapisywania informacji debugowania
     * @param ConditionCheckerFactory $checkerFactory Fabryka sprawdzaczy warunków
     */
    public function __construct(NpcLogger $logger, ConditionCheckerFactory $checkerFactory)
    {
        $this->logger = $logger;
        $this->checkerFactory = $checkerFactory;
    }

    /**
     * Zwraca pierwszy dialog, który spełnia podane kryteria
     *
     * @param array $dialogs Wszystkie dialogi NPC
     * @param array $criteria Kryteria filtrowania (lokalizacja, typ strony)
     * @return array|null Pierwszy pasujący dialog lub null jeśli nie znaleziono
     */
    public function get_first_matching_dialog(array $dialogs, array $criteria): ?array
    {
        if (empty($dialogs)) {
            $this->logger->debug_log("Brak dialogów do sprawdzenia");
            return null;
        }

        $default_dialog = null;
        $this->logger->debug_log("Sprawdzanie pasujących dialogów, liczba dialogów: " . count($dialogs));

        foreach ($dialogs as $index => $dialog) {
            $dialog_id = isset($dialog['id_pola']) ? $dialog['id_pola'] : "Dialog #{$index}";
            $this->logger->debug_log("Sprawdzanie dialogu: {$dialog_id}");

            // Zapisz dialog domyślny (zostanie użyty, jeśli żaden inny nie pasuje)
            if (isset($dialog['id_pola']) && $dialog['id_pola'] === 'domyslny') {
                $default_dialog = $dialog;
                $this->logger->debug_log("Znaleziono dialog domyślny: {$dialog_id}");
            }

            // Sprawdź warunki widoczności
            $matches = $this->dialog_matches_criteria($dialog, $criteria);
            $this->logger->debug_log("Dialog {$dialog_id} pasuje do kryteriów: " . ($matches ? 'TAK' : 'NIE'));

            if ($matches) {
                $this->logger->debug_log("Zwracam pasujący dialog: {$dialog_id}");
                return $dialog;
            }
        }

        // Jeśli nie znaleziono pasującego dialogu, użyj domyślnego
        $this->logger->debug_log("Brak pasujących dialogów, zwracam domyślny: " . ($default_dialog ? ($default_dialog['id_pola'] ?? 'Unknown') : 'Brak'));
        return $default_dialog;
    }

    /**
     * Sprawdza czy dialog spełnia podane kryteria
     *
     * @param array $dialog Dialog do sprawdzenia
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy dialog pasuje do kryteriów
     */
    public function dialog_matches_criteria(array $dialog, array $criteria): bool
    {
        $dialog_id = $dialog['id_pola'] ?? 'unknown';

        // Jeśli dialog nie ma ustawień widoczności, zawsze go pokaż
        if (
            !isset($dialog['layout_settings']) ||
            !isset($dialog['layout_settings']['visibility_settings']) ||
            empty($dialog['layout_settings']['visibility_settings'])
        ) {
            $this->logger->debug_log("Dialog {$dialog_id} nie ma ustawień widoczności - pokazuję domyślnie");
            return true;
        }

        $visibility_settings = $dialog['layout_settings']['visibility_settings'];
        $logic_operator = isset($dialog['layout_settings']['logic_operator']) ?
            $dialog['layout_settings']['logic_operator'] : 'and';

        $this->logger->debug_log("Dialog {$dialog_id}, operator logiczny: {$logic_operator}");
        $this->logger->debug_log("Ustawienia widoczności dialogu {$dialog_id}:", $visibility_settings);

        $matches = [];

        // Sprawdź każdy warunek widoczności
        foreach ($visibility_settings as $index => $condition) {
            $condition_type = isset($condition['acf_fc_layout']) ? $condition['acf_fc_layout'] : "Warunek #{$index}";
            $this->logger->debug_log("Sprawdzanie warunku {$condition_type} dla dialogu {$dialog_id}");

            $result = $this->check_single_condition($condition, $criteria);
            $matches[] = $result;

            $this->logger->debug_log("Wynik warunku {$condition_type}: " . ($result ? 'PRAWDA' : 'FAŁSZ'));
        }

        // Zastosuj operator logiczny do wszystkich warunków
        if ($logic_operator === 'and') {
            $result = !in_array(false, $matches, true);
        } else { // 'or'
            $result = in_array(true, $matches, true);
        }

        $this->logger->debug_log("Dialog {$dialog_id} końcowy wynik: " . ($result ? 'PASUJE' : 'NIE PASUJE'));
        return $result;
    }

    /**
     * Filtruje odpowiedzi w dialogu na podstawie podanych kryteriów
     *
     * @param array $dialog Dialog z odpowiedziami do filtrowania
     * @param array $criteria Kryteria filtrowania
     * @return array Dialog z przefiltrowanymi odpowiedziami
     */
    public function filter_answers(array $dialog, array $criteria): array
    {
        if (!isset($dialog['anwsers']) || empty($dialog['anwsers'])) {
            return $dialog;
        }

        $filtered_answers = [];
        $default_answer = null;

        foreach ($dialog['anwsers'] as $answer) {
            // Jeśli odpowiedź ma identyfikator 'domyślna', zapisz ją jako domyślną
            if (
                isset($answer['anwser_text']) &&
                (strtolower($answer['anwser_text']) === 'domyślna' ||
                    strtolower($answer['anwser_text']) === 'domyslna')
            ) {
                $default_answer = $answer;
            }

            // Sprawdź czy odpowiedź spełnia kryteria widoczności
            if ($this->answer_matches_criteria($answer, $criteria)) {
                $filtered_answers[] = $answer;
            }
        }

        // Jeśli nie ma odpowiedzi spełniających kryteria, dodaj domyślną (jeśli istnieje)
        if (empty($filtered_answers) && $default_answer !== null) {
            $filtered_answers[] = $default_answer;
        }

        // Jeśli nadal nie ma odpowiedzi, dodaj wszystkie oryginalne
        if (empty($filtered_answers)) {
            $filtered_answers = $dialog['anwsers'];
        }

        $dialog['anwsers'] = $filtered_answers;
        return $dialog;
    }

    /**
     * Sprawdza czy odpowiedź spełnia podane kryteria
     *
     * @param array $answer Odpowiedź do sprawdzenia
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy odpowiedź pasuje do kryteriów
     */
    public function answer_matches_criteria(array $answer, array $criteria): bool
    {
        $location = $criteria['location'] ?? '';

        // Jeśli odpowiedź nie ma ustawień widoczności, zawsze ją pokaż
        if (
            !isset($answer['layout_settings']) ||
            !isset($answer['layout_settings']['visibility_settings']) ||
            empty($answer['layout_settings']['visibility_settings'])
        ) {
            return true;
        }

        $visibility_settings = $answer['layout_settings']['visibility_settings'];
        $logic_operator = isset($answer['layout_settings']['logic_operator']) ?
            $answer['layout_settings']['logic_operator'] : 'and';

        $matches = [];

        // Sprawdź każdy warunek widoczności
        foreach ($visibility_settings as $condition) {
            $result = $this->check_single_condition($condition, $criteria);
            $matches[] = $result;
        }

        // Zastosuj operator logiczny do wszystkich warunków
        if ($logic_operator === 'and') {
            return !in_array(false, $matches, true);
        } else { // 'or'
            return in_array(true, $matches, true);
        }
    }

    /**
     * Sprawdza pojedynczy warunek widoczności
     *
     * @param array $condition Warunek do sprawdzenia
     * @param array $criteria Kryteria do sprawdzenia
     * @return bool Czy warunek jest spełniony
     */
    public function check_single_condition(array $condition, array $criteria): bool
    {
        $layout = isset($condition['acf_fc_layout']) ? $condition['acf_fc_layout'] : '';

        $this->logger->debug_log("Sprawdzanie warunku typu: {$layout}");
        $this->logger->debug_log("Dane warunku:", $condition);

        // Pobierz odpowiedni sprawdzacz warunku
        $checker = $this->checkerFactory->get_checker($layout);

        if ($checker) {
            $result = $checker->check_condition($condition, $criteria);
            $this->logger->debug_log("Wynik warunku {$layout}: " . ($result ? 'SPEŁNIONY' : 'NIESPEŁNIONY'));
            return $result;
        }

        // Dla nieznanych warunków, uznaj że są spełnione
        $this->logger->debug_log("Nieznany warunek typu {$layout} - przyjmuję jako spełniony");
        return true;
    }

    /**
     * Upraszcza strukturę dialogu, zostawiając tylko potrzebne pola
     * 
     * @param array $dialog Pełna struktura dialogu
     * @return array Uproszczona struktura dialogu
     */
    public function simplify_dialog(array $dialog): array
    {
        // Prawidłowe przetwarzanie tekstu dialogu - usunięcie niepotrzebnych znaków specjalnych
        $question = isset($dialog['question']) ? $dialog['question'] : '';
        
        // Logowanie dla celów debugowania
        $this->logger->debug_log("Oryginalny tekst dialogu przed przetworzeniem: " . substr($question, 0, 100) . (strlen($question) > 100 ? '...' : ''));
        
        // Upewnij się, że znaki nowej linii są zachowane poprawnie
        $question = str_replace("\r\n", "\n", $question);
        
        $simplified = [
            'acf_fc_layout' => $dialog['acf_fc_layout'] ?? '',
            'question' => $question,
            'id_pola' => $dialog['id_pola'] ?? ''
        ];

        // Dodaj odpowiedzi, jeśli istnieją
        if (isset($dialog['anwsers']) && is_array($dialog['anwsers'])) {
            $simplified['anwsers'] = array_map(function ($answer) {
                $answerText = isset($answer['anwser_text']) ? $answer['anwser_text'] : '';
                // Również normalizuj znaki nowej linii w odpowiedziach
                $answerText = str_replace("\r\n", "\n", $answerText);
                
                return [
                    'acf_fc_layout' => $answer['acf_fc_layout'] ?? '',
                    'anwser_text' => $answerText,
                    'type_anwser' => $answer['type_anwser'] ?? false,
                    'go_to_id' => $answer['go_to_id'] ?? '0'
                ];
            }, $dialog['anwsers']);
        } else {
            $simplified['anwsers'] = [];
        }
        
        // Logowanie dla celów debugowania
        $this->logger->debug_log("Tekst dialogu po przetworzeniu: " . substr($simplified['question'], 0, 100) . (strlen($simplified['question']) > 100 ? '...' : ''));

        return $simplified;
    }
}
