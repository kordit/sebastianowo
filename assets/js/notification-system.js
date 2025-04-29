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
            duration: options.duration || 100000000, // Czas wyświetlania w ms (domyślnie 10 sekund)
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

    displayNotification(message, status = 'neutral', options = {}) {
        const notification = document.createElement('div');
        notification.className = `game-notification game-notification-${status}`;

        // Dodaj ikonę w zależności od statusu
        let icon = '';
        switch (status) {
            case 'success':
                icon = '<span class="notification-icon">✓</span>';
                break;
            case 'bad':
                icon = '<span class="notification-icon">⚠</span>';
                break;
            case 'failed':
                icon = '<span class="notification-icon">✗</span>';
                break;
            case 'neutral':
                icon = '<span class="notification-icon">ℹ</span>';
                break;
        }

        notification.innerHTML = `
            ${icon}
            <div class="notification-content">
                ${message}
            </div>
            <button class="notification-close">×</button>
        `;

        // Dodaj przycisk zamykania
        const closeButton = notification.querySelector('.notification-close');
        closeButton.addEventListener('click', () => this.close(notification));

        // Dodaj do kontenera
        this.container.appendChild(notification);
        this.notifications.push(notification);

        // Animacja wejścia
        if (this.options.animation) {
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
        } else {
            notification.classList.add('show');
        }

        // Usuń starsze powiadomienia jeśli przekroczono limit
        while (this.notifications.length > this.options.maxNotifications) {
            this.close(this.notifications[0]);
        }

        // Automatyczne zamknięcie po określonym czasie (jeśli duration > 0)
        if (options.duration !== 0 && this.options.duration > 0) {
            const duration = options.duration || this.options.duration;
            setTimeout(() => {
                this.close(notification);
            }, duration);
        }

        return notification;
    }

    /**
     * Zamyka i usuwa powiadomienie
     * @param {HTMLElement} notification - Element powiadomienia do zamknięcia
     */
    close(notification) {
        if (!notification) return;

        notification.classList.remove('show');
        notification.classList.add('hide');

        // Usuń element po zakończeniu animacji
        setTimeout(() => {
            if (notification.parentNode === this.container) {
                this.container.removeChild(notification);
            }
            this.notifications = this.notifications.filter(item => item !== notification);
        }, 300); // Czas trwania animacji wyjścia
    }

    /**
     * Metody pomocnicze do różnych typów powiadomień
     */
    success(message, options = {}) {
        return this.show(message, 'success', options);
    }

    bad(message, options = {}) {
        return this.show(message, 'bad', options);
    }

    failed(message, options = {}) {
        return this.show(message, 'failed', options);
    }

    neutral(message, options = {}) {
        return this.show(message, 'neutral', options);
    }

    /**
     * Usuwa wszystkie powiadomienia
     */
    clearAll() {
        [...this.notifications].forEach(notification => this.close(notification));
    }
}

// Inicjalizacja globalnej instancji NotificationSystem
window.gameNotifications = new NotificationSystem();

// Funkcja pomocnicza do wyświetlania powiadomień (do użytku globalnego)
window.showNotification = function (message, status = 'neutral', options = {}) {
    return window.gameNotifications.show(message, status, options);
};

// Funkcja kompatybilności wstecznej - dla zastąpienia istniejących użyć showPopup
window.showPopup = function (message, status = 'neutral') {
    // Mapowanie statusów ze starych na nowe
    const statusMap = {
        'success': 'success',
        'error': 'failed',
        'bad': 'bad',
        'neutral': 'neutral'
    };

    const mappedStatus = statusMap[status] || 'neutral';
    return window.gameNotifications.show(message, mappedStatus);
};
