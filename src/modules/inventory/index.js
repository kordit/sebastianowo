/**
 * Entry point dla modułu Inventory
 */
import inventoryManager from './inventory-manager';

// Inicjalizacja modułu po załadowaniu DOM
document.addEventListener('DOMContentLoaded', () => {
    // Inicjalizacja modułu ekwipunku
    inventoryManager.init();
});

// Eksportuj moduł do globalnego obiektu window dla kompatybilności
window.inventoryManager = inventoryManager;
