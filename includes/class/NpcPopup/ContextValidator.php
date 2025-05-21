<?php
class ContextValidator
{
    private UserContext $userContext;
    private NpcLogger $logger;

    public function __construct(UserContext $userContext, NpcLogger $logger)
    {
        $this->userContext = $userContext;
        $this->logger = $logger;
    }

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
                $area_slug = $location_info['area_slug'] ?? ($this->userContext->get_location()['area_slug'] ?? null);
                return ['current_location_text' => $area_slug];
            case 'condition_inventory':
                return ['items' => $this->userContext->get_item_counts()];
            default:
                $this->logger->debug_log("Nieznany typ warunku: $acf_layout");
                return [];
        }
    }

    public function validateCondition(array $condition, array $location_info = []): array
    {
        $acf_layout = $condition['acf_fc_layout'] ?? '';
        return $this->getContextForCondition($acf_layout, $location_info);
    }
}
