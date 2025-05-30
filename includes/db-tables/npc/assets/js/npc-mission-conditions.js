/**
 * NPC Mission Conditions
 * Rozszerzenie funkcjonalności warunków dla misji
 */
(function ($) {
    'use strict';

    // Główna funkcja inicjalizująca rozszerzenie dla misji
    function initMissionConditions() {
        // 1. Dodaj obsługę pola dodatkowego dla misji w funkcji handleTypeChange
        extendHandleTypeChange();

        // 2. Zaktualizuj funkcję updateValueField aby obsługiwała parametry misji
        extendUpdateValueField();

        // 3. Zaktualizuj funkcję updateConditionsData aby zapisywała parametry misji
        extendUpdateConditionsData();

        // 4. Zaktualizuj walidację dla misji
        extendValidateConditions();
    }

    // Rozszerzenie funkcji handleTypeChange
    function extendHandleTypeChange() {
        const originalHandleTypeChange = NPCConditionsManager.prototype.handleTypeChange;

        NPCConditionsManager.prototype.handleTypeChange = function (event) {
            // Wywołaj oryginalną funkcję
            originalHandleTypeChange.call(this, event);

            // Dodaj obsługę dla misji
            const $select = $(event.target);
            const $condition = $select.closest('.condition-item');
            const $fields = $condition.find('.condition-fields');
            const type = $select.val();

            if (type === 'user_mission') {
                const $extraGroup = $fields.find('.field-group-extra');
                const $missionParameter = $extraGroup.find('.mission-parameter');

                $extraGroup.show();
                $missionParameter.show();
                $extraGroup.find('label').text('Dodatkowy warunek misji:');

                // Obsługa zmiany operatora dla misji
                $fields.find('.condition-operator').off('change.mission').on('change.mission', function () {
                    const op = $(this).val();
                    // Tu można dodać specyficzną logikę dla różnych operatorów misji
                });
            }
        };
    }

    // Rozszerzenie funkcji updateValueField
    function extendUpdateValueField() {
        const originalUpdateValueField = NPCConditionsManager.prototype.updateValueField;

        NPCConditionsManager.prototype.updateValueField = function ($valueField, type) {
            // Dla misji nie modyfikujemy oryginalnego zachowania
            originalUpdateValueField.call(this, $valueField, type);
        };
    }

    // Rozszerzenie funkcji updateConditionsData
    function extendUpdateConditionsData() {
        const originalUpdateConditionsData = NPCConditionsManager.prototype.updateConditionsData;

        NPCConditionsManager.prototype.updateConditionsData = function () {
            const self = this;
            $('.conditions-manager').each((index, manager) => {
                const $manager = $(manager);

                // Zachowaj obecny stan warunków
                let currentConditions = [];
                try {
                    const data = JSON.parse($manager.find('.conditions-data').val() || '[]');
                    currentConditions = data.conditions || data;
                } catch (e) {
                    console.warn('Error parsing conditions:', e);
                }

                // Wywołaj oryginalną funkcję, która zaktualizuje większość pól
                originalUpdateConditionsData.call(self);

                // Dodaj obsługę dla pola parametru misji
                $manager.find('.condition-item').each((condIndex, condition) => {
                    const $condition = $(condition);
                    const type = $condition.find('.condition-type').val();

                    if (type === 'user_mission') {
                        // Pobierz wartość parametru misji
                        const missionParameter = $condition.find('.mission-parameter').val() || '';

                        // Zaktualizuj pole field dla warunków misji
                        try {
                            const data = JSON.parse($manager.find('.conditions-data').val());
                            if (data.conditions && data.conditions[condIndex] && data.conditions[condIndex].type === 'user_mission') {
                                data.conditions[condIndex].field = missionParameter;
                                $manager.find('.conditions-data').val(JSON.stringify(data));
                            }
                        } catch (e) {
                            console.warn('Error updating mission parameter:', e);
                        }
                    }
                });
            });
        };
    }

    // Rozszerzenie funkcji validateConditions
    function extendValidateConditions() {
        const originalValidateConditions = NPCConditionsManager.prototype.validateConditions;

        NPCConditionsManager.prototype.validateConditions = function ($manager) {
            const errors = originalValidateConditions.call(this, $manager);

            $manager.find('.condition-item').each((index, condition) => {
                const $condition = $(condition);
                const type = $condition.find('.condition-type').val();

                if (type === 'user_mission') {
                    const value = $condition.find('.condition-value').val();
                    const operator = $condition.find('.condition-operator').val();

                    if (!value) {
                        errors.push(`Warunek ${index + 1}: Wybierz misję`);
                    }

                    if (!operator || !['not_started', 'in_progress', 'completed', 'failed', 'expired'].includes(operator)) {
                        errors.push(`Warunek ${index + 1}: Wybierz poprawny status misji`);
                    }
                }
            });

            return errors;
        };
    }

    // Inicjalizacja po załadowaniu dokumentu
    $(document).ready(function () {
        // Poczekaj na inicjalizację głównej klasy NPCConditionsManager
        setTimeout(function () {
            if (typeof NPCConditionsManager !== 'undefined') {
                initMissionConditions();
            }
        }, 500);
    });

})(jQuery);
