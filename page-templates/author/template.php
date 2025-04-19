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
                <h3 class="learning-points-info">
                    <strong>Dostępne punkty nauki:</strong> <?php echo intval($learning_points); ?>
                </h3>
            <?php
            }
            ?>

            <div class="stats-container">
                <?php
                // Pobierz statystyki
                if (function_exists('get_field')) {
                    $stats = get_field('stats', 'user_' . $user_id);
                }
                if ($stats && is_array($stats)) :
                ?>
                    <div class="stats-section">
                        <h3>Statystyki</h3>
                        <div class="stats-grid">
                            <?php foreach ($attributes_data as $stat_key => $stat_info): ?>
                                <div class="stat-item" data-stat="<?php echo $stat_key; ?>">
                                    <span class="stat-label">
                                        <?php echo $stat_info['label']; ?>:
                                        <?php if (!empty($stat_info['instructions'])): ?>
                                            <span class="info-icon tooltip"><?php echo '?'; ?>
                                                <span class="tooltip-text"><?php echo esc_html($stat_info['instructions']); ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="stat-value"><?php echo $stat_info['value']; ?></span>
                                    <?php if ($learning_points > 0): ?>
                                        <button class="stat-upgrade-btn" data-stat="<?php echo $stat_key; ?>">+</button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                endif; ?>
            </div>
            <div class="stats-container">
                <?php
                // Pobierz statystyki
                if (function_exists('get_field')) {
                    $vitality_data = get_field('vitality', 'user_' . $user_id);
                }
                if ($vitality_data && is_array($vitality_data)) :
                ?>
                    <div class="stats-section">
                        <h3>Witalność</h3>
                        <div class="stats-grid">
                            <?php foreach ($additional_stats as $stat_key => $stat_info): ?>
                                <div class="stat-item">
                                    <span class="stat-label">
                                        <?php echo $stat_info['label']; ?>:
                                        <?php if (!empty($stat_info['instructions'])): ?>
                                            <span class="info-icon tooltip"><?php echo '?'; ?>
                                                <span class="tooltip-text"><?php echo esc_html($stat_info['instructions']); ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="stat-value"><?php echo $stat_info['value']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                endif; ?>
            </div>
        </div>

        <!-- Tab Umiejętności -->
        <div id="umiejetnosci" class="tab-pane">
            <h2>Umiejętności</h2>
            <div class="skills-container">
                <?php if ($skills && is_array($skills)): ?>
                    <div class="stats-grid">
                        <?php foreach ($skills_data as $skill_key => $skill_info): ?>
                            <div class="stat-item">
                                <span class="stat-label">
                                    <?php echo $skill_info['label']; ?>:
                                    <?php if (!empty($skill_info['instructions'])): ?>
                                        <span class="info-icon tooltip"><?php echo '?'; ?>
                                            <span class="tooltip-text"><?php echo esc_html($skill_info['instructions']); ?></span>
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <span class="stat-value"><?php echo $skill_info['value']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Brak umiejętności do wyświetlenia.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>