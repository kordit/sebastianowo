async function createGroupProcess(title, acfFields, costFields) {
    try {
        // Najpierw pobieramy hajs (odejmujemy koszt)
        await updateACFFieldsWithGui(costFields, ['body'], "Koszt został pobrany.");

        // Następnie próbujemy utworzyć grupę (wpis typu 'group')
        const groupResponse = await createCustomPost(title, 'group', acfFields);
        const groupId = groupResponse.data.post_id;

        // Po pomyślnym utworzeniu grupy aktualizujemy pole użytkownika (np. "my_group")
        await updateACFFields({ my_group: groupId });

        createCustomPopup({
            imageId: 54,
            header: "Grupa została utworzona!",
            description: "Twoja grupa została zarejestrowana i przypisana do Twojego konta.",
            link: groupResponse.data.post_url,
            linkLabel: "Przejdź do grupy",
            status: "success",
            closeable: false
        });

        return groupResponse;
    } catch (error) {
        // Jeśli błąd dotyczy istnienia grupy, refundujemy pobrany koszt
        if (error.message && error.message.includes("istnieje")) {
            const refundFields = {};
            // Dla każdego pola kosztowego mnożymy wartość przez -1
            for (let key in costFields) {
                refundFields[key] = -costFields[key];
            }
            await updateACFFieldsWithGui(refundFields, ['body'], "Koszt został zwrócony.");
        }
        throw error;
    }
}

const createGroupForm = document.getElementById('create-group-form');
if (createGroupForm) {
    createGroupForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const title = document.getElementById('group-title').value.trim();
        if (!title) {
            showPopup("Podaj nazwę grupy!", "error");
            return;
        }
        const selectedColor = document.querySelector('input[name="color-district"]:checked');
        if (!selectedColor) {
            showPopup("Wybierz kolor osiedla!", "error");
            return;
        }
        const terenId = document.getElementById('teren-id').value;
        const userId = parseInt(document.getElementById('user-id').value, 10);

        // Definicja pól ACF – uwzględniamy kolor, lidera, lokalizację i członków
        const acfFields = {
            field_color_district: selectedColor.value,
            field_leader: userId,
            field_teren_grupy: [parseInt(terenId, 10)],
            field_the_villagers: [userId]
        };
        // Koszt utworzenia grupy: odejmujemy 200 złota
        const costFields = {
            "bag.gold": -500,
            "bag.piwo": -20,
            "bag.papierosy": -200,

        };

        try {
            await createGroupProcess(title, acfFields, costFields);
        } catch (error) {
            showPopup(error.message || error, "error");
        }
    });
}


document.addEventListener("DOMContentLoaded", function () {
    const svg = document.querySelector("svg");
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


document.querySelectorAll('path[data-select="scena"]').forEach(el => {
    el.addEventListener('click', e => {
        e.preventDefault();
        let target = el.getAttribute('data-target');
        window.location.href = target;
    });
});

document.querySelectorAll('path[data-select="npc"]').forEach(el => {
    el.addEventListener('click', e => {
        e.preventDefault();
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
                console.log("HTML po wstawieniu:", document.body.innerHTML);
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
    });
});


document.getElementById('loadNpc').addEventListener('click', () => {
    const npcId = document.getElementById('loadNpc').getAttribute('data-npc-id');
    AjaxHelper.sendRequest(global.ajaxurl, 'POST', {
        action: 'get_npc_popup',
        npc_id: npcId
    })
        .then(response => {
            const trimmedData = response.data.trim();
            document.body.insertAdjacentHTML('beforeend', trimmedData);
            console.log("HTML po wstawieniu:", document.body.innerHTML);
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
});


