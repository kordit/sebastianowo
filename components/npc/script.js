
function buildNpcPopup(npcData, userId) {
    // Remove any existing popup for a fresh display
    const existingPopup = document.getElementById('npc-popup');
    if (existingPopup) {
        existingPopup.remove();
    }

    // Create container
    const popupContainer = document.createElement('div');
    popupContainer.id = 'npc-popup';
    popupContainer.className = 'controler-popup ';
    popupContainer.npcData = npcData; // Store npcData in the popup
    setTimeout(() => {
        popupContainer.classList.add('active');
    }, 100);

    // NPC Thumbnail (if exists)
    if (npcData.npc_thumbnail) {
        const img = document.createElement('img');
        img.src = npcData.npc_thumbnail;
        img.alt = npcData.npc_name || 'NPC Image';
        img.className = 'npc-thumbnail';
        popupContainer.appendChild(img);
    }

    // Conversation Display
    const conversationWrapper = document.createElement('div');
    conversationWrapper.className = 'npc-conversation-wrapper';
    popupContainer.appendChild(conversationWrapper);

    // Add the container to the body
    document.body.appendChild(popupContainer);

    // Initial index is 0
    console.log()
    renderConversation(0, npcData, conversationWrapper, popupContainer, userId);
}

function renderConversation(index, npcData, conversationWrapper, popupContainer, userId) {
    const conversationStep = npcData.conversation[index];

    if (!conversationStep) {
        // No more questions, remove popup or do something else
        popupContainer.remove();
        return;
    }

    const newQuestionEl = document.createElement('div');
    newQuestionEl.className = 'npc-question';
    newQuestionEl.style.opacity = 0; // Start hidden

    const npcNameElement = document.createElement('h2');
    npcNameElement.textContent = (npcData.npc_name ?? 'Unknown NPC') + ' mówi';

    const questionTextElement = document.createElement('p');
    questionTextElement.innerHTML = conversationStep.question ?? '';

    newQuestionEl.appendChild(npcNameElement);
    newQuestionEl.appendChild(questionTextElement);

    const answersEl = document.createElement('div');
    answersEl.className = 'npc-answers';

    (conversationStep.answers || []).forEach((answer) => {
        const answerBtn = document.createElement('button');
        answerBtn.textContent = answer.answer_text || '...';
        answerBtn.className = 'npc-answer-btn';

        // Add data attributes to the button
        Object.keys(answer).forEach(key => {
            let val = answer[key];
            if (typeof val === 'object') {
                val = JSON.stringify(val);
            }
            answerBtn.dataset[key] = val; // Use dataset to set data attributes
        });
        answerBtn.dataset.userId = userId; // Store user ID in the dataset

        answerBtn.addEventListener('click', handleAnswer);
        answersEl.appendChild(answerBtn);
    });

    // Fade out old content, fade in new content
    if (conversationWrapper.firstChild) {
        conversationWrapper.firstChild.style.transition = 'opacity 0.5s';
        conversationWrapper.firstChild.style.opacity = 0;
        setTimeout(() => {
            conversationWrapper.innerHTML = ''; // Clear previous
            conversationWrapper.appendChild(newQuestionEl);
            conversationWrapper.appendChild(answersEl);

            // Fade in new content with delay
            setTimeout(() => {
                newQuestionEl.style.transition = 'opacity 0.5s'; // Duration of 0.5s
                newQuestionEl.style.opacity = 1;
            }, 100); // Delay of 100ms before fade-in starts

        }, 500); // Wait for fade out
    } else {
        // Initial load
        conversationWrapper.innerHTML = '';
        conversationWrapper.appendChild(newQuestionEl);
        conversationWrapper.appendChild(answersEl);

        // Fade in new content with delay
        setTimeout(() => {
            newQuestionEl.style.transition = 'opacity 0.5s'; // Duration of 0.5s
            newQuestionEl.style.opacity = 1;
        }, 100); // Delay of 100ms before fade-in starts
    }
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
    console.log('Odpowiedź:', answerObj);

    const nextQuestionId = answerObj.next_question;
    const questionType = answerObj.question_type;
    let message = null;
    let popupstate = null;
    console.log(questionType);
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
                            await updateACFFieldsWithGui({ [typevalue]: addRemove }, ['body'], 'test')

                            const numVal = parseInt(addRemove, 10)
                            let bagMessage = numVal < 0
                                ? `Wydano ${Math.abs(numVal)} ${friendly}`
                                : `Otrzymano ${numVal} ${friendly}`

                            message = message
                                ? `${message} i ${bagMessage}`
                                : bagMessage

                            popupstate = 'success'
                            showPopup(message, popupstate)
                        } catch (error) {
                            const container = document.querySelector(".answers-container");
                            const errorMessage = `Nie masz wystarczająco dużo ${friendly}`;

                            message = message ? `${message} oraz ${errorMessage}` : errorMessage;
                            popupstate = 'error';

                            showPopup(message, popupstate);
                            return;
                        }
                        break;
                    }

                    case "relation": {
                        const relationNPC = transaction.target_npc;
                        const relationChange = parseInt(transaction.relation_change, 10);
                        const userId = answerObj.userId; // Get the userId from the dataset
                        const field_name = `npc-relation-user-${userId}`;
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

    // Handle moving to the next question or closing the popup
    const popupContainer = document.getElementById('npc-popup'); // Get the popup container

    // Get userId from the dataset
    const userId = answerObj.userId;

    const nextQId = parseInt(nextQuestionId, 10);

    if (nextQId === 0) {
        if (popupContainer) {
            popupContainer.remove(); // Close popup
        }
    } else {
        // Move to the next question
        const newIndex = nextQId - 1; // Calculate the new index

        if (popupContainer) {
            const conversationWrapper = popupContainer.querySelector('.npc-conversation-wrapper');
            const npcData = popupContainer.npcData;
            // Call renderConversation with the updated index and other necessary parameters
            renderConversation(newIndex, npcData, conversationWrapper, popupContainer, userId);
        }
    }
}