<?php
class DynamicACFUpdater
{
    public function __construct()
    {
        add_action('wp_ajax_update_acf_fields', [$this, 'handle_update_acf_fields']);
    }

    public function handle_update_acf_fields()
    {
        try {
            check_ajax_referer('dm_nonce', 'nonce');
            if (!is_user_logged_in()) {
                throw new Exception('Wymagane logowanie');
            }
            // Umożliwiamy edycję tylko własnych danych
            $user_id = get_current_user_id();
            if ($user_id !== get_current_user_id()) {
                throw new Exception('Nie masz uprawnień do edycji danych innego użytkownika');
            }
            if (!$user_id) {
                throw new Exception('Nie rozpoznano użytkownika');
            }
            if (empty($_POST['fields'])) {
                throw new Exception('Brak danych do aktualizacji');
            }
            $raw_data = sanitize_textarea_field(stripslashes($_POST['fields']));
            $data = json_decode($raw_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Błąd dekodowania JSON: ' . json_last_error_msg());
            }
            if (!is_array($data)) {
                throw new Exception('Nieprawidłowy format danych');
            }
            $registered_fields = get_field_objects("user_{$user_id}");
            if (!$registered_fields) {
                $registered_fields = [];
            }
            // PRE-CHECK: Blokujemy dodatnie zmiany dla minerali dla nie-adminów oraz sprawdzamy odejmowanie
            $preCheckErrors = [];
            foreach ($data as $field_key => $value) {
                if (strpos($field_key, '.') !== false) {
                    list($group_field_name, $sub_field_name) = explode('.', $field_key, 2);
                    if (!isset($registered_fields[$group_field_name])) {
                        continue;
                    }
                    $group_field = $registered_fields[$group_field_name];
                    if ($group_field['type'] !== 'group') {
                        continue;
                    }
                    $group_value = get_field($group_field_name, "user_{$user_id}");
                    if (!is_array($group_value)) {
                        $group_value = [];
                    }
                    $sub_field = null;
                    if (isset($group_field['sub_fields']) && is_array($group_field['sub_fields'])) {
                        foreach ($group_field['sub_fields'] as $sf) {
                            if ($sf['name'] === $sub_field_name) {
                                $sub_field = $sf;
                                break;
                            }
                        }
                    }
                    if (!$sub_field) {
                        continue;
                    }
                    if ($sub_field['type'] === 'number') {
                        $old = isset($group_value[$sub_field_name]) ? $group_value[$sub_field_name] : 0;
                        $oldNumeric = is_numeric($old) ? floatval($old) : 0;
                        $delta = floatval($value);
                        // Blokujemy dodanie dla minerali, jeśli użytkownik nie jest adminem
                        if (
                            !current_user_can('manage_options') &&
                            in_array("{$group_field_name}.{$sub_field_name}", ['minerals.gold', 'minerals.stone']) &&
                            $delta > 0
                        ) {
                            $resourceName = $this->getFriendlyResourceName("{$group_field_name}.{$sub_field_name}");
                            $preCheckErrors[] = "Nie możesz dodać {$resourceName}.";
                        }
                        if ($delta < 0 && abs($delta) > $oldNumeric) {
                            $resourceName = $this->getFriendlyResourceName("{$group_field_name}.{$sub_field_name}");
                            $preCheckErrors[] = "Brakuje " . abs($delta) . " {$resourceName}.";
                        }
                    }
                } else {
                    if (!isset($registered_fields[$field_key])) {
                        continue;
                    }
                    $field = $registered_fields[$field_key];
                    if ($field['type'] === 'number') {
                        $old = get_field($field_key, "user_{$user_id}");
                        $oldNumeric = is_numeric($old) ? floatval($old) : 0;
                        $delta = floatval($value);
                        if (
                            !current_user_can('manage_options') &&
                            in_array($field_key, ['minerals.gold', 'minerals.stone']) &&
                            $delta > 0
                        ) {
                            $resourceName = $this->getFriendlyResourceName($field_key);
                            $preCheckErrors[] = "Nie możesz dodać {$resourceName}.";
                        }
                        if ($delta < 0 && abs($delta) > $oldNumeric) {
                            $resourceName = $this->getFriendlyResourceName($field_key);
                            $preCheckErrors[] = "Nie masz wystarczająco {$resourceName}. Próbujesz odjąć " . abs($delta) . ", ale masz tylko " . $oldNumeric . ".";
                        }
                    }
                }
            }
            if (!empty($preCheckErrors)) {
                throw new Exception(implode(" ", $preCheckErrors));
            }
            // Aktualizacja pól
            $update_messages = [];
            foreach ($data as $field_key => $value) {
                if (strpos($field_key, '.') !== false) {
                    list($group_field_name, $sub_field_name) = explode('.', $field_key, 2);
                    if (!isset($registered_fields[$group_field_name])) {
                        $update_messages[] = "Brak pola grupy: {$group_field_name} dla klucza {$field_key}.";
                        continue;
                    }
                    $group_field = $registered_fields[$group_field_name];
                    if ($group_field['type'] !== 'group') {
                        $update_messages[] = "Pole {$group_field_name} nie jest grupą.";
                        continue;
                    }
                    $group_value = get_field($group_field_name, "user_{$user_id}");
                    if (!is_array($group_value)) {
                        $group_value = [];
                    }
                    $sub_field = null;
                    if (isset($group_field['sub_fields']) && is_array($group_field['sub_fields'])) {
                        foreach ($group_field['sub_fields'] as $sf) {
                            if ($sf['name'] === $sub_field_name) {
                                $sub_field = $sf;
                                break;
                            }
                        }
                    }
                    if (!$sub_field) {
                        $update_messages[] = "Brak pola podrzędnego: {$sub_field_name} w grupie {$group_field_name}.";
                        continue;
                    }
                    try {
                        $this->validate_value($value, $sub_field, "{$group_field_name}.{$sub_field_name}");
                    } catch (Exception $ex) {
                        $update_messages[] = $ex->getMessage();
                        continue;
                    }
                    $old = isset($group_value[$sub_field_name]) ? $group_value[$sub_field_name] : 0;
                    if ($sub_field['type'] === 'number') {
                        list($oldNumeric, $newValue, $clamped) = $this->processNumericValue($old, $value, "{$group_field_name}.{$sub_field_name}");
                        $group_value[$sub_field_name] = $newValue;
                        update_field($group_field['key'], $group_value, "user_{$user_id}");
                        $update_messages[] = $this->getChangeMessage($oldNumeric, $newValue, "{$group_field_name}.{$sub_field_name}", $clamped);
                    } elseif ($sub_field['type'] === 'select') {
                        $newLabel = isset($sub_field['choices'][$value]) ? $sub_field['choices'][$value] : $value;
                        $group_value[$sub_field_name] = ['value' => $value, 'label' => $newLabel];
                        update_field($group_field['key'], $group_value, "user_{$user_id}");
                        $oldLabel = is_array($old) && isset($old['label']) ? $old['label'] : (isset($sub_field['choices'][$old]) ? $sub_field['choices'][$old] : $old);
                        $update_messages[] = $this->getChangeMessage($oldLabel, $newLabel, "{$group_field_name}.{$sub_field_name}");
                    } elseif ($sub_field['type'] === 'true_false') {
                        $group_value[$sub_field_name] = ($value ? 1 : 0);
                        update_field($group_field['key'], $group_value, "user_{$user_id}");
                        $update_messages[] = "Pole " . $this->getFriendlyResourceName("{$group_field_name}.{$sub_field_name}") . " zostało zaktualizowane.";
                    } elseif ($sub_field['type'] === 'image') {
                        $group_value[$sub_field_name] = intval($value);
                        update_field($group_field['key'], $group_value, "user_{$user_id}");
                        $update_messages[] = "Avatar został zaktualizowany.";
                    } else {
                        $group_value[$sub_field_name] = $value;
                        update_field($group_field['key'], $group_value, "user_{$user_id}");
                        $update_messages[] = $this->getChangeMessage($old, $value, "{$group_field_name}.{$sub_field_name}");
                    }
                } else {
                    if (!isset($registered_fields[$field_key])) {
                        $update_messages[] = "Brak pola: {$field_key}.";
                        continue;
                    }
                    $field = $registered_fields[$field_key];
                    try {
                        $this->validate_value($value, $field, $field_key);
                    } catch (Exception $ex) {
                        $update_messages[] = $ex->getMessage();
                        continue;
                    }
                    $old = get_field($field_key, "user_{$user_id}");
                    if ($field['type'] === 'number') {
                        list($oldNumeric, $newValue, $clamped) = $this->processNumericValue($old, $value, $field_key);
                        update_field($field['key'], $newValue, "user_{$user_id}");
                        $update_messages[] = $this->getChangeMessage($oldNumeric, $newValue, $field_key, $clamped);
                    } elseif ($field['type'] === 'true_false') {
                        update_field($field['key'], ($value ? 1 : 0), "user_{$user_id}");
                        $update_messages[] = "Pole " . $this->getFriendlyResourceName($field_key) . " zostało zaktualizowane.";
                    } elseif ($field['type'] === 'select') {
                        $newLabel = isset($field['choices'][$value]) ? $field['choices'][$value] : $value;
                        update_field($field['key'], ['value' => $value, 'label' => $newLabel], "user_{$user_id}");
                        $oldLabel = is_array($old) && isset($old['label']) ? $old['label'] : (isset($field['choices'][$old]) ? $field['choices'][$old] : $old);
                        $update_messages[] = $this->getChangeMessage($oldLabel, $newLabel, $field_key);
                    } elseif ($field['type'] === 'image') {
                        update_field($field['key'], intval($value), "user_{$user_id}");
                        $update_messages[] = "Avatar został zaktualizowany.";
                    } else {
                        update_field($field['key'], $value, "user_{$user_id}");
                        $update_messages[] = $this->getChangeMessage($old, $value, $field_key);
                    }
                }
            }
            $full_message = implode(" ", $update_messages);
            wp_send_json_success(['message' => $full_message, 'code' => 'success']);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage(), 'code' => 'error']);
        }
    }

    private function processNumericValue($old, $value, $field_identifier = '')
    {
        $delta = floatval($value);
        $oldNumeric = is_numeric($old) ? floatval($old) : 0;
        if ($delta < 0 && abs($delta) > $oldNumeric) {
            $resourceName = $field_identifier ? $this->getFriendlyResourceName($field_identifier) : 'minerałów';
            throw new Exception("Brakuje " . abs($delta) . " {$resourceName}");
        }
        $newValue = $oldNumeric + $delta;
        return [$oldNumeric, $newValue, false];
    }

    private function getChangeMessage($old, $new, $field_name, $clamped = false)
    {
        $friendlyName = $this->getFriendlyResourceName($field_name);
        if (is_numeric($old) && is_numeric($new)) {
            $delta = $new - $old;
            if ($delta > 0) {
                return "Twoje zapasy {$friendlyName} wzbogaciły się o {$delta}!";
            } elseif ($delta < 0) {
                if ($clamped) {
                    return "Nie mogłeś stracić więcej {$friendlyName} niż miałeś! Odebrano całe posiadane {$friendlyName}.";
                } else {
                    return "Twoje zapasy {$friendlyName} zmniejszyły się o " . abs($delta) . "!";
                }
            } else {
                return "Zapasy {$friendlyName} pozostają bez zmian.";
            }
        } else {
            return "Pole {$friendlyName} zostało zmienione z '{$old}' na '{$new}'.";
        }
    }

    private function getFriendlyResourceName($field_name)
    {
        if (stripos($field_name, 'gold') !== false) {
            return "złotych";
        } elseif (stripos($field_name, 'piwo') !== false) {
            return "piw";
        } elseif (stripos($field_name, 'papierosy') !== false) {
            return "szlugów";
        }
        return $field_name;
    }

    private function validate_value($value, $field, $field_name)
    {
        switch ($field['type']) {
            case 'text':
            case 'wysiwyg':
            case 'select':
                if (!is_string($value)) {
                    throw new Exception("Pole {$field_name} musi być tekstem");
                }
                break;
            case 'number':
                if (!is_numeric($value)) {
                    throw new Exception("Pole {$field_name} musi być liczbą");
                }
                break;
            case 'true_false':
                if (!is_bool($value)) {
                    throw new Exception("Pole {$field_name} musi być wartością logiczną");
                }
                break;
            case 'image':
                if (!is_numeric($value)) {
                    throw new Exception("Pole {$field_name} musi być ID obrazka (liczbą)");
                }
                break;
            case 'group':
                if (!is_array($value)) {
                    throw new Exception("Pole {$field_name} musi być tablicą");
                }
                break;
            default:
                break;
        }
    }
}
new DynamicACFUpdater();



// Endpoint do pobierania aktualnych pól ACF użytkownika
class ACFFieldsFetcher
{
    public function __construct()
    {
        add_action('wp_ajax_get_acf_fields', [$this, 'handle_get_acf_fields']);
    }
    public function handle_get_acf_fields()
    {
        try {
            check_ajax_referer('dm_nonce', 'nonce');
            if (!is_user_logged_in()) {
                throw new Exception('Wymagane logowanie');
            }
            $user_id = get_current_user_id();
            $registered_fields = get_field_objects("user_{$user_id}");
            if (!$registered_fields) {
                $registered_fields = [];
            }
            $result = [];
            foreach ($registered_fields as $key => $field) {
                $result[$field['name']] = get_field($field['name'], "user_{$user_id}");
            }
            wp_send_json_success(['fields' => $result]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
new ACFFieldsFetcher();
