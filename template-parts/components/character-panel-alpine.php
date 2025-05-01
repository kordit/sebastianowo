<!-- Panel postaci z Alpine.js -->
<div class="character-panel" x-data="characterManager()">
    <!-- Stan ładowania -->
    <div class="loading-overlay" x-show="isLoading">
        <div class="loading-spinner"></div>
        <div class="loading-text">Ładowanie danych postaci...</div>
    </div>

    <!-- Komunikat błędu -->
    <div class="error-message" x-show="error" x-text="error"></div>

    <!-- Panel danych postaci -->
    <div class="character-data" x-show="userData && !isLoading && !error">
        <!-- Nagłówek z podstawowymi informacjami -->
        <div class="character-header">
            <div class="character-avatar"></div>
            <div class="character-info">
                <h3 class="character-name" x-text="userData.display_name"></h3>
                <div class="character-class" x-text="userData.class"></div>
                <div class="character-level">
                    <span>Poziom:</span>
                    <strong x-text="userData.level"></strong>
                </div>
            </div>
        </div>

        <!-- Pasek doświadczenia -->
        <div class="experience-bar-container">
            <div class="experience-label">Doświadczenie:</div>
            <div class="experience-bar">
                <div class="experience-progress" :style="'width: ' + userData.experience_percentage + '%'"></div>
                <span class="experience-text">
                    <span x-text="userData.experience"></span> / <span x-text="userData.next_level_exp"></span>
                </span>
            </div>
        </div>

        <!-- Statystyki postaci -->
        <div class="character-stats">
            <h4>Statystyki</h4>
            <div class="stats-grid">
                <template x-for="(value, name) in userData.stats" :key="name">
                    <div class="stat-item">
                        <div class="stat-label" x-text="formatStat(name)"></div>
                        <div class="stat-value" x-text="value"></div>
                        <div class="stat-controls">
                            <button @click="updateStat(name, value - 1)" class="stat-btn stat-decrease">-</button>
                            <button @click="updateStat(name, value + 1)" class="stat-btn stat-increase">+</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Przycisk do testowego dodawania doświadczenia -->
        <div class="character-actions">
            <button @click="updateUserLevel(userData.experience + 100)" class="action-btn">
                Dodaj 100 EXP
            </button>
        </div>
    </div>
</div>

<!-- Style CSS dla komponentu -->
<style>
    .character-panel {
        background-color: #1a1a22;
        border-radius: 8px;
        color: #e0e0e0;
        font-family: 'Work Sans', sans-serif;
        max-width: 600px;
        margin: 20px auto;
        padding: 20px;
        position: relative;
    }

    .loading-overlay {
        align-items: center;
        background-color: rgba(26, 26, 34, 0.9);
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        height: 100%;
        justify-content: center;
        left: 0;
        position: absolute;
        top: 0;
        width: 100%;
        z-index: 10;
    }

    .loading-spinner {
        border: 4px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        border-top: 4px solid #007bff;
        height: 40px;
        margin-bottom: 15px;
        width: 40px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .error-message {
        background-color: rgba(220, 53, 69, 0.2);
        border-left: 4px solid #dc3545;
        color: #ffaaaa;
        padding: 15px;
        margin-bottom: 20px;
    }

    .character-header {
        align-items: center;
        display: flex;
        margin-bottom: 20px;
    }

    .character-avatar {
        background-color: #2d2d3a;
        border-radius: 50%;
        height: 80px;
        margin-right: 20px;
        width: 80px;
    }

    .character-name {
        color: #ffffff;
        font-size: 1.5rem;
        margin: 0 0 5px;
    }

    .character-class {
        color: #ffc107;
        font-size: 1rem;
        margin-bottom: 5px;
    }

    .character-level {
        color: #c0c0c0;
        font-size: 0.9rem;
    }

    .experience-bar-container {
        margin-bottom: 20px;
    }

    .experience-label {
        color: #c0c0c0;
        font-size: 0.85rem;
        margin-bottom: 5px;
    }

    .experience-bar {
        background-color: #2d2d3a;
        border-radius: 4px;
        height: 20px;
        overflow: hidden;
        position: relative;
    }

    .experience-progress {
        background-color: #007bff;
        background-image: linear-gradient(45deg, rgba(255, 255, 255, .15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, .15) 50%, rgba(255, 255, 255, .15) 75%, transparent 75%, transparent);
        background-size: 1rem 1rem;
        height: 100%;
        left: 0;
        position: absolute;
        top: 0;
        transition: width 0.3s ease;
    }

    .experience-text {
        align-items: center;
        color: #ffffff;
        display: flex;
        font-size: 0.75rem;
        height: 100%;
        justify-content: center;
        position: relative;
        z-index: 1;
    }

    .character-stats {
        margin-bottom: 20px;
    }

    .character-stats h4 {
        border-bottom: 1px solid #3a3a4a;
        color: #ffffff;
        margin-bottom: 15px;
        padding-bottom: 5px;
    }

    .stats-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }

    .stat-item {
        align-items: center;
        background-color: #2d2d3a;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        padding: 8px 12px;
    }

    .stat-label {
        color: #c0c0c0;
    }

    .stat-value {
        color: #ffffff;
        font-weight: 600;
    }

    .stat-controls {
        display: flex;
        gap: 5px;
    }

    .stat-btn {
        background-color: #3a3a4a;
        border: none;
        border-radius: 3px;
        color: #ffffff;
        cursor: pointer;
        font-size: 12px;
        height: 24px;
        line-height: 1;
        padding: 0;
        transition: background-color 0.2s;
        width: 24px;
    }

    .stat-btn:hover {
        background-color: #4a4a5a;
    }

    .stat-decrease {
        color: #dc3545;
    }

    .stat-increase {
        color: #28a745;
    }

    .character-actions {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .action-btn {
        background-color: #007bff;
        border: none;
        border-radius: 4px;
        color: #ffffff;
        cursor: pointer;
        font-family: 'Work Sans', sans-serif;
        font-size: 0.9rem;
        padding: 8px 16px;
        text-transform: uppercase;
        transition: background-color 0.2s;
    }

    .action-btn:hover {
        background-color: #0069d9;
    }
</style>