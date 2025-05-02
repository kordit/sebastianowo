/**
 * UserManager API - interfejs JavaScript do zarządzania ManagerUser.php
 * 
 * Moduł zapewnia łatwe zarządzanie statystykami, przedmiotami, rejonami użytkownika
 * z wykorzystaniem REST API klasy ManagerUser.php
 */

const UserManager = (() => {
    /**
     * Podstawowe ustawienia axios dla żądań
     */
    const axiosConfig = {
        headers: {
            'X-WP-Nonce': userManagerData.nonce,
            'Content-Type': 'application/json'
        }
    };

    /**
     * Obsługa błędów z API
     * 
     * @param {Error} error - Obiekt błędu z axios
     * @returns {Object} - Ustrukturyzowany obiekt błędu
     */
    const handleError = (error) => {
        console.error('Błąd API ManagerUser:', error);

        if (error.response) {
            return {
                success: false,
                message: error.response.data.message || 'Wystąpił błąd podczas komunikacji z serwerem',
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
        try {
            const response = await axios.post(
                userManagerData.rest_url + '/update-user-field',
                params,
                axiosConfig
            );

            return response.data;
        } catch (error) {
            throw handleError(error);
        }
    };

    /**
     * Pobieranie danych użytkownika
     * 
     * @returns {Promise} - Promise z danymi użytkownika
     */
    const getUserData = async () => {
        try {
            const response = await axios.get(
                userManagerData.rest_url + '/get-user-data',
                axiosConfig
            );

            return response.data;
        } catch (error) {
            throw handleError(error);
        }
    };

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
        getUserData
    };
})();

// Eksportuj moduł do globalnego użycia
window.UserManager = UserManager;
