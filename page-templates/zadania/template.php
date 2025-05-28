<?php

/**
 * Template Name: Zadania
 * 
 * Szablon strony zadań (misji) użytkownika
 * 
 * @package game
 */

// Pobierz dane aktualnego użytkownika
$current_user = wp_get_current_user();
if (!$current_user->ID) {
    wp_redirect(home_url('/login/'));
    exit;
}

// Dołączenie arkuszy stylów
wp_enqueue_style('zadania-style', get_template_directory_uri() . '/page-templates/zadania/style.css');

// Zmienna pomocnicza do śledzenia pierwszej misji
$first_mission_id = null;
?>

<div class="missions-container">
    <div class="missions-header">
        <h1 class="missions-title">Twoje zadania</h1>
    </div>

    <?php
    // Pobieranie wszystkich pól ACF użytkownika
    $user_fields = get_fields("user_{$current_user->ID}");

    // Tablica na misje
    $user_missions = [];
    $first_active_mission = null;

    // Wyszukiwanie wszystkich pól zaczynających się od 'mission_'
    if (!empty($user_fields)) {
        foreach ($user_fields as $field_key => $field_value) {
            if (strpos($field_key, 'mission_') === 0 && is_array($field_value)) {
                $mission_id = (int) str_replace('mission_', '', $field_key);
                $user_missions[$mission_id] = $field_value;

                // Zapamiętaj pierwszą aktywną misję
                if (
                    !$first_active_mission &&
                    isset($field_value['status']) &&
                    ($field_value['status'] === 'in_progress' || $field_value['status'] === 'completed')
                ) {
                    $first_active_mission = 'mission_' . $mission_id;
                }
            }
        }
    }
    ?>

    <div class="missions-list" x-data="{ openMission: '<?php echo esc_attr($first_active_mission); ?>' }">
        <?php
        // Jeżeli znaleziono jakieś misje
        if (!empty($user_missions)) {
            foreach ($user_missions as $mission_id => $mission_data) {
                // Sprawdź czy misja jest w toku lub zakończona
                if (
                    !isset($mission_data['status']) ||
                    ($mission_data['status'] !== 'in_progress' && $mission_data['status'] !== 'completed')
                ) {
                    continue;
                }

                // Pobierz dane misji z CPT
                $mission_post = get_post($mission_id);
                if (!$mission_post) {
                    continue;
                }

                // Pobierz dodatkowe dane misji
                $mission_description = get_field('mission_description', $mission_id);
                $mission_tasks = get_field('mission_tasks', $mission_id);

                // Generuj ID dla misji w Alpine.js
                $mission_alpine_id = 'mission_' . $mission_id;
        ?>

                <div class="mission-item">
                    <div class="mission-header"
                        @click="openMission = (openMission === '<?php echo esc_attr($mission_alpine_id); ?>') ? null : '<?php echo esc_attr($mission_alpine_id); ?>'">
                        <h2 class="mission-title">
                            <?php echo esc_html($mission_post->post_title); ?>

                            <span class="mission-status <?php echo esc_attr($mission_data['status']); ?>">
                                <?php
                                switch ($mission_data['status']) {
                                    case 'in_progress':
                                        echo 'W trakcie';
                                        break;
                                    case 'completed':
                                        echo 'Ukończona';
                                        break;
                                    case 'failed':
                                        echo 'Nieudana';
                                        break;
                                    default:
                                        echo 'Nieznany status';
                                }
                                ?>
                            </span>
                        </h2>

                        <div class="mission-toggle">
                            <i class="mission-icon" :class="openMission === '<?php echo esc_attr($mission_alpine_id); ?>' ? 'open' : 'closed'"></i>
                        </div>
                    </div>

                    <div class="mission-content"
                        x-show="openMission === '<?php echo esc_attr($mission_alpine_id); ?>'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-4"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 transform translate-y-0"
                        x-transition:leave-end="opacity-0 transform -translate-y-4">

                        <?php if (!empty($mission_description)) : ?>
                            <div class="mission-description">
                                <?php echo wp_kses_post(wpautop($mission_description)); ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        // Sprawdź, czy zadania istnieją
                        if (!empty($mission_data['tasks']) && is_array($mission_data['tasks'])) :
                            // Znajdź pierwsze aktywne zadanie
                            $first_active_task = null;
                            foreach ($mission_data['tasks'] as $task_id => $task_data) {
                                if ($task_data !== 'not_started' && !(is_array($task_data) && isset($task_data['status']) && $task_data['status'] === 'not_started')) {
                                    $first_active_task = 'task_' . sanitize_title($task_id);
                                    break;
                                }
                            }
                        ?>
                            <!-- Lista zadań misji -->
                            <div class="mission-tasks" x-data="{ openTask: '<?php echo esc_attr($first_active_task); ?>' }">
                                <h3>Zadania:</h3>
                                <ul class="tasks-list">
                                    <?php
                                    // Iteruj przez zadania misji
                                    foreach ($mission_data['tasks'] as $task_id => $task_data) {
                                        // Pomiń zadania nierozpoczęte
                                        if ($task_data === 'not_started' || (is_array($task_data) && isset($task_data['status']) && $task_data['status'] === 'not_started')) {
                                            continue;
                                        }

                                        // Znajdź dane zadania w CPT misji
                                        $task_info = null;
                                        $task_title = '';
                                        $task_description = '';

                                        if (!empty($mission_tasks)) {
                                            foreach ($mission_tasks as $task_index => $task) {
                                                $cpt_task_id = isset($task['task_id']) ?
                                                    $task['task_id'] :
                                                    sanitize_title($task['task_title']) . '_' . $task_index;

                                                if ($cpt_task_id === $task_id) {
                                                    $task_info = $task;
                                                    $task_title = $task['task_title'];
                                                    $task_description = isset($task['task_description']) ? $task['task_description'] : '';
                                                    break;
                                                }
                                            }
                                        }

                                        // Jeśli nie znaleziono informacji o zadaniu, użyj ID jako awaryjny tytuł
                                        if (empty($task_title)) {
                                            $task_title = ucfirst(str_replace(['-', '_'], ' ', $task_id));
                                        }

                                        // Określ status zadania
                                        $task_status = '';
                                        $task_status_text = '';

                                        // Czy zadanie jest typu NPC (złożona struktura)
                                        if (is_array($task_data)) {
                                            if (isset($task_data['status'])) {
                                                // Jeśli zadanie ma określony status
                                                $task_status = $task_data['status'];

                                                switch ($task_status) {
                                                    case 'in_progress':
                                                        $task_status_text = 'W trakcie';
                                                        break;
                                                    case 'completed':
                                                        $task_status_text = 'Ukończone';
                                                        break;
                                                    case 'failed':
                                                        $task_status_text = 'Nieudane';
                                                        break;
                                                    default:
                                                        $task_status_text = 'Nieznany status';
                                                }
                                            }
                                        } else {
                                            // Prosta wartość statusu
                                            $task_status = $task_data;

                                            switch ($task_status) {
                                                case 'in_progress':
                                                    $task_status_text = 'W trakcie';
                                                    break;
                                                case 'completed':
                                                    $task_status_text = 'Ukończone';
                                                    break;
                                                case 'failed':
                                                    $task_status_text = 'Nieudane';
                                                    break;
                                                default:
                                                    $task_status_text = 'Nieznany status';
                                            }
                                        }

                                        // Generuj ID dla zadania w Alpine.js
                                        $task_alpine_id = 'task_' . sanitize_title($task_id);
                                    ?>

                                        <li class="task-item <?php echo esc_attr($task_status); ?>"
                                            @click="openTask = (openTask === '<?php echo esc_attr($task_alpine_id); ?>') ? null : '<?php echo esc_attr($task_alpine_id); ?>'">
                                            <div class="task-header">
                                                <div class="task-title">
                                                    <?php echo esc_html($task_title); ?>
                                                </div>

                                                <div class="task-status-badge <?php echo esc_attr($task_status); ?>">
                                                    <?php echo esc_html($task_status_text); ?>
                                                </div>
                                            </div>

                                            <div class="task-body"
                                                x-show="openTask === '<?php echo esc_attr($task_alpine_id); ?>'"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0"
                                                x-transition:enter-end="opacity-100">

                                                <?php if (!empty($task_description)) : ?>
                                                    <div class="task-description">
                                                        <?php echo wp_kses_post(wpautop($task_description)); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php
                                                // Jeśli zadanie ma podzadania NPC
                                                if (is_array($task_data) && !empty($task_data)) {
                                                    // Filtrujemy, aby uzyskać tylko klucze NPC (nie statusy, daty itp.)
                                                    $npc_subtasks = array_filter(array_keys($task_data), function ($key) {
                                                        return $key !== 'status' && $key !== 'assigned_date' && $key !== 'completion_date';
                                                    });

                                                    if (!empty($npc_subtasks)) {
                                                        echo '<div class="npc-subtasks">';
                                                        echo '<h4>Szczegóły zadania:</h4>';
                                                        echo '<ul class="npc-tasks-list">';

                                                        foreach ($npc_subtasks as $npc_key) {
                                                            // Tylko jeśli status zadania NPC jest ustawiony
                                                            if (!isset($task_data[$npc_key]) || $task_data[$npc_key] === 'not_started') {
                                                                continue;
                                                            }

                                                            $npc_status = $task_data[$npc_key];

                                                            // Określ tekstowy status NPC
                                                            $npc_status_text = '';
                                                            switch ($npc_status) {
                                                                case 'in_progress':
                                                                    $npc_status_text = 'W trakcie';
                                                                    break;
                                                                case 'completed':
                                                                    $npc_status_text = 'Ukończone';
                                                                    break;
                                                                case 'failed':
                                                                    $npc_status_text = 'Nieudane';
                                                                    break;
                                                                default:
                                                                    $npc_status_text = $npc_status;
                                                            }

                                                            // Sprawdź, czy to jest NPC i pobierz jego nazwę
                                                            $npc_id = null;
                                                            if (strpos($npc_key, 'npc_') === 0) {
                                                                $npc_id = (int) str_replace('npc_', '', $npc_key);
                                                            } else if (strpos($npc_key, 'npc_target_') === 0) {
                                                                $npc_id = (int) str_replace('npc_target_', '', $npc_key);
                                                            }

                                                            if ($npc_id) {
                                                                $npc_post = get_post($npc_id);
                                                                if ($npc_post) {
                                                                    $npc_name = $npc_post->post_title;
                                                                } else {
                                                                    $npc_name = 'NPC #' . $npc_id;
                                                                }
                                                            } else {
                                                                $npc_name = ucfirst(str_replace('_', ' ', $npc_key));
                                                            }

                                                            echo '<li class="npc-task-item ' . esc_attr($npc_status) . '">';
                                                            echo '<span class="npc-name">' . esc_html($npc_name) . '</span>';
                                                            echo '<span class="npc-status">' . esc_html($npc_status_text) . '</span>';
                                                            echo '</li>';
                                                        }

                                                        echo '</ul>';
                                                        echo '</div>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </li>
                                    <?php
                                    }
                                    ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($mission_data['assigned_date'])) : ?>
                            <div class="mission-date">
                                <div class="mission-assigned-date">
                                    Przydzielono: <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($mission_data['assigned_date']))); ?>
                                </div>

                                <?php if (isset($mission_data['completion_date']) && !empty($mission_data['completion_date'])) : ?>
                                    <div class="mission-completion-date">
                                        Zakończono: <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($mission_data['completion_date']))); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
        <?php
            }
        }

        // Jeśli nie ma żadnych misji lub wszystkie są nierozpoczęte
        if (empty($user_missions) || !isset($mission_id)) {
            echo '<div class="no-missions">Nie masz jeszcze żadnych aktywnych misji.</div>';
        }
        ?>
    </div>
</div>