/**
 * Skrypt obsługujący funkcjonalność plecaka (bez jQuery)
 */

document.addEventListener('DOMContentLoaded', function () {
    // Pomocnicze funkcje
    function hideAll(selector) {
        document.querySelectorAll(selector).forEach(el => {
            el.style.display = 'none';
            el.classList.remove('active');
        });
    }

    function show(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.style.display = '';
            element.classList.add('active');
        }
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

    /**
     * Funkcja zapewniająca, że ID przedmiotu jest zawsze prawidłowym stringiem
     * @param {*} itemId - ID przedmiotu (może być obiektem, liczbą lub stringiem)
     * @returns {string} - ID przedmiotu jako string
     */
    function safeItemId(itemId) {
        // Jeśli itemId jest null lub undefined, zwróć pusty string
        if (itemId === null || itemId === undefined) {
            return '';
        }

        // Jeśli itemId jest obiektem, spróbuj wydobyć jego ID 
        // (w przypadku gdy jest to wynik z odpowiedzi serwera)
        if (typeof itemId === 'object' && itemId !== null) {
            // Próbuj wydobyć ID z kilku możliwych właściwości
            if (itemId.id) return String(itemId.id);
            if (itemId.ID) return String(itemId.ID);
            if (itemId.item_id) return String(itemId.item_id);
            if (itemId.itemId) return String(itemId.itemId);

            // W ostateczności, jeśli obiekt ma metodę toString, użyj jej
            return String(itemId);
        }

        // W przeciwnym wypadku po prostu skonwertuj na string
        return String(itemId);
    }

    /**
     * Aktualizacja interfejsu użytkownika po założeniu lub zdjęciu przedmiotu
     * @param {string} itemId - ID przedmiotu
     * @param {string} slot - Slot, w którym przedmiot ma być założony/zdjęty
     * @param {string} action - 'equip' lub 'unequip'
     * @param {object} responseData - Dodatkowe dane z odpowiedzi serwera
     */
    function updateEquipmentUI(itemId, slot, action, responseData) {
        // Używamy safeItemId do zapewnienia, że ID jest stringiem
        const safeId = safeItemId(itemId);

        if (action === 'equip') {
            // 1. Znajdź wszystkie wystąpienia przedmiotu we wszystkich kategoriach i podkategoriach
            const itemCards = document.querySelectorAll(`.item-card[data-item-id="${safeId}"]`);
            if (itemCards.length === 0) return;

            // 2. Znajdź miejsce w zakładce "Założone przedmioty" dla tego slotu
            const equipSlot = document.querySelector(`#tab-equipped .equipment-slot[data-slot="${slot}"]`);
            if (!equipSlot) return;

            // 2.1. Sprawdź, czy w slocie już jest jakiś przedmiot (zamiana)
            const existingItemCard = equipSlot.querySelector('.item-card');
            let oldItemId = null;

            // Jeśli mamy dane o zamienianym przedmiocie z serwera, użyjmy ich
            if (responseData && responseData.old_item_id) {
                oldItemId = safeItemId(responseData.old_item_id);

                // Dodaj stary przedmiot z powrotem do ekwipunku
                addItemToInventory(oldItemId, slot);
            }
            // Jeśli nie mamy danych z serwera, ale jest jakiś przedmiot w slocie
            else if (existingItemCard) {
                oldItemId = safeItemId(getDataAttribute(existingItemCard, 'item-id'));

                // Dodaj stary przedmiot z powrotem do ekwipunku
                addItemToInventory(oldItemId, slot);
            }

            // 3. Skopiuj pierwszy znaleziony element przedmiotu do slotu ekwipunku
            const originalItem = itemCards[0];
            const clonedItem = originalItem.cloneNode(true);

            // 4. Zmień przyciski "Załóż" na "Zdejmij" w sklonowanym elemencie
            const equipButton = clonedItem.querySelector('.item-equip');
            if (equipButton) {
                equipButton.classList.remove('item-equip');
                equipButton.classList.add('item-unequip');
                equipButton.textContent = 'Zdejmij';
            }

            // 5. Wyczyść zawartość slotu i dodaj sklonowany element
            equipSlot.innerHTML = '';
            equipSlot.appendChild(clonedItem);

            // 6. Zaktualizuj licznik na wszystkich wystąpieniach przedmiotu lub usuń je
            itemCards.forEach(itemCard => {
                const itemQuantityElement = itemCard.querySelector('.item-quantity');
                if (itemQuantityElement) {
                    // Pobranie aktualnej ilości z tekstu (format "Ilość: X")
                    const quantityText = itemQuantityElement.textContent;
                    const matches = quantityText.match(/Ilość: (\d+)/);
                    if (matches && matches[1]) {
                        let quantity = parseInt(matches[1]);
                        if (quantity > 1) {
                            // Zmniejsz ilość o 1
                            quantity--;
                            itemQuantityElement.textContent = `Ilość: ${quantity}`;
                        } else {
                            // Jeśli to ostatni przedmiot, usuń kartę
                            itemCard.remove();
                        }
                    }
                } else {
                    // Jeśli nie ma licznika, ukryj element
                    itemCard.remove();
                }
            });

            // Sprawdź, czy nie opróżniliśmy kategorii lub podkategorii
            checkEmptyCategories();
        } else if (action === 'unequip') {
            // 1. Znajdź slot w założonych przedmiotach
            const equipSlot = document.querySelector(`#tab-equipped .equipment-slot[data-slot="${slot}"]`);
            if (!equipSlot) return;

            // 2. Pobierz informacje o zdejmowanym przedmiocie
            const itemCard = equipSlot.querySelector(`.item-card[data-item-id="${safeId}"]`);
            if (!itemCard) return;

            const itemName = itemCard.querySelector('.item-name').textContent;
            const itemImage = itemCard.querySelector('.item-image img').cloneNode(true);

            // 3. Znajdź wszystkie wystąpienia przedmiotu we wszystkich kategoriach
            const existingItemCards = document.querySelectorAll(`#tab-inventory .item-card[data-item-id="${safeId}"]`);

            if (existingItemCards.length > 0) {
                // Jeśli przedmiot istnieje w którymkolwiek miejscu plecaka, zwiększ licznik
                existingItemCards.forEach(card => {
                    const itemQuantityElement = card.querySelector('.item-quantity');
                    if (itemQuantityElement) {
                        const quantityText = itemQuantityElement.textContent;
                        const matches = quantityText.match(/Ilość: (\d+)/);
                        if (matches && matches[1]) {
                            let quantity = parseInt(matches[1]);
                            quantity++;
                            itemQuantityElement.textContent = `Ilość: ${quantity}`;
                        }
                    }
                });
            } else {
                // Jeśli przedmiotu nie ma w plecaku, dodaj go

                // 4. Przygotuj nową kartę przedmiotu
                const newItemCard = document.createElement('div');
                newItemCard.className = 'item-card';
                // Upewnij się, że itemId jest używane jako string
                newItemCard.setAttribute('data-item-id', String(itemId));

                // Określ kategorie przedmiotu na podstawie slotu
                let categoryId, subcategoryId;

                if (slot === 'chest_item') {
                    categoryId = '2'; // Ubrania
                    subcategoryId = '3'; // Na klatę
                } else if (slot === 'bottom_item') {
                    categoryId = '2'; // Ubrania
                    subcategoryId = '4'; // Na poślady
                } else if (slot === 'legs_item') {
                    categoryId = '2'; // Ubrania
                    subcategoryId = '7'; // Na giczuły
                }

                // Struktura karty przedmiotu
                newItemCard.innerHTML = `
                    <div class="item-image">
                        ${itemImage.outerHTML}
                    </div>
                    <div class="item-info">
                        <h4 class="item-name">${itemName}</h4>
                        <div class="item-quantity">Ilość: 1</div>
                    </div>
                    <div class="item-actions">
                        <div class="equipment-options">
                            <button class="item-equip" data-item-id="${itemId}" data-slot="${slot}">Załóż</button>
                        </div>
                    </div>
                `;

                // Dodaj kartę do wszystkich odpowiednich miejsc

                // a) Do sekcji "Wszystkie przedmioty"
                const allItemsGrid = document.querySelector('#category-all .items-grid');
                if (allItemsGrid) {
                    allItemsGrid.appendChild(newItemCard.cloneNode(true));
                }

                // b) Do odpowiedniej kategorii i podkategorii (jeśli to ubranie)
                if (categoryId && subcategoryId) {
                    const subcategorySection = document.querySelector(`#subcategory-${subcategoryId} .items-grid`);
                    if (subcategorySection) {
                        subcategorySection.appendChild(newItemCard.cloneNode(true));
                    }

                    // Usuń komunikat "brak przedmiotów" jeśli istnieje
                    const noItemsMsg = document.querySelector(`#subcategory-${subcategoryId} .no-items`);
                    if (noItemsMsg) {
                        noItemsMsg.remove();
                    }
                }
            }
        }
    }

    /**
     * Sprawdza czy jakieś kategorie lub podkategorie są puste i dodaje komunikat
     */
    function checkEmptyCategories() {
        // Sprawdź wszystkie podkategorie
        document.querySelectorAll('.subcategory-section').forEach(subcategory => {
            const itemsGrid = subcategory.querySelector('.items-grid');
            const hasItems = itemsGrid && itemsGrid.querySelector('.item-card');

            if (!hasItems) {
                // Usuń istniejący komunikat, jeśli istnieje
                const existingMsg = subcategory.querySelector('.no-items');
                if (!existingMsg) {
                    const noItemsMsg = document.createElement('p');
                    noItemsMsg.className = 'no-items';
                    noItemsMsg.textContent = 'Brak przedmiotów w tej podkategorii.';

                    if (itemsGrid) {
                        itemsGrid.innerHTML = '';
                        itemsGrid.appendChild(noItemsMsg);
                    } else {
                        subcategory.appendChild(noItemsMsg);
                    }
                }
            }
        });

        // Sprawdź kategorię "Wszystkie przedmioty"
        const allCategory = document.querySelector('#category-all');
        if (allCategory) {
            const itemsGrid = allCategory.querySelector('.items-grid');
            const hasItems = itemsGrid && itemsGrid.querySelector('.item-card');

            if (!hasItems) {
                const noItemsMsg = document.createElement('p');
                noItemsMsg.className = 'no-items';
                noItemsMsg.textContent = 'Twój ekwipunek jest pusty.';

                if (itemsGrid) {
                    itemsGrid.innerHTML = '';
                    itemsGrid.appendChild(noItemsMsg);
                } else {
                    allCategory.appendChild(noItemsMsg);
                }
            }
        }
    }

    /**
     * Dodaje przedmiot do inwentarza (plecaka) bez odświeżania strony
     * @param {string} itemId - ID przedmiotu do dodania
     * @param {string} slot - Slot, z którego przedmiot pochodzi (do określenia kategorii)
     */
    function addItemToInventory(itemId, slot) {
        // Używamy safeItemId dla bezpieczeństwa
        const safeId = safeItemId(itemId);
        
        // Jeśli nie mamy ID przedmiotu, nic nie robimy
        if (!safeId) return;

        // Sprawdź, czy przedmiot już istnieje w plecaku
        const existingItemCards = document.querySelectorAll(`#tab-inventory .item-card[data-item-id="${safeId}"]`);

        // Jeśli przedmiot już istnieje, zwiększamy licznik
        if (existingItemCards.length > 0) {
            existingItemCards.forEach(card => {
                const itemQuantityElement = card.querySelector('.item-quantity');
                if (itemQuantityElement) {
                    const quantityText = itemQuantityElement.textContent;
                    const matches = quantityText.match(/Ilość: (\d+)/);
                    if (matches && matches[1]) {
                        const quantity = parseInt(matches[1]) + 1;
                        itemQuantityElement.textContent = `Ilość: ${quantity}`;
                    }
                }
                // Upewnij się, że karta jest widoczna
                card.style.display = '';
            });
        } else {
            // Jeśli przedmiotu nie ma w plecaku, musimy pobrać jego dane
            const oldItemInEquipment = document.querySelector(`#tab-equipped .item-card[data-item-id="${safeId}"]`);

            // Jeśli nie mamy danych o przedmiocie, to nie możemy go dodać
            if (!oldItemInEquipment) {
                // Pobierz dane o przedmiocie z equipped_items
                const equipSlot = document.querySelector(`#tab-equipped .equipment-slot[data-slot="${slot}"]`);
                if (!equipSlot) return;

                const itemCard = equipSlot.querySelector('.item-card');
                if (!itemCard) return;

                const itemName = itemCard.querySelector('.item-name')?.textContent || 'Przedmiot';
                const itemImage = itemCard.querySelector('.item-image img')?.cloneNode(true);

                if (!itemImage) return;

                // Stwórz nową kartę przedmiotu
                const newItemCard = document.createElement('div');
                newItemCard.className = 'item-card';
                // Zawsze używaj safeItemId aby uniknąć problemów z [object Object]
                newItemCard.setAttribute('data-item-id', safeId);

                // Określ kategorie przedmiotu na podstawie slotu
                let categoryId, subcategoryId;

                if (slot === 'chest_item') {
                    categoryId = '2'; // Ubrania
                    subcategoryId = '3'; // Na klatę
                } else if (slot === 'bottom_item') {
                    categoryId = '2'; // Ubrania
                    subcategoryId = '4'; // Na poślady
                } else if (slot === 'legs_item') {
                    categoryId = '2'; // Ubrania
                    subcategoryId = '7'; // Na giczuły
                }

                // Struktura karty przedmiotu - użyj safeId zamiast itemId dla bezpieczeństwa
                newItemCard.innerHTML = `
                    <div class="item-image">
                        ${itemImage.outerHTML}
                    </div>
                    <div class="item-info">
                        <h4 class="item-name">${itemName}</h4>
                        <div class="item-quantity">Ilość: 1</div>
                    </div>
                    <div class="item-actions">
                        <div class="equipment-options">
                            <button class="item-equip" data-item-id="${safeId}" data-slot="${slot}">Załóż</button>
                        </div>
                    </div>
                `;

                // Dodaj kartę do wszystkich odpowiednich miejsc

                // a) Do sekcji "Wszystkie przedmioty"
                const allItemsGrid = document.querySelector('#category-all .items-grid');
                if (allItemsGrid) {
                    allItemsGrid.appendChild(newItemCard.cloneNode(true));
                }

                // b) Do odpowiedniej kategorii i podkategorii (jeśli to ubranie)
                if (categoryId && subcategoryId) {
                    const subcategorySection = document.querySelector(`#subcategory-${subcategoryId} .items-grid`);
                    if (subcategorySection) {
                        subcategorySection.appendChild(newItemCard.cloneNode(true));
                    }

                    // Usuń komunikat "brak przedmiotów" jeśli istnieje
                    const noItemsMsg = document.querySelector(`#subcategory-${subcategoryId} .no-items`);
                    if (noItemsMsg) {
                        noItemsMsg.remove();
                    }
                }
            }
        }
    }

    // Obsługa przełączania zakładek (taby)
    document.querySelectorAll('.plecak-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            // Pobierz ID zakładki
            const tabId = getDataAttribute(this, 'tab');

            // Usuń klasę aktywną ze wszystkich zakładek
            removeClassFromAll('.plecak-tab', 'active');

            // Dodaj klasę aktywną do klikniętej zakładki
            this.classList.add('active');

            // Ukryj zawartość wszystkich zakładek
            hideAll('.plecak-tab-content');

            // Pokaż zawartość wybranej zakładki
            show('#tab-' + tabId);
        });
    });

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

    // Pokaż domyślnie kategorię "Wszystkie przedmioty" gdy zakładka "Plecak" jest aktywna
    hideAll('.category-section');
    show('#category-all');

    // Domyślne pokazywanie zakładki "Założone przedmioty" (tab "equipped")
    document.querySelector('.plecak-tab[data-tab="equipped"]').classList.add('active');
    document.querySelector('.plecak-tab[data-tab="inventory"]').classList.remove('active');

    document.querySelector('#tab-equipped').classList.add('active');
    document.querySelector('#tab-inventory').classList.remove('active');

    // Bezpośrednie użycie AjaxHelper do wysyłania zapytań
    // Usuwamy funkcję sendAjaxRequest i używamy bezpośrednio AjaxHelper.sendRequest

    // Obsługa przycisków zakładania przedmiotów
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('item-equip')) {
            const itemId = getDataAttribute(e.target, 'item-id');
            const slot = getDataAttribute(e.target, 'slot');

            if (!itemId || !slot) {
                console.error('Brak wymaganych atrybutów: item-id lub slot');
                return;
            }

            // Wysyłamy żądanie Ajax do założenia przedmiotu
            const data = {
                action: 'handle_equipment_action',
                item_id: itemId,
                slot: slot,
                equipment_action: 'equip',
                nonce: global.dataManagerNonce
            };

            console.log('[DEBUG] Wysyłanie żądania AJAX:', data);

            AjaxHelper.sendRequest(global.ajaxurl, 'POST', data)
                .then(response => {
                    console.log('[DEBUG] Odpowiedź AJAX:', response);
                    if (response.success) {
                        // Pokaż popup zamiast odświeżać stronę
                        showPopup(response.data.message, 'success');
                        // Aktualizuj interfejs użytkownika przekazując dane z odpowiedzi
                        updateEquipmentUI(itemId, slot, 'equip', response.data);
                    } else {
                        showPopup(response.data.message || 'Wystąpił nieznany błąd', 'error');
                    }
                })
                .catch(error => {
                    console.error('[DEBUG] Błąd AJAX:', error);
                    showPopup(error, 'error');
                });
        }
    });

    // Obsługa przycisków zdejmowania przedmiotów
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('item-unequip')) {
            const itemId = getDataAttribute(e.target, 'item-id');
            const slot = getDataAttribute(e.target, 'slot');

            if (!itemId || !slot) {
                console.error('Brak wymaganych atrybutów: item-id lub slot');
                return;
            }

            // Wysyłamy żądanie Ajax do zdjęcia przedmiotu
            const data = {
                action: 'handle_equipment_action',
                item_id: itemId,
                slot: slot,
                equipment_action: 'unequip',
                nonce: global.dataManagerNonce
            };

            console.log('[DEBUG] Wysyłanie żądania AJAX:', data);

            AjaxHelper.sendRequest(global.ajaxurl, 'POST', data)
                .then(response => {
                    console.log('[DEBUG] Odpowiedź AJAX:', response);
                    if (response.success) {
                        // Pokaż popup zamiast odświeżać stronę
                        showPopup(response.data.message, 'success');
                        // Aktualizuj interfejs użytkownika
                        updateEquipmentUI(itemId, slot, 'unequip');
                    } else {
                        showPopup(response.data.message || 'Wystąpił nieznany błąd', 'error');
                    }
                })
                .catch(error => {
                    console.error('[DEBUG] Błąd AJAX:', error);
                    showPopup('Wystąpił błąd podczas zdejmowania przedmiotu.', 'error');
                });
        }
    });
});
