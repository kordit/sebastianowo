// BattlePopup.js
// Klasa do obsługi popupa walki

export default class BattlePopup {
    constructor(player, opponent) {
        this.player = player;
        this.opponent = opponent;
        this.container = null;
    }

    render() {
        // Tworzy HTML popupa walki
        const html = `
        <div id="battle-container">
            <div id="health-bars-container">
                <div id="player-health-bar">
                    <div class="fighter-name">${this.player.name}</div>
                    <div class="hp-outer">
                        <div id="player-hp" class="hp-inner"></div>
                    </div>
                    <div class="hp-text"><span id="player-hp-value">${this.player.hp}</span>/<span id="player-hp-max">${this.player.maxHp}</span></div>
                </div>

                <div id="opponent-health-bar">
                    <div class="fighter-name">${this.opponent.name}</div>
                    <div class="hp-outer">
                        <div id="opponent-hp" class="hp-inner"></div>
                    </div>
                    <div class="hp-text"><span id="opponent-hp-value">${this.opponent.hp}</span>/<span id="opponent-hp-max">${this.opponent.maxHp}</span></div>
                </div>
            </div>

            <div id="battle-scene">
                <div id="player-character">
                    <img id="player-sprite" src="${this.player.img}" alt="Player Fighter">
                </div>

                <div id="opponent-character">
                    <img id="opponent-sprite" src="${this.opponent.img}" alt="Opponent">
                </div>

                <div id="battle-message">
                    <div id="battle-text">Co chcesz zrobić?</div>
                </div>

                <div id="battle-controls">
                    <div id="attack-menu" class="menu">
                        <button id="attack-1" class="attack-button">Z baniaka</button>
                        <button id="attack-2" class="attack-button">Sito</button>
                        <button id="attack-3" class="attack-button">Choleryk</button>
                        <button id="attack-4" class="attack-button">Dzik</button>
                    </div>
                    <div id="utility-menu" class="menu">
                        <button id="backpack-button" class="utility-button">Plecak 🎒</button>
                        <button id="escape-button" class="utility-button">Ucieczka 🏃</button>
                    </div>
                </div>
            </div>
        </div>
        `;
        this.container = document.createElement('div');
        this.container.innerHTML = html;
        document.body.appendChild(this.container);
    }

    destroy() {
        if (this.container) {
            this.container.remove();
            this.container = null;
        }
    }
}
