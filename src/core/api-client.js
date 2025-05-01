/**
 * API Client oparty na Axios
 * Centralny moduł do obsługi wszystkich zapytań AJAX w grze
 */

// Tworzymy instancję Axios z domyślną konfiguracją
// Używamy globalnego axios, który został już zaimportowany w index.js
const api = window.axios.create({
    baseURL: window.gameData ? window.gameData.ajaxurl : '/wp-admin/admin-ajax.php',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    }
});

/**
 * Konfiguracja interceptorów i ustawienie klienta API
 */
export function setupApiClient() {
    // Dodaj interceptor do automatycznego dodawania nonce do każdego żądania
    api.interceptors.request.use(config => {
        // Przygotuj parametry formularza jeśli jeszcze nie istnieją
        if (!config.data) {
            config.data = new URLSearchParams();
        } else if (!(config.data instanceof URLSearchParams)) {
            // Jeśli dane to obiekt, konwertuj go na URLSearchParams
            const params = new URLSearchParams();
            for (const key in config.data) {
                if (typeof config.data[key] === 'object') {
                    params.append(key, JSON.stringify(config.data[key]));
                } else {
                    params.append(key, config.data[key]);
                }
            }
            config.data = params;
        }

        // Dodaj odpowiedni nonce do żądania w zależności od akcji
        const actionStr = config.data.get ? config.data.get('action') : '';

        if (window.gameData) {
            if (actionStr && actionStr.includes('mission')) {
                config.data.append('nonce', window.gameData.missionNonce || window.gameData.nonce);
            } else if (actionStr && actionStr.includes('data_manager')) {
                config.data.append('nonce', window.gameData.dataManagerNonce || window.gameData.nonce);
            } else {
                config.data.append('nonce', window.gameData.nonce);
            }
        }

        return config;
    });

    // Interceptor do obsługi odpowiedzi i błędów
    api.interceptors.response.use(
        // Obsługa udanych odpowiedzi
        response => {
            // WordPress AJAX zawsze zwraca 200, nawet w przypadku błędu
            // Musimy sprawdzić wewnętrzny status sukcesu
            if (response.data && typeof response.data.success !== 'undefined') {
                if (response.data.success === false) {
                    // Standardowy format błędu WordPress AJAX
                    const error = new Error(
                        response.data.data?.message || 'Nieznany błąd'
                    );
                    error.wpError = response.data.data;
                    return Promise.reject(error);
                }
                // Zwracamy tylko właściwe dane z odpowiedzi WordPress
                return response.data.data;
            }

            // Dla innych formatów odpowiedzi, zwracamy całą odpowiedź
            return response;
        },
        // Obsługa błędów
        error => {
            // Wyświetl błędy w konsoli w trybie debug
            if (window.GameDebug) {
                console.error('API error:', error);
            }

            // Możemy tutaj dodać globalną obsługę błędów
            // np. wyświetlanie powiadomień o błędach
            if (window.notifyError) {
                window.notifyError(
                    error.message || 'Wystąpił błąd podczas komunikacji z serwerem'
                );
            }

            return Promise.reject(error);
        }
    );

    // Udostępniamy API globalnie
    window.gameAPI = api;

    // Dodajemy funkcję pomocniczą do łatwiejszego wywoływania zapytań AJAX
    window.ajaxRequest = async (action, data = {}) => {
        const params = new URLSearchParams();
        params.append('action', action);

        // Dodanie wszystkich pól z obiektu data
        Object.entries(data).forEach(([key, value]) => {
            if (typeof value === 'object') {
                params.append(key, JSON.stringify(value));
            } else {
                params.append(key, value);
            }
        });

        try {
            const response = await api.post('', params);
            return response;
        } catch (error) {
            console.error(`Error in ajaxRequest for action ${action}:`, error);
            throw error;
        }
    };
}

/**
 * Funkcja pomocnicza do tworzenia żądań AJAX zgodnych z WordPress
 * 
 * @param {string} action - Nazwa akcji AJAX WordPress
 * @param {object} data - Dane do wysłania
 * @returns {Promise} - Promise z wynikiem zapytania
 */
export function ajaxAction(action, data = {}) {
    const params = new URLSearchParams();
    params.append('action', action);

    // Dodaj pozostałe dane do parametrów
    for (const key in data) {
        if (typeof data[key] === 'object') {
            params.append(key, JSON.stringify(data[key]));
        } else {
            params.append(key, data[key]);
        }
    }

    return api.post('', params);
}

// Eksportujemy gotowe funkcje do różnych operacji użytkownika
export const userAPI = {
    /**
     * Pobiera dane użytkownika
     * @param {number} userId - ID użytkownika (opcjonalne)
     */
    getUserData: (userId = null) => {
        const data = {};
        if (userId) {
            data.user_id = userId;
        }
        return ajaxAction('get_user_data', data);
    },

    /**
     * Aktualizuje dane użytkownika
     * @param {object} userData - Dane użytkownika do aktualizacji
     * @param {number} userId - ID użytkownika (opcjonalne)
     */
    updateUserData: (userData, userId = null) => {
        const data = {
            user_data: userData
        };
        if (userId) {
            data.user_id = userId;
        }
        return ajaxAction('update_user_data', data);
    },

    /**
     * Aktualizuje poziom użytkownika
     * @param {number} experience - Nowe doświadczenie użytkownika
     * @param {number} level - Opcjonalny nowy poziom
     * @param {number} userId - ID użytkownika (opcjonalne)
     */
    updateUserLevel: (experience, level = null, userId = null) => {
        const data = {};
        if (experience !== null) data.experience = experience;
        if (level !== null) data.level = level;
        if (userId) data.user_id = userId;

        return ajaxAction('update_user_level', data);
    },

    /**
     * Pobiera misje użytkownika
     * @param {number} userId - ID użytkownika (opcjonalne)
     */
    getUserMissions: (userId = null) => {
        const data = {};
        if (userId) data.user_id = userId;
        return ajaxAction('get_user_missions', data);
    },

    /**
     * Aktualizuje misję użytkownika
     * @param {object} missionData - Dane misji
     * @param {number} userId - ID użytkownika (opcjonalne)
     */
    updateUserMission: (missionData, userId = null) => {
        const data = { mission_data: missionData };
        if (userId) data.user_id = userId;
        return ajaxAction('update_user_mission', data);
    },

    /**
     * Pobiera ekwipunek użytkownika
     * @param {number} userId - ID użytkownika (opcjonalne)
     */
    getUserInventory: (userId = null) => {
        const data = {};
        if (userId) data.user_id = userId;
        return ajaxAction('get_user_inventory', data);
    },

    /**
     * Aktualizuje ekwipunek użytkownika
     * @param {object} inventoryData - Dane ekwipunku
     * @param {number} userId - ID użytkownika (opcjonalne)
     */
    updateUserInventory: (inventoryData, userId = null) => {
        const data = { inventory_data: inventoryData };
        if (userId) data.user_id = userId;
        return ajaxAction('update_user_inventory', data);
    },

    /**
     * Waliduje wymaganie użytkownika
     * @param {string} reqType - Typ wymagania (gold, stats, skills, vitality, item, equipped_item)
     * @param {string|number} reqValue - Wartość wymagania
     * @param {number} reqAmount - Ilość wymagana
     * @param {boolean} validate - Czy walidować (true) czy pominąć (false)
     * @param {number} userId - ID użytkownika (opcjonalne)
     */
    validateUserRequirement: (reqType, reqValue, reqAmount, validate = true, userId = null) => {
        const data = {
            req_type: reqType,
            req_value: reqValue,
            req_amount: reqAmount,
            validate: validate
        };
        if (userId) data.user_id = userId;
        return ajaxAction('validate_user_requirement', data);
    }
};

export default api;
