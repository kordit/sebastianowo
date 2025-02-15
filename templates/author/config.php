
<?php
$scena_szukana = 'kreator';
$wrapper_chat = get_field('wrapper_chat', 143);

if (!is_array($wrapper_chat)) {
    $wrapper_chat = []; // Upewnienie się, że mamy tablicę
}

$wynik = array_filter($wrapper_chat, function ($item) use ($scena_szukana) {
    return is_array($item) && isset($item['scena_dialogowa']) && $item['scena_dialogowa'] === $scena_szukana;
});

$wynik = array_values($wynik); // Resetowanie indeksów

// Pobierz tylko conversation, jeśli istnieje
$conversation = !empty($wynik) && isset($wynik[0]['conversation']) ? $wynik[0]['conversation'] : [];

// et_r($conversation);
get_npc(155, 'npc-popup', true);
?>

