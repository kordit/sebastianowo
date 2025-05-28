/**
 * Game Admin Panel JavaScript
 * Vanilla JS - bez jQuery!
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('Game Admin Panel loaded');

    // Tutaj będą funkcje dla przyszłych interakcji
    // np. walidacja formularzy, dynamiczne ładowanie list graczy itp.

    initConfirmButtons();
});

/**
 * Dodaje potwierdzenia do niebezpiecznych akcji
 */
function initConfirmButtons() {
    const dangerButtons = document.querySelectorAll('input[type="submit"][onclick*="confirm"]');

    dangerButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            const message = button.getAttribute('onclick').match(/confirm\('([^']+)'\)/);
            if (message && !confirm(message[1])) {
                e.preventDefault();
                return false;
            }
        });
    });
}
