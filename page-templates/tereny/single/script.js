
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

    const saveButton = document.getElementById('save-character');
    if (saveButton) {
        saveButton.addEventListener('click', saveCharacter);
    }
});
