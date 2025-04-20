<?php
/*
Template Name: Zadania
*/
get_header();

$current_user_id = get_current_user_id();

// Pobierz wszystkie aktywne misje użytkownika
$active_missions = get_field('active_missions', 'user_' . $current_user_id);

// Pobierz ukończone misje
$completed_missions = get_field('completed_missions', 'user_' . $current_user_id);
?>

<div class="zadania-container">
    <div class="tab-controls">
        <button class="tab-btn active" data-tab="aktywne">Aktywne Misje</button>
        <button class="tab-btn" data-tab="ukonczone">Zrealizowane Misje</button>
    </div>

    <div class="tabs-content">
        <!-- Aktywne misje -->
        <div class="tab-panel active" id="aktywne-tab">
            <?php if (!empty($active_missions)) : ?>
                <?php foreach ($active_missions as $mission_data) :
                    $mission = $mission_data['mission'];

                    if (empty($mission)) continue;

                    $mission_id = $mission->ID;
                    $mission_title = $mission->post_title;
                    $mission_content = wp_trim_words(strip_tags($mission->post_content), 30, '...');

                    // Pobierz zadania bezpośrednio z definicji misji
                    $mission_tasks = get_field('tasks', $mission_id);
                    $total_tasks = is_array($mission_tasks) ? count($mission_tasks) : 0;
                    $completed_tasks = 0;

                    // Utwórz mapę ukończonych zadań z danych użytkownika
                    $completed_task_map = [];
                    if (isset($mission_data['progress']) && is_array($mission_data['progress'])) {
                        foreach ($mission_data['progress'] as $progress_item) {
                            if (isset($progress_item['task_id']) && isset($progress_item['completed']) && $progress_item['completed']) {
                                $completed_task_map[$progress_item['task_id']] = true;
                                $completed_tasks++;
                            }
                        }
                    }

                    // Oblicz procent ukończenia
                    $completion_percent = $total_tasks > 0 ? floor(($completed_tasks / $total_tasks) * 100) : 0;
                ?>
                    <div class="mission-card">
                        <div class="mission-header">
                            <h3><?php echo esc_html($mission_title); ?></h3>
                            <div class="mission-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo esc_attr($completion_percent); ?>%"></div>
                                </div>
                                <span class="progress-text"><?php echo esc_html($completion_percent); ?>% (<?php echo esc_html($completed_tasks); ?>/<?php echo esc_html($total_tasks); ?>)</span>
                            </div>
                        </div>

                        <div class="mission-description">
                            <p><?php echo esc_html($mission_content); ?></p>
                        </div>

                        <?php if (!empty($mission_tasks)) : ?>
                            <div class="mission-tasks">
                                <h4>Zadania:</h4>
                                <ul>
                                    <?php foreach ($mission_tasks as $index => $task_details) :
                                        $task_id = 'task_' . $index;
                                        $is_completed = isset($completed_task_map[$task_id]);
                                        $task_title = isset($task_details['title']) ? $task_details['title'] : '';

                                        // Jeśli tytuł jest pusty, spróbuj użyć opisu
                                        if (empty($task_title) && isset($task_details['description'])) {
                                            $description = strip_tags($task_details['description']);
                                            $task_title = (strlen($description) > 40) ?
                                                substr($description, 0, 40) . '...' :
                                                $description;
                                        }

                                        // Ostatecznie, jeśli nadal nie mamy tytułu
                                        if (empty($task_title)) {
                                            $task_title = 'Zadanie ' . ($index + 1);
                                        }
                                    ?>
                                        <li class="task-item <?php echo $is_completed ? 'task-completed' : ''; ?>">
                                            <div class="task-header">
                                                <span class="task-status-icon"><?php echo $is_completed ? '✓' : '◯'; ?></span>
                                                <h4 class="task-title"><?php echo esc_html($task_title); ?></h4>
                                            </div>

                                            <?php if (!empty($task_details['description'])) : ?>
                                                <div class="task-description">
                                                    <?php echo wpautop($task_details['description']); ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php
                                            // Wyświetl wymagania do zrealizowania zadania
                                            if (!empty($task_details['requirements'])) : ?>
                                                <div class="task-requirements">
                                                    <h5>Wymagania:</h5>
                                                    <ul>
                                                        <?php foreach ($task_details['requirements'] as $requirement) :
                                                            $req_text = '';

                                                            // Określ typ wymagania
                                                            if (isset($requirement['acf_fc_layout'])) {
                                                                switch ($requirement['acf_fc_layout']) {
                                                                    case 'visit_npc':
                                                                        $npc_id = isset($requirement['npc_id']) ? $requirement['npc_id'] : 0;
                                                                        $npc_name = get_the_title($npc_id);
                                                                        $req_text = 'Odwiedź NPC: <strong>' . ($npc_name ? $npc_name : 'ID: ' . $npc_id) . '</strong>';
                                                                        break;
                                                                    case 'visited_location':
                                                                        $req_text = 'Odwiedź lokację: <strong>' . (isset($requirement['location_id']) ? $requirement['location_id'] : '') . '</strong>';
                                                                        break;
                                                                    case 'has_perk':
                                                                        $perk_id = isset($requirement['perk_id']) ? $requirement['perk_id'] : 0;
                                                                        $perk_name = get_the_title($perk_id);
                                                                        $amount = isset($requirement['amount']) ? $requirement['amount'] : 1;
                                                                        $req_text = 'Posiadaj przedmiot: <strong>' . ($perk_name ? $perk_name : 'ID: ' . $perk_id) . '</strong> (' . $amount . ' szt.)';
                                                                        break;
                                                                    case 'stat_above':
                                                                        $stat_type = isset($requirement['stat_type']) ? $requirement['stat_type'] : '';
                                                                        $min_value = isset($requirement['min_value']) ? $requirement['min_value'] : '';
                                                                        $stats_names = [
                                                                            'combat' => 'Walka',
                                                                            'steal' => 'Kradzież',
                                                                            'craft' => 'Produkcja',
                                                                            'trade' => 'Handel',
                                                                            'relations' => 'Relacje',
                                                                            'street' => 'Uliczna wiedza',
                                                                            'technical' => 'Zdolności manualne',
                                                                            'charisma' => 'Cwaniactwo',
                                                                        ];
                                                                        $stat_name = isset($stats_names[$stat_type]) ? $stats_names[$stat_type] : $stat_type;
                                                                        $req_text = "Umiejętność <strong>$stat_name</strong>: min. $min_value";
                                                                        break;
                                                                    case 'relation_with_npc':
                                                                        $npc_id = isset($requirement['npc_id']) ? $requirement['npc_id'] : 0;
                                                                        $npc_name = get_the_title($npc_id);
                                                                        $relation_value = isset($requirement['relation_value']) ? $requirement['relation_value'] : '';
                                                                        $req_text = 'Relacja z <strong>' . ($npc_name ? $npc_name : 'NPC') . '</strong>: min. ' . $relation_value;
                                                                        break;
                                                                    default:
                                                                        $req_text = 'Wymaganie: ' . $requirement['acf_fc_layout'];
                                                                }
                                                            }

                                                            if ($req_text) :
                                                        ?>
                                                                <li class="requirement-item"><?php echo $req_text; ?></li>
                                                        <?php endif;
                                                        endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>

                                            <?php
                                            // Wyświetl nagrody za zadanie
                                            if (!empty($task_details['rewards'])) : ?>
                                                <div class="task-rewards">
                                                    <h5>Nagrody:</h5>
                                                    <ul>
                                                        <?php foreach ($task_details['rewards'] as $reward) :
                                                            $reward_text = '';

                                                            // Określ typ nagrody
                                                            if (isset($reward['acf_fc_layout'])) {
                                                                switch ($reward['acf_fc_layout']) {
                                                                    case 'add_item':
                                                                        $item_type = isset($reward['item_type']) ? $reward['item_type'] : '';
                                                                        $amount = isset($reward['amount']) ? $reward['amount'] : 1;
                                                                        $items_names = [
                                                                            'gold' => 'Złote',
                                                                            'cigarettes' => 'Papierosy',
                                                                            'beer' => 'Piwo',
                                                                            'moonshine' => 'Bimber',
                                                                            'weed' => 'Zioło',
                                                                            'mushrooms' => 'Grzyby',
                                                                            'glue' => 'Klej',
                                                                        ];
                                                                        $item_name = isset($items_names[$item_type]) ? $items_names[$item_type] : $item_type;
                                                                        $reward_text = "<strong>$item_name</strong>: $amount szt.";
                                                                        break;
                                                                    case 'add_relation_with_npc':
                                                                        $npc_id = isset($reward['npc_id']) ? $reward['npc_id'] : 0;
                                                                        $npc_name = get_the_title($npc_id);
                                                                        $relation_change = isset($reward['relation_change']) ? $reward['relation_change'] : '';
                                                                        $reward_text = 'Relacja z <strong>' . ($npc_name ? $npc_name : 'NPC') . '</strong>: ' . ($relation_change > 0 ? '+' : '') . $relation_change;
                                                                        break;
                                                                    case 'add_perk':
                                                                        $perk_id = isset($reward['perk_id']) ? $reward['perk_id'] : 0;
                                                                        $perk_name = get_the_title($perk_id);
                                                                        $amount = isset($reward['amount']) ? $reward['amount'] : 1;
                                                                        $reward_text = 'Przedmiot: <strong>' . ($perk_name ? $perk_name : 'ID: ' . $perk_id) . '</strong> (' . $amount . ' szt.)';
                                                                        break;
                                                                    case 'add_exp':
                                                                        $exp_amount = isset($reward['exp_amount']) ? $reward['exp_amount'] : 0;
                                                                        $reward_text = 'Doświadczenie: <strong>' . $exp_amount . ' EXP</strong>';
                                                                        break;
                                                                    case 'add_reputation':
                                                                        $rep_change = isset($reward['reputation_change']) ? $reward['reputation_change'] : 0;
                                                                        $reward_text = 'Reputacja: <strong>' . ($rep_change > 0 ? '+' : '') . $rep_change . '</strong>';
                                                                        break;
                                                                    default:
                                                                        $reward_text = 'Nagroda: ' . $reward['acf_fc_layout'];
                                                                }
                                                            }

                                                            if ($reward_text) :
                                                        ?>
                                                                <li class="reward-item"><?php echo $reward_text; ?></li>
                                                        <?php endif;
                                                        endforeach; ?>
                                                    </ul>
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
                    <?php foreach ($completed_missions as $mission) :
                        $mission_title = $mission->post_title;
                        $mission_content = wp_trim_words(strip_tags($mission->post_content), 20, '...');
                    ?>
                        <div class="completed-mission-card">
                            <h3><?php echo esc_html($mission_title); ?></h3>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanels = document.querySelectorAll('.tab-panel');

        // Obsługa zakładek
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Usuń klasę active ze wszystkich przycisków
                tabBtns.forEach(b => b.classList.remove('active'));

                // Dodaj klasę active do klikniętego przycisku
                btn.classList.add('active');

                // Ukryj wszystkie panele
                tabPanels.forEach(panel => panel.classList.remove('active'));

                // Pokaż panel powiązany z klikniętym przyciskiem
                const tabId = btn.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
    });
</script>

<?php get_footer(); ?>