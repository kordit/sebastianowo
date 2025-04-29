/**
 * Moduł wspólnych funkcji dla aplikacji
 * 
 * Ten plik zawiera funkcje ogólnego przeznaczenia używane w całej aplikacji.
 */

// Usuwanie atrybutów title z elementów dla lepszej kompatybilności z urządzeniami dotykowymi
document.querySelectorAll('[title]').forEach(el => el.removeAttribute('title'));

/**
 * Tworzy niestandardowy popup na stronie
 * @param {Object} params - Parametry popupu
 * @param {number} params.imageId - ID obrazu do wyświetlenia
 * @param {string} params.header - Nagłówek popupu
 * @param {string} params.description - Opis/treść popupu
 * @param {string} params.link - Link do umieszczenia w popupie
 * @param {string} params.linkLabel - Etykieta linku
 * @param {string} params.status - Status popupu (success, error, itp.)
 * @param {boolean} params.closeable - Czy popup może być zamknięty
 * @returns {Promise<void>}
 */
async function createCustomPopup(params) {
    try {
        const existingPopup = document.querySelector('.popup-full');
        if (existingPopup) {
            existingPopup.remove();
        }
        const response = await AjaxHelper.sendRequest(window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
            action: 'create_custom_popup',
            nonce: window.dataManagerNonce || '',
            image_id: params.imageId,
            header: params.header,
            description: params.description,
            link: params.link,
            linkLabel: params.linkLabel,
            status: params.status,
            closeable: params.closeable ? 'true' : 'false'
        });

        if (!response.success) {
            throw new Error(response.data?.message || "Nieznany błąd serwera");
        }

        document.body.insertAdjacentHTML('beforeend', response.data.popup);
        setTimeout(() => {
            const newPopup = document.querySelector('.popup-full');
            if (newPopup) {
                newPopup.classList.add('active');
                const closeButton = newPopup.querySelector('.popup-close');
                if (closeButton) {
                    closeButton.addEventListener('click', () => {
                        newPopup.classList.remove('active');

                        setTimeout(() => {
                            newPopup.remove();
                        }, 300);
                    });
                }
            }
        }, 100);
    } catch (error) {
        console.error("❌ Błąd przy tworzeniu popupu:", error);
    }
}

/**
 * Funkcja showPopup - służy do wyświetlania powiadomień
 * Mapuje statusy 'success', 'error' itd. na statusy systemu powiadomień
 * @param {string} message - Treść powiadomienia
 * @param {string} type - Typ powiadomienia (success, error, bad, neutral)
 */
function showPopup(message, type = 'success') {
    // Sprawdź, czy nowy system powiadomień jest dostępny
    if (typeof window.gameNotifications !== 'undefined') {
        // Mapowanie typów powiadomień
        const statusMap = {
            'success': 'success',
            'error': 'failed',
            'bad': 'bad',
            'neutral': 'neutral'
        };

        // Mapuj typ na odpowiedni status lub użyj 'neutral' jako domyślny
        const mappedStatus = statusMap[type] || 'neutral';

        // Wywołaj system powiadomień
        window.gameNotifications.show(message, mappedStatus);
    } else {
        // Jeśli system powiadomień nie jest jeszcze dostępny, użyj tymczasowego powiadomienia
        console.warn('System powiadomień nie został jeszcze załadowany!', message);

        // Stworz tymczasowe powiadomienie
        const existingPopup = document.querySelector('.popup');
        if (existingPopup) existingPopup.remove();

        const popup = document.createElement('div');
        popup.className = `popup popup-${type}`;
        popup.innerHTML = `
            <div class="popup-content">
                <div class="popup-message">${message}</div>
                <button class="popup-close">X</button>
            </div>
        `;

        document.body.appendChild(popup);

        function closePopup() {
            popup.remove();
            document.removeEventListener('keydown', escHandler);
        }

        popup.querySelector('.popup-close').addEventListener('click', closePopup);

        function escHandler(event) {
            if (event.key === 'Escape') {
                closePopup();
            }
        }
        document.addEventListener('keydown', escHandler);

        // Automatycznie zamknij po 5 sekundach
        setTimeout(closePopup, 5000);
    }
}

/**
 * Pobiera dane strony na podstawie parametrów URL i atrybutów danych
 * @returns {Object} Dane strony
 */
function getPageData() {
    // Pobieranie danych strony na podstawie parametrów URL, atrybutów danych, itp.
    const params = new URLSearchParams(window.location.search);

    const pageData = {
        scena: params.get('scena') || '',
        mission: params.get('mission') || '',
        instation: params.get('instation') || ''
    };

    // Dołącz dodatkowe dane, jeśli są dostępne
    const dataElements = document.querySelectorAll('[data-page-info]');
    dataElements.forEach(el => {
        try {
            const info = JSON.parse(el.dataset.pageInfo);
            Object.assign(pageData, info);
        } catch (e) {
            console.error('Error parsing page info:', e);
        }
    });

    return pageData;
}

// Dodawanie funkcji do przestrzeni globalnej dla wstecznej kompatybilności
window.createCustomPopup = createCustomPopup;
window.showPopup = showPopup;
window.getPageData = getPageData;
