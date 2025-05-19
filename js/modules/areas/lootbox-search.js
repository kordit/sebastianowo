/**
 * Funkcja do przeszukania lootboxa
 * @param {Number} lootboxId - ID lootboxa
 */
function searchLootbox(lootboxId) {
    // Dodaj nonce dla autoryzacji
    const restNonce = userManagerData?.nonce || '';

    // Wykonaj zapytanie
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
                buildLootboxPopup(data, lootboxId);

                // Aktualizuj pasek energii
                if (data.user_energy !== undefined) {
                    // Użyj maksymalnej energii zwróconej przez API lub pobierz z istniejącego paska
                    const maxEnergy = data.max_energy !== undefined
                        ? data.max_energy
                        : (() => {
                            const energyBar = document.querySelector('.bar-game[data-bar-type="energy"]');
                            return energyBar ? parseInt(energyBar.dataset.barMax) : 100;
                        })();

                    UIHelpers.updateStatusBar('energy', data.user_energy, maxEnergy);

                    // Dodaj efekt wizualny aktualizacji paska energii
                    const barWrappers = document.querySelectorAll('.wrap-bar');
                    barWrappers.forEach(wrapper => {
                        wrapper.classList.add('resource-updated');
                        setTimeout(() => {
                            wrapper.classList.remove('resource-updated');
                        }, 1000);
                    });
                }
            }
        })
        .catch(error => {
            console.error("Błąd zapytania:", error);
            UIHelpers.showNotification("Wystąpił błąd podczas przeszukiwania.", 'error');
        });
}

window.searchLootbox = searchLootbox;
