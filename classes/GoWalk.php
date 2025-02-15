<?php
add_action('wp_ajax_get_random_event', 'get_random_event');
add_action('wp_ajax_nopriv_get_random_event', 'get_random_event');

function get_random_event()
{
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : get_the_ID(); // Pobranie ID podstrony
    $events = get_field('events', $post_id); // Pobranie zdarzeń z tej podstrony

    if (empty($events) || !is_array($events)) {
        wp_send_json_error([
            'message' => 'Brak dostępnych zdarzeń',
            'debug' => [
                'post_id' => $post_id,
                'events' => $events
            ]
        ]);
    }

    // Tworzenie listy zdarzeń z wagą
    $weightedEvents = [];
    foreach ($events as $event) {
        $count = max(1, intval($event['liczba_zdarzen'] ?? 1));
        for ($i = 0; $i < $count; $i++) {
            $weightedEvents[] = $event;
        }
    }

    if (empty($weightedEvents)) {
        wp_send_json_error(['message' => 'Nie udało się wylosować zdarzenia']);
    }

    // Losowanie zdarzenia
    $randomEvent = $weightedEvents[array_rand($weightedEvents)];

    // Jeśli zdarzenie to NPC
    if ($randomEvent['events_type'] === 'npc') {
        wp_send_json_success([
            'events_type' => 'npc',
            'npc' => $randomEvent['npc']
        ]);
    }

    // Jeśli zdarzenie to event
    $event_id = $randomEvent['event'];
    $event_post = get_post($event_id);
    $post_name = get_post_field('post_name', $post_id); // Pobranie slug podstrony
    $redirect_url = home_url("/tereny/{$post_name}/spacer?losuj=1"); // Tworzenie URL z `losuj=1`

    // Pobranie danych eventu
    $image_id = get_post_thumbnail_id($event_id) ?: 13;
    $header = get_the_title($event_id);
    $description = get_field('content_wysiwyg', $event_id) ?: '';

    wp_send_json_success([
        'events_type' => 'event',
        'redirect_url' => $redirect_url,
        'image_id' => $image_id,
        'header' => $header,
        'description' => $description
    ]);
}
