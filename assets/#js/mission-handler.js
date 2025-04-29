/**
 * Nowy usprawniony system obsługi misji i powiadomień
 * 
 * Ten system zajmuje się:
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
            const missionInfoResponse = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
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
            const assignResponse = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', missionData);

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
        // Dodaj powiadomienie do kolejki
        this.pendingNotifications.push({ message, status });

        // Rozpocznij przetwarzanie kolejki, jeśli jeszcze nie jest aktywne
        if (!this.isProcessingNotifications) {
            this.processNotificationQueue();
        }
    }

    /**
     * Przetwórz kolejkę powiadomień, wyświetlając je z opóźnieniem
     */
    async processNotificationQueue() {
        if (this.pendingNotifications.length === 0) {
            this.isProcessingNotifications = false;
            return;
        }

        this.isProcessingNotifications = true;

        // Pobierz pierwsze powiadomienie z kolejki
        const notification = this.pendingNotifications.shift();

        // Użyj ogólnego systemu powiadomień dla spójności
        if (typeof window.showPopup === 'function') {
            window.showPopup(notification.message, notification.status);
        } else {
            console.log(`[POWIADOMIENIE] ${notification.status}: ${notification.message}`);
        }

        // Zaplanuj następne powiadomienie po opóźnieniu
        await new Promise(resolve => setTimeout(resolve, this.notificationDelay));
        this.processNotificationQueue();
    }

    /**
     * Obsłuż wiele zadań misji i zapewnij, że powiadomienia są poprawne i unikalne
     * 
     * @param {Array} missionsToStart - Tablica z misjami do uruchomienia
     * @returns {Promise<Array>} - Tablica przetworzonych komunikatów o misjach
     */
    async processMissions(missionsToStart) {
        console.log('Przetwarzanie misji:', missionsToStart);

        // Tablica na wszystkie komunikaty o misjach
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
            const npcId = document.getElementById('npcdatamanager')?.dataset?.id;

            for (const missionConfig of missionsInGroup) {
                try {
                    if (!missionConfig.mission_id) {
                        console.error('Brak ID misji w konfiguracji:', missionConfig);
                        continue;
                    }

                    // Parametry dla funkcji startMission
                    const missionParams = {
                        mission_id: missionConfig.mission_id,
                        npc_id: npcId,
                        mission_status: missionConfig.mission_status || 'active',
                        mission_task_status: missionConfig.mission_task_status || 'active',
                        success_message: missionConfig.success_message || 'Otrzymano nową misję!',
                        show_confirmation: missionConfig.show_confirmation || false
                    };

                    // Przekaż ID zadania misji, jeśli istnieje
                    if (missionConfig.mission_task_id) {
                        missionParams.mission_task_id = missionConfig.mission_task_id;
                    }

                    // Uruchom misję
                    const missionResult = await this.startMission(missionParams);

                    // Jeśli wystąpił błąd podczas uruchamiania misji
                    if (missionResult === false) {
                        return allMissionMessages;
                    }

                    // Zapisz komunikaty misji do tablicy
                    if (!missionParams.show_confirmation && missionResult) {                    // Obsługa pojedynczego komunikatu
                        if (missionResult.message) {
                            // Wyodrębnij numer zadania z ID
                            let taskNum = '';
                            if (missionConfig.mission_task_id && missionConfig.mission_task_id.match(/_(\d+)$/)) {
                                taskNum = missionConfig.mission_task_id.match(/_(\d+)$/)[1];
                            }

                            // Sprawdź czy mamy nazwę zadania w odpowiedzi
                            const taskName = missionResult.task_name || '';
                            console.log('Otrzymano nazwę zadania z backendu:', taskName, 'dla ID:', missionConfig.mission_task_id);

                            // Jeśli mamy nazwę zadania z backendu, zastąp nią nazwę w komunikacie
                            let finalMessage = missionResult.message;
                            if (taskName && taskName !== 'Nieznane zadanie') {
                                // Znajdź nazwę zadania w komunikacie i zastąp ją
                                const taskNameMatch = finalMessage.match(/Zadanie "(.*?)" jest teraz/);
                                if (taskNameMatch && taskNameMatch[1]) {
                                    finalMessage = finalMessage.replace(
                                        `Zadanie "${taskNameMatch[1]}"`,
                                        `Zadanie "${taskName}"`
                                    );
                                    console.log('Zamieniono nazwę zadania w komunikacie na:', taskName);
                                }
                            }

                            allMissionMessages.push({
                                message: finalMessage,
                                taskId: missionConfig.mission_task_id || '',
                                missionId: missionConfig.mission_id,
                                taskStatus: missionConfig.mission_task_status || '',
                                taskNum: taskNum,
                                timestamp: Date.now(),
                                taskName: taskName
                            });
                        }

                        // Obsługa wielu komunikatów
                        if (missionResult.messages && Array.isArray(missionResult.messages)) {
                            missionResult.messages.forEach(msg => {
                                allMissionMessages.push({
                                    message: msg,
                                    taskId: missionConfig.mission_task_id || '',
                                    missionId: missionConfig.mission_id,
                                    taskStatus: missionConfig.mission_task_status || '',
                                    timestamp: Date.now() + allMissionMessages.length
                                });
                            });
                        }
                    }
                } catch (error) {
                    console.error('Błąd podczas uruchamiania misji:', error);
                    this.showNotification(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'failed');
                    return allMissionMessages;
                }
            }
        }

        // Zwróć wszystkie przetworzone komunikaty
        return allMissionMessages;
    }

    /**
     * Wzbogać i wyświetl komunikaty o misjach
     * 
     * @param {Array} missionMessages - Tablica z komunikatami o misjach
     */
    displayMissionMessages(missionMessages) {
        if (missionMessages.length === 0) return;

        console.log('Komunikaty misji przed przetworzeniem:', missionMessages);

        // Sortowanie komunikatów
        missionMessages.sort((a, b) => {
            // Najpierw według numeru zadania
            const aNum = parseInt((a.taskId.match(/_(\d+)$/) || [0, 0])[1], 10) || 0;
            const bNum = parseInt((b.taskId.match(/_(\d+)$/) || [0, 0])[1], 10) || 0;

            if (aNum !== bNum) return aNum - bNum;

            // Następnie według statusu (completed przed in_progress)
            if (a.taskStatus === 'completed' && b.taskStatus !== 'completed') return -1;
            if (a.taskStatus !== 'completed' && b.taskStatus === 'completed') return 1;

            // Na końcu według znacznika czasu
            return a.timestamp - b.timestamp;
        });

        // Mapowanie zadań o tej samej nazwie
        const taskNameMap = {};
        const processedMessages = missionMessages.map(item => {
            const taskNameMatch = item.message.match(/Zadanie "(.*?)" jest teraz/);

            if (taskNameMatch && taskNameMatch[1]) {
                const taskName = taskNameMatch[1];

                // Jeśli zadanie o tej samej nazwie już wystąpiło
                if (taskNameMap[taskName]) {
                    taskNameMap[taskName]++;

                    // Użyj numeru z ID zadania lub przypisz kolejny numer
                    const taskNum = item.taskNum || taskNameMap[taskName];

                    // Zastąp nazwę zadania w komunikacie, dodając numer
                    const newMessage = item.message.replace(
                        `Zadanie "${taskName}"`,
                        `Zadanie "${taskName} (${taskNum})"`
                    );

                    return {
                        ...item,
                        message: newMessage
                    };
                } else {
                    taskNameMap[taskName] = 1;

                    // Jeśli to pierwsze wystąpienie, ale mamy numer, też go dodaj
                    if (item.taskNum) {
                        const newMessage = item.message.replace(
                            `Zadanie "${taskName}"`,
                            `Zadanie "${taskName} (${item.taskNum})"`
                        );

                        return {
                            ...item,
                            message: newMessage
                        };
                    }
                }
            }

            return item;
        });

        // Wyświetl wszystkie komunikaty jako osobne powiadomienia
        processedMessages.forEach(item => {
            this.showNotification(item.message, 'success');
        });
    }
}

// Utwórz instancję zarządcy misji i udostępnij ją globalnie
window.missionManager = new MissionManager();

// Zastąp starą funkcję startMission nową funkcją zarządcy misji
window.startMission = function (params) {
    return window.missionManager.startMission(params);
};

const missionManager = new MissionManager();