/**
 * Skrypt do dynamicznego ładowania zadań misji w warunkach startu rozmowy
 * Zaprojektowany do współpracy z polem "Zadanie" w warunkach startu rozmowy NPC
 */

// (function ($) {
//     console.log('[CONDITIONS] Skrypt warunków misji załadowany - jQuery Version: ' + $.fn.jquery);

//     // Sprawdź czy mamy dostęp do ACF
//     if (typeof acf === 'undefined') {
//         console.log('[CONDITIONS] ACF nie został wykryty');
//         return;
//     }

//     console.log('[CONDITIONS] ACF wykryty, dodaję hooks');

//     // Przechwytuj nowe pola dodawane przez ACF
//     acf.addAction('append', function ($el) {
//         console.log('[CONDITIONS] Nowy element dodany przez ACF');
//         bindMissionSelects($el);
//     });

//     // Inicjalizuj po załadowaniu dokumentu
//     $(document).ready(function () {
//         console.log('[CONDITIONS] Document ready - inicjalizacja bindings dla warunków');
//         bindMissionSelects($(document));

//         // Inicjalizacja zadań dla już wybranych misji po załadowaniu strony
//         initExistingMissionTasks();
//     });

//     // Przechwytuj nowe pola dodawane przez ACF
//     acf.addAction('ready', function () {
//         console.log('[CONDITIONS] ACF ready event dla warunków');
//         // Dodatkowa inicjalizacja po załadowaniu ACF
//     });

//     /**
//      * Binduje wszystkie selecty misji w podanym kontekście
//      */
//     function bindMissionSelects($context) {
//         console.log('[CONDITIONS] Bindowanie selectów misji w kontekście:', $context);

//         // Znajdź wszystkie pola typu post_object o nazwie 'mission' w kontekście
//         var $missionFields = $context.find('.acf-field[data-name="mission"]');
//         console.log('[CONDITIONS] Znalezione pola misji:', $missionFields.length);

//         // Dla każdego znalezionego pola misji
//         $missionFields.each(function () {
//             var $missionField = $(this);
//             var $missionSelect = $missionField.find('select');

//             if ($missionSelect.length) {
//                 console.log('[CONDITIONS] Znaleziono select misji:', $missionSelect.attr('id'));

//                 // Usuń istniejące eventy, aby uniknąć duplikacji
//                 $missionSelect.off('change.mission_tasks');

//                 // Dodaj obsługę zmiany wartości selecta
//                 $missionSelect.on('change.mission_tasks', function () {
//                     var missionId = $(this).val();
//                     console.log('[CONDITIONS] Zmieniono misję na ID:', missionId);

//                     // Znajdź odpowiednie pole zadań - najpierw szukamy w rodzicu (grupie)
//                     var $container = $missionField.closest('.acf-fields');
//                     var $taskField = $container.find('.acf-field[data-name="mission_task_id"]');
//                     var $taskSelect = $taskField.find('select');

//                     if (!$taskSelect.length) {
//                         console.log('[CONDITIONS] Nie znaleziono pola zadań w grupie');
//                         return;
//                     }

//                     // Wyczyść obecne opcje
//                     $taskSelect.empty();
//                     $taskSelect.append($('<option>', {
//                         value: '',
//                         text: 'Ładowanie zadań...'
//                     }));

//                     // Wykonaj zapytanie AJAX, aby pobrać zadania dla wybranej misji
//                     $.post(ajaxurl, {
//                         action: 'get_mission_tasks',
//                         mission_id: missionId
//                     }, function (response) {
//                         console.log('[CONDITIONS] Otrzymano zadania:', response);

//                         // Wyczyść pole i dodaj placeholder
//                         $taskSelect.empty();
//                         $taskSelect.append($('<option>', {
//                             value: '',
//                             text: 'Wybierz zadanie z misji'
//                         }));

//                         // Dodaj otrzymane opcje
//                         if (response.success && response.data && response.data.tasks) {
//                             $.each(response.data.tasks, function (taskId, taskTitle) {
//                                 $taskSelect.append($('<option>', {
//                                     value: taskId,
//                                     text: taskTitle
//                                 }));
//                             });

//                             // Jeśli była wybrana wartość, przywróć ją
//                             if (response.data.selected_task) {
//                                 $taskSelect.val(response.data.selected_task);
//                             }
//                         }
//                     });
//                 });

//                 // Zapisz ID misji do późniejszego wykorzystania
//                 $missionSelect.data('mission-id', $missionSelect.val());
//             }
//         });
//     }

//     /**
//      * Inicjalizuje zadania dla już wybranych misji po załadowaniu strony
//      */
//     function initExistingMissionTasks() {
//         console.log('[CONDITIONS] Inicjalizacja zadań dla wybranych misji po załadowaniu strony');

//         // Znajdź wszystkie pola wyboru misji, które mają już wybraną wartość
//         $('.acf-field[data-name="mission"] select').each(function () {
//             var $missionSelect = $(this);
//             var missionId = $missionSelect.val();

//             if (missionId) {
//                 console.log('[CONDITIONS] Znaleziono misję z ID:', missionId);
//                 $missionSelect.trigger('change.mission_tasks');
//             }
//         });
//     }

//     // Dodatkowo monitoruj zmiany w drzewie DOM dla przypadków, gdy ACF dodaje nowe pola dynamicznie
//     if (typeof MutationObserver !== 'undefined') {
//         var observer = new MutationObserver(function (mutations) {
//             mutations.forEach(function (mutation) {
//                 if (mutation.addedNodes && mutation.addedNodes.length > 0) {
//                     for (var i = 0; i < mutation.addedNodes.length; i++) {
//                         var node = mutation.addedNodes[i];
//                         if (node.nodeType === 1) { // Element node
//                             bindMissionSelects($(node));
//                         }
//                     }
//                 }
//             });
//         });

//         // Obserwuj zmiany w całym dokumencie
//         observer.observe(document.body, {
//             childList: true,
//             subtree: true
//         });

//         console.log('[CONDITIONS] MutationObserver uruchomiony');
//     }

// })(jQuery);
