<?php

if (!defined('ABSPATH')) {
    exit;
}

class NPC_Debug
{
    private static $log_file;

    public static function init()
    {
        self::$log_file = dirname(__FILE__) . '/debug.log';
    }

    public static function log($message, $data = null)
    {
        if (!self::$log_file) {
            self::init();
        }

        $timestamp = date('[Y-m-d H:i:s]');
        $log_message = $timestamp . ' ' . $message;

        if ($data !== null) {
            $log_message .= "\nData: " . print_r($data, true);
        }

        $log_message .= "\n" . str_repeat('-', 80) . "\n";

        error_log($log_message, 3, self::$log_file);
    }

    public static function log_post()
    {
        self::log('POST Data:', $_POST);
        self::log('FILES Data:', $_FILES);
        self::log('REQUEST URI:', $_SERVER['REQUEST_URI']);
    }

    public static function log_form_submission($form_name, $data)
    {
        self::log("Form Submission: {$form_name}", $data);
    }
}

// Initialize the debug class
NPC_Debug::init();
