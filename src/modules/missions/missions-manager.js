/**
 * Entry point dla modułu Missions
 */
import { userAPI } from '../../core/api-client';

// Stan misji użytkownika
let userMissions = [];

/**
 * Inicjalizacja modułu misji
 * @param {string} missionsListId - ID elementu HTML dla listy misji
 */
export function initMissions(missionsListId = 'missions-list') {
    // Znajdź element listy misji w DOM
    const missionsList = document.getElementById(missionsListId);

    // Jeśli element nie istnieje, nie inicjalizuj modułu
    if (!missionsList) return;

    // Pobierz misje użytkownika
    loadUserMissions()
        .then(missions => {
            renderMissionsList(missions, missionsList);
        })
        .catch(error => {
            console.error('Błąd podczas ładowania misji:', error);
        });
}

/**
 * Ładuje misje użytkownika z serwera
 * @param {number} userId - ID użytkownika (opcjonalnie)
 * @returns {Promise} - Promise z danymi misji
 */
export async function loadUserMissions(userId = null) {
    try {
        // Użycie API z Axios
        userMissions = await userAPI.getUserMissions(userId);
        return userMissions;
    } catch (error) {
        console.error('Błąd podczas ładowania misji użytkownika:', error);
        throw error;
    }
}

/**
 * Aktualizuje status misji
 * @param {string|number} missionId - ID misji
 * @param {string} status - Nowy status misji
 * @param {number} progress - Postęp misji (0-100)
 * @returns {Promise} - Promise z wynikiem operacji
 */
export async function updateMissionStatus(missionId, status, progress = null) {
    try {
        // Znajdź misję w bieżących misjach użytkownika
        const existingMission = userMissions.find(m => m.mission_id == missionId);

        // Przygotuj dane do aktualizacji
        const missionData = existingMission ? { ...existingMission } : { mission_id: missionId };

        // Aktualizuj status
        missionData.status = status;

        // Aktualizuj postęp, jeśli podany
        if (progress !== null) {
            missionData.progress = progress;
        }

        // Zaktualizuj na serwerze
        await userAPI.updateUserMission(missionData);

        // Odśwież dane misji
        return await loadUserMissions();
    } catch (error) {
        console.error('Błąd podczas aktualizacji statusu misji:', error);
        throw error;
    }
}

/**
 * Renderuje listę misji w elemencie DOM
 * @param {Array} missions - Lista misji
 * @param {HTMLElement} container - Element DOM, w którym wyświetlić misje
 */
function renderMissionsList(missions, container) {
    if (!container) return;

    // Jeśli nie ma misji, wyświetl komunikat
    if (!missions || missions.length === 0) {
        container.innerHTML = '<div class="no-missions">Brak dostępnych misji</div>';
        return;
    }

    // Generuj HTML dla każdej misji
    const missionsHTML = missions.map(mission => `
    <div class="mission-item" data-mission-id="${mission.mission_id}">
      <div class="mission-header">
        <h3 class="mission-title">${mission.title || 'Misja #' + mission.mission_id}</h3>
        <span class="mission-status mission-status-${mission.status || 'new'}">${formatStatus(mission.status)}</span>
      </div>
      <div class="mission-description">${mission.description || 'Brak opisu'}</div>
      ${mission.progress ? `
        <div class="mission-progress-bar">
          <div class="progress-fill" style="width: ${mission.progress}%"></div>
          <span class="progress-text">${mission.progress}%</span>
        </div>
      ` : ''}
      <div class="mission-actions">
        <button class="btn-mission-action" data-action="start" ${mission.status === 'in_progress' ? 'disabled' : ''}>
          ${mission.status === 'new' ? 'Rozpocznij' : 'Wznów'}
        </button>
        <button class="btn-mission-action" data-action="complete" ${mission.status === 'completed' ? 'disabled' : ''}>
          Zakończ
        </button>
      </div>
    </div>
  `).join('');

    // Wstaw HTML do kontenera
    container.innerHTML = missionsHTML;

    // Dodaj obsługę zdarzeń dla przycisków
    addMissionButtonsEventListeners(container);
}

/**
 * Dodaje obsługę zdarzeń do przycisków misji
 * @param {HTMLElement} container - Kontener z misjami
 */
function addMissionButtonsEventListeners(container) {
    // Znajdź wszystkie przyciski akcji dla misji
    const buttons = container.querySelectorAll('.btn-mission-action');

    // Dodaj obsługę zdarzeń do każdego przycisku
    buttons.forEach(button => {
        button.addEventListener('click', event => {
            // Pobierz akcję i ID misji
            const action = button.getAttribute('data-action');
            const missionItem = button.closest('.mission-item');
            const missionId = missionItem.getAttribute('data-mission-id');

            // Wykonaj odpowiednią akcję
            if (action === 'start') {
                updateMissionStatus(missionId, 'in_progress')
                    .then(() => {
                        // Oznacz przycisk jako niedostępny
                        button.disabled = true;
                        // Zaktualizuj wygląd misji
                        const statusElement = missionItem.querySelector('.mission-status');
                        statusElement.className = 'mission-status mission-status-in_progress';
                        statusElement.textContent = formatStatus('in_progress');
                    });
            } else if (action === 'complete') {
                updateMissionStatus(missionId, 'completed', 100)
                    .then(() => {
                        // Oznacz przycisk jako niedostępny
                        button.disabled = true;
                        // Zaktualizuj wygląd misji
                        const statusElement = missionItem.querySelector('.mission-status');
                        statusElement.className = 'mission-status mission-status-completed';
                        statusElement.textContent = formatStatus('completed');

                        // Aktualizuj pasek postępu
                        const progressBar = missionItem.querySelector('.mission-progress-bar');
                        if (progressBar) {
                            const progressFill = progressBar.querySelector('.progress-fill');
                            const progressText = progressBar.querySelector('.progress-text');
                            progressFill.style.width = '100%';
                            progressText.textContent = '100%';
                        }
                    });
            }
        });
    });
}

/**
 * Formatuje status misji na przyjazny dla użytkownika tekst
 * @param {string} status - Status misji
 * @returns {string} - Sformatowany tekst statusu
 */
function formatStatus(status) {
    const statusMap = {
        'new': 'Nowa',
        'in_progress': 'W trakcie',
        'completed': 'Ukończona',
        'failed': 'Nie udało się'
    };

    return statusMap[status] || status;
}

// Eksportuj publiczne API modułu
export default {
    init: initMissions,
    loadMissions: loadUserMissions,
    updateMissionStatus
};
