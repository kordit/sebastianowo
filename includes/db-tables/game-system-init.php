<?php

/**
 * Autoloader i inicjalizator systemu zarządzania grą
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Ścieżka do katalogu db-tables
define('GAME_DB_TABLES_PATH', dirname(__FILE__));

/**
 * Automatyczne ładowanie klas gry
 */
function game_autoload($className)
{
    $gameClasses = [
        'GameDatabaseManager' => 'GameDatabaseManager.php',
        'GameDeltaManager' => 'GameDeltaManager.php',
        'GameUserRepository' => 'GameUserRepository.php',
        'GameMissionManager' => 'GameMissionManager.php',
        'GameAdminPanel' => 'GameAdminPanel.php',
        'GameDataBuilder' => 'GameDataBuilder.php'
    ];

    if (isset($gameClasses[$className])) {
        $filePath = GAME_DB_TABLES_PATH . '/' . $gameClasses[$className];
        if (file_exists($filePath)) {
            require_once $filePath;
        }
    }
}

// Rejestrujemy autoloader
spl_autoload_register('game_autoload');

/**
 * Inicjalizacja systemu gry
 */
class GameSystemInit
{

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Hook do aktywacji motywu
        add_action('after_setup_theme', [$this, 'checkAndCreateTables']);

        // Hook do sprawdzania wygasłych misji (raz dziennie)
        add_action('wp', [$this, 'scheduleExpiredMissionsCheck']);
        add_action('game_check_expired_missions', [$this, 'checkExpiredMissions']);

        // Dodanie ajax handlers dla gry (jeśli potrzebne w przyszłości)
        add_action('wp_ajax_game_action', [$this, 'handleGameAjax']);
        add_action('wp_ajax_nopriv_game_action', [$this, 'handleGameAjax']);

        // Hook do obsługi logowania graczy
        add_action('wp_login', [$this, 'handlePlayerLogin'], 10, 2);

        // Sprawdzenie czy gracz istnieje przy pierwszym zalogowaniu
        add_action('wp_loaded', [$this, 'ensurePlayerExists']);

        // Inicjalizacja panelu administracyjnego (tylko raz!)
        $this->initAdminPanel();
    }

    /**
     * Sprawdza i tworzy tabele jeśli nie istnieją (przy aktualizacji motywu)
     */
    public function checkAndCreateTables()
    {
        // Sprawdzamy czy tabele istnieją, jeśli nie - tworzymy
        $dbManager = GameDatabaseManager::getInstance();

        $missingTables = [];
        foreach (GameDatabaseManager::TABLES as $tableName) {
            if (!$dbManager->tableExists($tableName)) {
                $missingTables[] = $tableName;
            }
        }

        // Jeśli brakuje tabel, tworzymy je automatycznie
        if (!empty($missingTables)) {
            $results = $dbManager->createAllTables();

            // Opcjonalnie: logowanie wyników
            if (WP_DEBUG) {
                error_log('Game System: Created missing tables: ' . implode(', ', $missingTables));
            }
        }
    }

    /**
     * Inicjalizuje panel administracyjny
     */
    private function initAdminPanel()
    {
        // Tworzymy instancję panelu administracyjnego
        // Panel automatycznie rejestruje swoje menu przez hook admin_menu w konstruktorze
        new GameAdminPanel();
    }

    /**
     * Harmonogram sprawdzania wygasłych misji
     */
    public function scheduleExpiredMissionsCheck()
    {
        if (!wp_next_scheduled('game_check_expired_missions')) {
            wp_schedule_event(time(), 'daily', 'game_check_expired_missions');
        }
    }

    /**
     * Sprawdza wygasłe misje
     */
    public function checkExpiredMissions()
    {
        $missionManager = new GameMissionManager();
        $expiredCount = $missionManager->checkExpiredMissions();

        if (WP_DEBUG && $expiredCount > 0) {
            error_log("Game System: Marked $expiredCount missions as expired");
        }
    }

    /**
     * Obsługuje akcje AJAX gry (jeśli będą potrzebne)
     */
    public function handleGameAjax()
    {
        // Sprawdzenie nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'game_ajax_nonce')) {
            wp_die('Błąd bezpieczeństwa');
        }

        $action = $_POST['game_action'] ?? '';

        switch ($action) {
            case 'get_player_data':
                $this->ajaxGetPlayerData();
                break;

            case 'update_stat':
                $this->ajaxUpdateStat();
                break;

            default:
                wp_send_json_error('Nieznana akcja');
        }
    }

    /**
     * Obsługuje logowanie gracza
     */
    public function handlePlayerLogin($user_login, $user)
    {
        $userRepo = new GameUserRepository();

        // Sprawdzamy czy gracz ma dane gry, jeśli nie - tworzymy
        if (!$userRepo->playerExists($user->ID)) {
            $result = $userRepo->createPlayer($user->ID);

            if (WP_DEBUG) {
                error_log("Game System: Created player data for user {$user->ID}");
            }
        }
    }

    /**
     * Zapewnia że zalogowany gracz ma dane gry
     */
    public function ensurePlayerExists()
    {
        if (is_user_logged_in()) {
            $userId = get_current_user_id();
            $userRepo = new GameUserRepository();

            if (!$userRepo->playerExists($userId)) {
                $userRepo->createPlayer($userId);
            }
        }
    }

    /**
     * AJAX: Pobiera dane gracza
     */
    private function ajaxGetPlayerData()
    {
        $userId = get_current_user_id();

        if (!$userId) {
            wp_send_json_error('Nie zalogowany');
        }

        $userRepo = new GameUserRepository();
        $data = $userRepo->getPlayerData($userId);

        wp_send_json_success($data);
    }

    /**
     * AJAX: Aktualizuje statystykę (przykład użycia delta managera)
     */
    private function ajaxUpdateStat()
    {
        $userId = get_current_user_id();

        if (!$userId) {
            wp_send_json_error('Nie zalogowany');
        }

        $stat = sanitize_text_field($_POST['stat'] ?? '');
        $delta = intval($_POST['delta'] ?? 0);

        if (empty($stat) || $delta === 0) {
            wp_send_json_error('Niepoprawne dane');
        }

        $deltaManager = new GameDeltaManager();

        // Przykład: zwiększenie doświadczenia
        if ($stat === 'experience') {
            $result = $deltaManager->addExperience($userId, $delta);
            wp_send_json($result);
        }

        wp_send_json_error('Nieobsługiwana statystyka');
    }
}

/**
 * Pomocnicze funkcje globalne
 */

/**
 * Pobiera repozytorium gracza
 */
function game_get_user_repository()
{
    return new GameUserRepository();
}

/**
 * Pobiera menedżera misji
 */
function game_get_mission_manager()
{
    return new GameMissionManager();
}

/**
 * Pobiera menedżera delta
 */
function game_get_delta_manager()
{
    return new GameDeltaManager();
}

/**
 * Pobiera menedżera bazy danych
 */
function game_get_database_manager()
{
    return GameDatabaseManager::getInstance();
}

/**
 * Pobiera buildera danych
 */
function game_get_data_builder()
{
    return new GameDataBuilder();
}

/**
 * Sprawdza czy gracz ma dane gry
 */
function game_player_exists($userId)
{
    $userRepo = game_get_user_repository();
    return $userRepo->playerExists($userId);
}

/**
 * Tworzy gracza jeśli nie istnieje
 */
function game_ensure_player_exists($userId)
{
    if (!game_player_exists($userId)) {
        $userRepo = game_get_user_repository();
        return $userRepo->createPlayer($userId);
    }
    return ['success' => true, 'message' => 'Gracz już istnieje'];
}

/**
 * Pobiera pełne dane gracza
 */
function game_get_player_data($userId)
{
    $userRepo = game_get_user_repository();
    return $userRepo->getPlayerData($userId);
}

/**
 * Dodaje doświadczenie graczowi
 */
function game_add_experience($userId, $exp)
{
    $deltaManager = game_get_delta_manager();
    return $deltaManager->addExperience($userId, $exp);
}

/**
 * Zadaje obrażenia graczowi
 */
function game_deal_damage($userId, $damage)
{
    $deltaManager = game_get_delta_manager();
    return $deltaManager->takeDamage($userId, $damage);
}

/**
 * Leczy gracza
 */
function game_heal_player($userId, $healing)
{
    $deltaManager = game_get_delta_manager();
    return $deltaManager->heal($userId, $healing);
}

/**
 * Zmienia relację z NPC
 */
function game_change_npc_relation($userId, $npcId, $delta)
{
    $deltaManager = game_get_delta_manager();
    return $deltaManager->changeRelation($userId, $npcId, $delta);
}

/**
 * Dodaje przedmiot graczowi
 */
function game_add_item($userId, $itemId, $quantity = 1)
{
    $deltaManager = game_get_delta_manager();
    return $deltaManager->addItem($userId, $itemId, $quantity);
}

/**
 * Odblokowuje rejon dla gracza
 */
function game_unlock_area($userId, $areaId, $sceneId)
{
    $userRepo = game_get_user_repository();
    return $userRepo->unlockArea($userId, $areaId, $sceneId);
}

/**
 * Rozpoczyna misję dla gracza
 */
function game_start_mission($userId, $missionId)
{
    $missionManager = game_get_mission_manager();
    return $missionManager->activateMission($userId, $missionId);
}

/**
 * Kończy zadanie misji
 */
function game_complete_task($userId, $missionId, $taskId)
{
    $missionManager = game_get_mission_manager();
    return $missionManager->completeTask($userId, $missionId, $taskId);
}

/**
 * Aktualizuje wynik walki z NPC
 */
function game_update_fight_result($userId, $npcId, $result)
{
    $userRepo = game_get_user_repository();
    return $userRepo->updateFightResult($userId, $npcId, $result);
}

/**
 * Sprawdza stan systemu gry
 */
function game_system_check()
{
    $status = [
        'database' => [],
        'classes' => [],
        'errors' => []
    ];

    // Sprawdź klasy
    $requiredClasses = [
        'GameDatabaseManager',
        'GameDeltaManager',
        'GameUserRepository',
        'GameMissionManager',
        'GameAdminPanel',
        'GameDataBuilder'
    ];

    foreach ($requiredClasses as $className) {
        $status['classes'][$className] = class_exists($className);
        if (!class_exists($className)) {
            $status['errors'][] = "Klasa $className nie została załadowana";
        }
    }

    // Sprawdź tabele
    try {
        $dbManager = GameDatabaseManager::getInstance();
        foreach (GameDatabaseManager::TABLES as $tableName) {
            $exists = $dbManager->tableExists($tableName);
            $status['database'][$tableName] = $exists;
            if (!$exists) {
                $status['errors'][] = "Tabela $tableName nie istnieje";
            }
        }
    } catch (Exception $e) {
        $status['errors'][] = "Błąd sprawdzania bazy danych: " . $e->getMessage();
    }

    return $status;
}

/**
 * Loguje błędy systemu gry
 */
function game_log_error($message, $context = [])
{
    if (WP_DEBUG && WP_DEBUG_LOG) {
        $logMessage = '[GAME_SYSTEM] ' . $message;
        if (!empty($context)) {
            $logMessage .= ' Context: ' . json_encode($context);
        }
        error_log($logMessage);
    }
}

/**
 * Walidacja ID użytkownika
 */
function game_validate_user_id($userId)
{
    $userId = intval($userId);
    if ($userId <= 0) {
        return false;
    }

    $user = get_user_by('ID', $userId);
    return $user !== false;
}

/**
 * Walidacja danych statystyk
 */
function game_validate_stats($stats)
{
    if (!is_array($stats)) {
        return false;
    }

    $allowedStats = ['strength', 'endurance', 'dexterity', 'intelligence', 'wisdom', 'charisma'];

    foreach ($stats as $stat => $value) {
        if (!in_array($stat, $allowedStats)) {
            return false;
        }
        if (!is_numeric($value) || $value < 0 || $value > 100) {
            return false;
        }
    }

    return true;
}

/**
 * Bezpieczna konwersja wartości dla systemu delta
 */
function game_safe_numeric($value, $min = null, $max = null)
{
    $value = floatval($value);

    if ($min !== null && $value < $min) {
        $value = $min;
    }

    if ($max !== null && $value > $max) {
        $value = $max;
    }

    return $value;
}

// Inicjalizujemy system gry
GameSystemInit::getInstance();
