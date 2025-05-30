/**
 * Answer Actions JavaScript
 * Obsługuje zarządzanie akcjami w odpowiedziach NPC
 */

(function ($) {
    'use strict';

    class AnswerActionsManager {
        constructor() {
            this.actionTypes = {};
            this.actionsData = [];
            this.init();
        }

        init() {
            console.log('AnswerActionsManager: Initializing...');
            this.loadActionTypes();
            this.bindEvents();
            this.loadExistingActions();
            console.log('AnswerActionsManager: Initialization complete');
        }

        loadActionTypes() {
            console.log('AnswerActionsManager: Loading action types');

            const configScript = document.getElementById('action-types-config');
            console.log('Config script element:', configScript);

            if (configScript) {
                try {
                    this.actionTypes = JSON.parse(configScript.textContent);
                    console.log('Action types loaded:', this.actionTypes);
                } catch (e) {
                    console.error('Error parsing action types config:', e);
                }
            } else {
                console.warn('No action-types-config script found');
            }
        }

        bindEvents() {
            console.log('AnswerActionsManager: Binding events');

            // Dodawanie nowej akcji
            $(document).on('click', '.add-action-btn', this.addAction.bind(this));

            // Usuwanie akcji
            $(document).on('click', '.remove-action-btn', this.removeAction.bind(this));

            // Zmiana wartości pól akcji
            $(document).on('change input', '.action-field-input', this.updateActionData.bind(this));
        }

        loadExistingActions() {
            console.log('AnswerActionsManager: Loading existing actions');

            const dataInput = document.getElementById('answer-actions-data');
            console.log('Data input element:', dataInput);
            console.log('Data input value:', dataInput ? dataInput.value : 'none');

            if (dataInput && dataInput.value) {
                try {
                    this.actionsData = JSON.parse(dataInput.value) || [];
                    console.log('Existing actions loaded:', this.actionsData);
                } catch (e) {
                    console.error('Error parsing existing actions data:', e);
                    this.actionsData = [];
                }
            } else {
                this.actionsData = [];
                console.log('No existing actions data found, initialized empty array');
            }
        }

        addAction() {
            console.log('AnswerActionsManager: addAction called');

            const typeSelect = document.getElementById('new-action-type');
            console.log('Type select element:', typeSelect);

            const selectedType = typeSelect ? typeSelect.value : '';
            console.log('Selected type:', selectedType);

            if (!selectedType || !this.actionTypes[selectedType]) {
                console.log('No type selected or invalid type');
                alert('Wybierz typ akcji.');
                return;
            }

            const actionConfig = this.actionTypes[selectedType];
            const actionIndex = this.actionsData.length;

            console.log('Adding action:', selectedType, 'at index:', actionIndex);

            // Dodaj do danych
            const newAction = {
                type: selectedType,
                params: {}
            };

            // Ustaw domyślne wartości
            Object.keys(actionConfig.fields).forEach(fieldName => {
                const fieldConfig = actionConfig.fields[fieldName];
                newAction.params[fieldName] = fieldConfig.default || '';
            });

            this.actionsData.push(newAction);

            // Renderuj w interfejsie
            this.renderAction(newAction, actionIndex);

            // Wyczyść select
            typeSelect.value = '';

            // Zaktualizuj hidden input
            this.updateHiddenInput();

            console.log('Action added successfully');
        }

        removeAction(event) {
            console.log('AnswerActionsManager: removeAction called');

            const actionItem = $(event.target).closest('.action-item');
            console.log('Action item:', actionItem);

            const actionIndex = parseInt(actionItem.data('index'));
            console.log('Action index:', actionIndex);

            if (isNaN(actionIndex)) {
                console.error('Invalid action index');
                return;
            }

            // Usuń z danych
            this.actionsData.splice(actionIndex, 1);
            console.log('Action removed from data. New length:', this.actionsData.length);

            // Usuń z interfejsu
            actionItem.remove();
            console.log('Action item removed from UI');

            // Ponownie indeksuj pozostałe akcje
            this.reindexActions();

            // Zaktualizuj hidden input
            this.updateHiddenInput();

            console.log('Remove action completed');
        }

        renderAction(action, index) {
            const actionConfig = this.actionTypes[action.type];
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
            Object.keys(actionConfig.fields).forEach(fieldName => {
                const fieldConfig = actionConfig.fields[fieldName];
                const fieldValue = action.params[fieldName] || fieldConfig.default || '';

                const $fieldWrapper = $(`
                    <div class="action-field">
                        <label>${fieldConfig.label}</label>
                    </div>
                `);

                const $fieldInput = this.createFieldInput(fieldName, fieldConfig, fieldValue);
                $fieldWrapper.append($fieldInput);
                $fieldsContainer.append($fieldWrapper);
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
                        </select>
                    `);

                    let options = {};
                    if (typeof fieldConfig.options === 'string' && window[fieldConfig.options]) {
                        options = window[fieldConfig.options]();
                    } else if (typeof fieldConfig.options === 'object') {
                        options = fieldConfig.options;
                    }

                    Object.keys(options).forEach(optionValue => {
                        const selected = value == optionValue ? 'selected' : '';
                        $input.append(`<option value="${optionValue}" ${selected}>${options[optionValue]}</option>`);
                    });
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