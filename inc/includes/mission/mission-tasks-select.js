(function ($) {
    console.log('[MISSION JS] Skrypt załadowany - jQuery Version:', $.fn.jquery);

    // Funkcja pomocnicza do ładowania zadań do selecta
    // Tablica zainicjalizowanych już selectów zadań
    const loadedTaskSelectors = {};

    function loadTasksIntoSelect(missionId, $taskSelect) {
        // Unikalne ID dla tego selekta
        const taskSelectId = $taskSelect.attr('name') || $taskSelect.attr('id');

        // Sprawdź, czy to pole ma już załadowane zadania dla tej misji
        if (taskSelectId && loadedTaskSelectors[taskSelectId] === missionId) {
            console.log('[MISSION JS] Ten select ma już załadowane zadania dla misji ' + missionId + ', pomijam:', taskSelectId);
            return;
        }

        console.log('[MISSION JS] Ładowanie zadań dla misji ID:', missionId, 'do selecta:', $taskSelect);

        // Pobierz aktualnie wybraną wartość przed wyczyszczeniem opcji
        const currentSelectedValue = $taskSelect.val();

        // Sprawdź także wszystkie możliwe miejsca gdzie może być zapisana wartość
        const savedValue =
            currentSelectedValue ||
            $taskSelect.find('option:selected').val() ||
            $taskSelect.data('selected') ||
            $taskSelect.siblings('input[type="hidden"]').val();

        console.log('[MISSION JS] Aktualna/zapisana wartość przed wyczyszczeniem:', currentSelectedValue, savedValue);

        // Zapamiętaj wartość w atrybucie data-selected przed wyczyszczeniem
        if (savedValue) {
            $taskSelect.data('selected', savedValue);
        }

        // Wyczyść select przed dodaniem nowych opcji
        $taskSelect.empty();

        // ZAWSZE dodaj placeholder jako pierwszą opcję
        $taskSelect.append($('<option>', { value: '', text: 'Wybierz zadanie z misji' }));

        if (!missionId) {
            console.log('[MISSION JS] Brak ID misji');
            return;
        }

        console.log('[MISSION JS] Wysyłanie zapytania AJAX o zadania');
        console.log('[MISSION JS] Zapisana wartość przed AJAX:', savedValue);

        // Pobierz ID postu (jeśli jesteśmy na stronie edycji)
        const currentPostId = $('#post_ID').val() || 0;

        // Pobierz ścieżkę pola (nazwę bez "acf")
        const fieldPath = $taskSelect.attr('name')
            ? $taskSelect.attr('name').replace('acf', '').replace(/\]\[/g, '][')
            : '';

        console.log('[MISSION JS] ID postu:', currentPostId, 'Ścieżka pola:', fieldPath);

        // Oznacz ten select jako przetwarzany z tym ID misji
        if (taskSelectId) {
            loadedTaskSelectors[taskSelectId] = missionId;
        }

        $.post(ajaxurl, {
            action: 'get_mission_tasks',
            mission_id: missionId,
            selected_task: savedValue || currentSelectedValue,
            post_id: currentPostId,
            field_path: fieldPath
        }, function (response) {
            console.log('[MISSION JS] Odpowiedź AJAX:', response);

            let hasSetSelectedValue = false;

            if (response.success && response.data && response.data.tasks && Object.keys(response.data.tasks).length) {
                // Tablica do śledzenia już dodanych zadań, aby uniknąć duplikatów
                const addedTaskIds = [];
                // Przygotuj unikalną listę zadań (usuń duplikaty)
                const uniqueTasks = {};

                // Najpierw przefiltruj zadania, aby uzyskać unikalne
                $.each(response.data.tasks, function (id, title) {
                    uniqueTasks[id] = title;
                });

                // Teraz dodaj unikalne zadania do selecta
                $.each(uniqueTasks, function (id, title) {
                    console.log('[MISSION JS] Dodaję zadanie:', id, title);

                    // Sprawdź wszystkie możliwe warianty wartości wybranej
                    const isSelected = (
                        id === response.data.selected_task ||
                        id === savedValue ||
                        id === currentSelectedValue
                    );

                    if (isSelected) {
                        console.log('[MISSION JS] Znaleziono wybraną wartość:', id);
                        $taskSelect.append($('<option>', {
                            value: id,
                            text: title,
                            selected: 'selected'
                        }));
                        hasSetSelectedValue = true;
                    } else {
                        $taskSelect.append($('<option>', { value: id, text: title }));
                    }
                });

                // Jeśli nie udało się ustawić wartości, a mamy zapisaną, próbujemy jeszcze raz
                if (!hasSetSelectedValue && savedValue) {
                    console.log('[MISSION JS] Próba ustawienia zapisanej wartości po załadowaniu:', savedValue);
                    setTimeout(function () {
                        $taskSelect.val(savedValue);
                    }, 100);
                }
            } else {
                $taskSelect.append($('<option>', { value: '', text: 'Brak dostępnych zadań' }));
            }

            // Odświeżenie selecta
            try {
                console.log('[MISSION JS] Aktualizacja selecta zakończona');
                $taskSelect.trigger('change');
            } catch (e) {
                console.log('[MISSION JS] Błąd podczas aktualizacji selecta:', e);
            }
        });
    }

    function findMissionSelects(context) {
        // Sprawdź wszystkie możliwe selektory dla pól wyboru misji
        const selectors = [
            // Standardowe selektory
            '[name$="[mission_id]"]',
            '[name*="field_mission_id"]',
            'select[data-name="mission_id"]',
            // ACF selektory
            '.acf-field-post-object[data-name="mission_id"] select',
            '.acf-field[data-key="field_mission_id"] select',
            // Selektory specyficzne dla layoutu
            '.layout select[name*="mission_id"]'
        ];

        // Połącz wszystkie selektory
        return $(context).find(selectors.join(', '));
    }

    function findMissionLikeSelects(context) {
        // Bardziej agresywne wyszukiwanie
        return $(context).find('select').filter(function () {
            const name = $(this).attr('name') || '';
            const id = $(this).attr('id') || '';
            const dataName = $(this).data('name') || '';
            const parentName = $(this).closest('.acf-field').data('name') || '';

            // Sprawdź czy to pole wygląda na pole wyboru misji
            return (name.indexOf('mission') !== -1 && name.indexOf('task') === -1) ||
                (id.indexOf('mission') !== -1 && id.indexOf('task') === -1) ||
                (dataName === 'mission_id') ||
                (parentName === 'mission_id');
        });
    }

    function findTaskSelect(missionSelect) {
        const $missionSelect = $(missionSelect);
        const missionName = $missionSelect.attr('name') || '';
        const missionId = $missionSelect.attr('id') || '';

        console.log('[MISSION JS] Szukam pola zadań dla misji:', missionName);

        // 1. Próbuj znaleźć po podobnej nazwie pola
        let taskFieldName = '';
        if (missionName.includes('field_mission_id')) {
            taskFieldName = missionName.replace('field_mission_id', 'field_mission_task_id');
        } else if (missionName.endsWith('[mission_id]')) {
            taskFieldName = missionName.replace('[mission_id]', '[mission_task_id]');
        } else {
            taskFieldName = missionName.replace('mission_id', 'mission_task_id');
        }

        let $taskSelect = $('[name="' + taskFieldName + '"]');
        if ($taskSelect.length) {
            console.log('[MISSION JS] Znaleziono pole zadań po nazwie:', taskFieldName);
            return $taskSelect;
        }

        // 2. Spróbuj znaleźć po strukturze DOM
        const $parent = $missionSelect.closest('.layout, .acf-fields, .acf-row');
        if ($parent.length) {
            const $taskSelects = $parent.find('select').filter(function () {
                const name = $(this).attr('name') || '';
                return name.indexOf('task_id') !== -1;
            });

            if ($taskSelects.length) {
                console.log('[MISSION JS] Znaleziono pole zadań po strukturze DOM:', $taskSelects.first().attr('name'));
                return $taskSelects.first();
            }
        }

        console.log('[MISSION JS] Nie znaleziono pola zadań!');
        return $();
    }

    // Funkcja do pobierania zapisanych danych misji z tablicy anwser
    // Globalny obiekt do śledzenia, które pola zadań już zostały zainicjalizowane
    const initializedTaskSelects = {};

    function getSelectedMissionData($missionSelect, $taskSelect) {
        console.log('[MISSION JS] Pobieranie zapisanych danych misji z tablicy anwser');

        // Pobierz ID postu i ścieżkę pola
        const currentPostId = $('#post_ID').val() || 0;
        const fieldPath = $missionSelect.attr('name') || '';

        // Unikalne ID dla tego pola zadania
        const taskSelectId = $taskSelect.attr('name') || $taskSelect.attr('id');

        // Sprawdź, czy to pole zadania zostało już zainicjalizowane
        if (taskSelectId && initializedTaskSelects[taskSelectId]) {
            console.log('[MISSION JS] Pole zadań już zainicjalizowane, pomijam:', taskSelectId);
            return;
        }

        if (!currentPostId) {
            console.log('[MISSION JS] Brak ID postu - nie można pobrać danych misji');
            return;
        }

        console.log('[MISSION JS] Wysyłanie zapytania o dane misji - ID postu:', currentPostId, 'ścieżka:', fieldPath);

        // Oznacz pole jako przetwarzane, aby uniknąć wielokrotnych wywołań
        if (taskSelectId) {
            initializedTaskSelects[taskSelectId] = true;
        }

        // Wykonaj zapytanie AJAX do pobrania zapisanych danych
        $.post(ajaxurl, {
            action: 'get_selected_mission_data',
            post_id: currentPostId,
            field_path: fieldPath
        }, function (response) {
            console.log('[MISSION JS] Odpowiedź z danymi misji:', response);

            if (response.success && response.data) {
                const missionData = response.data;

                // Ustaw wartość misji jeśli nie jest już ustawiona
                if (missionData.mission_id && !$missionSelect.val()) {
                    console.log('[MISSION JS] Ustawianie ID misji z zapisanych danych:', missionData.mission_id);
                    $missionSelect.val(missionData.mission_id).trigger('change');

                    // Znajdź pole statusu misji i ustaw wartość
                    const $missionStatusSelect = findRelatedField($missionSelect, 'mission_status');
                    if ($missionStatusSelect.length && missionData.mission_status) {
                        console.log('[MISSION JS] Ustawianie statusu misji:', missionData.mission_status);
                        $missionStatusSelect.val(missionData.mission_status).trigger('change');
                    }
                }

                // Jeśli mamy ID misji, załaduj zadania i ustaw zapisane zadanie
                if (missionData.mission_id && $taskSelect.length) {
                    const missionId = missionData.mission_id;
                    const savedTaskId = missionData.mission_task_id;

                    console.log('[MISSION JS] Ładowanie zadań dla misji:', missionId, 'z zapisanym zadaniem:', savedTaskId);

                    // Zapisz wartość zadania jako atrybut data aby nie zgubić jej podczas ładowania
                    if (savedTaskId) {
                        $taskSelect.data('selected', savedTaskId);
                    }

                    // Załaduj zadania - ale tylko raz dla danego pola
                    loadTasksIntoSelect(missionId, $taskSelect);

                    // Znajdź pole statusu zadania i ustaw wartość
                    if (missionData.mission_task_status && savedTaskId) {
                        const $taskStatusSelect = findRelatedField($taskSelect, 'mission_task_status');
                        if ($taskStatusSelect.length) {
                            console.log('[MISSION JS] Ustawianie statusu zadania:', missionData.mission_task_status);
                            setTimeout(function () {
                                $taskStatusSelect.val(missionData.mission_task_status).trigger('change');
                            }, 500); // Odczekaj chwilę na pojawienie się pola
                        }
                    }
                }
            }
        });
    }

    // Pomocnicza funkcja do znajdowania powiązanych pól (np. statusu)
    function findRelatedField($baseField, fieldName) {
        const $parent = $baseField.closest('.layout, .acf-fields, .acf-row');
        if (!$parent.length) return $();

        return $parent.find('[name*="' + fieldName + '"]');
    }

    function bindMissionSelects(context) {
        console.log('[MISSION JS] Bindowanie selectów w kontekście:', context);

        // Próba 1: Standardowe wyszukiwanie
        let $missionSelects = findMissionSelects(context);
        console.log('[MISSION JS] Znalezione standardowe pola misji:', $missionSelects.length);

        // Próba 2: Alternatywne wyszukiwanie jeśli nie znaleziono standardowo
        if ($missionSelects.length === 0) {
            const $altSelects = findMissionLikeSelects(context);
            console.log('[MISSION JS] Znalezione alternatywne pola misji:', $altSelects.length);
            $missionSelects = $missionSelects.add($altSelects);
        }

        // Podgląd znalezionych pól
        $missionSelects.each(function () {
            console.log('[MISSION JS] Pole misji:', {
                name: $(this).attr('name'),
                id: $(this).attr('id'),
                value: $(this).val()
            });
        });

        // Przypisanie handlerów zdarzeń
        $missionSelects.off('change.missionTasks').on('change.missionTasks', function () {
            console.log('[MISSION JS] Zmiana wartości pola misji:', $(this).val());

            const missionId = $(this).val();
            if (!missionId) return;

            // Znajdź powiązane pole zadań
            const $taskSelect = findTaskSelect(this);

            if ($taskSelect.length) {
                // Załaduj zadania do pola wyboru
                loadTasksIntoSelect(missionId, $taskSelect);
            }
        });

        // Dla każdego pola misji, spróbuj pobrać zapisane dane z anwser
        $missionSelects.each(function () {
            const $taskSelect = findTaskSelect(this);
            if ($taskSelect.length) {
                getSelectedMissionData($(this), $taskSelect);
            }
        });
    }

    function initializeTasksForSelectedMission() {
        console.log('[MISSION JS] Inicjalizacja zadań dla już wybranych misji');

        // Znajdź wszystkie pola misji
        const $missionSelects = $('select').filter(function () {
            const name = $(this).attr('name') || '';
            return (name.indexOf('mission_id') !== -1);
        });

        console.log('[MISSION JS] Znaleziono pól misji:', $missionSelects.length);

        // Dla każdego pola misji, znajdź powiązane pole zadań
        $missionSelects.each(function () {
            const $missionSelect = $(this);
            const $taskSelect = findTaskSelect(this);

            if ($taskSelect.length) {
                // Pobierz dane z tablicy anwser
                getSelectedMissionData($missionSelect, $taskSelect);

                // Jeśli misja jest już wybrana, załaduj zadania
                const missionId = $missionSelect.val();
                if (missionId) {
                    // Zachowaj aktualnie wybraną wartość zadania
                    const savedValue = $taskSelect.val();
                    console.log('[MISSION JS] Inicjalizacja zadań dla misji:', missionId, 'z zapisaną wartością:', savedValue);

                    // Załaduj opcje zadań
                    loadTasksIntoSelect(missionId, $taskSelect);
                }
            }
        });
    }

    $(function () {
        console.log('[MISSION JS] Document ready - inicjalizacja');
        bindMissionSelects(document);
        initializeTasksForSelectedMission();
    });

    // Obsługa ACF
    if (window.acf) {
        console.log('[MISSION JS] ACF wykryty, dodaję hooks');

        // Hook na dodanie nowego pola
        window.acf.addAction('append_field', function ($el) {
            console.log('[MISSION JS] Nowe pole dodane przez ACF');
            bindMissionSelects($el);
        });

        window.acf.addAction('ready', function () {
            console.log('[MISSION JS] ACF ready event');
        });

        // Zmienna do śledzenia ostatniego modala i zapobiegania wielokrotnym inicjalizacjom
        let lastModalInit = null;

        // Obsługa modali ACF Flexible Content
        $(document).on('click', '.acf-fc-layout-handle[data-action="acfe-flexible-modal-edit"]', function () {
            // Pobierz unikalny identyfikator dla tego layoutu
            const layoutId = $(this).closest('.layout').data('id') || '';

            // Zapobiegaj wielokrotnej inicjalizacji tego samego modala
            if (layoutId && layoutId === lastModalInit) {
                console.log('[MISSION JS] Pomijam ponowną inicjalizację modala:', layoutId);
                return;
            }

            // Zapamiętaj ten modal jako ostatnio inicjowany
            lastModalInit = layoutId;

            // Użyj dłuższego opóźnienia dla pewności, że modal zostanie w pełni wyrenderowany
            setTimeout(function () {
                const $modal = $('.acfe-modal-content:visible');

                if ($modal.length) {
                    console.log('[MISSION JS] Wykryto otwarty modal ACF, ID:', layoutId);

                    // Oznacz ten modal jako zainicjalizowany
                    $modal.data('mission-initialized', true);

                    // Binduj selecty w modalu
                    bindMissionSelects($modal);
                }
            }, 300); // Dłuższe opóźnienie dla pewności pełnego załadowania
        });

        // Obsługa dynamicznie dodawanych elementów Flexible Content
        window.acf.addAction('show', function ($el) {
            if ($el.hasClass('layout')) {
                console.log('[MISSION JS] Pokazano layout, szukam pól misji');
                bindMissionSelects($el);
            }
        });
    }
})(jQuery);
