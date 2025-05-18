<?php

/**
 * ContextValidator - Klasa do walidacji i generowania kontekstu dla warunków dialogowych
 * 
 * Klasa ta jest odpowiedzialna za generowanie odpowiedniego kontekstu dla warunków dialogowych
 * na podstawie typu warunku (acf_layout) oraz danych użytkownika.
 *
 * @package Game
 * @subpackage NpcPopup
 * @since 1.0.0
 */
class ContextValidator
{
    /**
     * @var UserContext Obiekt UserContext dostarczający dane o użytkowniku
     */
    private $userContext;

    /**
     * Konstruktor klasy ContextValidator
     *
     * @param UserContext $userContext Obiekt UserContext dostarczający dane o użytkowniku
     */
    public function __construct(UserContext $userContext)
    {
        $this->userContext = $userContext;
    }

    /**
     * Generuje kontekst potrzebny do walidacji warunku na podstawie jego typu (acf_layout)
     *
     * @param string $acf_layout Typ warunku (np. 'condition_mission', 'condition_npc_relation', ...)
     * @param array $location_info Informacje o lokalizacji (opcjonalnie)
     * @return array Kontekst do walidacji warunku
     */
    public function getContextForCondition(string $acf_layout, array $location_info = []): array
    {
        switch ($acf_layout) {
            case 'condition_mission':
                return ['mission' => $this->userContext->get_missions()];
            case 'condition_npc_relation':
                return ['relations' => $this->userContext->get_relations()];
            case 'condition_task':
                return ['task' => $this->userContext->get_tasks()];
            case 'condition_location':
                // Preferuj przekazany location_info, fallback na własne get_location()
                $area_slug = $location_info['area_slug'] ?? ($this->userContext->get_location()['area_slug'] ?? null);
                return ['current_location_text' => $area_slug];
            case 'condition_inventory':
                return ['items' => $this->userContext->get_item_counts()];
            default:
                return [];
        }
    }

    /**
     * Waliduje warunek dialogowy na podstawie kontekstu
     *
     * @param array $condition Warunek do zwalidowania
     * @param array $location_info Informacje o lokalizacji (opcjonalnie)
     * @return array Kontekst dla warunku
     */
    public function validateCondition(array $condition, array $location_info = []): array
    {
        $acf_layout = $condition['acf_fc_layout'] ?? '';
        return $this->getContextForCondition($acf_layout, $location_info);
    }
}
