/**
* API Helper - Funkcje pomocnicze do komunikacji z REST API WordPress
* Zapewnia jednolity sposób obsługi zapytań API z autoryzacją i obsługą błędów
*/

/**
 * Tworzy konfigurację Axios z dodanym nonce dla autoryzacji
 * @returns {Object} Konfiguracja dla zapytań Axios
 */
const getAxiosConfig = () => {
    // Sprawdź czy dane userManagerData są dostępne
    if (typeof userManagerData === 'undefined') {
        console.error('Brak danych userManagerData. Autoryzacja może nie działać.');
        return {};
    }

    // Sprawdź czy w cookie jest właściwy token CSRF
    const getCookieValue = (name) => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    };

    const csrfToken = getCookieValue('wordpress_logged_in_') ? userManagerData.nonce : null;

    return {
        headers: {
            'X-WP-Nonce': userManagerData.nonce,
            'Content-Type': 'application/json'
        },
        withCredentials: true // Pozwala wysyłać ciasteczka z żądaniami
    };
};

/**
 * Wykonuje zapytanie POST do REST API z autoryzacją
 * @param {string} endpoint - Ścieżka endpointu REST API (np. '/wp-json/game/v1/upgrade-stat')
 * @param {Object} data - Dane do wysłania w formacie JSON
 * @returns {Promise} Obiekt Promise z odpowiedzią z API
 */
const postToApi = async (endpoint, data = {}) => {
    try {
        // Dodaj nonce do danych
        const postData = {
            ...data,
            _wpnonce: userManagerData?.nonce
        };

        // Wykonaj zapytanie z konfiguracją zawierającą nagłówki autoryzacji
        return await axios.post(endpoint, postData, getAxiosConfig());
    } catch (error) {
        console.error('Błąd API:', error);

        // Obsługa konkretnych kodów błędów
        if (error.response) {
            if (error.response.status === 401) {
                // Błąd autoryzacji
                throw new Error('Brak autoryzacji. Zaloguj się ponownie.');
            } else {
                // Inny błąd z odpowiedzią serwera
                throw new Error(`Błąd serwera: ${error.response.status} - ${error.response.statusText}`);
            }
        } else if (error.request) {
            // Brak odpowiedzi z serwera
            throw new Error('Nie można połączyć się z serwerem.');
        } else {
            // Inny błąd
            throw error;
        }
    }
};

/**
 * Wykonuje zapytanie GET do REST API z autoryzacją
 * @param {string} endpoint - Ścieżka endpointu REST API
 * @param {Object} params - Parametry URL (opcjonalne)
 * @returns {Promise} Obiekt Promise z odpowiedzią z API
 */
const getFromApi = async (endpoint, params = {}) => {
    try {
        // Wykonaj zapytanie GET z nagłówkami autoryzacji
        return await axios.get(endpoint, {
            ...getAxiosConfig(),
            params: params
        });
    } catch (error) {
        console.error('Błąd API:', error);

        if (error.response) {
            if (error.response.status === 401) {
                throw new Error('Brak autoryzacji. Zaloguj się ponownie.');
            } else {
                throw new Error(`Błąd serwera: ${error.response.status} - ${error.response.statusText}`);
            }
        } else if (error.request) {
            throw new Error('Nie można połączyć się z serwerem.');
        } else {
            throw error;
        }
    }
};

// Eksport funkcji dla użycia w innych modułach
// Jeśli używasz modułów ES6, możesz dodać:
// export { getAxiosConfig, postToApi, getFromApi };
