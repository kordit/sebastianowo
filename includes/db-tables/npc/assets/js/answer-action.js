/**
 * Answer Actions JavaScript - Zrefaktoryzowany
 * Obsługuje zarządzanie akcjami w odpowiedziach NPC
 */

(function ($) {
    'use strict';

    // === MODUŁ API ===
    const API = {
        /**
         * Pobiera listę przedmiotów z serwera
         */
        async getItems() {
            try {
                const response = await $.post(npcAdmin.ajax_url, {
                    action: 'npc_get_items',
                    nonce: npcAdmin.nonce
                });

                if (response.success) {
                    const options = {};
                    response.data.forEach(item => {
                        options[item.id] = item.title;
                    });
                    return options;
                }
                throw new Error(response.data || 'Błąd pobierania przedmiotów');
            } catch (error) {
                console.error('API.getItems error:', error);
                return {};
            }
        },

        /**
         * Pobiera listę lokacji z serwera
         */
        async getLocations() {
            try {
                const response = await $.post(npcAdmin.ajax_url, {
                    action: 'npc_get_locations',
                    nonce: npcAdmin.nonce
                });

                if (response.success) {
                    const options = {};
                    response.data.forEach(location => {
                        options[location.id] = location.title;
                    });
                    return options;
                }
                throw new Error(response.data || 'Błąd pobierania lokacji');
            } catch (error) {
                console.error('API.getLocations error:', error);
                return {};
            }
        },

        /**
         * Pobiera listę lokacji ze scenami z serwera
         */
        async getLocationsWithScenes() {
            try {
                const response = await $.post(npcAdmin.ajax_url, {
                    action: 'npc_get_locations_with_scenes',
                    nonce: npcAdmin.nonce
                });

                if (response.success) {
                    return response.data;
                }
                throw new Error(response.data || 'Błąd pobierania lokacji ze scenami');
            } catch (error) {
                console.error('API.getLocationsWithScenes error:', error);
                return [];
            }
        }
    };

    // === MODUŁ POLA FORMULARZA ===
    class FieldInputFactory {
        constructor() {
            this.fieldRenderers = {
                'number': this.createNumberInput.bind(this),
                'text': this.createTextInput.bind(this),
                'select': this.createSelectInput.bind(this),
            };
        }

        /**
         * Tworzy input na podstawie konfiguracji pola
         */
        async createField(fieldName, fieldConfig, value = '') {
            const renderer = this.fieldRenderers[fieldConfig.type] || this.createTextInput.bind(this);
            return await renderer(fieldName, fieldConfig, value);
        }

        /**
         * Tworzy input typu number
         */
        createNumberInput(fieldName, fieldConfig, value) {
            return $(`
                <input type="number" 
                       class="regular-text action-field-input"
                       data-field="${fieldName}"
                       value="${value}"
                       ${fieldConfig.required ? 'required' : ''}
                       />
            `);
        }

        /**
         * Tworzy input typu text
         */
        createTextInput(fieldName, fieldConfig, value) {
            return $(`
                <input type="text" 
                       class="regular-text action-field-input"
                       data-field="${fieldName}"
                       value="${value}"
                       ${fieldConfig.required ? 'required' : ''}
                       />
            `);
        }

        /**
         * Tworzy select input z opcjami
         */
        async createSelectInput(fieldName, fieldConfig, value) {
            const $select = $(`
                <select class="regular-text action-field-input" 
                        data-field="${fieldName}"
                        ${fieldConfig.required ? 'required' : ''}>
                    <option value="">-- Wybierz --</option>
                </select>
            `);

            // Pobierz opcje i wypełnij select
            let options = [];

            if (typeof fieldConfig.options === 'string' && window[fieldConfig.options]) {
                options = await window[fieldConfig.options]();
            } else if (typeof fieldConfig.options === 'object') {
                options = fieldConfig.options;
            }

            this.populateSelectOptions($select, options, value);
            return $select;
        }

        /**
         * Wypełnia opcje w select
         */
        populateSelectOptions($select, options, selectedValue = '') {
            // Usuń wszystkie opcje oprócz pierwszej (placeholder)
            $select.find('option:not(:first)').remove();

            if (Array.isArray(options)) {
                // Array obiektów
                options.forEach(option => {
                    const optionValue = typeof option === 'object' ? option.id : option;
                    const optionLabel = typeof option === 'object' ? option.title : option;
                    const optionScenes = typeof option === 'object' ? option.scenes : null;
                    const selected = selectedValue == optionValue ? 'selected' : '';
                    const scenesAttr = optionScenes ? ` data-scenes='${JSON.stringify(optionScenes)}'` : '';

                    $select.append(`
                        <option value="${optionValue}" ${selected}${scenesAttr}>
                            ${optionLabel}
                        </option>
                    `);
                });
            } else if (typeof options === 'object') {
                // Obiekt key-value
                Object.entries(options).forEach(([optionValue, optionData]) => {
                    if (optionValue === '0') {
                        // Aktualizuj placeholder
                        $select.find('option:first').text(optionData);
                    } else {
                        const optionLabel = typeof optionData === 'object' ? optionData.title : optionData;
                        const optionScenes = typeof optionData === 'object' ? optionData.scenes : null;
                        const selected = selectedValue == optionValue ? 'selected' : '';
                        const scenesAttr = optionScenes ? ` data-scenes='${JSON.stringify(optionScenes)}'` : '';

                        $select.append(`
                            <option value="${optionValue}" ${selected}${scenesAttr}>
                                ${optionLabel}
                            </option>
                        `);
                    }
                });
            }
        }
    }

    // === MODUŁ ZALEŻNOŚCI MIĘDZY POLAMI ===
    class FieldDependencyManager {
        constructor() {
            this.dependencies = new Map();
        }

        /**
         * Rejestruje zależność między polami
         */
        registerDependency($parentField, $childField, fieldConfig) {
            const parentFieldName = $parentField.data('field');
            const childFieldName = $childField.data('field');

            if (!this.dependencies.has(parentFieldName)) {
                this.dependencies.set(parentFieldName, []);
            }

            this.dependencies.get(parentFieldName).push({
                childField: $childField,
                childFieldName,
                config: fieldConfig
            });

            // Binduj event na pole nadrzędne
            $parentField.on('change.dependency', (event) => {
                this.handleParentFieldChange(event);
            });
        }

        /**
         * Obsługuje zmianę w polu nadrzędnym
         */
        async handleParentFieldChange(event) {
            const $parentField = $(event.target);
            const parentFieldName = $parentField.data('field');
            const $selectedOption = $parentField.find('option:selected');

            const dependencies = this.dependencies.get(parentFieldName) || [];

            for (const dependency of dependencies) {
                const { childField: $childField, config } = dependency;

                if (config.depends_on === parentFieldName) {
                    await this.updateChildField($childField, $selectedOption, config);
                }
            }
        }

        /**
         * Aktualizuje pole zależne na podstawie wyboru w polu nadrzędnym
         */
        async updateChildField($childField, $selectedOption, fieldConfig) {
            // Wyczyść opcje pola zależnego
            $childField.find('option:not(:first)').remove();
            $childField.val('');

            const rawScenes = $selectedOption.attr('data-scenes');
            if (!rawScenes) {
                return;
            }

            try {
                const scenes = JSON.parse(rawScenes);
                if (Array.isArray(scenes) && scenes.length > 0) {
                    scenes.forEach(scene => {
                        const sceneTitle = scene.title || `Scena ${scene.id}`;
                        $childField.append(`
                            <option value="${scene.id}">
                                ${sceneTitle}
                            </option>
                        `);
                    });
                }
            } catch (error) {
                console.error('Błąd parsowania danych scen:', error);
            }
        }

        /**
         * Usuwa wszystkie zależności dla danego kontenera
         */
        cleanup($container) {
            $container.find('.action-field-input').off('.dependency');
        }
    }

    // === GŁÓWNA KLASA ZARZĄDZAJĄCA AKCJAMI ===
    class AnswerActionsManager {
        constructor() {
            this.actionTypes = {};
            this.actions = [];
            this.fieldFactory = new FieldInputFactory();
            this.dependencyManager = new FieldDependencyManager();
            this.sortable = null;
            this.isInitialized = false;
            this.eventsBound = false;
            this.isProcessing = false; // Flaga zapobiegająca podwójnemu kliknięciu

            // Nie wywołujemy automatycznie init() - pozwalamy na kontrolę z zewnątrz
            console.log('AnswerActionsManager instance created');
        }

        /**
         * Inicjalizacja managera
         */
        async init() {
            if (this.isInitialized) {
                console.warn('AnswerActionsManager already initialized');
                return;
            }

            try {
                this.loadActionTypes();
                this.bindEvents();
                await this.loadExistingActions();
                this.initSortable();
                this.isInitialized = true;
                console.log('AnswerActionsManager initialized successfully');
            } catch (error) {
                console.error('Error initializing AnswerActionsManager:', error);
            }
        }

        /**
         * Ładuje konfigurację typów akcji
         */
        loadActionTypes() {
            const configScript = document.getElementById('action-types-config');

            if (configScript) {
                try {
                    const config = JSON.parse(configScript.textContent);
                    if (typeof config !== 'object' || !config) {
                        throw new Error('Invalid action types configuration format');
                    }
                    this.actionTypes = config;
                    console.log('Loaded action types:', Object.keys(this.actionTypes));
                } catch (e) {
                    console.error('Error parsing action types config:', e);
                    this.actionTypes = {};
                }
            } else {
                console.warn('No action-types-config script found');
                this.actionTypes = {};
            }
        }

        /**
         * Binduje eventy - tylko raz!
         */
        bindEvents() {
            if (this.eventsBound) {
                console.warn('Events already bound');
                return;
            }

            // Sprawdź czy kontener akcji istnieje - używaj poprawnego selektora
            const $actionsContainer = $('.answer-actions-manager');
            if (!$actionsContainer.length) {
                console.warn('Actions container (.answer-actions-manager) not found for event binding');
                return;
            }

            // Usuń wszystkie poprzednie eventy z kontenera akcji
            $actionsContainer.off('.answerActions');

            // Binduj eventy tylko w kontenerze akcji z namespace
            $actionsContainer.on('click.answerActions', '.add-action-btn', (e) => {
                e.preventDefault();
                e.stopPropagation();

                // Dodatkowe sprawdzenie czy to faktycznie przycisk dodawania akcji
                const $target = $(e.target);
                if (!$target.hasClass('add-action-btn') && !$target.closest('.add-action-btn').length) {
                    console.log('Event caught but not from add-action-btn, ignoring');
                    return;
                }

                console.log('Add action button clicked');
                this.addAction();
            });

            $actionsContainer.on('click.answerActions', '.remove-action-btn', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Remove action button clicked');
                this.removeAction(e);
            });

            $actionsContainer.on('change.answerActions', '.action-field-input', (e) => {
                e.stopPropagation();
                this.updateActionData(e);
            });

            $actionsContainer.on('input.answerActions', '.action-field-input', (e) => {
                e.stopPropagation();
                this.updateActionData(e);
            });

            this.eventsBound = true;
            console.log('Events bound successfully to actions container');
        }

        /**
         * Ładuje istniejące akcje z hidden input
         */
        async loadExistingActions() {
            const dataInput = document.getElementById('answer-actions-data');

            if (dataInput && dataInput.value.trim()) {
                try {
                    const actions = JSON.parse(dataInput.value) || [];
                    await this.loadActions(actions);
                } catch (e) {
                    console.error('Error parsing existing actions data:', e);
                    this.actions = [];
                }
            } else {
                this.actions = [];
            }
        }

        /**
         * Dodaje nową akcję
         */
        async addAction() {
            // Zapobiegaj podwójnemu dodawaniu
            if (this.isProcessing) {
                console.warn('Already processing action, ignoring duplicate click');
                return;
            }

            this.isProcessing = true;
            console.log('Adding new action...');

            try {
                const typeSelect = document.getElementById('new-action-type');
                const selectedType = typeSelect ? typeSelect.value : '';

                if (!selectedType) {
                    console.warn('No action type selected');
                    return;
                }

                // Sprawdź czy select faktycznie ma wartość i nie jest to przypadkowe wywołanie
                if (!typeSelect.options[typeSelect.selectedIndex]) {
                    console.warn('Invalid action type selected');
                    return;
                }

                const actionConfig = this.actionTypes[selectedType];
                if (!actionConfig) {
                    console.error('Brak konfiguracji dla typu akcji:', selectedType);
                    return;
                }

                // Przygotuj nową akcję
                const newAction = {
                    type: selectedType,
                    params: {}
                };

                // Dodaj domyślne wartości dla parametrów
                if (actionConfig.fields) {
                    Object.keys(actionConfig.fields).forEach(fieldName => {
                        const field = actionConfig.fields[fieldName];
                        if ('default' in field) {
                            newAction.params[fieldName] = field.default;
                        }
                    });
                }

                // Dodaj do listy akcji
                this.actions.push(newAction);
                const newIndex = this.actions.length - 1;

                console.log('Action added to array, index:', newIndex, 'Total actions:', this.actions.length);

                // Renderuj w interfejsie
                await this.renderAction(newAction, newIndex);

                // Wyczyść select
                if (typeSelect) {
                    typeSelect.value = '';
                }

                // Aktualizuj hidden input
                this.updateHiddenInput();

                console.log('Action added successfully');
            } catch (error) {
                console.error('Error adding action:', error);
            } finally {
                // Zawsze resetuj flagę po zakończeniu operacji
                setTimeout(() => {
                    this.isProcessing = false;
                }, 200);
            }
        }

        /**
         * Usuwa akcję
         */
        removeAction(event) {
            const $actionItem = $(event.target).closest('.action-item');
            const actionIndex = parseInt($actionItem.data('index'));

            console.log('Removing action at index:', actionIndex, 'Actions length:', this.actions.length);

            if (isNaN(actionIndex) || actionIndex < 0 || actionIndex >= this.actions.length) {
                console.error('Invalid action index:', actionIndex, 'Actions length:', this.actions.length);
                console.log('Available actions:', this.actions);
                return;
            }

            // Wyczyść zależności dla tego kontenera
            this.dependencyManager.cleanup($actionItem);

            // Usuń z danych
            this.actions.splice(actionIndex, 1);

            // Usuń z interfejsu
            $actionItem.remove();

            // Ponownie indeksuj pozostałe akcje
            this.reindexActions();

            // Zaktualizuj hidden input
            this.updateHiddenInput();

            console.log('Action removed, new total:', this.actions.length);
        }

        /**
         * Renderuje akcję w interfejsie
         */
        async renderAction(action, index) {
            const actionConfig = this.actionTypes[action.type];
            if (!actionConfig) {
                console.error('Brak konfiguracji dla akcji:', action.type);
                return;
            }

            const $actionsList = $('.actions-list');

            const $actionItem = $(`
                <div class="action-item" data-index="${index}" data-type="${action.type}">
                    <div class="action-header">
                        <h5>${actionConfig.label}</h5>
                        <button type="button" class="remove-action-btn">&times;</button>
                    </div>
                    <div class="action-body">
                        <p class="action-description">${actionConfig.description}</p>
                        <div class="action-fields"></div>
                    </div>
                </div>
            `);

            const $fieldsContainer = $actionItem.find('.action-fields');

            // Renderuj pola
            const fieldPromises = Object.keys(actionConfig.fields || {}).map(async (fieldName) => {
                const fieldConfig = actionConfig.fields[fieldName];
                if (!fieldConfig) {
                    console.error('Missing field configuration:', fieldName);
                    return;
                }

                const fieldValue = action.params[fieldName] || fieldConfig.default || '';

                const $fieldWrapper = $(`
                    <div class="action-field">
                        <label>${fieldConfig.label}</label>
                    </div>
                `);

                const $fieldInput = await this.fieldFactory.createField(fieldName, fieldConfig, fieldValue);
                if ($fieldInput) {
                    $fieldWrapper.append($fieldInput);
                    $fieldsContainer.append($fieldWrapper);

                    // Zarejestruj zależności jeśli istnieją
                    if (fieldConfig.depends_on) {
                        setTimeout(() => {
                            const $parentField = $actionItem.find(`[data-field="${fieldConfig.depends_on}"]`);
                            if ($parentField.length) {
                                this.dependencyManager.registerDependency($parentField, $fieldInput, fieldConfig);

                                // Wyzwól początkową zmianę jeśli pole nadrzędne ma wartość
                                if ($parentField.val()) {
                                    $parentField.trigger('change.dependency');
                                }
                            }
                        }, 50);
                    }
                }
            });

            // Poczekaj na wyrenderowanie wszystkich pól
            await Promise.all(fieldPromises);

            $actionsList.append($actionItem);
        }

        /**
         * Aktualizuje dane akcji na podstawie zmian w polach
         */
        updateActionData(event) {
            const $input = $(event.target);
            const $actionItem = $input.closest('.action-item');
            const actionIndex = parseInt($actionItem.data('index'));
            const fieldName = $input.data('field');
            const value = $input.val();

            if (isNaN(actionIndex) || actionIndex < 0 || actionIndex >= this.actions.length) {
                console.error('Invalid action index for update:', actionIndex, 'Actions length:', this.actions.length);
                return;
            }

            // Aktualizuj dane akcji
            if (!this.actions[actionIndex].params) {
                this.actions[actionIndex].params = {};
            }
            this.actions[actionIndex].params[fieldName] = value;

            // Dodaj klasę modified do elementu action-item
            $actionItem.addClass('modified');

            // Zaktualizuj hidden input
            this.updateHiddenInput();
        }

        /**
         * Ponownie indeksuje akcje po usunięciu
         */
        reindexActions() {
            $('.actions-list .action-item').each((index, element) => {
                $(element).attr('data-index', index);
            });
        }

        /**
         * Aktualizuje hidden input z danymi akcji
         */
        updateHiddenInput() {
            const $hiddenInput = $('#answer-actions-data');
            $hiddenInput.val(JSON.stringify(this.actions));
        }

        /**
         * Inicjalizuje sortowanie akcji
         */
        initSortable() {
            const $actionsList = $('.actions-list');
            if ($actionsList.length && typeof Sortable !== 'undefined') {
                this.sortable = new Sortable($actionsList[0], {
                    animation: 150,
                    handle: '.action-header',
                    onEnd: (evt) => {
                        const oldIndex = evt.oldIndex;
                        const newIndex = evt.newIndex;

                        if (oldIndex !== newIndex) {
                            // Przenieś w tablicy danych
                            const item = this.actions.splice(oldIndex, 1)[0];
                            this.actions.splice(newIndex, 0, item);

                            // Ponownie indeksuj
                            this.reindexActions();

                            // Aktualizuj hidden input
                            this.updateHiddenInput();
                        }
                    }
                });
            }
        }

        /**
         * Ładuje akcje (używane przy edycji)
         */
        async loadActions(actions) {
            console.log('Loading actions:', actions);
            this.actions = actions || [];
            $('.actions-list').empty();

            // Jeśli nie ma akcji, po prostu zaktualizuj hidden input i zakończ
            if (!this.actions.length) {
                console.log('No actions to load');
                this.updateHiddenInput();
                return;
            }

            // Renderuj akcje
            for (let i = 0; i < this.actions.length; i++) {
                await this.renderAction(this.actions[i], i);
            }

            this.updateHiddenInput();
            console.log('Actions loaded successfully, total:', this.actions.length);
        }

        /**
         * Zbiera dane formularza przed wysłaniem
         */
        collectFormData() {
            // Wyczyść tablicę akcji
            this.actions = [];

            // Przejdź przez wszystkie akcje w formularzu
            $('.action-item').each((index, actionElement) => {
                const $actionItem = $(actionElement);
                const actionType = $actionItem.data('type');
                const actionConfig = this.actionTypes[actionType];

                if (!actionType || !actionConfig) {
                    return;
                }

                // Stwórz nowy obiekt akcji
                const action = {
                    type: actionType,
                    params: {}
                };

                let hasValidData = false;

                // Zbierz wartości wszystkich pól dla tej akcji
                $actionItem.find('.action-field-input').each((_, input) => {
                    const $input = $(input);
                    const fieldName = $input.data('field');
                    const fieldValue = $input.val();
                    const fieldConfig = actionConfig.fields[fieldName];

                    if (!fieldName || !fieldConfig) {
                        return;
                    }

                    // Sprawdź czy pole ma wartość
                    if (fieldValue !== '' && fieldValue !== null && fieldValue !== undefined) {
                        action.params[fieldName] = fieldValue;

                        // Jeśli wartość różni się od domyślnej, uznaj akcję za ważną
                        if (fieldValue !== fieldConfig.default && fieldValue !== '0') {
                            hasValidData = true;
                        }
                    }
                });

                // Dodaj akcję tylko jeśli ma jakieś ważne dane
                if (hasValidData && Object.keys(action.params).length > 0) {
                    this.actions.push(action);
                }
            });

            // Zaktualizuj hidden input z nowymi danymi
            this.updateHiddenInput();
        }

        /**
         * Waliduje akcje przed wysłaniem
         */
        validateActions() {
            const errors = [];

            $('.action-item').each((index, actionElement) => {
                const $actionItem = $(actionElement);
                const actionType = $actionItem.data('type');
                const actionConfig = this.actionTypes[actionType];

                if (!actionConfig) return;

                // Sprawdź wymagane pola
                Object.keys(actionConfig.fields || {}).forEach(fieldName => {
                    const fieldConfig = actionConfig.fields[fieldName];
                    if (fieldConfig.required) {
                        const $input = $actionItem.find(`[data-field="${fieldName}"]`);
                        if (!$input.val()) {
                            errors.push(`Akcja "${actionConfig.label}": Pole "${fieldConfig.label}" jest wymagane`);
                        }
                    }
                });
            });

            return errors;
        }

        /**
         * Publiczna metoda do pobierania aktualnych akcji
         */
        getActions() {
            return this.actions;
        }

        /**
         * Publiczna metoda do resetowania managera
         */
        reset() {
            this.actions = [];
            $('.actions-list').empty();
            this.updateHiddenInput();
        }
    }

    // Globalne funkcje dla kompatybilności wstecznej
    window.get_items = API.getItems;
    window.get_locations = API.getLocations;
    window.get_locations_with_scenes = API.getLocationsWithScenes;
    window.get_items = API.getItems;
    window.get_locations = API.getLocations;
    window.get_locations_with_scenes = API.getLocationsWithScenes;

    // Globalna instancja
    window.AnswerActionsManager = AnswerActionsManager;

    // Inicjalizacja po załadowaniu DOM
    $(document).ready(function () {
        // Sprawdź czy kontener akcji istnieje przed inicjalizacją
        if ($('.answer-actions-manager').length === 0) {
            console.log('Actions manager container not found, skipping initialization');
            return;
        }

        // Sprawdź czy już została utworzona i zainicjalizowana instancja przez npc-admin.js
        if (window.answerActionsManager && window.answerActionsManager.isInitialized) {
            console.log('AnswerActionsManager already initialized by NPCAdmin');
            return;
        }

        // Jeśli NPCAdmin utworzył instancję ale nie zainicjalizował (nie powinno się zdarzyć)
        if (window.answerActionsManager && !window.answerActionsManager.isInitialized) {
            console.log('Initializing existing AnswerActionsManager instance');
            window.answerActionsManager.init();
            return;
        }

        // Utwórz nową instancję globalną tylko jeśli jeszcze nie istnieje
        if (!window.answerActionsManager) {
            console.log('Creating new AnswerActionsManager instance from document.ready');
            window.answerActionsManager = new AnswerActionsManager();
            window.answerActionsManager.init();
        }

        // Dodaj obsługę wysyłania formularza
        $('#answer-form').off('submit.answerActions').on('submit.answerActions', function (e) {
            if (!window.answerActionsManager) {
                return true;
            }

            // Waliduj akcje
            const errors = window.answerActionsManager.validateActions();
            if (errors.length > 0) {
                alert('Błędy walidacji:\n' + errors.join('\n'));
                e.preventDefault();
                return false;
            }

            // Pobierz wszystkie dane z formularza przed wysłaniem
            window.answerActionsManager.collectFormData();
        });
    });

})(jQuery);
