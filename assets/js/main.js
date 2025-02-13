document.querySelectorAll('[title]').forEach(el => el.removeAttribute('title'));

class AjaxHelper {
    static sendRequest(url, method, data) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open(method, url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            // Dodaj ten nagłówek, aby PHP rozpoznało żądanie jako AJAX
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(response.data?.message || 'Unknown error');
                        }
                    } catch (error) {
                        reject('Invalid JSON response');
                    }
                } else {
                    reject(`HTTP error: ${xhr.status}`);
                }
            };

            xhr.onerror = () => reject('Request failed');

            const encodedData = new URLSearchParams(data).toString();
            xhr.send(encodedData);
        });
    }
}

async function createCustomPopup(params) {
    try {
        // Usuń istniejący popup, jeśli taki jest
        const existingPopup = document.querySelector('.popup-full');
        if (existingPopup) {
            existingPopup.remove();
        }

        // Wyślij żądanie AJAX, aby pobrać markup popupu
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

        // Dodaj nowy popup do body
        document.body.insertAdjacentHTML('beforeend', response.data.popup);

        // Po krótkim czasie dodaj klasę "active"
        setTimeout(() => {
            const newPopup = document.querySelector('.popup-full');
            if (newPopup) {
                newPopup.classList.add('active');

                // Dodanie obsługi zamknięcia popupu
                const closeButton = newPopup.querySelector('.popup-close');
                if (closeButton) {
                    closeButton.addEventListener('click', () => {
                        newPopup.classList.remove('active');

                        // Usunięcie popupu po krótkim czasie dla płynnej animacji
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

        // Aktualizacja pasków postępu – zakładamy, że każdy pasek ma klasę .bar-game
        // oraz atrybut data-bar-type, którego wartość odpowiada nazwie statystyki (np. "life")
        document.querySelectorAll('.bar-game').forEach(wrapper => {
            // Klucz statystyki oczekiwany w spłaszczonych danych to "stats-" + barType
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

        // Wyświetl popup z komunikatem – użyj customMsg, jeśli został podany, lub domyślnego komunikatu z backendu
        showPopup(customMsg || response.data.message, 'success');

        return response;
    } catch (error) {
        const errorMsg = error && error.message ? error.message : String(error);
        console.error("❌ Błąd aktualizacji bazy danych:", errorMsg);
        showPopup(errorMsg, 'error');
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


// async function createGroupWithCost(title, terenId, costFields) {
//     try {
//         const userFields = await fetchLatestACFFields();
//         if (userFields.przynaleznosc_do_grupy) {
//             throw new Error("Jesteś już przypisany do grupy. Nie możesz założyć nowej.");
//         }
//         const currentGold = userFields.minerals && userFields.minerals.gold ? parseFloat(userFields.minerals.gold) : 0;
//         if (currentGold < Math.abs(costFields["minerals.gold"])) {
//             throw new Error("Nie masz wystarczająco złota, aby pokryć koszt.");
//         }
//         // Najpierw pobierz hajsy – tylko gdy pobranie się uda, idziemy dalej
//         await updateACFFieldsWithGui(costFields, ['body'], "Koszt został pobrany.");
//         // Następnie tworzymy grupę
//         const groupResponse = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
//             action: 'create_group',
//             nonce: global.dataManagerNonce,
//             title: title,
//             teren_id: terenId,
//             request_id: Date.now() + Math.random().toString(36).substring(2, 9)
//         });
//         if (!groupResponse.success) {
//             throw new Error(groupResponse.data?.message || "Nieznany błąd serwera");
//         }
//         createCustomPopup({
//             imageId: 54,
//             header: "Grupa została utworzona!",
//             description: "Gratulacje! Twoja grupa została założona. Przejdź do niej, aby zobaczyć szczegóły.",
//             link: groupResponse.data.post_url,
//             linkLabel: "Przejdź do grupy",
//             status: "success",
//             closeable: false
//         });
//         return groupResponse;
//     } catch (error) {
//         // console.error("❌ Błąd przy tworzeniu grupy:", error);
//         showPopup(error.message || "Wystąpił błąd przy tworzeniu grupy", "error");
//         throw error;
//     }
// }
// window.createGroupWithCost = createGroupWithCost;



document.querySelectorAll('.bar-game').forEach(wrapper => {
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
        if (!title) return;

        const bbox = path.getBBox(); // Pobranie granic path w obrębie SVG
        const percentX = (bbox.x + bbox.width / 2) / svgWidth * 100;
        const percentY = (bbox.y - 20) / svgHeight * 100; // 20 jednostek nad ścieżką

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

function initSvgInteractions() {
    document.querySelectorAll('.container-world svg path').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            let selectType = el.getAttribute('data-select');

            if (selectType === "scena") {
                let target = el.getAttribute('data-target');
                if (target) {
                    window.location.href = target;
                }
            } else if (selectType === "npc") {
                const npcId = el.getAttribute('data-npc');
                if (!npcId) {
                    console.error("Brak atrybutu data-npc w elemencie");
                    return;
                }

                AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
                    action: 'get_npc_popup',
                    npc_id: npcId
                })
                    .then(response => {
                        const trimmedData = response.data.trim();
                        document.body.insertAdjacentHTML('beforeend', trimmedData);
                        // console.log("HTML po wstawieniu:", document.body.innerHTML);

                        setTimeout(() => {
                            const popup = document.getElementById('npc-popup');
                            if (!popup) {
                                console.error("Popup container nadal nie istnieje");
                                return;
                            }
                            initNpcPopup(parseInt(npcId, 10), 'npc-popup', true);
                        }, 500);
                    })
                    .catch(error => {
                        console.error('Błąd:', error);
                    });
            }
        });
    });
}

// Uruchomienie funkcji po załadowaniu DOM
document.addEventListener("DOMContentLoaded", initSvgInteractions);

function runFunctionNPC(functionsList) {
    console.log('runFunctionNPC', functionsList);
    console.log(typeof functionsList, Array.isArray(functionsList)); // Debugging

    // Jeśli dane są w postaci stringa JSON, parsujemy je
    if (typeof functionsList === "string") {
        try {
            functionsList = JSON.parse(functionsList);
        } catch (error) {
            console.error("Błąd parsowania JSON:", error);
            return;
        }
    }

    if (!Array.isArray(functionsList) || functionsList.length === 0) {
        console.error("Błąd: Nieprawidłowa tablica funkcji.");
        return;
    }

    functionsList.forEach(funcObj => {
        if (!funcObj.function_name || !funcObj.npc_id) {
            console.error("Błąd: Brak wymaganych danych w obiekcie funkcji.", funcObj);
            return;
        }

        // Zamiana nazwy funkcji z myślnikami na camelCase
        const functionName = funcObj.function_name.replace(/-([a-z])/g, g => g[1].toUpperCase());
        const npcId = funcObj.npc_id;

        if (typeof window[functionName] === "function") {
            window[functionName](npcId);
        } else {
            console.error(`Błąd: Funkcja "${functionName}" nie istnieje.`);
        }
    });
}


