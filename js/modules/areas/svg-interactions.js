/**
 * Moduł obsługi interakcji SVG
 * 
 * Ten moduł zawiera funkcje do obsługi interaktywnych elementów SVG,
 * takich jak NPC, sceny, teleporty itp.
 */

/**
 * Inicjalizuje interakcje dla elementów SVG
 */
function initSvgInteractions() {
    const paths = document.querySelectorAll('.container-world svg path');
    if (!paths.length) {
        return;
    }

    // Funkcja do obsługi kliknięcia lub auto-startu
    const handlePathInteraction = (el, isAutoStart = false) => {
        const selectType = el.getAttribute('data-select');

        if (selectType === 'npc') {
            const npcId = el.getAttribute('data-npc-id');
            if (!npcId) {
                console.error("Brak atrybutu data-npc-id dla elementu.");
                return;
            }

            // Emituj zdarzenie npcClicked dla narzędzi deweloperskich
            const pageData = window.pageData || UIHelpers.getPageData();

            document.dispatchEvent(new CustomEvent('npcClicked', {
                detail: {
                    npcId: npcId,
                    pageData: pageData,
                    currentUrl: window.location.href
                }
            }));

            // Wywołaj endpoint NPC za pomocą Axios
            axios({
                method: 'POST',
                url: '/wp-json/game/v1/npc/popup',
                data: {
                    npc_id: npcId,
                    page_data: pageData,
                    current_url: window.location.href
                }
            })
                .then(response => {
                    const npcData = response?.data;
                    if (!npcData) {
                        console.error("Brak danych NPC w odpowiedzi:", response);
                        return;
                    }

                    const userId = npcData.npc_data?.user_id;
                    console.log("Dane NPC:", npcData);

                    if (window.buildNpcPopup && typeof window.buildNpcPopup === 'function') {
                        window.buildNpcPopup(npcData.npc_data, userId);
                    } else {
                        console.warn("Funkcja buildNpcPopup nie jest dostępna");
                    }
                })
                .catch(error => {
                    console.error("Błąd zapytania:", error);
                    // Dodaj bardziej szczegółowe logowanie błędów
                    if (error.response) {
                        // Serwer zwrócił status error
                        console.error("Status błędu:", error.response.status);
                        console.error("Dane błędu:", error.response.data);
                    } else if (error.request) {
                        // Żądanie zostało wykonane, ale brak odpowiedzi
                        console.error("Brak odpowiedzi z serwera");
                    } else {
                        // Coś poszło nie tak przy tworzeniu żądania
                        console.error("Błąd konfiguracji żądania:", error.message);
                    }
                });
        }
        else if (selectType === 'lootbox') {
            const lootboxId = el.getAttribute('data-lootbox');
            if (!lootboxId) {
                console.error("Brak atrybutu data-lootbox dla elementu.");
                return;
            }

            // Emituj zdarzenie lootboxClicked
            document.dispatchEvent(new CustomEvent('lootboxClicked', {
                detail: {
                    lootboxId: lootboxId,
                    currentUrl: window.location.href
                }
            }));

            // Dodaj nonce dla autoryzacji
            const restNonce = userManagerData?.nonce || '';

            // Bezpośrednio przeszukaj lootbox
            axios({
                method: 'POST',
                url: '/wp-json/game/v1/lootbox/search',
                headers: {
                    'X-WP-Nonce': restNonce,
                    'Content-Type': 'application/json'
                },
                data: {
                    lootbox_id: lootboxId
                }
            })
                .then(response => {
                    const data = response?.data;
                    console.log("Wyniki przeszukania:", data);

                    if (data.error) {
                        UIHelpers.showNotification(data.error, 'error');
                        return;
                    }

                    if (data.already_searched) {
                        UIHelpers.showNotification("Już przeszukałeś ten obiekt.", 'info');
                        return;
                    }

                    if (data.success && data.results) {
                        // Pokaż popup z wynikami
                        buildLootboxPopup(data);
                    }
                })
                .catch(error => {
                    console.error("Błąd zapytania:", error);
                    UIHelpers.showNotification("Wystąpił błąd podczas przeszukiwania.", 'error');
                });
        }
        else if (selectType === 'scena') {
            const target = el.getAttribute('data-target');
            if (!target) return;
            const container = document.querySelector('.container-world');
            if (container) {
                container.style.animation = 'fadeZoomBlur .5s ease-in forwards';
                setTimeout(() => {
                    window.location.href = target;
                }, 500);
            } else {
                window.location.href = target;
            }
        }
        else if (selectType === 'page') {
            const target = el.getAttribute('data-page');
            if (!target) return;
            const container = document.querySelector('.container-world');
            if (container) {
                container.style.animation = 'fadeZoomBlur 1s ease-in forwards';
                setTimeout(() => {
                    window.location.href = target;
                }, 500);
            } else {
                window.location.href = target;
            }
        }
    };

    // Dodaj obsługę kliknięć dla każdej ścieżki
    paths.forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            handlePathInteraction(el);
        });
    });

    // Sprawdź, czy jest jakaś ścieżka z atrybutem data-autostart="1" i automatycznie ją aktywuj
    setTimeout(() => {
        paths.forEach(el => {
            const autoStart = el.getAttribute('data-autostart');
            if (autoStart === "1") {
                handlePathInteraction(el, true);
                const svgElement = document.querySelector('.container-world svg');
                if (svgElement) {
                    svgElement.style.display = 'none';
                }
            }
        });
    }, 500); // Opóźnienie, aby strona mogła się w pełni załadować
}

/**
 * Dostosowuje elementy SVG w kontenerze
 */
function setupSvgContainer() {
    const container = document.querySelector('.container-world');
    if (!container) return;

    const svg = container.querySelector('svg');
    if (!svg) return;

    svg.setAttribute('preserveAspectRatio', 'xMidYMid slice');
    svg.style.width = '100%';
    svg.style.height = '100%';
    svg.style.display = 'block';
}

/**
 * Dodaje opisy do elementów SVG
 */
function addSvgTitles() {
    const svg = document.querySelector(".container-world svg");
    if (!svg) return;

    const svgWidth = svg.viewBox.baseVal.width;
    const svgHeight = svg.viewBox.baseVal.height;
    const paths = svg.querySelectorAll("path");

    paths.forEach(path => {
        const title = path.getAttribute("data-title");
        const color = path.getAttribute("data-color");
        path.style.fill = color;
        if (!title) return;

        const bbox = path.getBBox();
        const percentX = (bbox.x + bbox.width / 2) / svgWidth * 100;
        const percentY = (bbox.y + bbox.height + 15) / svgHeight * 100;

        // Tworzenie dynamicznego napisu, ukrytego domyślnie
        const text = document.createElement("div");
        text.textContent = title;
        text.style.position = "absolute";
        text.style.left = `${percentX}%`;
        text.style.top = `${percentY}%`;
        text.style.transform = "translate(-50%, -50%)";
        text.style.color = "#fff";
        text.style.fontSize = "14px";
        text.style.fontWeight = "700";
        text.style.background = "rgba(0,0,0,0.8)";
        text.style.padding = "4px 8px";
        text.style.borderRadius = "3px";
        text.style.whiteSpace = "nowrap";
        text.style.pointerEvents = "none";
        text.style.zIndex = "999";
        text.style.display = "none"; // Ukryte domyślnie

        svg.parentNode.appendChild(text);

        // Pokazanie napisu na hoverze
        path.addEventListener("mouseenter", () => {
            text.style.display = "block";
        });

        // Ukrycie napisu po opuszczeniu elementu
        path.addEventListener("mouseleave", () => {
            text.style.display = "none";
        });
    });
}

// Uruchom funkcje po załadowaniu DOM
document.addEventListener("DOMContentLoaded", () => {
    setupSvgContainer();
    addSvgTitles();
    initSvgInteractions();
});

// Eksport funkcji dla wstecznej kompatybilności
window.initSvgInteractions = initSvgInteractions;
