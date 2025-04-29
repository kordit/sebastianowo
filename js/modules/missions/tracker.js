/**
 * Moduł do śledzenia postępu misji
 * 
 * Ten moduł zajmuje się:
 * - śledzeniem aktywnych misji użytkownika
 * - aktualizacją statusu misji i zadań
 * - wyświetlaniem powiadomień o postępie
 */

class MissionTracker {
    constructor() {
        this.activeMissions = {};
        this.initialized = false;
    }

    /**
     * Inicjalizuje tracker misji
     */
    initialize() {
        if (this.initialized) return;
        this.initialized = true;

        // Pobierz aktywne misje użytkownika przy inicjalizacji
        this.loadActiveMissions().then(missions => {
            this.activeMissions = missions || {};
        });

        // Nasłuchuj na wydarzenia związane ze zmianami w misjach
        document.addEventListener('missionUpdated', this.handleMissionUpdate.bind(this));
        document.addEventListener('missionCompleted', this.handleMissionCompleted.bind(this));
    }

    /**
     * Pobiera aktywne misje użytkownika
     * @returns {Promise<Object>} Obiekt z aktywnymi misjami
     */
    async loadActiveMissions() {
        try {
            const response = await AjaxHelper.sendRequest(window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
                action: 'get_user_active_missions',
                nonce: window.gameData && window.gameData.missionNonce ? window.gameData.missionNonce : ''
            });

            if (response.success && response.data) {
                return response.data.missions || {};
            }

            return {};
        } catch (error) {
            console.error('Błąd podczas pobierania aktywnych misji:', error);
            return {};
        }
    }

    /**
     * Obsługuje aktualizację misji
     * @param {CustomEvent} event - Zdarzenie aktualizacji misji
     */
    handleMissionUpdate(event) {
        const missionData = event.detail;
        if (!missionData || !missionData.mission_id) return;

        // Aktualizuj lokalny status misji
        this.activeMissions[missionData.mission_id] = {
            ...this.activeMissions[missionData.mission_id],
            ...missionData
        };

        // Pokaż powiadomienie o aktualizacji
        if (missionData.notification) {
            showPopup(missionData.notification, 'success');
        }
    }

    /**
     * Obsługuje ukończenie misji
     * @param {CustomEvent} event - Zdarzenie ukończenia misji
     */
    handleMissionCompleted(event) {
        const missionData = event.detail;
        if (!missionData || !missionData.mission_id) return;

        // Usuń misję z listy aktywnych
        delete this.activeMissions[missionData.mission_id];

        // Pokaż powiadomienie o ukończeniu
        if (missionData.notification) {
            showPopup(missionData.notification, 'success');
        }
    }

    /**
     * Aktualizuje status zadania w misji
     * @param {string} missionId - ID misji
     * @param {string} taskId - ID zadania
     * @param {string} status - Nowy status zadania
     * @returns {Promise<boolean>} - Rezultat operacji
     */
    async updateTaskStatus(missionId, taskId, status) {
        try {
            const response = await AjaxHelper.sendRequest(window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
                action: 'update_mission_task_status',
                mission_id: missionId,
                task_id: taskId,
                status: status
            });

            if (response.success) {
                // Aktualizuj lokalny stan
                if (this.activeMissions[missionId]) {
                    if (!this.activeMissions[missionId].tasks) {
                        this.activeMissions[missionId].tasks = {};
                    }

                    this.activeMissions[missionId].tasks[taskId] = {
                        ...this.activeMissions[missionId].tasks[taskId],
                        status: status
                    };
                }

                // Wyemituj zdarzenie aktualizacji
                const updateEvent = new CustomEvent('missionUpdated', {
                    detail: {
                        mission_id: missionId,
                        task_id: taskId,
                        status: status,
                        notification: response.data?.message
                    }
                });

                document.dispatchEvent(updateEvent);
                return true;
            }

            return false;
        } catch (error) {
            console.error('Błąd podczas aktualizacji statusu zadania:', error);
            return false;
        }
    }
}

// Utwórz globalną instancję trackera misji
window.missionTracker = new MissionTracker();

// Inicjalizuj tracker po załadowaniu dokumentu
document.addEventListener('DOMContentLoaded', () => {
    window.missionTracker.initialize();
});
