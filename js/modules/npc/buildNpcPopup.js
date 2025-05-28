/**
 * Moduł obsługi dialogów z postaciami NPC
 *
 * Ten plik zawiera funkcje odpowiedzialne za wyświetlanie i obsługę 
 * interaktywnych dialogów z postaciami NPC w grze.
 */

/**
 * Buduje i wyświetla popup z dialogiem NPC na pełnym ekranie
 * 
 * @param {Object} npcData - Dane NPC otrzymane z API
 * @param {number} userId - ID zalogowanego użytkownika
 */
const buildNpcPopup = (npcData, userId) => {
    // Sprawdź czy dane NPC istnieją
    if (!npcData) {
        console.error('Brak danych NPC do wyświetlenia dialogu.');
        return;
    }

    // Utwórz kontener dla popupu jeśli nie istnieje lub usuń istniejący
    let popupContainer = document.getElementById('npc-dialog-popup');
    if (popupContainer) {
        popupContainer.remove();
    }

    // Utwórz nowy kontener dla popupu
    popupContainer = document.createElement('div');
    popupContainer.id = 'npc-dialog-popup';
    popupContainer.className = 'npc-dialog-popup';

    // Logowanie struktury dialogu w celach diagnostycznych

    // Utwórz strukturę popupu z układem pełnoekranowym
    const popupContent = `
        <div class="npc-dialog-container">
            <div class="npc-dialog-header">
                <h3>${npcData.title || npcData.name}</h3>
                <button class="npc-dialog-close">&times;</button>
            </div>
            <div class="npc-dialog-body">
                <div class="npc-dialog-content">
                    <div class="npc-dialog-bubble">
                        <div class="npc-dialog-speaker">${npcData.name} mówi:</div>
                        <div class="npc-dialog-text">${formatDialogText(npcData.dialog?.text || npcData.dialog?.question || '')}</div>
                    </div>
                    <div class="npc-dialog-answers" data-dialog-id="${npcData.dialog?.id || npcData.dialog?.id_pola || ''}">
                        ${buildAnswerButtons(npcData.dialog?.answers || npcData.dialog?.anwsers || [])}
                    </div>
                </div>
                <div class="npc-dialog-avatar">
                    ${npcData.thumbnail_url ?
            `<img src="${npcData.thumbnail_url}" alt="${npcData.name}" />` :
            '<div class="npc-avatar-placeholder"></div>'}
                </div>
            </div>
        </div>
    `;

    // Dodaj zawartość do kontenera
    popupContainer.innerHTML = popupContent;

    // Dodaj popup do body
    document.body.appendChild(popupContainer);

    // Dodaj obsługę zdarzeń
    setupEventListeners(popupContainer, npcData, userId);

    // Pokaż popup z animacją
    setTimeout(() => {
        popupContainer.classList.add('active');
    }, 10);
};

/**
 * Formatuje tekst dialogu (obsługuje znaki nowej linii, HTML, itp.)
 * 
 * @param {string} text - Tekst dialogu do sformatowania
 * @return {string} - Sformatowany tekst HTML
 */
const formatDialogText = (text) => {
    if (!text) return '';

    // Usuń niepotrzebne cudzysłowy na początku i końcu (jeśli istnieją)
    let formattedText = text.trim();
    if ((formattedText.startsWith('"') && formattedText.endsWith('"')) ||
        (formattedText.startsWith("'") && formattedText.endsWith("'"))) {
        formattedText = formattedText.substring(1, formattedText.length - 1);
    }

    // Zamień podwójne znaki nowej linii na znacznik podziału akapitu
    formattedText = formattedText.replace(/\n\s*\n/g, '</p><p>');

    // Zamień pojedyncze znaki nowej linii na <br>
    formattedText = formattedText.replace(/\n/g, '<br>');

    // Dodaj znaczniki akapitów jeśli ich nie ma
    if (!formattedText.startsWith('<p>')) {
        formattedText = '<p>' + formattedText;
    }
    if (!formattedText.endsWith('</p>')) {
        formattedText = formattedText + '</p>';
    }

    // Usuń puste akapity
    formattedText = formattedText.replace(/<p>\s*<\/p>/g, '');

    return formattedText;
};

/**
 * Buduje przyciski odpowiedzi na podstawie danych
 * 
 * @param {Array} answers - Tablica z możliwymi odpowiedziami
 * @return {string} - HTML z przyciskami odpowiedzi
 */
const buildAnswerButtons = (answers) => {
    if (!answers || !answers.length) {
        return '<button class="npc-answer-button" data-go-to="close" data-answer-id="close" data-answer-index="-1">Zamknij</button>';
    }

    return answers.map((answer, index) => {
        // Obsługa różnych formatów dialogów (zarówno z ACF jak i po uproszczeniu)
        const goToId = answer.next_dialog || answer.go_to_id || 'close';
        const buttonClass = answer.type_anwser ? 'npc-answer-button special' : 'npc-answer-button';
        const answerText = answer.text || answer.anwser_text || 'Dalej';
        const answerId = answer.id || `answer-${index}`;

        return `
            <button class="${buttonClass}" 
                   data-go-to="${goToId}" 
                   data-answer-id="${answerId}" 
                   data-answer-index="${index}">
                ${answerText}
            </button>
        `;
    }).join('');
};

/**
 * Konfiguruje obsługę zdarzeń dla popupu dialogowego
 * 
 * @param {HTMLElement} container - Kontener popupu
 * @param {Object} npcData - Dane NPC
 * @param {number} userId - ID zalogowanego użytkownika
 */
const setupEventListeners = (container, npcData, userId) => {
    // Obsługa przycisku zamknięcia
    const closeButton = container.querySelector('.npc-dialog-close');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            closeNpcDialog(container);
        });
    }

    // Obsługa klawisza ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeNpcDialog(container);
        }
    });

    // Obsługa przycisków odpowiedzi
    const answerButtons = container.querySelectorAll('.npc-answer-button');
    answerButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const goToId = button.getAttribute('data-go-to');
            const answerId = button.getAttribute('data-answer-id');
            const answerIndex = button.getAttribute('data-answer-index');

            if (goToId === 'close') {
                closeNpcDialog(container);
                return;
            }

            // Pobierz ID aktualnego dialogu
            const currentDialogId = container.querySelector('.npc-dialog-answers').getAttribute('data-dialog-id');

            // Obsługa przejścia do kolejnej części dialogu
            handleDialogNavigation(goToId, npcData, userId, {
                answerId: answerId,
                answerIndex: answerIndex,
                currentDialogId: currentDialogId
            });
        });
    });
};

/**
 * Obsługuje przejście do kolejnej części dialogu
 * 
 * @param {string} goToId - ID następnej części dialogu
 * @param {Object} npcData - Dane NPC
 * @param {number} userId - ID zalogowanego użytkownika
 * @param {Object} answerData - Dane o wybranej odpowiedzi
 */
const handleDialogNavigation = async (goToId, npcData, userId, answerData = {}) => {
    if (goToId === '0' || !goToId) {
        console.log('Dialog: zamykanie dialogu po kliknięciu przycisku z go_to_id =', goToId);
        closeNpcDialog(document.getElementById('npc-dialog-popup'));
        return;
    }

    try {
        // Pobranie aktualnego URL strony
        const currentUrl = window.location.href;

        // Ekstrakcja danych strony z atrybutów danych
        const pageData = window.pageData || {};

        // Przygotuj dane do wysłania
        const requestData = {
            npc_id: npcData.id,
            dialog_id: goToId,
            current_url: currentUrl,
            user_id: userId,
            page_data: pageData
        };

        // Dodaj dane o odpowiedzi jeśli dostępne
        if (answerData) {
            requestData.answer_id = answerData.answerId;
            requestData.answer_index = answerData.answerIndex;
            requestData.current_dialog_id = answerData.currentDialogId;
        }

        // Pobranie kolejnej części dialogu z API
        const response = await axios({
            method: 'POST',
            url: '/wp-json/game/v1/dialog',
            data: requestData
        });

        const dialogData = response?.data;
        if (!dialogData || !dialogData.success) {
            console.error("Brak danych dialogu w odpowiedzi:", response);
            return;
        }

        // Dostosowanie struktury danych do formatu używanego przez updateDialogContent
        const formattedData = {
            dialog: dialogData.dialog,
            name: dialogData.npc?.name || npcData.name,
            user_id: userId,
            id: npcData.id,
            thumbnail_url: dialogData.npc?.image || npcData.thumbnail_url
        };

        // Sprawdź, czy otrzymaliśmy powiadomienie o transakcji
        if (dialogData.notification) {
            console.log('Dialog: otrzymano powiadomienie z API:', dialogData.notification);

            // Wyświetl powiadomienie, jeśli jest dostępny system powiadomień
            if (window.gameNotifications) {
                console.log('Dialog: wyświetlanie powiadomienia za pomocą gameNotifications');
                window.gameNotifications.show(
                    dialogData.notification.message,
                    dialogData.notification.status === 'error' ? 'error' :
                        dialogData.notification.status === 'bad' ? 'bad' : 'success'
                );
            } else if (window.showPopup) {
                // Spadek do starego systemu powiadomień, jeśli dostępny
                console.log('Dialog: wyświetlanie powiadomienia za pomocą showPopup');
                window.showPopup(
                    dialogData.notification.message,
                    dialogData.notification.status === 'bad' ? 'error' :
                        dialogData.notification.status === 'error' ? 'error' : 'success'
                );
            } else {
                console.warn('Dialog: brak systemu powiadomień do wyświetlenia informacji:', dialogData.notification);
            }

            // Odśwież dane interfejsu użytkownika po transakcji
            if (window.UIHelpers && typeof window.UIHelpers.refreshUserData === 'function') {
                console.log('Dialog: odświeżanie danych użytkownika po transakcji');
                try {
                    await window.UIHelpers.refreshUserData();
                } catch (refreshError) {
                    console.error('Błąd podczas odświeżania danych użytkownika:', refreshError);
                }
            }
        }

        // Aktualizacja dialogu
        updateDialogContent(formattedData);

    } catch (error) {
        console.error("Błąd podczas pobierania dialogu:", error);
        // Wyświetl komunikat o błędzie w dialogu
        const dialogText = document.querySelector('.npc-dialog-text');
        if (dialogText) {
            dialogText.innerHTML = '<p class="error">Wystąpił błąd podczas ładowania dialogu.</p>';
        }
    }
};

/**
 * Aktualizuje zawartość dialogu bez tworzenia nowego popupu
 * 
 * @param {Object} npcData - Nowe dane dialogu
 */
const updateDialogContent = (npcData) => {
    const dialogText = document.querySelector('.npc-dialog-text');
    const answersContainer = document.querySelector('.npc-dialog-answers');
    const speakerName = document.querySelector('.npc-dialog-speaker');

    // Logowanie struktury dialogu w celach diagnostycznych

    if (dialogText && npcData.dialog) {
        dialogText.innerHTML = formatDialogText(npcData.dialog.text || npcData.dialog.question || '');
    }

    if (speakerName && npcData.name) {
        speakerName.textContent = `${npcData.name} mówi:`;
    }

    if (answersContainer && npcData.dialog) {
        answersContainer.innerHTML = buildAnswerButtons(npcData.dialog.answers || npcData.dialog.anwsers || []);
        answersContainer.setAttribute('data-dialog-id', npcData.dialog.id || npcData.dialog.id_pola || '');

        // Dodaj nowe event listenery
        const newAnswerButtons = answersContainer.querySelectorAll('.npc-answer-button');
        newAnswerButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const goToId = button.getAttribute('data-go-to');
                const answerId = button.getAttribute('data-answer-id');
                const answerIndex = button.getAttribute('data-answer-index');

                if (goToId === 'close') {
                    closeNpcDialog(document.getElementById('npc-dialog-popup'));
                    return;
                }

                // Pobierz ID aktualnego dialogu
                const currentDialogId = answersContainer.getAttribute('data-dialog-id');

                handleDialogNavigation(goToId, npcData, npcData.user_id, {
                    answerId: answerId,
                    answerIndex: answerIndex,
                    currentDialogId: currentDialogId
                });
            });
        });
    }
};

/**
 * Zamyka dialog NPC z animacją
 * 
 * @param {HTMLElement} container - Kontener dialogu do zamknięcia
 */
const closeNpcDialog = (container) => {
    if (!container) return;

    // Usuń event listener dla klawisza ESC
    document.removeEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeNpcDialog(container);
        }
    });

    container.classList.remove('active');
    container.classList.add('closing');

    // Odśwież dane interfejsu użytkownika po zamknięciu dialogu
    if (window.UIHelpers && typeof window.UIHelpers.refreshUserData === 'function') {
        console.log('Dialog: odświeżanie danych użytkownika po zamknięciu dialogu');
        window.UIHelpers.refreshUserData().catch(error => {
            console.error('Błąd podczas odświeżania danych użytkownika:', error);
        });
    }

    // Usuń element po zakończeniu animacji
    setTimeout(() => {
        container.remove();
    }, 300); // Czas trwania animacji
};

// Eksportuj funkcję do użycia w innych modułach
window.buildNpcPopup = buildNpcPopup;