/**
 * Moduł zarządzania postacią oparty na Alpine.js
 */
console.log('Character Manager module loaded');

// Funkcja inicjalizująca komponent Alpine.js dla zarządzania postacią
export function initCharacterManager() {
    // Rejestracja komponentu Alpine.js
    window.characterManager = function () {
        return {
            // Stan komponentu
            userData: null,
            isLoading: true,
            error: null,

            // Metoda inicjalizująca
            async init() {
                try {
                    await this.getUserData();
                } catch (error) {
                    console.error('Nie udało się załadować danych użytkownika:', error);
                    this.error = 'Nie udało się załadować danych użytkownika';
                } finally {
                    this.isLoading = false;
                }
            },

            // Pobieranie danych użytkownika
            async getUserData(userId = null) {
                try {
                    this.isLoading = true;
                    const response = await window.ajaxRequest('get_user_data', userId ? { user_id: userId } : {});

                    if (response.success) {
                        this.userData = response.data;
                        // Dodanie dodatkowych pól do wyświetlania w UI
                        this.processUserData();
                        return response.data;
                    } else {
                        throw new Error(response.data?.message || 'Nieznany błąd');
                    }
                } catch (error) {
                    console.error('Błąd podczas pobierania danych użytkownika:', error);
                    this.error = 'Nie udało się pobrać danych użytkownika';
                    return null;
                } finally {
                    this.isLoading = false;
                }
            },

            // Przetwarzanie danych użytkownika dla UI
            processUserData() {
                if (!this.userData) return;

                // Obliczanie procentu doświadczenia do następnego poziomu
                const level = this.userData.level || 1;
                const exp = this.userData.experience || 0;

                // Próg dla następnego poziomu (na podstawie Twojej funkcji calculate_level_from_experience)
                const baseExp = 1000;
                const factor = 1.5;

                // Oblicz próg dla obecnego poziomu
                let currentThreshold = baseExp;
                for (let i = 1; i < level; i++) {
                    currentThreshold *= factor;
                }

                // Próg dla następnego poziomu
                const nextThreshold = currentThreshold * factor;

                // Oblicz procent postępu
                const expForLevel = exp - currentThreshold;
                const expNeeded = nextThreshold - currentThreshold;
                const percentage = Math.min(Math.floor((expForLevel / expNeeded) * 100), 100);

                // Dodaj te wartości do userData
                this.userData.experience_percentage = percentage >= 0 ? percentage : 0;
                this.userData.next_level_exp = Math.floor(nextThreshold);
                this.userData.current_level_exp = Math.floor(currentThreshold);
            },

            // Aktualizacja poziomu użytkownika
            async updateUserLevel(experience) {
                try {
                    this.isLoading = true;
                    const response = await window.ajaxRequest('update_user_level', { experience });

                    if (response.success) {
                        // Odśwież dane użytkownika
                        await this.getUserData();

                        // Sprawdź czy nastąpił level up
                        if (response.data?.level_up) {
                            if (window.notifySuccess) {
                                window.notifySuccess(`Gratulacje! Osiągnąłeś poziom ${response.data.new_level}!`);
                            }
                        }
                        return response.data;
                    } else {
                        throw new Error(response.data?.message || 'Nieznany błąd');
                    }
                } catch (error) {
                    console.error('Błąd podczas aktualizacji poziomu:', error);
                    if (window.notifyError) {
                        window.notifyError('Nie udało się zaktualizować poziomu');
                    }
                    return null;
                } finally {
                    this.isLoading = false;
                }
            },

            // Aktualizacja statystyk
            async updateStat(statName, value) {
                if (!this.userData || !this.userData.stats) {
                    return;
                }

                try {
                    this.isLoading = true;
                    const updatedStats = { ...this.userData.stats };
                    updatedStats[statName] = value;

                    const userData = { stats: updatedStats };

                    const response = await window.ajaxRequest('update_user_data', {
                        user_data: JSON.stringify(userData)
                    });

                    if (response.success) {
                        // Odśwież dane użytkownika
                        await this.getUserData();

                        if (window.notifySuccess) {
                            window.notifySuccess(`Statystyka ${statName} została zaktualizowana`);
                        }

                        return response.data;
                    } else {
                        throw new Error(response.data?.message || 'Nieznany błąd');
                    }
                } catch (error) {
                    console.error('Błąd podczas aktualizacji statystyk:', error);
                    if (window.notifyError) {
                        window.notifyError('Nie udało się zaktualizować statystyk');
                    }
                    return null;
                } finally {
                    this.isLoading = false;
                }
            },

            // Formatuje nazwę statystyki na przyjazną dla użytkownika
            formatStat(statName) {
                const statDict = {
                    'strength': 'Siła',
                    'agility': 'Zręczność',
                    'intelligence': 'Inteligencja',
                    'charisma': 'Charyzma',
                    'health': 'Zdrowie',
                    'energy': 'Energia'
                };
                return statDict[statName] || statName;
            }
        };
    };

    console.log('Character Manager initialized with Alpine.js');
}

// Natychmiast inicjuj moduł
document.addEventListener('DOMContentLoaded', () => {
    initCharacterManager();
});
