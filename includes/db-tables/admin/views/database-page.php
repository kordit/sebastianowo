<div class="wrap ga-container">
    <!-- Header -->
    <div class="ga-header">
        <h1 class="ga-header__title">🗄️ Game Database Setup</h1>
        <p class="ga-header__subtitle">Zarządzanie bazą danych i synchronizacja użytkowników</p>
    </div>

    <!-- Status tabel -->
    <div class="ga-card ga-card--primary">
        <div class="ga-card__header">
            <h3 class="ga-card__title">Status tabel</h3>
            <?php if ($tables_exist): ?>
                <div class="ga-badge ga-badge--success">✓ Wszystkie tabele istnieją</div>
            <?php else: ?>
                <div class="ga-badge ga-badge--warning">⚠ Brakuje tabel</div>
            <?php endif; ?>
        </div>
        <div class="ga-card__content">
            <table class="ga-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Nazwa tabeli</th>
                        <th style="width: 35%;">Opis</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 25%;">Liczba rekordów</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables_status as $table): ?>
                        <tr>
                            <td><code><?php echo esc_html($table['full_name']); ?></code></td>
                            <td><?php echo esc_html($table['description']); ?></td>
                            <td>
                                <?php if ($table['exists']): ?>
                                    <span class="ga-badge ga-badge--success">✓ Istnieje</span>
                                <?php else: ?>
                                    <span class="ga-badge ga-badge--danger">✗ Brak</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($table['exists']): ?>
                                    <strong><?php echo number_format($table['count']); ?></strong>
                                <?php else: ?>
                                    <span class="ga-text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Zarządzanie tabelami -->
    <div class="ga-card ga-card--warning">
        <div class="ga-card__header">
            <h3 class="ga-card__title">Zarządzanie tabelami</h3>
        </div>
        <div class="ga-card__content">
            <div class="ga-actions">
                <form method="post">
                    <?php wp_nonce_field('create_tables'); ?>
                    <button type="submit" name="create_tables" class="ga-button ga-button--primary"
                        onclick="return confirm('Czy na pewno chcesz utworzyć/zaktualizować tabele?')">
                        🔧 Utwórz/Aktualizuj tabele
                    </button>
                </form>

                <form method="post">
                    <?php wp_nonce_field('migrate_tables'); ?>
                    <button type="submit" name="migrate_tables" class="ga-button ga-button--info"
                        onclick="return confirm('Czy chcesz zmigrować strukturę tabel do najnowszej wersji?')">
                        🚀 Migruj strukturę tabel
                    </button>
                </form>

                <form method="post">
                    <?php wp_nonce_field('drop_tables'); ?>
                    <button type="submit" name="drop_tables" class="ga-button ga-button--danger"
                        onclick="return confirm('UWAGA: To usunie wszystkie dane graczy! Czy na pewno?')">
                        🗑️ Usuń tabele
                    </button>
                </form>
            </div>

            <div class="ga-notice ga-notice--info ga-mt-2">
                <div class="ga-notice__icon">ℹ️</div>
                <div>
                    <p><strong>Informacje o tabelach:</strong></p>
                    <p>System używa <?php echo count($tables_status); ?> tabel do przechowywania danych graczy. Wszystkie tabele są powiązane relacjami i mają automatyczne usuwanie powiązanych rekordów (CASCADE).</p>

                    <details class="ga-mt-1">
                        <summary><strong>Szczegóły struktury tabel</strong></summary>
                        <ul class="ga-mt-1">
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
    </div>

    <!-- Synchronizacja użytkowników -->
    <div class="ga-card ga-card--info">
        <div class="ga-card__header">
            <h3 class="ga-card__title">Synchronizacja użytkowników</h3>
        </div>
        <div class="ga-card__content">
            <div class="ga-stats ga-mb-2">
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo number_format($users_stats['wp_users']); ?></div>
                    <div class="ga-stat__label">Użytkownicy WordPress</div>
                </div>
                <div class="ga-stat">
                    <div class="ga-stat__number"><?php echo number_format($users_stats['game_users']); ?></div>
                    <div class="ga-stat__label">Gracze w bazie gry</div>
                </div>
                <div class="ga-stat <?php echo $users_stats['missing'] > 0 ? 'ga-stat--warning' : 'ga-stat--success'; ?>">
                    <div class="ga-stat__number"><?php echo number_format($users_stats['missing']); ?></div>
                    <div class="ga-stat__label">Brakujący gracze</div>
                </div>
            </div>

            <?php if ($users_stats['missing'] > 0): ?>
                <div class="ga-notice ga-notice--warning">
                    <div class="ga-notice__icon">⚠️</div>
                    <div>
                        <p><strong>Znaleziono <?php echo $users_stats['missing']; ?> użytkowników WordPress bez konta gracza</strong></p>
                        <p>Użyj przycisku poniżej, aby automatycznie utworzyć konta graczy dla wszystkich brakujących użytkowników.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="ga-notice ga-notice--success">
                    <div class="ga-notice__icon">✅</div>
                    <div>
                        <p><strong>Wszyscy użytkownicy WordPress mają konta graczy</strong></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="ga-actions">
                <form method="post">
                    <?php wp_nonce_field('import_users'); ?>
                    <button type="submit" name="import_users" class="ga-button <?php echo $users_stats['missing'] > 0 ? 'ga-button--primary' : 'ga-button--secondary'; ?>"
                        <?php if ($users_stats['missing'] == 0): ?>
                        onclick="return confirm('Wszyscy użytkownicy są już zsynchronizowani. Sprawdzić ponownie?')"
                        <?php else: ?>
                        onclick="return confirm('Czy chcesz utworzyć konta graczy dla <?php echo $users_stats['missing']; ?> użytkowników?')"
                        <?php endif; ?>>
                        <?php echo $users_stats['missing'] > 0 ? 'Importuj brakujących graczy (' . $users_stats['missing'] . ')' : 'Sprawdź ponownie synchronizację'; ?>
                    </button>
                </form>
            </div>

            <div class="ga-notice ga-notice--info ga-mt-2">
                <div class="ga-notice__icon">ℹ️</div>
                <div>
                    <h4 class="ga-mb-1">Jak działa synchronizacja:</h4>
                    <ul>
                        <li><strong>Automatyczna</strong> - Nowi użytkownicy WordPress automatycznie otrzymują konto gracza przy rejestracji</li>
                        <li><strong>Manualna</strong> - Istniejących użytkowników można zaimportować za pomocą przycisku powyżej</li>
                        <li><strong>Domyślne wartości</strong> - Nowi gracze rozpoczynają z podstawowymi statystykami i zasobami</li>
                        <li><strong>Bezpieczne</strong> - Istniejące konta graczy nie są nadpisywane podczas importu</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>