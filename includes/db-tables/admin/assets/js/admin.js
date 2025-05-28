/**
 * Game Admin Panel JavaScript
 * Vanilla JS - bez jQuery!
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('Game Admin Panel loaded');

    // Inicjalizacja wszystkich funkcjonalności
    initConfirmButtons();
    initFormValidation();
    initProgressBars();
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


/**
 * Inicjalizuje animowane paski postępu
 */
function initProgressBars() {
    const progressBars = document.querySelectorAll('.progress-bar-large');

    progressBars.forEach(bar => {
        const fill = bar.querySelector('.progress-fill');
        const text = bar.querySelector('.progress-text');

        if (fill && text) {
            // Animuj pasek przy ładowaniu
            setTimeout(() => {
                fill.style.transition = 'width 1s ease-in-out';
            }, 100);

            // Aktualizuj pasek gdy zmieniają się wartości życia/energii
            const parentRow = bar.closest('tr');
            if (parentRow) {
                const inputs = parentRow.querySelectorAll('input[type="number"]');
                inputs.forEach(input => {
                    input.addEventListener('input', function () {
                        updateProgressBar(bar, inputs);
                    });
                });
            }
        }
    });
}

/**
 * Aktualizuje pasek postępu na podstawie wartości inputów
 */
function updateProgressBar(progressBar, inputs) {
    if (inputs.length !== 2) return;

    const current = parseInt(inputs[0].value) || 0;
    const max = parseInt(inputs[1].value) || 1;
    const percentage = Math.min(Math.max((current / max) * 100, 0), 100);

    const fill = progressBar.querySelector('.progress-fill');
    const text = progressBar.querySelector('.progress-text');

    if (fill && text) {
        fill.style.width = percentage + '%';
        text.textContent = current + '/' + max;
    }
}

/**
 * Pokazuje powiadomienie
 */
function showNotification(message, type = 'info', duration = 5000) {
    // Usuń poprzednie powiadomienia
    const existing = document.querySelectorAll('.game-notification');
    existing.forEach(notif => notif.remove());

    const notification = document.createElement('div');
    notification.className = `game-notification game-notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentNode.remove()">&times;</button>
    `;

    document.body.appendChild(notification);

    if (duration > 0) {
        setTimeout(() => {
            notification.remove();
        }, duration);
    }
}
