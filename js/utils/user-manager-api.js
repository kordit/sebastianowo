/**
 * UserManager API - interfejs JavaScript do zarządzania ManagerUser.php
 * 
 * Moduł zapewnia łatwe zarządzanie statystykami, przedmiotami, rejonami użytkownika
 * z wykorzystaniem REST API klasy ManagerUser.php
 */

const UserManager = (() => {
    /**
     * Podstawowe ustawienia axios dla żądań z zabezpieczeniami
     */
    const axiosConfig = {
        headers: {
            'X-WP-Nonce': userManagerData?.nonce || '', // Token nonce do zabezpieczenia CSRF
            'Content-Type': 'application/json'
        },
        withCredentials: true // Zapewnia wysyłanie ciasteczek w żądaniach cross-origin
    };

    /**
     * Flaga wskazująca, czy obecnie odświeżamy token
     * Zapobiega wielokrotnym jednoczesnym próbom odświeżania
     */
    let isRefreshingToken = false;

    /**
     * Kolejka żądań oczekujących na odświeżenie tokena
     */
    const pendingRequests = [];

    /**
     * Bazowy URL dla endpointów REST API
     * Zapewnij, że zawsze używamy absolutnego URL zaczynającego się od protokołu lub /
     * 
     * @returns {string} Bazowy URL dla endpointów REST API
     */
    const getBaseRestUrl = () => {
        // Sprawdź czy userManagerData.rest_url jest zdefiniowane
        if (userManagerData?.rest_url) {
            // Jeśli URL zaczyna się od http lub / - użyj go bezpośrednio
            if (userManagerData.rest_url.startsWith('http') || userManagerData.rest_url.startsWith('/')) {
                return userManagerData.rest_url;
            }
        }
        // Fallback - użyj standardowej ścieżki WordPress REST API
        return '/wp-json/game/v1';
    };

    /**
     * Asynchronicznie odświeża token bezpieczeństwa
     * 
     * @returns {Promise<string|null>} Promise z nowym tokenem lub null jeśli wystąpił błąd
     */
    const refreshTokenAsync = async () => {
        // Jeśli już odświeżamy token, zwróć istniejący proces
        if (isRefreshingToken) {
            return new Promise((resolve) => {
                // Dodaj do kolejki funkcję, która zostanie wywołana po odświeżeniu tokena
                pendingRequests.push((token) => {
                    resolve(token);
                });
            });
        }

        isRefreshingToken = true;

        try {
            const response = await fetch(`${getBaseRestUrl()}/refresh-nonce`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });

            if (!response.ok) {
                throw new Error(`Błąd HTTP: ${response.status}`);
            }

            const data = await response.json();

            if (data && data.success && data.nonce) {
                // Aktualizuj globalny token i konfigurację axios
                if (window.userManagerData) {
                    window.userManagerData.nonce = data.nonce;
                }
                axiosConfig.headers['X-WP-Nonce'] = data.nonce;

                // Wywołaj wszystkie oczekujące żądania
                pendingRequests.forEach(callback => callback(data.nonce));
                pendingRequests.length = 0; // Wyczyść kolejkę

                return data.nonce;
            }
            return null;
        } catch (error) {
            console.error('Błąd podczas odświeżania tokena:', error);

            // Poinformuj oczekujące żądania o błędzie
            pendingRequests.forEach(callback => callback(null));
            pendingRequests.length = 0; // Wyczyść kolejkę

            return null;
        } finally {
            isRefreshingToken = false;
        }
    };

    /**
     * Wykonanie żądania z automatycznym ponawianiem przy błędach autoryzacji
     * 
     * @param {Function} requestFn - Funkcja wykonująca żądanie axios
     * @param {number} retries - Liczba pozostałych prób
     * @returns {Promise<any>} Wynik żądania
     */
    const executeWithRetry = async (requestFn, retries = 1) => {
        try {
            return await requestFn();
        } catch (error) {
            // Jeśli błąd 403 i mamy jeszcze próby, odśwież token i spróbuj ponownie
            if (error.status === 403 && retries > 0) {
                console.warn('Odświeżanie tokena bezpieczeństwa i ponawianie żądania...');

                const newToken = await refreshTokenAsync();
                if (newToken) {
                    return executeWithRetry(requestFn, retries - 1);
                }
            }

            throw error; // Propaguj błąd dalej, jeśli nie udało się ponowić
        }
    };

    /**
     * Obsługa błędów z API z uwzględnieniem błędów autoryzacji
     * 
     * @param {Error} error - Obiekt błędu z axios
     * @returns {Object} - Ustrukturyzowany obiekt błędu
     */
    const handleError = (error) => {
        console.error('Błąd API UserManager:', error);

        if (error.response) {
            return {
                success: false,
                message: error.response.data?.message || 'Wystąpił błąd podczas komunikacji z serwerem',
                status: error.response.status
            };
        }

        return {
            success: false,
            message: error.message || 'Nieznany błąd',
            status: 500
        };
    };

    /**
     * Wykonanie żądania do API ManagerUser
     * 
     * @param {Object} params - Parametry żądania
     * @returns {Promise} - Promise z wynikiem żądania
     */
    const makeRequest = async (params) => {
        const baseUrl = getBaseRestUrl();

        // Funkcja wykonująca właściwe żądanie
        const performRequest = async () => {
            try {
                // Debugowanie - dla łatwiejszego wykrywania problemów z URL
                console.debug('Wywołanie API:', `${baseUrl}/update-user-field`, params);

                const response = await axios.post(
                    `${baseUrl}/update-user-field`,
                    params,
                    axiosConfig
                );

                return response.data;
            } catch (error) {
                console.error('Błąd żądania:', error.message, error.config?.url);
                throw handleError(error);
            }
        };

        // Wykonaj z automatycznym ponawianiem
        return executeWithRetry(performRequest);
    };

    /**
     * Pobieranie danych użytkownika
     * 
     * @returns {Promise} - Promise z danymi użytkownika
     */
    const getUserData = async () => {
        const baseUrl = getBaseRestUrl();

        // Funkcja wykonująca właściwe żądanie
        const performRequest = async () => {
            try {
                const response = await axios.get(
                    `${baseUrl}/get-user-data`,
                    axiosConfig
                );
                return response.data;
            } catch (error) {
                throw handleError(error);
            }
        };

        // Wykonaj z automatycznym ponawianiem
        return executeWithRetry(performRequest);
    };

    // Główny obiekt API z metodami publicznymi
    return {
        /**
         * Aktualizacja statystyki
         * 
         * @param {string} statName - Nazwa statystyki (strength, defense, itd.)
         * @param {number} value - Wartość do dodania/odjęcia
         * @returns {Promise} - Promise z wynikiem operacji
         */
        updateStat: async (statName, value) => {
            return makeRequest({
                fieldType: 'stat',
                fieldName: statName,
                value: parseFloat(value)
            });
        },

        /**
         * Aktualizacja umiejętności
         * 
         * @param {string} skillName - Nazwa umiejętności (combat, steal, itd.)
         * @param {number} value - Wartość do dodania/odjęcia
         * @returns {Promise} - Promise z wynikiem operacji
         */
        updateSkill: async (skillName, value) => {
            return makeRequest({
                fieldType: 'skill',
                fieldName: skillName,
                value: parseFloat(value)
            });
        },

        /**
         * Zakładanie przedmiotu na postać
         * 
         * @param {number} itemId - ID przedmiotu do założenia
         * @param {string} slot - Slot, na który założyć przedmiot (chest_item, bottom_item, legs_item)
         * @returns {Promise} - Promise z wynikiem operacji
         */
        equipItem: async (itemId, slot) => {
            try {
                const response = await axios.post(
                    `${getBaseRestUrl()}/equip-item`,
                    {
                        item_id: itemId,
                        slot: slot
                    },
                    axiosConfig
                );
                return response.data;
            } catch (error) {
                throw handleError(error);
            }
        },

        /**
         * Zdejmowanie przedmiotu z postaci
         * 
         * @param {string} slot - Slot, z którego zdjąć przedmiot (chest_item, bottom_item, legs_item)
         * @returns {Promise} - Promise z wynikiem operacji
         */
        unequipItem: async (slot) => {
            try {
                const response = await axios.post(
                    `${getBaseRestUrl()}/unequip-item`,
                    {
                        slot: slot
                    },
                    axiosConfig
                );
                return response.data;
            } catch (error) {
                throw handleError(error);
            }
        },

        /**
         * Aktualizacja zawartości plecaka
         * 
         * @param {string} itemName - Nazwa przedmiotu (gold, cigarettes, itd.)
         * @param {number} value - Wartość do dodania/odjęcia
         * @returns {Promise} - Promise z wynikiem operacji
         */
        updateBackpack: async (itemName, value) => {
            return makeRequest({
                fieldType: 'backpack',
                fieldName: itemName,
                value: parseFloat(value)
            });
        },

        /**
         * Aktualizacja witalności
         * 
         * @param {string} vitalityName - Nazwa witalności (life, max_life, energy, max_energy)
         * @param {number} value - Wartość do dodania/odjęcia
         * @returns {Promise} - Promise z wynikiem operacji
         */
        updateVitality: async (vitalityName, value) => {
            return makeRequest({
                fieldType: 'vitality',
                fieldName: vitalityName,
                value: parseFloat(value)
            });
        },

        /**
         * Aktualizacja postępu
         * 
         * @param {string} progressName - Nazwa postępu (exp, learning_points, reputation)
         * @param {number} value - Wartość do dodania/odjęcia
         * @returns {Promise} - Promise z wynikiem operacji
         */
        updateProgress: async (progressName, value) => {
            return makeRequest({
                fieldType: 'progress',
                fieldName: progressName,
                value: parseFloat(value)
            });
        },

        /**
         * Dodanie przedmiotu do plecaka
         * 
         * @param {number} itemId - ID przedmiotu do dodania
         * @param {number} quantity - Ilość przedmiotu do dodania
         * @returns {Promise} - Promise z wynikiem operacji
         */
        addItem: async (itemId, quantity = 1) => {
            return makeRequest({
                fieldType: 'item',
                fieldName: 'add',
                itemId: parseInt(itemId),
                quantity: parseInt(quantity)
            });
        },

        /**
         * Usunięcie przedmiotu z plecaka
         * 
         * @param {number} itemId - ID przedmiotu do usunięcia
         * @param {number} quantity - Ilość przedmiotu do usunięcia
         * @returns {Promise} - Promise z wynikiem operacji
         */
        removeItem: async (itemId, quantity = 1) => {
            return makeRequest({
                fieldType: 'item',
                fieldName: 'remove',
                itemId: parseInt(itemId),
                quantity: parseInt(quantity)
            });
        },

        /**
         * Dodanie rejonu do dostępnych rejonów
         * 
         * @param {number} areaId - ID rejonu do dodania
         * @returns {Promise} - Promise z wynikiem operacji
         */
        addAvailableArea: async (areaId) => {
            return makeRequest({
                fieldType: 'area',
                action: 'add',
                areaId: parseInt(areaId)
            });
        },

        /**
         * Usunięcie rejonu z dostępnych rejonów
         * 
         * @param {number} areaId - ID rejonu do usunięcia
         * @returns {Promise} - Promise z wynikiem operacji
         */
        removeAvailableArea: async (areaId) => {
            return makeRequest({
                fieldType: 'area',
                action: 'remove',
                areaId: parseInt(areaId)
            });
        },

        /**
         * Zmiana aktualnego rejonu gracza
         * 
         * @param {number} areaId - ID rejonu do ustawienia jako aktualny
         * @returns {Promise} - Promise z wynikiem operacji
         */
        setCurrentArea: async (areaId) => {
            return makeRequest({
                fieldType: 'area',
                action: 'set_current',
                areaId: parseInt(areaId)
            });
        },

        /**
         * Aktualizacja relacji z NPC
         * 
         * @param {number} npcId - ID NPC, z którym aktualizujemy relację
         * @param {number} value - Wartość do dodania/odjęcia od relacji (-100 do 100)
         * @returns {Promise} - Promise z wynikiem operacji
         */
        updateNpcRelation: async (npcId, value) => {
            return makeRequest({
                fieldType: 'relation',
                npcId: parseInt(npcId),
                value: parseInt(value)
            });
        },

        /**
         * Pobranie danych użytkownika
         * 
         * @returns {Promise} - Promise z danymi użytkownika
         */
        getUserData,

        /**
         * Ręczne odświeżenie tokenu bezpieczeństwa
         * 
         * @returns {Promise<boolean>} - Promise z informacją czy token został pomyślnie odświeżony
         */
        refreshSecurityToken: async () => {
            const token = await refreshTokenAsync();
            return token !== null;
        }
    };
})();

/**
 * Funkcja do generowania nowego tokenu bezpieczeństwa
 * Może być wykorzystana w przypadku wygaśnięcia tokenu
 * 
 * @returns {string|null} - Nowy token bezpieczeństwa lub null jeśli błąd
 */
window.refreshSecurityToken = async function () {
    try {
        const response = await fetch('/wp-json/game/v1/refresh-nonce', {
            method: 'GET',
            credentials: 'same-origin' // Ważne dla bezpieczeństwa
        });

        if (!response.ok) throw new Error('Nie udało się odświeżyć tokenu');

        const data = await response.json();
        if (data && data.success && data.nonce) {
            // Aktualizuj globalny token
            if (window.userManagerData) {
                window.userManagerData.nonce = data.nonce;
            }
            return data.nonce;
        }
        return null;
    } catch (error) {
        console.error('Błąd podczas odświeżania tokenu:', error);
        return null;
    }
};

// Eksportuj moduł do globalnego użycia
window.UserManager = UserManager;

// Dobra praktyka - nasłuchuj na zdarzenia związane z załadowaniem strony
document.addEventListener('DOMContentLoaded', () => {
    // Jeśli użytkownik jest zalogowany, sprawdź czy token jest ważny
    if (userManagerData && userManagerData.nonce) {
        // Możemy opcjonalnie wykonać lekkie żądanie, aby sprawdzić ważność tokena
        UserManager.getUserData()
            .catch(() => {
                console.info('Automatyczne odświeżanie tokena bezpieczeństwa...');
                return UserManager.refreshSecurityToken();
            })
            .then(() => {
                console.debug('Inicjalizacja UserManager zakończona');
            });
    }
});
