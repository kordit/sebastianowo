<button id="add-gold-btn">Dodaj 200 złota</button>
<script>
    document.getElementById('add-gold-btn').addEventListener('click', async function(e) {
        e.preventDefault();
        try {
            // Wywołanie funkcji updateACFFieldsWithGui, która aktualizuje dane,
            // aktualizuje GUI i wyświetla popup.
            // UWAGA: Mimo że przycisk nosi napis "Dodaj 200 złota",
            // ta operacja doda 100 złota – zmień wartość w obiekcie, jeśli potrzebujesz innej.
            await updateACFFieldsWithGui({
                "stats.energy": 10
            });
        } catch (error) {
            console.error("Błąd przy dodawaniu złota:", error);
        }
    });
</script>