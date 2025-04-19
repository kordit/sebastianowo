/**
 * Skrypt obsługujący funkcjonalność plecaka (bez jQuery)
 */
document.addEventListener('DOMContentLoaded', function () {
    // FUNKCJE POMOCNICZE

    // Pokazywanie/ukrywanie elementów
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

    // Obsługa atrybutów i klas
    function getDataAttribute(element, name) {
        return element.getAttribute('data-' + name);
    }

    function removeClassFromAll(selector, className) {
        document.querySelectorAll(selector).forEach(element => {
            element.classList.remove(className);
        });
    }

    /**
     * Zapewnia, że ID przedmiotu jest zawsze prawidłowym stringiem
     */
    function safeItemId(itemId) {
        if (itemId === null || itemId === undefined) return '';

        if (typeof itemId === 'object' && itemId !== null) {
            if (itemId.id) return String(itemId.id);
            if (itemId.ID) return String(itemId.ID);
            if (itemId.item_id) return String(itemId.item_id);
            if (itemId.itemId) return String(itemId.itemId);
            return String(itemId);
        }

        return String(itemId);
    }

    /**
     * Sprawdza czy przedmiot jest aktualnie założony
     */
    function isItemEquipped(itemId) {
        const safeId = safeItemId(itemId);
        const equippedItem = document.querySelector(`#tab-equipped .item-card[data-item-id="${safeId}"]`);
        console.log('isItemEquipped - sprawdzam ID:', safeId, 'wynik:', !!equippedItem);
        return !!equippedItem;
    }

    /**
     * Loguje informacje o stanie przycisków i przedmiotów
     */
    function logButtonsState(message = 'Stan przycisków') {
        console.log(`${message}:`, {
            'Załóż przyciski': document.querySelectorAll('.item-equip').length,
            'Zdejmij przyciski': document.querySelectorAll('.item-unequip').length,
            'ajaxInProgress': ajaxInProgress
        });

        // Logowanie założonych przedmiotów
        const equippedItems = document.querySelectorAll('#tab-equipped .item-card');
        if (equippedItems.length > 0) {
            console.log('Założone przedmioty:');
            equippedItems.forEach(item => {
                const unequipBtn = item.querySelector('.item-unequip');
                const slot = unequipBtn ? getDataAttribute(unequipBtn, 'slot') : 'brak-slotu';
                console.log(`- ID: ${getDataAttribute(item, 'item-id')}, Slot: ${slot}`);
            });
        }
    }

    /**
     * Sprawdza czy kategorie/podkategorie są puste i dodaje komunikat
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

            if (!hasItems && itemsGrid) {
                itemsGrid.innerHTML = '<p class="no-items">Twój ekwipunek jest pusty.</p>';
            }
        }
    }

    /**
     * Dodaje przedmiot do plecaka
     */
    function addItemToInventory(itemId, slot) {
        const safeId = safeItemId(itemId);
        if (!safeId) return;

        // Sprawdź, czy przedmiot już istnieje w plecaku
        let existingItemCards = document.querySelectorAll(`#tab-inventory .item-card[data-item-id="${safeId}"]`);

        if (existingItemCards.length > 0) {
            // Jeśli jest więcej niż jedna karta, zostaw tylko pierwszą i usuń resztę
            for (let i = 1; i < existingItemCards.length; i++) {
                existingItemCards[i].remove();
            }
            const card = existingItemCards[0];
            const quantityEl = card.querySelector('.item-quantity');
            if (quantityEl) {
                const matches = quantityEl.textContent.match(/Ilość: (\d+)/);
                const quantity = matches && matches[1] ? parseInt(matches[1]) + 1 : 1;
                quantityEl.textContent = `Ilość: ${quantity}`;
            }
            card.style.display = '';
            // Zawsze aktualizuj przycisk "Załóż", aby uniknąć duplikatów
            const eqOpt = card.querySelector('.equipment-options');
            if (eqOpt) {
                eqOpt.innerHTML = `<button class="item-equip" data-item-id="${safeId}" data-slot="${slot}">Załóż</button>`;
            }
            return;
        }

        // Jeśli przedmiotu nie ma w plecaku, pobierz jego dane z ekwipunku (slot)
        // lub utwórz nową kartę na podstawie slotu
        let itemName = 'Przedmiot';
        let itemImage = null;
        const equipSlot = document.querySelector(`#tab-equipped .equipment-slot[data-slot="${slot}"]`);
        if (equipSlot) {
            const itemCard = equipSlot.querySelector('.item-card');
            if (itemCard) {
                itemName = itemCard.querySelector('.item-name')?.textContent || 'Przedmiot';
                itemImage = itemCard.querySelector('.item-image img')?.cloneNode(true);
            }
        }
        // Jeśli nie znaleziono obrazka, spróbuj znaleźć w innych kartach (np. w DOM)
        if (!itemImage) {
            const anyCard = document.querySelector(`.item-card[data-item-id="${safeId}"] .item-image img`);
            if (anyCard) itemImage = anyCard.cloneNode(true);
        }
        if (!itemImage) return;

        // Określ kategorie przedmiotu na podstawie slotu
        let categoryId, subcategoryId;
        if (slot === 'chest_item') {
            categoryId = '2';
            subcategoryId = '3';
        } else if (slot === 'bottom_item') {
            categoryId = '2';
            subcategoryId = '4';
        } else if (slot === 'legs_item') {
            categoryId = '2';
            subcategoryId = '7';
        }

        // Stwórz nową kartę przedmiotu
        const newItemCard = createItemCard(safeId, itemName, itemImage, slot);

        // Dodaj kartę do "Wszystkie przedmioty"
        const allItemsGrid = document.querySelector('#category-all .items-grid');
        if (allItemsGrid) {
            allItemsGrid.appendChild(newItemCard.cloneNode(true));
        }

        // Dodaj do odpowiedniej kategorii i podkategorii
        if (categoryId && subcategoryId) {
            const subcategorySection = document.querySelector(`#subcategory-${subcategoryId} .items-grid`);
            if (subcategorySection) {
                subcategorySection.appendChild(newItemCard.cloneNode(true));
                const noItemsMsg = document.querySelector(`#subcategory-${subcategoryId} .no-items`);
                if (noItemsMsg) noItemsMsg.remove();
            }
        }
    }

    /**
     * Tworzy element karty przedmiotu
     */
    function createItemCard(itemId, itemName, itemImage, slot) {
        const newItemCard = document.createElement('div');
        newItemCard.className = 'item-card';
        newItemCard.setAttribute('data-item-id', itemId);

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

        return newItemCard;
    }

    // GŁÓWNE FUNKCJE

    /**
     * Zakłada przedmiot
     */
    function equipItem(itemId, slot) {
        console.log('Wywołano equipItem:', itemId, slot);
        const safeId = safeItemId(itemId);

        // Znajdź wszystkie karty przedmiotu
        const itemCards = document.querySelectorAll(`.item-card[data-item-id="${safeId}"]`);
        console.log(`Znaleziono ${itemCards.length} kart przedmiotu o ID ${safeId}`);

        if (itemCards.length === 0) {
            console.error('Nie znaleziono kart przedmiotu o ID', safeId);
            return;
        }

        // Znajdź slot w ekwipunku
        const equipSlot = document.querySelector(`#tab-equipped .equipment-slot[data-slot="${slot}"]`);
        if (!equipSlot) {
            console.error('Nie znaleziono slotu', slot);
            return;
        }

        // Sprawdź, czy w slocie jest już przedmiot
        const existingItemCard = equipSlot.querySelector('.item-card');
        if (existingItemCard) {
            const oldItemId = getDataAttribute(existingItemCard, 'item-id');
            console.log('W slocie jest już przedmiot:', oldItemId, 'Dodaję go z powrotem do plecaka');
            addItemToInventory(oldItemId, slot);
        }

        // Sklonuj przedmiot i dodaj do slotu
        const originalItem = itemCards[0];
        const clonedItem = originalItem.cloneNode(true);
        console.log('Sklonowano przedmiot:', safeId);

        // Zmień przycisk na "Zdejmij"
        const equipButton = clonedItem.querySelector('.item-equip');
        if (equipButton) {
            console.log('Zmieniam przycisk z "Załóż" na "Zdejmij"');
            equipButton.classList.remove('item-equip');
            equipButton.classList.add('item-unequip');
            equipButton.textContent = 'Zdejmij';
            // Upewnij się, że przycisk ma odpowiednie atrybuty
            equipButton.setAttribute('data-item-id', safeId);
            equipButton.setAttribute('data-slot', slot);
            // Upewnij się, że przycisk nie jest disabled
            equipButton.disabled = false;
            equipButton.classList.remove('processing');
        }

        // Dodaj do slotu
        console.log('Wstawiam przedmiot do slotu', slot);
        equipSlot.innerHTML = '';
        equipSlot.appendChild(clonedItem);

        // Aktualizuj licznik w plecaku lub usuń kartę
        itemCards.forEach(card => {
            const quantityEl = card.querySelector('.item-quantity');
            if (quantityEl) {
                const matches = quantityEl.textContent.match(/Ilość: (\d+)/);
                if (matches && matches[1]) {
                    const quantity = parseInt(matches[1]);
                    console.log('Aktualizuję ilość w plecaku:', quantity);
                    if (quantity > 1) {
                        quantityEl.textContent = `Ilość: ${quantity - 1}`;
                    } else {
                        console.log('Usuwam kartę z plecaka (ilość = 1)');
                        card.remove();
                    }
                }
            } else {
                console.log('Usuwam kartę z plecaka (brak elementu ilości)');
                card.remove();
            }
        });

        console.log('Sprawdzam puste kategorie po założeniu przedmiotu');
        checkEmptyCategories();
    }

    /**
     * Zdejmuje przedmiot
     */
    function unequipItem(itemId, slot) {
        console.log('Wywołano unequipItem:', itemId, slot);
        const safeId = safeItemId(itemId);

        // Znajdź slot w ekwipunku
        const equipSlot = document.querySelector(`#tab-equipped .equipment-slot[data-slot="${slot}"]`);
        if (!equipSlot) {
            console.error('Nie znaleziono slotu', slot);
            return;
        }

        // Pobierz informacje o przedmiocie
        const itemCard = equipSlot.querySelector(`.item-card[data-item-id="${safeId}"]`);
        if (!itemCard) {
            console.error('Nie znaleziono karty przedmiotu w slocie');
            return;
        }

        // Dodaj przedmiot do plecaka
        console.log('Dodaję przedmiot z powrotem do plecaka');
        addItemToInventory(safeId, slot);

        // Utwórz i dodaj domyślny komunikat zamiast czyszczenia slotu
        let slotNames = {
            'chest_item': 'Na klatę',
            'bottom_item': 'Na poślady',
            'legs_item': 'Na giczuły'
        };

        console.log('Czyszczę slot', slot);
        equipSlot.innerHTML = `
            <h4 class="slot-name">${slotNames[slot] || slot}</h4>
            <div class="empty-slot">
                <div class="empty-slot-icon"></div>
                <p>Przejdź do zakładki przedmioty, by założyć przedmiot</p>
            </div>
        `;
        console.log('Slot wyczyszczony');

        // Sprawdź czy nie ma duplikatów przycisków po operacji
        setTimeout(() => {
            const duplicateButtons = document.querySelectorAll(`.item-equip[data-item-id="${safeId}"]`);
            if (duplicateButtons.length > 1) {
                console.warn(`Znaleziono ${duplicateButtons.length} duplikatów przycisków dla przedmiotu ${safeId}`);
            }
            logButtonsState('Stan po całkowitym zdjęciu przedmiotu');
        }, 100);
    }

    // INICJALIZACJA UI

    // Obsługa przełączania zakładek
    document.querySelectorAll('.plecak-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            const tabId = getDataAttribute(this, 'tab');
            removeClassFromAll('.plecak-tab', 'active');
            this.classList.add('active');
            hideAll('.plecak-tab-content');
            show('#tab-' + tabId);
        });
    });

    // Obsługa kategorii
    document.querySelectorAll('.category-item').forEach(item => {
        item.addEventListener('click', function (e) {
            if (e.target.classList.contains('subcategory-item')) return;

            const categoryId = getDataAttribute(this, 'category');
            hideAll('.category-section');
            show(`#category-${categoryId}`);
            removeClassFromAll('.category-item', 'active');
            this.classList.add('active');

            // Pokaż podkategorie
            this.querySelectorAll('.subcategory-item').forEach(subItem => {
                const subcategoryId = getDataAttribute(subItem, 'subcategory');
                show(`#subcategory-${subcategoryId}`);
            });
        });
    });

    // Obsługa podkategorii
    document.querySelectorAll('.subcategory-item').forEach(item => {
        item.addEventListener('click', function (e) {
            e.stopPropagation();

            const subcategoryId = getDataAttribute(this, 'subcategory');
            const categoryItem = this.closest('.category-item');
            const categoryId = getDataAttribute(categoryItem, 'category');

            hideAll(`#category-${categoryId} .subcategory-section`);
            hideAll(`#category-${categoryId} .main-items`);
            show(`#subcategory-${subcategoryId}`);
            removeClassFromAll('.subcategory-item', 'active');
            this.classList.add('active');
        });
    });

    // Ustawienia początkowe
    hideAll('.category-section');
    show('#category-all');
    document.querySelector('.plecak-tab[data-tab="equipped"]').classList.add('active');
    document.querySelector('#tab-equipped').classList.add('active');

    // Flaga do śledzenia czy żądanie AJAX jest w toku
    let ajaxInProgress = false;

    // OBSŁUGA ZDARZEŃ

    // Zakładanie przedmiotu
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('item-equip')) {
            console.log('Kliknięto przycisk: Załóż', e.target);
            logButtonsState('Przed akcją "Załóż"');

            // Jeśli żądanie jest już w toku, ignoruj kliknięcie
            if (ajaxInProgress) {
                console.log('AJAX w toku, ignoruję kliknięcie załóż');
                return;
            }

            const itemId = getDataAttribute(e.target, 'item-id');
            const slot = getDataAttribute(e.target, 'slot');
            console.log('Załóż - itemId:', itemId, 'slot:', slot);

            // Sprawdź czy ten przedmiot nie jest już założony
            if (isItemEquipped(itemId)) {
                console.log('Przedmiot już założony, ignoruję kliknięcie');
                showPopup('Ten przedmiot jest już założony', 'bad');
                return;
            }

            if (!itemId || !slot) {
                console.error('Brak wymaganych atrybutów: item-id lub slot');
                return;
            }

            // Oznacz przycisk jako nieaktywny i zmień jego wygląd
            e.target.disabled = true;
            e.target.classList.add('processing');

            // Ustaw flagę, że żądanie jest w toku
            ajaxInProgress = true;

            // Wysyłamy żądanie Ajax do założenia przedmiotu
            const data = {
                action: 'handle_equipment_action',
                item_id: safeItemId(itemId),
                slot: slot,
                equipment_action: 'equip',
                nonce: global.dataManagerNonce
            };
            console.log('Wysyłam żądanie equip:', data);

            AjaxHelper.sendRequest(global.ajaxurl, 'POST', data)
                .then(response => {
                    console.log('Odpowiedź Ajax (equip):', response);
                    if (response.success) {
                        showPopup(response.data.message, 'success');
                        equipItem(itemId, slot);
                        logButtonsState('Po equipItem');
                    } else {
                        showPopup(response.data.message || 'Wystąpił nieznany błąd', 'error');
                    }
                })
                .catch(error => {
                    console.error('Błąd AJAX:', error);
                    showPopup('Wystąpił błąd podczas zakładania przedmiotu.', 'error');
                })
                .finally(() => {
                    // Reset flagi i stanu przycisku
                    ajaxInProgress = false;

                    // Przywróć stan wszystkich przycisków "Załóż" dla tego przedmiotu
                    document.querySelectorAll(`.item-equip[data-item-id="${itemId}"]`).forEach(btn => {
                        btn.disabled = false;
                        btn.classList.remove('processing');
                    });

                    console.log('Zakończono proces zakładania przedmiotu');
                    logButtonsState('Po zakończeniu akcji "Załóż"');
                });
        }
    });

    // Zdejmowanie przedmiotu
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('item-unequip')) {
            console.log('Kliknięto przycisk: Zdejmij', e.target);
            logButtonsState('Przed akcją "Zdejmij"');

            // Jeśli żądanie jest już w toku, ignoruj kliknięcie
            if (ajaxInProgress) {
                console.log('AJAX w toku, ignoruję kliknięcie zdejmij');
                return;
            }

            const itemId = getDataAttribute(e.target, 'item-id');
            const slot = getDataAttribute(e.target, 'slot');
            console.log('Zdejmowanie przedmiotu - itemId:', itemId, 'slot:', slot);

            if (!itemId || !slot) {
                console.error('Brak wymaganych atrybutów: item-id lub slot');
                return;
            }

            // Oznacz przycisk jako nieaktywny i zmień jego wygląd
            e.target.disabled = true;
            e.target.classList.add('processing');

            // Ustaw flagę, że żądanie jest w toku
            ajaxInProgress = true;

            // Wysyłamy żądanie Ajax do zdjęcia przedmiotu
            const data = {
                action: 'handle_equipment_action',
                item_id: safeItemId(itemId),
                slot: slot,
                equipment_action: 'unequip',
                nonce: global.dataManagerNonce
            };

            console.log('Wysyłam żądanie unequip:', data);

            AjaxHelper.sendRequest(global.ajaxurl, 'POST', data)
                .then(response => {
                    console.log('Odpowiedź Ajax (unequip):', response);
                    if (response.success) {
                        showPopup(response.data.message, 'success');
                        console.log('Wykonuję unequipItem...');
                        unequipItem(itemId, slot);
                        console.log('Po wykonaniu unequipItem');
                        logButtonsState('Po unequipItem');
                    } else {
                        showPopup(response.data.message || 'Wystąpił nieznany błąd', 'error');
                    }
                })
                .catch(error => {
                    console.error('Błąd AJAX:', error);
                    showPopup('Wystąpił błąd podczas zdejmowania przedmiotu.', 'error');
                })
                .finally(() => {
                    // Reset flagi i stanu przycisku
                    ajaxInProgress = false;
                    console.log('Zakończono proces zdejmowania przedmiotu, ajaxInProgress =', ajaxInProgress);
                    logButtonsState('Po zakończeniu akcji "Zdejmij"');
                });
        }
    });
});
