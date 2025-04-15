// Asynchroniczna funkcja ustawiania klasy postaci
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
        142: "skin",
        145: "blokers",
        143: "dres"
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

createCustomPopup({
    imageId: 149,
    header: "Podróż do Sebastianowa",
    description: "Jeszcze zanim otwierasz drzwi, już widzisz ich w oknie. Trzech typów. Każdy inny, ale każdy wygląda, jakby nie miał dziś najlepszego dnia. Czuć, że w tym wagonie nie będzie miłej pogawędki. Pociąg rusza, drzwi zamykają się za tobą. Nie masz wyboru – siadasz. Ich spojrzenia już na tobie wiszą. Ktoś pierwszy się odezwie. Porozmawiaj z każdym z nich. Kliknij na ich avatary.",
    link: "",
    linkLabel: "",
    status: "active",
    closeable: true
});
