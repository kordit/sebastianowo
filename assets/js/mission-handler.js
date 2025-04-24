/**
 * Funkcje do obsługi misji w interfejsie użytkownika
 */

/**
 * Funkcja uruchamiająca misję z parametrami od NPC
 * @param {Object} params Parametry misji
 */
async function startMission(params) {
    console.log('🚀 Uruchamianie misji:', params);

    try {
        // Sprawdź czy mamy ID misji
        if (!params.mission_id) {
            throw new Error('Brak ID misji');
        }

        // Identyfikator NPC, który daje misję
        const npcId = params.npc_id || document.getElementById('npcdatamanager')?.dataset?.id;
        if (!npcId) {
            console.warn('Uwaga: Nie określono NPC, który daje misję');
        }

        // Pokaż loader lub komunikat
        showPopup('Trwa przypisywanie misji...', 'info');

        // Wywołaj endpoint AJAX
        const response = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
            action: 'assign_mission_to_user',
            mission_id: params.mission_id,
            npc_id: npcId
        });

        // Sprawdź odpowiedź
        if (response.success) {
            // Misja została przypisana pomyślnie
            const successMessage = params.success_message || 'Otrzymałeś nową misję!';

            // Opcjonalne: dodaj przedmioty związane z misją
            if (params.item_id && params.item_quantity) {
                try {
                    await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                        action: 'handle_item_action',
                        item_id: params.item_id,
                        quantity: params.item_quantity || 1,
                        operation: 'give'
                    });
                } catch (itemError) {
                    console.error('Błąd podczas dodawania przedmiotu:', itemError);
                }
            }

            // Wyświetl komunikat o sukcesie
            if (params.show_confirmation !== false) {
                // Sprawdź czy używamy customowego popup'a czy standardowego
                if (typeof createCustomPopup === 'function' && params.use_custom_popup) {
                    await createCustomPopup({
                        imageId: params.popup_image_id || 54,
                        header: successMessage,
                        description: `${response.data.mission_title} została dodana do twoich aktywnych misji.`,
                        link: '/zadania/',
                        linkLabel: 'Przejdź do misji'
                    });
                } else {
                    showPopup(`${successMessage} - ${response.data.mission_title}`, 'success');
                }
            }

            // Opcjonalne: automatycznie zakończ pierwszy task jeśli jest to odbiór przedmiotu
            if (response.data.task_id && params.auto_complete_first_task) {
                setTimeout(async () => {
                    await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                        action: 'complete_mission_task',
                        mission_id: params.mission_id,
                        task_id: response.data.task_id
                    });
                }, 1000);
            }

            // Opcjonalnie przekieruj użytkownika
            if (params.redirect_after && response.data.redirect) {
                window.location.href = response.data.redirect;
            }

            return true;
        } else {
            // Wystąpił błąd
            const errorMessage = response.data?.message || 'Wystąpił błąd podczas przypisywania misji';
            showPopup(errorMessage, 'error');
            return false;
        }
    } catch (error) {
        console.error('Błąd podczas uruchamiania misji:', error);
        showPopup(`Wystąpił błąd: ${error.message || 'Nieznany błąd'}`, 'error');
        return false;
    }
}

/**
 * Funkcja aktualizująca status zadania misji
 * @param {Object} params Parametry zadania
 */
async function completeMissionTask(params) {
    console.log('✅ Oznaczanie zadania jako ukończone:', params);

    try {
        if (!params.mission_id || !params.task_id) {
            throw new Error('Brak wymaganych parametrów: mission_id lub task_id');
        }

        const response = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
            action: 'complete_mission_task',
            mission_id: params.mission_id,
            task_id: params.task_id
        });

        if (response.success) {
            if (params.show_confirmation !== false) {
                showPopup('Zadanie zostało ukończone!', 'success');
            }

            // Jeśli wszystkie zadania zostały zakończone, wyświetl specjalny komunikat
            if (response.data.all_tasks_completed) {
                setTimeout(() => {
                    showPopup('Wszystkie zadania misji zostały ukończone!', 'success');
                }, 1500);
            }

            return true;
        } else {
            const errorMessage = response.data?.message || 'Wystąpił błąd podczas aktualizacji zadania';
            showPopup(errorMessage, 'error');
            return false;
        }
    } catch (error) {
        console.error('Błąd podczas aktualizacji zadania misji:', error);
        showPopup(`Wystąpił błąd: ${error.message || 'Nieznany błąd'}`, 'error');
        return false;
    }
}

// Eksportuj funkcje - będą dostępne globalnie
window.startMission = startMission;
window.completeMissionTask = completeMissionTask;
