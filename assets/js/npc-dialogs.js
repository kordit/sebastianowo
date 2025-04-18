// filepath: /Users/kordiansasiela/localhost/seb.soeasy.it/public_html/wp-content/themes/game/assets/js/npc-dialogs.js
/**
 * Obsługa dialogów NPC
 * Wyświetla dialogi nad głowami NPC w scenach SVG
 */
(function () {
    'use strict';

    // Obiekt przechowujący wszystkie dialogi dla bieżącej sceny
    let sceneDialogs = null;

    // Obiekt przechowujący aktywne dialogi, ich stan i interwały
    const activeDialogs = {};

    // Indeks obecnie wyświetlanego dialogu w rotacji
    let currentDialogIndex = 0;

    // Interwał rotacji dialogów
    let rotationInterval = null;

    const parts = window.location.pathname.split('/').filter(Boolean);

    // Używamy tylko ostatniego segmentu URL do domyślnego sluga (pociag, peron, itp.)
    // albo "powitanie" jeśli nie można ustalić z URL
    let defaultSlugFromUrl = 'powitanie';
    if (parts.length > 0) {
        defaultSlugFromUrl = parts[parts.length - 1];
    }

    console.log('Domyślny slug dialogu ustalony z URL:', defaultSlugFromUrl);

    // Domyślne ustawienia
    const settings = {
        intervalTime: 5000,  // Interwał zmiany dialogów (5 sekund)
        minDisplayTime: 1000, // Minimalny czas wyświetlania dialogu (3 sekundy)
        charTime: 50, // Czas na znak (0,1 sekundy)
        dialogClass: 'npc-dialog-bubble',
        defaultSlug: defaultSlugFromUrl,
        fadeTime: 300, // Czas trwania animacji fade (w ms)
        defaultMode: 'manual' // Domyślny tryb: 'manual' (ze strzałkami) lub 'auto' (automatyczny)
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
        // Pobierz identyfikator sceny z URL zamiast używać pierwszej dostępnej sceny
        const sceneId = getSceneIdFromUrl();
        console.log('Inicjalizacja dialogów dla sceny:', sceneId);

        // Sprawdź czy mamy dialogi dla tej sceny, jeśli nie, użyj sceny "main" jako zapasowej
        const currentScene = dialogs[sceneId] ? sceneId : Object.keys(dialogs)[0];
        console.log('Używana scena dla dialogów:', currentScene);

        if (!currentScene || !dialogs[currentScene]) {
            console.warn('Brak dialogów dla aktywnej sceny');
            return;
        }

        // Pokaż domyślne dialogi powitalne dla wszystkich NPC
        console.log('Próbuję znaleźć dialog dla sluga:', settings.defaultSlug, 'w scenie:', currentScene);
        console.log('Dostępne slugi w tej scenie:', Object.keys(dialogs[currentScene]));

        // Najpierw spróbuj użyć domyślnego sluga z ustawień
        if (dialogs[currentScene][settings.defaultSlug]) {
            console.log('Znaleziono dialog dla sluga:', settings.defaultSlug);
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
        // Jeśli nie znaleziono sluga z URL, spróbuj użyć 'powitanie' jako fallback
        else if (dialogs[currentScene]['powitanie']) {
            console.log('Nie znaleziono dialogu dla sluga:', settings.defaultSlug, ', używam domyślnego "powitanie"');
            const dialogData = dialogs[currentScene]['powitanie'];

            // Sprawdź nową strukturę danych
            if (dialogData.dialogi) {
                startDialogRotation(dialogData.dialogi, dialogData.koniec_dialogu);
            } else {
                startDialogRotation(dialogData);
            }
        }
        else {
            console.warn('Nie znaleziono żadnego dialogu dla sluga', settings.defaultSlug, 'ani dla "powitanie"');
        }
    }

    // Globalna zmienna przechowująca dialogi do rotacji
    let currentDialogs = [];
    let currentEndAction = null;

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
                hideDialog(dialogElement);
            }
            if (activeDialogs[npcId] && activeDialogs[npcId].timeout) {
                clearTimeout(activeDialogs[npcId].timeout);
            }
        });

        // Zapisz dialogi i akcję końcową w zmiennych globalnych
        currentDialogs = dialogs;
        currentEndAction = koniecDialogu;

        // Rozpocznij rotację od pierwszego dialogu
        if (dialogs && dialogs.length > 0) {
            currentDialogIndex = -1; // Zaczynamy od -1, bo showNextDialog zwiększy to do 0
            showNextDialog();
        }
    }

    /**
     * Funkcja do wyświetlania następnego dialogu
     */
    function showNextDialog() {
        const dialogs = currentDialogs;
        const koniecDialogu = currentEndAction;

        if (!dialogs || !dialogs.length) {
            return; // Brak dialogów do wyświetlenia
        }

        // Ukryj poprzedni dialog jeśli istnieje
        if (dialogs[currentDialogIndex]) {
            const prevDialog = dialogs[currentDialogIndex];
            const dialogElement = document.getElementById(`npc-dialog-${prevDialog.npc_id}`);
            if (dialogElement) {
                // Nie ukrywaj poprzedniego dialogu jeśli to ostatni dialog i akcja to 'stop'
                const isLastDialog = currentDialogIndex === dialogs.length - 1;
                const isStopAction = koniecDialogu && koniecDialogu.akcja === 'stop';

                if (!(isLastDialog && isStopAction)) {
                    hideDialog(dialogElement);
                }
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

        // Określ tryb wyświetlania dialogu - auto jeśli akcja to repeater lub auto, w przeciwnym razie manual
        const dialogMode = (koniecDialogu && (koniecDialogu.akcja === 'repeater' || koniecDialogu.akcja === 'auto')) ? 'auto' : settings.defaultMode;

        showNpcDialog(currentDialog.npc_id, currentDialog.message, null, dialogMode);

        // Jeśli tryb to auto, zaplanuj automatyczne przejście do następnego dialogu
        if (dialogMode === 'auto') {
            // Resetujemy istniejący rotationInterval dla pewności
            if (rotationInterval) {
                clearTimeout(rotationInterval);
            }

            // Określamy czas trwania na podstawie długości wiadomości
            const textLength = currentDialog.message.replace(/<[^>]*>/g, '').length; // Usuń tagi HTML przy liczeniu
            const displayDuration = settings.minDisplayTime + (textLength * settings.charTime);

            console.log('Auto przejście do następnego dialogu za', displayDuration, 'ms');

            // Ustawienie timera dla automatycznego przejścia
            rotationInterval = setTimeout(() => {
                showNextDialog();
            }, displayDuration);
        } else {
            // Dla trybu manual timer zostanie ustawiony po kliknięciu przycisku
            rotationInterval = null;
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

            case 'stop':
                // Zatrzymaj rotację ale zachowaj ostatni dialog na ekranie
                console.log('Akcja: Zatrzymaj na ostatnim dialogu');
                if (rotationInterval) {
                    clearInterval(rotationInterval);
                    rotationInterval = null;
                }
                // Nie ukrywamy dialogów - ostatni pozostaje widoczny
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
     * Pobiera identyfikator sceny z URL
     * @returns {string|null} Identyfikator sceny lub null, jeśli nie znaleziono
     */
    function getSceneIdFromUrl() {
        console.log('Próba pobrania identyfikatora sceny z URL...');

        // Pobierz pełny URL strony
        const url = window.location.pathname;

        // Rozdziel URL na segmenty
        const segments = url.split('/').filter(segment => segment.length > 0);

        // Sprawdź, czy mamy format /tereny/nazwa/scena/
        if (segments.length >= 3 && segments[0] === 'tereny') {
            // Ostatni niepusty segment to nazwa sceny
            const lastSegment = segments[segments.length - 1];
            if (lastSegment && lastSegment !== '') {
                console.log('Znaleziono identyfikator sceny w URL:', lastSegment);
                return lastSegment;
            }
        }

        // Jeśli nie znaleziono sceny, zwróć domyślną wartość 'main'
        console.log('Nie znaleziono identyfikatora sceny w URL, używam domyślnej wartości: main');
        return 'main';
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

        // Pobierz identyfikator sceny z URL
        const sceneId = getSceneIdFromUrl() || 'main';
        console.log('Pobrano identyfikator sceny z URL:', sceneId);

        // Utwórz obiekt FormData
        const formData = new FormData();
        formData.append('action', 'get_npc_dialogs');
        formData.append('post_id', postId);
        formData.append('scene_id', sceneId);
        formData.append('security', npcDialogsData.security);

        // Wykonaj zapytanie AJAX używając fetch API
        fetch(npcDialogsData.ajaxurl, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Błąd sieci podczas pobierania dialogów');
                }
                return response.json();
            })
            .then(response => {
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
            })
            .catch(error => {
                console.error('Błąd AJAX podczas pobierania dialogów:', error);
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

            // Pobierz atrybut d ścieżki
            const pathData = pathElement.getAttribute('d');

            // Jeśli nie ma atrybutu d, używamy domyślnej pozycji
            if (!pathData) {
                console.warn(`Ścieżka NPC ${npcId} nie ma atrybutu d - używamy domyślnej pozycji`);
                return;
            }

            // Parsowanie atrybutu d, aby wyodrębnić pierwszy punkt
            // Format d to zazwyczaj "M x,y ..." gdzie M to move to, a x,y to współrzędne
            const matches = pathData.match(/[Mm]\s*([+-]?\d*\.?\d+)[,\s]([+-]?\d*\.?\d+)/);

            if (!matches || matches.length < 3) {
                console.warn(`Nie udało się sparsować pierwszego punktu z atrybutu d dla NPC ${npcId}`);
                return;
            }

            // Pobierz współrzędne pierwszego punktu
            const firstX = parseFloat(matches[1]);
            const firstY = parseFloat(matches[2]);

            // Pobierz wymiary viewBox SVG
            const viewBox = svgElement.viewBox.baseVal;
            const svgWidth = viewBox.width;
            const svgHeight = viewBox.height;

            // Przelicz współrzędne na procenty
            const xPercent = (firstX / svgWidth) * 100;
            const yPercent = (firstY / svgHeight) * 100;

            // Utwórz element dymku
            const dialogElement = document.createElement('div');
            dialogElement.id = `npc-dialog-${npcId}`;
            dialogElement.className = settings.dialogClass;

            // Ustaw pozycję dymku używając procentów
            dialogElement.style.position = 'absolute';
            dialogElement.style.left = `${xPercent}%`;
            dialogElement.style.top = `${yPercent}%`;

            console.log(`Dymek dla NPC ${npcId} - pierwszy punkt: (${firstX}, ${firstY}), pozycja: ${xPercent}%, ${yPercent}%`);

            // Dodaj dymek do dokumentu (zaraz po SVG, a nie wewnątrz)
            svgElement.parentNode.insertBefore(dialogElement, svgElement.nextSibling);
        } catch (error) {
            console.error(`Błąd podczas tworzenia kontenera dla NPC ${npcId}:`, error);
        }
    }

    /**
     * Tworzy czerwoną kropkę na podstawie pierwszej wartości z atrybutu d ścieżki SVG
     * @param {Element} pathElement - Element ścieżki SVG
     * @param {string} npcId - ID NPC
     * @param {Element} svgElement - Element SVG nadrzędny
     * @param {Object} pathRect - Wymiary ścieżki
     * @param {Object} svgRect - Wymiary SVG
     * @param {number} scaleX - Współczynnik skalowania
     */
    function createRedDot(pathElement, npcId, svgElement, pathRect, svgRect, scaleX) {
        // Pobierz atrybut d ścieżki
        const pathData = pathElement.getAttribute('d');

        // Jeśli nie ma atrybutu d, używamy domyślnej pozycji
        if (!pathData) {
            console.warn(`Ścieżka NPC ${npcId} nie ma atrybutu d - używamy domyślnej pozycji`);
            return;
        }

        // Parsowanie atrybutu d, aby wyodrębnić pierwszy punkt
        // Format d to zazwyczaj "M x,y ..." gdzie M to move to, a x,y to współrzędne
        const matches = pathData.match(/[Mm]\s*([+-]?\d*\.?\d+)[,\s]([+-]?\d*\.?\d+)/);

        if (!matches || matches.length < 3) {
            console.warn(`Nie udało się sparsować pierwszego punktu z atrybutu d dla NPC ${npcId}`);
            return;
        }

        // Pobierz współrzędne pierwszego punktu
        const firstX = parseFloat(matches[1]);
        const firstY = parseFloat(matches[2]);

        // Pobierz wymiary viewBox SVG
        const viewBox = svgElement.viewBox.baseVal;
        const svgWidth = viewBox.width;
        const svgHeight = viewBox.height;

        // Przelicz współrzędne na procenty
        const xPercent = (firstX / svgWidth) * 100;
        const yPercent = (firstY / svgHeight) * 100;

        // Tworzenie elementu kropki
        const dotElement = document.createElement('div');
        dotElement.id = `npc-red-dot-${npcId}`;
        dotElement.className = 'npc-red-dot';

        // Stylowanie kropki używając procentów dla pozycji
        dotElement.style.position = 'absolute';
        dotElement.style.left = `${xPercent}%`;
        dotElement.style.top = `${yPercent}%`;
        dotElement.style.width = '10px';
        dotElement.style.height = '10px';
        dotElement.style.backgroundColor = 'red';
        dotElement.style.borderRadius = '50%';
        dotElement.style.zIndex = '1000';

        // Dodanie kropki bezpośrednio do SVG (a nie do rodzica)
        // zapewni to właściwe pozycjonowanie względem SVG
        console.log(`Kropka dla NPC ${npcId} - pierwszy punkt: (${firstX}, ${firstY}), pozycja: ${xPercent}%, ${yPercent}%`);

        // Dodanie kropki do dokumentu (jako dziecko SVG)
        svgElement.appendChild(dotElement);
    }

    /**
     * Wyświetla dialog dla danego NPC
     * @param {string|number} npcId - ID NPC
     * @param {string} message - Wiadomość do wyświetlenia
     * @param {number} [duration] - Czas wyświetlania w ms, automatycznie obliczany na podstawie długości tekstu
     * @param {string} [mode] - Tryb wyświetlania: 'auto' (automatyczny) lub 'manual' (ze strzałkami)
     */
    function showNpcDialog(npcId, message, duration = null, mode = settings.defaultMode) {
        const dialogElement = document.getElementById(`npc-dialog-${npcId}`);
        if (!dialogElement) {
            console.warn(`Nie znaleziono kontenera dialogowego dla NPC ${npcId}`);
            return;
        }

        // Zatrzymaj aktywny dialog dla tego NPC, jeśli istnieje
        if (activeDialogs[npcId] && activeDialogs[npcId].timeout) {
            clearTimeout(activeDialogs[npcId].timeout);
        }

        // Pobierz nazwę NPC z atrybutu data-npc-name na ścieżce SVG
        let npcName = "";
        const npcPath = document.querySelector(`svg path[data-npc="${npcId}"]`);
        if (npcPath) {
            npcName = npcPath.getAttribute('data-npc-name') || `NPC #${npcId}`;
        } else {
            npcName = `NPC #${npcId}`;
        }

        // Zastosuj efekt fade out przed zmianą treści
        function fadeOutAndUpdateContent() {
            return new Promise(resolve => {
                // Jeśli element jest ukryty, przejdź od razu do aktualizacji
                if (dialogElement.style.display === 'none') {
                    resolve();
                    return;
                }

                // Zastosuj animację fade out
                dialogElement.style.opacity = '1';
                dialogElement.style.transition = `opacity ${settings.fadeTime}ms ease`;
                dialogElement.style.opacity = '0';

                // Po zakończeniu animacji, przejdź do aktualizacji treści
                setTimeout(() => {
                    resolve();
                }, settings.fadeTime);
            });
        }

        // Aktualizuj zawartość i pokaż z efektem fade in
        function updateContentAndFadeIn() {
            // Wyczyść poprzednią zawartość
            dialogElement.innerHTML = '';

            // Dodaj nagłówek z imieniem NPC
            const nameDiv = document.createElement('div');
            nameDiv.className = 'npc-dialog-speaker';
            nameDiv.textContent = npcName;
            dialogElement.appendChild(nameDiv);

            // Utwórz element zawartości dialogu
            const contentDiv = document.createElement('div');
            contentDiv.className = 'npc-dialog-content';
            contentDiv.innerHTML = message;
            dialogElement.appendChild(contentDiv);

            // Dodaj przyciski nawigacji tylko, jeśli tryb to 'manual'
            // ale nie gdy jest to rotacja z repeaterem (rotationInterval !== null && akcja === 'repeater')
            const isRepeaterAction = currentEndAction && currentEndAction.akcja === 'repeater';

            if (mode === 'manual' && !isRepeaterAction) {
                const navContainer = document.createElement('div');
                navContainer.className = 'npc-dialog-navigation';

                const nextButton = document.createElement('button');
                nextButton.className = 'npc-dialog-next';
                nextButton.innerHTML = '&#10095;'; // Znak strzałki w prawo
                nextButton.setAttribute('aria-label', 'Następna wiadomość');

                // Dodaj obsługę kliknięcia przycisku
                nextButton.addEventListener('click', handleNextButtonClick);

                navContainer.appendChild(nextButton);
                dialogElement.appendChild(navContainer);
            }

            // Pokaż dymek z efektem fade in
            dialogElement.style.display = 'block';
            dialogElement.style.opacity = '0';

            // Uruchom fade in po krótkim opóźnieniu (aby przeglądarka miała czas na wyrenderowanie)
            setTimeout(() => {
                dialogElement.style.transition = `opacity ${settings.fadeTime}ms ease`;
                dialogElement.style.opacity = '1';
            }, 10);
        }

        // Oblicz czas wyświetlania na podstawie liczby znaków
        // Usuń tagi HTML, aby policzyć tylko tekst
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = message;
        const textLength = tempDiv.textContent.length;

        // Oblicz całkowity czas: minimalny czas + (liczba znaków * czas na znak)
        const calculatedDuration = settings.minDisplayTime + (textLength * settings.charTime);

        // Użyj obliczonego czasu lub podanego parametru duration
        const displayTime = duration || calculatedDuration;

        // Wykonaj animację i aktualizację treści
        fadeOutAndUpdateContent().then(() => {
            updateContentAndFadeIn();

            // W trybie 'auto' ustaw timer do automatycznego przejścia, w trybie 'manual' nie ustawiaj timera
            // dialogu powinien pozostać widoczny dopóki użytkownik nie kliknie strzałki
            if (mode === 'auto') {
                // Dla trybu auto zawsze ustawiamy timer
                activeDialogs[npcId] = {
                    message: message,
                    duration: displayTime,
                    timeout: setTimeout(() => {
                        if (rotationInterval !== null) {
                            handleNextButtonClick();
                        } else {
                            hideDialog(dialogElement);
                        }
                    }, displayTime),
                    mode: mode
                };
            } else {
                // Dla trybu manual nie ustawiamy timera - dialog pozostaje widoczny do kliknięcia
                activeDialogs[npcId] = {
                    message: message,
                    duration: displayTime,
                    timeout: null,
                    mode: mode
                };
            }
        });
    }

    /**
     * Funkcja ukrywająca dialog z efektem fade out
     * @param {HTMLElement} dialogElement - Element dymka dialogowego
     */
    function hideDialog(dialogElement) {
        dialogElement.style.transition = `opacity ${settings.fadeTime}ms ease`;
        dialogElement.style.opacity = '0';

        setTimeout(() => {
            dialogElement.style.display = 'none';
            // Usuwamy dialog z aktywnych dialogów
            const npcId = dialogElement.id.replace('npc-dialog-', '');
            delete activeDialogs[npcId];
        }, settings.fadeTime);
    }

    /**
     * Obsługuje kliknięcie przycisku "Dalej"
     */
    function handleNextButtonClick() {
        // Zatrzymaj istniejący timer
        if (rotationInterval) {
            clearTimeout(rotationInterval);
        }

        // Sprawdź czy to ostatni dialog w sekwencji
        if (currentDialogIndex === currentDialogs.length - 1) {
            // Jeśli to ostatni dialog, zamknij go (ukryj)
            const lastDialog = currentDialogs[currentDialogIndex];
            const dialogElement = document.getElementById(`npc-dialog-${lastDialog.npc_id}`);
            if (dialogElement) {
                hideDialog(dialogElement);
            }
            return;
        }

        // Pokaż następny dialog (jeśli nie jest to ostatni)
        showNextDialog();
    }

    // Uruchom inicjalizację po załadowaniu dokumentu
    document.addEventListener('DOMContentLoaded', function () {
        console.log('Inicjalizacja dialogów NPC...');
        initNpcDialogs();
    });

})();
