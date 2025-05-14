/**
 * Obsługa przycisków aktualizacji statystyk postaci
 * Używa Axios do komunikacji z backendem przez REST API
 */

document.addEventListener('DOMContentLoaded', () => {
    // Znajdź wszystkie przyciski aktualizacji statystyk
    const upgradeButtons = document.querySelectorAll('.stat-upgrade-btn');

    // Dodaj obsługę kliknięcia dla każdego przycisku
    upgradeButtons.forEach(button => {
        button.addEventListener('click', handleStatUpgrade);
    });

    /**
     * Obsługa kliknięcia przycisku aktualizacji statystyki
     * @param {Event} event - Zdarzenie kliknięcia
     */
    async function handleStatUpgrade(event) {
        const button = event.currentTarget;
        const statName = button.dataset.stat;
        const statItem = button.closest('.stat-item');
        const statValueElement = statItem.querySelector('.stat-value');
        const learningPointsInfo = document.querySelector('.learning-points-info strong');

        // Wyłącz przycisk na czas trwania żądania
        button.disabled = true;
        button.classList.add('loading');

        try {
            // Ścieżka do endpointu REST API
            const restEndpoint = '/wp-json/game/v1/upgrade-stat';

            // Wyślij żądanie za pomocą Axios do REST API
            const response = await axios.post(restEndpoint, {
                stat_name: statName
            });

            // Obsłuż odpowiedź
            const data = response.data;

            if (data.success) {
                // Aktualizuj UI
                statValueElement.textContent = data.new_stat_value;

                // Aktualizuj liczbę punktów nauki
                const currentPoints = parseInt(learningPointsInfo.textContent.match(/\d+/)[0]) - 1;
                learningPointsInfo.textContent = `Dostępne punkty nauki: ${currentPoints}`;

                // Jeśli nie ma więcej punktów nauki, ukryj wszystkie przyciski
                if (currentPoints <= 0) {
                    document.querySelectorAll('.stat-upgrade-btn').forEach(btn => {
                        btn.style.display = 'none';
                    });
                }

                // Opcjonalnie: Wyświetl komunikat o powodzeniu
                if (window.showNotification) {
                    window.showNotification('success', data.message);
                } else {
                    console.log(data.message);
                }
            } else {
                // Obsłuż błąd zwrócony przez serwer
                if (window.showNotification) {
                    window.showNotification('error', data.message || 'Wystąpił błąd podczas aktualizacji statystyki.');
                } else {
                    console.error(data.message || 'Wystąpił błąd podczas aktualizacji statystyki.');
                }
            }
        } catch (error) {
            // Obsłuż błąd połączenia
            console.error('Błąd podczas komunikacji z serwerem:', error);

            if (window.showNotification) {
                window.showNotification('error', 'Wystąpił problem podczas komunikacji z serwerem. Spróbuj ponownie.');
            }
        } finally {
            // Włącz przycisk ponownie
            button.disabled = false;
            button.classList.remove('loading');
        }
    }
});