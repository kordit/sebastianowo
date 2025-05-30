# NPC Admin - Modular JavaScript Architecture

## Opis

System NPC został przeprojektowany z monolitycznego pliku `npc-admin.js` na modularną architekturę składającą się z wyspecjalizowanych komponentów.

## Struktura plików

### Komponenty JavaScript

1. **npc-admin.js** - Główny orchestrator systemu
2. **notification-manager.js** - System powiadomień
3. **modal-manager.js** - Zarządzanie modalami
4. **form-validator.js** - Walidacja formularzy
5. **tab-manager.js** - Zarządzanie zakładkami
6. **sortable-manager.js** - Funkcjonalność przeciągania i sortowania
7. **image-uploader.js** - Obsługa uploadowania obrazów
8. **auto-save-manager.js** - Automatyczne zapisywanie
9. **table-enhancements.js** - Ulepszenia tabel (filtrowanie, sortowanie)
10. **answer-action.js** - Zarządzanie akcjami odpowiedzi

## Kolejność ładowania

Pliki są ładowane w określonej kolejności aby zapewnić poprawne działanie zależności:

1. **jQuery** (WordPress core)
2. **jQuery UI** (sortable, draggable)
3. **notification-manager.js** - Zawsze pierwszy (używany przez inne komponenty)
4. **modal-manager.js** - Zależy od notification-manager
5. **form-validator.js** - Zależy od notification-manager
6. **tab-manager.js** - Niezależny
7. **sortable-manager.js** - Zależy od jQuery UI i notification-manager
8. **image-uploader.js** - Zależy od notification-manager
9. **auto-save-manager.js** - Zależy od notification-manager
10. **table-enhancements.js** - Zależy od notification-manager
11. **answer-action.js** - Niezależny (ma własny manager)
12. **npc-admin.js** - Główny orchestrator (zawsze ostatni)

## Globalne instancje

Każdy komponent tworzy globalną instancję dostępną w `window`:

- `window.notificationManager` - NotificationManager
- `window.modalManager` - ModalManager
- `window.formValidator` - FormValidator
- `window.tabManager` - TabManager
- `window.sortableManager` - SortableManager
- `window.imageUploader` - ImageUploader
- `window.autoSaveManager` - AutoSaveManager
- `window.tableEnhancements` - TableEnhancements
- `window.answerActionsManager` - AnswerActionsManager (tworzona dynamicznie)
- `window.npcAdminInstance` - Główny orchestrator

## API komponentów

### NotificationManager
```javascript
window.notificationManager.showNotice(message, type); // 'success', 'error', 'warning'
window.notificationManager.showOrderUpdateNotice(message);
window.notificationManager.showAutoSaveIndicator();
```

### ModalManager
```javascript
// Obsługuje eventy automatycznie, nie wymaga bezpośredniego API
```

### FormValidator
```javascript
// Obsługuje walidację automatycznie poprzez eventy submit
```

### TabManager
```javascript
// Zarządza zakładkami automatycznie
```

### SortableManager
```javascript
window.sortableManager.reinitializeSortableForTab(location);
```

### ImageUploader
```javascript
window.imageUploader.showImagePreview(imageUrl, targetInput);
window.imageUploader.hideImagePreview(targetInput);
```

### AutoSaveManager
```javascript
window.autoSaveManager.saveManually();
```

### TableEnhancements
```javascript
// Obsługuje filtry i sortowanie automatycznie
```

### NPCAdmin (orchestrator)
```javascript
window.npcAdminInstance.getComponent('notifications'); // Dostęp do komponentów
window.npcAdminInstance.showNotice(message, type); // Metoda pomocnicza
window.npcAdminInstance.debugInfo(); // Informacje debugowania
```

## Kompatybilność wsteczna

System zachowuje kompatybilność z istniejącym kodem poprzez:

1. Utrzymanie globalnych instancji
2. Zachowanie istniejących nazw zmiennych
3. Proxy methods w głównym orchestratorze

## Backup

Oryginalny plik został zachowany jako `npc-admin-old.js` dla celów bezpieczeństwa.

## Testowanie

Po wdrożeniu należy przetestować:

1. ✅ Otwieranie/zamykanie modali
2. ✅ Walidację formularzy
3. ✅ Przełączanie zakładek
4. ✅ Sortowanie dialogów/odpowiedzi
5. ✅ Upload obrazów
6. ✅ Auto-save
7. ✅ Filtry i sortowanie tabel
8. ✅ Zarządzanie akcjami odpowiedzi
9. ✅ Powiadomienia

## Rozwiązywanie problemów

### Sprawdzenie czy komponenty są załadowane:
```javascript
window.npcAdminInstance.debugInfo();
```

### Sprawdzenie czy jQuery jest dostępne:
```javascript
console.log('jQuery:', !!window.jQuery);
```

### Sprawdzenie zależności:
```javascript
console.log({
    notificationManager: !!window.notificationManager,
    modalManager: !!window.modalManager,
    // ... inne komponenty
});
```
