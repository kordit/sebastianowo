/**
 * NPC Conditions Manager JavaScript
 * Obsługuje interfejs zarządzania warunkami wyświetlania
 */

(function ($) {
    'use strict';

    class NPCConditionsManager {
        constructor() {
            this.conditionIndex = 0;
            this.conditionDescriptions = {
                'user_level': 'Sprawdza poziom gracza. Użyj liczby całkowitej.',
                'user_skill': 'Sprawdza poziom umiejętności gracza (walka, kradzież, handel, itp.). Użyj liczby całkowitej.',
                'user_class': 'Sprawdza klasę gracza. Wybierz z dostępnych opcji.',
                'user_item': 'Sprawdza czy gracz posiada określony przedmiot. Użyj ID przedmiotu.',
                'user_mission': 'Sprawdza status misji gracza. Użyj ID misji.',
                'quest_completed': '[LEGACY] Sprawdza czy zadanie zostało ukończone. Użyj ID zadania.',
                'user_stat': 'Sprawdza statystykę gracza (np. siła, zręczność). Podaj nazwę statystyki.'
            };

            this.operatorsByType = {
                'user_level': {
                    '==': 'równe',
                    '!=': 'różne od',
                    '>': 'większe niż',
                    '>=': 'większe lub równe',
                    '<': 'mniejsze niż',
                    '<=': 'mniejsze lub równe'
                },
                'user_skill': {
                    '==': 'równe',
                    '!=': 'różne od',
                    '>': 'większe niż',
                    '>=': 'większe lub równe',
                    '<': 'mniejsze niż',
                    '<=': 'mniejsze lub równe'
                },
                'user_class': {
                    '==': 'jest klasą',
                    '!=': 'nie jest klasą'
                },
                'user_stat': {
                    '==': 'równe',
                    '!=': 'różne od',
                    '>': 'większe niż',
                    '>=': 'większe lub równe',
                    '<': 'mniejsze niż',
                    '<=': 'mniejsze lub równe'
                },
                'user_item': {
                    'has': 'posiada',
                    'not_has': 'nie posiada'
                },
                'user_mission': {
                    'not_started': 'nie rozpoczęta',
                    'in_progress': 'w trakcie',
                    'completed': 'ukończona',
                    'failed': 'nieudana',
                    'expired': 'wygasła'
                },
                'quest_completed': {
                    'completed': 'ukończone',
                    'not_completed': 'nie ukończone'
                }
            };

            this.init();
        }

        init() {
            this.bindEvents();
            this.updateConditionNumbers();
        }

        bindEvents() {
            // Dodawanie nowego warunku
            $(document).on('click', '.add-condition-btn', this.addCondition.bind(this));

            // Usuwanie warunku
            $(document).on('click', '.delete-condition', this.deleteCondition.bind(this));

            // Zmiana typu warunku
            $(document).on('change', '.condition-type', this.handleTypeChange.bind(this));

            // Zmiana logiki warunków (OR/AND)
            $(document).on('change', '.conditions-logic-operator', this.updateConditionsData.bind(this));

            // Aktualizacja danych przy zmianie pól
            $(document).on('change input', '.condition-item input, .condition-item select', this.updateConditionsData.bind(this));

            // Inicjalizacja istniejących warunków
            this.initExistingConditions();
        }

        addCondition(event) {
            event.preventDefault();

            const $manager = $(event.target).closest('.conditions-manager');
            const $list = $manager.find('.conditions-list');
            const $noConditions = $list.find('.no-conditions');

            // Usuń komunikat "brak warunków"
            if ($noConditions.length) {
                $noConditions.remove();
            }

            // Pobierz template
            const template = $('#condition-template').html();
            const conditionHtml = template
                .replace(/\{\{INDEX\}\}/g, this.conditionIndex)
                .replace(/\{\{NUMBER\}\}/g, this.conditionIndex + 1);

            const $newCondition = $(conditionHtml);
            $newCondition.addClass('adding');

            $list.append($newCondition);

            // Animacja
            setTimeout(() => {
                $newCondition.removeClass('adding');
            }, 300);

            this.conditionIndex++;
            this.updateConditionNumbers();
            this.updateConditionsData();
        }

        deleteCondition(event) {
            event.preventDefault();

            const $condition = $(event.target).closest('.condition-item');
            const $manager = $condition.closest('.conditions-manager');
            const $list = $manager.find('.conditions-list');

            $condition.addClass('removing');

            setTimeout(() => {
                $condition.remove();

                // Sprawdź czy nie ma już warunków
                if ($list.find('.condition-item').length === 0) {
                    $list.html('<div class="no-conditions"><p>Brak warunków. Element będzie zawsze widoczny.</p></div>');
                }

                this.updateConditionNumbers();
                this.updateConditionsData();
            }, 300);
        }

        handleTypeChange(event) {
            const $select = $(event.target);
            const $condition = $select.closest('.condition-item');
            const $fields = $condition.find('.condition-fields');
            const type = $select.val();

            // Pokaż/ukryj grupy pól
            const $operatorGroup = $fields.find('.operator-group');
            const $valueGroup = $fields.find('.value-group');
            const $extraGroup = $fields.find('.field-group-extra');
            const $description = $fields.find('.condition-help');

            if (type) {
                $operatorGroup.show();
                $valueGroup.show();

                // Aktualizuj operatory
                this.updateOperators($fields.find('.condition-operator'), type);

                // Aktualizuj pole wartości
                this.updateValueField($fields.find('.condition-value'), type);

                // Pokaż pole dodatkowe dla user_stat i user_skill
                if (type === 'user_stat' || type === 'user_skill') {
                    $extraGroup.show();

                    // Pokaż odpowiednie pole - select dla user_skill, input dla user_stat
                    if (type === 'user_skill') {
                        $extraGroup.find('.skill-select').show();
                        $extraGroup.find('.text-input').hide();
                        $extraGroup.find('label').text('Nazwa umiejętności:');
                    } else {
                        $extraGroup.find('.skill-select').hide();
                        $extraGroup.find('.text-input').show();
                        $extraGroup.find('label').text('Nazwa statystyki:');
                    }
                } else {
                    $extraGroup.hide();
                }

                // Aktualizuj opis
                $description.text(this.conditionDescriptions[type] || '');
            } else {
                $operatorGroup.hide();
                $valueGroup.hide();
                $extraGroup.hide();
                $description.text('');
            }

            this.updateConditionsData();
        }

        updateOperators($operatorSelect, type) {
            const operators = this.operatorsByType[type] || this.operatorsByType['custom'];

            $operatorSelect.empty();

            Object.entries(operators).forEach(([value, label]) => {
                $operatorSelect.append(`<option value="${value}">${label}</option>`);
            });
        }

        updateValueField($valueField, type) {
            const $parent = $valueField.parent();

            // Usuń stare pole
            $valueField.remove();

            let newField = '';

            switch (type) {
                case 'user_level':
                case 'user_stat':
                case 'user_skill':
                    newField = '<input type="number" class="condition-value" min="0" placeholder="Wprowadź liczbę">';
                    break;

                case 'user_class':
                    newField = '<select class="condition-value">' +
                        '<option value="">Wybierz klasę...</option>' +
                        '<option value="wojownik">Wojownik</option>' +
                        '<option value="handlarz">Handlarz</option>' +
                        '<option value="zlodziej">Złodziej</option>' +
                        '<option value="dyplomata">Dyplomata</option>' +
                        '</select>';
                    break;

                case 'user_item':
                    newField = '<input type="text" class="condition-value" placeholder="ID przedmiotu">';
                    break;

                case 'user_mission':
                    newField = '<input type="number" class="condition-value" placeholder="ID misji" min="1">';
                    break;

                case 'quest_completed':
                    newField = '<input type="text" class="condition-value" placeholder="ID zadania">';
                    break;

                default:
                    newField = '<input type="text" class="condition-value" placeholder="Wprowadź wartość">';
            }

            $parent.append(newField);
        }

        updateConditionNumbers() {
            $('.condition-item').each(function (index) {
                $(this).find('.condition-number').text((index + 1) + '.');
                $(this).attr('data-index', index);
            });
        }

        updateConditionsData() {
            $('.conditions-manager').each((index, manager) => {
                const $manager = $(manager);
                const $dataField = $manager.find('.conditions-data');
                const $logicSelect = $manager.find('.conditions-logic-operator');
                const conditions = [];

                $manager.find('.condition-item').each((condIndex, condition) => {
                    const $condition = $(condition);
                    const type = $condition.find('.condition-type').val();

                    if (!type) return;

                    const conditionData = {
                        type: type,
                        operator: $condition.find('.condition-operator').val() || '==',
                        value: $condition.find('.condition-value').val() || ''
                    };

                    // Dodaj pole field dla user_stat i user_skill
                    if (type === 'user_stat') {
                        conditionData.field = $condition.find('.condition-field.text-input').val() || '';
                    } else if (type === 'user_skill') {
                        conditionData.field = $condition.find('.condition-field.skill-select').val() || '';
                    }

                    conditions.push(conditionData);
                });

                // Utwórz nową strukturę z logiką OR/AND
                const data = {
                    logic: $logicSelect.val() || 'AND',
                    conditions: conditions
                };

                $dataField.val(JSON.stringify(data));
            });
        }

        initExistingConditions() {
            $('.conditions-manager').each((index, manager) => {
                const $manager = $(manager);
                const $dataField = $manager.find('.conditions-data');

                try {
                    const data = JSON.parse($dataField.val() || '[]');
                    let conditions = [];

                    // Sprawdź czy to nowa struktura z logiką OR/AND
                    if (data.logic && data.conditions) {
                        conditions = data.conditions;
                        // Ustaw logikę w selekcie
                        $manager.find('.conditions-logic-operator').val(data.logic);
                    } else if (Array.isArray(data)) {
                        // Stara struktura - tablica warunków
                        conditions = data;
                        $manager.find('.conditions-logic-operator').val('AND');
                    }

                    if (conditions.length > 0) {
                        // Usuń komunikat "brak warunków"
                        $manager.find('.no-conditions').remove();

                        // Znajdź najwyższy indeks
                        this.conditionIndex = Math.max(this.conditionIndex, conditions.length);

                        // Inicjalizuj pola dla istniejących warunków
                        $manager.find('.condition-item').each((condIndex, condition) => {
                            const $condition = $(condition);
                            const type = $condition.find('.condition-type').val();

                            if (type) {
                                this.handleTypeChange({ target: $condition.find('.condition-type')[0] });
                            }
                        });
                    }
                } catch (e) {
                    console.warn('Error parsing existing conditions:', e);
                }
            });
        }

        // Nowa metoda do ładowania warunków z zewnątrz
        loadExistingConditions() {
            this.initExistingConditions();
        }

        // Metoda pomocnicza do walidacji warunków
        validateConditions($manager) {
            const errors = [];

            $manager.find('.condition-item').each((index, condition) => {
                const $condition = $(condition);
                const type = $condition.find('.condition-type').val();
                const value = $condition.find('.condition-value').val();

                if (!type) {
                    errors.push(`Warunek ${index + 1}: Wybierz typ warunku`);
                    return;
                }

                if (!value && !['user_mission'].includes(type)) {
                    errors.push(`Warunek ${index + 1}: Wprowadź wartość`);
                    return;
                }

                // Dodatkowa walidacja dla specific types
                if (['user_level', 'user_stat', 'user_skill'].includes(type)) {
                    if (isNaN(value) || parseInt(value) < 0) {
                        errors.push(`Warunek ${index + 1}: Wartość musi być liczbą nieujemną`);
                    }
                }

                if (type === 'user_mission') {
                    const missionId = parseInt(value);
                    if (isNaN(missionId) || missionId < 1) {
                        errors.push(`Warunek ${index + 1}: ID misji musi być liczbą większą od 0`);
                    }
                }

                if (type === 'user_class') {
                    const validClasses = ['wojownik', 'handlarz', 'zlodziej', 'dyplomata'];
                    if (!validClasses.includes(value)) {
                        errors.push(`Warunek ${index + 1}: Wybierz prawidłową klasę`);
                    }
                }

                if (type === 'user_stat') {
                    const field = $condition.find('.condition-field.text-input').val();
                    if (!field) {
                        errors.push(`Warunek ${index + 1}: Podaj nazwę statystyki`);
                    }
                }

                if (type === 'user_skill') {
                    const field = $condition.find('.condition-field.skill-select').val();
                    if (!field) {
                        errors.push(`Warunek ${index + 1}: Wybierz umiejętność`);
                    }
                }
            });

            return errors;
        }

        // Publiczna metoda do walidacji przed zapisem
        static validateAllConditions() {
            const errors = [];

            $('.conditions-manager').each((index, manager) => {
                const $manager = $(manager);
                const context = $manager.data('context');
                const conditionErrors = new NPCConditionsManager().validateConditions($manager);

                conditionErrors.forEach(error => {
                    errors.push(`${context === 'dialog' ? 'Dialog' : 'Odpowiedź'} - ${error}`);
                });
            });

            return errors;
        }
    }

    // Inicjalizuj gdy dokument jest gotowy
    $(document).ready(function () {
        new NPCConditionsManager();

        // Dodaj walidację do formularzy
        $('#dialog-form').on('submit', function (e) {
            const errors = NPCConditionsManager.validateAllConditions();
            if (errors.length > 0) {
                e.preventDefault();
                alert('Błędy w warunkach:\n' + errors.join('\n'));
                return false;
            }
        });
    });

    // Udostępnij klasę globalnie
    window.NPCConditionsManager = NPCConditionsManager;

})(jQuery);
