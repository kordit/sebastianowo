<?php
// BattlePopup.php
// Klasa PHP do obsługi danych walki (np. do API)

class BattlePopup
{
    public static function get_battle_data($user_id, $npc_id)
    {
        // Pobierz dane gracza
        $user = get_userdata($user_id);
        $player = [
            'name' => $user ? $user->display_name : 'Sebastian',
            'hp' => 100,
            'maxHp' => 100,
            'img' => 'http://seb.soeasy.it/wp-content/uploads/2025/05/New-Project-26.png',
        ];
        // Pobierz dane NPC/przeciwnika
        $npc = get_post($npc_id);
        $opponent = [
            'name' => $npc ? $npc->post_title : 'Przeciwnik',
            'hp' => 100,
            'maxHp' => 100,
            'img' => 'http://seb.soeasy.it/wp-content/uploads/2025/04/Adobe-Express-file.png',
        ];
        return [
            'player' => $player,
            'opponent' => $opponent,
        ];
    }
}
