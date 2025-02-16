async function createGroupProcess(title, acfFields, costFields) {
    try {
        // Najpierw pobieramy hajs (odejmujemy koszt)
        await updateACFFieldsWithGui(costFields, ['body'], "Koszt został pobrany.");

        // Następnie próbujemy utworzyć grupę (wpis typu 'group')
        const groupResponse = await createCustomPost(title, 'group', acfFields);
        const groupId = groupResponse.data.post_id;

        // Po pomyślnym utworzeniu grupy aktualizujemy pole użytkownika (np. "my_group")
        await updateACFFields({ my_group: groupId });

        createCustomPopup({
            imageId: 54,
            header: "Grupa została utworzona!",
            description: "Twoja grupa została zarejestrowana i przypisana do Twojego konta.",
            link: groupResponse.data.post_url,
            linkLabel: "Przejdź do grupy",
            status: "success",
            closeable: false
        });

        return groupResponse;
    } catch (error) {
        // Jeśli błąd dotyczy istnienia grupy, refundujemy pobrany koszt
        if (error.message && error.message.includes("istnieje")) {
            const refundFields = {};
            // Dla każdego pola kosztowego mnożymy wartość przez -1
            for (let key in costFields) {
                refundFields[key] = -costFields[key];
            }
            await updateACFFieldsWithGui(refundFields, ['body'], "Koszt został zwrócony.");
        }
        throw error;
    }
}

const createGroupForm = document.getElementById('create-group-form');
if (createGroupForm) {
    createGroupForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const title = document.getElementById('group-title').value.trim();
        if (!title) {
            showPopup("Podaj nazwę grupy!", "error");
            return;
        }
        const selectedColor = document.querySelector('input[name="color-district"]:checked');
        if (!selectedColor) {
            showPopup("Wybierz kolor osiedla!", "error");
            return;
        }
        const terenId = document.getElementById('teren-id').value;
        const userId = parseInt(document.getElementById('user-id').value, 10);

        // Definicja pól ACF – uwzględniamy kolor, lidera, lokalizację i członków
        const acfFields = {
            field_color_district: selectedColor.value,
            field_leader: userId,
            field_teren_grupy: [parseInt(terenId, 10)],
            field_the_villagers: [userId]
        };
        // Koszt utworzenia grupy: odejmujemy 200 złota
        const costFields = {
            "bag.gold": -500,
            "bag.piwo": -20,
            "bag.papierosy": -200,

        };

        try {
            await createGroupProcess(title, acfFields, costFields);
        } catch (error) {
            showPopup(error.message || error, "error");
        }
    });
}

document.getElementById("go-to-a-walk").addEventListener("click", async () => {
    try {
        // Pobieranie ID podstrony
        const bodyClasses = document.body.classList;
        const postIdClass = [...bodyClasses].find(cls => cls.startsWith("postid-"));
        const postId = postIdClass ? postIdClass.replace("postid-", "") : null;
        const currentUrl = window.location.pathname;

        console.log("📌 ID podstrony:", postId);

        // ✅ Odejmowanie energii o 1
        await updateACFFieldsWithGui({ "stats.energy": -1 });

        // ✅ Pobieranie wylosowanego zdarzenia
        const response = await AjaxHelper.sendRequest(global.ajaxurl, "POST", {
            action: "get_random_event",
            post_id: postId
        });

        if (!response.success) {
            throw new Error(response.data?.message || "Nieznany błąd serwera");
        }

        const eventData = response.data;
        console.log("🔹 Wylosowane zdarzenie:", eventData);

        // ✅ Aktualizacja zasobów w GUI
        if (eventData.acf_updates && Object.keys(eventData.acf_updates).length > 0) {
            console.log("🔄 Aktualizacja ACF GUI:", eventData.acf_updates);
            await updateACFFieldsWithGui(eventData.acf_updates);
        }

        // ✅ Obsługa NPC
        if (eventData.events_type === "npc") {
            console.log("🔹 Trafiono NPC, otwieranie popupu...");

            AjaxHelper.sendRequest(global.ajaxurl, "POST", {
                action: "get_npc_popup",
                npc_id: eventData.npc,
                page_id: JSON.stringify(getPageData()),
                current_url: window.location.href
            }).then((response) => {
                console.log("🟢 Otrzymana odpowiedź AJAX:", response);

                if (!response.success) {
                    console.error("❌ Błąd pobierania NPC Popup:", response.data);
                    return;
                }

                const { html, npc_data } = response.data;
                const trimmedData = html.trim();

                let popup = document.getElementById(npc_data.popup_id);
                if (!popup) {
                    console.warn("⚠ Nie znaleziono NPC Popup, tworzę nowy...");
                    document.body.insertAdjacentHTML("beforeend", trimmedData);
                    popup = document.getElementById(npc_data.popup_id);
                }

                setTimeout(() => {
                    if (!popup) {
                        console.error("❌ Popup nadal nie istnieje!");
                        return;
                    }

                    popup.classList.add("active");

                    if (npc_data.conversation) {
                        popup.setAttribute("data-conversation", JSON.stringify(npc_data.conversation));
                    } else {
                        console.warn("⚠ Brak danych konwersacji, ale popup otwarty.");
                    }

                    initNpcPopup(eventData.npc, npc_data.popup_id, true);
                }, 500);
            });

        } else if (eventData.events_type === "event") {
            console.log("🔹 Trafiono Event");

            if (!currentUrl.includes("/spacer")) {
                console.log("🔹 Przekierowanie na spacer...");
                window.location.href = eventData.redirect_url + "?losuj=1";
            } else {
                console.log("🔹 Jesteś już na /spacer – generowanie popupa...");
                createCustomPopup({
                    imageId: eventData.image_id || 13,
                    header: eventData.header,
                    description: eventData.description,
                    link: eventData.redirect_url + "?losuj=1",
                    linkLabel: "Idź dalej",
                    status: "success",
                    closeable: true
                });
            }
        } else {
            console.error("❌ Nieznany typ zdarzenia:", eventData);
        }
    } catch (error) {
        console.error("❌ Błąd przy losowaniu eventu:", error);
        error = error.replace("stats.energy", "energii");
        showPopup(error, "error");
    }
});
