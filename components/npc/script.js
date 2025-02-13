document.querySelectorAll('[title]').forEach(el => el.removeAttribute('title'));
// document.addEventListener("DOMContentLoaded", () => {
//     // Funkcja, która przekształca tablicę transakcji do notacji kropkowej
//     function transformTransactionArrayToDotNotation(transactions) {
//         const result = {};
//         transactions.forEach(tx => {
//             if (!tx.hasOwnProperty("add_remove")) return;
//             const change = parseFloat(tx.add_remove);
//             if (isNaN(change)) return;
//             // Dla każdej właściwości (poza add_remove) tworzymy klucz dot notation
//             Object.keys(tx).forEach(key => {
//                 if (key === "add_remove") return;
//                 const dotKey = key + "." + tx[key]; // np. "bag.gold"
//                 // Sumujemy zmiany, jeśli już istnieje ten klucz
//                 result[dotKey] = (result[dotKey] || 0) + change;
//             });
//         });
//         return result;
//     }


//     const npcContainer = document.getElementById("conversation");
//     const currentUserId = npcContainer.dataset.currentUserId;
//     const npcId = npcContainer.dataset.npcId;

//     console.log('test' + currentUserId);

//     if (!window.npcConversations || !window.npcConversations[npcId]) {
//         console.error("Brak danych dla NPC!");
//         return;
//     }
//     const conversationData = window.npcConversations[npcId];
//     let currentQuestionId = 1;

//     function renderQuestion(questionId) {
//         const index = parseInt(questionId, 10) - 1;
//         const questionObj = conversationData[index];
//         if (!questionObj) return;
//         npcContainer.innerHTML = "";
//         const questionElement = document.createElement("h2");
//         questionElement.textContent = questionObj.question;
//         npcContainer.appendChild(questionElement);
//         const answersContainer = document.createElement("div");
//         answersContainer.className = "answers-container";
//         questionObj.answers.forEach(answer => {
//             const button = document.createElement("button");
//             button.textContent = answer.answer_text;
//             button.setAttribute("data-next", answer.next_question ? answer.next_question : "0");
//             button.setAttribute("data-transaction", answer.transaction ? JSON.stringify(answer.transaction) : "null");
//             button.setAttribute("data-question-type", answer.question_type);
//             button.setAttribute("data-slider-relation", answer.slider_relation);
//             button.addEventListener("click", handleAnswer);
//             answersContainer.appendChild(button);
//         });
//         npcContainer.appendChild(answersContainer);
//     }

//     async function handleAnswer(event, idPopup = "popup") {
//         const button = event.currentTarget;
//         const nextQuestionId = button.getAttribute("data-next");
//         const transactionData = button.getAttribute("data-transaction");
//         const questionType = button.getAttribute("data-question-type");
//         const sliderRelation = button.getAttribute("data-slider-relation");

//         // Aktualizacja relacji w NPC
//         if (questionType === "relation_with_npc") {
//             const fieldKey = "npc-relation-user-" + currentUserId;
//             const fieldsData = {};
//             fieldsData[fieldKey] = parseFloat(sliderRelation);
//             try {
//                 const response = await updatePostACFFields(npcId, fieldsData);
//                 showPopup(response.data.message, "success");
//             } catch (error) {
//                 showPopup(error, "error");
//             }
//         }

//         // Aktualizacja zasobów użytkownika – przetwarzamy transakcje
//         if (transactionData && transactionData !== "null") {
//             try {
//                 const transactionArray = JSON.parse(transactionData);
//                 const dotTransaction = transformTransactionArrayToDotNotation(transactionArray);
//                 console.log("Przetwarzanie transakcji (dot notation):", dotTransaction);
//                 const response = await updateACFFieldsWithGui(dotTransaction);
//                 showPopup(response.data.message, "success");
//             } catch (error) {
//                 showPopup(error, "error");
//             }
//         }

//         if (nextQuestionId !== "0") {
//             renderQuestion(parseInt(nextQuestionId, 10));
//         } else {
//             // npcContainer.innerHTML = "<p>Koniec rozmowy.</p>";
//             console.log(document.getElementById(idPopup));
//             document.querySelectorAll('.controler-popup').forEach(el => el.classList.remove("active"));

//         }
//     }

//     renderQuestion(currentQuestionId);
// });

function initNpcPopup(npcId, containerId = 'npc-popup', active = false) {
    const popupContainer = document.getElementById(containerId);
    if (!popupContainer) {
        console.error("Nie znaleziono popup container o id:", containerId);
        return;
    }
    if (active) {
        popupContainer.classList.add('active');
    }

    // Jeśli dane konwersacji nie zostały zapisane w window.npcConversations, spróbuj je pobrać z atrybutu data-conversation
    if (!window.npcConversations) {
        window.npcConversations = {};
    }
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

    function transformTransactionArrayToDotNotation(transactions) {
        const result = {};
        transactions.forEach(tx => {
            if (!tx.hasOwnProperty("add_remove")) return;
            const change = parseFloat(tx.add_remove);
            if (isNaN(change)) return;
            Object.keys(tx).forEach(key => {
                if (key === "add_remove") return;
                const dotKey = key + "." + tx[key];
                result[dotKey] = (result[dotKey] || 0) + change;
            });
        });
        return result;
    }

    function renderQuestion(questionId) {
        const index = parseInt(questionId, 10) - 1;
        const questionObj = conversationData[index];
        if (!questionObj) {
            console.error("Nie znaleziono pytania dla id:", questionId);
            return;
        }
        conversationContainer.innerHTML = "";
        const questionElement = document.createElement("h2");
        questionElement.textContent = questionObj.question;
        conversationContainer.appendChild(questionElement);
        const answersContainer = document.createElement("div");
        answersContainer.className = "answers-container";
        questionObj.answers.forEach(answer => {
            const button = document.createElement("button");
            button.textContent = answer.answer_text;
            button.setAttribute("data-next", answer.next_question ? answer.next_question : "0");
            button.setAttribute("data-transaction", answer.transaction ? JSON.stringify(answer.transaction) : "null");
            button.setAttribute("data-question-type", answer.question_type);
            button.setAttribute("data-slider-relation", answer.slider_relation);
            button.addEventListener("click", handleAnswer);
            answersContainer.appendChild(button);
        });
        conversationContainer.appendChild(answersContainer);
    }

    async function handleAnswer(event) {
        const button = event.currentTarget;
        const nextQuestionId = button.getAttribute("data-next");
        const transactionData = button.getAttribute("data-transaction");
        const questionType = button.getAttribute("data-question-type");
        const sliderRelation = button.getAttribute("data-slider-relation");

        if (questionType === "relation_with_npc") {
            const fieldKey = "npc-relation-user-" + currentUserId;
            const fieldsData = {};
            fieldsData[fieldKey] = parseFloat(sliderRelation);
            try {
                const response = await updatePostACFFields(npcId, fieldsData);
                showPopup(response.data.message, "success");
            } catch (error) {
                showPopup(error, "error");
            }
        }

        if (transactionData && transactionData !== "null") {
            try {
                const transactionArray = JSON.parse(transactionData);
                const dotTransaction = transformTransactionArrayToDotNotation(transactionArray);
                console.log("Przetwarzanie transakcji (dot notation):", dotTransaction);
                const response = await updateACFFieldsWithGui(dotTransaction);
                showPopup(response.data.message, "success");
            } catch (error) {
                showPopup(error, "error");
            }
        }

        if (nextQuestionId !== "0") {
            renderQuestion(parseInt(nextQuestionId, 10));
        } else {
            // Zamiast usuwać klasę "active", usuwamy cały popup z DOM:
            popupContainer.remove();
        }
    }

    renderQuestion(currentQuestionId);
}
