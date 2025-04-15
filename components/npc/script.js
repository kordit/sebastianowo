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

    for (const singletransaction of transactions) {
        console.log(transactions);
        switch (singletransaction.acf_fc_layout) {
            case "transaction":
                const bagType = singletransaction.bag;
                const value = parseInt(singletransaction.value, 10);

                let friendly;
                switch (bagType) {
                    case 'gold':
                        friendly = 'złote';
                        break;
                    case 'papierosy':
                        friendly = 'szlug';
                        break;
                    case 'piwo':
                        friendly = 'browara';
                        break;
                    default:
                        friendly = bagType;
                }
                console.log("BagType" + bagType);
                console.log("Value: " + value);

                try {
                    await updateACFFieldsWithGui(
                        { [`bag.${bagType}`]: value },
                        ['body']
                    );

                    const bagMessage = value < 0
                        ? `Wydano ${Math.abs(value)} ${friendly}`
                        : `Otrzymano ${value} ${friendly}`;

                    message = message
                        ? `${message} i ${bagMessage}`
                        : bagMessage;

                    popupstate = 'success';
                    showPopup(message, popupstate);
                } catch (error) {
                    const errorMessage = `Nie masz wystarczająco dużo ${friendly}`;
                    message = message
                        ? `${message} oraz ${errorMessage}`
                        : errorMessage;
                    popupstate = 'error';
                    showPopup(message, popupstate);
                    return;
                }
                break;

            case "function":
                const innernpcId = document.getElementById('npcdatamanager').dataset.id;
                const functionsArray = [{ function_name: singletransaction.do_function, npc_id: innernpcId }];
                document.getElementById('npc-popup').dataset.functions = JSON.stringify(functionsArray);
                break;

            case "relation":
                const npcId = singletransaction.npc;
                const relationChange = parseInt(singletransaction.change_relation, 10);
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
                    popupstate = 'error';
                }
                // showPopup(message, popupstate);

                updatePostACFFields(npcId, { [fieldName]: relationChange });
                break;

            default:
                console.warn('Nieznany typ akcji:', singletransaction.acf_fc_layout);
                break;

        }
    }
}