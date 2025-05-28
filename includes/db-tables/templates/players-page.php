<div class="wrap">
    <h1>Zarządzanie graczami</h1>

    <?php
    /**
     * Główny szablon strony zarządzania graczami.
     *
     * Dostępne zmienne:
     * $selectedUserId (int) - ID wybranego użytkownika do edycji, lub 0 jeśli wyświetlana jest lista.
     * $userRepo (GameUserRepository) - Repozytorium użytkowników gry.
     * $gameAdminPanel (GameAdminPanel) - Instancja głównej klasy panelu admina.
     * $dataBuilder (GameDataBuilder) - Instancja klasy budującej dane.
     * $templateData (array) - Tablica z dodatkowymi danymi przekazanymi z GameAdminPanel::renderPlayersPage().
     */

    if ($selectedUserId > 0) {
        // Przygotuj dane dla edytora gracza
        // Metoda renderPlayerEditor w GameAdminPanel przygotuje dane w $templateData
        $gameAdminPanel->renderPlayerEditor($selectedUserId, $templateData);
        include __DIR__ . '/player-editor.php';
    } else {
        // Przygotuj dane dla tabeli graczy
        // Metoda renderPlayersTable w GameAdminPanel przygotuje dane w $templateData
        $gameAdminPanel->renderPlayersTable($templateData);
        include __DIR__ . '/players-table.php';
    }
    ?>
</div>