<?php

/**
 * Minimalistyczny sidebar gry
 */

// Sprawdź czy użytkownik jest zalogowany
if (!is_user_logged_in()) {
    return;
}

$current_user_id = get_current_user_id();

try {
    // Pobierz dane użytkownika z GameResourceManager
    $gameResourceManager = new GameResourceManager();
    $userData = $gameResourceManager->getUserData($current_user_id);

    if (!$userData) {
        error_log("Brak danych użytkownika dla ID: " . $current_user_id);
        return;
    }

    // Pobierz nick i avatar z danych gry
    $nick = !empty($userData['nick']) ? $userData['nick'] : get_userdata($current_user_id)->display_name;
    $avatar_id = isset($userData['avatar']) ? (int)$userData['avatar'] : 0;

    // Jeśli jest avatar z gry, użyj go, w przeciwnym razie użyj domyślny avatar WordPress
    if ($avatar_id > 0) {
        $avatar_url = wp_get_attachment_image_src($avatar_id, 'thumbnail')[0];
    } else {
        $avatar_url = get_avatar_url($current_user_id, array('size' => 60));
    }

    // Dane zdrowia i energii
    $life = isset($userData['life']) ? (int)$userData['life'] : 100;
    $max_life = isset($userData['max_life']) ? (int)$userData['max_life'] : 100;
    $energy = isset($userData['energy']) ? (int)$userData['energy'] : 100;
    $max_energy = isset($userData['max_energy']) ? (int)$userData['max_energy'] : 100;

    // Reputacja (respect)
    $reputation = isset($userData['reputation']) ? (int)$userData['reputation'] : 1;

    // Oblicz procenty dla pasków
    $life_percent = $max_life > 0 ? ($life / $max_life) * 100 : 0;
    $energy_percent = $max_energy > 0 ? ($energy / $max_energy) * 100 : 0;
} catch (Exception $e) {
    error_log("Błąd w sidebar.php: " . $e->getMessage());
    return;
}
?>

<div class="game-sidebar">
    <!-- Avatar i podstawowe dane -->
    <div class="sidebar-user">
        <div class="user-avatar">
            <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar" class="avatar-img">
        </div>
        <div class="user-info">
            <div class="user-nick"><?php echo esc_html($nick); ?></div>
            <div class="user-respect">Szacunek: <?php echo $reputation; ?></div>
        </div>
    </div>

    <!-- Paski zdrowia i energii -->
    <div class="sidebar-vitals">
        <div class="vital-bar">
            <div class="vital-label">
                <span>Życie</span>
                <span class="vital-values"><?php echo $life . '/' . $max_life; ?></span>
            </div>
            <div class="vital-progress">
                <div class="vital-fill health-fill" style="width: <?php echo $life_percent; ?>%"></div>
            </div>
        </div>

        <div class="vital-bar">
            <div class="vital-label">
                <span>Energia</span>
                <span class="vital-values"><?php echo $energy . '/' . $max_energy; ?></span>
            </div>
            <div class="vital-progress">
                <div class="vital-fill energy-fill" style="width: <?php echo $energy_percent; ?>%"></div>
            </div>
        </div>
    </div> <!-- Nawigacja -->
    <nav class="sidebar-navigation">
        <a href="/rejon" class="nav-item" title="Rejon">
            <img src="<?php echo PNG; ?>/spacer.png" alt="Rejon" class="nav-icon">
            <span>Rejon</span>
        </a>

        <a href="/backpack" class="nav-item" title="Plecak">
            <img src="<?php echo PNG; ?>/plecak.png" alt="Plecak" class="nav-icon">
            <span>Plecak</span>
        </a>

        <a href="/user/me" class="nav-item" title="Postać">
            <img src="<?php echo PNG; ?>/postac.png" alt="Postać" class="nav-icon">
            <span>Postać</span>
        </a>

        <a href="/zadania" class="nav-item" title="Zadania">
            <img src="<?php echo PNG; ?>/pin.png" alt="Zadania" class="nav-icon">
            <span>Zadania</span>
        </a>

        <a href="/ustawienia" class="nav-item" title="Ustawienia">
            <img src="<?php echo PNG; ?>/ustawienia.png" alt="Ustawienia" class="nav-icon">
            <span>Ustawienia</span>
        </a>
    </nav>
</div>