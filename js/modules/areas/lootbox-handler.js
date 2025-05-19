/**
 * Moduł obsługi lootboxów - ulepszony
 */

/**
 * Pokazuje popup lootboxa bez automatycznego losowania
 * @param {Number} lootboxId - ID lootboxa
 */
function showLootboxPopup(lootboxId) {
    // Dodaj nonce dla autoryzacji
    const restNonce = userManagerData?.nonce || '';

    // Wykonaj zapytanie o dane lootboxa
    axios({
        method: 'POST',
        url: '/wp-json/game/v1/lootbox/popup',
        headers: {
            'X-WP-Nonce': restNonce,
            'Content-Type': 'application/json'
        },
        data: {
            lootbox_id: lootboxId
        }
    })
        .then(response => {
            const data = response?.data;
            console.log("Dane lootboxa:", data);

            if (data.error) {
                UIHelpers.showNotification(data.error, 'error');
                return;
            }

            if (data.already_searched) {
                UIHelpers.showNotification("Już przeszukałeś ten obiekt.", 'info');
                return;
            }

            // Zbuduj początkowy popup lootboxa z przyciskiem do przeszukania
            buildInitialLootboxPopup(data, lootboxId);
        })
        .catch(error => {
            console.error("Błąd zapytania:", error);
            UIHelpers.showNotification("Wystąpił błąd podczas ładowania danych lootboxa.", 'error');
        });
}

/**
 * Tworzy początkowy popup lootboxa z przyciskiem do przeszukania
 * @param {Object} data - Dane lootboxa
 * @param {Number} lootboxId - ID lootboxa
 */
function buildInitialLootboxPopup(data, lootboxId) {
    // Stwórz kontener popupu
    const popupOverlay = document.createElement('div');
    popupOverlay.className = 'popup-overlay';
    popupOverlay.id = 'lootbox-popup';

    const popupContent = document.createElement('div');
    popupContent.className = 'popup-content';

    // Dodaj nagłówek
    const header = document.createElement('div');
    header.className = 'popup-header';
    const title = document.createElement('h3');
    title.textContent = data.title || 'Lootbox';
    title.style.margin = '0';
    title.style.padding = '5px 0';
    header.appendChild(title);

    const closeButton = document.createElement('span');
    closeButton.className = 'popup-close';
    closeButton.innerHTML = '&times;';
    closeButton.addEventListener('click', () => {
        document.body.removeChild(popupOverlay);
    });

    header.appendChild(closeButton);
    popupContent.appendChild(header);

    // Dodaj treść popupu
    const popupBody = document.createElement('div');
    popupBody.className = 'popup-body';

    // Informacja o lootboxie
    const lootboxInfo = document.createElement('div');
    lootboxInfo.className = 'lootbox-info';

    if (data.type) {
        const typeInfo = document.createElement('p');
        typeInfo.innerHTML = `Typ: <strong>${data.type}</strong>`;
        lootboxInfo.appendChild(typeInfo);
    }

    // Informacja o kosztach i energii
    const energyInfo = document.createElement('div');
    energyInfo.className = 'energy-info';
    energyInfo.innerHTML = `<p>Koszt przeszukania: <strong>${data.price}</strong> energii</p>
                           <p>Twoja energia: <strong>${data.user_energy}</strong> / ${data.max_user_energy}</p>`;
    lootboxInfo.appendChild(energyInfo);

    // Dodaj obszar wyników, początkowo pusty
    const resultsContainer = document.createElement('div');
    resultsContainer.className = 'lootbox-results';
    resultsContainer.id = 'lootbox-results-container';

    popupBody.appendChild(lootboxInfo);
    popupBody.appendChild(resultsContainer);
    popupContent.appendChild(popupBody);

    // Dodaj stopkę popupu
    const footer = document.createElement('div');
    footer.className = 'popup-footer';
    footer.id = 'lootbox-popup-footer';

    // Sprawdź czy użytkownik ma wystarczająco energii
    const price = data.price || 0;
    const userEnergy = data.user_energy || 0;

    if (userEnergy >= price) {
        const searchBtn = document.createElement('button');
        searchBtn.className = 'popup-button search-btn';
        searchBtn.textContent = 'Przeszukaj';
        searchBtn.addEventListener('click', () => {
            // Wykonaj pojedyncze losowanie
            performSingleSearch(lootboxId, popupOverlay);
        });

        footer.appendChild(searchBtn);
    } else {
        // Informacja o niewystarczającej energii
        const noEnergyInfo = document.createElement('div');
        noEnergyInfo.className = 'not-enough-energy-info';
        noEnergyInfo.textContent = 'Nie masz wystarczająco energii, aby przeszukać ten obiekt.';
        noEnergyInfo.style.color = '#ff6666';
        noEnergyInfo.style.marginRight = '10px';
        footer.appendChild(noEnergyInfo);
    }

    const closeBtn = document.createElement('button');
    closeBtn.className = 'popup-button';
    closeBtn.textContent = 'Zamknij';
    closeBtn.style.marginLeft = '10px';
    closeBtn.addEventListener('click', () => {
        document.body.removeChild(popupOverlay);
    });

    footer.appendChild(closeBtn);
    popupContent.appendChild(footer);

    // Dodaj popup do strony
    popupOverlay.appendChild(popupContent);
    document.body.appendChild(popupOverlay);

    // Dodaj style CSS dla popupu
    addLootboxPopupStyles();
}

/**
 * Wykonuje pojedyncze losowanie dla lootboxa i aktualizuje popup
 * @param {Number} lootboxId - ID lootboxa
 * @param {HTMLElement} popupOverlay - Element popupu do aktualizacji
 */
function performSingleSearch(lootboxId, popupOverlay) {
    // Dodaj nonce dla autoryzacji
    const restNonce = userManagerData?.nonce || '';

    // Zablokuj przycisk na czas zapytania
    const searchBtn = popupOverlay.querySelector('.search-btn');
    if (searchBtn) {
        searchBtn.disabled = true;
        searchBtn.textContent = 'Przeszukiwanie...';
    }

    // Wykonaj zapytanie o pojedyncze losowanie
    axios({
        method: 'POST',
        url: '/wp-json/game/v1/lootbox/search',
        headers: {
            'X-WP-Nonce': restNonce,
            'Content-Type': 'application/json'
        },
        data: {
            lootbox_id: lootboxId,
            single_draw: true  // Parametr dla API - tylko jedno losowanie
        }
    })
        .then(response => {
            const data = response?.data;
            console.log("Wyniki pojedynczego przeszukania:", data);

            if (data.error) {
                UIHelpers.showNotification(data.error, 'error');

                // Odblokuj przycisk
                if (searchBtn) {
                    searchBtn.disabled = false;
                    searchBtn.textContent = 'Przeszukaj';
                }

                return;
            }

            if (data.already_searched) {
                UIHelpers.showNotification("Już przeszukałeś ten obiekt.", 'info');

                // Zamknij popup po komunikacie
                setTimeout(() => {
                    if (document.body.contains(popupOverlay)) {
                        document.body.removeChild(popupOverlay);
                    }
                }, 1500);

                return;
            }

            // Aktualizuj informacje o energii w popupie
            updatePopupEnergyInfo(popupOverlay, data);

            // Dodaj wyniki do kontenera
            displaySearchResults(popupOverlay, data.results);

            // Aktualizuj pasek energii w UI
            updateEnergyUI(data);

            // Aktualizuj przyciski w stopce
            updateFooterButtons(popupOverlay, lootboxId, data);
        })
        .catch(error => {
            console.error("Błąd zapytania:", error);
            UIHelpers.showNotification("Wystąpił błąd podczas przeszukiwania.", 'error');

            // Odblokuj przycisk
            if (searchBtn) {
                searchBtn.disabled = false;
                searchBtn.textContent = 'Przeszukaj';
            }
        });
}

/**
 * Aktualizuje informacje o energii w popupie
 * @param {HTMLElement} popupOverlay - Element popupu
 * @param {Object} data - Dane z API
 */
function updatePopupEnergyInfo(popupOverlay, data) {
    const energyInfo = popupOverlay.querySelector('.energy-info');
    if (energyInfo) {
        energyInfo.innerHTML = `<p>Koszt przeszukania: <strong>${data.energy_cost}</strong> energii</p>
                                <p>Twoja energia: <strong>${data.user_energy}</strong> / ${data.max_energy}</p>`;
    }
}

/**
 * Wyświetla wyniki przeszukania w kontenerze
 * @param {HTMLElement} popupOverlay - Element popupu
 * @param {Array} results - Wyniki przeszukania
 */
function displaySearchResults(popupOverlay, results) {
    const resultsContainer = popupOverlay.querySelector('#lootbox-results-container');
    if (!resultsContainer) return;

    // Dla każdego wyniku dodaj element z animacją
    results.forEach((result, index) => {
        const resultItem = document.createElement('div');
        resultItem.className = 'lootbox-result-item';

        // Dodaj ikonę w zależności od typu nagrody
        let iconHtml = '';
        if (result.type === 'gold') {
            iconHtml = '<img src="/wp-content/themes/game/assets/images/png/hajs.png" class="reward-icon">';
        } else if (result.type === 'szlugi') {
            iconHtml = '<img src="/wp-content/themes/game/assets/images/png/szlug.png" class="reward-icon">';
        } else if (result.type === 'item') {
            iconHtml = '<img src="/wp-content/themes/game/assets/images/png/plecak.png" class="reward-icon">';
        }

        resultItem.innerHTML = `
            <div class="result-icon">${iconHtml}</div>
            <div class="result-message">${result.message}</div>
        `;

        // Efekt pojawiania się
        resultItem.style.opacity = '0';
        resultsContainer.appendChild(resultItem);

        // Animacja fade in
        setTimeout(() => {
            resultItem.style.transition = 'opacity 0.5s ease-in-out';
            resultItem.style.opacity = '1';
        }, 50);
    });

    // Przewiń do nowo dodanych wyników
    resultsContainer.scrollTop = resultsContainer.scrollHeight;
}

/**
 * Aktualizuje pasek energii w interfejsie
 * @param {Object} data - Dane z API
 */
function updateEnergyUI(data) {
    if (data.user_energy !== undefined && data.max_energy !== undefined) {
        UIHelpers.updateStatusBar('energy', data.user_energy, data.max_energy);

        // Dodaj efekt wizualny aktualizacji paska energii
        const barWrappers = document.querySelectorAll('.wrap-bar');
        barWrappers.forEach(wrapper => {
            wrapper.classList.add('resource-updated');
            setTimeout(() => {
                wrapper.classList.remove('resource-updated');
            }, 1000);
        });
    }
}

/**
 * Aktualizuje przyciski w stopce popupu
 * @param {HTMLElement} popupOverlay - Element popupu
 * @param {Number} lootboxId - ID lootboxa
 * @param {Object} data - Dane z API
 */
function updateFooterButtons(popupOverlay, lootboxId, data) {
    const footer = popupOverlay.querySelector('#lootbox-popup-footer');
    if (!footer) return;

    // Wyczyść zawartość stopki
    footer.innerHTML = '';

    // Sprawdź, czy użytkownik ma jeszcze energię
    const energyCost = data.energy_cost || 0;
    const userEnergy = data.user_energy || 0;

    // Jeśli lootbox został już przeszukany całkowicie
    if (data.completely_searched) {
        const completeInfo = document.createElement('div');
        completeInfo.className = 'search-complete-info';
        completeInfo.textContent = 'Całkowicie przeszukałeś ten obiekt.';
        completeInfo.style.color = '#4CAF50';
        completeInfo.style.marginRight = '10px';
        footer.appendChild(completeInfo);
    }
    // Jeśli użytkownik ma jeszcze energię i lootbox nie został całkowicie przeszukany
    else if (userEnergy >= energyCost) {
        const searchMoreBtn = document.createElement('button');
        searchMoreBtn.className = 'popup-button search-btn';
        searchMoreBtn.textContent = 'Przeszukaj dalej';
        searchMoreBtn.addEventListener('click', () => {
            // Wykonaj kolejne pojedyncze losowanie
            performSingleSearch(lootboxId, popupOverlay);
        });

        footer.appendChild(searchMoreBtn);
    } else {
        // Informacja o niewystarczającej energii
        const noEnergyInfo = document.createElement('div');
        noEnergyInfo.className = 'not-enough-energy-info';
        noEnergyInfo.textContent = 'Brak wystarczającej energii na kolejne przeszukanie.';
        noEnergyInfo.style.color = '#ff6666';
        noEnergyInfo.style.marginRight = '10px';
        footer.appendChild(noEnergyInfo);
    }

    const closeBtn = document.createElement('button');
    closeBtn.className = 'popup-button';
    closeBtn.textContent = 'Zamknij';
    closeBtn.style.marginLeft = '10px';
    closeBtn.addEventListener('click', () => {
        document.body.removeChild(popupOverlay);
    });

    footer.appendChild(closeBtn);
}

// Eksportuj funkcje dla globalnego dostępu
window.showLootboxPopup = showLootboxPopup;
window.performSingleSearch = performSingleSearch;
