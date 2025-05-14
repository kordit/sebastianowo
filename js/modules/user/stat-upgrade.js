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
        const learningPointsInfo = document.querySelector('.learning-points-info');

        // Wyłącz przycisk na czas trwania żądania
        button.disabled = true;
        button.classList.add('loading');

        try {
            // Ścieżka do endpointu REST API
            const restEndpoint = '/wp-json/game/v1/upgrade-stat';

            // Użyj funkcji pomocniczej do zapytania API
            const response = await postToApi(restEndpoint, {
                stat_name: statName
            });

            // Obsłuż odpowiedź
            const data = response.data;

            if (data.success) {
                // Aktualizuj UI
                statValueElement.textContent = data.new_stat_value;

                // Aktualizuj liczbę punktów nauki - szukaj liczby w całym tekście elementu .learning-points-info
                const pointsText = learningPointsInfo.textContent;
                const pointsMatch = pointsText.match(/\d+/);
                
                if (pointsMatch) {
                    const currentPoints = parseInt(pointsMatch[0]) - 1;
                    
                    // Aktualizuj widok punktów nauki - bardziej niezawodna metoda
                    // Całkowicie przebuduj zawartość
                    learningPointsInfo.innerHTML = `<strong>Dostępne punkty nauki:</strong> ${currentPoints}`;

                    // Jeśli nie ma więcej punktów nauki, ukryj wszystkie przyciski
                    if (currentPoints <= 0) {
                        document.querySelectorAll('.stat-upgrade-btn').forEach(btn => {
                            btn.style.display = 'none';
                        });
                    }
                }

                // Wyświetl komunikat o powodzeniu używając systemu powiadomień gry
                if (window.gameNotifications) {
                    window.gameNotifications.show(data.message, 'success');
                } else {
                    console.log(data.message);
                }
            } else {
                // Obsłuż błąd zwrócony przez serwer
                const errorMessage = data.message || 'Wystąpił błąd podczas aktualizacji statystyki.';
                if (window.gameNotifications) {
                    window.gameNotifications.show(errorMessage, 'failed');
                } else {
                    console.error(errorMessage);
                    alert(errorMessage);
                }
            }
        } catch (error) {
            // Obsłuż błąd połączenia
            console.error('Błąd podczas komunikacji z serwerem:', error);

            // Użyj globalnego systemu powiadomień, jeśli jest dostępny
            if (window.gameNotifications) {
                window.gameNotifications.show(error.message || 'Wystąpił problem podczas komunikacji z serwerem.', 'failed');
            } else {
                alert(error.message || 'Wystąpił problem podczas komunikacji z serwerem. Spróbuj ponownie.');
            }

            // Automatycznie przeładuj stronę w przypadku błędu autoryzacji po 2 sekundach
            if (error.message && error.message.includes('autoryzacji')) {
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } finally {
            // Włącz przycisk ponownie
            button.disabled = false;
            button.classList.remove('loading');
        }
    }
});