<?php if (isset($_GET['created']) && $_GET['created'] === '1') : ?>
    <div class="notice notice-success">
        <p>Tabele zostały utworzone pomyślnie!</p>
    </div>
<?php endif; ?>

<?php if (isset($_GET['recreated']) && $_GET['recreated'] === '1') : ?>
    <div class="notice notice-success">
        <p>Tabele zostały odtworzone pomyślnie!</p>
    </div>
<?php endif; ?>

<div class="wrap">
    <h1>Zarządzanie bazą danych</h1>

    <div class="postbox">
        <h2>Status tabel</h2>
        <?php if (isset($tableData) && !empty($tableData)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Tabela</th>
                        <th>Status</th>
                        <th>Opis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableData as $table): ?>
                        <tr>
                            <td><code><?php echo esc_html($table['name']); ?></code></td>
                            <td><?php echo $table['status']; // Bezpieczne, bo generowane wewnętrznie 
                                ?></td>
                            <td><?php echo esc_html($table['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Brak tabel do wyświetlenia.</p>
        <?php endif; ?>
    </div>

    <div class="postbox">
        <h2>Akcje</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="game_create_tables">
            <?php wp_nonce_field('game_create_tables'); ?>
            <p>
                <input type="submit" class="button button-primary" value="Utwórz wszystkie tabele"
                    onclick="return confirm('Czy na pewno chcesz utworzyć tabele?');">
            </p>
            <p class="description">
                Utworzy wszystkie tabele gry jeśli nie istnieją. Bezpieczne do ponownego uruchomienia.
            </p>
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="game_recreate_tables">
            <?php wp_nonce_field('game_recreate_tables'); ?>
            <p>
                <input type="submit" class="button button-secondary" value="Usuń i utwórz tabele ponownie"
                    onclick="return confirm('UWAGA: To usunie wszystkie dane! Czy na pewno chcesz kontynuować?');"
                    style="background-color: #dc3232; border-color: #dc3232; color: white;">
            </p>
            <p class="description" style="color: #dc3232;">
                <strong>UWAGA:</strong> To usunie wszystkie istniejące tabele i utworzy je od nowa. Wszystkie dane zostaną utracone!
            </p>
        </form>
    </div>
</div>