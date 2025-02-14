function initNpcPopup(npcId, containerId = 'npc-popup', active = false) {
    const popupContainer = document.getElementById(containerId);
    if (!popupContainer) {
        console.error("Nie znaleziono popup container o id:", containerId);
        return;
    }
    if (active) popupContainer.classList.add('active');

    window.npcConversations = window.npcConversations || {};
    if (!window.npcConversations[npcId]) {
        const conversationJson = popupContainer.getAttribute('data-conversation');
        if (!conversationJson) {
            console.error("Brak atrybutu data-conversation w popupie");
            return;
        }
        try {
            window.npcConversations[npcId] = JSON.parse(conversationJson);
        } catch (e) {
            console.error("Błąd parsowania data-conversation:", e);
            return;
        }
    }

    const conversationContainer = popupContainer.querySelector("#conversation");
    if (!conversationContainer) {
        console.error("Nie znaleziono kontenera rozmowy w popupie");
        return;
    }
    const currentUserId = conversationContainer.getAttribute('data-current-user-id');
    const conversationData = window.npcConversations[npcId];
    let currentQuestionId = 1;



    function filterData(value) {
        if (value === false || value === null || value === 0) {
            return undefined;
        }
        if (Array.isArray(value)) {
            const newArr = [];
            value.forEach(item => {
                const filtered = filterData(item);
                if (filtered !== undefined) {
                    newArr.push(filtered);
                }
            });
            return newArr;
        }
        if (typeof value === "object") {
            const newObj = {};
            Object.keys(value).forEach(key => {
                const filtered = filterData(value[key]);
                if (filtered !== undefined) {
                    newObj[key] = filtered;
                }
            });
            return newObj;
        }
        return value;
    }

    function renderQuestion(questionId) {
        const index = parseInt(questionId, 10) - 1;
        const questionObj = conversationData[index];
        if (!questionObj) {
            console.error("Nie znaleziono pytania dla id:", questionId);
            return;
        }
        conversationContainer.innerHTML = "";
        const qEl = document.createElement("p");
        qEl.innerHTML = questionObj.question;
        conversationContainer.appendChild(qEl);
        const answersContainer = document.createElement("div");
        answersContainer.className = "answers-container";
        questionObj.answers.forEach(answer => {
            const btn = document.createElement("button");
            answersContainer.setAttribute("last-question-id", questionId);
            btn.textContent = answer.answer_text;
            Object.keys(answer).forEach(key => {
                let value = answer[key];
                if (value === false || value === null || value === 0) return;
                if (typeof value === "object") {
                    const filtered = filterData(value);
                    value = JSON.stringify(filtered);
                }
                btn.setAttribute(`data-${key.replace(/_/g, "-")}`, value);
            });
            btn.addEventListener("click", handleAnswer);
            answersContainer.appendChild(btn);
        });
        conversationContainer.appendChild(answersContainer);
    }


    async function handleAnswer(event) {
        const dataset = event.target.dataset;
        const answerObj = {};
        Object.keys(dataset).forEach(key => {
            let value = dataset[key];
            if (value && (value.startsWith("{") || value.startsWith("["))) {
                try {
                    value = JSON.parse(value);
                } catch (e) { }
            }
            answerObj[key] = value;
        });

        const nextQuestionId = answerObj.nextQuestion;
        const questionType = answerObj.questionType;
        let message = null;
        let popupstate = null;

        switch (questionType) {
            case "function":
                window.lastDataFunction = [{ function_name: answerObj.function, npc_id: npcId }];
                window.npcId = npcId;
                break;

            case "transaction":
                const transactions = answerObj.transaction;
                for (const transaction of transactions) {
                    switch (transaction.transaction_type) {
                        case "bag": {
                            const addRemove = parseInt(transaction.add_remove, 10);
                            const typevalue = transaction.transaction_type + "." + transaction.bag;
                            let friendly = transaction.bag;
                            if (transaction.bag === 'gold') {
                                friendly = 'złote';
                            } else if (transaction.bag === 'papierosy') {
                                friendly = 'szlug';
                            } else if (transaction.bag === 'piwo') {
                                friendly = 'browara';
                            }
                            try {
                                const response = await updateACFFieldsWithGui({ [typevalue]: addRemove }, ['body'], 'test');

                                const bagMessage = `${addRemove} ${friendly}.`;

                                message = message ? `${message} oraz ${bagMessage}` : bagMessage;
                                popupstate = 'success';
                            } catch (error) {
                                const container = document.querySelector(".answers-container");
                                const errorMessage = `Nie masz wystarczająco dużo ${friendly}`;

                                message = message ? `${message} oraz ${errorMessage}` : errorMessage;
                                popupstate = 'error';
                                showPopup(message, popupstate);
                                if (container) {
                                    const lastQuestionId = container.getAttribute("last-question-id");
                                    return renderQuestion(lastQuestionId);
                                }
                                return;
                            }
                            break;
                        }

                        case "relation": {
                            const relationNPC = transaction.target_npc;
                            const relationChange = parseInt(transaction.relation_change, 10);
                            const field_name = `npc-relation-user-${currentUserId}`;
                            if (relationChange > 0) {
                                message = `Ziomeczek Cię bardziej lubi, cena: ` + message;
                                popupstate = 'success';
                            }
                            else if (relationChange < 0) {
                                message = `Wkurwiłeś ziomeczka`;
                                popupstate = 'error';
                            }

                            updatePostACFFields(relationNPC, { [field_name]: relationChange });
                            break;
                        }

                        default:
                            break;
                    }
                }

                break;

            default:
                break;
        }

        // Wywołaj popup tylko, jeśli message i popupstate są ustawione
        if (message && popupstate) {
            showPopup(message, popupstate);
        }
        if (nextQuestionId !== "0") {
            renderQuestion(parseInt(nextQuestionId, 10));
        } else {
            popupContainer.remove();
            runFunctionNPC(window.lastDataFunction);
            window.lastDataFunction = null;
        }
    }




    renderQuestion(currentQuestionId);
}
