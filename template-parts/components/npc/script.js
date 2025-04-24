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
                        if (errorOccurred) return;
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
                        }
                    } else {
                        if (popupContainer.dataset.functions) {
                            const functionsList = JSON.parse(popupContainer.dataset.functions);
                            runFunctionNPC(functionsList);
                        }
                        popupContainer.classList.remove('active');
                        setTimeout(() => popupContainer.remove(), 300);
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
    console.log(dataset);
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

    // Faza 1: Walidacja wszystkich transakcji
    try {
        // Pobieramy aktualne wartości zasobów gracza
        const userFields = await fetchLatestACFFields();

        for (const singletransaction of transactions) {
            if (singletransaction.acf_fc_layout === "transaction") {
                const bagType = singletransaction.bag;
                const value = parseInt(singletransaction.value, 10);

                // Sprawdzenie czy gracz ma wystarczająco dużo zasobów
                if (value < 0) {
                    const currentValue = userFields.bag && userFields.bag[bagType] ?
                        parseInt(userFields.bag[bagType], 10) : 0;

                    if (currentValue < Math.abs(value)) {
                        let friendly;
                        switch (bagType) {
                            case 'gold': friendly = 'złote'; break;
                            case 'papierosy': friendly = 'szlug'; break;
                            case 'piwo': friendly = 'browara'; break;
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
                            case 'piwo': return 'browara';
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
            await updateACFFieldsWithGui(
                { [`bag.${transaction.bagType}`]: transaction.value },
                ['body']
            );

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

            console.log('Przekazuję pełne dane funkcji:', functionData);
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

        // Uruchom misje
        for (const missionConfig of missionsToStart) {
            try {
                const missionId = missionConfig.mission_id;
                if (!missionId) {
                    console.error('Brak ID misji w konfiguracji:', missionConfig);
                    continue;
                }

                // Pobierz ID NPC, który daje misję
                const npcId = document.getElementById('npcdatamanager')?.dataset?.id;

                // Sprawdź, czy funkcja startMission jest dostępna
                if (typeof window.startMission === 'function') {
                    console.log('Uruchamiam misję:', missionId, 'od NPC:', npcId);

                    // Parametry dla funkcji startMission
                    const missionParams = {
                        mission_id: missionId,
                        npc_id: npcId,
                        show_confirmation: missionConfig.show_confirmation !== undefined ? missionConfig.show_confirmation : true,
                        success_message: missionConfig.success_message || 'Otrzymano nową misję!',
                        // Opcjonalne parametry
                        auto_complete_first_task: missionConfig.auto_complete_first_task || false,
                        redirect_after: missionConfig.redirect_after || false,
                        item_id: missionConfig.item_id,
                        item_quantity: missionConfig.item_quantity
                    };

                    // Uruchom misję
                    await window.startMission(missionParams);

                    // Ustaw komunikat potwierdzający (jeśli nie pokazujemy osobnego potwierdzenia)
                    if (!missionConfig.show_confirmation && !message) {
                        message = 'Misja została przypisana!';
                        popupstate = 'success';
                    }
                } else {
                    console.error('Funkcja startMission nie jest dostępna');
                    showPopup('Wystąpił błąd podczas uruchamiania misji', 'error');
                }
            } catch (error) {
                console.error('Błąd podczas uruchamiania misji:', error);
                showPopup(`Wystąpił błąd: ${error.message || 'nieznany błąd'}`, 'error');
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