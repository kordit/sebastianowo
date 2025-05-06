<?php

/**
 * Klasa NpcLogger
 *
 * Odpowiada za logowanie działań związanych z NPC do pliku.
 *
 * @package Game
 * @since 1.0.0
 */

class NpcLogger
{
    /**
     * Ścieżka do pliku logów debugowania
     * 
     * @var string
     */
    private string $debug_log_file;

    /**
     * Konstruktor klasy
     * 
     * @param string $log_file_path Ścieżka do pliku logów (opcjonalna)
     */
    public function __construct(string $log_file_path = '')
    {
        if (empty($log_file_path)) {
            $this->debug_log_file = get_template_directory() . '/npc_debug.log';
        } else {
            $this->debug_log_file = $log_file_path;
        }
    }

    /**
     * Zapisuje informacje debugowania do pliku
     * 
     * @param string $message Wiadomość do zapisania
     * @param mixed $data Dodatkowe dane do logowania (opcjonalne)
     */
    public function debug_log(string $message, $data = null): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}";

        if ($data !== null) {
            $data_string = is_array($data) || is_object($data) ? print_r($data, true) : $data;
            $log_message .= "\n" . $data_string;
        }

        $log_message .= "\n--------------------------------------------------\n";
        file_put_contents($this->debug_log_file, $log_message, FILE_APPEND);
    }
}
