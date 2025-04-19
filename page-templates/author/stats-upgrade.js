/**
 * Obsługa ulepszania statystyk użytkownika
 */
document.addEventListener('DOMContentLoaded', function () {
    // Znajdujemy wszystkie przyciski ulepszenia statystyk
    const upgradeButtons = document.querySelectorAll('.stat-upgrade-btn');

    if (upgradeButtons.length === 0) {
        console.log('Nie znaleziono przycisków do ulepszania statystyk');
        return;
    }

    console.log('Znaleziono ' + upgradeButtons.length + ' przycisków do ulepszania statystyk');
    const userIdElement = document.getElementById('get-user-id');

    console.log('User ID element:', userIdElement ? 'Znaleziono' : 'Nie znaleziono');

    // Dodajemy obsługę kliknięć dla każdego przycisku
    upgradeButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            const stat = this.getAttribute('data-stat');

            // Pobieramy ID użytkownika z nagłówka z id 'get-user-id' (atrybut data-id)
            let userId = '';
            if (userIdElement) {
                userId = userIdElement.getAttribute('data-id');
            } else if (userIdElement) {
                userId = userIdElement.value;
            }

            const statItem = this.closest('.stat-item');

            console.log('Kliknięto przycisk dla statystyki:', stat);
            console.log('User ID:', userId);

            // Wyłączamy przycisk podczas ładowania
            this.disabled = true;
            this.classList.add('loading');

            // Przygotowujemy dane do wysłania
            const data = {
                action: 'upgrade_user_stat',
                stat: stat,
                nonce: global.dataManagerNonce,
                user_id: userId
            };

            console.log('Wysyłane dane:', data);
            console.log('URL do AJAX:', global.ajaxurl);

            // Wysyłamy żądanie AJAX używając AjaxHelper
            AjaxHelper.sendRequest(global.ajaxurl, 'POST', data)
                .then(response => {
                    console.log('Odpowiedź AJAX:', response);

                    // Aktualizujemy wartość statystyki
                    const statValueElement = statItem.querySelector('.stat-value');
                    if (statValueElement) {
                        statValueElement.textContent = response.data.new_value;
                    }

                    // Aktualizujemy liczbę punktów nauki w elemencie learning-points-info
                    const learningPointsInfo = document.querySelector('.learning-points-info');
                    if (learningPointsInfo) {
                        const remainingPoints = response.data.remaining_points;
                        learningPointsInfo.innerHTML = '<strong>Dostępne punkty nauki:</strong> ' + remainingPoints;
                    }

                    // Jeśli nie ma już punktów nauki, ukrywamy wszystkie przyciski
                    if (response.data.remaining_points <= 0) {
                        document.querySelectorAll('.stat-upgrade-btn').forEach(btn => {
                            btn.style.display = 'none';
                        });
                    }

                    // Powiadomienie o sukcesie
                    showPopup('Statystyka została ulepszona!', 'success');
                })
                .catch(error => {
                    console.error('Błąd AJAX:', error);
                    showPopup('Wystąpił błąd podczas ulepszania statystyki: ' + error, 'error');
                })
                .finally(() => {
                    // Włączamy przycisk po zakończeniu
                    this.disabled = false;
                    this.classList.remove('loading');
                });
        });
    });
});
