/**
 * JavaScript dla panelu użytkownika
 */

document.addEventListener('DOMContentLoaded', function () {
    // Obsługa przełączania zakładek głównych
    const tabItems = document.querySelectorAll('.tab-item');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabItems.forEach(function (tab) {
        tab.addEventListener('click', function () {
            // Usunięcie klasy active ze wszystkich zakładek
            tabItems.forEach(function (item) {
                item.classList.remove('active');
            });

            // Dodanie klasy active do klikniętej zakładki
            this.classList.add('active');

            // Ukrycie wszystkich paneli zawartości
            tabPanes.forEach(function (pane) {
                pane.classList.remove('active');
            });

            // Pokazanie odpowiedniego panelu zawartości
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });

    // Opcjonalnie: obsługa zapisywania formularzy w panelu
    const forms = document.querySelectorAll('.author-panel-form');

    forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Tutaj możesz dodać kod do obsługi wysyłania formularzy przez AJAX
            const formData = new FormData(form);

            // Przykładowa obsługa AJAX
            // fetch('endpoint-url', {
            //     method: 'POST',
            //     body: formData
            // })
            // .then(response => response.json())
            // .then(data => {
            //     console.log('Success:', data);
            //     // Obsługa sukcesu
            // })
            // .catch(error => {
            //     console.error('Error:', error);
            //     // Obsługa błędu
            // });

            // Tymczasowy komunikat potwierdzenia
            alert('Zapisano zmiany!');
        });
    });
});
