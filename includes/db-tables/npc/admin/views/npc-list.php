<?php

/**
 * Widok listy NPC
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap npc-admin-wrap">
    <h1 class="wp-heading-inline">Lista NPC</h1>
    <a href="<?php echo admin_url('admin.php?page=npc-add'); ?>" class="page-title-action">Dodaj Nowy</a>

    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($_GET['message']) {
                    case 'deleted':
                        echo 'NPC został usunięty.';
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
                    case 'delete_failed':
                        echo 'Nie udało się usunąć NPC.';
                        break;
                    default:
                        echo 'Wystąpił błąd.';
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="npc-stats-cards">
        <div class="npc-stat-card">
            <div class="stat-number"><?php echo count($npcs); ?></div>
            <div class="stat-label">Łącznie NPC</div>
        </div>
    </div>

    <div class="npc-list-container">
        <?php if (empty($npcs)): ?>
            <div class="npc-empty-state">
                <h3>Brak NPC</h3>
                <p>Nie ma jeszcze żadnych NPC w systemie.</p>
                <a href="<?php echo admin_url('admin.php?page=npc-add'); ?>" class="button button-primary">
                    Dodaj pierwszego NPC
                </a>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped npc-list-table">
                <thead>
                    <tr>
                        <th scope="col" class="column-image">Obrazek</th>
                        <th scope="col" class="column-name">Nazwa</th>
                        <th scope="col" class="column-dialogs">Dialogi</th>
                        <th scope="col" class="column-actions">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($npcs as $npc): ?>
                        <?php
                        $dialog_count = $this->dialog_repository->count_by_npc($npc->id);
                        ?>
                        <tr>
                            <td class="column-image">
                                <?php if ($npc->image_url): ?>
                                    <img src="<?php echo esc_url($npc->image_url); ?>"
                                        alt="<?php echo esc_attr($npc->name); ?>"
                                        class="npc-thumbnail">
                                <?php else: ?>
                                    <div class="npc-no-image">
                                        <span class="dashicons dashicons-businessman"></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="column-name">
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=npc-add&npc_id=' . $npc->id); ?>">
                                        <?php echo esc_html($npc->name); ?>
                                    </a>
                                </strong>
                                <?php if ($npc->description): ?>
                                    <div class="npc-description">
                                        <?php echo esc_html(wp_trim_words($npc->description, 15)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="column-dialogs">
                                <span class="dialog-count"><?php echo $dialog_count; ?></span>
                                <?php if ($dialog_count > 0): ?>
                                    <a href="<?php echo admin_url('admin.php?page=npc-add&npc_id=' . $npc->id . '#dialogs'); ?>"
                                        class="dialog-link">Zobacz</a>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=npc-add&npc_id=' . $npc->id); ?>">
                                            Edytuj
                                        </a>
                                    </span>
                                    |
                                    <span class="delete">
                                        <a href="<?php echo wp_nonce_url(
                                                        admin_url('admin.php?page=npc-manager&action=delete_npc&npc_id=' . $npc->id),
                                                        'npc_admin_action',
                                                        'npc_nonce'
                                                    ); ?>"
                                            onclick="return confirm('Czy na pewno chcesz usunąć tego NPC? To usunie również wszystkie jego dialogi.');"
                                            class="delete-link">
                                            Usuń
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>