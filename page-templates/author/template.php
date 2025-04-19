<div class="author-panel-tabs">
    <ul class="tab-navigation">
        <li class="tab-item active" data-tab="profil">Profil</li>
        <li class="tab-item" data-tab="statystyki">Statystyki</li>
        <li class="tab-item" data-tab="umiejetnosci">Umiejętności</li>
    </ul>

    <div class="tab-content-author">
        <!-- Tab Profil -->
        <div id="profil" class="tab-pane active">
            <h2>Profil postaci</h2>
            <div class="profile-details">
                <div class="profile-section">
                    <h3>Informacje podstawowe</h3>
                    <div class="profile-field">
                        <span class="label">Nick:</span>
                        <span class="value"><?php echo esc_html(get_field('nick', 'user_' . $user_id) ?: $current_user->display_name); ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="label">Klasa postaci:</span>
                        <span class="value"><?php echo $user_class ? esc_html($user_class['label']) : 'Brak klasy'; ?></span>
                    </div>
                    <!-- Tutaj możesz dodać więcej pól związanych z profilem -->
                </div>

                <div class="profile-section">
                    <h3>Historia postaci</h3>
                    <div class="story-content">
                        <?php echo get_field('story', 'user_' . $user_id) ?: 'Brak historii postaci'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Statystyki -->
        <div id="statystyki" class="tab-pane">
            <h2>Statystyki</h2>

            <?php
            // Pobierz punkty nauki
            if (function_exists('get_field')) {
                $progress = get_field('progress', 'user_' . $user_id);
                $learning_points = isset($progress['learning_points']) ? $progress['learning_points'] : 0;

            ?>
                <div class="learning-points-info">
                    <strong>Dostępne punkty nauki:</strong> <?php echo intval($learning_points); ?>
                </div>
            <?php
            }
            ?>

            <div class="stats-container">
                <?php
                // Pobierz statystyki
                if (function_exists('get_field')) {
                    $vitality_data = get_field('vitality', 'user_' . $user_id);
                    $stats = get_field('stats', 'user_' . $user_id);
                }
                if ($stats && is_array($stats)) :
                ?>
                    <div class="stats-section">
                        <h3>Atrybuty</h3>
                        <div class="stats-grid">
                            <div class="stat-item" data-stat="strength">
                                <span class="stat-label">Siła:</span>
                                <span class="stat-value"><?php echo isset($stats['strength']) ? intval($stats['strength']) : 0; ?></span>
                                <?php if ($learning_points > 0): ?>
                                    <button class="stat-upgrade-btn" data-stat="strength">+</button>
                                <?php endif; ?>
                            </div>
                            <div class="stat-item" data-stat="vitality_stat">
                                <span class="stat-label">Wytrzymałość:</span>
                                <span class="stat-value"><?php echo isset($stats['vitality']) ? intval($stats['vitality']) : 0; ?></span>
                                <?php if ($learning_points > 0): ?>
                                    <button class="stat-upgrade-btn" data-stat="vitality_stat">+</button>
                                <?php endif; ?>
                            </div>
                            <div class="stat-item" data-stat="dexterity">
                                <span class="stat-label">Zręczność:</span>
                                <span class="stat-value"><?php echo isset($stats['dexterity']) ? intval($stats['dexterity']) : 0; ?></span>
                                <?php if ($learning_points > 0): ?>
                                    <button class="stat-upgrade-btn" data-stat="dexterity">+</button>
                                <?php endif; ?>
                            </div>
                            <div class="stat-item" data-stat="perception">
                                <span class="stat-label">Percepcja:</span>
                                <span class="stat-value"><?php echo isset($stats['perception']) ? intval($stats['perception']) : 0; ?></span>
                                <?php if ($learning_points > 0): ?>
                                    <button class="stat-upgrade-btn" data-stat="perception">+</button>
                                <?php endif; ?>
                            </div>
                            <div class="stat-item" data-stat="technical">
                                <span class="stat-label">Zdolności manualne:</span>
                                <span class="stat-value"><?php echo isset($stats['technical']) ? intval($stats['technical']) : 0; ?></span>
                                <?php if ($learning_points > 0): ?>
                                    <button class="stat-upgrade-btn" data-stat="technical">+</button>
                                <?php endif; ?>
                            </div>
                            <div class="stat-item" data-stat="charisma">
                                <span class="stat-label">Cwaniactwo:</span>
                                <span class="stat-value"><?php echo isset($stats['charisma']) ? intval($stats['charisma']) : 0; ?></span>
                                <?php if ($learning_points > 0): ?>
                                    <button class="stat-upgrade-btn" data-stat="charisma">+</button>
                                <?php endif; ?>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Maksymalne Życie:</span>
                                <span class="stat-value">
                                    <?php

                                    echo isset($vitality_data['max_life']) ? intval($vitality_data['max_life']) : 0;
                                    ?>
                                </span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Maksymalna energia:</span>
                                <span class="stat-value">
                                    <?php
                                    echo isset($vitality_data['max_energy']) ? intval($vitality_data['max_energy']) : 0;
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="stats_upgrade_nonce" value="<?php echo wp_create_nonce('stats_upgrade_nonce'); ?>">
                <?php
                endif; ?>
            </div>
        </div>

        <!-- Tab Umiejętności -->
        <div id="umiejetnosci" class="tab-pane">
            <h2>Umiejętności</h2>
            <div class="skills-container">
                <?php
                // Pobierz umiejętności
                if (function_exists('get_field')) {
                    $skills = get_field('skills', 'user_' . $user_id);
                    if ($skills && is_array($skills)) :
                ?>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-label">Walka:</span>
                                <span class="stat-value"><?php echo isset($skills['combat']) ? intval($skills['combat']) : 0; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Kradzież:</span>
                                <span class="stat-value"><?php echo isset($skills['steal']) ? intval($skills['steal']) : 0; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Produkcja:</span>
                                <span class="stat-value"><?php echo isset($skills['craft']) ? intval($skills['craft']) : 0; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Handel:</span>
                                <span class="stat-value"><?php echo isset($skills['trade']) ? intval($skills['trade']) : 0; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Relacje:</span>
                                <span class="stat-value"><?php echo isset($skills['relations']) ? intval($skills['relations']) : 0; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Uliczna wiedza:</span>
                                <span class="stat-value"><?php echo isset($skills['street']) ? intval($skills['street']) : 0; ?></span>
                            </div>
                        </div>
                    <?php
                    else:
                    ?>
                        <p>Brak umiejętności do wyświetlenia.</p>
                <?php
                    endif;
                } else {
                    echo '<p>Nie można wyświetlić umiejętności - plugin ACF nie jest aktywny.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>