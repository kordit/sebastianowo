/**
 * System powiadomień dla gry
 * Obsługuje różne typy powiadomień: success, bad, failed, neutral
 */

// Główna klasa obsługująca system powiadomień
class NotificationSystem {
    constructor(options = {}) {
        this.options = {
            container: options.container || 'body',
            animation: options.animation || true,
            duration: options.duration || 5000, // Czas wyświetlania w ms (domyślnie 5 sekund)
            maxNotifications: options.maxNotifications || 5, // Maksymalna liczba jednocześnie wyświetlanych powiadomień
            position: options.position || 'bottom-right', // Pozycja powiadomień
            delay: options.delay || 250 // Opóźnienie między pojawianiem się powiadomień (ms)
        };

        this.notifications = [];
        this.notificationQueue = [];
        this.isProcessingQueue = false;
        this.containerId = 'game-notifications-container';
        this.init();
    }

    // Inicjalizacja systemu powiadomień
    init() {
        // Sprawdź czy kontener już istnieje
        if (!document.getElementById(this.containerId)) {
            const container = document.createElement('div');
            container.id = this.containerId;
            container.className = `game-notifications-wrapper ${this.options.position}`;

            // Dodaj kontener do DOM
            const target = document.querySelector(this.options.container);
            if (target) {
                target.appendChild(container);
            } else {
                document.body.appendChild(container);
            }
        }

        this.container = document.getElementById(this.containerId);
    }

    /**
     * Dodaje powiadomienie do kolejki i rozpoczyna przetwarzanie kolejki jeśli nie jest aktywne
     * @param {String} message - Treść powiadomienia
     * @param {String} status - Status powiadomienia (success, bad, failed, neutral)
     * @param {Object} options - Dodatkowe opcje
     */
    show(message, status = 'neutral', options = {}) {
        // Unikaj pustych lub niezdefiniowanych wiadomości
        if (!message) return null;

        // Dodaj powiadomienie do kolejki
        this.notificationQueue.push({ message, status, options });

        // Rozpocznij przetwarzanie kolejki, jeśli jeszcze nie jest aktywne
        if (!this.isProcessingQueue) {
            this.processQueue();
        }

        // Zwróć identyfikator powiadomienia (dla kompatybilności)
        return {
            queued: true,
            message,
            status
        };
    }

    /**
     * Przetwarza kolejkę powiadomień sekwencyjnie z opóźnieniem
     */
    processQueue() {
        if (this.notificationQueue.length === 0) {
            this.isProcessingQueue = false;
            return;
        }

        this.isProcessingQueue = true;

        // Pobierz pierwsze powiadomienie z kolejki
        const { message, status, options } = this.notificationQueue.shift();

        // Wyświetl powiadomienie
        this.displayNotification(message, status, options);

        // Zaplanuj przetworzenie kolejnego powiadomienia po opóźnieniu
        setTimeout(() => {
            this.processQueue();
        }, this.options.delay);
    }

    /**
     * Tworzy i wyświetla powiadomienie na stronie
     * @param {String} message - Treść powiadomienia
     * @param {String} status - Status powiadomienia
     * @param {Object} options - Dodatkowe opcje
     */
    displayNotification(message, status = 'neutral', options = {}) {
        const notification = document.createElement('div');
        notification.className = `game-notification game-notification-${status}`;

        // Dodaj ikonę w zależności od statusu
        let icon = '';
        switch (status) {
            case 'success':
                icon = '✓';
                break;
            case 'bad':
                icon = '⚠';
                break;
            case 'failed':
                icon = '✗';
                break;
            default:
                icon = 'i';
                break;
        }

        // Utwórz strukturę HTML powiadomienia
        notification.innerHTML = `
            <div class="notification-icon">${icon}</div>
            <div class="notification-content">${message}</div>
            <button class="notification-close" aria-label="Zamknij">×</button>
        `;

        // Znajdź przycisk zamknięcia i dodaj mu obsługę zdarzenia
        const closeButton = notification.querySelector('.notification-close');
        closeButton.addEventListener('click', () => {
            this.hideNotification(notification);
        });

        // Jeśli mamy za dużo powiadomień, usuń najstarsze
        if (this.notifications.length >= this.options.maxNotifications) {
            const oldestNotification = this.notifications[0];
            this.hideNotification(oldestNotification);
        }

        // Dodaj powiadomienie do kontenera
        this.container.appendChild(notification);
        this.notifications.push(notification);

        // Uruchom animację wejścia
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Automatyczne zamknięcie po określonym czasie
        if (this.options.duration) {
            setTimeout(() => {
                this.hideNotification(notification);
            }, options.duration || this.options.duration);
        }
    }

    /**
     * Usuwa powiadomienie ze strony z animacją
     * @param {HTMLElement} notification - Element powiadomienia
     */
    hideNotification(notification) {
        if (!notification || notification.classList.contains('hide')) {
            return;
        }

        // Zastosuj animację wyjścia
        notification.classList.add('hide');
        notification.classList.remove('show');

        // Usuń z DOM i z tablicy powiadomień po zakończeniu animacji
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
            this.notifications = this.notifications.filter(n => n !== notification);
        }, 300); // Czas trwania animacji
    }

    /**
     * Usuwa wszystkie aktywne powiadomienia
     */
    clearAll() {
        this.notifications.forEach(notification => {
            this.hideNotification(notification);
        });
        this.notificationQueue = [];
    }
}

// Globalna instancja systemu powiadomień
window.gameNotifications = new NotificationSystem();

/**
 * Funkcja pomocnicza dla wstecznej kompatybilności - zastępuje stary system powiadomień
 * @param {String} message - Treść powiadomienia
 * @param {String} type - Typ powiadomienia (success, error, bad, neutral)
 */
function showPopup(message, type = 'success') {
    // Mapowanie starych typów na nowe
    const statusMap = {
        'success': 'success',
        'error': 'failed',
        'bad': 'bad',
        'neutral': 'neutral'
    };

    // Użyj globalnej instancji do pokazania powiadomienia
    const status = statusMap[type] || 'neutral';
    window.gameNotifications.show(message, status);
}

// Eksport globalny dla wstecznej kompatybilności
window.NotificationSystem = NotificationSystem;
window.showPopup = showPopup;
