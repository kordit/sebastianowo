/**
 * Moduł obsługi lootboxów
 */

/**
 * Buduje popup z wynikami przeszukania lootboxa
 * @param {Object} data - Dane przeszukania lootboxa
 */
function buildLootboxPopup(data) {
    // Stwórz kontener popupu
    const popupOverlay = document.createElement('div');
    popupOverlay.className = 'popup-overlay';
    popupOverlay.id = 'lootbox-popup';

    const popupContent = document.createElement('div');
    popupContent.className = 'popup-content';

    // Dodaj nagłówek
    const header = document.createElement('div');
    header.className = 'popup-header';

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

    // Informacja o kosztach i energii
    const energyInfo = document.createElement('div');
    energyInfo.className = 'energy-info';
    energyInfo.innerHTML = `<p>Koszt przeszukania: <strong>${data.energy_cost}</strong> energii</p>
                           <p>Twoja energia: <strong>${data.user_energy}</strong></p>`;
    popupBody.appendChild(energyInfo);

    // Wyniki przeszukania
    const resultsContainer = document.createElement('div');
    resultsContainer.className = 'lootbox-results';

    // Dodaj animację rozwijania wyników
    const addResultWithDelay = (result, index) => {
        setTimeout(() => {
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

            // Dodaj efekt pojawiania się
            resultItem.style.opacity = '0';
            resultsContainer.appendChild(resultItem);

            // Animacja fade in
            setTimeout(() => {
                resultItem.style.transition = 'opacity 0.5s ease-in-out';
                resultItem.style.opacity = '1';
            }, 50);

        }, index * 1000); // Opóźnienie między kolejnymi wynikami
    };

    // Dodaj wszystkie wyniki z opóźnieniem
    data.results.forEach((result, index) => {
        addResultWithDelay(result, index);
    });

    popupBody.appendChild(resultsContainer);
    popupContent.appendChild(popupBody);

    // Dodaj stopkę popupu
    const footer = document.createElement('div');
    footer.className = 'popup-footer';

    const closeBtn = document.createElement('button');
    closeBtn.className = 'popup-button';
    closeBtn.textContent = 'Zamknij';
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
 * Dodaje style CSS dla popupu lootboxa
 */
function addLootboxPopupStyles() {
    // Sprawdź, czy style już istnieją
    if (document.getElementById('lootbox-popup-styles')) {
        return;
    }

    const styles = `
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .popup-content {
            background: #1a1a1a;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            color: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        .popup-header {
            padding: 15px;
            background: #222;
            position: relative;
            border-bottom: 1px solid #333;
        }
        
        .popup-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }
        
        .popup-close:hover {
            color: white;
        }
        
        .popup-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .energy-info {
            margin-bottom: 20px;
            padding: 10px;
            background: #222;
            border-radius: 5px;
        }
        
        .lootbox-results {
            margin-top: 20px;
        }
        
        .lootbox-result-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 10px;
            background: #272727;
            border-radius: 5px;
            border-left: 3px solid #4CAF50;
            transition: all 0.3s ease;
        }
        
        .lootbox-result-item:hover {
            background: #333;
        }
        
        .result-icon {
            margin-right: 15px;
        }
        
        .reward-icon {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }
        
        .popup-footer {
            padding: 15px;
            background: #222;
            border-top: 1px solid #333;
            text-align: right;
        }
        
        .popup-button {
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .popup-button:hover {
            background: #45a049;
        }
    `;

    const styleElement = document.createElement('style');
    styleElement.id = 'lootbox-popup-styles';
    styleElement.textContent = styles;
    document.head.appendChild(styleElement);
}

// Eksportuj funkcje dla globalnego dostępu
window.buildLootboxPopup = buildLootboxPopup;
