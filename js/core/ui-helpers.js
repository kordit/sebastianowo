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

        // Inicjalizuj system aktualizacji interfejsu użytkownika
        UIHelpers.initializeUIUpdater();

        // Inicjalizuj dialogi NPC
        // UIHelpers.al();

        // Nasłuchuj na zdarzenia, które mogą wymagać aktualizacji pasków
        document.addEventListener('statsUpdated', UIHelpers.updateStatusBars);
        document.addEventListener('userDataUpdated', (event) => {
            // Możemy automatycznie aktualizować paski statusu gdy dane użytkownika się zmieniają
            if (event.detail && event.detail.vitality) {
                const vitality = event.detail.vitality;
                UIHelpers.updateStatusBar('life', vitality.life, vitality.max_life);
                UIHelpers.updateStatusBar('energy', vitality.energy, vitality.max_energy);
            }
        });
    }

    /**
     * Wyświetla powiadomienie używając systemu powiadomień
     * 
     * @param {String} message - Treść powiadomienia
     * @param {String} type - Typ powiadomienia (success, error, info, warn)
     */
    static showNotification(message, type = 'info') {
        // Mapowanie typów na system powiadomień
        const typeMap = {
            'success': 'success',
            'error': 'failed',
            'info': 'neutral',
            'warn': 'bad'
        };

        const status = typeMap[type] || 'neutral';

        if (window.gameNotifications) {
            window.gameNotifications.show(message, status);
        } else if (window.showPopup) {
            window.showPopup(message, type);
        } else {
            console.log(`Powiadomienie (${type}): ${message}`);
        }
    }

    /**
     * Pobiera dane o aktualnej stronie na podstawie URL i klas dokumentu
     * 
     * @returns {Object} - Obiekt zawierający typ strony i jej wartość
     */
    static getPageData() {
        const body = document.body;
        let pageData = {};

        // Pobierz aktualny URL i segmenty ścieżki
        const url = new URL(window.location.href);
        const pathSegments = url.pathname.split('/').filter(segment => segment); // Usunięcie pustych wartości
        const segmentCount = pathSegments.length;

        let lastSegment = pathSegments[segmentCount - 1] || ''; // Ostatni segment (domyślnie)

        // 1️⃣ Jeśli body ma klasę zaczynającą się od 'template-', to jest to 'instance' (zwraca normalnie)
        const templateClass = [...body.classList].find(cls => cls.startsWith('template-'));
        if (templateClass) {
            pageData = {
                TypePage: 'instance',
                value: lastSegment // ✅ Pobranie ostatniego segmentu URL
            };
        }
        // 2️⃣ Jeśli body ma klasę 'single', to jest to 'scene' (musi zwracać kolejną logikę)
        else if (body.classList.contains('single')) {
            if (segmentCount === 2) {
                pageData = {
                    TypePage: 'scena',
                    value: `${pathSegments[1]}/main` // ✅ Format: "kolejowa/main"
                };
            } else if (segmentCount >= 3) {
                pageData = {
                    TypePage: 'scena',
                    value: `${pathSegments[1]}/${lastSegment}` // ✅ Format: "kolejowa/klatka"
                };
            }
        }

        return pageData;
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
     * Inicjalizuje system aktualizacji danych interfejsu użytkownika
     */
    static initializeUIUpdater() {
        const flushButton = document.querySelector('#flush-ui-button');

        // Obsługa przycisku odświeżania
        if (flushButton) {
            flushButton.addEventListener('click', async () => {
                try {
                    const data = await UIHelpers.refreshUserData();
                    console.log('Interfejs został odświeżony z danymi:', data);
                } catch (error) {
                    console.error('Błąd odświeżania UI:', error);
                }
            });
        }

        // Eksportujemy funkcje do globalnego użycia (dla konsoli)
        window.refreshUserData = UIHelpers.refreshUserData;
        window.updateUIElements = UIHelpers.updateUIElements;
    }

    /**
     * Aktualizuje wszystkie elementy interfejsu użytkownika na podstawie otrzymanych danych
     * 
     * @param {Object} userData - Dane użytkownika z API
     */
    static updateUIElements(userData) {
        if (!userData) return;

        // Aktualizacja zasobów w nagłówku (hajs, szlugi, etc.)
        if (userData.backpack) {
            UIHelpers.updateResourcesUI(userData);
        }

        // Aktualizacja pasków witalności (życie, energia)
        if (userData.vitality) {
            UIHelpers.updateSidebarUI(userData);
        }

        // Próba aktualizacji statystyk postaci (jeśli strona zawiera panel postaci)
        if (window.CharacterManager && typeof CharacterManager.updateCharacterUI === 'function') {
            CharacterManager.updateCharacterUI(userData);
        }
    }

    /**
     * Aktualizuje interfejs zasobów (nagłówek) z nowymi danymi
     * 
     * @param {Object} userData - Dane użytkownika z API
     */
    static updateResourcesUI(userData) {
        if (!userData.backpack) return;

        const backpackData = userData.backpack;
        const resourceItems = document.querySelectorAll('.resource-item');

        resourceItems.forEach(item => {
            const resourceName = item.dataset.resource;
            const valueElement = item.querySelector('.resource-value');

            if (valueElement && backpackData[resourceName] !== undefined) {
                valueElement.textContent = backpackData[resourceName];
            }

            // Efekt wizualny aktualizacji
            item.classList.add('resource-updated');
            setTimeout(() => {
                item.classList.remove('resource-updated');
            }, 1000);
        });
    }

    /**
     * Aktualizuje paski statusu in sidebarze (życie, energia)
     * 
     * @param {Object} userData - Dane użytkownika z API
     */
    static updateSidebarUI(userData) {
        if (!userData || !userData.vitality) return;

        const vitality = userData.vitality;

        // Aktualizacja paska życia
        if (vitality.life !== undefined && vitality.max_life !== undefined) {
            UIHelpers.updateStatusBar('life', vitality.life, vitality.max_life);
        }

        // Aktualizacja paska energii
        if (vitality.energy !== undefined && vitality.max_energy !== undefined) {
            UIHelpers.updateStatusBar('energy', vitality.energy, vitality.max_energy);
        }

        // Dodaj efekt wizualny aktualizacji do wrappera pasków
        const barWrappers = document.querySelectorAll('.wrap-bar');
        barWrappers.forEach(wrapper => {
            wrapper.classList.add('resource-updated');
            setTimeout(() => {
                wrapper.classList.remove('resource-updated');
            }, 1000);
        });
    }

    /**
     * Pobiera aktualne dane użytkownika z serwera i odświeża wszystkie elementy interfejsu
     * 
     * @returns {Promise<Object>} - Zwraca Promise z danymi użytkownika
     */
    static async refreshUserData() {
        try {
            // Pobierz adres bazowy WordPress API
            const wpApiUrl = '/wp-json/';

            console.log('Próba odświeżenia danych użytkownika z API');

            // Korzystamy z REST API WordPressa do pobrania danych użytkownika
            const response = await axios.get(
                `${wpApiUrl}game/v1/get-user-data`,
                {
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }
            );

            if (response.data && response.data.success) {
                const userData = response.data.data;
                console.log('Otrzymano dane użytkownika:', userData);

                // Aktualizujemy wszystkie elementy interfejsu
                UIHelpers.updateUIElements(userData);

                // Wywołujemy zdarzenie aktualizacji danych użytkownika
                // Inne moduły mogą nasłuchiwać tego zdarzenia
                const event = new CustomEvent('userDataUpdated', { detail: userData });
                document.dispatchEvent(event);

                return userData;
            } else {
                throw new Error(response.data?.message || 'Brak danych z serwera');
            }
        } catch (error) {
            console.error('Błąd podczas odświeżania danych użytkownika:', error);
            throw error;
        }
    }
}

// Eksportuj klasę do globalnego obiektu window
window.UIHelpers = UIHelpers;

// Zainicjuj UI gdy dokument jest załadowany
document.addEventListener('DOMContentLoaded', function () {
    UIHelpers.initialize();
});
