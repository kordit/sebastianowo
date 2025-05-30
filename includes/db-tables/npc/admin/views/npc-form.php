<?php

/**
 * Widok formularza NPC
 */

if (!defined('ABSPATH')) {
    exit;
}

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
            <h2>Podstawowe informacje</h2>
            <form method="post" action="<?php echo admin_url('admin.php?page=npc-add'); ?>" class="npc-form">
                <?php wp_nonce_field('npc_admin_action', 'npc_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo $is_edit ? 'update_npc' : 'create_npc'; ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="npc_id" value="<?php echo $npc->id; ?>">
                <?php endif; ?>

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
                    <tr>
                        <th scope="row">
                            <label for="image_url">URL obrazka</label>
                        </th>
                        <td>
                            <input type="url" id="image_url" name="image_url"
                                value="<?php echo esc_url($npc->image_url ?? ''); ?>"
                                class="regular-text">
                            <button type="button" class="button" id="upload-image-btn">Wybierz plik</button>
                            <?php if ($npc && $npc->image_url): ?>
                                <div class="image-preview">
                                    <img src="<?php echo esc_url($npc->image_url); ?>"
                                        alt="<?php echo esc_attr($npc->name); ?>"
                                        style="max-width: 100px; height: auto;">
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php echo $is_edit ? 'Zaktualizuj NPC' : 'Utwórz NPC'; ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=npc-manager'); ?>" class="button">
                        Powrót do listy
                    </a>
                </p>
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
                    </ol>
                </div>

                <div class="dialogs-container">
                    <?php if (empty($dialogs)): ?>
                        <div class="no-dialogs">
                            <p>Ten NPC nie ma jeszcze żadnych dialogów.</p>
                        </div>
                    <?php else: ?>
                        <?php
                        foreach ($dialogs as $dialog):
                        ?>
                            <div class="dialog-item" data-dialog-id="<?php echo $dialog->id; ?>">
                                <div class="dialog-header">
                                    <h3 class="dialog-title">
                                        <?php echo esc_html($dialog->title); ?>
                                    </h3>
                                    <div class="dialog-actions">
                                        <button type="button" class="button-link edit-dialog-btn">Edytuj</button>
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
                                                        <div class="answer-actions">
                                                            <button type="button" class="button-link edit-answer-btn"
                                                                data-answer-id="<?php echo $answer->id; ?>"
                                                                data-dialog-id="<?php echo $dialog->id; ?>">
                                                                Edytuj
                                                            </button>
                                                            <a href="<?php echo wp_nonce_url(
                                                                            admin_url('admin.php?page=npc-add&action=delete_answer&answer_id=' . $answer->id . '&npc_id=' . $npc->id),
                                                                            'npc_admin_action',
                                                                            'npc_nonce'
                                                                        ); ?>"
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
                    <?php endif; ?>
                </div>
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
            <input type="hidden" name="answer_id" id="answer-id" value="">

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
                                ?>
                                    <option value="<?php echo $d->id; ?>"><?php echo esc_html($d->title); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Wybierz dialog, który zostanie wyświetlony po wybraniu tej odpowiedzi.</p>
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