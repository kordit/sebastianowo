document.addEventListener('DOMContentLoaded', function () {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    // Obsługa zakładek
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Usuń klasę active ze wszystkich przycisków
            tabBtns.forEach(b => b.classList.remove('active'));

            // Dodaj klasę active do klikniętego przycisku
            btn.classList.add('active');

            // Ukryj wszystkie panele
            tabPanels.forEach(panel => panel.classList.remove('active'));

            // Pokaż panel powiązany z klikniętym przyciskiem
            const tabId = btn.getAttribute('data-tab') + '-tab';
            document.getElementById(tabId).classList.add('active');
        });
    });
});