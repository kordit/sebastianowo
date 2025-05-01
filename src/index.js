/**
 * Główny plik aplikacji Game
 * Importuje wszystkie główne moduły i inicjalizuje aplikację
 */

// Importy zewnętrznych bibliotek
import axios from 'axios';
import Alpine from 'alpinejs';

console.log('Initializing game application');

// Udostępnienie bibliotek globalnie
window.axios = axios;
window.Alpine = Alpine;

// Konfiguracja domyślna Axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Inicjalizacja Alpine.js
Alpine.start();

// Importy głównych modułów
import './core/ajax-helper';
import './core/common';
import './core/notifications';
import './core/ui-helpers';

// API klienta
import { setupApiClient } from './core/api-client';

// Inicjalizacja API klienta
setupApiClient();

// Kod wykonywany po załadowaniu DOM
document.addEventListener('DOMContentLoaded', () => {
    console.log('Game application initialized');

    // Sprawdzanie czy Alpine.js działa
    if (document.querySelector('[x-data]')) {
        console.log('Alpine.js components detected on page');
    }
});
