/**
 * System obsługi misji
 * 
 * Ten moduł zajmuje się:
 * - uruchamianiem misji
 * - obsługą zadań
 * - wyświetlaniem powiadomień o misjach i zadaniach
 */

// Globalny zarządca misji
class MissionManager {
    constructor() {
        this.pendingNotifications = [];
        this.isProcessingNotifications = false;
        this.notificationDelay = 250; // ms między powiadomieniami
    }

    /**
     * Uruchom misję z podanymi parametrami
     * 
     * @param {Object} params - Parametry misji
     * @returns {Promise<Object|boolean>} - Rezultat misji lub false w przypadku błędu
     */
    async startMission(params) {
        console.log('Parametry misji:', params);

        // Sprawdź, czy otrzymaliśmy wymagane parametry
        if (!params || !params.mission_id) {
            console.error('Błąd: brak wymaganych parametrów misji');
            this.showNotification('Nie można uruchomić misji: brak identyfikatora misji', 'failed');
            return false;
        }

        try {
            // Pobierz informacje o misji przed próbą jej przypisania
            const missionInfoResponse = await AjaxHelper.sendRequest(window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
                action: 'get_mission_info',
                mission_id: params.mission_id,
                mission_task_id: params.mission_task_id || null
            });

            if (!missionInfoResponse.success) {
                console.error('Błąd podczas pobierania informacji o misji:', missionInfoResponse.data?.message);
                this.showNotification('Nie można znaleźć misji', 'failed');
                return false;
            }

            // Pobierz dane misji
            const missionInfo = missionInfoResponse.data;

            // Zawsze używaj ID zadania z parametrów, jeśli jest dostępne
            const taskIdToUse = params.mission_task_id || missionInfo.first_task_id;

            // Przygotuj dane do zapisania misji
            const missionData = {
                action: 'assign_mission_to_user',
                mission_id: params.mission_id,
                npc_id: params.npc_id || null,
                mission_status: params.mission_status,
                mission_task_id: taskIdToUse,
                mission_task_status: params.mission_task_status
            };

            // Przypisz misję do użytkownika
            const assignResponse = await AjaxHelper.sendRequest(window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', missionData);

            if (!assignResponse.success) {
                console.error('Błąd podczas przypisywania misji:', assignResponse.data?.message);
                this.showNotification(assignResponse.data?.message || 'Nie można przypisać misji', 'failed');
                return false;
            }

            // Pokaż komunikat potwierdzający, jeśli wymagany
            if (params.show_confirmation) {
                const message = params.success_message || 'Otrzymano nową misję!';
                this.showNotification(message, 'success');
            }

            // Wzbogać dane odpowiedzi o dodatkowe informacje
            assignResponse.data.original_params = params;

            // Dodaj numer zadania do odpowiedzi
            const taskNumMatch = taskIdToUse ? taskIdToUse.match(/_(\d+)$/) : null;
            if (taskNumMatch && taskNumMatch[1]) {
                assignResponse.data.task_num = taskNumMatch[1];
            }

            // Opcjonalne przekierowanie
            if (params.redirect_after) {
                setTimeout(() => {
                    window.location.href = params.redirect_after;
                }, 1500);
            }

            // Zwróć dane odpowiedzi
            return assignResponse.data;
        } catch (error) {
            console.error('Wystąpił błąd podczas uruchamiania misji:', error);
            this.showNotification('Wystąpił błąd podczas uruchamiania misji: ' + error, 'failed');
            return false;
        }
    }

    /**
     * Pokaż powiadomienie i dodaj je do kolejki
     * 
     * @param {string} message - Treść powiadomienia
     * @param {string} status - Status powiadomienia (success, bad, failed, neutral)
     */
    showNotification(message, status = 'neutral') {
        // Użyj globalnej funkcji showPopup, jeśli jest dostępna
        if (typeof window.showPopup === 'function') {
            window.showPopup(message, status);
        } else {
            console.log(`[POWIADOMIENIE] ${status}: ${message}`);
        }
    }

    /**
     * Obsłuż wiele zadań misji i zapewnij, że powiadomienia są poprawne i unikalne
     * 
     * @param {Array} missionsToStart - Tablica z misjami do uruchomienia
     * @returns {Promise<Array>} - Tablica przetworzonych komunikatów o misjach
     */
    async handleMultipleMissions(missionsToStart) {
        if (!Array.isArray(missionsToStart) || missionsToStart.length === 0) {
            return [];
        }

        const allMissionMessages = [];

        // Grupowanie misji według identyfikatora misji
        const missionGroups = {};
        for (const mission of missionsToStart) {
            const id = mission.mission_id;
            if (!missionGroups[id]) {
                missionGroups[id] = [];
            }
            missionGroups[id].push(mission);
        }

        // Przetwarzanie każdej grupy misji
        for (const missionId in missionGroups) {
            const missionsInGroup = missionGroups[missionId];

            for (const missionConfig of missionsInGroup) {
                try {
                    if (!missionConfig.mission_id) {
                        console.error('Brak ID misji w konfiguracji:', missionConfig);
                        continue;
                    }

                    // Parametry dla funkcji startMission
                    const missionParams = {
                        mission_id: missionConfig.mission_id,
                        npc_id: missionConfig.npc_id,
                        mission_status: missionConfig.mission_status || 'active',
                        mission_task_status: missionConfig.mission_task_status || 'active',
                        success_message: missionConfig.success_message || 'Otrzymano nową misję!',
                        // Przekazujemy dodatkowy parametr, aby uniknąć pokazywania automatycznych komunikatów
                        show_confirmation: missionConfig.show_confirmation || false
                    };

                    // Przekaż prawidłowe ID zadania misji
                    if (missionConfig.mission_task_id) {
                        missionParams.mission_task_id = missionConfig.mission_task_id;
                    }

                    // Uruchom misję i sprawdź status
                    const missionResult = await this.startMission(missionParams);

                    // Jeśli wystąpił błąd podczas uruchamiania misji
                    if (missionResult === false) {
                        continue;
                    }

                    // Zapisz komunikat z dodatkowymi informacjami do identyfikacji
                    if (!missionConfig.show_confirmation && missionResult && missionResult.message) {
                        // Dodaj pełne dane do wiadomości
                        allMissionMessages.push({
                            message: missionResult.message,
                            taskId: missionConfig.mission_task_id || '',
                            missionId: missionConfig.mission_id || '',
                            taskStatus: missionConfig.mission_task_status || '',
                            originalMessage: missionResult.message,
                            timestamp: Date.now()
                        });
                    }
                } catch (error) {
                    console.error('Błąd podczas uruchamiania misji:', error);
                    this.showNotification('Wystąpił błąd: ' + (error.message || 'nieznany błąd'), 'failed');
                }
            }
        }

        return allMissionMessages;
    }
}

// Tworzymy instancję globalną
window.missionManager = new MissionManager();

// Dla zgodności wstecz
window.startMission = async function (params) {
    return await window.missionManager.startMission(params);
};
