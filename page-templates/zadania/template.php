<?php

$current_user_id = get_current_user_id();
$user_missions = get_field('user_missions', 'user_' . $current_user_id);
$mission_tasks_progress = get_field('mission_tasks_progress', 'user_' . $current_user_id);

// Aktywne i ukończone misje
$active_missions = isset($user_missions['active_missions']) ? $user_missions['active_missions'] : [];
$completed_missions = isset($user_missions['completed']) ? $user_missions['completed'] : [];

function get_task_progress_map($mission_id, $mission_tasks_progress)
{
    $map = [];
    if (is_array($mission_tasks_progress)) {
        foreach ($mission_tasks_progress as $progress) {
            if (isset($progress['mission_id']) && $progress['mission_id'] == $mission_id && isset($progress['task_id'])) {
                $map[$progress['task_id']] = $progress;
            }
        }
    }
    return $map;
}
?>
<div class="zadania-container">
    <div class="tab-controls">
        <div class="tab-btn active" data-tab="aktywne">Aktywne Misje</div>
        <div class="tab-btn" data-tab="ukonczone">Zrealizowane Misje</div>
    </div>
    <div class="tabs-content">
        <!-- Aktywne misje -->
        <div class="tab-panel active" id="aktywne-tab">
            <?php if (!empty($active_missions)) : ?>
                <?php foreach ($active_missions as $mission_id) :
                    $mission = get_post($mission_id);
                    if (!$mission) continue;
                    $mission_title = $mission->post_title;
                    $mission_content = wp_trim_words(strip_tags($mission->post_content), 30, '...');
                    $mission_tasks = get_field('tasks', $mission_id);
                    $task_progress_map = get_task_progress_map($mission_id, $mission_tasks_progress);
                    // Filtruj tylko zadania rozpoczęte lub ukończone
                    $visible_tasks = [];
                    $completed_tasks = 0;
                    if (is_array($mission_tasks)) {
                        foreach ($mission_tasks as $index => $task) {
                            $task_id = isset($task['task_id']) ? $task['task_id'] : 'task_' . $index;
                            $progress = isset($task_progress_map[$task_id]) ? $task_progress_map[$task_id] : null;
                            $status = $progress && isset($progress['status']) ? $progress['status'] : 'not_started';
                            if ($status === 'in_progress' || $status === 'completed') {
                                $visible_tasks[] = [
                                    'task' => $task,
                                    'progress' => $progress,
                                    'status' => $status
                                ];
                                if ($status === 'completed') $completed_tasks++;
                            }
                        }
                    }
                    $tasks_count = count($visible_tasks);
                    $completion_percent = $tasks_count > 0 ? floor(($completed_tasks / $tasks_count) * 100) : 0;
                ?>
                    <div class="mission-card">
                        <div class="mission-header">
                            <h3>Misja "<?php echo esc_html($mission_title); ?>"</h3>
                            <div class="mission-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo esc_attr($completion_percent); ?>%"></div>
                                </div>
                                <span class="progress-text"><?php echo esc_html($completion_percent); ?>% (<?php echo esc_html($completed_tasks); ?>/<?php echo esc_html($tasks_count); ?>)</span>
                            </div>
                        </div>
                        <div class="mission-description">
                            <p><?php echo esc_html($mission_content); ?></p>
                        </div>
                        <?php if ($tasks_count > 0) : ?>
                            <div class="mission-tasks">
                                <ul>
                                    <?php foreach ($visible_tasks as $t) :
                                        $task = $t['task'];
                                        $progress = $t['progress'];
                                        $status = $t['status'];
                                        $task_class = $status === 'completed' ? 'task-completed' : 'task-in-progress';
                                        $task_title = !empty($task['title']) ? $task['title'] : (!empty($task['description']) ? wp_trim_words(strip_tags($task['description']), 8, '...') : 'Zadanie');
                                        $task_description = !empty($task['description']) ? $task['description'] : '';
                                    ?>
                                        <li class="task-item <?php echo $task_class; ?>">
                                            <div class="task-header">
                                                <div class="bold"><?php echo esc_html($task_title); ?></div>
                                                <div class="task-status <?php echo $status; ?>">
                                                    <span>
                                                        <?php if ($status === 'completed') echo 'Ukończone';
                                                        elseif ($status === 'in_progress') echo 'Aktywne'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php if ($task_description) : ?>
                                                <div class="task-description">
                                                    <?php echo wpautop($task_description); ?>
                                                </div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="empty-state">
                    <p>Nie masz żadnych aktywnych misji. Porozmawiaj z NPC, aby otrzymać nowe zadania.</p>
                </div>
            <?php endif; ?>
        </div>
        <!-- Ukończone misje -->
        <div class="tab-panel" id="ukonczone-tab">
            <?php if (!empty($completed_missions)) : ?>
                <div class="completed-missions-grid">
                    <?php foreach ($completed_missions as $mission_id) :
                        $mission = get_post($mission_id);
                        if (!$mission) continue;
                        $mission_title = $mission->post_title;
                        $mission_content = wp_trim_words(strip_tags($mission->post_content), 20, '...');
                    ?>
                        <div class="completed-mission-card">
                            <h3>Misja "<?php echo esc_html($mission_title); ?>"</h3>
                            <div class="mission-description">
                                <p><?php echo esc_html($mission_content); ?></p>
                            </div>
                            <div class="mission-completion-badge">
                                <span>Ukończona</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="empty-state">
                    <p>Nie ukończyłeś jeszcze żadnej misji. Wykonaj zadania z aktywnych misji, aby je tutaj zobaczyć.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>