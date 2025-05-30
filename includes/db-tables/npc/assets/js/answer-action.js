/**
 * Answer Actions JavaScript
 * Obsługuje zarządzanie akcjami w odpowiedziach NPC
 */

(function ($) {
    'use strict';


    // Globalna funkcja do pobierania listy przedmiotów
    window.get_items = function () {
        return new Promise((resolve) => {
            $.post(npcAdmin.ajax_url, {
                action: 'npc_get_items',
                nonce: npcAdmin.nonce
            })
                .done(function (response) {
                    if (response.success) {
                        const options = {};
                        response.data.forEach(item => {
                            options[item.id] = item.title;
                        });
                        resolve(options);
                    } else {
                        console.error('Błąd pobierania przedmiotów:', response.data);
                        resolve({});
                    }
                })
                .fail(function () {
                    console.error('Błąd połączenia podczas pobierania przedmiotów');
                    resolve({});
                });
        });
    };

    window.get_locations = function () {
        return new Promise((resolve) => {
            $.post(npcAdmin.ajax_url, {
                action: 'npc_get_locations',
                nonce: npcAdmin.nonce
            })
                .done(function (response) {
                    if (response.success) {
                        const options = {};
                        response.data.forEach(location => {
                            options[location.id] = location.title;
                        });
                        resolve(options);
                    } else {
                        console.error('Błąd pobierania lokacji:', response.data);
                        resolve({});
                    }
                })
                .fail(function () {
                    console.error('Błąd połączenia podczas pobierania lokacji');
                    resolve({});
                });
        });
    };

    window.get_locations_with_scenes = function () {
        return new Promise((resolve) => {
            $.post(npcAdmin.ajax_url, {
                action: 'npc_get_locations_with_scenes',
                nonce: npcAdmin.nonce
            })
                .done(function (response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        console.error('Błąd pobierania lokacji ze scenami:', response.data);
                        resolve([]);
                    }
                })
                .fail(function () {
                    console.error('Błąd połączenia podczas pobierania lokacji ze scenami');
                    resolve([]);
                });
        });
    };



    class AnswerActionsManager {
        constructor() {
            this.actionTypes = {};
            this.actionsData = [];
            this.sortable = null;
            this.init();
        }

        init() {
            this.loadActionTypes();
            this.bindEvents();
            this.loadExistingActions();
            this.initSortable();
        }

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

        bindEvents() {

            // Dodawanie nowej akcji
            $(document).on('click', '.add-action-btn', this.addAction.bind(this));

            // Usuwanie akcji
            $(document).on('click', '.remove-action-btn', this.removeAction.bind(this));

            // Zmiana wartości pól akcji
            $(document).on('change input', '.action-field-input', this.updateActionData.bind(this));
        }

        loadExistingActions() {
            const dataInput = document.getElementById('answer-actions-data');

            if (dataInput && dataInput.value) {
                try {
                    const actions = JSON.parse(dataInput.value) || [];
                    this.actionsData = actions;
                } catch (e) {
                    console.error('Error parsing existing actions data:', e);
                    this.actionsData = [];
                }
            } else {
                this.actionsData = [];
            }
        }

        addAction() {

            const typeSelect = document.getElementById('new-action-type');

            const selectedType = typeSelect ? typeSelect.value : '';

            const actionConfig = this.actionTypes[selectedType];
            const actionIndex = this.actionsData.length;


            // Dodaj do danych
            const newAction = {
                type: selectedType,
                params: {}
            };

            this.actionsData.push(newAction);

            // Renderuj w interfejsie
            this.renderAction(newAction, actionIndex);

            // Wyczyść select
            typeSelect.value = '';

            // Zaktualizuj hidden input
            this.updateHiddenInput();
        }

        removeAction(event) {
            const actionItem = $(event.target).closest('.action-item');

            const actionIndex = parseInt(actionItem.data('index'));

            if (isNaN(actionIndex)) {
                console.error('Invalid action index');
                return;
            }

            // Usuń z danych
            this.actionsData.splice(actionIndex, 1);

            // Usuń z interfejsu
            actionItem.remove();

            // Ponownie indeksuj pozostałe akcje
            this.reindexActions();

            // Zaktualizuj hidden input
            this.updateHiddenInput();

        }

        renderAction(action, index) {
            const actionConfig = this.actionTypes[action.type];
            if (!actionConfig) {
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
            Object.keys(actionConfig.fields || {}).forEach(fieldName => {
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

                const $fieldInput = this.createFieldInput(fieldName, fieldConfig, fieldValue);
                if ($fieldInput) {
                    $fieldWrapper.append($fieldInput);
                    $fieldsContainer.append($fieldWrapper);
                }
            });

            $actionsList.append($actionItem);
        }

        createFieldInput(fieldName, fieldConfig, value) {
            let $input;

            switch (fieldConfig.type) {
                case 'number':
                    $input = $(`
                        <input type="number" 
                               class="regular-text action-field-input"
                               data-field="${fieldName}"
                               value="${value}"
                               min="${fieldConfig.min || 0}"
                               max="${fieldConfig.max || ''}"
                               />
                    `);
                    break;

                case 'text':
                    $input = $(`
                        <input type="text" 
                               class="regular-text action-field-input"
                               data-field="${fieldName}"
                               value="${value}"
                               />
                    `);
                    break;

                case 'select':
                    $input = $(`
                        <select class="regular-text action-field-input" data-field="${fieldName}">
                            <option value="">-- Wybierz --</option>
                        </select>
                    `);

                    const populateOptions = (options) => {
                        $input.find('option:not(:first)').remove(); // Usuń wszystkie opcje oprócz pierwszej

                        if (Array.isArray(options)) {
                            // Prosty array lub array obiektów
                            options.forEach(option => {
                                const optionValue = typeof option === 'object' ? option.id : option;
                                const optionLabel = typeof option === 'object' ? option.title : option;
                                const optionScenes = typeof option === 'object' ? option.scenes : null;
                                const selected = value == optionValue ? 'selected' : '';
                                const scenesAttr = optionScenes ? ` data-scenes='${JSON.stringify(optionScenes)}'` : '';
                                $input.append(`<option value="${optionValue}" ${selected}${scenesAttr}>${optionLabel}</option>`);
                            });
                        } else if (typeof options === 'object') {
                            // Obiekt z tytułami i scenami
                            Object.entries(options).forEach(([optionValue, optionData]) => {
                                if (optionValue === '0') {
                                    // Specjalna obsługa placeholdera
                                    $input.find('option:first').text(optionData);
                                } else {
                                    const optionLabel = typeof optionData === 'object' ? optionData.title : optionData;
                                    const optionScenes = typeof optionData === 'object' ? optionData.scenes : null;
                                    const selected = value == optionValue ? 'selected' : '';
                                    const scenesAttr = optionScenes ? ` data-scenes='${JSON.stringify(optionScenes)}'` : '';
                                    $input.append(`<option value="${optionValue}" ${selected}${scenesAttr}>${optionLabel}</option>`);
                                }
                            });
                        }

                        // Debug: sprawdź czy dane scen zostały dodane
                        console.log('Opcje po populacji:', $input.find('option').map(function () {
                            return {
                                value: $(this).val(),
                                scenes: $(this).attr('data-scenes')
                            };
                        }).get());
                    };

                    // Jeśli pole zależy od innego pola
                    if (fieldConfig.depends_on) {
                        // Opóźniamy bindowanie eventów aby mieć pewność że elementy są w DOM
                        setTimeout(() => {
                            const $parentField = $input.closest('.action-item').find(`[data-field="${fieldConfig.depends_on}"]`);

                            if (!$parentField.length) {
                                console.error(`Nie znaleziono pola nadrzędnego: ${fieldConfig.depends_on}`);
                                return;
                            }

                            // Aktualizuj opcje gdy zmienia się pole nadrzędne
                            $parentField.on('change', (event) => {
                                const $selectedOption = $(event.target).find('option:selected');
                                console.log('Wybrana lokalizacja:', $selectedOption.val());

                                // Próbujemy pobrać dane scen bezpośrednio z atrybutu data
                                const rawScenes = $selectedOption.attr('data-scenes');
                                console.log('Surowe dane scen:', rawScenes);

                                let scenes = [];
                                try {
                                    scenes = rawScenes ? JSON.parse(rawScenes) : [];
                                } catch (e) {
                                    console.error('Błąd parsowania danych scen:', e);
                                }

                                console.log('Przetworzone sceny:', scenes);

                                // Aktualizuj opcje pola zależnego
                                $input.find('option:not(:first)').remove();
                                if (Array.isArray(scenes) && scenes.length > 0) {
                                    scenes.forEach(scene => {
                                        const selected = value == scene.id ? 'selected' : '';
                                        const sceneTitle = scene.title || `Scena ${scene.id}`;
                                        $input.append(`<option value="${scene.id}" ${selected}>${sceneTitle}</option>`);
                                    });
                                } else {
                                    console.log('Brak scen dla wybranej lokalizacji');
                                    $input.val('');
                                }
                            });

                            // Wyzwól zmianę na polu nadrzędnym aby załadować początkowe opcje
                            if ($parentField.val()) {
                                console.log('Wyzwalanie początkowej zmiany dla lokalizacji:', $parentField.val());
                                $parentField.trigger('change');
                            }
                        }, 100); // Zwiększamy opóźnienie do 100ms dla pewności
                    }
                    // Jeśli pole ma dynamiczne opcje (funkcja)
                    else if (typeof fieldConfig.options === 'string' && window[fieldConfig.options]) {
                        window[fieldConfig.options]().then(populateOptions);
                    }
                    // Jeśli pole ma statyczne opcje
                    else if (typeof fieldConfig.options === 'object') {
                        populateOptions(fieldConfig.options);
                    }

                    break;

                default:
                    $input = $(`<input type="text" class="regular-text action-field-input" data-field="${fieldName}" value="${value}" />`);
            }

            return $input;
        }

        updateActionData(event) {
            const $input = $(event.target);
            const $actionItem = $input.closest('.action-item');
            const actionIndex = parseInt($actionItem.data('index'));
            const fieldName = $input.data('field');
            const fieldValue = $input.val();

            if (isNaN(actionIndex) || !fieldName) {
                return;
            }

            if (!this.actionsData[actionIndex]) {
                return;
            }

            // Zaktualizuj dane
            this.actionsData[actionIndex].params[fieldName] = fieldValue;

            // Zaktualizuj hidden input
            this.updateHiddenInput();
        }

        reindexActions() {
            $('.actions-list .action-item').each((index, element) => {
                $(element).attr('data-index', index);
            });
        }

        updateHiddenInput() {
            const $hiddenInput = $('#answer-actions-data');
            $hiddenInput.val(JSON.stringify(this.actionsData));
        }

        initSortable() {
            const $actionsList = $('.actions-list');
            if ($actionsList.length) {
                this.sortable = new Sortable($actionsList[0], {
                    animation: 150,
                    handle: '.action-header', // używaj nagłówka jako uchwytu do przeciągania
                    onEnd: (evt) => {
                        // Zaktualizuj kolejność w danych
                        const oldIndex = evt.oldIndex;
                        const newIndex = evt.newIndex;

                        if (oldIndex !== newIndex) {
                            const item = this.actionsData.splice(oldIndex, 1)[0];
                            this.actionsData.splice(newIndex, 0, item);
                            this.reindexActions();
                            this.updateHiddenInput();
                        }
                    }
                });
            }
        }

        // Publiczna metoda do ładowania akcji (używana przy edycji)
        loadActions(actions) {
            this.actionsData = actions || [];
            $('.actions-list').empty();

            this.actionsData.forEach((action, index) => {
                this.renderAction(action, index);
            });

            this.updateHiddenInput();
        }
    }

    // Globalna instancja
    window.AnswerActionsManager = AnswerActionsManager;

    // Inicjalizacja po załadowaniu DOM
    $(document).ready(function () {
        // Zawsze utwórz instancję globalną, niezależnie od tego czy element istnieje
        window.answerActionsManager = new AnswerActionsManager();
    });

})(jQuery);