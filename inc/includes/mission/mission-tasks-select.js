(function ($) {
    console.log('[MISSION JS] Skrypt załadowany - jQuery Version:', $.fn.jquery);

    // Funkcja pomocnicza do ładowania zadań do selecta
    function loadTasksIntoSelect(missionId, $taskSelect) {
        console.log('[MISSION JS] Ładowanie zadań dla misji ID:', missionId, 'do selecta:', $taskSelect);

        $taskSelect.find('option').remove();
        // ZAWSZE dodaj placeholder jako pierwszą opcję
        $taskSelect.append($('<option>', { value: '', text: 'Najpierw wybierz zadanie z misji' }));

        if (!missionId) {
            console.log('[MISSION JS] Brak ID misji');
            return;
        }

        console.log('[MISSION JS] Wysyłanie zapytania AJAX o zadania');
        // Pobierz aktualną wartość przed usunięciem opcji
        const currentSelectedValue = $taskSelect.val();
        console.log('[MISSION JS] Aktualna wartość przed AJAX:', currentSelectedValue);

        // Pobierz ID postu (jeśli jesteśmy na stronie edycji)
        const currentPostId = $('#post_ID').val() || 0;

        // Pobierz ścieżkę pola (nazwę bez "acf")
        const fieldPath = $taskSelect.attr('name')
            ? $taskSelect.attr('name').replace('acf', '').replace(/\]\[/g, '][')
            : '';

        console.log('[MISSION JS] ID postu:', currentPostId, 'Ścieżka pola:', fieldPath);

        $.post(ajaxurl, {
            action: 'get_mission_tasks',
            mission_id: missionId,
            selected_task: currentSelectedValue, // Przekaż aktualnie wybraną wartość
            post_id: currentPostId, // Przekaż ID aktualnego postu
            field_path: fieldPath // Przekaż ścieżkę pola
        }, function (response) {
            console.log('[MISSION JS] Odpowiedź AJAX:', response);

            if (response.success && response.data && response.data.tasks && Object.keys(response.data.tasks).length) {
                $.each(response.data.tasks, function (id, title) {
                    console.log('[MISSION JS] Dodaję zadanie:', id, title);
                    // Ustaw atrybut selected jeśli to wybrana wartość
                    const isSelected = (id === response.data.selected_task);
                    if (isSelected) {
                        console.log('[MISSION JS] Znaleziono wybraną wartość:', id);
                        $taskSelect.append($('<option>', {
                            value: id,
                            text: title,
                            selected: 'selected'
                        }));
                    } else {
                        $taskSelect.append($('<option>', { value: id, text: title }));
                    }
                });
            } else {
                $taskSelect.append($('<option>', { value: '', text: 'Brak dostępnych zadań' }));
            }

            // Odświeżenie standardowego selecta - taka operacja nie jest wymagana dla zwykłego HTML selecta,
            // ale zostawiamy trigger change, aby obsłużyć inne potencjalne listenery
            try {
                console.log('[MISSION JS] Aktualizacja selecta zakończona');
                $taskSelect.trigger('change');
            } catch (e) {
                console.log('[MISSION JS] Błąd podczas aktualizacji selecta:', e);
            }
        });
    }

    function bindMissionSelects(context) {
        console.log('[MISSION JS] Bindowanie selectów w kontekście:', context);

        const $missionSelects = $(context).find('[name$="[mission_id]"]');
        console.log('[MISSION JS] Znalezione selecty misji:', $missionSelects.length);

        $missionSelects.off('change.missionTasks').on('change.missionTasks', function () {
            console.log('[MISSION JS] ==== ZMIANA SELECTA MISJI ====');
            const missionId = $(this).val();
            const missionName = $(this).attr('name');
            console.log('[MISSION JS] Mission ID:', missionId, 'Name:', missionName);

            // Wyciągnij prefix do rowa
            const rowPrefix = missionName.replace(/\[mission_id\]$/, '');
            console.log('[MISSION JS] Wyciągnięty prefix:', rowPrefix);

            // Znajdź select zadania z tym samym prefixem
            const $taskSelect = $('[name="' + rowPrefix + '[mission_task_id]"]');
            console.log('[MISSION JS] Znaleziony select zadań:', $taskSelect.length);

            if (!$taskSelect.length) {
                console.log('[MISSION JS] BŁĄD: Nie znaleziono selecta mission_task_id!');
                return;
            }

            // Załaduj zadania do selecta
            loadTasksIntoSelect(missionId, $taskSelect);
        });
    }

    // Funkcja do sprawdzania i załadowania zadań dla aktualnie wybranej misji
    function initializeTasksForSelectedMission() {
        console.log('[MISSION JS] Inicjalizacja zadań dla wybranych misji po załadowaniu strony');

        // Sprawdź wszystkie selecty misji na stronie
        $('[name$="[mission_id]"]').each(function () {
            const $missionSelect = $(this);
            const missionId = $missionSelect.val();
            const missionName = $missionSelect.attr('name');

            if (missionId) {
                console.log('[MISSION JS] Znaleziono wybraną misję:', missionId, 'w selekcie:', missionName);

                // Wyciągnij prefix
                const rowPrefix = missionName.replace(/\[mission_id\]$/, '');

                // Znajdź select zadania z tym samym prefixem
                const $taskSelect = $('[name="' + rowPrefix + '[mission_task_id]"]');

                if ($taskSelect.length) {
                    // Zachowaj aktualną wartość przed ładowaniem nowych opcji
                    const savedValue = $taskSelect.val();
                    console.log('[MISSION JS] Zapisana wartość zadania:', savedValue);

                    // Załaduj zadania dla tej misji (jeśli jest wybrana)
                    loadTasksIntoSelect(missionId, $taskSelect);

                    // Przywróć zapisaną wartość po załadowaniu zadań
                    if (savedValue) {
                        setTimeout(function () {
                            $taskSelect.val(savedValue);
                            console.log('[MISSION JS] Przywrócono zapisaną wartość zadania:', savedValue);
                        }, 1000);
                    }
                }
            }
        });
    }

    $(function () {
        console.log('[MISSION JS] Document ready - inicjalizacja bindings');
        bindMissionSelects(document);
        // Dodane: inicjalizacja zadań dla już wybranych misji
        initializeTasksForSelectedMission();
    });

    // Obsługa dynamicznego dodawania przez ACF
    if (window.acf) {
        console.log('[MISSION JS] ACF wykryty, dodaję hooks');

        window.acf.addAction('append_field', function ($el) {
            console.log('[MISSION JS] Nowe pole dodane przez ACF');
            bindMissionSelects($el);
        });

        window.acf.addAction('ready', function () {
            console.log('[MISSION JS] ACF ready event');
        });

        // Obsługa modali ACF Flexible Content
        $(document).on('click', '.acf-fc-layout-handle[data-action="acfe-flexible-modal-edit"]', function () {
            console.log('[MISSION JS] Kliknięto w handle modalu');

            // Daj czas modalowi na pojawienie się w DOM
            setTimeout(function () {
                console.log('[MISSION JS] Sprawdzanie modalu po setTimeout');
                const $modal = $('.acfe-modal-content:visible');

                if ($modal.length) {
                    console.log('[MISSION JS] Wykryto otwarty modal');

                    // Znajdź wszystkie selecty misji w modalu
                    const $missionSelects = $modal.find('[name$="[field_mission_id]"]');
                    console.log('[MISSION JS] Selecty misji w modalu:', $missionSelects.length);

                    // Dla każdego selecta misji
                    if ($missionSelects.length > 0) {
                        $missionSelects.each(function () {
                            const $missionSelect = $(this);
                            const missionId = $missionSelect.val();
                            const missionName = $missionSelect.attr('name');

                            // Znajdź odpowiedni select zadania
                            const taskName = missionName.replace('[field_mission_id]', '[field_mission_task_id]');
                            // Ważne: wybieramy tylko element select, nie input
                            const $taskSelect = $modal.find('select[name="' + taskName + '"]');

                            console.log('[MISSION JS] Znaleziony select misji:', missionName, 'z wartością:', missionId);
                            console.log('[MISSION JS] Znaleziony select zadania:', $taskSelect.length);

                            // Jeśli wybrano misję i znaleziono select zadań
                            if ($taskSelect.length) {
                                // Zachowaj aktualną wartość zadania
                                const currentTaskValue = $taskSelect.val();
                                console.log('[MISSION JS] Aktualna wartość zadania:', currentTaskValue);

                                // Od razu załaduj zadania jeśli misja jest wybrana
                                if (missionId) {
                                    loadTasksIntoSelect(missionId, $taskSelect);

                                    // Po załadowaniu zadań, przywróć wybraną wartość
                                    if (currentTaskValue) {
                                        // Używamy setTimeout aby dać czas na załadowanie opcji przez AJAX
                                        setTimeout(function () {
                                            $taskSelect.val(currentTaskValue);
                                            console.log('[MISSION JS] Przywrócono zapisaną wartość zadania w modalu:', currentTaskValue);

                                            // Sprawdzenie czy udało się ustawić wartość
                                            if ($taskSelect.val() !== currentTaskValue) {
                                                console.log('[MISSION JS] Uwaga: Nie udało się przywrócić wartości zadania w modalu');
                                            }
                                        }, 1000); // Dłuższy czas na odpowiedź AJAX
                                    }
                                }

                                // Dodaj event na zmianę misji
                                $missionSelect.off('change.modalTasks').on('change.modalTasks', function () {
                                    const missionId = $(this).val();
                                    console.log('[MISSION JS] Zmiana misji w modalu na:', missionId);
                                    loadTasksIntoSelect(missionId, $taskSelect);
                                });
                            }
                        });
                    }
                }
            }, 50);
        });
    } else {
        console.log('[MISSION JS] ACF nie jest dostępny!');
    }
})(jQuery);