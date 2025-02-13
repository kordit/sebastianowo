<?php
$npc_id = get_field('npc')[0]->ID;
$current_user_id = get_current_user_id();
get_npc($npc_id);
?>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Funkcja, która przekształca tablicę transakcji do notacji kropkowej
        function transformTransactionArrayToDotNotation(transactions) {
            const result = {};
            transactions.forEach(tx => {
                if (!tx.hasOwnProperty("add_remove")) return;
                const change = parseFloat(tx.add_remove);
                if (isNaN(change)) return;
                // Dla każdej właściwości (poza add_remove) tworzymy klucz dot notation
                Object.keys(tx).forEach(key => {
                    if (key === "add_remove") return;
                    const dotKey = key + "." + tx[key]; // np. "bag.gold"
                    // Sumujemy zmiany, jeśli już istnieje ten klucz
                    result[dotKey] = (result[dotKey] || 0) + change;
                });
            });
            return result;
        }


        const npcContainer = document.getElementById("conversation");
        window.currentUserId = window.currentUserId || <?= $current_user_id; ?>;
        const npcId = <?= $npc_id; ?>;

        if (!window.npcConversations || !window.npcConversations[npcId]) {
            console.error("Brak danych dla NPC!");
            return;
        }
        const conversationData = window.npcConversations[npcId];
        let currentQuestionId = 1;

        function renderQuestion(questionId) {
            const index = parseInt(questionId, 10) - 1;
            const questionObj = conversationData[index];
            if (!questionObj) return;
            npcContainer.innerHTML = "";
            const questionElement = document.createElement("h2");
            questionElement.textContent = questionObj.question;
            npcContainer.appendChild(questionElement);
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
            npcContainer.appendChild(answersContainer);
        }

        async function handleAnswer(event) {
            const button = event.currentTarget;
            const nextQuestionId = button.getAttribute("data-next");
            const transactionData = button.getAttribute("data-transaction");
            const questionType = button.getAttribute("data-question-type");
            const sliderRelation = button.getAttribute("data-slider-relation");

            // Aktualizacja relacji w NPC
            if (questionType === "relation_with_npc") {
                const currentUserId = window.currentUserId;
                const fieldKey = "user-" + currentUserId + "-usera";
                const fieldsData = {};
                fieldsData[fieldKey] = parseFloat(sliderRelation);
                try {
                    const response = await updatePostACFFields(npcId, fieldsData);
                    showPopup(response.data.message, "success");
                } catch (error) {
                    showPopup(error, "error");
                }
            }

            // Aktualizacja zasobów użytkownika – przetwarzamy transakcje
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
                npcContainer.innerHTML = "<p>Koniec rozmowy.</p>";
            }
        }

        renderQuestion(currentQuestionId);
    });
</script>