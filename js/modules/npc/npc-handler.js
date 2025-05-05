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
                                // Konfiguracja bezpieczeństwa dla żądań API
                                const securityConfig = {
                                    headers: {
                                        'X-WP-Nonce': userManagerData.nonce,
                                        'Content-Type': 'application/json'
                                    }
                                };

                                // Używamy poprawnego endpointu popup z metodą POST
                                const response = await axios({
                                    method: 'POST',  // Metoda POST jest bardziej bezpieczna
                                    url: '/wp-json/game/v1/npc/popup',
                                    data: {  // Dane w formacie JSON
                                        npc_id: npcData.npc_id,
                                        id_conversation: answer.go_to_id,
                                        user_id: userId,
                                        page_id: JSON.stringify(typeof getPageData === 'function' ? getPageData() : {})
                                    },
                                    ...securityConfig  // Dodajemy konfigurację bezpieczeństwa
                                });

                                // Sprawdzamy czy otrzymaliśmy właściwą odpowiedź zgodną ze strukturą z ApiNpcHandler.php
                                console.log('Otrzymana odpowiedź:', response.data);

                                if (response.data && response.data.npc_data) {
                                    // Struktura z ApiNpcHandler.php - poprawiona obsługa
                                    if (response.data.npc_data.conversation) {
                                        // Przekazujemy obiekt conversation, który ma prawidłową strukturę
                                        renderDialogueContent(response.data.npc_data.conversation);

                                        // Aktualizujemy też inne dane NPC (tytuł, miniaturkę itd.)
                                        if (response.data.npc_data.npc_post_title) {
                                            const header = popupContainer.querySelector('h2');
                                            if (header) {
                                                header.innerHTML = response.data.npc_data.npc_post_title + ' mówi:';
                                            }
                                        }

                                        if (response.data.npc_data.npc_thumbnail) {
                                            const img = popupContainer.querySelector('.npc-thumbnail');
                                            if (img) {
                                                img.src = response.data.npc_data.npc_thumbnail;
                                            }
                                        }
                                    } else {
                                        console.warn('Brak konwersacji w otrzymanej odpowiedzi:', response.data);
                                        throw new Error('Nie udało się pobrać dialogu - brak konwersacji');
                                    }
                                } else if (response.data && response.data.success && response.data.data && response.data.data.conversation) {
                                    renderDialogueContent(response.data.data.conversation);
                                } else if (response.data && response.data.conversation) {
                                    // Alternatywna struktura odpowiedzi
                                    renderDialogueContent(response.data.conversation);
                                } else {
                                    console.warn('Nietypowa struktura odpowiedzi:', response.data);
                                    throw new Error('Nie udało się pobrać dialogu - niepoprawna struktura odpowiedzi');
                                }
                            } catch (err) {
                                console.error('Błąd podczas pobierania dialogu:', err);
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
            await axios({
                method: 'GET',
                url: '/wp-json/game/v1/acf/fields'
            }).then(response => {
                if (!response.data.success) throw new Error(response.data || "Nieznany błąd serwera");
                return response.data.data.fields;
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
            console.log('Wykonuję transakcję:', transaction);                    // Aktualizuj wartość w plecaku zgodnie ze strukturą w register_fields.php
            const response = await (typeof window.UserManager !== 'undefined' && window.UserManager.updateBackpack ?
                window.UserManager.updateBackpack(transaction.bagType, transaction.value) :
                (typeof window.updateACFFieldsWithGui === 'function' ?
                    window.updateACFFieldsWithGui({
                        [`backpack.${transaction.bagType}`]: transaction.value
                    }) :
                    axios({
                        method: 'POST',
                        url: '/wp-json/game/v1/acf/update',
                        data: {
                            fields: { [`backpack.${transaction.bagType}`]: transaction.value } // Ścieżka zgodna z ACF: backpack.gold
                        }
                    }).then(response => {
                        if (!response.data.success) throw new Error(response.data || "Nieznany błąd serwera");
                        return response.data;
                    })
                ));

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
                    const response = await (typeof window.UserManager !== 'undefined' && window.UserManager.updateSkill ?
                        window.UserManager.updateSkill(skill.skillType, skill.value) :
                        (typeof window.updateACFFieldsWithGui === 'function' ?
                            window.updateACFFieldsWithGui({
                                [`skills.${skill.skillType}`]: skill.value
                            }) :
                            axios({
                                method: 'POST',
                                url: '/wp-json/game/v1/acf/update',
                                data: {
                                    fields: { [`skills.${skill.skillType}`]: skill.value } // Ścieżka zgodna z ACF: skills.steal
                                }
                            }).then(response => {
                                if (!response.data.success) throw new Error(response.data || "Nieznany błąd serwera");
                                return response.data;
                            })
                        ));

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

                    console.log('Aktualizacja relacji dla NPC ID:', npcId, 'zmiana o wartość:', changeValue);

                    // Użyj nowej funkcji UserManager.updateNpcRelation() do aktualizacji relacji
                    const response = await (typeof window.UserManager !== 'undefined' && window.UserManager.updateNpcRelation ?
                        window.UserManager.updateNpcRelation(npcId, changeValue) :
                        (typeof window.updateACFFieldsWithGui === 'function' ?
                            window.updateACFFieldsWithGui({
                                [`npc-relation-${npcId}`]: changeValue
                            }) :
                            axios({
                                method: 'POST',
                                url: '/wp-json/game/v1/acf/update',
                                data: {
                                    fields: { [`npc-relation-${npcId}`]: changeValue }
                                }
                            }).then(response => {
                                if (!response.data.success) throw new Error(response.data || "Nieznany błąd serwera");
                                return response.data;
                            })
                        ));

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
                    const areaInfoResponse = await axios({
                        method: 'GET',
                        url: '/wp-json/game/v1/area/info',
                        params: {
                            area_id: areaId
                        }
                    });

                    const areaName = areaInfoResponse.data.success ? areaInfoResponse.data.data?.name : 'nowy rejon';

                    // Aktualizuj dostęp do rejonu
                    const response = await axios({
                        method: 'POST',
                        url: '/wp-json/game/v1/area/unlock',
                        data: {
                            area_id: areaId
                        }
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
                    const newAreaInfoResponse = await axios({
                        method: 'GET',
                        url: '/wp-json/game/v1/area/info',
                        params: {
                            area_id: areaId
                        }
                    });

                    const newAreaName = newAreaInfoResponse.data.success ? newAreaInfoResponse.data.data?.name : 'nowy rejon';

                    // Aktualizuj aktualny rejon użytkownika
                    const response = await (typeof window.UserManager !== 'undefined' && window.UserManager.setCurrentArea ?
                        window.UserManager.setCurrentArea(areaId) :
                        (typeof window.updateACFFieldsWithGui === 'function' ?
                            window.updateACFFieldsWithGui({
                                'user_area': areaId
                            }) :
                            axios({
                                method: 'POST',
                                url: '/wp-json/game/v1/acf/update',
                                data: {
                                    fields: { 'user_area': areaId }
                                }
                            }).then(response => {
                                if (!response.data.success) throw new Error(response.data || "Nieznany błąd serwera");
                                return response.data;
                            })
                        ));

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

// Eksport modułu i funkcji globalnych dla wstecznej kompatybilności
const NpcModule = {
    // fetchDialogue,
    buildNpcPopup,
    handleAnswer,
};

// Eksport globalny
window.NpcModule = NpcModule;
window.buildNpcPopup = buildNpcPopup;
window.handleAnswer = handleAnswer;

