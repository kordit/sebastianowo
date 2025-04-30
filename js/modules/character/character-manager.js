/**
 * Moduł zarządzający postacią gracza
 * Obsługuje operacje związane z danymi postaci, w tym aktualizację złota
 */
console.log('test');;
class CharacterManager {
    /**
     * Inicjalizacja modułu Character Manager
     */
    constructor() {
        this.ajaxUrl = SoeasyGame.ajaxurl || '/wp-admin/admin-ajax.php';
        this.userManagerNonce = SoeasyGame.user_manager_nonce || '';
    }

    /**
     * Dodaje określoną ilość złota do konta użytkownika
     * 
     * @param {number} amount - Ilość złota do dodania (wartość ujemna oznacza odjęcie)
     * @param {number|null} userId - ID użytkownika (opcjonalnie, tylko dla admina)
     * @returns {Promise} - Promise z wynikiem operacji
     */
    addGold(amount, userId = null) {
        // Najpierw pobieramy aktualny stan plecaka użytkownika
        return this.getBackpack(userId)
            .then(backpackData => {
                // Aktualizujemy ilość złota
                const currentGold = backpackData.gold || 0;
                const newGold = Math.max(0, currentGold + parseInt(amount));

                // Przygotowujemy dane do aktualizacji
                const backpack = {
                    ...backpackData,
                    gold: newGold
                };

                // Zapisujemy zaktualizowany plecak
                return this.updateBackpack(backpack, userId);
            });
    }

    /**
     * Pobiera dane plecaka użytkownika
     * 
     * @param {number|null} userId - ID użytkownika (opcjonalnie, tylko dla admina)
     * @returns {Promise} - Promise z danymi plecaka
     */
    getBackpack(userId = null) {
        const data = {
            action: 'get_user_data',
            nonce: this.userManagerNonce
        };

        if (userId && userId > 0) {
            data.user_id = userId;
        }

        return AjaxHelper.sendRequest(this.ajaxUrl, 'POST', data)
            .then(response => {
                if (response.data && response.data.backpack) {
                    return response.data.backpack;
                }
                // Jeśli nie ma plecaka w odpowiedzi, zwracamy pusty obiekt
                return { gold: 0 };
            });
    }

    /**
     * Aktualizuje dane plecaka użytkownika
     * 
     * @param {object} backpackData - Nowe dane plecaka
     * @param {number|null} userId - ID użytkownika (opcjonalnie, tylko dla admina)
     * @returns {Promise} - Promise z wynikiem operacji
     */
    updateBackpack(backpackData, userId = null) {
        const data = {
            action: 'update_user_data',
            nonce: this.userManagerNonce,
            user_data: JSON.stringify({ backpack: backpackData })
        };

        if (userId && userId > 0) {
            data.user_id = userId;
        }

        return AjaxHelper.sendRequest(this.ajaxUrl, 'POST', data);
    }
}

// Eksportujemy instancję klasy
const characterManager = new CharacterManager();
export default characterManager;
