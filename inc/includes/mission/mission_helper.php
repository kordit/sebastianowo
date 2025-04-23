<?php

/**
 * Funkcje pomocnicze dla misji
 * 
 * @package Game
 */

if (!defined('ABSPATH')) {
    exit; // Zabezpieczenie przed bezpośrednim dostępem
}

/**
 * Automatycznie dodaje misję do wszystkich użytkowników przy jej utworzeniu lub aktualizacji
 */
function add_mission_to_all_users($post_id, $post)
{
    // Sprawdź czy to jest misja i czy nie jest autosave
    if ($post->post_type !== 'mission' || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    // Sprawdź czy status wpisu to publish (opublikowany)
    if ($post->post_status !== 'publish') {
        return;
    }

    // Pobierz wszystkich użytkowników (można dodać warunki, np. tylko gracze)
    $users = get_users(array(
        'fields' => 'ID'
    ));

    foreach ($users as $user_id) {
        // Sprawdź czy misja już jest dodana do użytkownika
        $active_missions = get_field('active_missions', 'user_' . $user_id);

        if (!$active_missions) {
            $active_missions = array();
        }

        // Sprawdź czy użytkownik już ma tę misję
        $mission_exists = false;
        foreach ($active_missions as $mission_data) {
            if (isset($mission_data['mission']) && ($mission_data['mission'] == $post_id ||
                (is_object($mission_data['mission']) && $mission_data['mission']->ID == $post_id))) {
                $mission_exists = true;
                break;
            }
        }

        if (!$mission_exists) {
            // Pobierz zadania z misji
            $mission_tasks = get_mission_tasks($post_id);
            $progress = array();

            // Przygotuj progress dla każdego zadania
            if (is_array($mission_tasks)) {
                foreach ($mission_tasks as $task_id => $task_title) {
                    $progress[] = array(
                        'task_id' => $task_id,
                        'task_title' => $task_title,
                        'completed' => 0
                    );
                }
            }

            // Dodaj misję do aktywnych misji użytkownika
            $active_missions[] = array(
                'mission' => $post_id,
                'progress' => $progress,
                'status' => 'inprogress'
            );

            update_field('active_missions', $active_missions, 'user_' . $user_id);
        }
    }
}
add_action('save_post', 'add_mission_to_all_users', 10, 2);

/**
 * Pobiera listę zadań dla danej misji w formacie ID => Tytuł
 * 
 * @param int $mission_id ID misji
 * @return array Tablica zadań w formacie task_index => tytuł zadania
 */
function get_mission_tasks($mission_id)
{
    $choices = array();
    $tasks = get_field('tasks', $mission_id);

    if (is_array($tasks) && !empty($tasks)) {
        foreach ($tasks as $index => $task) {
            // Używam indeksu zadania jako klucza
            $task_key = 'task_' . $index;

            // Tworzymy etykietę na podstawie tytułu zadania
            $task_label = '';
            if (!empty($task['title'])) {
                $task_label = $task['title'];
            } else if (!empty($task['description'])) {
                // Fallback na opis jeśli tytuł jest pusty
                $description = strip_tags($task['description']);
                $task_label = (strlen($description) > 40) ?
                    substr($description, 0, 40) . '...' :
                    $description;
            } else {
                $task_label = 'Zadanie ' . ($index + 1);
            }

            $choices[$task_key] = $task_label;
        }
    }

    return $choices;
}
