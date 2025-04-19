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

function runFunctionNPC(functionsList) {
    console.log('🔥 runFunctionNPC:', functionsList);

    // Obsługa JSON w stringu
    if (typeof functionsList === 'string') {
        try {
            if (functionsList.trim().startsWith('{') || functionsList.trim().startsWith('[')) {
                functionsList = JSON.parse(functionsList);
            } else {
                // Jeśli to pojedyncza funkcja jako string
                return runFunctionNPC([{ function_name: functionsList }]);
            }
        } catch (error) {
            console.error('❌ Błąd parsowania JSON:', error);
            return;
        }
    }

    // Upewniamy się, że to tablica
    if (!Array.isArray(functionsList)) {
        functionsList = [functionsList];
    }

    functionsList.forEach(func => {
        // Jeśli mamy do czynienia z przyciskiem, który wysyła dane z data-type-anwser, użyjmy tych danych
        if (func && func.do_function) {
            // Już mamy wszystkie parametry w obiekcie, więc możemy go używać bezpośrednio
            const rawFunctionName = func.do_function;
            const functionName = rawFunctionName.replace(/-([a-z])/g, g => g[1].toUpperCase());

            if (typeof window[functionName] === 'function') {
                try {
                    console.log(`🚀 Wywołuję ${functionName}() z parametrami:`, func);
                    window[functionName](func); // Przekazujemy cały obiekt funkcji
                } catch (err) {
                    console.error(`❌ Błąd podczas wykonywania ${functionName}():`, err);
                }
            } else {
                console.error(`❌ Funkcja "${functionName}" nie istnieje w window.`);
            }
        }
        // Jeśli mamy do czynienia z obiektem z function_name, który przychodzi np. z NPC z data-target
        else if (func && func.function_name) {
            const rawFunctionName = func.function_name;
            const functionName = rawFunctionName.replace(/-([a-z])/g, g => g[1].toUpperCase());

            if (typeof window[functionName] === 'function') {
                try {
                    console.log(`🚀 Wywołuję ${functionName}() z parametrami:`, func);
                    window[functionName](func); // Przekazujemy cały obiekt funkcji
                } catch (err) {
                    console.error(`❌ Błąd podczas wykonywania ${functionName}():`, err);
                }
            } else {
                console.error(`❌ Funkcja "${functionName}" nie istnieje w window.`);
            }
        }
        else {
            console.error('❌ Nieprawidłowy format funkcji:', func);
        }
    });
}

window.runFunctionNPC = runFunctionNPC;


async function SetClass(params) {
    console.log("Wybrany NPC:", params);

    // Obsługa różnych formatów parametrów
    let npcId;
    if (params && params.npc_id) {
        // Jeśli przychodzi jako obiekt z npc_id
        npcId = params.npc_id;
    } else if (typeof params === 'string' || typeof params === 'number') {
        // Jeśli przychodzi jako bezpośrednia wartość
        npcId = params;
    } else {
        console.error("Nieprawidłowy format parametrów:", params);
        return;
    }

    const containerWorld = document.querySelector('.container-world');
    const stepElement = document.querySelector('.step');

    if (!containerWorld || !stepElement) {
        console.error("Elementy .container-world lub .step nie istnieją.");
        return;
    }

    // Mapowanie NPC na klasy postaci
    const classMap = {
        70: "zawijacz",
        76: "zadymiarz",
        77: "kombinator"
    };

    // Ustawiamy klasę na podstawie ID NPC
    window.selectedClass = classMap[npcId] || '';
    console.log("Ustawiam klasę na podstawie NPC ID:", npcId, "->", window.selectedClass);

    // Dodanie efektu przejścia
    containerWorld.classList.add('zooming');
    await new Promise(resolve => setTimeout(resolve, 100));
    stepElement.classList.add('active');

    if (!window.selectedClass) {
        console.error("Nieznany NPC ID:", npcId, "- nie znaleziono odpowiedniej klasy");
        return;
    }

    console.log("Przypisana klasa:", window.selectedClass);

    // Automatycznie wybiera pierwszy avatar zgodny z klasą
    const avatar = document.querySelector(`.avatar-option[data-class="${window.selectedClass}"]`);
    if (avatar) {
        avatar.click();
    }
}

async function goToPage(options) {
    console.log("➡️ goToPage uruchomione z:", options);

    try {
        if (!options || typeof options !== 'object' || !options.page_url) {
            console.error("❌ Brak page_url w przekazanym obiekcie:", options);
            return;
        }

        let targetUrl = options.page_url.trim();

        if (!targetUrl) {
            console.error("❌ page_url jest pusty.");
            return;
        }

        // Jeśli URL zaczyna się od http/https – pełna ścieżka, przekieruj
        if (targetUrl.startsWith('http://') || targetUrl.startsWith('https://')) {
            window.location.href = targetUrl;
            return;
        }

        // Dodaj początkowy slash, jeśli nie ma
        if (!targetUrl.startsWith('/')) {
            targetUrl = '/' + targetUrl;
        }

        const fullUrl = window.location.origin + targetUrl;

        console.log(`✅ Przekierowanie na ${fullUrl}`);
        window.location.href = fullUrl;

    } catch (error) {
        console.error("❌ Błąd podczas przekierowania:", error);
        if (typeof showPopup === 'function') {
            showPopup("Wystąpił błąd podczas przekierowania", "error");
        }
    }
}

window.goToPage = goToPage;



// **Funkcja do obsługi wyboru avatara**
function initAvatarSelection() {
    document.querySelectorAll('.avatar-option').forEach(img => {
        img.addEventListener('click', function () {
            document.querySelectorAll('.wrapper-image').forEach(wrapper => wrapper.classList.remove('active'));
            this.parentElement.classList.add('active');

            window.selectedAvatarId = this.getAttribute('data-avatar-id');
            window.selectedFullId = this.getAttribute('data-full-id');
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

    if (!window.selectedClass || !window.selectedAvatarId || !nickname) {
        showPopup('Uzupełnij wszystkie pola!', 'error');
        return;
    }

    try {
        // Tworzymy obiekt z danymi
        const userData = {
            "user_class": window.selectedClass,
            "avatar": window.selectedAvatarId,
            "avatar_full": window.selectedFullId,
            "nick": nickname,
        };

        console.log("Dane do zapisania:", userData);

        // Bezpośrednie wywołanie funkcji updateACFFields z utworzonym obiektem
        const updateResponse = await updateACFFields(userData);

        console.log("Odpowiedź z serwera:", updateResponse);

        await createCustomPopup({
            imageId: 88,
            header: "Twój nowy bohater został utworzony!",
            description: "Gratulacje! Twoje dane zostały zaktualizowane. Przejdź do panelu, aby zobaczyć szczegóły.",
            link: "/tereny/start/krzaki/",
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