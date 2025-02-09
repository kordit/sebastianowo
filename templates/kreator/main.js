document.addEventListener("DOMContentLoaded", function () {
    let selectedClass = '';
    let selectedAvatarId = '';

    // Krok 1: Wybór klasy postaci
    document.querySelectorAll('.select-class').forEach(button => {
        button.addEventListener('click', function () {
            selectedClass = this.closest('.class-box').getAttribute('data-class');
            document.getElementById('step-1').classList.remove('active');
            document.getElementById('step-2').classList.add('active');
        });
    });

    // Krok 2: Wybór avatara
    document.querySelectorAll('.avatar-option').forEach(img => {
        img.addEventListener('click', function () {
            document.querySelectorAll('.wrapper-image').forEach(wrapper => wrapper.classList.remove('active'));
            this.parentElement.classList.add('active');
            selectedAvatarId = this.getAttribute('data-avatar-id');

            const previewDiv = document.getElementById('preview');
            if (previewDiv) {
                previewDiv.innerHTML = '';
                const previewImg = document.createElement('img');
                previewImg.src = this.src;
                previewImg.alt = 'Podgląd wybranego avatara';
                previewImg.classList.add('preview-image');
                previewDiv.appendChild(previewImg);
            }
        });
    });

    // Krok 3: Zapis danych – aktualizacja pól ACF, a następnie utworzenie custom popupu
    document.getElementById('save-character').addEventListener('click', async function () {
        const nickname = document.getElementById('nickname').value.trim();
        const story = document.getElementById('story').value.trim();

        if (!selectedClass || !selectedAvatarId || !nickname) {
            showPopup('Uzupełnij wszystkie pola!', 'error');
            return;
        }

        try {
            // Wywołaj funkcję aktualizującą dane ACF (bez GUI)
            const updateResponse = await updateACFFields({
                "creator_end": true,
                "user_class": selectedClass,
                "avatar": selectedAvatarId,
                "nick": nickname,
                "story": story
            });
            console.log(updateResponse);

            // Po udanej aktualizacji, wywołaj custom popup.
            // Parametry popupu możesz zmieniać dynamicznie.
            await createCustomPopup({
                imageId: 12, // ID obrazka do wyświetlenia w popupie
                header: "Twój nowy bohater został utworzony!",
                description: "Gratulacje! Twoje dane zostały zaktualizowane. Przejdź do panelu, aby zobaczyć szczegóły.",
                link: "/user/me", // adres URL, gdzie chcesz przekierować użytkownika
                linkLabel: "Zacznij przygodę!",
                status: "success", // lub 'error'
                closeable: false
            });
        } catch (error) {
            console.error("Błąd podczas aktualizacji danych:", error);
            showPopup(error.message || "Wystąpił błąd", "error");
        }
    });
});
