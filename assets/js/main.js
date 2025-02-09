class AjaxHelper {
    static sendRequest(url, method, data) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open(method, url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

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
        const existingPopup = document.querySelector('.popup');
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
        document.body.insertAdjacentHTML('beforeend', response.data.popup);
        // Po 0.1 sekundy dodaj klasę "active" do nowego popupu
        setTimeout(() => {
            const newPopup = document.querySelector('.popup-full');
            if (newPopup) {
                newPopup.classList.add('active');
            }
        }, 100);
    } catch (error) {
        console.error("❌ Błąd przy tworzeniu popupu:", error);
    }
}
window.createCustomPopup = createCustomPopup;



function showPopup(message, type = 'success') {
    const existingPopup = document.querySelector('.popup');
    if (existingPopup) existingPopup.remove();

    const popup = document.createElement('div');
    popup.className = `popup popup-${type}`;
    popup.innerHTML = `
        <div class="popup-content">
            <div class="popup-message">${message}</div>
            <button class="popup-close">Zamknij</button>
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
        const errorMsg = error && error.message ? error.message : String(error);
        // console.error("❌ Błąd aktualizacji bazy danych:", errorMsg);
        throw error; // <-- Dodajemy rethrow błędu
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


async function createGroupWithCost(title, terenId, costFields) {
    try {
        // Najpierw pobierz najnowsze pola użytkownika
        const userFields = await fetchLatestACFFields();
        // Sprawdź, czy użytkownik już ma przypisaną grupę – używamy pola 'przynaleznosc_do_grupy' (dla użytkownika)
        if (userFields.przynaleznosc_do_grupy) {
            throw new Error("Jesteś już przypisany do grupy. Nie możesz założyć nowej.");
        }
        // Sprawdź, czy użytkownik ma wystarczająco złota, aby pokryć koszt
        const currentGold = userFields.minerals && userFields.minerals.gold ? parseFloat(userFields.minerals.gold) : 0;
        if (currentGold < Math.abs(costFields["minerals.gold"])) {
            throw new Error("Nie masz wystarczająco złota, aby pokryć koszt.");
        }
        // Wywołaj endpoint tworzenia grupy
        const groupResponse = await AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
            action: 'create_group',
            nonce: global.dataManagerNonce,
            title: title,
            teren_id: terenId,
            request_id: Date.now() + Math.random().toString(36).substring(2, 9)
        });
        if (!groupResponse.success) {
            throw new Error(groupResponse.data?.message || "Nieznany błąd serwera");
        }
        // Jeśli tworzenie grupy się powiodło, odejmij koszty oraz zaktualizuj interfejs
        await updateACFFieldsWithGui(costFields, ['body'], "Koszt został pobrany.");
        // Wyświetl popup z sukcesem i linkiem do nowo utworzonej grupy
        createCustomPopup({
            imageId: 54, // dopasuj ID obrazka do swoich potrzeb
            header: "Grupa została utworzona!",
            description: "Gratulacje! Twoja grupa została założona. Przejdź do niej, aby zobaczyć szczegóły.",
            link: groupResponse.data.post_url,
            linkLabel: "Przejdź do grupy",
            status: "success",
            closeable: false
        });
        return groupResponse;
    } catch (error) {
        console.error("❌ Błąd przy tworzeniu grupy:", error);
        showPopup(error.message || "Wystąpił błąd przy tworzeniu grupy", "error");
        throw error;
    }
}
window.createGroupWithCost = createGroupWithCost;


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



// document.addEventListener('DOMContentLoaded', () => {
//     const leaveButton = document.querySelector('#leave-village-button');

//     if (leaveButton) {
//         leaveButton.addEventListener('click', (e) => {
//             e.preventDefault(); // Zatrzymaj domyślną akcję
//             e.stopPropagation(); // Zatrzymaj propagację zdarzenia

//             const userId = e.target.dataset.userId;
//             const postId = e.target.dataset.postId;
//             const fields = ['the_villagers', 'applications', 'leader']; // Pola do odłączenia

//             relationHandler
//                 .disconnectRelation(userId, postId, fields)
//                 .then((response) => {
//                     showPopup(response.message || 'Relacje zostały pomyślnie odłączone!', 'success');
//                     console.log('Relacje usunięte:', response); // Debugging
//                 })
//                 .catch((error) => {
//                     showPopup(error || 'Wystąpił błąd podczas odłączania relacji.', 'error');
//                     console.error('Błąd:', error); // Debugging
//                 });
//         });
//     }
//     const Applybutton = document.querySelector('#apply-to-village-button');
//     if (Applybutton) {
//         Applybutton.addEventListener('click', (e) => {
//             e.preventDefault();

//             const postId = e.target.dataset.postId;

//             if (!postId) {
//                 showPopup('Nie znaleziono ID wioski.', 'error');
//                 return;
//             }

//             // Wyślij żądanie AJAX do aplikowania do wioski
//             AjaxHelper.sendRequest(DMVars.ajaxurl, 'POST', {
//                 action: 'apply_to_village',
//                 post_id: postId,
//             })
//                 .then((response) => {
//                     showPopup(response.message || 'Aplikacja została pomyślnie złożona!', 'success');
//                 })
//                 .catch((error) => {
//                     showPopup(error || 'Wystąpił błąd podczas aplikowania do wioski.', 'error');
//                 });
//         });
//     }
// });


// document.addEventListener('DOMContentLoaded', () => {
//     const section = document.querySelector('#applicants-section');
//     const postId = section ? section.dataset.postId : null;

//     if (!postId) {
//         console.error('Brak ID wioski w #applicants-section');
//         return;
//     }

//     const loadApplicants = () => {
//         AjaxHelper.sendRequest(DMVars.ajaxurl, 'POST', {
//             action: 'get_applicants',
//             post_id: postId,
//         })
//             .then((response) => {
//                 const tbody = document.querySelector('#applicants-table tbody');
//                 tbody.innerHTML = '';

//                 // Sprawdź, czy aplikanci istnieją
//                 if (Array.isArray(response.data.applicants) && response.data.applicants.length > 0) {
//                     const currentVillagers = response.data.villagers || 0; // Liczba obecnych mieszkańców
//                     const maxVillagers = 5; // Maksymalna liczba mieszkańców
//                     const disableAccept = currentVillagers >= maxVillagers;

//                     response.data.applicants.forEach((applicant) => {
//                         const row = document.createElement('tr');
//                         row.innerHTML = `
//                             <td>${applicant.display_name}</td>
//                             <td>${applicant.user_email}</td>
//                             <td>
//                                 <button class="accept-btn btn btn-green" data-applicant-id="${applicant.ID}" ${disableAccept ? 'disabled' : ''}>Akceptuj</button>
//                                 <button class="reject-btn btn btn-red" data-applicant-id="${applicant.ID}">Odrzuć</button>
//                             </td>
//                         `;
//                         tbody.appendChild(row);
//                     });

//                     addApplicantActionHandlers();
//                 } else {
//                     console.log('Brak aplikantów.');
//                     const row = document.createElement('tr');
//                     row.innerHTML = `<td colspan="3">Brak aplikantów.</td>`;
//                     tbody.appendChild(row);
//                 }
//             })
//             .catch((error) => {
//                 console.error('Błąd AJAX:', error);
//                 showPopup(error || 'Wystąpił problem podczas ładowania aplikantów.', 'error');
//             });
//     };

//     const updateApplicantStatus = (applicantId, actionType) => {
//         AjaxHelper.sendRequest(DMVars.ajaxurl, 'POST', {
//             action: 'update_applicant_status',
//             post_id: postId,
//             applicant_id: applicantId,
//             action_type: actionType,
//         })
//             .then((response) => {
//                 showPopup(response.message || 'Status aplikanta został zaktualizowany.', 'success');
//                 loadApplicants(); // Odśwież listę aplikantów
//             })
//             .catch((error) => {
//                 showPopup(error || 'Wystąpił problem podczas aktualizacji statusu aplikanta.', 'error');
//             });
//     };

//     const addApplicantActionHandlers = () => {
//         document.querySelectorAll('.accept-btn').forEach((button) => {
//             button.addEventListener('click', (e) => {
//                 const applicantId = e.target.dataset.applicantId;
//                 updateApplicantStatus(applicantId, 'accept');
//             });
//         });

//         document.querySelectorAll('.reject-btn').forEach((button) => {
//             button.addEventListener('click', (e) => {
//                 const applicantId = e.target.dataset.applicantId;
//                 updateApplicantStatus(applicantId, 'reject');
//             });
//         });
//     };

//     loadApplicants();
// });


// document.addEventListener('DOMContentLoaded', () => {
//     const section = document.querySelector('#villagers-section');
//     const postId = section ? section.dataset.postId : null;

//     if (!postId) {
//         console.error('Brak ID wioski w #villagers-section');
//         return;
//     }

//     const loadVillagers = () => {
//         AjaxHelper.sendRequest(DMVars.ajaxurl, 'POST', {
//             action: 'get_villagers',
//             post_id: postId,
//         })
//             .then((response) => {
//                 const tbody = document.querySelector('#villagers-table tbody');
//                 tbody.innerHTML = '';

//                 if (Array.isArray(response.data.villagers) && response.data.villagers.length > 0) {
//                     const currentUserId = response.data.current_user_id; // ID bieżącego użytkownika (lidera)

//                     response.data.villagers.forEach((villager) => {
//                         const isLeader = villager.ID === currentUserId;

//                         const row = document.createElement('tr');
//                         row.innerHTML = `
//                             <td>${villager.display_name}</td>
//                             <td>${villager.user_email}</td>
//                             <td>
//                                 ${isLeader
//                                 ? '<span class="text-muted">Nie można usunąć lidera</span>'
//                                 : `<button class="remove-btn btn btn-red" data-villager-id="${villager.ID}">Wyrzuć</button>`
//                             }
//                             </td>
//                         `;
//                         tbody.appendChild(row);
//                     });

//                     addVillagerActionHandlers();
//                 } else {
//                     const row = document.createElement('tr');
//                     row.innerHTML = `<td colspan="3">Brak mieszkańców.</td>`;
//                     tbody.appendChild(row);
//                 }
//             })
//             .catch((error) => {
//                 console.error('Błąd AJAX:', error);
//                 showPopup(error || 'Wystąpił problem podczas ładowania mieszkańców.', 'error');
//             });
//     };

//     const removeVillager = (villagerId) => {
//         AjaxHelper.sendRequest(DMVars.ajaxurl, 'POST', {
//             action: 'remove_villager',
//             post_id: postId,
//             villager_id: villagerId,
//         })
//             .then((response) => {
//                 showPopup(response.message || 'Mieszkaniec został wyrzucony z wioski.', 'success');
//                 loadVillagers(); // Odśwież listę mieszkańców
//             })
//             .catch((error) => {
//                 showPopup(error || 'Wystąpił problem podczas wyrzucania mieszkańca.', 'error');
//             });
//     };

//     const addVillagerActionHandlers = () => {
//         document.querySelectorAll('.remove-btn').forEach((button) => {
//             button.addEventListener('click', (e) => {
//                 const villagerId = e.target.dataset.villagerId;
//                 removeVillager(villagerId);
//             });
//         });
//     };

//     loadVillagers();
// });

