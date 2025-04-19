/**
 * Skrypt obsługujący funkcjonalność plecaka (bez jQuery)
 */

document.addEventListener('DOMContentLoaded', function () {
    // Pomocnicze funkcje
    function hideAll(selector) {
        document.querySelectorAll(selector).forEach(el => {
            el.style.display = 'none';
        });
    }

    function show(selector) {
        const element = document.querySelector(selector);
        if (element) element.style.display = '';
    }

    function getDataAttribute(element, name) {
        return element.getAttribute('data-' + name);
    }

    function removeClassFromAll(selector, className) {
        document.querySelectorAll(selector).forEach(element => {
            element.classList.remove(className);
        });
    }

    function findClosest(element, selector) {
        while (element && !element.matches(selector)) {
            element = element.parentElement;
        }
        return element;
    }

    // Obsługa nawigacji po kategoriach
    document.querySelectorAll('.category-item').forEach(item => {
        item.addEventListener('click', function (e) {
            // Unikaj kliknięć na podkategorie
            if (e.target.classList.contains('subcategory-item')) {
                return;
            }

            // Pobierz ID kategorii
            const categoryId = getDataAttribute(this, 'category');

            // Ukryj wszystkie sekcje kategorii
            hideAll('.category-section');

            // Pokaż wybraną sekcję
            show(`#category-${categoryId}`);

            // Dodaj klasę aktywną do wybranej kategorii
            removeClassFromAll('.category-item', 'active');
            this.classList.add('active');

            // Jeśli kategoria domyślnie ma otwarte podkategorie, pokaż je wszystkie
            this.querySelectorAll('.subcategory-item').forEach(subItem => {
                const subcategoryId = getDataAttribute(subItem, 'subcategory');
                show(`#subcategory-${subcategoryId}`);
            });
        });
    });

    // Obsługa nawigacji po podkategoriach
    document.querySelectorAll('.subcategory-item').forEach(item => {
        item.addEventListener('click', function (e) {
            e.stopPropagation(); // Powstrzymaj propagację do rodzica (kategorii)

            const subcategoryId = getDataAttribute(this, 'subcategory');
            const categoryItem = findClosest(this, '.category-item');
            const categoryId = getDataAttribute(categoryItem, 'category');

            // Ukryj wszystkie podkategorie w aktualnej kategorii
            hideAll(`#category-${categoryId} .subcategory-section`);

            // Ukryj główne przedmioty (bez podkategorii) w tej kategorii
            hideAll(`#category-${categoryId} .main-items`);

            // Pokaż wybraną sekcję podkategorii
            show(`#subcategory-${subcategoryId}`);

            // Dodaj klasę aktywną do wybranej podkategorii
            removeClassFromAll('.subcategory-item', 'active');
            this.classList.add('active');
        });
    });

    // Pokaż domyślnie kategorię "Wszystkie przedmioty"
    // Ukryj wszystkie sekcje kategorii
    hideAll('.category-section');

    // Pokaż sekcję "Wszystkie przedmioty"
    show('#category-all');

    // Aktywna klasa została już dodana w HTML do pierwszego elementu

    // Funkcja do wysyłania żądania Ajax z wykorzystaniem AjaxHelper
    function sendAjaxRequest(data, successCallback, errorCallback) {
        console.log('[DEBUG] Wysyłanie żądania AJAX:', data);

        // Dodaj nonce do danych
        if (!data.nonce) {
            data.nonce = global.dataManagerNonce;
        }

        AjaxHelper.sendRequest(global.ajaxurl, 'POST', data)
            .then(response => {
                console.log('[DEBUG] Odpowiedź AJAX:', response);
                successCallback(response);
            })
            .catch(error => {
                console.error('[DEBUG] Błąd AJAX:', error);
                errorCallback(error);
            });
    }

    // Obsługa przycisku "Użyj"
    document.querySelectorAll('.item-use').forEach(button => {
        button.addEventListener('click', function () {
            const itemId = getDataAttribute(this, 'item-id');

            // Wysyłamy żądanie Ajax do użycia przedmiotu
            sendAjaxRequest(
                {
                    action: 'handle_item_action',
                    operation: 'use',
                    item_id: itemId,
                    quantity: 1
                },
                function (response) {
                    if (response.success) {
                        alert(response.data.message);

                        // Jeśli przedmiot został zużyty całkowicie, odśwież stronę
                        if (response.data.removed) {
                            location.reload();
                        }
                    } else {
                        alert('Błąd: ' + response.data.message);
                    }
                },
                function (errorMsg) {
                    alert('Wystąpił błąd podczas używania przedmiotu: ' + errorMsg);
                }
            );
        });
    });

    // Obsługa przycisku "Załóż"
    document.querySelectorAll('.item-equip').forEach(button => {
        button.addEventListener('click', function () {
            const itemId = getDataAttribute(this, 'item-id');
            const itemCard = findClosest(this, '.item-card');

            console.log('[DEBUG] Kliknięto przycisk "Załóż" dla przedmiotu ID:', itemId);
            console.log('[DEBUG] Nonce dostępny:', !!global.dataManagerNonce);

            // Wyświetl bardziej szczegółowe informacje przed wysłaniem żądania
            console.log('[DEBUG] URL AJAX:', global.ajaxurl);
            console.log('[DEBUG] Element karty przedmiotu:', itemCard);

            const requestData = {
                action: 'handle_item_action',
                operation: 'equip',
                item_id: itemId,
                quantity: 1,
                nonce: global.dataManagerNonce
            };

            console.log('[DEBUG] Pełne dane żądania dla "Załóż":', JSON.stringify(requestData));

            sendAjaxRequest(
                requestData,
                function (response) {
                    console.log('[DEBUG] Sukces operacji "Załóż":', response);
                    if (response.success) {
                        // Zmiana wyglądu przedmiotu na "założony"
                        itemCard.classList.add('equipped');

                        // Zmiana przycisku "Załóż" na "Zdejmij"
                        const equipButton = itemCard.querySelector('.item-equip');
                        equipButton.classList.remove('item-equip');
                        equipButton.classList.add('item-unequip');
                        equipButton.textContent = 'Zdejmij';

                        // Dodanie informacji, że przedmiot jest założony
                        if (!itemCard.querySelector('.item-equipped')) {
                            const itemInfoDiv = itemCard.querySelector('.item-info');
                            const equippedDiv = document.createElement('div');
                            equippedDiv.className = 'item-equipped';
                            equippedDiv.textContent = 'Założony';
                            itemInfoDiv.appendChild(equippedDiv);
                        }

                        // Przeładuj stronę, aby zaktualizować wszystkie przedmioty tego samego typu
                        location.reload();
                    } else {
                        console.error('[DEBUG] Błąd odpowiedzi:', response);
                        alert('Błąd: ' + response.data.message);
                    }
                },
                function (errorMsg) {
                    console.error('[DEBUG] Szczegółowy błąd "Załóż":', errorMsg);
                    alert('Wystąpił błąd podczas zakładania przedmiotu: ' + errorMsg);
                }
            );
        });
    });

    // Obsługa przycisku "Zdejmij"
    document.querySelectorAll('.item-unequip').forEach(button => {
        button.addEventListener('click', function () {
            const itemId = getDataAttribute(this, 'item-id');
            const itemCard = findClosest(this, '.item-card');

            sendAjaxRequest(
                {
                    action: 'handle_item_action',
                    operation: 'unequip',
                    item_id: itemId,
                    quantity: 1,
                    nonce: global.dataManagerNonce
                },
                function (response) {
                    if (response.success) {
                        // Zmiana wyglądu przedmiotu na "niezałożony"
                        itemCard.classList.remove('equipped');

                        // Zmiana przycisku "Zdejmij" na "Załóż"
                        const unequipButton = itemCard.querySelector('.item-unequip');
                        unequipButton.classList.remove('item-unequip');
                        unequipButton.classList.add('item-equip');
                        unequipButton.textContent = 'Załóż';

                        // Usunięcie informacji, że przedmiot jest założony
                        const equippedInfo = itemCard.querySelector('.item-equipped');
                        if (equippedInfo) equippedInfo.remove();
                    } else {
                        alert('Błąd: ' + response.data.message);
                    }
                },
                function (errorMsg) {
                    alert('Wystąpił błąd podczas zdejmowania przedmiotu: ' + errorMsg);
                }
            );
        });
    });

    // Wyświetlanie szczegółów przedmiotu po kliknięciu
    document.querySelectorAll('.item-card').forEach(card => {
        card.addEventListener('click', function (e) {
            // Nie pokazuj szczegółów, jeśli kliknięto przycisk
            if (e.target.tagName === 'BUTTON') {
                return;
            }

            const itemId = getDataAttribute(this, 'item-id');

            // Pobierz szczegóły przedmiotu przez Ajax
            sendAjaxRequest(
                {
                    action: 'get_item_details',
                    item_id: itemId
                },
                function (response) {
                    if (response.success) {
                        // Wyświetl modal z detalami przedmiotu
                        const item = response.data.item;

                        // Stwórz zawartość modalu
                        let modalContent = `
                            <div class="item-details-modal">
                                <h2>${item.title}</h2>
                                <div class="item-image">${item.image}</div>
                                <div class="item-description">${item.description}</div>
                                <div class="item-stats">
                                    <p>Typ: ${item.type}</p>
                        `;

                        // Dodaj statystyki przedmiotu, jeśli istnieją
                        if (item.stats) {
                            for (const [stat, value] of Object.entries(item.stats)) {
                                modalContent += `<p>${stat}: ${value}</p>`;
                            }
                        }

                        modalContent += `
                                </div>
                                <button class="close-modal">Zamknij</button>
                            </div>
                        `;

                        // Dodaj modal do body
                        const modalDiv = document.createElement('div');
                        modalDiv.innerHTML = modalContent;
                        document.body.appendChild(modalDiv.firstElementChild);

                        // Obsługa przycisku zamykania
                        document.querySelector('.close-modal').addEventListener('click', function () {
                            document.querySelector('.item-details-modal').remove();
                        });
                    } else {
                        alert('Błąd: ' + response.data.message);
                    }
                },
                function (errorMsg) {
                    alert('Wystąpił błąd podczas pobierania szczegółów przedmiotu: ' + errorMsg);
                }
            );
        });
    });
});
