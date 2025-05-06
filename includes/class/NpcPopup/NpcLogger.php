<?php

/**
 * Klasa NpcLogger
 * 
 * Zapewnia funkcjonalność logowania dla systemu dialogów NPC.
 * 
 * @package Game
 * @subpackage NpcPopup
 * @since 1.0.0
 */
class NpcLogger
{
    /**
     * Ścieżka do pliku logu
     *
     * @var string
     */
    private string $log_file;

    /**
     * Maksymalny rozmiar pliku logu w bajtach (5MB)
     *
     * @var int
     */
    private const MAX_LOG_SIZE = 5242880;

    /**
     * Konstruktor klasy NpcLogger
     * 
     * @param string|null $log_file Opcjonalna ścieżka do pliku logu
     */
    public function __construct(?string $log_file = null)
    {
        $this->log_file = $log_file ?? get_template_directory() . '/npc_debug.log';
        $this->rotate_log_if_needed();
    }

    /**
     * Dodaje wpis do pliku logu
     *
     * @param string|mixed $message Wiadomość do zalogowania lub dane do zrzutu
     * @param string|array $level Poziom logu (info, warning, error, debug) lub dane do zrzutu
     * @return void
     */
    public function log($message, $level = 'info'): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $timestamp = date('[Y-m-d H:i:s]');

        // Sprawdź czy drugi parametr jest poziomem logowania czy danymi do zrzutu
        if (is_string($level) && in_array($level, ['info', 'warning', 'error', 'debug'])) {
            $level_upper = strtoupper($level);

            // Jeśli message jest tablicą lub obiektem, wykonaj zrzut
            if (is_array($message) || is_object($message)) {
                $log_message = "{$timestamp} [{$level_upper}] " . print_r($message, true) . PHP_EOL;
            } else {
                $log_message = "{$timestamp} [{$level_upper}] {$message}" . PHP_EOL;
            }
        } else {
            // Drugi parametr to dane do zrzutu
            $log_message = "{$timestamp} [INFO] {$message}" . PHP_EOL;
            if (!empty($level)) {
                $log_message .= "{$timestamp} [DATA] " . print_r($level, true) . PHP_EOL;
            }
        }

        error_log($log_message, 3, $this->log_file);
    }

    /**
     * Kompatybilność ze starszym kodem używającym debug_log
     *
     * @param string|mixed $message Wiadomość do zalogowania
     * @param array|null $data Opcjonalne dane do zrzutu
     * @return void
     */
    public function debug_log($message, $data = null): void
    {
        $this->log($message, $data !== null ? $data : 'debug');
    }

    /**
     * Rotuje plik logu jeśli przekracza maksymalny rozmiar
     *
     * @return void
     */
    private function rotate_log_if_needed(): void
    {
        if (!file_exists($this->log_file)) {
            return;
        }

        if (filesize($this->log_file) > self::MAX_LOG_SIZE) {
            $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
            rename($this->log_file, $backup_file);
        }
    }
}
