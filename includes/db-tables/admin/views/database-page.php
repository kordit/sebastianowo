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
            <h3>Szczegółowy status tabel:</h3>
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

    <div class="game-admin-section">
        <h2>Synchronizacja użytkowników</h2>

        <div class="game-users-sync-status">
            <div class="sync-stats-grid">
                <div class="sync-stat-item">
                    <span class="sync-stat-number"><?php echo number_format($users_stats['wp_users']); ?></span>
                    <span class="sync-stat-label">Użytkownicy WordPress</span>
                </div>
                <div class="sync-stat-item">
                    <span class="sync-stat-number"><?php echo number_format($users_stats['game_users']); ?></span>
                    <span class="sync-stat-label">Gracze w bazie gry</span>
                </div>
                <div class="sync-stat-item">
                    <span class="sync-stat-number sync-stat-missing"><?php echo number_format($users_stats['missing']); ?></span>
                    <span class="sync-stat-label">Brakujący gracze</span>
                </div>
            </div>

            <?php if ($users_stats['missing'] > 0): ?>
                <div class="notice notice-warning inline">
                    <p><strong>⚠ Znaleziono <?php echo $users_stats['missing']; ?> użytkowników WordPress bez konta gracza</strong></p>
                    <p>Użyj przycisku poniżej, aby automatycznie utworzyć konta graczy dla wszystkich brakujących użytkowników.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-success inline">
                    <p><strong>✓ Wszyscy użytkownicy WordPress mają konta graczy</strong></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="game-admin-actions">
            <form method="post" style="display: inline-block; margin-right: 20px;">
                <?php wp_nonce_field('import_users'); ?>
                <input type="submit" name="import_users" class="button button-primary"
                    value="<?php echo $users_stats['missing'] > 0 ? 'Importuj brakujących graczy (' . $users_stats['missing'] . ')' : 'Sprawdź ponownie synchronizację'; ?>"
                    <?php if ($users_stats['missing'] == 0): ?>onclick="return confirm('Wszyscy użytkownicy są już zsynchronizowani. Sprawdzić ponownie?')" <?php else: ?>onclick="return confirm('Czy chcesz utworzyć konta graczy dla <?php echo $users_stats['missing']; ?> użytkowników?')" <?php endif; ?>>
            </form>
        </div>

        <div class="game-admin-info">
            <h3>Jak działa synchronizacja:</h3>
            <ul>
                <li><strong>Automatyczna</strong> - Nowi użytkownicy WordPress automatycznie otrzymują konto gracza przy rejestracji</li>
                <li><strong>Manualna</strong> - Istniejących użytkowników można zaimportować za pomocą przycisku powyżej</li>
                <li><strong>Domyślne wartości</strong> - Nowi gracze rozpoczynają z podstawowymi statystykami i zasobami</li>
                <li><strong>Bezpieczne</strong> - Istniejące konta graczy nie są nadpisywane podczas importu</li>
            </ul>
        </div>
    </div>
</div>