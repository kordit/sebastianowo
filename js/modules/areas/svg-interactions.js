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
                console.error("Brak atrybutu data-npc dla elementu.");
                return;
            }

            // Emituj zdarzenie npcClicked dla narzędzi deweloperskich
            const pageData = window.pageData || UIHelpers.getPageData();
            console.log(JSON.stringify(pageData));

            document.dispatchEvent(new CustomEvent('npcClicked', {
                detail: {
                    npcId: npcId,
                    pageData: pageData,
                    currentUrl: window.location.href
                }
            }));

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
                        console.error("Brak danych NPC w odpowiedzi AJAX:", response);
                        return;
                    }
                    const userId = npcData.user_id;
                    console.log("Dane NPC:", npcData);
                    // buildNpcPopup(npcData, userId);
                })
                .catch(error => {
                    console.error("Błąd zapytania:", error);
                    if (error.response && error.response.status === 401) {
                        console.warn("Użytkownik nie jest zalogowany. W trybie deweloperskim (WP_DEBUG) wciąż możesz testować funkcjonalność.");
                        
                        // Sprawdź czy mamy obsługę powiadomień
                        if (window.NotificationSystem) {
                            window.NotificationSystem.showNotification({
                                type: 'warning',
                                message: 'Funkcja dostępna tylko dla zalogowanych użytkowników',
                                duration: 5000
                            });
                        }
                    }
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
