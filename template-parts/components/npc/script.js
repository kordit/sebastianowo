// Importujemy klasę MissionManager z pliku mission-handler.js
// Utworzenie globalnej instancji MissionManager


function fetchDialogue(npcData, idConversation, conditions, userId) {
    const data = {
        action: 'get_dialogue',
        npc_data: JSON.stringify(npcData),
        user_id: userId
    };
    if (idConversation) data.id_conversation = idConversation;
    if (conditions) data.conditions = JSON.stringify(conditions);
    return AjaxHelper.sendRequest(global.ajaxurl, 'POST', data)
        .then(response => response.data);
}

// Funkcja globalna do obsługi uruchamiania misji
window.startMission = async function (params) {
    // Używamy instancji MissionManager do obsługi misji
    return await missionManager.startMission(params);
};

function buildNpcPopup(npcData, userId) {
    if (!userId) {
        const userElem = document.getElementById('get-user-id');
        userId = userElem ? userElem.dataset.id : null;
    }

    const existingPopup = document.getElementById('npc-popup');
    if (existingPopup) existingPopup.remove();
    const popupContainer = document.createElement('div');
    popupContainer.id = 'npc-popup';
    popupContainer.className = 'controler-popup';
    popupContainer.npcData = npcData;

    setTimeout(() => popupContainer.classList.add('active'), 100);

    if (npcData.npc_thumbnail) {
        const img = document.createElement('img');
        img.src = npcData.npc_thumbnail;
        img.alt = npcData.npc_name || 'NPC Image';
        img.className = 'npc-thumbnail';
        img.id = 'npcdatamanager';
        img.dataset.id = npcData.npc_id;
        popupContainer.appendChild(img);
    }

    const conversationWrapper = document.createElement('div');
    conversationWrapper.className = 'npc-conversation-wrapper';

    if (npcData.npc_post_title) {
        const header = document.createElement('h2');
        header.innerHTML = npcData.npc_post_title + ' mówi:';
        conversationWrapper.appendChild(header);
    }


    // Oddzielny kontener dla zawartości dialogu, aby nagłówek pozostał widoczny
    const dialogueContent = document.createElement('div');
    dialogueContent.className = 'npc-dialogue-content';
    conversationWrapper.appendChild(dialogueContent);

    popupContainer.appendChild(conversationWrapper);
    document.body.appendChild(popupContainer);

    function renderDialogueContent(dialogue) {
        dialogueContent.innerHTML = '';
        if (dialogue.question) {
            const questionEl = document.createElement('div');
            questionEl.className = 'npc-question';
            questionEl.innerHTML = dialogue.question;
            dialogueContent.appendChild(questionEl);
        }
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
                Object.keys(answer).forEach(key => {
                    if (key !== 'type_anwser') {
                        btn.dataset[key] = answer[key];
                    }
                });
                btn.addEventListener('click', async () => {
                    // Zapobiegaj wielokrotnym kliknięciom - wyłączenie przycisku
                    if (btn.disabled) return;
                    btn.disabled = true;
                    btn.classList.add('processing');

                    try {
                        if (btn.hasAttribute('data-type-anwser')) {
                            const typeAnwser = JSON.parse(btn.getAttribute('data-type-anwser'));
                            let errorOccurred = false;
                            const originalShowPopup = window.showPopup;
                            window.showPopup = function (message, state) {
                                if (state === 'error') errorOccurred = true;
                                originalShowPopup(message, state);
                            };
                            await handleAnswer(typeAnwser);
                            window.showPopup = originalShowPopup;
                            if (errorOccurred) {
                                btn.disabled = false;
                                btn.classList.remove('processing');
                                return;
                            }
                        }
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



    if (npcData.conversation) {
        renderDialogueContent(npcData.conversation);
    }
}

async function handleAnswer(input) {
    const dataset = input && input.currentTarget ? input.currentTarget.dataset : input;
    if (!dataset) return;
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

    // Najpierw zbieramy wszystkie transakcje i sprawdzamy, czy wszystkie mogą zostać wykonane
    const transactionsToExecute = [];
    const functionsToExecute = [];
    const relationsToUpdate = [];
    const itemsToManage = []; // Nowa tablica dla operacji na przedmiotach
    const missionsToStart = []; // Nowa tablica dla misji do uruchomienia
    const skillsToUpdate = []; // Nowa tablica dla aktualizacji umiejętności
    const expRepToUpdate = []; // Nowa tablica dla aktualizacji doświadczenia i reputacji
    const areasToUnlock = []; // Nowa tablica dla odblokowywania rejonów
    const areasToChange = []; // Nowa tablica dla zmiany aktualnego rejonu

    // Faza 1: Walidacja wszystkich transakcji
    try {
        // Pobieramy aktualne wartości zasobów gracza
        const userFields = await fetchLatestACFFields();

        for (const singletransaction of transactions) {
            if (singletransaction.acf_fc_layout === "transaction") {
                const bagType = singletransaction.backpack;
                const value = parseInt(singletransaction.value, 10);

                // Sprawdzenie czy gracz ma wystarczająco dużo zasobów
                if (value < 0) {
                    // Mapowanie nazw z UI na nazwy pól w bazie danych
                    const fieldMapping = {
                        'gold': 'gold',
                        'papierosy': 'cigarettes'
                        // dodaj inne mapowania jeśli pojawią się nowe waluty
                    };

                    // Pobierz właściwą nazwę pola
                    const fieldName = fieldMapping[bagType] || bagType;

                    // Sprawdź wartość w userFields.backpack
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
            } else if (singletransaction.acf_fc_layout === "function") {
                functionsToExecute.push(singletransaction);
            } else if (singletransaction.acf_fc_layout === "relation") {
                relationsToUpdate.push(singletransaction);
            } else if (singletransaction.acf_fc_layout === "mission") {
                // Obsługa misji - dodajemy misję do uruchomienia
                missionsToStart.push(singletransaction);
            } else if (singletransaction.acf_fc_layout === "skills") {
                // Obsługa umiejętności - dodajemy umiejętność do aktualizacji
                const skillType = singletransaction.type_of_skills;
                const value = parseInt(singletransaction.value, 10);

                // Dodaj do listy umiejętności do aktualizacji
                skillsToUpdate.push({
                    skillType,
                    value
                });
            } else if (singletransaction.acf_fc_layout === "exp_rep") {
                // Obsługa doświadczenia i reputacji
                const type = singletransaction.type;
                const value = parseInt(singletransaction.value, 10);

                // Dodaj do listy doświadczenia/reputacji do aktualizacji
                expRepToUpdate.push({
                    type,
                    value
                });
            } else if (singletransaction.acf_fc_layout === "unlock_area") {
                // Obsługa odblokowywania rejonu
                const areaId = parseInt(singletransaction.area, 10);
                if (areaId) {
                    // Dodaj rejon do listy rejonów do odblokowania
                    areasToUnlock.push({
                        areaId
                    });
                }
            } else if (singletransaction.acf_fc_layout === "change_area") {
                // Obsługa zmiany aktualnego rejonu
                const areaId = parseInt(singletransaction.area, 10);
                if (areaId) {
                    // Dodaj rejon do zmiany do listy
                    areasToChange.push({
                        areaId
                    });
                }
            } else if (singletransaction.acf_fc_layout === "item") {
                // Obsługa przedmiotów
                const itemId = parseInt(singletransaction.item, 10);
                const quantity = parseInt(singletransaction.quantity, 10) || 1;
                const action = singletransaction.item_action || 'give';

                if (action === 'take') {
                    // Sprawdzamy czy użytkownik ma przedmiot przed próbą jego zabrania
                    const userItems = userFields.items || [];
                    const foundItem = userItems.find(item =>
                        item.item && (item.item.ID === itemId || item.item === itemId)
                    );

                    if (!foundItem || parseInt(foundItem.quantity, 10) < quantity) {
                        try {
                            // Pobierz nazwę przedmiotu, aby pokazać ją w komunikacie błędu
                            const itemData = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                                action: 'get_item_name',
                                item_id: itemId
                            });

                            const itemName = itemData.data?.name || 'przedmiot';
                            showPopup(`Nie masz wystarczająco dużo: ${itemName}`, 'error');
                        } catch (error) {
                            console.error('Błąd podczas pobierania nazwy przedmiotu:', error);
                            showPopup('Nie masz wystarczająco dużo tego przedmiotu', 'error');
                        }
                        return; // Przerwij całą operację
                    }
                }

                // Jeśli walidacja przeszła, dodaj przedmiot do zarządzania
                itemsToManage.push({
                    itemId,
                    quantity,
                    action
                });
            }
        }

        // Faza 2: Wykonanie wszystkich transakcji
        // Wykonaj transakcje
        for (const transaction of transactionsToExecute) {
            console.log('Wykonuję transakcję:', transaction);
            console.log('Wysyłam dane:', { [`backpack.${transaction.bagType}`]: transaction.value });

            const response = await updateACFFieldsWithGui(
                { [`backpack.${transaction.bagType}`]: transaction.value },
                ['body']
            );

            console.log('Odpowiedź z serwera:', response);

            const bagMessage = transaction.value < 0
                ? `Wydano ${Math.abs(transaction.value)} ${transaction.friendly}`
                : `Otrzymano ${transaction.value} ${transaction.friendly}`;

            message = message
                ? `${message} i ${bagMessage}`
                : bagMessage;

            popupstate = 'success';
        }

        // Wykonaj operacje na przedmiotach
        for (const itemOperation of itemsToManage) {
            try {
                const { itemId, quantity, action } = itemOperation;

                // Pobierz informacje o przedmiocie
                const itemInfoResponse = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                    action: 'get_item_name',
                    item_id: itemId
                });

                const itemName = itemInfoResponse.data?.name || 'przedmiot';

                // Wykonaj operację na przedmiocie
                const response = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                    action: 'handle_item_action',
                    item_id: itemId,
                    quantity: quantity,
                    operation: action
                });

                if (response.success) {
                    const itemMessage = action === 'give'
                        ? `Otrzymano ${quantity} × ${itemName}`
                        : `Oddano ${quantity} × ${itemName}`;

                    message = message
                        ? `${message} i ${itemMessage}`
                        : itemMessage;

                    popupstate = 'success';
                } else {
                    console.error('Błąd podczas operacji na przedmiocie:', response.data?.message);
                    throw new Error(response.data?.message || 'Nieznany błąd');
                }
            } catch (error) {
                console.error('Błąd podczas operacji na przedmiocie:', error);
                showPopup(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'error');
                return; // Przerwij dalsze operacje
            }
        }

        // Wykonaj aktualizacje umiejętności
        for (const skillOperation of skillsToUpdate) {
            try {
                const { skillType, value } = skillOperation;

                // Mapowanie nazw umiejętności na przyjazne nazwy dla komunikatów
                const skillNames = {
                    'combat': 'Walka',
                    'steal': 'Kradzież',
                    'craft': 'Produkcja',
                    'trade': 'Handel',
                    'relations': 'Relacje',
                    'street': 'Uliczna wiedza'
                };

                // Aktualizacja umiejętności przy użyciu istniejącej funkcji updateACFFieldsWithGui
                const response = await updateACFFieldsWithGui(
                    { [`skills.${skillType}`]: value },
                    ['body']
                );

                console.log('Odpowiedź po aktualizacji umiejętności:', response);

                // Przygotuj przyjazny komunikat
                const skillName = skillNames[skillType] || skillType;
                const skillMessage = value > 0
                    ? `Umiejętność ${skillName} wzrosła o ${value}`
                    : `Umiejętność ${skillName} zmalała o ${Math.abs(value)}`;

                // Dodaj komunikat do ogólnej wiadomości
                message = message
                    ? `${message} i ${skillMessage}`
                    : skillMessage;

                popupstate = 'success';
            } catch (error) {
                console.error('Błąd podczas aktualizacji umiejętności:', error);
                showPopup('Wystąpił błąd: ' + error, 'error');
                return; // Przerwij dalsze operacje
            }
        }

        // Wykonaj aktualizacje doświadczenia i reputacji
        for (const expRepOperation of expRepToUpdate) {
            try {
                const { type, value } = expRepOperation;

                // Mapowanie typu na nazwy pól w bazie danych i przyjazne nazwy dla komunikatów
                const fieldMapping = {
                    'exp': 'progress.exp',
                    'reputation': 'progress.reputation'
                };

                const nameMapping = {
                    'exp': 'Doświadczenie',
                    'reputation': 'Reputacja'
                };

                // Aktualizacja doświadczenia lub reputacji przy użyciu istniejącej funkcji
                const fieldName = fieldMapping[type];

                // Sprawdzamy, czy dla reputacji potrzebujemy dodać pole, jeśli nie istnieje
                if (type === 'reputation') {
                    try {
                        const userFields = await fetchLatestACFFields();
                        if (!userFields.progress || userFields.progress.reputation === undefined) {
                            // Jeśli pole reputacji nie istnieje, najpierw je utworzymy z wartością 0
                            await updateACFFieldsWithGui(
                                { 'progress.reputation': 0 },
                                ['body']
                            );
                        }
                    } catch (error) {
                        console.error('Błąd podczas sprawdzania pola reputacji:', error);
                    }
                }

                const response = await updateACFFieldsWithGui(
                    { [fieldName]: value },
                    ['body']
                );

                console.log(`Odpowiedź po aktualizacji ${type}:`, response);

                // Przygotuj przyjazny komunikat
                const typeName = nameMapping[type] || type;
                const expRepMessage = value > 0
                    ? `${typeName} wzrosło o ${value}`
                    : `${typeName} zmalało o ${Math.abs(value)}`;

                // Dodaj komunikat do ogólnej wiadomości
                message = message
                    ? `${message} i ${expRepMessage}`
                    : expRepMessage;

                popupstate = 'success';
            } catch (error) {
                console.error('Błąd podczas aktualizacji doświadczenia/reputacji:', error);
                showPopup(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'error');
                return; // Przerwij dalsze operacje
            }
        }

        // Wykonaj funkcje
        for (const func of functionsToExecute) {
            const innernpcId = document.getElementById('npcdatamanager').dataset.id;
            // Przekazujemy wszystkie parametry z obiektu func i dodajemy npc_id
            const functionData = {
                do_function: func.do_function,  // Używamy do_function zamiast function_name
                npc_id: innernpcId,
                ...func // Kopiujemy wszystkie pozostałe parametry z func (w tym page_url i inne)
            };

            // Usuń duplikat do_function, który może się pojawić przez spread
            if (functionData.do_function === func.do_function) {
                delete functionData.do_function;
                functionData.do_function = func.do_function;
            }

            const functionsArray = [functionData];
            document.getElementById('npc-popup').dataset.functions = JSON.stringify(functionsArray);
        }

        // Aktualizuj relacje
        for (const relation of relationsToUpdate) {
            const npcId = relation.npc;
            const relationChange = parseInt(relation.change_relation, 10);
            const userId = document.getElementById('get-user-id').dataset.id;
            const fieldName = `npc-relation-user-${userId}`;

            if (relationChange > 0) {
                message = message
                    ? `${message} i ziomeczek Cię bardziej lubi`
                    : 'Ziomeczek Cię bardziej lubi';
                popupstate = 'success';
            } else if (relationChange < 0) {
                message = message
                    ? `${message} i wkurwiłeś ziomeczka`
                    : 'Wkurwiłeś ziomeczka';
                popupstate = 'bad';
            }

            const tempnpcId = document.getElementById('npcdatamanager')?.dataset?.id;
            const finalNpcId = npcId || tempnpcId;

            updatePostACFFields(finalNpcId, { [fieldName]: relationChange });
        }

        // Uruchom misje - musimy wykonać misje PRZED przedmiotami
        // Wykonujemy misje pierwsze, ponieważ jeśli misja nie może być przydzielona (np. już istnieje),
        // nie powinniśmy przyznawać przedmiotów związanych z nią
        if (missionsToStart.length > 0) {
            console.log('Uruchamiam misje:', missionsToStart);

            // Tablica, która przechowa wszystkie komunikaty o misjach (nie używamy Set, aby zachować duplikaty)
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

                // Pobierz ID NPC, który daje misję
                const npcId = document.getElementById('npcdatamanager')?.dataset?.id;

                for (const missionConfig of missionsInGroup) {
                    try {
                        if (!missionConfig.mission_id) {
                            console.error('Brak ID misji w konfiguracji:', missionConfig);
                            continue;
                        }

                        // Sprawdź, czy funkcja startMission jest dostępna
                        if (typeof window.startMission === 'function') {
                            // Parametry dla funkcji startMission
                            const missionParams = {
                                mission_id: missionConfig.mission_id,
                                npc_id: npcId,
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
                            const missionResult = await window.startMission(missionParams);

                            // Jeśli wystąpił błąd podczas uruchamiania misji
                            if (missionResult === false) {
                                return; // Przerwij całą operację
                            }

                            // Zapisz komunikat z dodatkowymi informacjami do identyfikacji
                            if (!missionConfig.show_confirmation && missionResult && missionResult.message) {
                                // Dodaj pełne dane do wiadomości
                                allMissionMessages.push({
                                    message: missionResult.message,
                                    taskId: missionConfig.mission_task_id || '',
                                    missionId: missionConfig.mission_id || '',
                                    taskStatus: missionConfig.mission_task_status || '',
                                    // Dodajemy oryginalną wiadomość z serwera
                                    originalMessage: missionResult.message,
                                    // Dodaj znacznik czasu, aby zapewnić unikalne sortowanie
                                    timestamp: Date.now()
                                });
                                console.log("Dodano komunikat misji:", missionResult.message, "dla zadania:", missionConfig.mission_task_id);
                            }
                        } else {
                            console.error('Funkcja startMission nie jest dostępna');
                            showPopup('Wystąpił błąd podczas uruchamiania misji', 'error');
                            return; // Przerwij całą operację
                        }
                    } catch (error) {
                        console.error('Błąd podczas uruchamiania misji:', error);
                        showPopup(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'error');
                        return; // Przerwij całą operację
                    }
                }
            }

            // Wyświetl każdy komunikat o misji jako osobne powiadomienie w poprawnej kolejności
            if (allMissionMessages.length > 0) {
                console.log('Wszystkie komunikaty misji przed sortowaniem:', allMissionMessages);

                // Sortuj komunikaty według taskId i statusu, aby zapewnić logiczną kolejność
                allMissionMessages.sort((a, b) => {
                    // Sortuj najpierw według ID zadania (jeśli są różne)
                    if (a.taskId !== b.taskId) {
                        // Wyodrębnij numery z taskId (np. z "dojedz-do-sebastianowa_0" -> 0)
                        const aNum = parseInt((a.taskId.match(/_(\d+)$/) || [0, 0])[1], 10);
                        const bNum = parseInt((b.taskId.match(/_(\d+)$/) || [0, 0])[1], 10);
                        return aNum - bNum;
                    }

                    // Jeśli zadania mają ten sam ID, sortuj według statusu:
                    // completed powinno być przed in_progress
                    if (a.taskStatus === 'completed' && b.taskStatus !== 'completed') return -1;
                    if (a.taskStatus !== 'completed' && b.taskStatus === 'completed') return 1;

                    // Jako ostatnią instancję, sortuj według znacznika czasu
                    return a.timestamp - b.timestamp;
                });

                console.log('Komunikaty misji po sortowaniu:', allMissionMessages);

                // Dodaj numer pozycji do zadań z tą samą nazwą, aby rozróżnić je w komunikatach
                const taskCounts = {};
                const processedMessages = allMissionMessages.map(item => {
                    // Znajdź identyfikator zadania z treści wiadomości
                    const taskNameMatch = item.message.match(/Zadanie "(.*?)" jest teraz/);

                    if (taskNameMatch && taskNameMatch[1]) {
                        const taskName = taskNameMatch[1];

                        // Jeśli nazwa zadania już wystąpiła, dodaj do niej numer pozycji
                        if (taskCounts[taskName]) {
                            taskCounts[taskName]++;

                            // Wyciągnij numer z ID zadania, jeśli istnieje
                            const taskNumMatch = item.taskId.match(/_(\d+)$/);
                            const taskNum = taskNumMatch ? taskNumMatch[1] : taskCounts[taskName];

                            // Dodaj numer pozycji do nazwy zadania w komunikacie
                            const newMessage = item.message.replace(
                                `Zadanie "${taskName}"`,
                                `Zadanie "${taskName} (${taskNum})"`
                            );

                            return {
                                ...item,
                                message: newMessage
                            };
                        } else {
                            taskCounts[taskName] = 1;
                        }
                    }
                    return item;
                });

                // Wyświetl komunikaty w odpowiedniej kolejności
                processedMessages.forEach(item => {
                    console.log("Wyświetlam komunikat misji:", item.message);
                    showPopup(item.message, 'success');
                });

                // Jeśli był już jakiś wcześniejszy komunikat, również go pokaż osobno
                if (message) {
                    showPopup(message, popupstate || 'success');
                    // Resetujemy message, aby nie pokazywać go ponownie na końcu funkcji
                    message = '';
                }
            }
        }

        // Odblokuj rejony
        if (areasToUnlock.length > 0) {
            console.log('Odblokowuję rejony:', areasToUnlock);
            for (const areaOperation of areasToUnlock) {
                try {
                    const { areaId } = areaOperation;

                    // Pobierz informacje o rejonie
                    const areaInfoResponse = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                        action: 'get_area_info',
                        area_id: areaId
                    });

                    const areaName = areaInfoResponse.success ? areaInfoResponse.data?.name : 'nowy rejon';

                    // Aktualizuj dostęp do rejonu
                    const response = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                        action: 'unlock_area_for_user',
                        area_id: areaId
                    });

                    if (response.success) {
                        const areaMessage = `Odblokowano dostęp do rejonu: ${areaName}`;

                        message = message
                            ? `${message} i ${areaMessage}`
                            : areaMessage;

                        popupstate = 'success';
                    } else {
                        console.error('Błąd podczas odblokowywania rejonu:', response.data?.message);
                        throw new Error(response.data?.message || 'Nieznany błąd');
                    }
                } catch (error) {
                    console.error('Błąd podczas odblokowywania rejonu:', error);
                    showPopup(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'error');
                    return; // Przerwij dalsze operacje
                }
            }
        }

        // Zmień rejon, jeśli są takie operacje
        if (areasToChange.length > 0) {
            console.log('Zmiana rejonu na:', areasToChange);
            for (const areaChange of areasToChange) {
                try {
                    const { areaId } = areaChange;

                    // Pobierz informacje o nowym rejonie
                    const newAreaInfoResponse = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                        action: 'get_area_info',
                        area_id: areaId
                    });

                    const newAreaName = newAreaInfoResponse.success ? newAreaInfoResponse.data?.name : 'nowy rejon';

                    // Zaktualizuj pole rejonu użytkownika
                    const response = await updateACFFieldsWithGui(
                        { 'user_area': areaId },
                        ['body']
                    );

                    if (response) {
                        message = `Przeniesiono do rejonu: ${newAreaName}`;
                        popupstate = 'success';
                    } else {
                        throw new Error('Nie udało się zmienić rejonu');
                    }
                } catch (error) {
                    console.error('Błąd podczas zmiany rejonu:', error);
                    showPopup(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'error');
                    return; // Przerwij dalsze operacje
                }
            }
        }

        // Wyświetl komunikat podsumowujący
        if (message) {
            showPopup(message, popupstate || 'success');
        }

        // Zmień aktualny rejon
        if (areasToChange.length > 0) {
            console.log('Zmieniam aktualny rejon:', areasToChange);
            for (const areaOperation of areasToChange) {
                try {
                    const { areaId } = areaOperation;

                    // Pobierz informacje o rejonie
                    const areaInfoResponse = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                        action: 'get_area_info',
                        area_id: areaId
                    });

                    const areaName = areaInfoResponse.success ? areaInfoResponse.data?.name : 'nowy rejon';

                    // Zmień aktualny rejon użytkownika
                    const response = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                        action: 'change_current_area',
                        area_id: areaId
                    });

                    if (response.success) {
                        const areaMessage = `Zmieniono aktualny rejon na: ${areaName}`;

                        // Wyświetl komunikat o zmianie rejonu
                        showPopup(areaMessage, 'success');
                    } else {
                        console.error('Błąd podczas zmiany rejonu:', response.data?.message);
                        throw new Error(response.data?.message || 'Nieznany błąd');
                    }
                } catch (error) {
                    console.error('Błąd podczas zmiany rejonu:', error);
                    showPopup(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'error');
                }
            }
        }

    } catch (error) {
        console.error('Błąd podczas przetwarzania transakcji:', error);
        showPopup('Wystąpił błąd podczas przetwarzania transakcji', 'error');
    }
}