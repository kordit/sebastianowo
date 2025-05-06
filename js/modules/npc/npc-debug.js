/**
 * Skrypt deweloperski NPC Debug
 * 
 * Skrypt wywołuje dwa endpointy po kliknięciu na NPC:
 * 1. Standardowy endpoint dla graczy (filtrowane dane)
 * 2. Endpoint deweloperski (pełne, niefiltrowane dane)
 * 
 * @package Game
 * @since 1.0.0
 */

(() => {
    // Poczekaj na pełne załadowanie DOM
    document.addEventListener('DOMContentLoaded', () => {
        initNpcDebugMode();
    });

    /**
     * Inicjalizuje tryb debugowania NPC
     */
    const initNpcDebugMode = () => {
        console.log('[NPC Debug] Inicjalizacja trybu debugowania NPC');

        // Nasłuchuj na zdarzenie kliknięcia na NPC
        document.addEventListener('npcClicked', handleNpcClick);

        // Dodaj przycisk debugowania do interfejsu
        addDebugButton();
    };

    /**
     * Obsługuje kliknięcie na NPC
     * @param {CustomEvent} event - Zdarzenie kliknięcia na NPC
     */
    const handleNpcClick = async (event) => {
        const { npcId, pageData, currentUrl } = event.detail;

        if (!isDebugModeActive()) return;

        try {
            // Wywołaj oba endpointy równolegle
            const [regularResponse, debugResponse] = await Promise.all([
                callRegularEndpoint(npcId, pageData, currentUrl),
                callDebugEndpoint(npcId, pageData, currentUrl)
            ]);

            // Pokaż podgląd w interfejsie
            showDebugOverlay(regularResponse, debugResponse);

        } catch (error) {
            console.error('[NPC Debug] Błąd podczas pobierania danych:', error);
        }
    };

    /**
     * Wywołuje standardowy endpoint NPC
     * @param {number} npcId - ID NPC
     * @param {Object} pageData - Dane strony
     * @param {string} currentUrl - Aktualny URL
     * @returns {Promise<Object>} - Odpowiedź z API
     */
    const callRegularEndpoint = async (npcId, pageData, currentUrl) => {
        const response = await axios({
            method: 'POST',
            url: '/wp-json/game/v1/npc/popup',
            data: {
                npc_id: npcId,
                page_data: pageData,
                current_url: currentUrl
            }
        });

        return response.data;
    };

    /**
     * Wywołuje endpoint deweloperski NPC
     * @param {number} npcId - ID NPC
     * @param {Object} pageData - Dane strony
     * @param {string} currentUrl - Aktualny URL
     * @returns {Promise<Object>} - Odpowiedź z API
     */
    const callDebugEndpoint = async (npcId, pageData, currentUrl) => {
        // Użyj Axios zamiast jQuery AJAX
        const response = await axios({
            method: 'POST',
            url: '/wp-json/game/v1/npc/debug',
            data: {
                npc_id: npcId,
                page_data: pageData,
                current_url: currentUrl
            },
            headers: {
                'X-WP-Nonce': npcDebugData?.nonce || ''
            }
        });

        return response.data;
    };

    /**
     * Sprawdza czy tryb debugowania jest aktywny
     * @returns {boolean} - Czy tryb debugowania jest aktywny
     */
    const isDebugModeActive = () => {
        return localStorage.getItem('npcDebugMode') === 'active';
    };

    /**
     * Dodaje przycisk debugowania do interfejsu
     */
    const addDebugButton = () => {
        const button = document.createElement('button');
        button.id = 'npc-debug-toggle';
        button.innerText = isDebugModeActive() ? '🐛 Tryb Debug: ON' : '🐛 Tryb Debug: OFF';
        button.style.position = 'fixed';
        button.style.bottom = '10px';
        button.style.right = '10px';
        button.style.zIndex = '9999';
        button.style.padding = '5px 10px';
        button.style.backgroundColor = isDebugModeActive() ? '#28a745' : '#6c757d';
        button.style.color = 'white';
        button.style.border = 'none';
        button.style.borderRadius = '4px';
        button.style.cursor = 'pointer';

        button.addEventListener('click', toggleDebugMode);

        document.body.appendChild(button);
    };

    /**
     * Przełącza tryb debugowania
     */
    const toggleDebugMode = () => {
        const isActive = isDebugModeActive();
        const button = document.getElementById('npc-debug-toggle');

        if (isActive) {
            localStorage.removeItem('npcDebugMode');
            button.innerText = '🐛 Tryb Debug: OFF';
            button.style.backgroundColor = '#6c757d';

            // Ukryj nakładkę debugowania, jeśli jest widoczna
            const overlay = document.getElementById('npc-debug-overlay');
            if (overlay) overlay.remove();

        } else {
            localStorage.setItem('npcDebugMode', 'active');
            button.innerText = '🐛 Tryb Debug: ON';
            button.style.backgroundColor = '#28a745';
        }
    };

    /**
     * Wyświetla nakładkę debugowania z porównaniem danych
     * @param {Object} regularData - Dane z regularnego endpointu
     * @param {Object} debugData - Dane z endpointu debugowania
     */
    const showDebugOverlay = (regularData, debugData) => {
        // Usuń istniejącą nakładkę, jeśli istnieje
        const existingOverlay = document.getElementById('npc-debug-overlay');
        if (existingOverlay) existingOverlay.remove();

        // Utwórz nakładkę debugowania
        const overlay = document.createElement('div');
        overlay.id = 'npc-debug-overlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '50px';
        overlay.style.right = '10px';
        overlay.style.width = '400px';
        overlay.style.maxHeight = '80vh';
        overlay.style.backgroundColor = 'rgba(33, 37, 41, 0.95)';
        overlay.style.color = 'white';
        overlay.style.padding = '15px';
        overlay.style.borderRadius = '5px';
        overlay.style.zIndex = '9998';
        overlay.style.overflowY = 'auto';
        overlay.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.5)';

        // Nagłówek
        const header = document.createElement('div');
        header.style.display = 'flex';
        header.style.justifyContent = 'space-between';
        header.style.alignItems = 'center';
        header.style.marginBottom = '10px';

        const title = document.createElement('h4');
        title.innerText = 'NPC Debug - ID: ' + (debugData.npc_data?.id || 'N/A');
        title.style.margin = '0';

        const closeButton = document.createElement('button');
        closeButton.innerText = '×';
        closeButton.style.backgroundColor = 'transparent';
        closeButton.style.border = 'none';
        closeButton.style.color = 'white';
        closeButton.style.fontSize = '24px';
        closeButton.style.cursor = 'pointer';
        closeButton.addEventListener('click', () => overlay.remove());

        header.appendChild(title);
        header.appendChild(closeButton);
        overlay.appendChild(header);

        // Przyciski przełączania widoku
        const tabsContainer = document.createElement('div');
        tabsContainer.style.display = 'flex';
        tabsContainer.style.marginBottom = '10px';

        const createTab = (text, isActive = false) => {
            const tab = document.createElement('button');
            tab.innerText = text;
            tab.style.flex = '1';
            tab.style.padding = '8px';
            tab.style.backgroundColor = isActive ? '#007bff' : '#343a40';
            tab.style.color = 'white';
            tab.style.border = 'none';
            tab.style.cursor = 'pointer';
            tab.style.margin = '0 2px';
            return tab;
        };

        const regularTab = createTab('Standardowe dane', true);
        const debugTab = createTab('Pełne dane');
        const comparisonTab = createTab('Porównanie');

        tabsContainer.appendChild(regularTab);
        tabsContainer.appendChild(debugTab);
        tabsContainer.appendChild(comparisonTab);
        overlay.appendChild(tabsContainer);

        // Kontener zawartości
        const contentContainer = document.createElement('div');
        contentContainer.id = 'debug-content-container';
        overlay.appendChild(contentContainer);

        // Funkcje wyświetlania różnych widoków
        const showRegularData = () => {
            regularTab.style.backgroundColor = '#007bff';
            debugTab.style.backgroundColor = '#343a40';
            comparisonTab.style.backgroundColor = '#343a40';

            contentContainer.innerHTML = `
                <pre style="color: #8be9fd;">${JSON.stringify(regularData, null, 2)}</pre>
            `;
        };

        const showDebugData = () => {
            regularTab.style.backgroundColor = '#343a40';
            debugTab.style.backgroundColor = '#007bff';
            comparisonTab.style.backgroundColor = '#343a40';

            contentContainer.innerHTML = `
                <pre style="color: #50fa7b;">${JSON.stringify(debugData, null, 2)}</pre>
            `;
        };

        const showComparison = () => {
            regularTab.style.backgroundColor = '#343a40';
            debugTab.style.backgroundColor = '#343a40';
            comparisonTab.style.backgroundColor = '#007bff';

            contentContainer.innerHTML = `
                <h5>Statystyki porównania:</h5>
                <div style="margin-bottom: 15px;">
                    <div>Ilość dialogów (filtrowane): ${regularData.npc_data?.dialog ? 1 : 0}</div>
                    <div>Ilość dialogów (niefiltrowane): ${debugData.npc_data?.dialogs?.length || 0}</div>
                </div>
                <h5>Szczegóły filtrowania:</h5>
                <div>Lokalizacja: ${debugData.request_params?.page_data?.value || 'N/A'}</div>
                <div>Typ strony: ${debugData.request_params?.page_data?.TypePage || 'N/A'}</div>
            `;
        };

        // Dodaj nasłuchiwacze zdarzeń dla przycisków
        regularTab.addEventListener('click', showRegularData);
        debugTab.addEventListener('click', showDebugData);
        comparisonTab.addEventListener('click', showComparison);

        // Domyślnie pokaż standardowe dane
        showRegularData();

        document.body.appendChild(overlay);
    };
})();