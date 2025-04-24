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
        $.post(ajaxurl, { action: 'get_mission_tasks', mission_id: missionId }, function (response) {
            console.log('[MISSION JS] Odpowiedź AJAX:', response);

            if (response.success && response.data && response.data.tasks && Object.keys(response.data.tasks).length) {
                $.each(response.data.tasks, function (id, title) {
                    console.log('[MISSION JS] Dodaję zadanie:', id, title);
                    $taskSelect.append($('<option>', { value: id, text: title }));
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

    $(function () {
        console.log('[MISSION JS] Document ready - inicjalizacja bindings');
        bindMissionSelects(document);
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
                                            // Sprawdź czy wybrana wartość istnieje wśród opcji
                                            if ($taskSelect.find('option[value="' + currentTaskValue + '"]').length > 0) {
                                                $taskSelect.val(currentTaskValue);
                                                console.log('[MISSION JS] Przywrócono wybraną wartość zadania:', currentTaskValue);
                                            }
                                        }, 500); // Poczekaj 500ms na odpowiedź AJAX
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
            }, 500);
        });
    } else {
        console.log('[MISSION JS] ACF nie jest dostępny!');
    }
})(jQuery);