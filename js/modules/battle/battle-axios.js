// battle-axios.js
// Obsługa pobierania danych walki przez axios i otwierania popupa
import axios from 'axios';
import BattlePopup from './BattlePopup.js';

export async function startBattle(npcId) {
    try {
        const response = await axios.post('/wp-json/game/v1/battle', { npc_id: npcId });
        const { player, opponent } = response.data;
        const popup = new BattlePopup(player, opponent);
        popup.render();
        // Możesz dodać obsługę zamykania, ataków itd.
        return popup;
    } catch (error) {
        alert('Błąd podczas rozpoczynania walki!');
        console.error(error);
    }
}
