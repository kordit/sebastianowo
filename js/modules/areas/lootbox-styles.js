/**
 * Dodaje style CSS dla popupu lootboxa
 */
function addLootboxPopupStyles() {
    // Sprawdź, czy style już istnieją
    if (document.getElementById('lootbox-popup-styles')) {
        return;
    }

    const styles = `
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .popup-content {
            width: 90%;
            max-width: 500px;
            background: #1a1a1a;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            color: #ffffff;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        
        .popup-header {
            padding: 15px;
            background: #222;
            position: relative;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .popup-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }
        
        .popup-close:hover {
            color: white;
        }
        
        .popup-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
            flex: 1;
        }
        
        .energy-info {
            margin-bottom: 20px;
            padding: 10px;
            background: #222;
            border-radius: 5px;
        }
        
        .lootbox-results {
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .lootbox-result-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 10px;
            background: #272727;
            border-radius: 5px;
            border-left: 3px solid #4CAF50;
            transition: all 0.3s ease;
        }
        
        .lootbox-result-item:hover {
            background: #333;
        }
        
        .result-icon {
            margin-right: 15px;
        }
        
        .reward-icon {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }
        
        .popup-footer {
            padding: 15px;
            background: #222;
            border-top: 1px solid #333;
            text-align: right;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        
        .popup-button {
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .popup-button:hover {
            background: #45a049;
        }

        .popup-button:disabled {
            background: #555;
            cursor: not-allowed;
        }

        .not-enough-energy-info {
            flex: 1;
            text-align: left;
        }

        .search-complete-info {
            flex: 1;
            text-align: left;
        }
        
        .lootbox-info {
            margin-bottom: 15px;
            background: #333;
            padding: 15px;
            border-radius: 5px;
        }
    `;

    const styleElement = document.createElement('style');
    styleElement.id = 'lootbox-popup-styles';
    styleElement.textContent = styles;
    document.head.appendChild(styleElement);
}
