<div class="wrap">
    <h1>Game Database Setup</h1>

    <div class="game-admin-section">
        <h2>Status tabel</h2>

        <?php if ($tables_exist): ?>
            <div class="notice notice-success inline">
                <p><strong>✓ Wszystkie tabele gry istnieją</strong></p>
            </div>
        <?php else: ?>
            <div class="notice notice-warning inline">
                <p><strong>⚠ Nie wszystkie tabele gry istnieją</strong></p>
            </div>
        <?php endif; ?>

        <div class="game-tables-status">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">Szczegółowy status tabel:</h3>
                <button type="button" class="button button-secondary" onclick="location.reload()">
                    🔄 Odśwież status
                </button>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" style="width: 25%;">Nazwa tabeli</th>
                        <th scope="col" style="width: 35%;">Opis</th>
                        <th scope="col" style="width: 15%;">Status</th>
                        <th scope="col" style="width: 25%;">Liczba rekordów</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables_status as $table): ?>
                        <tr>
                            <td><code><?php echo esc_html($table['full_name']); ?></code></td>
                            <td><?php echo esc_html($table['description']); ?></td>
                            <td>
                                <?php if ($table['exists']): ?>
                                    <span class="status-badge status-exists">✓ Istnieje</span>
                                <?php else: ?>
                                    <span class="status-badge status-missing">✗ Brak</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($table['exists']): ?>
                                    <span class="record-count"><?php echo number_format($table['count']); ?></span>
                                <?php else: ?>
                                    <span class="record-count-na">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="game-admin-section">
        <h2>Zarządzanie tabelami</h2>

        <div class="game-admin-actions">
            <form method="post" style="display: inline-block; margin-right: 20px;">
                <?php wp_nonce_field('create_tables'); ?>
                <input type="submit" name="create_tables" class="button button-primary"
                    value="Utwórz/Aktualizuj tabele"
                    onclick="return confirm('Czy na pewno chcesz utworzyć/zaktualizować tabele?')">
            </form>

            <form method="post" style="display: inline-block;">
                <?php wp_nonce_field('drop_tables'); ?>
                <input type="submit" name="drop_tables" class="button button-secondary"
                    value="Usuń tabele"
                    onclick="return confirm('UWAGA: To usunie wszystkie dane graczy! Czy na pewno?')">
            </form>
        </div>

        <div class="game-admin-info">
            <h3>Informacje o tabelach:</h3>
            <p>System używa <?php echo count($tables_status); ?> tabel do przechowywania danych graczy. Wszystkie tabele są powiązane relacjami i mają automatyczne usuwanie powiązanych rekordów (CASCADE).</p>

            <details>
                <summary><strong>Szczegóły struktury tabel</strong></summary>
                <ul>
                    <li><code>game_users</code> - główna tabela z danymi gracza (statystyki, lokalizacja, zasoby)</li>
                    <li><code>game_user_items</code> - ekwipunek i przedmioty graczy</li>
                    <li><code>game_user_areas</code> - odblokowane rejony i sceny</li>
                    <li><code>game_user_relations</code> - relacje z postaciami NPC</li>
                    <li><code>game_user_fight_tokens</code> - tokeny walk i cooldowny</li>
                    <li><code>game_user_missions</code> - aktywne i ukończone misje</li>
                    <li><code>game_user_mission_tasks</code> - zadania w ramach misji</li>
                </ul>
            </details>
        </div>
    </div>
</div>