/**
 * Entry point dla modułu Inventory
 */
import { userAPI } from '../../core/api-client';

// Stan ekwipunku użytkownika
let userInventory = [];

/**
 * Inicjalizacja modułu ekwipunku
 * @param {string} inventoryContainerId - ID elementu HTML dla kontenera ekwipunku
 */
export function initInventory(inventoryContainerId = 'inventory-container') {
    // Znajdź element kontenera ekwipunku w DOM
    const inventoryContainer = document.getElementById(inventoryContainerId);

    // Jeśli element nie istnieje, nie inicjalizuj modułu
    if (!inventoryContainer) return;

    // Pobierz ekwipunek użytkownika
    loadUserInventory()
        .then(inventory => {
            renderInventory(inventory, inventoryContainer);
        })
        .catch(error => {
            console.error('Błąd podczas ładowania ekwipunku:', error);
        });
}

/**
 * Ładuje ekwipunek użytkownika z serwera
 * @param {number} userId - ID użytkownika (opcjonalnie)
 * @returns {Promise} - Promise z danymi ekwipunku
 */
export async function loadUserInventory(userId = null) {
    try {
        // Użycie API z Axios
        userInventory = await userAPI.getUserInventory(userId);
        return userInventory;
    } catch (error) {
        console.error('Błąd podczas ładowania ekwipunku użytkownika:', error);
        throw error;
    }
}

/**
 * Renderuje ekwipunek w elemencie DOM
 * @param {Array} inventory - Ekwipunek użytkownika
 * @param {HTMLElement} container - Element DOM, w którym wyświetlić ekwipunek
 */
function renderInventory(inventory, container) {
    if (!container) return;

    // Jeśli nie ma przedmiotów, wyświetl komunikat
    if (!inventory || inventory.length === 0) {
        container.innerHTML = '<div class="empty-inventory">Twój plecak jest pusty</div>';
        return;
    }

    // Generuj HTML dla kategorii przedmiotów
    const categories = groupItemsByCategory(inventory);
    let inventoryHTML = '';

    // Dla każdej kategorii generuj sekcję
    for (const [category, items] of Object.entries(categories)) {
        inventoryHTML += `
      <div class="inventory-category">
        <h3 class="category-title">${formatCategoryName(category)}</h3>
        <div class="category-items">
          ${items.map(item => `
            <div class="inventory-item" data-item-id="${item.item_id}">
              <div class="item-image">
                ${item.image ? `<img src="${item.image}" alt="${item.name}">` : ''}
              </div>
              <div class="item-details">
                <h4 class="item-name">${item.name || 'Przedmiot #' + item.item_id}</h4>
                <div class="item-quantity">${item.quantity > 1 ? 'x' + item.quantity : ''}</div>
                <div class="item-description">${item.description || ''}</div>
              </div>
              <div class="item-actions">
                <button class="btn-use-item" data-item-id="${item.item_id}">Użyj</button>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
    }

    // Wstaw HTML do kontenera
    container.innerHTML = inventoryHTML;

    // Dodaj obsługę zdarzeń dla przycisków
    addInventoryEventListeners(container);
}

/**
 * Grupuje przedmioty według kategorii
 * @param {Array} inventory - Przedmioty w ekwipunku
 * @returns {Object} - Pogrupowane przedmioty
 */
function groupItemsByCategory(inventory) {
    const categories = {};

    inventory.forEach(item => {
        const category = item.category || 'other';
        if (!categories[category]) {
            categories[category] = [];
        }
        categories[category].push(item);
    });

    return categories;
}

/**
 * Formatuje nazwę kategorii na przyjazną dla użytkownika
 * @param {string} category - Nazwa kategorii
 * @returns {string} - Sformatowana nazwa
 */
function formatCategoryName(category) {
    const categoryNames = {
        'weapon': 'Broń',
        'armor': 'Zbroja',
        'potion': 'Mikstury',
        'food': 'Żywność',
        'quest': 'Przedmioty zadań',
        'other': 'Inne'
    };

    return categoryNames[category] || category;
}

/**
 * Dodaje obsługę zdarzeń do przycisków ekwipunku
 * @param {HTMLElement} container - Kontener ekwipunku
 */
function addInventoryEventListeners(container) {
    // Znajdź wszystkie przyciski "Użyj"
    const useButtons = container.querySelectorAll('.btn-use-item');

    // Dodaj obsługę zdarzeń do każdego przycisku
    useButtons.forEach(button => {
        button.addEventListener('click', event => {
            const itemId = button.getAttribute('data-item-id');
            // Tutaj dodaj logikę używania przedmiotu
            console.log(`Używam przedmiotu o ID: ${itemId}`);

            // Przykład: wyświetl informację o użyciu przedmiotu
            if (typeof window.notifySuccess === 'function') {
                window.notifySuccess(`Używasz przedmiotu z plecaka`);
            }
        });
    });
}

// Eksportuj publiczne API modułu
export default {
    init: initInventory,
    loadInventory: loadUserInventory
};
