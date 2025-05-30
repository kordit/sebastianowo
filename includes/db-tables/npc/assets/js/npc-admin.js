/**
 * NPC Admin - Main Orchestrator
 * Główny plik koordynujący wszystkie komponenty NPC Admin
 */

(function ($) {
    'use strict';

    class NPCAdmin {
        constructor() {
            this.components = {};
            this.init();
        }

        init() {
            // Zainicjalizuj wszystkie komponenty jeśli są dostępne
            this.initializeComponents();
            
            // Bind global events
            this.bindGlobalEvents();

            console.log('NPC Admin initialized with components:', Object.keys(this.components));
        }

        initializeComponents() {
            // Notification Manager - zawsze pierwszy
            if (window.NotificationManager) {
                this.components.notifications = window.notificationManager || new window.NotificationManager();
            }

            // Modal Manager
            if (window.ModalManager) {
                this.components.modals = window.modalManager || new window.ModalManager();
            }

            // Form Validator
            if (window.FormValidator) {
                this.components.formValidator = window.formValidator || new window.FormValidator();
            }

            // Tab Manager
            if (window.TabManager) {
                this.components.tabs = window.tabManager || new window.TabManager();
            }

            // Sortable Manager
            if (window.SortableManager) {
                this.components.sortable = window.sortableManager || new window.SortableManager();
            }

            // Image Uploader
            if (window.ImageUploader) {
                this.components.imageUploader = window.imageUploader || new window.ImageUploader();
            }

            // Auto Save Manager
            if (window.AutoSaveManager) {
                this.components.autoSave = window.autoSaveManager || new window.AutoSaveManager();
            }

            // Table Enhancements
            if (window.TableEnhancements) {
                this.components.table = window.tableEnhancements || new window.TableEnhancements();
            }

            // Answer Actions Manager
            if (window.AnswerActionsManager) {
                this.components.answerActions = window.answerActionsManager || null;
            }
        }

        bindGlobalEvents() {
            // Global keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));

            // Window events
            $(window).on('beforeunload', this.handleBeforeUnload.bind(this));
        }

        handleKeyboardShortcuts(event) {
            // Ctrl+S dla zapisywania
            if (event.ctrlKey && event.key === 's') {
                event.preventDefault();
                this.components.autoSave?.saveManually();
                return false;
            }

            // Escape dla zamykania modali
            if (event.key === 'Escape') {
                this.components.modals?.closeModal();
            }
        }

        handleBeforeUnload() {
            // Cleanup przed opuszczeniem strony
            console.log('NPC Admin cleanup...');
        }

        // Publiczne API dla dostępu do komponentów
        getComponent(name) {
            return this.components[name] || null;
        }

        // Legacy methods dla kompatybilności z starym kodem
        showNotice(message, type) {
            this.components.notifications?.showNotice(message, type);
        }

        // Metoda pomocnicza do debugowania
        debugInfo() {
            console.log('NPC Admin Debug Info:', {
                components: Object.keys(this.components),
                jQuery: !!window.jQuery,
                npcAdmin: !!window.npcAdmin,
                wp: !!window.wp
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(function () {
        // Globalna instancja
        window.NPCAdmin = NPCAdmin;
        window.npcAdminInstance = new NPCAdmin();

        // Dla kompatybilności wstecznej
        window.npcAdmin = window.npcAdmin || {};

        console.log('NPC Admin system ready!');
    });

})(jQuery);
