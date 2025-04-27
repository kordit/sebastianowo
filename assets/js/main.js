document.querySelectorAll('[title]').forEach(el => el.removeAttribute('title'));

async function createCustomPopup(params) {
    try {
        const existingPopup = document.querySelector('.popup-full');
        if (existingPopup) {
            existingPopup.remove();
        }
        const response = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
            action: 'create_custom_popup',
            nonce: global.dataManagerNonce,
            image_id: params.imageId,
            header: params.header,
            description: params.description,
            link: params.link,
            linkLabel: params.linkLabel,
            status: params.status,
            closeable: params.closeable ? 'true' : 'false'
        });

        if (!response.success) {
            throw new Error(response.data?.message || "Nieznany błąd serwera");
        }

        document.body.insertAdjacentHTML('beforeend', response.data.popup);
        setTimeout(() => {
            const newPopup = document.querySelector('.popup-full');
            if (newPopup) {
                newPopup.classList.add('active');
                const closeButton = newPopup.querySelector('.popup-close');
                if (closeButton) {
                    closeButton.addEventListener('click', () => {
                        newPopup.classList.remove('active');

                        setTimeout(() => {
                            newPopup.remove();
                        }, 300);
                    });
                }
            }
        }, 100);
    } catch (error) {
        console.error("❌ Błąd przy tworzeniu popupu:", error);
    }
}

// Rejestracja w `window`, aby była dostępna globalnie
window.createCustomPopup = createCustomPopup;

function showPopup(message, type = 'success') {
    const existingPopup = document.querySelector('.popup');
    if (existingPopup) existingPopup.remove();

    const popup = document.createElement('div');
    popup.className = `popup popup-${type}`;
    popup.innerHTML = `
        <div class="popup-content">
            <div class="popup-message">${message}</div>
            <button class="popup-close">X</button>
        </div>
    `;

    document.body.appendChild(popup);

    function closePopup() {
        popup.remove();
        document.removeEventListener('keydown', escHandler);
    }

    popup.querySelector('.popup-close').addEventListener('click', closePopup);

    function escHandler(event) {
        if (event.key === 'Escape') {
            closePopup();
        }
    }
    document.addEventListener('keydown', escHandler);
}


async function fetchLatestACFFields() {
    try {
        const response = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
            action: 'get_acf_fields',
            nonce: global.dataManagerNonce
        });
        if (!response.success) {
            throw new Error(response || "Nieznany błąd serwera");
        }
        return response.data.fields;
    } catch (error) {
        console.error("❌ Błąd pobierania bazy:", error);
        return {};
    }
}

async function updatePostACFFields(postId, fields) {
    return AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
        action: 'update_acf_post_fields_reusable',
        nonce: global.dataManagerNonce,
        post_id: postId,
        fields: JSON.stringify(fields),
        request_id: Date.now() + Math.random().toString(36).substring(2, 9)
    });
}
window.updatePostACFFields = updatePostACFFields;


async function updateACFFields(fields) {
    try {
        const response = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
            action: 'update_acf_fields',
            nonce: global.dataManagerNonce,
            fields: JSON.stringify(fields),
            request_id: Date.now() + Math.random().toString(36).substring(2, 9)
        });
        if (!response.success) {
            throw new Error(response.data?.message || "Nieznany błąd serwera");
        }
        return response;
    } catch (error) {
        // const errorMsg = error && error.message ? error.message : String(error);
        throw error;
    }
}
window.updateACFFields = updateACFFields;

async function updateACFFieldsWithGui(fields, parentSelectors = ['body'], customMsg = null) {
    try {
        // Wywołaj czystą funkcję aktualizacji danych
        const response = await updateACFFields(fields);

        // Pobierz najnowsze dane ACF i spłaszcz je
        const freshData = await fetchLatestACFFields();
        const flatData = flattenData(freshData);

        // Aktualizacja standardowych elementów (np. elementów z klasą .ud-*)
        parentSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(parent => {
                Object.entries(flatData).forEach(([key, value]) => {
                    parent.querySelectorAll(`.ud-${key}`).forEach(el => {
                        el.textContent = value;
                    });
                });
            });
        });

        document.querySelectorAll('.bar-game').forEach(wrapper => {
            const statKey = 'stats-' + (wrapper.dataset.barType || '');
            if (flatData.hasOwnProperty(statKey)) {
                const newCurrent = parseFloat(flatData[statKey]);
                const max = parseFloat(wrapper.dataset.barMax);
                const percentage = (newCurrent / max) * 100;
                // Aktualizacja szerokości paska
                const bar = wrapper.querySelector('.bar');
                if (bar) {
                    bar.style.width = percentage + '%';
                }
                // Aktualizacja wartości wyświetlanej obok paska
                const barValueSpan = wrapper.querySelector('.bar-value span');
                if (barValueSpan) {
                    barValueSpan.textContent = newCurrent;
                }
                // Zaktualizuj atrybut data-bar-current dla synchronizacji
                wrapper.dataset.barCurrent = newCurrent;
            }
        });

        return response;
    } catch (error) {
        const errorMsg = error && error.message ? error.message : String(error);
        console.error("❌ Błąd aktualizacji bazy danych:", errorMsg);
        // showPopup(errorMsg, 'error');
        throw error;
    }
}
window.updateACFFieldsWithGui = updateACFFieldsWithGui;


function flattenData(data, prefix = '') {
    let flat = {};
    for (let key in data) {
        if (data.hasOwnProperty(key)) {
            const newKey = prefix ? `${prefix}-${key}` : key;
            if (data[key] !== null && typeof data[key] === 'object' && !Array.isArray(data[key])) {
                Object.assign(flat, flattenData(data[key], newKey));
            } else {
                flat[newKey] = data[key];
            }
        }
    }
    return flat;
}

document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('.container-world');
    if (!container) return;

    const svg = container.querySelector('svg');
    if (!svg) return;

    svg.setAttribute('preserveAspectRatio', 'xMidYMid slice');
    svg.style.width = '100%';
    svg.style.height = '100%';
    svg.style.display = 'block';
});



document.querySelectorAll('.bar-game').forEach(wrapper => {
    wrapper.innerHTML = '';

    const max = parseFloat(wrapper.dataset.barMax);
    const current = parseFloat(wrapper.dataset.barCurrent);
    const color = wrapper.dataset.barColor || '#4caf50';
    const type = wrapper.dataset.barType || 'default';
    const percentage = (current / max) * 100;

    const bar = document.createElement('div');
    bar.classList.add('bar');
    bar.style.width = percentage + '%';
    bar.style.background = color;

    const barValue = document.createElement('div');
    barValue.classList.add('bar-value');
    barValue.innerHTML = `<span class="ud-stats-${type}">${current}</span> / ${max}`;

    wrapper.appendChild(bar);
    wrapper.appendChild(barValue);
});


async function createCustomPost(title, postType, acfFields) {
    try {
        const response = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
            action: 'create_custom_post',
            nonce: global.dataManagerNonce,
            title: title,
            post_type: postType,
            acf_fields: JSON.stringify(acfFields),
            request_id: Date.now() + Math.random().toString(36).substring(2, 9)
        });
        if (!response.success) {
            throw new Error(response.data?.message || "Nieznany błąd serwera");
        }
        return response;
    } catch (error) {
        console.error("❌ Błąd przy tworzeniu wpisu:", error);
        throw error;
    }
}
window.createCustomPost = createCustomPost;


document.addEventListener("DOMContentLoaded", function () {
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

        const bbox = path.getBBox(); // Pobranie granic path w obrębie SVG
        const percentX = (bbox.x + bbox.width / 2) / svgWidth * 100;
        const percentY = (bbox.y + bbox.height + 15) / svgHeight * 100; // 5 jednostek pod ścieżką


        // Tworzenie dynamicznego napisu, ale ukrytego domyślnie
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
});

function getPageData() {
    const body = document.body;
    let pageData = {};

    // Pobierz aktualny URL i segmenty ścieżki
    const url = new URL(window.location.href);
    const pathSegments = url.pathname.split('/').filter(segment => segment); // Usunięcie pustych wartości
    const segmentCount = pathSegments.length;

    let lastSegment = pathSegments[segmentCount - 1] || ''; // Ostatni segment (domyślnie)

    // 1️⃣ Jeśli body ma klasę zaczynającą się od 'template-', to jest to 'instance' (zwraca normalnie)
    const templateClass = [...body.classList].find(cls => cls.startsWith('template-'));
    if (templateClass) {
        pageData = {
            TypePage: 'instance',
            value: lastSegment // ✅ Pobranie ostatniego segmentu URL
        };
    }
    // 2️⃣ Jeśli body ma klasę 'single', to jest to 'scene' (musi zwracać kolejną logikę)
    else if (body.classList.contains('single')) {
        if (segmentCount === 2) {
            pageData = {
                TypePage: 'scena',
                value: `${pathSegments[1]}/main` // ✅ Format: "kolejowa/main"
            };
        } else if (segmentCount >= 3) {
            pageData = {
                TypePage: 'scena',
                value: `${pathSegments[1]}/${lastSegment}` // ✅ Format: "kolejowa/klatka"
            };
        }
    }

    return pageData;
}

// Bezpieczna deklaracja pageData - nie spowoduje błędu przy wielokrotnym załadowaniu skryptu
window.pageData = window.pageData || getPageData();

function initSvgInteractions() {
    const paths = document.querySelectorAll('.container-world svg path');

    // Funkcja do obsługi kliknięcia lub auto-startu
    const handlePathInteraction = (el, isAutoStart = false) => {
        const selectType = el.getAttribute('data-select');

        if (selectType === 'npc') {
            const npcId = el.getAttribute('data-npc');
            if (!npcId) {
                console.error("No data-npc attribute found on element.");
                return;
            }

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

// Uruchomienie funkcji po załadowaniu DOM
document.addEventListener("DOMContentLoaded", initSvgInteractions);



