<?php
class DynamicEventLoader
{
    private $name_fields;

    public function __construct($name_fields)
    {
        $this->name_fields = $name_fields;
    }

    public function loadEvents()
    {
        $acf_events = get_field($this->name_fields, 'option'); // Pobieranie danych z opcji ACF
        $events = [];

        if ($acf_events) {
            foreach ($acf_events as $acf_event) {
                $frequency = isset($acf_event['frequency']) ? (int) $acf_event['frequency'] : 1; // Pobierz częstotliwość, domyślnie 1
                for ($i = 0; $i < $frequency; $i++) { // Powtórz dodanie zdarzenia do tablicy tyle razy, ile wynosi częstotliwość
                    $events[] = $this->createEvent($acf_event);
                }
            }
        }
        return $events;
    }

    private function createEvent($acf_event)
    {
        $event = [
            'msg' => $acf_event['dialog'],
            'action' => $acf_event['action'],
        ];

        switch ($acf_event['action']) {
            case 'damage':
                $event['damage'] = rand($acf_event['from'], $acf_event['to']);
                break;
            case 'neutral':
                $event['neutral'] = true;
                break;
            case 'mineral':
                $event['mineral'] = $acf_event['profit'];
                $event['amount'] = rand($acf_event['from'], $acf_event['to']);
                break;
            case 'monster':
                if (isset($acf_event['monster']) && $acf_event['monster'] instanceof WP_Post) {
                    $event['monster'] = [
                        'id' => $acf_event['monster']->ID,
                        'name' => $acf_event['monster']->post_title,
                    ];
                }
                break;
            default:
                break;
        }
        return $event;
    }
}
