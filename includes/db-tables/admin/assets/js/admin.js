/**
 * Game Admin Panel JavaScript
 * Vanilla JS - bez jQuery!
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('Game Admin Panel loaded');

    // Inicjalizacja wszystkich funkcjonalności
    initConfirmButtons();
    initProgressBars();
    initNPCRelationSliders();
    updateFightsTotal();
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
    const progressBars = document.querySelectorAll('.ga-progress');

    progressBars.forEach(bar => {
        const fill = bar.querySelector('.ga-progress__fill');
        const text = bar.querySelector('.ga-progress__text');

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

    const fill = progressBar.querySelector('.ga-progress__fill');
    const text = progressBar.querySelector('.ga-progress__text');

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
    const existing = document.querySelectorAll('.ga-notice');
    existing.forEach(notif => notif.remove());

    const notification = document.createElement('div');
    notification.className = `ga-notice ga-notice--${type}`;
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

/**
 * Inicjalizacja sliderów relacji NPC
 */
function initNPCRelationSliders() {
    const sliders = document.querySelectorAll('.ga-range__slider');
    const numberInputs = document.querySelectorAll('.ga-range__input');

    // Synchronizacja slider -> number input
    sliders.forEach(slider => {
        slider.addEventListener('input', function () {
            const npcId = this.dataset.npc;
            const numberInput = document.querySelector(`.ga-range__input[data-npc="${npcId}"]`);
            const preview = this.closest('.ga-relation__content').querySelector('.relation-bar-preview .relation-fill');

            if (numberInput) {
                numberInput.value = this.value;
            }

            updateRelationPreview(preview, this.value);
        });
    });

    // Synchronizacja number input -> slider
    numberInputs.forEach(input => {
        input.addEventListener('input', function () {
            const npcId = this.dataset.npc;
            const slider = document.querySelector(`.ga-range__slider[data-npc="${npcId}"]`);
            const preview = this.closest('.ga-relation__content').querySelector('.relation-bar-preview .relation-fill');

            // Walidacja zakresu
            let value = parseInt(this.value);
            if (isNaN(value)) value = 0;
            if (value < -100) value = -100;
            if (value > 100) value = 100;
            this.value = value;

            if (slider) {
                slider.value = value;
            }

            updateRelationPreview(preview, value);
        });
    });
}

/**
 * Aktualizuje wizualny podgląd relacji
 */
function updateRelationPreview(preview, value) {
    if (!preview) return;

    const absValue = Math.abs(value);
    const width = absValue + '%';

    // Usuń wszystkie klasy
    preview.classList.remove('positive', 'negative', 'neutral');

    // Dodaj odpowiednią klasę
    if (value > 0) {
        preview.classList.add('positive');
    } else if (value < 0) {
        preview.classList.add('negative');
    } else {
        preview.classList.add('neutral');
    }

    // Ustaw szerokość
    preview.style.width = value === 0 ? '2px' : width;
}

/**
 * Oblicza łączną liczbę walk
 */
function updateFightsTotal() {
    const fightInputGroups = document.querySelectorAll('.ga-fight-stats');

    fightInputGroups.forEach(group => {
        const inputs = group.querySelectorAll('.ga-form-control--small');
        const totalElement = group.parentNode.querySelector('.ga-stat-value');

        if (totalElement) {
            inputs.forEach(input => {
                input.addEventListener('input', function () {
                    let total = 0;
                    inputs.forEach(inp => {
                        const val = parseInt(inp.value) || 0;
                        total += val;
                    });
                    totalElement.textContent = total;
                });
            });
        }
    });
}
