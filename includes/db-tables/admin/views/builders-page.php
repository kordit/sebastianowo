<?php

/**
 * Strona builderów w panelu admin
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>🔧 Buildery</h1>
    <p>Narzędzia do automatycznego budowania struktur gry</p>

    <div class="game-admin-grid">
        <!-- Builder relacji NPC -->
        <div class="game-admin-card builder-card">
            <h2>👥 Builder relacji NPC</h2>

            <div class="builder-stats-compact">
                <span class="stat-compact">
                    <strong><?php echo esc_html($relations_stats['total_users']); ?></strong> użytkowników
                </span>
                <span class="stat-compact">
                    <strong><?php echo esc_html($relations_stats['total_npcs']); ?></strong> NPC
                </span>
                <span class="stat-compact <?php echo $relations_stats['missing_relations'] > 0 ? 'stat-warning' : 'stat-success'; ?>">
                    <strong><?php echo esc_html($relations_stats['missing_relations']); ?></strong> brakuje
                </span>
            </div>

            <div class="builder-actions-compact">
                <form method="post" style="display: inline-block; margin-right: 10px;">
                    <?php wp_nonce_field('build_npc_relations'); ?>
                    <button type="submit" name="build_npc_relations" class="button button-primary">
                        🚀 Zbuduj relacje
                    </button>
                </form>

                <form method="post" style="display: inline-block;" onsubmit="return confirm('Usunąć wszystkie relacje?');">
                    <?php wp_nonce_field('clear_npc_relations'); ?>
                    <button type="submit" name="clear_npc_relations" class="button button-secondary">
                        🗑️ Wyczyść
                    </button>
                </form>
            </div>
        </div>

        <!-- Builder misji (przykład przyszłego buildera) -->
        <div class="game-admin-card builder-card">
            <h2>📜 Builder misji</h2>

            <div class="builder-stats-compact">
                <span class="stat-compact">
                    <strong>0</strong> misji
                </span>
                <span class="stat-compact">
                    <strong>0</strong> zadań
                </span>
                <span class="stat-compact stat-disabled">
                    <strong>Niedostępne</strong>
                </span>
            </div>

            <div class="builder-actions-compact">
                <button class="button" disabled>🔧 W przygotowaniu</button>
            </div>
        </div>

        <!-- Builder przedmiotów (przykład przyszłego buildera) -->
        <div class="game-admin-card builder-card">
            <h2>🎒 Builder przedmiotów</h2>

            <div class="builder-stats-compact">
                <span class="stat-compact">
                    <strong>0</strong> przedmiotów
                </span>
                <span class="stat-compact">
                    <strong>0</strong> kategorii
                </span>
                <span class="stat-compact stat-disabled">
                    <strong>Niedostępne</strong>
                </span>
            </div>

            <div class="builder-actions-compact">
                <button class="button" disabled>🔧 W przygotowaniu</button>
            </div>
        </div>

        <!-- Builder obszarów (przykład przyszłego buildera) -->
        <div class="game-admin-card builder-card">
            <h2>🗺️ Builder obszarów</h2>

            <div class="builder-stats-compact">
                <span class="stat-compact">
                    <strong>0</strong> obszarów
                </span>
                <span class="stat-compact">
                    <strong>0</strong> scen
                </span>
                <span class="stat-compact stat-disabled">
                    <strong>Niedostępne</strong>
                </span>
            </div>

            <div class="builder-actions-compact">
                <button class="button" disabled>🔧 W przygotowaniu</button>
            </div>
        </div>

    </div>

    <!-- Szczegółowe statystyki relacji (zwijane) -->
    <details class="builder-details">
        <summary><strong>📊 Szczegółowe statystyki relacji NPC</strong></summary>
        <div class="game-admin-card">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Oczekiwane relacje:</span>
                    <span class="stat-value"><?php echo esc_html($relations_stats['expected_relations']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Aktualne relacje:</span>
                    <span class="stat-value"><?php echo esc_html($relations_stats['total_relations']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Poznane NPC:</span>
                    <span class="stat-value"><?php echo esc_html($relations_stats['known_relations']); ?></span>
                </div>
                <div class="stat-item stat-positive">
                    <span class="stat-label">Pozytywne relacje:</span>
                    <span class="stat-value"><?php echo esc_html($relations_stats['positive_relations']); ?></span>
                </div>
                <div class="stat-item stat-negative">
                    <span class="stat-label">Negatywne relacje:</span>
                    <span class="stat-value"><?php echo esc_html($relations_stats['negative_relations']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Neutralne relacje:</span>
                    <span class="stat-value"><?php echo esc_html($relations_stats['neutral_relations']); ?></span>
                </div>
            </div>
        </div>
    </details>
</div>