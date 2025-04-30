/**
 * Moduł obsługi NPC
 * 
 * Plik zawiera funkcje do obsługi interakcji z NPC, dialogów, popupów itp.
 * Jest używany na wszystkich stronach, gdzie występują NPC.
 * Bazuje na strukturze pól ACF zdefiniowanych w register_fields.php.
 */

/**
 * Funkcja pomocnicza do aktualizacji pól ACF z interfejsem graficznym
 * Jest używana jako backup, jeśli główna funkcja nie jest dostępna
 * 
 * @param {Object} fields - Pola do aktualizacji w formacie {nazwa_pola: wartość}
 * @param {Array<string>} parentSelectors - Selektory kontenerów nadrzędnych
 * @param {string|null} customMsg - Niestandardowy komunikat podczas aktualizacji
 * @returns {Promise<Object>} - Wynik operacji
 */
async function localUpdateACFFieldsWithGui(fields, parentSelectors = ['body'], customMsg = null) {
    try {
        // Pokaż wskaźnik ładowania w każdym selektorze nadrzędnym
        document.querySelectorAll('.bar-game').forEach(wrapper => {
            wrapper.innerHTML = '';
            const loadingBar = document.createElement('div');
            loadingBar.classList.add('bar');
            loadingBar.style.width = '100%';
            loadingBar.style.background = '#00aaff';
            loadingBar.style.animation = 'pulse 1.5s infinite';

            const loadingText = document.createElement('div');
            loadingText.classList.add('bar-value');
            loadingText.textContent = customMsg || 'Zapisywanie danych...';

            wrapper.appendChild(loadingBar);
            wrapper.appendChild(loadingText);
        });

        // Wywołaj aktualizację poprzez AJAX
        const response = await AjaxHelper.sendRequest((window.global && window.global.ajaxurl) || window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
            action: 'update_acf_fields',  // Akcja zdefiniowana w acf_ajax_handlers.php
            nonce: (window.global && window.global.dataManagerNonce) || (window.gameData && window.gameData.dataManagerNonce) || '',
            fields: JSON.stringify(fields),
            request_id: Date.now() + Math.random().toString(36).substring(2, 9)
        });

        if (!response.success) {
            throw new Error(response.data?.message || "Nieznany błąd serwera");
        }

        // Po pomyślnej aktualizacji odśwież paski statusu
        document.querySelectorAll('.bar-game').forEach(wrapper => {
            const datasetExists = wrapper.dataset && wrapper.dataset.barMax && wrapper.dataset.barCurrent;

            if (datasetExists) {
                wrapper.innerHTML = '';
                const max = parseFloat(wrapper.dataset.barMax);
                const current = parseFloat(wrapper.dataset.barCurrent);
                const color = wrapper.dataset.barColor || '#4caf50';
                const type = wrapper.dataset.barType || 'default';
                const percentage = (current / max) * 100;

                const bar = document.createElement('div');
                bar.classList.add('bar');
                bar.style.width = percentage + '%';
                bar.style.background = color;

                const barValue = document.createElement('div');
                barValue.classList.add('bar-value');
                barValue.innerHTML = `<span class="ud-stats-${type}">${current}</span> / ${max}`;

                wrapper.appendChild(bar);
                wrapper.appendChild(barValue);
            }
        });

        return response;
    } catch (error) {
        const errorMsg = error && error.message ? error.message : String(error);
        console.error("❌ Błąd aktualizacji bazy danych:", errorMsg);
        throw error;
    }
}

/**
 * Pobiera dialogi NPC z serwera
 * 
 * @param {Object} npcData - Dane NPC
 * @param {string|null} idConversation - ID konwersacji
 * @param {Object|null} conditions - Warunki konwersacji
 * @param {number|string|null} userId - ID użytkownika
 * @returns {Promise<Object>} - Dane dialogu
 */
function fetchDialogue(npcData, idConversation, conditions, userId) {
    const data = {
        action: 'get_dialogue',  // Akcja zdefiniowana w npc_dialogs.php
        npc_data: JSON.stringify(npcData),
        user_id: userId
    };

    if (idConversation) data.id_conversation = idConversation;
    if (conditions) data.conditions = JSON.stringify(conditions);

    return AjaxHelper.sendRequest((window.global && window.global.ajaxurl) || window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', data)
        .then(response => response.data);
}

/**
 * Tworzy popup z NPC
 * 
 * @param {Object} npcData - Dane NPC
 * @param {number|string|null} userId - ID użytkownika
 */
function buildNpcPopup(npcData, userId) {
    if (!userId) {
        const userElem = document.getElementById('get-user-id');
        userId = userElem ? userElem.dataset.id : null;
    }

    // Usuń istniejący popup jeśli istnieje
    const existingPopup = document.getElementById('npc-popup');
    if (existingPopup) existingPopup.remove();

    // Utwórz nowy popup
    const popupContainer = document.createElement('div');
    popupContainer.id = 'npc-popup';
    popupContainer.className = 'controler-popup';
    popupContainer.npcData = npcData;

    setTimeout(() => popupContainer.classList.add('active'), 100);

    // Dodaj zdjęcie NPC jeśli istnieje
    if (npcData.npc_thumbnail) {
        const img = document.createElement('img');
        img.src = npcData.npc_thumbnail;
        img.alt = npcData.npc_name || 'NPC Image';
        img.className = 'npc-thumbnail';
        img.id = 'npcdatamanager';
        img.dataset.id = npcData.npc_id;
        popupContainer.appendChild(img);
    }

    // Utwórz kontener konwersacji
    const conversationWrapper = document.createElement('div');
    conversationWrapper.className = 'npc-conversation-wrapper';

    if (npcData.npc_post_title) {
        const header = document.createElement('h2');
        header.innerHTML = npcData.npc_post_title + ' mówi:';
        conversationWrapper.appendChild(header);
    }

    // Oddzielny kontener dla zawartości dialogu
    const dialogueContent = document.createElement('div');
    dialogueContent.className = 'npc-dialogue-content';
    conversationWrapper.appendChild(dialogueContent);

    popupContainer.appendChild(conversationWrapper);
    document.body.appendChild(popupContainer);

    /**
     * Renderuje zawartość dialogu
     * 
     * @param {Object} dialogue - Obiekt dialogu
     */
    function renderDialogueContent(dialogue) {
        dialogueContent.innerHTML = '';

        // Wyświetl pytanie NPC
        if (dialogue.question) {
            const questionEl = document.createElement('div');
            questionEl.className = 'npc-question';
            questionEl.innerHTML = dialogue.question;
            dialogueContent.appendChild(questionEl);
        }

        // Wyświetl możliwe odpowiedzi
        if (dialogue.answers && dialogue.answers.length) {
            const answersContainer = document.createElement('div');
            answersContainer.className = 'npc-answers';

            dialogue.answers.forEach(answer => {
                const btn = document.createElement('button');
                btn.className = 'npc-answer-btn';
                btn.innerHTML = answer.anwser_text;

                if (answer.type_anwser !== undefined && answer.type_anwser !== false) {
                    btn.setAttribute('data-type-anwser', JSON.stringify(answer.type_anwser));
                }

                // Dodaj wszystkie atrybuty odpowiedzi jako data-atrybuty
                Object.keys(answer).forEach(key => {
                    if (key !== 'type_anwser') {
                        btn.dataset[key] = answer[key];
                    }
                });

                // Obsługa kliknięcia przycisku odpowiedzi
                btn.addEventListener('click', async () => {
                    // Zapobiegaj wielokrotnym kliknięciom
                    if (btn.disabled) return;
                    btn.disabled = true;
                    btn.classList.add('processing');

                    try {
                        if (btn.hasAttribute('data-type-anwser')) {
                            const typeAnwser = JSON.parse(btn.getAttribute('data-type-anwser'));
                            let errorOccurred = false;

                            // Podmień funkcję showPopup, aby wykryć błędy
                            const originalShowPopup = window.showPopup;
                            window.showPopup = function (message, state) {
                                if (state === 'error') errorOccurred = true;
                                originalShowPopup(message, state);
                            };

                            // Wykonaj akcje związane z odpowiedzią
                            await handleAnswer(typeAnwser);
                            window.showPopup = originalShowPopup;

                            if (errorOccurred) {
                                btn.disabled = false;
                                btn.classList.remove('processing');
                                return;
                            }
                        }

                        // Przejdź do następnego dialogu lub zakończ rozmowę
                        if (answer.go_to_id && answer.go_to_id !== "0") {
                            dialogueContent.innerHTML = '<div class="loader">Myśli...</div>';
                            try {
                                const newData = await fetchDialogue(npcData, answer.go_to_id, getPageData(), userId);
                                if (newData && newData.conversation) {
                                    renderDialogueContent(newData.conversation);
                                }
                            } catch (err) {
                                console.error(err);
                                // Przywróć przycisk w przypadku błędu
                                btn.disabled = false;
                                btn.classList.remove('processing');
                            }
                        } else {
                            // Wykonaj dodatkowe funkcje i zamknij popup
                            if (popupContainer.dataset.functions) {
                                const functionsList = JSON.parse(popupContainer.dataset.functions);
                                runFunctionNPC(functionsList);
                            }
                            popupContainer.classList.remove('active');
                            setTimeout(() => popupContainer.remove(), 300);
                        }
                    } catch (error) {
                        console.error('Błąd podczas przetwarzania przycisku:', error);
                        // Przywróć przycisk w przypadku błędu
                        btn.disabled = false;
                        btn.classList.remove('processing');
                    }
                });

                answersContainer.appendChild(btn);
            });

            dialogueContent.appendChild(answersContainer);
        }
    }

    // Renderuj początkowy dialog jeśli istnieje
    if (npcData.conversation) {
        renderDialogueContent(npcData.conversation);
    }
}

/**
 * Obsługuje odpowiedź gracza i wykonuje związane z nią akcje
 * 
 * @param {Object|Event} input - Dane odpowiedzi lub event kliknięcia
 * @returns {Promise<void>}
 */
async function handleAnswer(input) {
    // Pozyskaj dane z wejścia
    const dataset = input && input.currentTarget ? input.currentTarget.dataset : input;
    if (!dataset) return;

    // Parsuj dane JSON jeśli istnieją
    const answerObj = {};
    Object.keys(dataset).forEach(key => {
        let value = dataset[key];
        if (typeof value === 'string' && (value.startsWith('{') || value.startsWith('['))) {
            try {
                value = JSON.parse(value);
            } catch (e) { }
        }
        answerObj[key] = value;
    });

    let message = null;
    let popupstate = null;

    if (!dataset) return;

    const transactions = Object.values(dataset);

    // Kontenery na różne typy transakcji
    const transactionsToExecute = []; // Transakcje walutowe (backpack.gold, backpack.cigarettes)
    const functionsToExecute = []; // Funkcje do wykonania
    const relationsToUpdate = []; // Relacje z NPC
    const itemsToManage = []; // Operacje na przedmiotach
    const missionsToStart = []; // Misje do uruchomienia
    const skillsToUpdate = []; // Umiejętności do aktualizacji
    const expRepToUpdate = []; // Doświadczenie i reputacja
    const areasToUnlock = []; // Rejony do odblokowania
    const areasToChange = []; // Zmiana aktualnego rejonu

    try {
        // Pobierz aktualne dane użytkownika
        const userFields = await (typeof window.fetchLatestACFFields === 'function' ?
            window.fetchLatestACFFields() : // Użyj globalnej funkcji jeśli istnieje
            await AjaxHelper.sendRequest((window.global && window.global.ajaxurl) || window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
                action: 'get_acf_fields', // Zdefiniowana w acf_ajax_handlers.php
                nonce: (window.global && window.global.dataManagerNonce) || (window.gameData && window.gameData.dataManagerNonce) || ''
            }).then(response => {
                if (!response.success) throw new Error(response || "Nieznany błąd serwera");
                return response.data.fields;
            }).catch(error => {
                console.error("❌ Błąd pobierania bazy ACF:", error);
                return {};
            }));

        // FAZA 1: Walidacja i podział transakcji na typy
        for (const singletransaction of transactions) {
            // Obsługa transakcji walutowych (złoto, papierosy)
            if (singletransaction.acf_fc_layout === "transaction") {
                const bagType = singletransaction.backpack; // 'gold' lub 'cigarettes' zgodnie z register_fields.php
                const value = parseInt(singletransaction.value, 10);

                // Sprawdzenie czy gracz ma wystarczająco środków dla wydatków
                if (value < 0) {
                    // Mapowanie nazw z UI na nazwy pól w bazie danych
                    const fieldMapping = {
                        'gold': 'gold',            // Zgodnie z register_fields.php: field_6793e6a783c33
                        'papierosy': 'cigarettes'  // Zgodnie z register_fields.php: field_6793e69b83c32
                    };

                    // Pobierz właściwą nazwę pola
                    const fieldName = fieldMapping[bagType] || bagType;

                    // Sprawdź wartość w userFields.backpack (zgodnie ze strukturą ACF)
                    const currentValue = userFields.backpack && userFields.backpack[fieldName] !== undefined ?
                        parseInt(userFields.backpack[fieldName], 10) : 0;

                    if (currentValue < Math.abs(value)) {
                        let friendly;
                        switch (bagType) {
                            case 'gold': friendly = 'złote'; break;
                            case 'papierosy': friendly = 'szlug'; break;
                            default: friendly = bagType;
                        }

                        const errorMessage = `Nie masz wystarczająco dużo ${friendly}`;
                        showPopup(errorMessage, 'error');
                        return; // Przerwij całą operację
                    }
                }

                // Jeśli walidacja przeszła, dodaj do transakcji do wykonania
                transactionsToExecute.push({
                    bagType,
                    value,
                    friendly: (() => {
                        switch (bagType) {
                            case 'gold': return 'złote';
                            case 'papierosy': return 'szlug';
                            default: return bagType;
                        }
                    })()
                });
            }
            // Obsługa funkcji do wykonania
            else if (singletransaction.acf_fc_layout === "function") {
                functionsToExecute.push(singletransaction);
            }
            // Obsługa relacji z NPC
            else if (singletransaction.acf_fc_layout === "relation") {
                relationsToUpdate.push(singletransaction);
            }
            // Obsługa misji
            else if (singletransaction.acf_fc_layout === "mission") {
                missionsToStart.push(singletransaction);
            }
            // Obsługa umiejętności (skill.*) zgodnie z register_fields.php: field_skills_group
            else if (singletransaction.acf_fc_layout === "skills") {
                skillsToUpdate.push({
                    skillType: singletransaction.type_of_skills, // 'combat', 'steal', 'craft', 'trade', 'relations', 'street'
                    value: parseInt(singletransaction.value, 10)
                });
            }
            // Obsługa doświadczenia i reputacji
            else if (singletransaction.acf_fc_layout === "exp_rep") {
                expRepToUpdate.push({
                    type: singletransaction.type,
                    value: parseInt(singletransaction.value, 10)
                });
            }
            // Obsługa odblokowywania rejonów
            else if (singletransaction.acf_fc_layout === "unlock_area") {
                areasToUnlock.push({
                    areaId: parseInt(singletransaction.area, 10)
                });
            }
            // Obsługa zmiany aktualnego rejonu
            else if (singletransaction.acf_fc_layout === "change_area") {
                areasToChange.push({
                    areaId: parseInt(singletransaction.area, 10)
                });
            }
            // Obsługa przedmiotów
            else if (singletransaction.acf_fc_layout === "item") {
                itemsToManage.push({
                    itemId: parseInt(singletransaction.item, 10),
                    quantity: parseInt(singletransaction.quantity, 10) || 1,
                    action: singletransaction.item_action || 'give'
                });
            }
        }

        // FAZA 2: Wykonanie transakcji

        // Wykonaj transakcje walutowe (złoto, papierosy)
        for (const transaction of transactionsToExecute) {
            console.log('Wykonuję transakcję:', transaction);

            // Aktualizuj wartość w plecaku zgodnie ze strukturą w register_fields.php
            const response = await (typeof window.updateACFFieldsWithGui === 'function' ?
                window.updateACFFieldsWithGui :
                localUpdateACFFieldsWithGui)(
                    { [`backpack.${transaction.bagType}`]: transaction.value }, // Ścieżka zgodna z ACF: backpack.gold
                    ['body']
                );

            const bagMessage = transaction.value < 0 ?
                `Wydano ${Math.abs(transaction.value)} ${transaction.friendly}` :
                `Otrzymano ${transaction.value} ${transaction.friendly}`;

            message = message ? `${message} i ${bagMessage}` : bagMessage;
            popupstate = 'success';
        }

        // Wykonaj operacje na umiejętnościach (zgodnie z register_fields.php: field_skills_group)
        if (skillsToUpdate.length > 0) {
            for (const skill of skillsToUpdate) {
                console.log('Aktualizuję umiejętność:', skill);
                try {
                    console.log('Aktualizacja umiejętności:', skill.skillType, 'wartość:', skill.value);

                    // Aktualizuj wartość umiejętności zgodnie ze strukturą w register_fields.php
                    const response = await (typeof window.updateACFFieldsWithGui === 'function' ?
                        window.updateACFFieldsWithGui :
                        localUpdateACFFieldsWithGui)(
                            { [`skills.${skill.skillType}`]: skill.value }, // Ścieżka zgodna z ACF: skills.steal
                            ['body'],
                            'Aktualizacja umiejętności...'
                        );

                    const skillMessage = skill.value > 0 ?
                        `Zwiększono umiejętność ${skill.skillType} o ${skill.value}` :
                        `Zmniejszono umiejętność ${skill.skillType} o ${Math.abs(skill.value)}`;

                    message = message ? `${message} i ${skillMessage}` : skillMessage;
                    popupstate = 'success';
                } catch (error) {
                    console.error('Błąd podczas aktualizacji umiejętności:', error);
                    showPopup(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'error');
                }
            }
        }

        // Wykonaj operacje na relacjach z NPC
        if (relationsToUpdate.length > 0) {
            for (const relation of relationsToUpdate) {
                console.log('Aktualizuję relację:', relation);
                try {
                    // Pobierz ID NPC i wartość zmiany relacji
                    const npcId = parseInt(relation.npc, 10);
                    const changeValue = parseInt(relation.change_relation, 10);

                    if (isNaN(npcId) || isNaN(changeValue)) {
                        throw new Error('Nieprawidłowe dane relacji');
                    }

                    // Założenie: relations jest obiektem z kluczami w formacie "npc_ID"
                    // Przykład: relations.npc_19 dla NPC o ID 19
                    const relationKey = `relations.npc_${npcId}`;

                    // Pobierz aktualną wartość relacji z bazy
                    const currentRelation = userFields?.relations?.[`npc_${npcId}`] || 0;
                    const newRelation = parseInt(currentRelation, 10) + changeValue;

                    console.log('Aktualizacja relacji dla NPC ID:', npcId, 'nowa wartość:', newRelation);

                    // Aktualizuj relację
                    const response = await (typeof window.updateACFFieldsWithGui === 'function' ?
                        window.updateACFFieldsWithGui :
                        localUpdateACFFieldsWithGui)(
                            { [relationKey]: newRelation },
                            ['body'],
                            'Aktualizacja relacji...'
                        );

                    // Przygotuj komunikat
                    const relationMessage = changeValue > 0 ?
                        `Zwiększono relację o ${changeValue}` :
                        `Zmniejszono relację o ${Math.abs(changeValue)}`;

                    message = message ? `${message} i ${relationMessage}` : relationMessage;
                    popupstate = 'success';
                } catch (error) {
                    console.error('Błąd podczas aktualizacji relacji:', error);
                    showPopup(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'error');
                }
            }
        }

        // Obsługa funkcji
        if (functionsToExecute.length > 0) {
            for (const funcData of functionsToExecute) {
                if (funcData.do_function === 'go-to-page' && funcData.page_url) {
                    // Przejście do innej strony
                    window.location.href = funcData.page_url;
                    return; // Natychmiastowe przerwanie, ponieważ zmieniamy stronę
                }
                // Tutaj można dodać obsługę innych typów funkcji
            }
        }

        // Obsługa misji
        if (missionsToStart.length > 0 && typeof window.missionManager !== 'undefined') {
            try {
                const allMissionMessages = await window.missionManager.handleMultipleMissions(missionsToStart);

                if (allMissionMessages.length > 0) {
                    // Wyświetl komunikaty misji
                    allMissionMessages.forEach(item => {
                        showPopup(item.message, 'success');
                    });

                    // Jeśli był już jakiś wcześniejszy komunikat, również go pokaż
                    if (message) {
                        showPopup(message, popupstate || 'success');
                        message = '';
                    }
                }
            } catch (error) {
                console.error('Błąd podczas obsługi misji:', error);
            }
        }

        // Obsługa odblokowywania rejonów
        if (areasToUnlock.length > 0) {
            for (const areaOperation of areasToUnlock) {
                try {
                    const { areaId } = areaOperation;

                    // Pobierz informacje o rejonie
                    const areaInfoResponse = await AjaxHelper.sendRequest((window.global && window.global.ajaxurl) || window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
                        action: 'get_area_info',
                        area_id: areaId
                    });

                    const areaName = areaInfoResponse.success ? areaInfoResponse.data?.name : 'nowy rejon';

                    // Aktualizuj dostęp do rejonu
                    const response = await AjaxHelper.sendRequest((window.global && window.global.ajaxurl) || window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
                        action: 'unlock_area_for_user',
                        area_id: areaId
                    });

                    if (response.success) {
                        const areaMessage = `Odblokowano dostęp do rejonu: ${areaName}`;
                        message = message ? `${message} i ${areaMessage}` : areaMessage;
                        popupstate = 'success';
                    }
                } catch (error) {
                    console.error('Błąd podczas odblokowywania rejonu:', error);
                    showPopup(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'error');
                }
            }
        }

        // Obsługa zmiany aktualnego rejonu
        if (areasToChange.length > 0) {
            for (const areaChange of areasToChange) {
                try {
                    const { areaId } = areaChange;

                    // Pobierz informacje o nowym rejonie
                    const newAreaInfoResponse = await AjaxHelper.sendRequest((window.global && window.global.ajaxurl) || window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
                        action: 'get_area_info',
                        area_id: areaId
                    });

                    const newAreaName = newAreaInfoResponse.success ? newAreaInfoResponse.data?.name : 'nowy rejon';

                    // Aktualizuj aktualny rejon użytkownika
                    const response = await (typeof window.updateACFFieldsWithGui === 'function' ?
                        window.updateACFFieldsWithGui :
                        localUpdateACFFieldsWithGui)(
                            { 'user_area': areaId },
                            ['body']
                        );

                    if (response) {
                        message = `Przeniesiono do rejonu: ${newAreaName}`;
                        popupstate = 'success';
                    }
                } catch (error) {
                    console.error('Błąd podczas zmiany rejonu:', error);
                    showPopup(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'error');
                }
            }
        }

        // Wyświetl komunikat podsumowujący
        if (message) {
            showPopup(message, popupstate || 'success');
        }

    } catch (error) {
        console.error('Błąd podczas przetwarzania transakcji:', error);
        showPopup('Wystąpił błąd podczas przetwarzania transakcji', 'error');
    }
}

/**
 * Własna implementacja funkcji flattenData jeśli nie jest dostępna w window
 * 
 * @param {Object} data - Zagnieżdzone dane do spłaszczenia
 * @param {string} prefix - Prefiks dla kluczy zagnieżdżonych
 * @return {Object} - Spłaszczony obiekt
 */
function flattenData(data, prefix = '') {
    let flat = {};
    for (let key in data) {
        if (data.hasOwnProperty(key)) {
            const newKey = prefix ? `${prefix}-${key}` : key;
            if (data[key] !== null && typeof data[key] === 'object' && !Array.isArray(data[key])) {
                Object.assign(flat, flattenData(data[key], newKey));
            } else {
                flat[newKey] = data[key];
            }
        }
    }
    return flat;
}

/**
 * Funkcja do pobierania najnowszych pól ACF
 * 
 * @returns {Promise<Object>} - Pola ACF
 */
async function fetchLatestACFFields() {
    try {
        const ajaxUrl = (window.global && window.global.ajaxurl) || window.ajaxurl || '/wp-admin/admin-ajax.php';
        const nonce = (window.global && window.global.dataManagerNonce) || (window.gameData && window.gameData.dataManagerNonce) || '';

        const response = await AjaxHelper.sendRequest(ajaxUrl, 'POST', {
            action: 'get_acf_fields',
            nonce: nonce
        });

        if (!response.success) {
            throw new Error(response || "Nieznany błąd serwera");
        }
        return response.data.fields;
    } catch (error) {
        console.error("❌ Błąd pobierania bazy:", error);
        return {};
    }
}

/**
 * Funkcja do aktualizacji pól ACF w poście
 * 
 * @param {number} postId - ID posta do aktualizacji
 * @param {Object} fields - Pola do aktualizacji
 * @returns {Promise<Object>} - Wynik operacji
 */
async function updatePostACFFields(postId, fields) {
    const ajaxUrl = (window.global && window.global.ajaxurl) || window.ajaxurl || '/wp-admin/admin-ajax.php';
    const nonce = (window.global && window.global.dataManagerNonce) || (window.gameData && window.gameData.dataManagerNonce) || '';

    return AjaxHelper.sendRequest(ajaxUrl, 'POST', {
        action: 'update_acf_post_fields_reusable',
        nonce: nonce,
        post_id: postId,
        fields: JSON.stringify(fields),
        request_id: Date.now() + Math.random().toString(36).substring(2, 9)
    });
}

/**
 * Funkcja do aktualizacji pól ACF (bez GUI)
 * 
 * @param {Object} fields - Pola do aktualizacji
 * @returns {Promise<Object>} - Wynik operacji
 */
async function updateACFFields(fields) {
    try {
        const ajaxUrl = (window.global && window.global.ajaxurl) || window.ajaxurl || '/wp-admin/admin-ajax.php';
        const nonce = (window.global && window.global.dataManagerNonce) || (window.gameData && window.gameData.dataManagerNonce) || '';

        console.log('Aktualizacja pól ACF:', fields);

        const response = await AjaxHelper.sendRequest(ajaxUrl, 'POST', {
            action: 'update_acf_fields',
            nonce: nonce,
            fields: JSON.stringify(fields),
            request_id: Date.now() + Math.random().toString(36).substring(2, 9)
        });

        console.log('Fields:', fields);
        console.log('Odpowiedź z serwera:', response);

        if (!response.success) {
            throw new Error(response.data?.message || "Nieznany błąd serwera");
        }

        return response;
    } catch (error) {
        console.error("❌ Błąd aktualizacji ACF:", error);
        throw error;
    }
}

/**
 * Funkcja do aktualizacji pól ACF z interfejsem graficznym
 * 
 * @param {Object} fields - Pola do aktualizacji
 * @param {Array<string>} parentSelectors - Selektory kontenerów nadrzędnych
 * @param {string|null} customMsg - Niestandardowy komunikat podczas aktualizacji
 * @returns {Promise<Object>} - Wynik operacji
 */
async function updateACFFieldsWithGui(fields, parentSelectors = ['body'], customMsg = null) {
    try {
        // Wywołaj czystą funkcję aktualizacji danych
        const response = await updateACFFields(fields);

        // Pobierz najnowsze dane ACF
        const freshData = await fetchLatestACFFields();

        // Własna implementacja flattenData, jeśli globalna nie jest dostępna
        const flattenFunc = typeof window.flattenData === 'function' ?
            window.flattenData : flattenData;

        // Spłaszcz dane
        const flatData = flattenFunc(freshData);

        // Aktualizacja standardowych elementów (np. elementów z klasą .ud-*)
        parentSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(parent => {
                if (flatData) {
                    Object.entries(flatData).forEach(([key, value]) => {
                        parent.querySelectorAll(`.ud-${key}`).forEach(el => {
                            el.textContent = value;
                        });
                    });
                }
            });
        });

        // Aktualizacja pasków postępu
        document.querySelectorAll('.bar-game').forEach(wrapper => {
            const statKey = 'stats-' + (wrapper.dataset.barType || '');
            if (flatData && flatData.hasOwnProperty(statKey)) {
                const newCurrent = parseFloat(flatData[statKey]);
                const max = parseFloat(wrapper.dataset.barMax);
                const percentage = (newCurrent / max) * 100;

                // Aktualizacja szerokości paska
                const bar = wrapper.querySelector('.bar');
                if (bar) {
                    bar.style.width = percentage + '%';
                }

                // Aktualizacja wartości wyświetlanej obok paska
                const barValueSpan = wrapper.querySelector('.bar-value span');
                if (barValueSpan) {
                    barValueSpan.textContent = newCurrent;
                }

                // Zaktualizuj atrybut data-bar-current dla synchronizacji
                wrapper.dataset.barCurrent = newCurrent;
            }
        });

        return response;
    } catch (error) {
        const errorMsg = error && error.message ? error.message : String(error);
        console.error("❌ Błąd aktualizacji bazy danych:", errorMsg);
        if (typeof window.showPopup === 'function') {
            window.showPopup(errorMsg, 'error');
        }
        throw error;
    }
}

// Eksport modułu i funkcji globalnych dla wstecznej kompatybilności
const NpcModule = {
    fetchDialogue,
    buildNpcPopup,
    handleAnswer,
    fetchLatestACFFields,
    updatePostACFFields,
    updateACFFields,
    updateACFFieldsWithGui,
    flattenData
};

// Eksport globalny
window.NpcModule = NpcModule;
window.fetchDialogue = fetchDialogue;
window.buildNpcPopup = buildNpcPopup;
window.handleAnswer = handleAnswer;
window.fetchLatestACFFields = window.fetchLatestACFFields || fetchLatestACFFields;
window.updatePostACFFields = window.updatePostACFFields || updatePostACFFields;
window.updateACFFields = window.updateACFFields || updateACFFields;
window.updateACFFieldsWithGui = window.updateACFFieldsWithGui || updateACFFieldsWithGui;
window.flattenData = window.flattenData || flattenData;
