/**
 * Obsługa dialogów NPC
 * Wyświetla dialogi nad głowami NPC w scenach SVG
 */
(function ($) {
    'use strict';

    // Obiekt przechowujący wszystkie dialogi dla bieżącej sceny
    let sceneDialogs = null;

    // Obiekt przechowujący aktywne dialogi, ich stan i interwały
    const activeDialogs = {};

    // Indeks obecnie wyświetlanego dialogu w rotacji
    let currentDialogIndex = 0;

    // Interwał rotacji dialogów
    let rotationInterval = null;

    // Domyślne ustawienia
    const settings = {
        intervalTime: 5000,  // Interwał zmiany dialogów (5 sekund)
        minDisplayTime: 1000, // Minimalny czas wyświetlania dialogu (3 sekundy)
        charTime: 50, // Czas na znak (0,1 sekundy)
        dialogClass: 'npc-dialog-bubble',
        defaultSlug: 'powitanie'
    };

    /**
     * Inicjalizacja dialogów dla wszystkich NPC w SVG
     */
    function initNpcDialogs() {
        // Znajdź wszystkie ścieżki SVG z atrybutem data-npc
        const npcPaths = document.querySelectorAll('svg path[data-npc]');
        console.log('Znalezione ścieżki NPC:', npcPaths.length, npcPaths);

        if (!npcPaths.length) {
            console.warn('Nie znaleziono ścieżek NPC w dokumencie');
            return;
        }

        // Pobierz ID strony z klasy body
        const postId = getPostIdFromBody();
        console.log('Pobrane ID podstrony:', postId);

        if (!postId) {
            console.warn('Nie można określić ID podstrony z klasy body');
            return;
        }

        // Zbierz wszystkie ID NPC, aby pobrać dialogi jednym zapytaniem
        const npcIds = Array.from(npcPaths).map(path => path.getAttribute('data-npc')).filter(id => id);

        // Inicjalizuj kontenery dla dymków dialogowych
        npcPaths.forEach(path => {
            const npcId = path.getAttribute('data-npc');
            if (!npcId) return;
            createDialogContainer(path, npcId);
        });

        // Pobierz wszystkie dialogi dla podstrony przez AJAX
        loadDialogsFromPostId(postId, function (dialogs) {
            console.log('Pobrane dialogi:', dialogs);
            // Tu można zainicjalizować dialogi, np. pokazać powitalne
            initializeDialogs(npcPaths, dialogs);
        });
    }

    /**
     * Inicjalizuje dialogi na podstawie pobranych danych
     * @param {NodeList} npcPaths - Lista ścieżek NPC w SVG
     * @param {Object} dialogs - Pobrane dialogi
     */
    function initializeDialogs(npcPaths, dialogs) {
        // Znajdź aktualną scenę (możesz dostosować tę logikę do swojej struktury)
        const currentScene = Object.keys(dialogs)[0]; // Używamy pierwszej sceny jako domyślnej

        if (!currentScene || !dialogs[currentScene]) {
            console.warn('Brak dialogów dla aktywnej sceny');
            return;
        }

        // Pokaż domyślne dialogi powitalne dla wszystkich NPC
        if (dialogs[currentScene][settings.defaultSlug]) {
            const dialogData = dialogs[currentScene][settings.defaultSlug];

            // Sprawdź nową strukturę danych - teraz mamy obiekt z dialogami i akcją końcową
            if (dialogData.dialogi) {
                // Rozpocznij rotację dialogów z uwzględnieniem akcji końcowej
                startDialogRotation(dialogData.dialogi, dialogData.koniec_dialogu);
            } else {
                // Kompatybilność wsteczna - stara struktura
                startDialogRotation(dialogData);
            }
        }
    }

    /**
     * Rozpoczyna rotację dialogów pomiędzy NPC
     * @param {Array} dialogs - Lista dialogów do rotacji
     * @param {Object} koniecDialogu - Obiekt zawierający informacje o akcji po zakończeniu dialogu
     */
    function startDialogRotation(dialogs, koniecDialogu = null) {
        // Zatrzymaj istniejącą rotację, jeśli istnieje
        if (rotationInterval) {
            clearTimeout(rotationInterval);
            rotationInterval = null;
            currentDialogIndex = 0;
        }

        // Schowaj wszystkie aktywne dialogi
        Object.keys(activeDialogs).forEach(npcId => {
            const dialogElement = document.getElementById(`npc-dialog-${npcId}`);
            if (dialogElement) {
                dialogElement.style.display = 'none';
            }
            if (activeDialogs[npcId] && activeDialogs[npcId].timeout) {
                clearTimeout(activeDialogs[npcId].timeout);
            }
        });

        // Funkcja do wyświetlania następnego dialogu
        function showNextDialog() {
            // Ukryj poprzedni dialog jeśli istnieje
            if (dialogs[currentDialogIndex]) {
                const prevDialog = dialogs[currentDialogIndex];
                const dialogElement = document.getElementById(`npc-dialog-${prevDialog.npc_id}`);
                if (dialogElement) {
                    dialogElement.style.display = 'none';
                }
            }

            // Przejdź do następnego dialogu
            currentDialogIndex++;

            // Sprawdź czy to ostatni dialog
            if (currentDialogIndex >= dialogs.length) {
                if (koniecDialogu) {
                    // Jeśli mamy akcję końcową, wykonaj ją
                    handleDialogEndAction(koniecDialogu);
                    return;
                } else {
                    // Bez akcji końcowej, wracamy do początku
                    currentDialogIndex = 0;

                    // Jeśli to nie typ 'repeater', kończymy rotację
                    if (!koniecDialogu || koniecDialogu.akcja !== 'repeater') {
                        rotationInterval = null;
                        return;
                    }
                }
            }

            // Pokaż aktualny dialog
            const currentDialog = dialogs[currentDialogIndex];
            showNpcDialog(currentDialog.npc_id, currentDialog.message);

            // Zaplanuj pokazanie następnego dialogu po czasie zależnym od długości tekstu
            rotationInterval = setTimeout(() => {
                showNextDialog();
            }, activeDialogs[currentDialog.npc_id].duration);
        }

        // Rozpocznij rotację od pierwszego dialogu
        if (dialogs && dialogs.length > 0) {
            currentDialogIndex = -1; // Zaczynamy od -1, bo showNextDialog zwiększy to do 0
            showNextDialog();
        }
    }

    /**
     * Obsługuje akcje po zakończeniu wyświetlania wszystkich dialogów
     * @param {Object} koniecDialogu - Obiekt zawierający informacje o akcji po zakończeniu dialogu
     */
    function handleDialogEndAction(koniecDialogu) {
        console.log('Wykonywanie akcji po zakończeniu dialogu:', koniecDialogu);

        if (!koniecDialogu || !koniecDialogu.akcja) {
            return; // Brak akcji do wykonania
        }

        // Wykonaj odpowiednią akcję w zależności od typu
        switch (koniecDialogu.akcja) {
            case 'nic':
                // Zatrzymaj rotację i zakończ dialog
                console.log('Akcja: Nic nie rób - zakończ dialog');
                if (rotationInterval) {
                    clearInterval(rotationInterval);
                    rotationInterval = null;
                }

                // Ukryj wszystkie dialogi
                Object.keys(activeDialogs).forEach(npcId => {
                    const dialogElement = document.getElementById(`npc-dialog-${npcId}`);
                    if (dialogElement) {
                        dialogElement.style.display = 'none';
                    }
                });
                break;

            case 'repeater':
                // Kontynuuj rotację dialogów - nie przerywaj
                console.log('Akcja: Powtarzaj dialogi');
                // Nie robimy nic, rotacja będzie kontynuowana
                return;

            case 'otworz_chat':
                // Otwórz chat z wybranym NPC
                if (koniecDialogu.npc_id) {
                    console.log('Akcja: Otwórz chat z NPC ID:', koniecDialogu.npc_id);

                    // Zatrzymaj rotację
                    if (rotationInterval) {
                        clearInterval(rotationInterval);
                        rotationInterval = null;
                    }

                    // Ukryj wszystkie dialogi
                    Object.keys(activeDialogs).forEach(npcId => {
                        const dialogElement = document.getElementById(`npc-dialog-${npcId}`);
                        if (dialogElement) {
                            dialogElement.style.display = 'none';
                        }
                    });

                    // Użyj AjaxHelper do pobrania danych NPC i wyświetlenia okna czatu
                    const npcId = koniecDialogu.npc_id;
                    const pageData = getPageData(); // Zakładamy, że ta funkcja istnieje w globalnej przestrzeni

                    if (typeof AjaxHelper !== 'undefined' && AjaxHelper.sendRequest) {
                        AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                            action: 'get_npc_popup',
                            npc_id: npcId,
                            page_id: JSON.stringify(pageData),
                            current_url: window.location.href
                        })
                            .then(response => {
                                const npcData = response?.data?.npc_data;
                                if (!npcData) {
                                    console.error("No npc_data in the AJAX response:", response);
                                    return;
                                }
                                const userId = npcData.user_id;
                                buildNpcPopup(npcData, userId);
                                console.log(pageData);
                            })
                            .catch(error => {
                                console.log(error);
                                console.error("AJAX request error:", error);
                            });
                    } else {
                        console.error('AjaxHelper not available for opening NPC chat');
                    }
                }
                break;

            case 'misja':
                // Uruchom funkcję misji z parametrami
                if (koniecDialogu.funkcja) {
                    console.log('Akcja: Uruchom funkcję misji:', koniecDialogu.funkcja, 'z parametrami:', koniecDialogu.parametry);

                    // Przygotuj parametry funkcji - przekazujemy je jako obiekt
                    const params = koniecDialogu.parametry || {};

                    // Sprawdź czy funkcja istnieje w globalnym scope
                    const functionName = koniecDialogu.funkcja.replace(/-([a-z])/g, g => g[1].toUpperCase());

                    if (typeof window[functionName] === 'function') {
                        // Wywołaj funkcję z parametrami
                        window[functionName](params);
                    } else if (typeof window.runFunctionNPC === 'function') {
                        // Alternatywnie użyj istniejącej funkcji runFunctionNPC, jeśli istnieje
                        const functionData = {
                            function_name: koniecDialogu.funkcja,
                            npc_id: params.npc_id || null
                        };
                        window.runFunctionNPC([functionData]);
                    } else {
                        console.error(`Funkcja ${functionName} nie istnieje`);
                    }
                }
                break;

            default:
                console.warn('Nieznany typ akcji po zakończeniu dialogu:', koniecDialogu.akcja);
        }
    }

    /**
     * Pobiera ID podstrony z klasy body 
     * @returns {number} ID podstrony
     */
    function getPostIdFromBody() {
        console.log('Próba pobrania ID podstrony z klasy body...');

        // Próba pobrania z klasy postid-XXX na body
        const bodyClasses = document.body.className;
        const postIdMatch = bodyClasses.match(/postid-(\d+)/);

        if (postIdMatch && postIdMatch[1]) {
            const postId = parseInt(postIdMatch[1], 10);
            console.log('Znaleziono ID podstrony z klasy body:', postId);
            return postId;
        }

        console.log('Nie znaleziono ID podstrony w klasie body');
        return null;
    }

    /**
     * Pobiera dialogi za pomocą AJAX na podstawie ID podstrony
     * @param {number} postId - ID podstrony
     * @param {Function} callback - Funkcja callback do wywołania po pobraniu dialogów
     */
    function loadDialogsFromPostId(postId, callback) {
        console.log('Pobieranie dialogów dla ID podstrony:', postId);

        if (!postId) {
            console.error('Nie podano ID podstrony');
            return;
        }

        // Sprawdź czy mamy dostęp do danych AJAX
        if (!npcDialogsData || !npcDialogsData.ajaxurl) {
            console.error('Brak danych AJAX dla dialogów NPC');
            return;
        }

        // Wykonaj zapytanie AJAX
        $.ajax({
            url: npcDialogsData.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_npc_dialogs',
                post_id: postId,
                security: npcDialogsData.security
            },
            success: function (response) {
                console.log('Odpowiedź z AJAX dla dialogów:', response);

                if (response.success && response.data && response.data.dialogs) {
                    // Przekaż dane dialogów do funkcji callback
                    if (typeof callback === 'function') {
                        callback(response.data.dialogs);
                    }

                    // Zapisz dane dialogów w globalnej zmiennej
                    sceneDialogs = response.data.dialogs;
                } else {
                    console.error('Błąd podczas pobierania dialogów:',
                        response.data ? response.data.message : 'Nieznany błąd');
                }
            },
            error: function (xhr, status, error) {
                console.error('Błąd AJAX podczas pobierania dialogów:', error);
            }
        });
    }

    /**
     * Tworzy kontener dla dymku dialogowego
     * @param {Element} pathElement - Element ścieżki SVG
     * @param {string} npcId - ID NPC
     */
    function createDialogContainer(pathElement, npcId) {
        console.log(`Tworzenie kontenera dialogowego dla NPC: ${npcId}`);

        // Sprawdź czy kontener już istnieje
        if (document.getElementById(`npc-dialog-${npcId}`)) {
            console.log(`Kontener dla NPC: ${npcId} już istnieje`);
            return;
        }

        try {
            // Pobierz pozycję ścieżki SVG względem viewportu
            const svgElement = pathElement.closest('svg');
            const pathRect = pathElement.getBBox();
            const svgRect = svgElement.getBoundingClientRect();

            // Oblicz środek ścieżki w koordynatach viewportu
            const centerX = pathRect.x + (pathRect.width / 2);
            const centerY = pathRect.y - 20; // Umieść dymek nad NPC

            // Utwórz element dymku
            const dialogElement = document.createElement('div');
            dialogElement.id = `npc-dialog-${npcId}`;
            dialogElement.className = settings.dialogClass;
            dialogElement.style.position = 'absolute';
            dialogElement.style.left = `${centerX}px`;
            dialogElement.style.top = `${centerY}px`;
            dialogElement.style.transform = 'translate(-50%, -100%)'; // Wyśrodkuj i umieść nad NPC
            dialogElement.style.display = 'none'; // Początkowo ukryty

            // Dodaj dymek do dokumentu (jako dziecko SVG lub innego kontenera)
            svgElement.parentNode.appendChild(dialogElement);
        } catch (error) {
            console.error(`Błąd podczas tworzenia kontenera dla NPC ${npcId}:`, error);
        }
    }

    /**
     * Wyświetla dialog dla danego NPC
     * @param {string|number} npcId - ID NPC
     * @param {string} message - Wiadomość do wyświetlenia
     * @param {number} [duration] - Czas wyświetlania w ms, automatycznie obliczany na podstawie długości tekstu
     */
    function showNpcDialog(npcId, message, duration = null) {
        const dialogElement = document.getElementById(`npc-dialog-${npcId}`);
        if (!dialogElement) {
            console.warn(`Nie znaleziono kontenera dialogowego dla NPC ${npcId}`);
            return;
        }

        // Zatrzymaj aktywny dialog dla tego NPC, jeśli istnieje
        if (activeDialogs[npcId] && activeDialogs[npcId].timeout) {
            clearTimeout(activeDialogs[npcId].timeout);
        }

        // Utwórz element zawartości dialogu
        const contentDiv = document.createElement('div');
        contentDiv.className = 'npc-dialog-content';
        contentDiv.innerHTML = message;

        // Wyczyść poprzednią zawartość i dodaj nową
        dialogElement.innerHTML = '';
        dialogElement.appendChild(contentDiv);
        dialogElement.style.display = 'block';

        // Oblicz czas wyświetlania na podstawie liczby znaków
        // Usuń tagi HTML, aby policzyć tylko tekst
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = message;
        const textLength = tempDiv.textContent.length;

        // Oblicz całkowity czas: minimalny czas + (liczba znaków * czas na znak)
        const calculatedDuration = settings.minDisplayTime + (textLength * settings.charTime);

        // Użyj obliczonego czasu lub podanego parametru duration
        const displayTime = duration || calculatedDuration;

        // W przypadku pojedynczego dialogu (nie w rotacji) ustaw timer do ukrycia dymka
        if (rotationInterval === null) {
            activeDialogs[npcId] = {
                message: message,
                duration: displayTime,
                timeout: setTimeout(() => {
                    dialogElement.style.display = 'none';
                    delete activeDialogs[npcId];
                }, displayTime)
            };
        } else {
            // Dla dialogów w rotacji, zapisujemy stan bez timera
            activeDialogs[npcId] = {
                message: message,
                duration: displayTime,
                timeout: null
            };
        }
    }

    // Uruchom inicjalizację po załadowaniu dokumentu
    $(document).ready(function () {
        console.log('Inicjalizacja dialogów NPC...');
        initNpcDialogs();
    });

})(jQuery);
