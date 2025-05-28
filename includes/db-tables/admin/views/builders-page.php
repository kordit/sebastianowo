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
        <div class="ga-card ga-card--warning">
            <div class="ga-card__header">
                <h3 class="ga-card__title">🗺️ Builder obszarów</h3>
                <div class="ga-card__meta">
                    <span class="ga-stat-compact">
                        <strong>0</strong> obszarów
                    </span>
                    <span class="ga-stat-compact">
                        <strong>0</strong> scen
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
</div>