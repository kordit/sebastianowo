/**
 * Moduł zarządzający postacią gracza
 * Obsługuje operacje związane z danymi postaci, aktualizacją statystyk i punktów nauki
 */

class CharacterManager {
    /**
     * Inicjalizacja menedżera postaci
     */
    static initialize() {
        // Inicjalizacja przycisków aktualizacji statystyk
        CharacterManager.initializeStatButtons();

        // Eksportuj funkcje do globalnego użycia
        window.refreshCharacterData = CharacterManager.refreshCharacterData;
        window.updateCharacterUI = CharacterManager.updateCharacterUI;

        // Nasłuchuj na zdarzenia aktualizacji statystyk
        document.addEventListener('statsUpdated', CharacterManager.refreshCharacterData);
    }

    /**
     * Inicjalizacja przycisków zwiększających statystyki
     */
    static initializeStatButtons() {
        const upgradeButtons = document.querySelectorAll('.stat-upgrade-btn');

        upgradeButtons.forEach(button => {
            button.addEventListener('click', async function (e) {
                const statName = this.dataset.stat;

                try {
                    // Najpierw sprawdź czy mamy dostępne punkty nauki
                    const learningPointsInfo = document.querySelector('.learning-points-info');
                    if (!learningPointsInfo) {
                        console.error('Nie można znaleźć elementu z punktami nauki');
                        CharacterManager.showNotification('Błąd', 'Nie można znaleźć informacji o punktach nauki', 'error');
                        return;
                    }

                    // Pobranie całego tekstu z elementu h3 i wyciągnięcie liczby
                    const learningPointsText = learningPointsInfo.textContent.trim();
                    const learningPointsMatch = learningPointsText.match(/Dostępne punkty nauki:\s*(\d+)/);
                    const learningPoints = learningPointsMatch ? parseInt(learningPointsMatch[1]) : 0;

                    if (isNaN(learningPoints) || learningPoints <= 0) {
                        console.error('Brak dostępnych punktów nauki');
                        CharacterManager.showNotification('Brak punktów', 'Nie masz dostępnych punktów nauki', 'warning');
                        return;
                    }

                    // Użyj API UserManager do zwiększenia statystyki
                    if (typeof UserManager !== 'undefined' && typeof UserManager.updateStat === 'function') {
                        const result = await UserManager.updateStat(statName, 1);

                        if (result.success) {
                            // Po sukcesie odjąć punkt nauki
                            const learningResult = await UserManager.updateProgress('learning_points', -1);

                            if (learningResult.success) {
                                // Odśwież dane postaci po obu operacjach
                                await CharacterManager.refreshCharacterData();

                                // Pokaż powiadomienie o sukcesie
                                const statLabel = document.querySelector(`.stat-item[data-stat="${statName}"] .stat-label`);
                                const statName_pl = statLabel ? statLabel.textContent.trim().replace(':', '').split('?')[0].trim() : statName;
                                CharacterManager.showNotification('Sukces', `Twoja ${statName_pl} wzrosła o 1`, 'success');
                            } else {
                                console.error('Błąd aktualizacji punktów nauki:', learningResult.message);
                                CharacterManager.showNotification('Błąd', `Nie udało się zaktualizować punktów nauki: ${learningResult.message}`, 'error');
                            }
                        } else {
                            console.error('Błąd aktualizacji statystyki:', result.message);
                            CharacterManager.showNotification('Błąd', `Nie udało się zwiększyć statystyki: ${result.message}`, 'error');
                        }
                    }
                } catch (error) {
                    console.error('Błąd podczas zwiększania statystyki:', error);
                }
            });
        });
    }

    /**
     * Pobranie aktualnych danych postaci i aktualizacja UI
     * 
     * @returns {Promise<Object>} - Dane postaci z serwera
     */
    static async refreshCharacterData() {
        try {
            // Korzystamy z REST API WordPressa do pobrania danych użytkownika
            const response = await axios.get(
                `${userManagerData.restUrl}game/v1/get-user-data`,
                {
                    headers: {
                        'X-WP-Nonce': userManagerData.nonce,
                        'Content-Type': 'application/json'
                    }
                }
            );

            if (response.data && response.data.success) {
                // Aktualizujemy UI postaci
                CharacterManager.updateCharacterUI(response.data.data);
                return response.data.data;
            } else {
                throw new Error(response.data.message || 'Brak danych z serwera');
            }
        } catch (error) {
            console.error('Błąd podczas odświeżania danych postaci:', error);
            throw error;
        }
    }

    /**
     * Aktualizacja interfejsu postaci z nowymi danymi
     * 
     * @param {Object} userData - Dane użytkownika z API
     */
    static updateCharacterUI(userData) {
        if (!userData) return;

        // Aktualizuj punkty nauki
        CharacterManager.updateLearningPoints(userData);

        // Aktualizuj statystyki podstawowe
        CharacterManager.updateStats(userData);

        // Aktualizuj statystyki witalności
        CharacterManager.updateVitality(userData);

        // Aktualizuj umiejętności
        CharacterManager.updateSkills(userData);
    }

    /**
     * Aktualizacja punktów nauki
     * 
     * @param {Object} userData - Dane użytkownika
     */
    static updateLearningPoints(userData) {
        if (!userData.progress || userData.progress.learning_points === undefined) return;

        const learningPointsInfo = document.querySelector('.learning-points-info');
        if (learningPointsInfo) {
            // Aktualizuj bezpośrednio HTML elementu, usuwając istniejącą zawartość
            const learningPointsStrong = learningPointsInfo.querySelector('strong');

            if (learningPointsStrong) {
                // Zachowaj tylko nagłówek "Dostępne punkty nauki:" wewnątrz tagu strong
                learningPointsStrong.textContent = 'Dostępne punkty nauki:';

                // Usuń wszystkie elementy po strong
                let nextSibling = learningPointsStrong.nextSibling;
                while (nextSibling) {
                    const current = nextSibling;
                    nextSibling = nextSibling.nextSibling;
                    learningPointsInfo.removeChild(current);
                }

                // Dodaj nowy element span z wartością
                const pointsSpan = document.createElement('span');
                pointsSpan.classList.add('points-value');
                pointsSpan.textContent = ' ' + userData.progress.learning_points;
                learningPointsInfo.appendChild(pointsSpan);

                // Efekt wizualny aktualizacji
                learningPointsInfo.classList.add('resource-updated');
                setTimeout(() => {
                    learningPointsInfo.classList.remove('resource-updated');
                }, 1000);
            }
        }

        // Aktualizacja widoczności przycisków zwiększających statystyki
        CharacterManager.updateStatButtonsVisibility(userData.progress.learning_points);
    }

    /**
     * Aktualizacja podstawowych statystyk
     * 
     * @param {Object} userData - Dane użytkownika
     */
    static updateStats(userData) {
        if (!userData.stats) return;

        const stats = userData.stats;

        // Znajdź wszystkie elementy statystyk
        document.querySelectorAll('.stat-item[data-stat]').forEach(statItem => {
            const statName = statItem.dataset.stat;
            const statValueElement = statItem.querySelector('.stat-value');

            // Jeśli znaleziono element i mamy wartość, aktualizujemy
            if (statValueElement && stats[statName] !== undefined) {
                statValueElement.textContent = stats[statName];

                // Efekt wizualny aktualizacji
                statItem.classList.add('resource-updated');
                setTimeout(() => {
                    statItem.classList.remove('resource-updated');
                }, 1000);
            }
        });
    }

    /**
     * Aktualizacja statystyk witalności
     * 
     * @param {Object} userData - Dane użytkownika
     */
    static updateVitality(userData) {
        if (!userData.vitality) return;

        const vitality = userData.vitality;

        // Znajdź wszystkie sekcje statystyk
        const statsSections = document.querySelectorAll('.stats-section');

        // Przeszukaj wszystkie sekcje, aby znaleźć tę z witalnością
        let vitalityItems = null;
        for (const section of statsSections) {
            const header = section.querySelector('h3');
            if (header && header.textContent.includes('Witalność')) {
                vitalityItems = section.querySelectorAll('.stat-item');
                break;
            }
        }

        if (vitalityItems) {
            vitalityItems.forEach(item => {
                const labelElement = item.querySelector('.stat-label');
                if (!labelElement) return;

                const label = labelElement.textContent.trim().replace(':', '').toLowerCase();
                const statValueElement = item.querySelector('.stat-value');

                // Mapowanie nazw na klucze
                let vitalityKey = null;
                if (label.includes('życie') && !label.includes('maks')) vitalityKey = 'life';
                else if (label.includes('maks') && label.includes('życie')) vitalityKey = 'max_life';
                else if (label.includes('energia') && !label.includes('maks')) vitalityKey = 'energy';
                else if (label.includes('maks') && label.includes('energia')) vitalityKey = 'max_energy';

                // Jeśli znaleziono element i mamy wartość, aktualizujemy
                if (statValueElement && vitalityKey && vitality[vitalityKey] !== undefined) {
                    statValueElement.textContent = vitality[vitalityKey];

                    // Efekt wizualny aktualizacji
                    item.classList.add('resource-updated');
                    setTimeout(() => {
                        item.classList.remove('resource-updated');
                    }, 1000);
                }
            });
        }
    }

    /**
     * Aktualizacja umiejętności
     * 
     * @param {Object} userData - Dane użytkownika
     */
    static updateSkills(userData) {
        if (!userData.skills) return;

        const skills = userData.skills;

        // Znajdź sekcję umiejętności
        const skillsPane = document.querySelector('#umiejetnosci');
        if (!skillsPane) return;

        const skillItems = skillsPane.querySelectorAll('.stat-item');

        skillItems.forEach(item => {
            const labelElement = item.querySelector('.stat-label');
            if (!labelElement) return;

            const label = labelElement.textContent.trim().replace(':', '').toLowerCase();
            const statValueElement = item.querySelector('.stat-value');

            // Mapowanie nazw na klucze
            let skillKey = null;
            if (label.includes('walka')) skillKey = 'combat';
            else if (label.includes('kradzie')) skillKey = 'steal';
            else if (label.includes('przetrwa')) skillKey = 'craft';
            else if (label.includes('handl')) skillKey = 'trade';
            else if (label.includes('relac')) skillKey = 'relations';
            else if (label.includes('ulica')) skillKey = 'street';

            // Jeśli znaleziono element i mamy wartość, aktualizujemy
            if (statValueElement && skillKey && skills[skillKey] !== undefined) {
                statValueElement.textContent = skills[skillKey];

                // Efekt wizualny aktualizacji
                item.classList.add('resource-updated');
                setTimeout(() => {
                    item.classList.remove('resource-updated');
                }, 1000);
            }
        });
    }

    /**
     * Aktualizacja widoczności przycisków zwiększających statystyki
     * na podstawie dostępnych punktów nauki
     * 
     * @param {number} learningPoints - Liczba dostępnych punktów nauki
     */
    static updateStatButtonsVisibility(learningPoints) {
        const upgradeButtons = document.querySelectorAll('.stat-upgrade-btn');

        upgradeButtons.forEach(button => {
            if (learningPoints <= 0) {
                // Ukryj przyciski, jeśli nie ma punktów nauki
                button.style.display = 'none';
            } else {
                // Pokaż przyciski, jeśli są punkty nauki
                button.style.display = '';
            }
        });
    }

    /**
     * Wyświetla powiadomienie dla użytkownika
     * 
     * @param {string} title - Tytuł powiadomienia
     * @param {string} message - Treść powiadomienia
     * @param {string} type - Typ powiadomienia ('success', 'error', 'warning', 'info')
     */
    static showNotification(title, message, type = 'info') {
        // Mapowanie typów powiadomień z CharacterManager na typy NotificationSystem
        const notificationTypes = {
            'success': 'success',
            'error': 'failed',
            'warning': 'bad',
            'info': 'neutral'
        };

        // Formatowanie treści z tytułem
        const formattedMessage = `<strong>${title}</strong><br>${message}`;

        // Sprawdź czy globalny system powiadomień jest dostępny
        if (window.gameNotifications && typeof window.gameNotifications.show === 'function') {
            window.gameNotifications.show(
                formattedMessage,
                notificationTypes[type] || 'neutral'
            );
        } else {
            // Fallback jeśli system powiadomień nie jest dostępny
            console.log(`${title}: ${message} (${type})`);

            // Próbujemy utworzyć tymczasowe powiadomienie
            try {
                if (typeof NotificationSystem === 'function') {
                    const tempNotifier = new NotificationSystem();
                    tempNotifier.show(formattedMessage, notificationTypes[type] || 'neutral');
                }
            } catch (e) {
                console.warn('Nie można utworzyć powiadomienia:', e);
            }
        }
    }
}

// Inicjalizacja menedżera postaci po załadowaniu dokumentu
document.addEventListener('DOMContentLoaded', () => {
    CharacterManager.initialize();
});
