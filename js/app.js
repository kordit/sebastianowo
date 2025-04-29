/**
 * Główny plik aplikacji - punkt wejścia do aplikacji
 * 
 * Ten plik inicjalizuje wszystkie potrzebne moduły i zapewnia globalne funkcje.
 */

// Globalny obiekt aplikacji
const GameApp = {
    /**
     * Inicjalizacja całej aplikacji
     */
    init() {
        console.log('Inicjalizacja aplikacji...');

        // Inicjalizuj system powiadomień
        if (typeof window.gameNotifications === 'undefined') {
            // Utwórz instancję systemu powiadomień jeśli nie istnieje
            window.gameNotifications = new NotificationSystem({
                position: 'top-right',
                duration: 5000,
                maxNotifications: 5
            });
        }

        // Ustaw globalną zmienną ajaxurl jeśli nie jest ustawiona
        if (typeof window.ajaxurl === 'undefined' && typeof gameData !== 'undefined') {
            window.ajaxurl = gameData.ajaxurl;
        }

        // Inicjalizuj obserwatory zdarzeń
        this.setupEventListeners();

        console.log('Aplikacja zainicjalizowana!');
    },

    /**
     * Konfiguracja nasłuchiwaczy zdarzeń
     */
    setupEventListeners() {
        // Obsługuj kliknięcia w elementy interaktywne
        document.addEventListener('click', (e) => {
            // Obsługa elementów z data-action
            const actionElement = e.target.closest('[data-action]');
            if (actionElement) {
                const action = actionElement.dataset.action;

                // Wykonaj odpowiednią akcję
                switch (action) {
                    case 'toggle-menu':
                        // Obsługa menu
                        break;
                    // Inne akcje
                }
            }
        });
    }
};

/**
 * Funkcja do pobierania danych strony
 * @returns {Object} Dane strony
 */
function getPageData() {
    // Pobieranie danych strony na podstawie parametrów URL, atrybutów danych, itp.
    const params = new URLSearchParams(window.location.search);

    const pageData = {
        scena: params.get('scena') || '',
        mission: params.get('mission') || '',
        instation: params.get('instation') || ''
    };

    // Dołącz dodatkowe dane, jeśli są dostępne
    const dataElements = document.querySelectorAll('[data-page-info]');
    dataElements.forEach(el => {
        try {
            const info = JSON.parse(el.dataset.pageInfo);
            Object.assign(pageData, info);
        } catch (e) {
            console.error('Błąd parsowania danych strony:', e);
        }
    });

    return pageData;
}

/**
 * Funkcja do uruchamiania funkcji NPC
 * @param {Array} functionsList - Lista funkcji do wykonania
 */
function runFunctionNPC(functionsList) {
    if (!functionsList || !Array.isArray(functionsList)) return;

    functionsList.forEach(functionData => {
        if (functionData.do_function) {
            // Wysyłamy dane przez AJAX
            AjaxHelper.sendRequest(
                window.ajaxurl || '/wp-admin/admin-ajax.php',
                'POST',
                {
                    action: functionData.do_function,
                    ...functionData
                }
            ).then(response => {
                if (response.success && response.data?.message) {
                    showPopup(response.data.message, response.data.status || 'success');
                }
            }).catch(error => {
                console.error('Błąd wykonania funkcji NPC:', error);
                showPopup('Wystąpił błąd: ' + error, 'error');
            });
        }
    });
}

// Eksport globalnych funkcji dla wstecznej kompatybilności
window.getPageData = getPageData;
window.runFunctionNPC = runFunctionNPC;

// Zainicjuj aplikację po załadowaniu dokumentu
document.addEventListener('DOMContentLoaded', () => {
    GameApp.init();
});
