/**
 * Entry point dla modułu Missions
 */
import missionsManager from './missions-manager';

// Inicjalizacja modułu po załadowaniu DOM
document.addEventListener('DOMContentLoaded', () => {
    // Inicjalizacja modułu misji
    missionsManager.init();
});

// Eksportuj moduł do globalnego obiektu window dla kompatybilności
window.missionsManager = missionsManager;
