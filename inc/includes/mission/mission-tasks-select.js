document.body.classList.contains('post-type-npc') && (() => {

    (function ($) {
        // Skrypt obsługi dynamicznego ładowania zadań misji
        console.log('[MISSION JS] Inicjalizacja skryptu');

        // Funkcja inicjująca nasłuchiwanie zdarzeń
        function initMissionTasksSelect() {
            // Nasłuchuj zmiany we wszystkich polach wyboru misji
            $(document).on('change', 'select[name*="mission_id"]', function () {
                const $missionSelect = $(this);
                const missionId = $missionSelect.val();

                console.log('[MISSION JS] Zmieniono wybór misji na ID:', missionId);

                if (!missionId) return;

                // Znajdź powiązany select zadań w tym samym kontenerze
                const $container = $missionSelect.closest('.acf-fields, .layout, .acf-row, .acf-clone');
                const $taskSelect = $container.find('select[name*="mission_task_id"]');

                if (!$taskSelect.length) {
                    console.log('[MISSION JS] Nie znaleziono pola zadań dla wybranej misji');
                    return;
                }

                console.log('[MISSION JS] Znaleziono pole zadań:', $taskSelect.attr('name'));

                // Wyczyść select i pokaż placeholder
                $taskSelect.empty().append($('<option>', { value: '', text: 'Ładowanie zadań...' }));

                // Zbierz dane kontekstowe dla zapytania AJAX
                const postId = $('#post_ID').val() || 0;
                const selectedValue = $taskSelect.val() || $taskSelect.data('selected');
                const fieldPath = $missionSelect.attr('name') || '';

                // Spróbuj określić indeks wiersza z nazwy pola
                let contentIndex = null;
                const rowMatch = fieldPath.match(/\[row-(\d+)\]/);
                if (rowMatch && rowMatch[1]) {
                    contentIndex = rowMatch[1];
                }

                console.log('[MISSION JS] Pobieranie zadań z kontekstem - Post ID:', postId, 'Field Path:', fieldPath, 'Content Index:', contentIndex);

                // Wykonaj zapytanie fetch do pobrania zadań z uwzględnieniem kontekstu
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'get_mission_tasks',
                        mission_id: missionId,
                        post_id: postId,
                        field_path: fieldPath,
                        content_index: contentIndex,
                        selected_task: selectedValue || ''
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('[MISSION JS] Otrzymano odpowiedź:', data);

                        // Wyczyść select i dodaj placeholder
                        $taskSelect.empty().append($('<option>', { value: '', text: 'Wybierz zadanie z misji' }));

                        // Obsługa nowego formatu danych z obiektami zadań zawierającymi title i selected
                        let tasks = null;
                        let selectedTaskId = null;

                        // Sprawdź czy odpowiedź ma format data.data[mission_id].tasks
                        if (data.success && data.data && data.data[missionId] && data.data[missionId].tasks) {
                            tasks = data.data[missionId].tasks;
                            console.log('[MISSION JS] Znaleziono zadania w formacie data.data[missionId].tasks');
                        }
                        // Sprawdź czy odpowiedź ma format data.data.tasks
                        else if (data.success && data.data && data.data.tasks) {
                            tasks = data.data.tasks;
                            console.log('[MISSION JS] Znaleziono zadania w formacie data.data.tasks');
                        }

                        // Dodaj zadania do selecta jeśli je znaleziono
                        if (tasks && Object.keys(tasks).length > 0) {
                            // Najpierw znajdź zadanie oznaczone jako selected
                            Object.entries(tasks).forEach(([id, taskData]) => {
                                // Sprawdź czy mamy obiekt z title i selected, czy stary format
                                if (typeof taskData === 'object' && taskData.title) {
                                    if (taskData.selected) {
                                        selectedTaskId = id;
                                        console.log('[MISSION JS] Znaleziono wybrane zadanie:', id);
                                    }

                                    // Dodaj opcję do selecta
                                    $taskSelect.append($('<option>', {
                                        value: id,
                                        text: taskData.title
                                    }));
                                } else {
                                    // Starszy format gdzie wartością jest bezpośrednio tytuł
                                    $taskSelect.append($('<option>', {
                                        value: id,
                                        text: taskData
                                    }));
                                }
                            });

                            // Ustaw wybrane zadanie (jeśli znaleziono)
                            if (selectedTaskId) {
                                $taskSelect.val(selectedTaskId);
                                console.log('[MISSION JS] Ustawiam wybrane zadanie:', selectedTaskId);
                            }
                            // Sprawdź czy w odpowiedzi jest osobny selected_task
                            else if (data.data.selected_task) {
                                $taskSelect.val(data.data.selected_task);
                                console.log('[MISSION JS] Ustawiam zadanie z data.data.selected_task:', data.data.selected_task);
                            }
                        } else {
                            // Brak zadań
                            $taskSelect.append($('<option>', {
                                value: '',
                                text: 'Brak dostępnych zadań dla tej misji'
                            }));
                            console.log('[MISSION JS] Nie znaleziono zadań w odpowiedzi');
                        }

                        // Wywołaj zdarzenie zmiany, aby zaktualizować UI
                        $taskSelect.trigger('change');
                    })
                    .catch(error => {
                        console.error('[MISSION JS] Błąd podczas pobierania zadań:', error);
                        $taskSelect.empty()
                            .append($('<option>', { value: '', text: 'Błąd pobierania zadań' }));
                    });
            });
        }

        // Funkcja inicjująca pola w modalach ACF
        function initModals() {
            $(document).on('click', '.acf-fc-layout-handle[data-action="acfe-flexible-modal-edit"]', function () {
                console.log('[MISSION JS] Otwieranie modala');

                // Poczekaj na załadowanie modala
                setTimeout(function () {
                    const $modal = $('.acfe-modal-content:visible');

                    if (!$modal.length) return;

                    console.log('[MISSION JS] Modal otwarty, inicjalizacja pól');

                    // Znajdź wszystkie wybrane misje i załaduj ich zadania
                    $modal.find('select[name*="mission_id"]').each(function () {
                        const missionId = $(this).val();
                        const modalId = $modal.attr('id') || 'nieznane_id';

                        console.log('[MISSION JS] W modalu', modalId, 'znaleziono misję:', missionId || 'nie wybrano');

                        // Jeśli misja jest już wybrana, wywołaj zdarzenie change aby załadować zadania
                        if (missionId) {
                            $(this).trigger('change');
                        }
                    });
                }, 300);
            });
        }

        // Inicjalizacja po załadowaniu strony
        $(function () {
            initMissionTasksSelect();
            initModals();

            // Dla istniejących misji na stronie
            $('select[name*="mission_id"]').each(function () {
                const missionId = $(this).val();
                if (missionId) {
                    console.log('[MISSION JS] Znaleziono już wybraną misję:', missionId);
                    $(this).trigger('change');
                }
            });
        });

        // Integracja z ACF
        if (window.acf) {
            // Hook dla nowych pól ACF
            window.acf.addAction('append_field', function ($el) {
                // Sprawdź czy nowe pole zawiera pola misji
                const $missionSelects = $el.find('select[name*="mission_id"]');
                if ($missionSelects.length) {
                    console.log('[MISSION JS] Dodano nowe pole wyboru misji');
                    $missionSelects.each(function () {
                        const missionId = $(this).val();
                        if (missionId) {
                            $(this).trigger('change');
                        }
                    });
                }
            });
        }
    })(jQuery);

})();