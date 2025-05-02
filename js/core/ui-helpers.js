/**
 * UIHelpers - moduł pomocniczy do obsługi elementów interfejsu użytkownika
 * 
 * Ten moduł zawiera funkcje do obsługi dynamicznych elementów UI:
 * - pasków życia, energii, doświadczenia itp.
 * - nawigacji zakładkowej (tab-navigation)
 * - powiadomień
 * - alertów
 * - dialogów
 */

class UIHelpers {
    /**
     * Inicjalizuje wszystkie elementy UI
     */
    static initialize() {
        // Inicjalizuj wszystkie paski statusu (życie, energia, itd.)
        UIHelpers.initializeStatusBars();

        // Inicjalizuj system zakładek (wszystkie rodzaje)
        UIHelpers.initializeTabs();

        // Inicjalizuj przyciski ulepszania statystyk
        UIHelpers.initializeStatUpgradeButtons();

        // Inicjalizuj dialogi NPC
        // UIHelpers.al();

        // Nasłuchuj na zdarzenia, które mogą wymagać aktualizacji pasków
        document.addEventListener('statsUpdated', UIHelpers.updateStatusBars);
    }

    /**
     * Inicjalizuje wszystkie paski statusu na stronie
     */
    static initializeStatusBars() {
        document.querySelectorAll('.bar-game').forEach(wrapper => {
            // Pobierz dane z atrybutów data
            const max = parseFloat(wrapper.dataset.barMax) || 100;
            const current = parseFloat(wrapper.dataset.barCurrent) || 0;
            const color = wrapper.dataset.barColor || '#4caf50';
            const type = wrapper.dataset.barType || 'default';

            // Oblicz procent wypełnienia
            const percentage = Math.min(100, Math.max(0, (current / max) * 100));

            // Wyczyść zawartość wrappera
            wrapper.innerHTML = '';

            // Utwórz element paska
            const bar = document.createElement('div');
            bar.classList.add('bar');
            bar.style.width = percentage + '%';
            bar.style.background = color;

            // Utwórz element tekstu z wartościami
            const barValue = document.createElement('div');
            barValue.classList.add('bar-value');
            barValue.innerHTML = `<span class="ud-stats-${type}">${current}</span> / ${max}`;

            // Dodaj elementy do wrappera
            wrapper.appendChild(bar);
            wrapper.appendChild(barValue);
        });
    }

    /**
     * Aktualizuje wszystkie paski statusu po zmianie statystyk
     */
    static updateStatusBars() {
        UIHelpers.initializeStatusBars();
    }

    /**
     * Aktualizuje pasek statusu o określonym typie
     * @param {string} type - Typ paska (np. 'life', 'energy')
     * @param {number} current - Aktualna wartość
     * @param {number} max - Maksymalna wartość
     */
    static updateStatusBar(type, current, max) {
        document.querySelectorAll(`.bar-game[data-bar-type="${type}"]`).forEach(wrapper => {
            // Zapisz nowe wartości w atrybutach data
            wrapper.dataset.barCurrent = current;
            wrapper.dataset.barMax = max;

            // Oblicz procent wypełnienia
            const percentage = Math.min(100, Math.max(0, (current / max) * 100));

            // Aktualizuj szerokość paska
            const bar = wrapper.querySelector('.bar');
            if (bar) {
                bar.style.width = percentage + '%';
            }

            // Aktualizuj tekst z wartością
            const barValue = wrapper.querySelector('.bar-value');
            if (barValue) {
                barValue.innerHTML = `<span class="ud-stats-${type}">${current}</span> / ${max}`;
            }
        });
    }

    /**
     * Inicjalizuje wszystkie systemy zakładek na stronie
     * Obsługuje różne typy systemów zakładkowych używanych w aplikacji
     */
    static initializeTabs() {
        // Obsługa zakładek typu author (panel użytkownika)
        const tabItems = document.querySelectorAll('.tab-item');
        const tabPanes = document.querySelectorAll('.tab-pane');

        if (tabItems.length > 0 && tabPanes.length > 0) {
            tabItems.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    // Usunięcie klasy active ze wszystkich zakładek
                    tabItems.forEach(function (item) {
                        item.classList.remove('active');
                    });

                    // Dodanie klasy active do klikniętej zakładki
                    this.classList.add('active');

                    // Ukrycie wszystkich paneli zawartości
                    tabPanes.forEach(function (pane) {
                        pane.classList.remove('active');
                    });

                    // Pokazanie odpowiedniego panelu zawartości
                    const tabId = this.getAttribute('data-tab');
                    const targetPane = document.getElementById(tabId);
                    if (targetPane) {
                        targetPane.classList.add('active');
                    }
                });
            });

            // Aktywuj pierwszą zakładkę, jeśli żadna nie jest aktywna
            if (!document.querySelector('.tab-item.active') && tabItems[0]) {
                tabItems[0].click();
            }
        }

        // Obsługa zakładek typu zadania (podstrona zadań)
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanels = document.querySelectorAll('.tab-panel');

        if (tabBtns.length > 0 && tabPanels.length > 0) {
            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Usuń klasę active ze wszystkich przycisków
                    tabBtns.forEach(b => b.classList.remove('active'));

                    // Dodaj klasę active do klikniętego przycisku
                    btn.classList.add('active');

                    // Ukryj wszystkie panele
                    tabPanels.forEach(panel => panel.classList.remove('active'));

                    // Pokaż panel powiązany z klikniętym przyciskiem
                    const tabId = btn.getAttribute('data-tab') + '-tab';
                    const targetPanel = document.getElementById(tabId);
                    if (targetPanel) {
                        targetPanel.classList.add('active');
                    }
                });
            });

            // Aktywuj pierwszą zakładkę, jeśli żadna nie jest aktywna
            if (!document.querySelector('.tab-btn.active') && tabBtns[0]) {
                tabBtns[0].click();
            }
        }
    }

    /**
     * Inicjalizuje przyciski ulepszania statystyk użytkownika
     * Obsługuje funkcjonalność przycisków z klasą .stat-upgrade-btn
     */
    static initializeStatUpgradeButtons() {
        // Znajdujemy wszystkie przyciski ulepszenia statystyk
        const upgradeButtons = document.querySelectorAll('.stat-upgrade-btn');

        if (upgradeButtons.length === 0) {
            console.log('Nie znaleziono przycisków do ulepszania statystyk');
            return;
        }

        console.log('Znaleziono ' + upgradeButtons.length + ' przycisków do ulepszania statystyk');
        const userIdElement = document.getElementById('get-user-id');

        // Dodajemy obsługę kliknięć dla każdego przycisku
        upgradeButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const stat = this.getAttribute('data-stat');

                // Pobieramy ID użytkownika z nagłówka z id 'get-user-id' (atrybut data-id)
                let userId = '';
                if (userIdElement) {
                    userId = userIdElement.getAttribute('data-id');
                }

                const statItem = this.closest('.stat-item');

                console.log('Kliknięto przycisk dla statystyki:', stat);
                console.log('User ID:', userId);

                // Wyłączamy przycisk podczas ładowania
                this.disabled = true;
                this.classList.add('loading');

                // Przygotowujemy dane do wysłania
                const data = {
                    stat: stat,
                    user_id: userId
                };

                console.log('Wysyłane dane:', data);

                // Wysyłamy żądanie za pomocą Axios do REST API
                axios({
                    method: 'POST',
                    url: '/wp-json/game/v1/user/upgrade-stat',
                    data: data
                })
                    .then(response => {
                        console.log('Odpowiedź API:', response);

                        // Przetwarzamy odpowiedź API
                        const apiData = response.data.data;

                        // Aktualizujemy wartość statystyki
                        const statValueElement = statItem.querySelector('.stat-value');
                        if (statValueElement) {
                            statValueElement.textContent = apiData.new_value;
                        }

                        // Aktualizujemy liczbę punktów nauki w elemencie learning-points-info
                        const learningPointsInfo = document.querySelector('.learning-points-info');
                        if (learningPointsInfo) {
                            const remainingPoints = apiData.remaining_points;
                            learningPointsInfo.innerHTML = '<strong>Dostępne punkty nauki:</strong> ' + remainingPoints;
                        }

                        // Jeśli nie ma już punktów nauki, ukrywamy wszystkie przyciski
                        if (apiData.remaining_points <= 0) {
                            document.querySelectorAll('.stat-upgrade-btn').forEach(btn => {
                                btn.style.display = 'none';
                            });
                        }

                        // Powiadomienie o sukcesie, jeśli jest dostępny system powiadomień
                        if (typeof showPopup === 'function') {
                            showPopup('Statystyka została ulepszona!', 'success');
                        } else if (typeof Notifications !== 'undefined' && typeof Notifications.show === 'function') {
                            Notifications.show('Statystyka została ulepszona!', 'success');
                        }
                    })
                    .catch(error => {
                        console.error('Błąd API:', error);
                        let errorMessage = 'Wystąpił błąd podczas ulepszania statystyki';

                        // Próba wyciągnięcia szczegółów błędu z odpowiedzi API
                        if (error.response && error.response.data && error.response.data.error) {
                            errorMessage += ': ' + error.response.data.error;
                        }

                        // Powiadomienie o błędzie, jeśli jest dostępny system powiadomień
                        if (typeof showPopup === 'function') {
                            showPopup(errorMessage, 'error');
                        } else if (typeof Notifications !== 'undefined' && typeof Notifications.show === 'function') {
                            Notifications.show(errorMessage, 'error');
                        }
                    })
                    .finally(() => {
                        // Włączamy przycisk po zakończeniu
                        this.disabled = false;
                        this.classList.remove('loading');
                    });
            });
        });
    }
}

// Eksportuj klasę do globalnego obiektu window
window.UIHelpers = UIHelpers;

// Zainicjuj UI gdy dokument jest załadowany
document.addEventListener('DOMContentLoaded', function () {
    UIHelpers.initialize();
});
