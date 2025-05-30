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
                'user_level': 'Sprawdza poziom gracza. Podaj wymaganą wartość.',
                'user_skill': 'Sprawdza poziom wybranej umiejętności gracza. Wybierz umiejętność i podaj wymaganą wartość.',
                'user_class': 'Sprawdza klasę gracza. Wybierz klasę z listy.',
                'user_item': 'Sprawdza czy gracz posiada przedmiot. Wybierz przedmiot i określ liczbę sztuk.',
                'user_mission': 'Sprawdza status misji gracza. Wybierz misję i wymagany status.',
                'user_quest': 'Sprawdza status zadania gracza. Wybierz misję, zadanie i wymagany status.',
                'user_stat': 'Sprawdza wybraną statystykę gracza. Wybierz statystykę i podaj wymaganą wartość.'
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
                    'not_has': 'nie posiada',
                    '==': 'ma dokładnie',
                    '!=': 'nie ma dokładnie',
                    '>': 'ma więcej niż',
                    '>=': 'ma co najmniej',
                    '<': 'ma mniej niż',
                    '<=': 'ma co najwyżej'
                },
                'user_mission': {
                    'not_started': 'nie rozpoczęta',
                    'in_progress': 'w trakcie',
                    'completed': 'ukończona',
                    'failed': 'nieudana',
                    'expired': 'wygasła'
                },
                'user_quest': {
                    'not_started': 'nie rozpoczęte',
                    'in_progress': 'w trakcie',
                    'completed': 'ukończone',
                    'failed': 'nieudane',
                    'skipped': 'pominięte'
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

                this.updateOperators($fields.find('.condition-operator'), type);
                this.updateValueField($fields.find('.condition-value'), type);

                $extraGroup.hide();
                $extraGroup.find('.skill-select, .stat-select, .item-amount, .quest-select').hide();

                if (type === 'user_skill') {
                    $extraGroup.show();
                    $extraGroup.find('.skill-select').show();
                    $extraGroup.find('label').text('Nazwa umiejętności:');
                } else if (type === 'user_stat') {
                    $extraGroup.show();
                    $extraGroup.find('.stat-select').show();
                    $extraGroup.find('label').text('Nazwa statystyki:');
                } else if (type === 'user_item') {
                    const operator = $fields.find('.condition-operator').val();
                    if (operator && !['has', 'not_has'].includes(operator)) {
                        $extraGroup.show();
                        $extraGroup.find('.item-amount').show();
                        $extraGroup.find('label').text('Liczba sztuk:');
                        
                        // Listener tylko dla user_item
                        $fields.find('.condition-operator').off('change.item-amount').on('change.item-amount', function () {
                            const op = $(this).val();
                            if (!['has', 'not_has'].includes(op)) {
                                $extraGroup.show();
                                $extraGroup.find('.item-amount').show();
                                $extraGroup.find('label').text('Liczba sztuk:');
                            } else {
                                $extraGroup.hide();
                            }
                        });
                    }
                } else if (type === 'user_quest') {
                    $extraGroup.show();
                    $extraGroup.find('.quest-select').show();
                    $extraGroup.find('label').text('Zadanie w misji:');
                }

                $description.text(this.conditionDescriptions[type] || '');

            } else {
                $operatorGroup.hide();
                $valueGroup.hide();
                $extraGroup.hide();
                $description.text('');
            }

            this.updateConditionsData();


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
                        '<option value="zadymiarz">🔥 Zadymiarz</option>' +
                        '<option value="zawijacz">💨 Zawijacz</option>' +
                        '<option value="kombinator">⚡ Kombinator</option>' +
                        '</select>';
                    break;

                case 'user_item':
                    // Pobierz dostępne przedmioty przez AJAX jeśli nie mamy ich jeszcze w cache
                    if (!this.itemOptions) {
                        newField = '<select class="condition-value"><option value="">Ładowanie przedmiotów...</option></select>';

                        // W prawdziwej implementacji, tutaj powinno być wywołanie AJAX
                        // Na potrzeby tego przykładu używamy setTimeout
                        const $select = $(newField);
                        $parent.append($select);

                        setTimeout(() => {
                            $.post(npcAdmin.ajax_url, {
                                action: 'npc_get_items',
                                nonce: npcAdmin.nonce
                            }).done((response) => {
                                if (response.success && response.data) {
                                    this.itemOptions = response.data;
                                    $select.empty().append('<option value="">Wybierz przedmiot...</option>');

                                    response.data.forEach(item => {
                                        $select.append(`<option value="${item.id}">${item.title}</option>`);
                                    });
                                } else {
                                    $select.html('<option value="">Błąd ładowania przedmiotów</option>');
                                }
                            }).fail(() => {
                                $select.html('<option value="">Błąd ładowania przedmiotów</option>');
                            });
                        }, 0);

                        return;
                    } else {
                        newField = '<select class="condition-value"><option value="">Wybierz przedmiot...</option>';

                        this.itemOptions.forEach(item => {
                            newField += `<option value="${item.id}">${item.title}</option>`;
                        });

                        newField += '</select>';
                    }
                    break;

                case 'user_mission':
                    // Podobnie jak dla przedmiotów, pobierz misje przez AJAX
                    if (!this.missionOptions) {
                        newField = '<select class="condition-value"><option value="">Ładowanie misji...</option></select>';

                        const $select = $(newField);
                        $parent.append($select);

                        setTimeout(() => {
                            $.post(npcAdmin.ajax_url, {
                                action: 'npc_get_missions',
                                nonce: npcAdmin.nonce
                            }).done((response) => {
                                if (response.success && response.data) {
                                    this.missionOptions = response.data;
                                    $select.empty().append('<option value="">Wybierz misję...</option>');

                                    response.data.forEach(mission => {
                                        $select.append(`<option value="${mission.id}">${mission.title}</option>`);
                                    });
                                } else {
                                    $select.html('<option value="">Błąd ładowania misji</option>');
                                }
                            }).fail(() => {
                                $select.html('<option value="">Błąd ładowania misji</option>');
                            });
                        }, 0);

                        return;
                    } else {
                        newField = '<select class="condition-value"><option value="">Wybierz misję...</option>';

                        this.missionOptions.forEach(mission => {
                            newField += `<option value="${mission.id}">${mission.title}</option>`;
                        });

                        newField += '</select>';
                    }
                    break;

                case 'user_quest':
                    // Dla zadań także ładujemy misje, ale w polu extra będziemy ładować zadania
                    if (!this.missionOptions) {
                        newField = '<select class="condition-value"><option value="">Ładowanie misji...</option></select>';

                        const $select = $(newField);
                        $parent.append($select);

                        setTimeout(() => {
                            $.post(npcAdmin.ajax_url, {
                                action: 'npc_get_missions',
                                nonce: npcAdmin.nonce
                            }).done((response) => {
                                if (response.success && response.data) {
                                    this.missionOptions = response.data;
                                    $select.empty().append('<option value="">Wybierz misję...</option>');

                                    response.data.forEach(mission => {
                                        $select.append(`<option value="${mission.id}">${mission.title}</option>`);
                                    });

                                    // Po załadowaniu misji, ustaw listener na zmianę misji
                                    this.setupQuestListener($select);
                                } else {
                                    $select.html('<option value="">Błąd ładowania misji</option>');
                                }
                            }).fail(() => {
                                $select.html('<option value="">Błąd ładowania misji</option>');
                            });
                        }, 0);

                        return;
                    } else {
                        newField = '<select class="condition-value"><option value="">Wybierz misję...</option>';

                        this.missionOptions.forEach(mission => {
                            newField += `<option value="${mission.id}">${mission.title}</option>`;
                        });

                        newField += '</select>';

                        // Dodaj listener po dodaniu do DOM
                        const $newSelect = $(newField);
                        $parent.append($newSelect);
                        this.setupQuestListener($newSelect);
                        return;
                    }
                    break;

                default:
                    newField = '<input type="text" class="condition-value" placeholder="Wprowadź wartość">';
            }

            $parent.append(newField);
        }

        setupQuestListener($missionSelect) {
            const self = this;

            $missionSelect.off('change.quest-listener').on('change.quest-listener', function () {
                const missionId = $(this).val();
                const $condition = $(this).closest('.condition-item');
                const $questSelect = $condition.find('.condition-field.quest-select');

                if (!missionId) {
                    $questSelect.empty().append('<option value="">Wybierz zadanie...</option>');
                    return;
                }

                // Pokaż loading
                $questSelect.empty().append('<option value="">Ładowanie zadań...</option>');

                // Pobierz zadania dla wybranej misji
                $.post(npcAdmin.ajax_url, {
                    action: 'npc_get_quests_for_mission',
                    nonce: npcAdmin.nonce,
                    mission_id: missionId
                }).done((response) => {
                    if (response.success && response.data) {
                        $questSelect.empty().append('<option value="">Wybierz zadanie...</option>');

                        response.data.forEach(quest => {
                            $questSelect.append(`<option value="${quest.id}">${quest.title}</option>`);
                        });
                    } else {
                        $questSelect.empty().append('<option value="">Błąd ładowania zadań</option>');
                    }
                }).fail(() => {
                    $questSelect.empty().append('<option value="">Błąd ładowania zadań</option>');
                });
            });
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

                    // Dodaj pole field dla różnych typów warunków
                    if (type === 'user_stat') {
                        conditionData.field = $condition.find('.condition-field.stat-select').val() || '';
                    } else if (type === 'user_skill') {
                        conditionData.field = $condition.find('.condition-field.skill-select').val() || '';
                    } else if (type === 'user_item' && !['has', 'not_has'].includes(conditionData.operator)) {
                        // Dla przedmiotów z operatorami numerycznymi zapisujemy liczbę sztuk w polu field
                        conditionData.field = $condition.find('.condition-field.item-amount').val() || '1';
                    } else if (type === 'user_quest') {
                        // Dla zadań zapisujemy ID zadania w polu field
                        conditionData.field = $condition.find('.condition-field.quest-select').val() || '';
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
                        errors.push(`Warunek ${index + 1}: Wybierz misję z listy`);
                    }

                    const operator = $condition.find('.condition-operator').val();
                    if (!['not_started', 'in_progress', 'completed', 'failed', 'expired'].includes(operator)) {
                        errors.push(`Warunek ${index + 1}: Wybierz poprawny status misji`);
                    }
                }

                if (type === 'user_class') {
                    const validClasses = ['zadymiarz', 'zawijacz', 'kombinator'];
                    if (!validClasses.includes(value)) {
                        errors.push(`Warunek ${index + 1}: Wybierz prawidłową klasę`);
                    }
                }

                if (type === 'user_item') {
                    const itemId = parseInt(value);
                    if (isNaN(itemId) || itemId < 1) {
                        errors.push(`Warunek ${index + 1}: Wybierz przedmiot z listy`);
                    }

                    const operator = $condition.find('.condition-operator').val();
                    if (!['has', 'not_has'].includes(operator)) {
                        const amountField = $condition.find('.condition-field.item-amount').val();
                        if (!amountField || isNaN(amountField) || parseInt(amountField) < 0) {
                            errors.push(`Warunek ${index + 1}: Wprowadź liczbę sztuk przedmiotu`);
                        }
                    }
                }

                if (type === 'user_stat') {
                    const field = $condition.find('.condition-field.stat-select').val();
                    if (!field) {
                        errors.push(`Warunek ${index + 1}: Wybierz statystykę`);
                    }
                }

                if (type === 'user_skill') {
                    const field = $condition.find('.condition-field.skill-select').val();
                    if (!field) {
                        errors.push(`Warunek ${index + 1}: Wybierz umiejętność`);
                    }
                }

                if (type === 'user_quest') {
                    const missionId = parseInt(value);
                    if (isNaN(missionId) || missionId < 1) {
                        errors.push(`Warunek ${index + 1}: Wybierz misję z listy`);
                    }

                    const questId = $condition.find('.condition-field.quest-select').val();
                    if (!questId) {
                        errors.push(`Warunek ${index + 1}: Wybierz zadanie z listy`);
                    }

                    const operator = $condition.find('.condition-operator').val();
                    if (!['not_started', 'in_progress', 'completed', 'failed', 'skipped'].includes(operator)) {
                        errors.push(`Warunek ${index + 1}: Wybierz poprawny status zadania`);
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
