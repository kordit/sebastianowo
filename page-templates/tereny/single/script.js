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

async function startRandomEventProcess() {
    try {
        const bodyClasses = document.body.classList;
        const postIdClass = [...bodyClasses].find(cls => cls.startsWith("postid-"));
        const postId = postIdClass ? postIdClass.replace("postid-", "") : null;
        const currentUrl = window.location.href;

        await updateACFFieldsWithGui({ "stats.energy": -1 });

        const response = await AjaxHelper.sendRequest(global.ajaxurl, "POST", {
            action: "get_random_event",
            post_id: postId
        });
        if (!response.success) {
            throw new Error(response.data?.message || "Nieznany błąd serwera");
        }

        const eventData = response.data;

        if (eventData.acf_updates && Object.keys(eventData.acf_updates).length > 0) {
            await updateACFFieldsWithGui(eventData.acf_updates);
        }

        if (eventData.events_type === "npc") {
            AjaxHelper.sendRequest(global.ajaxurl, "POST", {
                action: "get_npc_popup",
                npc_id: eventData.npc,
                page_id: JSON.stringify(getPageData()),
                current_url: window.location.href
            }).then((response) => {
                if (!response.success) return;
                const { html, npc_data } = response.data;
                const trimmedData = html.trim();

                let popup = document.getElementById(npc_data.popup_id);
                if (!popup) {
                    document.body.insertAdjacentHTML("beforeend", trimmedData);
                    popup = document.getElementById(npc_data.popup_id);
                }
                setTimeout(() => {
                    if (!popup) return;
                    popup.classList.add("active");
                    if (npc_data.conversation) {
                        popup.setAttribute("data-conversation", JSON.stringify(npc_data.conversation));
                    }
                    initNpcPopup(eventData.npc, npc_data.popup_id, true);
                }, 500);
            });

        } else if (eventData.events_type === "event") {
            if (!currentUrl.includes("go-further")) {
                window.location.href = eventData.redirect_url;
            } else {
                createCustomPopup({
                    imageId: eventData.image_id || 13,
                    header: eventData.header,
                    description: eventData.description,
                    link: eventData.redirect_url,
                    linkLabel: "Idź dalej",
                    status: "success",
                    closeable: true
                });
            }
        } else {
            console.error("Nieznany typ zdarzenia:", eventData);
        }
    } catch (error) {
        // Zastąp 'stats.energy' → 'energii' i pokaż błąd
        if (typeof error === "string") {
            error = error.replace("stats.energy", "energii");
        } else if (error.message) {
            error.message = error.message.replace("stats.energy", "energii");
        }
        showPopup(error.message || error, "error");
    }
}

var goToAWalk = document.getElementById("go-to-a-walk");
if (goToAWalk) {
    goToAWalk.addEventListener("click", startRandomEventProcess);
}

document.addEventListener("DOMContentLoaded", function () {
    if (window.location.search.includes("go-further")) {
        startRandomEventProcess();
    }
});



async function SetClass(npc) {
    console.log("Wybrany NPC:", npc);

    const containerWorld = document.querySelector('.container-world');
    const stepElement = document.querySelector('.step');

    if (!containerWorld || !stepElement) {
        console.error("Elementy .container-world lub .step nie istnieją.");
        return;
    }

    // Mapowanie NPC na klasy postaci
    const classMap = {
        530: "skin",
        521: "blokers",
        548: "dres"
    };

    window.selectedClass = classMap[npc] || '';

    // Dodanie efektu przejścia
    containerWorld.classList.add('zooming');
    await new Promise(resolve => setTimeout(resolve, 1000));
    stepElement.classList.add('active');

    if (!window.selectedClass) {
        console.error("Nieznany NPC:", npc);
        return;
    }

    console.log("Przypisana klasa:", window.selectedClass);

    // Automatycznie wybiera pierwszy avatar zgodny z klasą
    const avatar = document.querySelector(`.avatar-option[data-class="${window.selectedClass}"]`);
    if (avatar) {
        avatar.click();
    }
}

// **Funkcja do obsługi wyboru avatara**
function initAvatarSelection() {
    document.querySelectorAll('.avatar-option').forEach(img => {
        img.addEventListener('click', function () {
            document.querySelectorAll('.wrapper-image').forEach(wrapper => wrapper.classList.remove('active'));
            this.parentElement.classList.add('active');

            window.selectedAvatarId = this.getAttribute('data-avatar-id');
            const previewSrc = this.getAttribute('data-pair-src');

            const previewDiv = document.getElementById('preview');
            if (previewDiv) {
                previewDiv.innerHTML = '';
                if (previewSrc) {
                    const previewImg = document.createElement('img');
                    previewImg.src = previewSrc;
                    previewImg.alt = 'Podgląd wybranego avatara';
                    previewImg.classList.add('preview-image');
                    previewDiv.appendChild(previewImg);
                }
            }
        });
    });
}

// **Funkcja do zapisu postaci**
async function saveCharacter() {
    const nickname = document.getElementById('nickname').value.trim();
    const story = document.getElementById('story').value.trim();

    if (!window.selectedClass || !window.selectedAvatarId || !nickname) {
        showPopup('Uzupełnij wszystkie pola!', 'error');
        return;
    }

    try {
        const updateResponse = await updateACFFields({
            "creator_end": true,
            "user_class": window.selectedClass,
            "avatar": window.selectedAvatarId,
            "nick": nickname,
            "story": story
        });

        console.log(updateResponse);

        await createCustomPopup({
            imageId: 12,
            header: "Twój nowy bohater został utworzony!",
            description: "Gratulacje! Twoje dane zostały zaktualizowane. Przejdź do panelu, aby zobaczyć szczegóły.",
            link: "/tereny/kolejowa",
            linkLabel: "Zacznij przygodę!",
            status: "success",
            closeable: false
        });
    } catch (error) {
        console.error("Błąd podczas aktualizacji danych:", error);
        showPopup(error.message || "Wystąpił błąd", "error");
    }
}

// **Ładowanie eventów po załadowaniu DOM**
document.addEventListener("DOMContentLoaded", function () {
    window.selectedClass = '';
    window.selectedAvatarId = '';

    initAvatarSelection();

    document.getElementById('save-character').addEventListener('click', saveCharacter);
});

// createCustomPopup({
//     imageId: 149,
//     header: "Podróż do Sebastianowa",
//     description: "Jeszcze zanim otwierasz drzwi, już widzisz ich w oknie. Trzech typów. Każdy inny, ale każdy wygląda, jakby nie miał dziś najlepszego dnia. Czuć, że w tym wagonie nie będzie miłej pogawędki. Pociąg rusza, drzwi zamykają się za tobą. Nie masz wyboru – siadasz. Ich spojrzenia już na tobie wiszą. Ktoś pierwszy się odezwie. Porozmawiaj z każdym z nich. Kliknij na ich avatary.",
//     link: "",
//     linkLabel: "",
//     status: "active",
//     closeable: true
// });
