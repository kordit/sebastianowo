<?php

/**
 * Strona builderów w panelu admin
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ga-container">
    <!-- Header -->
    <div class="ga-header">
        <h1 class="ga-header__title">🔧 Buildery</h1>
        <p class="ga-header__subtitle">Narzędzia do automatycznego budowania struktur gry</p>
    </div>

    <div class="ga-grid ga-grid--2col">
        <!-- Builder relacji NPC -->
        <div class="ga-card ga-card--primary">
            <div class="ga-card__header">
                <h3 class="ga-card__title">👥 Builder relacji NPC</h3>
                <div class="ga-card__meta">
                    <span class="ga-stat-compact">
                        <strong><?php echo esc_html($relations_stats['total_users']); ?></strong> użytkowników
                    </span>
                    <span class="ga-stat-compact">
                        <strong><?php echo esc_html($relations_stats['total_npcs']); ?></strong> NPC
                    </span>
                    <span class="ga-stat-compact <?php echo $relations_stats['missing_relations'] > 0 ? 'ga-stat-compact--warning' : 'ga-stat-compact--success'; ?>">
                        <strong><?php echo esc_html($relations_stats['missing_relations']); ?></strong> brakuje
                    </span>
                </div>
            </div>
            <div class="ga-card__content">
                <div class="ga-actions">
                    <form method="post">
                        <?php wp_nonce_field('build_npc_relations'); ?>
                        <button type="submit" name="build_npc_relations" class="ga-button ga-button--success">
                            🚀 Zbuduj relacje
                        </button>
                    </form>

                    <form method="post" onsubmit="return confirm('Usunąć wszystkie relacje?');">
                        <?php wp_nonce_field('clear_npc_relations'); ?>
                        <button type="submit" name="clear_npc_relations" class="ga-button ga-button--danger">
                            🗑️ Wyczyść
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Builder misji -->
        <div class="ga-card ga-card--warning">
            <div class="ga-card__header">
                <h3 class="ga-card__title">📜 Builder misji</h3>
                <div class="ga-card__meta">
                    <span class="ga-stat-compact">
                        <strong>0</strong> misji
                    </span>
                    <span class="ga-stat-compact">
                        <strong>0</strong> zadań
                    </span>
                    <span class="ga-badge ga-badge--neutral">Niedostępne</span>
                </div>
            </div>
            <div class="ga-card__content">
                <div class="ga-actions">
                    <button class="ga-button ga-button--disabled" disabled>🔧 W przygotowaniu</button>
                </div>
            </div>
        </div>

        <!-- Builder obszarów -->
        <div class="ga-card ga-card--primary">
            <div class="ga-card__header">
                <h3 class="ga-card__title">🗺️ Builder obszarów</h3>
                <div class="ga-card__meta">
                    <span class="ga-stat-compact">
                        <strong><?php echo isset($areas_structure_stats['total_areas']) ? esc_html($areas_structure_stats['total_areas']) : 0; ?></strong> obszarów
                    </span>
                    <span class="ga-stat-compact">
                        <strong><?php echo isset($areas_database_stats['users_with_areas']) ? esc_html($areas_database_stats['users_with_areas']) : 0; ?></strong> użytkowników
                    </span>
                    <span class="ga-stat-compact <?php echo isset($areas_structure_stats['total_areas']) && $areas_database_stats['areas_in_db'] < $areas_structure_stats['total_areas'] ? 'ga-stat-compact--warning' : 'ga-stat-compact--success'; ?>">
                        <strong><?php echo isset($areas_database_stats['total_connections']) ? esc_html($areas_database_stats['total_connections']) : 0; ?></strong> powiązań
                    </span>
                </div>
            </div>
            <div class="ga-card__content">
                <div class="ga-actions">
                    <form method="post">
                        <?php wp_nonce_field('build_area_connections'); ?>
                        <button type="submit" name="build_area_connections" class="ga-button ga-button--success">
                            🚀 Zbuduj powiązania obszarów
                        </button>
                    </form>

                    <form method="post" onsubmit="return confirm('Usunąć wszystkie powiązania obszarów?');">
                        <?php wp_nonce_field('clear_area_connections'); ?>
                        <button type="submit" name="clear_area_connections" class="ga-button ga-button--danger">
                            🗑️ Wyczyść
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Szczegółowe statystyki relacji -->
    <div class="ga-card ga-card--full ga-card--info ga-mt-3">
        <div class="ga-card__header">
            <h3 class="ga-card__title">📊 Szczegółowe statystyki relacji NPC</h3>
        </div>
        <div class="ga-card__content">
            <div class="ga-stats">
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo esc_html($relations_stats['expected_relations']); ?></div>
                    <div class="ga-stat__label">Oczekiwane relacje</div>
                </div>
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo esc_html($relations_stats['total_relations']); ?></div>
                    <div class="ga-stat__label">Aktualne relacje</div>
                </div>
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo esc_html($relations_stats['known_relations']); ?></div>
                    <div class="ga-stat__label">Poznane NPC</div>
                </div>
                <div class="ga-stat ga-stat--success">
                    <div class="ga-stat__number"><?php echo esc_html($relations_stats['positive_relations']); ?></div>
                    <div class="ga-stat__label">Pozytywne relacje</div>
                </div>
                <div class="ga-stat ga-stat--danger">
                    <div class="ga-stat__number"><?php echo esc_html($relations_stats['negative_relations']); ?></div>
                    <div class="ga-stat__label">Negatywne relacje</div>
                </div>
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo esc_html($relations_stats['neutral_relations']); ?></div>
                    <div class="ga-stat__label">Neutralne relacje</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Szczegółowe statystyki obszarów -->
    <div class="ga-card ga-card--full ga-card--info ga-mt-3">
        <div class="ga-card__header">
            <h3 class="ga-card__title">📊 Szczegółowe statystyki obszarów</h3>
        </div>
        <div class="ga-card__content">
            <div class="ga-stats">
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo isset($areas_structure_stats['total_areas']) ? esc_html($areas_structure_stats['total_areas']) : 0; ?></div>
                    <div class="ga-stat__label">Wszystkie obszary</div>
                </div>
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo isset($areas_database_stats['areas_in_db']) ? esc_html($areas_database_stats['areas_in_db']) : 0; ?></div>
                    <div class="ga-stat__label">Obszary w bazie</div>
                </div>
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo isset($areas_database_stats['users_with_areas']) ? esc_html($areas_database_stats['users_with_areas']) : 0; ?></div>
                    <div class="ga-stat__label">Użytkownicy z obszarami</div>
                </div>
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo isset($areas_database_stats['total_connections']) ? esc_html($areas_database_stats['total_connections']) : 0; ?></div>
                    <div class="ga-stat__label">Wszystkie połączenia</div>
                </div>
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo isset($areas_database_stats['unlocked_connections']) ? esc_html($areas_database_stats['unlocked_connections']) : 0; ?></div>
                    <div class="ga-stat__label">Odblokowane</div>
                </div>
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo isset($areas_database_stats['viewed_connections']) ? esc_html($areas_database_stats['viewed_connections']) : 0; ?></div>
                    <div class="ga-stat__label">Oglądane</div>
                </div>
            </div>

            <?php if (isset($areas_list) && !empty($areas_list)) : ?>
                <div class="ga-table-container ga-mt-3">
                    <h4>Lista obszarów</h4>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nazwa</th>
                                <th>Typ</th>
                                <th>Sceny</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($areas_list as $area) : ?>
                                <tr>
                                    <td><?php echo esc_html($area['id']); ?></td>
                                    <td><?php echo esc_html($area['title']); ?></td>
                                    <td><?php echo esc_html($area['type']); ?></td>
                                    <td>
                                        <?php
                                        if (!empty($area['scenes'])) {
                                            echo esc_html(count($area['scenes'])) . ' scen';
                                            echo '<div class="ga-tag-list">';
                                            foreach ($area['scenes'] as $scene) {
                                                echo '<span class="ga-tag">' . esc_html($scene) . '</span>';
                                            }
                                            echo '</div>';
                                        } else {
                                            echo '<span class="ga-muted">Brak scen</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>