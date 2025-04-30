/**
 * Helpers do obsługi Advanced Custom Fields (ACF)
 * 
 * Ten moduł zawiera funkcje ułatwiające pracę z polami ACF w WP
 */
console.log("🔧 ACF Helpers loaded");
/**
 * Pobiera najnowsze pola ACF za pomocą AJAX
 * @returns {Promise<Object>} Obiekt zawierający pola ACF
 */
async function fetchLatestACFFields() {
    try {
        const response = await AjaxHelper.sendRequest(window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
            action: 'get_acf_fields',
            nonce: window.gameData && window.gameData.dataManagerNonce ? window.gameData.dataManagerNonce : ''
        });
        if (!response.success) {
            throw new Error(response || "Nieznany błąd serwera");
        }
        return response.data.fields;
    } catch (error) {
        console.error("❌ Błąd pobierania bazy ACF:", error);
        return {};
    }
}

/**
 * Aktualizuje pola ACF dla konkretnego wpisu
 * @param {number} postId - ID wpisu do zaktualizowania
 * @param {Object} fields - Obiekt zawierający pola do zaktualizowania
 * @returns {Promise<Object>} Rezultat operacji
 */
async function updatePostACFFields(postId, fields) {
    return AjaxHelper.sendRequest(window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
        action: 'update_acf_post_fields_reusable',
        nonce: window.gameData && window.gameData.dataManagerNonce ? window.gameData.dataManagerNonce : '',
        post_id: postId,
        fields: JSON.stringify(fields),
        request_id: Date.now() + Math.random().toString(36).substring(2, 9)
    });
}

/**
 * Aktualizuje pola ACF dla bieżącego kontekstu
 * @param {Object} fields - Obiekt zawierający pola do zaktualizowania
 * @returns {Promise<Object>} Rezultat operacji
 */
async function updateACFFields(fields) {
    try {
        const response = await AjaxHelper.sendRequest(window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
            action: 'update_acf_fields',
            nonce: window.gameData && window.gameData.dataManagerNonce ? window.gameData.dataManagerNonce : '',
            fields: JSON.stringify(fields),
            request_id: Date.now() + Math.random().toString(36).substring(2, 9)
        });
        if (!response.success) {
            throw new Error(response.data?.message || "Nieznany błąd serwera");
        }
        return response;
    } catch (error) {
        console.error("❌ Błąd aktualizacji pól ACF:", error);
        throw error;
    }
}

/**
 * Aktualizuje pola ACF z interfejsem graficznym (paskiem ładowania)
 * @param {Object} fields - Obiekt zawierający pola do zaktualizowania
 * @param {Array<string>} parentSelectors - Selektory kontenerów nadrzędnych
 * @param {string|null} customMsg - Niestandardowy komunikat podczas aktualizacji
 * @returns {Promise<Object>} Rezultat operacji
 */
async function updateACFFieldsWithGui(fields, parentSelectors = ['body'], customMsg = null) {
    try {
        // Pokaż wskaźnik ładowania w każdym selektorze nadrzędnym
        document.querySelectorAll('.bar-game').forEach(wrapper => {
            wrapper.innerHTML = '';
            const loadingBar = document.createElement('div');
            loadingBar.classList.add('bar');
            loadingBar.style.width = '100%';
            loadingBar.style.background = '#00aaff';
            loadingBar.style.animation = 'pulse 1.5s infinite';

            const loadingText = document.createElement('div');
            loadingText.classList.add('bar-value');
            loadingText.textContent = customMsg || 'Zapisywanie danych...';

            wrapper.appendChild(loadingBar);
            wrapper.appendChild(loadingText);
        });

        // Wywołaj aktualizację
        const response = await updateACFFields(fields);

        // Po pomyślnej aktualizacji odśwież paski statusu
        document.querySelectorAll('.bar-game').forEach(wrapper => {
            const datasetExists = wrapper.dataset && wrapper.dataset.barMax && wrapper.dataset.barCurrent;

            if (datasetExists) {
                wrapper.innerHTML = '';
                const max = parseFloat(wrapper.dataset.barMax);
                const current = parseFloat(wrapper.dataset.barCurrent);
                const color = wrapper.dataset.barColor || '#4caf50';
                const type = wrapper.dataset.barType || 'default';
                const percentage = (current / max) * 100;

                const bar = document.createElement('div');
                bar.classList.add('bar');
                bar.style.width = percentage + '%';
                bar.style.background = color;

                const barValue = document.createElement('div');
                barValue.classList.add('bar-value');
                barValue.innerHTML = `<span class="ud-stats-${type}">${current}</span> / ${max}`;

                wrapper.appendChild(bar);
                wrapper.appendChild(barValue);
            }
        });

        return response;
    } catch (error) {
        const errorMsg = error && error.message ? error.message : String(error);
        console.error("❌ Błąd aktualizacji bazy danych:", errorMsg);
        throw error;
    }
}

/**
 * Spłaszcza zagnieżdżony obiekt do postaci jednopoziomowej
 * @param {Object} data - Obiekt do spłaszczenia
 * @param {string} prefix - Prefiks do dodania do kluczy
 * @returns {Object} Spłaszczony obiekt
 */
function flattenData(data, prefix = '') {
    let flat = {};
    for (let key in data) {
        if (data.hasOwnProperty(key)) {
            if (data[key] !== null && typeof data[key] === 'object' && !Array.isArray(data[key])) {
                // Rekurencyjne spłaszczanie zagnieżdżonego obiektu
                Object.assign(flat, flattenData(data[key], prefix + key + '_'));
            } else {
                // Dodawanie prostych właściwości
                flat[prefix + key] = data[key];
            }
        }
    }
    return flat;
}

/**
 * Tworzy nowy wpis niestandardowy w WordPress
 * @param {string} title - Tytuł nowego wpisu
 * @param {string} postType - Typ wpisu (np. 'post', 'page', lub niestandardowy)
 * @param {Object} acfFields - Pola ACF do zapisania
 * @returns {Promise<Object>} Rezultat operacji
 */
async function createCustomPost(title, postType, acfFields) {
    try {
        const response = await AjaxHelper.sendRequest(window.ajaxurl || '/wp-admin/admin-ajax.php', 'POST', {
            action: 'create_custom_post',
            nonce: window.gameData && window.gameData.dataManagerNonce ? window.gameData.dataManagerNonce : '',
            title: title,
            post_type: postType,
            acf_fields: JSON.stringify(acfFields),
            request_id: Date.now() + Math.random().toString(36).substring(2, 9)
        });
        if (!response.success) {
            throw new Error(response.data?.message || "Nieznany błąd serwera");
        }
        return response;
    } catch (error) {
        console.error("❌ Błąd przy tworzeniu wpisu:", error);
        throw error;
    }
}

// Eksport funkcji dla wstecznej kompatybilności
window.fetchLatestACFFields = fetchLatestACFFields;
window.updatePostACFFields = updatePostACFFields;
window.updateACFFields = updateACFFields;
window.updateACFFieldsWithGui = updateACFFieldsWithGui;
window.flattenData = flattenData;
window.createCustomPost = createCustomPost;
