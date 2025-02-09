document.getElementById('create-village-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const title = document.getElementById('village-title').value.trim();
    const terenId = document.getElementById('teren-id').value;
    if (!title) {
        showPopup("Podaj nazwę grupy!", "error");
        return;
    }
    try {
        // Definiujemy obiekt kosztów – odejmujemy 200 złota
        const costFields = {
            "minerals.gold": -200
        };
        // Funkcja createGroupWithCost najpierw sprawdza, czy użytkownik już nie należy do grupy
        // oraz czy ma wystarczająco złota, a następnie tworzy grupę i odjęcie kosztu następuje dopiero po sukcesie.
        const groupResponse = await createGroupWithCost(title, terenId, costFields);
        // Jeśli grupa została utworzona, popup o sukcesie zostanie wyświetlony przez funkcję createGroupWithCost
    } catch (error) {
        console.error(error);
        showPopup(error, "error");
    }
});