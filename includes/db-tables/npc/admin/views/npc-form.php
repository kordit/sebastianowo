<?php

/**
 * Widok formularza NPC
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include komponentu akcji
require_once NPC_PLUGIN_PATH . 'admin/components/answer-actions.php';

$is_edit = $npc && $npc->id;
$page_title = $is_edit ? 'Edytuj NPC: ' . esc_html($npc->name) : 'Dodaj Nowy NPC';
?>

<div class="wrap npc-admin-wrap">
    <h1><?php echo $page_title; ?></h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($_GET['message']) {
                    case 'created':
                        echo 'NPC został utworzony.';
                        break;
                    case 'updated':
                        echo 'NPC został zaktualizowany.';
                        break;
                    case 'dialog_created':
                        echo 'Dialog został dodany.';
                        break;
                    case 'dialog_updated':
                        echo 'Dialog został zaktualizowany.';
                        break;
                    case 'dialog_deleted':
                        echo 'Dialog został usunięty.';
                        break;
                    case 'answer_created':
                        echo 'Odpowiedź została dodana.';
                        break;
                    case 'answer_updated':
                        echo 'Odpowiedź została zaktualizowana.';
                        break;
                    case 'answer_deleted':
                        echo 'Odpowiedź została usunięta.';
                        break;
                    default:
                        echo 'Akcja wykonana pomyślnie.';
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php
                switch ($_GET['error']) {
                    case 'create_failed':
                        echo 'Nie udało się utworzyć NPC.';
                        break;
                    case 'update_failed':
                        echo 'Nie udało się zaktualizować NPC.';
                        break;
                    case 'dialog_create_failed':
                        echo 'Nie udało się dodać dialogu.';
                        break;
                    case 'dialog_update_failed':
                        echo 'Nie udało się zaktualizować dialogu.';
                        break;
                    case 'dialog_delete_failed':
                        echo 'Nie udało się usunąć dialogu.';
                        break;
                    default:
                        echo 'Wystąpił błąd.';
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="npc-form-container">
        <!-- Formularz NPC -->
        <div class="npc-basic-info">
            <form method="post" action="<?php echo admin_url('admin.php?page=npc-add'); ?>" class="npc-form">
                <?php wp_nonce_field('npc_admin_action', 'npc_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo $is_edit ? 'update_npc' : 'create_npc'; ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="npc_id" value="<?php echo $npc->id; ?>">
                <?php endif; ?>

                <!-- Zakładki formularza -->
                <div class="npc-form-tabs">
                    <a href="#tab-basic" class="npc-tab-link-style npc-tab-link active">Podstawowe</a>
                    <a href="#tab-stats" class="npc-tab-link-style npc-tab-link">Statystyki</a>
                </div>

                <!-- Zakładka: Podstawowe informacje -->
                <div id="tab-basic" class="npc-tab-content active">
                    <div class="npc-form-card">
                        <h3>Podstawowe informacje</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="name">Nazwa NPC <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="text" id="name" name="name"
                                        value="<?php echo esc_attr($npc->name ?? ''); ?>"
                                        class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="description">Opis</label>
                                </th>
                                <td>
                                    <textarea id="description" name="description"
                                        rows="4" class="large-text"><?php echo esc_textarea($npc->description ?? ''); ?></textarea>
                                </td>
                            </tr>
                        </table>
                        <h3>Avatary postaci</h3>
                        <div class="npc-form-grid">
                            <div class="avatar-field">
                                <label for="avatar">Avatar (ID obrazka)</label>
                                <input type="number" id="avatar" name="avatar"
                                    value="<?php echo esc_attr($npc->avatar ?? ''); ?>"
                                    class="regular-text" min="0">
                                <p class="description">ID obrazka z media library do użycia jako avatar.</p>
                            </div>
                            <div class="avatar-field">
                                <label for="avatar_full">Avatar pełny (ID obrazka)</label>
                                <input type="number" id="avatar_full" name="avatar_full"
                                    value="<?php echo esc_attr($npc->avatar_full ?? ''); ?>"
                                    class="regular-text" min="0">
                                <p class="description">ID obrazka z media library do użycia jako pełny avatar.</p>
                            </div>
                            <div class="avatar-field">
                                <label for="avatar_full_back">Avatar tył (ID obrazka)</label>
                                <input type="number" id="avatar_full_back" name="avatar_full_back"
                                    value="<?php echo esc_attr($npc->avatar_full_back ?? ''); ?>"
                                    class="regular-text" min="0">
                                <p class="description">ID obrazka z media library do użycia jako avatar z tyłu.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zakładka: Statystyki -->
                <div id="tab-stats" class="npc-tab-content">
                    <div class="npc-form-card">
                        <h3>Statystyki podstawowe</h3>
                        <div class="stats-group">
                            <div class="stat-item">
                                <label for="strength">Siła</label>
                                <input type="number" id="strength" name="strength"
                                    value="<?php echo esc_attr($npc->strength ?? 0); ?>">
                            </div>
                            <div class="stat-item">
                                <label for="defence">Obrona</label>
                                <input type="number" id="defence" name="defence"
                                    value="<?php echo esc_attr($npc->defence ?? 0); ?>">
                            </div>
                            <div class="stat-item">
                                <label for="dexterity">Zręczność</label>
                                <input type="number" id="dexterity" name="dexterity"
                                    value="<?php echo esc_attr($npc->dexterity ?? 0); ?>">
                            </div>
                            <div class="stat-item">
                                <label for="perception">Percepcja</label>
                                <input type="number" id="perception" name="perception"
                                    value="<?php echo esc_attr($npc->perception ?? 0); ?>">
                            </div>
                            <div class="stat-item">
                                <label for="technical">Technika</label>
                                <input type="number" id="technical" name="technical"
                                    value="<?php echo esc_attr($npc->technical ?? 0); ?>">
                            </div>
                            <div class="stat-item">
                                <label for="charisma">Charyzma</label>
                                <input type="number" id="charisma" name="charisma"
                                    value="<?php echo esc_attr($npc->charisma ?? 0); ?>">
                            </div>
                        </div>
                        <h3>Umiejętności</h3>
                        <div class="stats-group">
                            <div class="stat-item">
                                <label for="combat">Walka</label>
                                <input type="number" id="combat" name="combat"
                                    value="<?php echo esc_attr($npc->combat ?? 0); ?>">
                            </div>
                            <div class="stat-item">
                                <label for="steal">Kradzież</label>
                                <input type="number" id="steal" name="steal"
                                    value="<?php echo esc_attr($npc->steal ?? 0); ?>">
                            </div>
                            <div class="stat-item">
                                <label for="craft">Rzemiosło</label>
                                <input type="number" id="craft" name="craft"
                                    value="<?php echo esc_attr($npc->craft ?? 0); ?>">
                            </div>
                            <div class="stat-item">
                                <label for="trade">Handel</label>
                                <input type="number" id="trade" name="trade"
                                    value="<?php echo esc_attr($npc->trade ?? 0); ?>">
                            </div>
                            <div class="stat-item">
                                <label for="relations">Relacje</label>
                                <input type="number" id="relations" name="relations"
                                    value="<?php echo esc_attr($npc->relations ?? 0); ?>">
                            </div>
                            <div class="stat-item">
                                <label for="street">Ulica</label>
                                <input type="number" id="street" name="street"
                                    value="<?php echo esc_attr($npc->street ?? 0); ?>">
                            </div>
                        </div>
                        <h3>Punkty życia</h3>
                        <div class="stats-group">
                            <div class="stat-item">
                                <label for="life">Obecne życie</label>
                                <input type="number" id="life" name="life"
                                    value="<?php echo esc_attr($npc->life ?? 0); ?>">
                            </div>
                            <div class="stat-item">
                                <label for="max_life">Maksymalne życie</label>
                                <input type="number" id="max_life" name="max_life"
                                    value="<?php echo esc_attr($npc->max_life ?? 0); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="npc-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo $is_edit ? 'Zaktualizuj NPC' : 'Utwórz NPC'; ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=npc-manager'); ?>" class="button">
                        Powrót do listy
                    </a>
                </div>
            </form>
        </div>

        <?php if ($is_edit): ?>
            <!-- Sekcja dialogów -->
            <div class="npc-dialogs-section" id="dialogs">
                <h2>Dialogi NPC</h2>

                <div class="dialog-actions">
                    <button type="button" class="button button-primary" id="add-dialog-btn">
                        Dodaj nowy dialog
                    </button>
                </div>

                <div class="dialog-instruction" style="margin: 15px 0; background-color: #f0f6fc; border-left: 4px solid #007cba; padding: 10px;">
                    <strong>Jak działa system dialogów:</strong>
                    <ol style="margin-left: 20px; margin-top: 5px;">
                        <li>Dialogi pokazują się w kolejności ustalonej przez przeciągnięcie (drag & drop).</li>
                        <li>Pierwszy dialog jest automatycznie dialogiem początkowym.</li>
                        <li>System wybierze pierwszy dialog, który spełnia wszystkie warunki.</li>
                        <li>Odpowiedzi również można sortować przez przeciągnięcie.</li>
                        <li>Dialogi są zorganizowane według lokalizacji w zakładkach.</li>
                    </ol>
                </div>

                <?php if (empty($dialogs)): ?>
                    <div class="no-dialogs">
                        <p>Ten NPC nie ma jeszcze żadnych dialogów.</p>
                    </div>
                <?php else: ?>
                    <!-- Tabbed Interface for Locations -->
                    <?php if (count($locations) > 1): ?>
                        <div class="location-tabs">
                            <div class="nav-tab-wrapper">
                                <?php foreach ($locations as $index => $location): ?>
                                    <a href="#tab-<?php echo esc_attr($location['slug']); ?>"
                                        class="npc-tab-link-style nav-tab <?php echo $index === 0 ? 'nav-tab-active' : ''; ?>"
                                        data-location="<?php echo esc_attr($location['slug']); ?>">
                                        <?php echo esc_html($location['title']); ?>
                                        <span class="count">(<?php echo $location['count']; ?>)</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tab Content -->
                    <div class="location-tab-content">
                        <?php foreach ($locations as $index => $location): ?>
                            <div id="tab-<?php echo esc_attr($location['slug']); ?>"
                                class="tab-pane <?php echo $index === 0 ? 'active' : ''; ?>"
                                data-location="<?php echo esc_attr($location['slug']); ?>">

                                <div class="dialogs-container" data-location="<?php echo esc_attr($location['slug']); ?>">
                                    <?php if (!empty($dialogs_by_location[$location['slug']])): ?>
                                        <?php foreach ($dialogs_by_location[$location['slug']] as $dialog): ?>
                                            <div class="dialog-item" data-dialog-id="<?php echo $dialog->id; ?>">
                                                <div class="dialog-header">
                                                    <h3 class="dialog-title">
                                                        <?php echo esc_html($dialog->title); ?>
                                                        <?php if (!empty($dialog->location) && $dialog->location !== '__none__'): ?>
                                                            <span class="location-badge"><?php echo esc_html($dialog->location); ?></span>
                                                        <?php endif; ?>
                                                    </h3>
                                                    <div class="dialog-actions">
                                                        <button type="button" class="button-link edit-dialog-btn" data-dialog-id="<?php echo $dialog->id; ?>">Edytuj</button>
                                                        <a href="<?php echo wp_nonce_url(
                                                                        admin_url('admin.php?page=npc-add&action=delete_dialog&dialog_id=' . $dialog->id . '&npc_id=' . $npc->id),
                                                                        'npc_admin_action',
                                                                        'npc_nonce'
                                                                    ); ?>"
                                                            onclick="return confirm('Czy na pewno chcesz usunąć ten dialog?');"
                                                            class="button-link delete-dialog-btn">
                                                            Usuń
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="dialog-content">
                                                    <p><?php echo esc_html(wp_trim_words($dialog->content, 20)); ?></p>
                                                    <?php if (!empty($dialog->answers)): ?>
                                                        <div class="dialog-answers">
                                                            <strong>Odpowiedzi (<?php echo count($dialog->answers); ?>):</strong>
                                                            <ul>
                                                                <?php foreach ($dialog->answers as $answer): ?>
                                                                    <li>
                                                                        <?php echo esc_html(wp_trim_words($answer->text, 10)); ?>
                                                                        <?php
                                                                        // Wyświetl miniaturki akcji
                                                                        if (!empty($answer->actions)) {
                                                                            echo generate_action_badges($answer->actions);
                                                                        }
                                                                        ?>
                                                                        <div class="answer-actions">
                                                                            <button type="button" class="button-link edit-answer-btn"
                                                                                data-answer-id="<?php echo $answer->id; ?>"
                                                                                data-dialog-id="<?php echo $dialog->id; ?>">
                                                                                Edytuj
                                                                            </button>
                                                                            <?php
                                                                            $delete_url = add_query_arg([
                                                                                'page' => 'npc-add',
                                                                                'action' => 'delete_answer',
                                                                                'answer_id' => $answer->id,
                                                                                'npc_id' => $npc->id,
                                                                                'npc_nonce' => wp_create_nonce('npc_admin_action')
                                                                            ], admin_url('admin.php'));
                                                                            ?>
                                                                            <a href="<?php echo esc_url($delete_url); ?>"
                                                                                onclick="return confirm('Czy na pewno chcesz usunąć tę odpowiedź?');"
                                                                                class="button-link delete-answer-btn">
                                                                                Usuń
                                                                            </a>
                                                                        </div>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                            <button type="button" class="button button-secondary add-answer-btn"
                                                                data-dialog-id="<?php echo $dialog->id; ?>">
                                                                Dodaj odpowiedź
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="dialog-answers">
                                                            <p>Brak odpowiedzi dla tego dialogu.</p>
                                                            <button type="button" class="button button-secondary add-answer-btn"
                                                                data-dialog-id="<?php echo $dialog->id; ?>">
                                                                Dodaj odpowiedź
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="no-dialogs">
                                            <p>Brak dialogów w tej lokalizacji.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal dla dodawania/edycji dialogu -->
<div id="dialog-modal" class="npc-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Dodaj nowy dialog</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form id="dialog-form" method="post" action="<?php echo admin_url('admin.php?page=npc-add'); ?>">
            <?php wp_nonce_field('npc_admin_action', 'npc_nonce'); ?>
            <input type="hidden" name="action" id="dialog-action" value="create_dialog">
            <input type="hidden" name="npc_id" value="<?php echo $npc->id; ?>">
            <input type="hidden" name="dialog_id" id="dialog-id" value="">

            <div class="modal-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="dialog_title">Tytuł dialogu <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="dialog_title" name="dialog_title"
                                class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dialog_content">Treść dialogu <span class="required">*</span></label>
                        </th>
                        <td>
                            <textarea id="dialog_content" name="dialog_content"
                                rows="6" class="large-text" required></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dialog_location">Lokalizacja</label>
                        </th>
                        <td>
                            <select id="dialog_location" name="dialog_location" class="regular-text">
                                <option value="">-- Bez lokalizacji --</option>
                                <?php if (isset($all_locations) && !empty($all_locations)): ?>
                                    <?php foreach ($all_locations as $location): ?>
                                        <option value="<?php echo esc_attr($location->post_name); ?>">
                                            <?php echo esc_html($location->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <p class="description">Wybierz lokalizację, w której będzie dostępny ten dialog. Pozostaw puste dla dialogów uniwersalnych.</p>
                        </td>
                    </tr>
                    <!-- Usunięto pola Kolejność i Dialog początkowy. Kolejność będzie ustalana przez przeciąganie -->
                    <input type="hidden" id="dialog_order" name="dialog_order" value="0">
                    <!-- Wartość is_starting_dialog jest ustawiana automatycznie w AdminPanel.php -->
                </table>
            </div>

            <div class="modal-footer">
                <button type="submit" class="button button-primary">Zapisz dialog</button>
                <button type="button" class="button modal-cancel">Anuluj</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal dla dodawania/edycji odpowiedzi -->
<div id="answer-modal" class="npc-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="answer-modal-title">Dodaj nową odpowiedź</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form id="answer-form" method="post" action="<?php echo admin_url('admin.php?page=npc-add'); ?>">
            <?php wp_nonce_field('npc_admin_action', 'npc_nonce'); ?>
            <input type="hidden" name="action" id="answer-action" value="create_answer">
            <input type="hidden" name="npc_id" value="<?php echo $npc->id; ?>">
            <input type="hidden" name="dialog_id" id="answer-dialog-id" value="">
            <input type="hidden" name="answer_id" id="answer_id" value="">

            <div class="modal-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="answer_text">Tekst odpowiedzi <span class="required">*</span></label>
                        </th>
                        <td>
                            <textarea id="answer_text" name="answer_text"
                                rows="3" class="large-text" required></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="answer_next_dialog_id">Następny dialog</label>
                        </th>
                        <td>
                            <select id="answer_next_dialog_id" name="answer_next_dialog_id">
                                <option value="">-- Brak następnego dialogu --</option>
                                <?php
                                $unique_dialog_ids_select = array();
                                foreach ($dialogs as $d):
                                    // Pomijamy dialogi z ID, które już były dodane do listy (unikamy duplikatów)
                                    if (in_array($d->id, $unique_dialog_ids_select)) {
                                        continue;
                                    }
                                    $unique_dialog_ids_select[] = $d->id;

                                    // Określ lokalizację dialogu
                                    $dialog_location = empty($d->location) ? '__none__' : $d->location;
                                ?>
                                    <option value="<?php echo $d->id; ?>"
                                        data-location="<?php echo esc_attr($dialog_location); ?>"
                                        title="<?php echo !empty($d->location) && $d->location !== '__none__' ? 'Lokalizacja: ' . esc_attr($d->location) : 'Bez lokalizacji'; ?>">
                                        <?php echo esc_html($d->title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" id="toggle-dialog-filter" class="button button-small" style="margin-left: 5px;" title="Pokaż/ukryj wszystkie dialogi">
                                🔍
                            </button>
                            <p class="description">
                                Wybierz dialog, który zostanie wyświetlony po wybraniu tej odpowiedzi.
                                <strong>Wyświetlane są tylko dialogi z tej samej lokalizacji.</strong>
                                <br><small>💡 Aby dodać dialog z innej lokalizacji, przełącz się na odpowiednią zakładkę lokalizacji lub kliknij przycisk 🔍.</small>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <?php
                            // Renderuj komponent akcji
                            render_answer_actions_manager(null, []);
                            ?>
                        </td>
                    </tr>
                    <!-- Usunięto pole Kolejność. Kolejność będzie ustalana przez przeciąganie -->
                    <input type="hidden" id="answer_order" name="answer_order" value="0">
                </table>
            </div>

            <div class="modal-footer">
                <button type="submit" class="button button-primary">Zapisz odpowiedź</button>
                <button type="button" class="button modal-cancel">Anuluj</button>
            </div>
        </form>
    </div>
</div>